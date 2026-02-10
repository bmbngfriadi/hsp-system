<?php
require 'db.php'; 
require 'helper.php';

// --- CONFIG & HEADERS ---
date_default_timezone_set('Asia/Jakarta'); 
$conn->query("SET time_zone = '+07:00'");
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// --- 1. GET MASTER ITEMS (Untuk Dropdown Pilihan) ---
if($action == 'inventory') {
    $res = $conn->query("SELECT * FROM atk_items ORDER BY name ASC");
    sendJson($res->fetch_all(MYSQLI_ASSOC));
}

// --- 2. GET DEPT STOCK (Untuk mengisi Last Stock otomatis) ---
if($action == 'getDeptStock') {
    $role = $input['role'] ?? '';
    $userDept = $conn->real_escape_string($input['department']);
    $targetDept = $conn->real_escape_string($input['targetDept'] ?? 'All');

    $sql = "SELECT * FROM atk_dept_stock WHERE 1=1";

    // Filter Role: User biasa hanya bisa lihat dept sendiri
    if (!in_array($role, ['HRGA', 'Administrator', 'PlantHead'])) {
        $sql .= " AND department = '$userDept'";
    } else {
        // HRGA bisa lihat semua atau filter tertentu
        if ($targetDept !== 'All') {
            $sql .= " AND department = '$targetDept'";
        }
    }

    $sql .= " ORDER BY department ASC, item_name ASC";
    $res = $conn->query($sql);
    sendJson($res->fetch_all(MYSQLI_ASSOC));
}

// --- 3. GET LIST DEPARTEMEN (Untuk Filter HRGA) ---
if($action == 'getStockDepts') {
    $res = $conn->query("SELECT DISTINCT department FROM atk_dept_stock ORDER BY department ASC");
    $data = [];
    while($r = $res->fetch_assoc()) $data[] = $r['department'];
    sendJson($data);
}

// --- 4. GET DATA REQUESTS (History) ---
if($action == 'getData' || $action == 'exportData') {
    $userRole = $input['role'] ?? '';
    $userDept = $conn->real_escape_string($input['department'] ?? '');

    $sql = "SELECT * FROM atk_requests WHERE 1=1";
    $globalViewRoles = ['HRGA', 'PlantHead', 'Administrator'];

    if (!in_array($userRole, $globalViewRoles)) {
        $sql .= " AND department = '$userDept'";
    }

    if ($action == 'exportData') {
        if (!empty($input['startDate']) && !empty($input['endDate'])) {
            $start = $input['startDate'] . " 00:00:00";
            $end = $input['endDate'] . " 23:59:59";
            $sql .= " AND created_at BETWEEN '$start' AND '$end'";
        }
        $sql .= " ORDER BY created_at ASC";
    } else {
        $sql .= " ORDER BY id DESC LIMIT 50";
    }

    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $row['timestamp'] = $row['created_at']; 
        $row['id'] = $row['req_id'];
        $row['items'] = json_decode($row['items_json']);
        $row['appHead'] = $row['app_head']; 
        $row['appHrga'] = $row['app_hrga'];
        $data[] = $row;
    }
    sendJson($data);
}

// --- 5. SUBMIT NEW REQUEST (AUTO POTONG STOCK) ---
if($action == 'submit') {
    $reqId = "ATK-" . time();
    $username = $input['username'];
    $fullname = $input['fullname'];
    $dept = $input['department'];
    $period = $conn->real_escape_string($input['period']); // Capture Period
    $reason = $conn->real_escape_string($input['reason']);
    $itemsArr = $input['items']; // Array items
    $itemsJson = json_encode($itemsArr);
    
    // Logic Approval Target
    $targetRoles = ['SectionHead', 'TeamLeader']; 
    if (strtoupper($dept) === 'HRGA') {
        $targetRoles = ['PlantHead', 'TeamLeader'];
    }

    $conn->begin_transaction();

    try {
        // A. Insert Request
        $stmt = $conn->prepare("INSERT INTO atk_requests (req_id, username, fullname, department, period, items_json, reason, status, app_head, app_hrga, reject_reason) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Head', 'Pending', 'Pending', '')");
        $stmt->bind_param("sssssss", $reqId, $username, $fullname, $dept, $period, $itemsJson, $reason);
        $stmt->execute();

        // B. Update Stock (Potong Pemakaian)
        foreach($itemsArr as $it) {
            $usage = intval($it['last_usage']);
            if($usage > 0) {
                $iName = $conn->real_escape_string($it['name']);
                // Kurangi stok. GREATEST(qty - usage, 0) memastikan tidak minus
                $sqlDed = "UPDATE atk_dept_stock 
                           SET qty = GREATEST(qty - $usage, 0), last_updated = NOW() 
                           WHERE department = '$dept' AND item_name = '$iName'";
                $conn->query($sqlDed);
            }
        }

        $conn->commit();

        // C. Notifikasi WA
        $approverPhones = getPhones($conn, $targetRoles, $dept);
        $msgHead = "📝 *ATK - NEW REQUEST*\n" .
                   "--------------------------------\n" .
                   "👤 *User:* $fullname\n" .
                   "📅 *Periode:* $period\n" .
                   "💬 *Alasan:* $reason\n\n" .
                   "📦 *BARANG (Req | Usage | Sisa Stock):*\n" . formatItemListDetailed($itemsJson) . "\n" .
                   "👉 _Login Portal untuk Approve._";
        
        foreach($approverPhones as $ph) sendWA($ph, $msgHead);
        
        $userPhone = getUserPhone($conn, $username);
        if($userPhone) sendWA($userPhone, "✅ *ATK - TERKIRIM*\nRequest Periode $period berhasil dibuat.\nStok pemakaian telah dipotong otomatis.");

        sendJson(['success'=>true, 'message'=>'Request Submitted & Stock Updated']);

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(['success'=>false, 'message'=>'Error: ' . $e->getMessage()]);
    }
}

// --- 6. UPDATE STATUS & CONFIRM RECEIVE (TAMBAH STOCK) ---
if($action == 'updateStatus') {
    $id = $input['id'];
    $act = $input['act'];
    $role = $input['role'] ?? ''; 
    $fullname = $input['fullname'] ?? ''; 
    
    $qry = $conn->query("SELECT * FROM atk_requests WHERE req_id = '$id'");
    if($qry->num_rows == 0) sendJson(['success'=>false, 'message'=>'Data not found']);
    
    $row = $qry->fetch_assoc();
    $reqUser = $row['username'];
    $reqDept = $row['department'];
    $reqPhone = getUserPhone($conn, $reqUser);
    $items = json_decode($row['items_json'], true);

    // --- CONFIRM RECEIVE: Barang diterima -> Tambah Stok ---
    if($act == 'confirmReceive') {
        if($row['username'] !== $input['username']) sendJson(['success'=>false, 'message'=>'Unauthorized']);
        if($row['status'] !== 'Approved') sendJson(['success'=>false, 'message'=>'Status must be Approved first']);

        $now = date('Y-m-d H:i:s');
        
        $conn->begin_transaction();
        try {
            // 1. Update Request
            $conn->query("UPDATE atk_requests SET status='Completed', received_at='$now', received_by='$fullname' WHERE req_id='$id'");
            
            // 2. Insert/Update Stock (ADD Qty Request)
            foreach($items as $it) {
                $iName = $conn->real_escape_string($it['name']);
                $iQty = intval($it['qty']); // Jumlah yang direquest & disetujui
                $iUnit = $conn->real_escape_string($it['unit']);
                
                $sqlStock = "INSERT INTO atk_dept_stock (department, item_name, qty, unit, last_updated) 
                             VALUES ('$reqDept', '$iName', $iQty, '$iUnit', '$now') 
                             ON DUPLICATE KEY UPDATE qty = qty + $iQty, last_updated = '$now'";
                $conn->query($sqlStock);
            }
            $conn->commit();
            
            if($reqPhone) sendWA($reqPhone, "📦 *ATK - DITERIMA*\nBarang telah dikonfirmasi.\nStok departemen telah ditambahkan.");
            sendJson(['success'=>true, 'message'=>'Items Received & Stock Added']);

        } catch (Exception $e) {
            $conn->rollback();
            sendJson(['success'=>false, 'message'=>$e->getMessage()]);
        }
        return;
    }

    // Logic Approval & Cancel
    if($act == 'cancel') {
        if($row['username'] !== $input['username']) sendJson(['success'=>false, 'message'=>'Unauthorized']);
        if($row['status'] !== 'Pending Head') sendJson(['success'=>false, 'message'=>'Cannot cancel processed request']);
        // Note: Stok yang sudah terpotong saat submit TIDAK dikembalikan otomatis saat cancel, 
        // karena asumsinya "Pemakaian" sudah terjadi secara fisik. 
        // Jika ingin dikembalikan, logic tambahan diperlukan di sini.
        // Untuk saat ini kita biarkan terpotong karena itu pelaporan pemakaian.
        $conn->query("UPDATE atk_requests SET status='Canceled', reject_reason='Canceled by User' WHERE req_id='$id'");
        sendJson(['success'=>true, 'message'=>'Request Canceled']);
    }

    if($act == 'approve') {
        if ($row['status'] == 'Pending Head') {
             if (strtoupper($row['department']) == 'HRGA') {
                $conn->query("UPDATE atk_requests SET status='Approved', app_head='Approved by $fullname (L1)', app_hrga='Auto-Approved (Internal)' WHERE req_id='$id'");
                if($reqPhone) sendWA($reqPhone, "🎉 *ATK - APPROVED*\nPermintaan disetujui.\n📦 *Items:*\n".formatItemListDetailed($row['items_json']));
            } else {
                $conn->query("UPDATE atk_requests SET status='Pending HRGA', app_head='Approved by $fullname' WHERE req_id='$id'");
                $hrgaPhones = getPhones($conn, 'HRGA');
                foreach($hrgaPhones as $ph) sendWA($ph, "⏳ *ATK - VERIFIKASI (L2)*\nUser: {$row['fullname']}\nApproved by Head.");
            }
        }
        elseif ($role == 'HRGA' && $row['status'] == 'Pending HRGA') {
            $conn->query("UPDATE atk_requests SET status='Approved', app_hrga='Approved by $fullname' WHERE req_id='$id'");
            if($reqPhone) sendWA($reqPhone, "🎉 *ATK - READY*\nPermintaan disetujui HRGA.\nSilakan ambil barang di GA/Stationary.");
        }
        sendJson(['success'=>true]);
    }

    if($act == 'reject') {
        $reason = $input['reason'] ?? '-';
        $conn->query("UPDATE atk_requests SET status='Rejected', reject_reason='$reason' WHERE req_id='$id'");
        if($reqPhone) sendWA($reqPhone, "❌ *ATK - DITOLAK*\nOleh: $fullname\nAlasan: $reason");
        sendJson(['success'=>true]);
    }
}

// Helper Format WA Detail
function formatItemListDetailed($itemsJson) {
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) return "-";
    $txt = "";
    foreach($items as $it) {
        $stock = isset($it['current_stock']) ? $it['current_stock'] : 0;
        $used = isset($it['last_usage']) ? $it['last_usage'] : 0;
        $sisa = $stock - $used;
        $txt .= "• {$it['name']}\n   Req: {$it['qty']} | Used: {$used} | Sisa: {$sisa}\n";
    }
    return $txt;
}
?>