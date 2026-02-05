<?php
require 'db.php'; 
require 'helper.php';

// --- SYNC WAKTU WIB (REALTIME) ---
date_default_timezone_set('Asia/Jakarta'); 
$conn->query("SET time_zone = '+07:00'");

// Suppress Error Display agar output bersih JSON
error_reporting(0); 
ini_set('display_errors', 0);

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// --- 1. GET INVENTORY LIST ---
if($action == 'inventory') {
    $res = $conn->query("SELECT * FROM atk_items ORDER BY name ASC");
    sendJson($res->fetch_all(MYSQLI_ASSOC));
}

// --- 2. GET DATA (NORMAL & EXPORT) ---
if($action == 'getData' || $action == 'exportData') {
    $userRole = $input['role'] ?? '';
    $userDept = $conn->real_escape_string($input['department'] ?? '');

    // Base Query
    $sql = "SELECT * FROM atk_requests WHERE 1=1";

    // LOGIC VISIBILITY
    $globalViewRoles = ['HRGA', 'PlantHead', 'Administrator'];

    // Jika bukan role global, filter by dept (kecuali Administrator minta export, biasanya dia mau semua)
    if (!in_array($userRole, $globalViewRoles)) {
        $sql .= " AND department = '$userDept'";
    }

    // LOGIC EXPORT vs VIEW
    if ($action == 'exportData') {
        // Filter Tanggal Export
        if (!empty($input['startDate']) && !empty($input['endDate'])) {
            $start = $input['startDate'] . " 00:00:00";
            $end = $input['endDate'] . " 23:59:59";
            $sql .= " AND created_at BETWEEN '$start' AND '$end'";
        }
        $sql .= " ORDER BY created_at ASC"; // Urutkan dari terlama ke terbaru untuk laporan
    } else {
        $sql .= " ORDER BY id DESC LIMIT 50"; // Limit tampilan web
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

// --- 3. SUBMIT NEW REQUEST ---
if($action == 'submit') {
    $reqId = "ATK-" . time();
    $username = $input['username'];
    $fullname = $input['fullname'];
    $dept = $input['department'];
    $reason = $input['reason'];
    $itemsJson = json_encode($input['items']);
    
    $txtItems = formatItemList($itemsJson, 'normal');

    $targetRoles = ['SectionHead', 'TeamLeader']; 
    if (strtoupper($dept) === 'HRGA') {
        $targetRoles = ['PlantHead', 'TeamLeader'];
    }

    $stmt = $conn->prepare("INSERT INTO atk_requests (req_id, username, fullname, department, items_json, reason, status, app_head, app_hrga, reject_reason) VALUES (?, ?, ?, ?, ?, ?, 'Pending Head', 'Pending', 'Pending', '')");
    $stmt->bind_param("ssssss", $reqId, $username, $fullname, $dept, $itemsJson, $reason);
    
    if($stmt->execute()) {
        $approverPhones = getPhones($conn, $targetRoles, $dept);
        $msgHead = "📝 *ATK - NEW REQUEST*\n" .
                   "--------------------------------\n" .
                   "Mohon persetujuan:\n\n" .
                   "👤 *Requester:* $fullname\n" .
                   "🏢 *Dept:* $dept\n" .
                   "💬 *Alasan:* $reason\n\n" .
                   "📦 *BARANG:*\n" . $txtItems . "\n" .
                   "👉 _Login Portal untuk Approve._";
        
        foreach($approverPhones as $ph) sendWA($ph, $msgHead);

        $userPhone = getUserPhone($conn, $username);
        if($userPhone) {
            $msgUser = "✅ *ATK - TERKIRIM*\n" .
                       "Request berhasil dibuat.\n" .
                       "🆔 *ID:* $reqId\n\n" .
                       "📦 *BARANG:*\n" . $txtItems;
            sendWA($userPhone, $msgUser);
        }
        
        sendJson(['success'=>true, 'message'=>'Request Submitted']);
    } else {
        sendJson(['success'=>false, 'message'=>$conn->error]);
    }
}

// --- 4. UPDATE STATUS ---
if($action == 'updateStatus') {
    $id = $input['id'];
    $act = $input['act'];
    $role = $input['role'] ?? ''; 
    $fullname = $input['fullname'] ?? ''; 
    
    $qry = $conn->query("SELECT * FROM atk_requests WHERE req_id = '$id'");
    if($qry->num_rows == 0) sendJson(['success'=>false, 'message'=>'Data not found']);
    
    $row = $qry->fetch_assoc();
    $reqUser = $row['username'];
    $reqPhone = getUserPhone($conn, $reqUser);
    $txtItems = formatItemList($row['items_json'], 'normal');

    if($act == 'cancel') {
        if($row['username'] !== $input['username']) sendJson(['success'=>false, 'message'=>'Unauthorized']);
        if($row['status'] !== 'Pending Head') sendJson(['success'=>false, 'message'=>'Cannot cancel processed request']);

        $conn->query("UPDATE atk_requests SET status='Canceled', reject_reason='Canceled by User' WHERE req_id='$id'");
        if($reqPhone) sendWA($reqPhone, "🚫 *ATK - CANCELED*\nAnda membatalkan request #$id.");
        sendJson(['success'=>true, 'message'=>'Request Canceled']);
    }

    if($act == 'approve') {
        $l1Roles = ['SectionHead', 'TeamLeader', 'PlantHead'];
        if (in_array($role, $l1Roles) && $row['status'] == 'Pending Head') {
            if (strtoupper($row['department']) == 'HRGA') {
                $conn->query("UPDATE atk_requests SET status='Approved', app_head='Approved by $fullname (L1)', app_hrga='Auto-Approved (Internal)' WHERE req_id='$id'");
                if($reqPhone) sendWA($reqPhone, "🎉 *ATK - APPROVED*\nPermintaan disetujui.\n📦 *Items:*\n$txtItems");
            } else {
                $conn->query("UPDATE atk_requests SET status='Pending HRGA', app_head='Approved by $fullname' WHERE req_id='$id'");
                $hrgaPhones = getPhones($conn, 'HRGA');
                foreach($hrgaPhones as $ph) sendWA($ph, "⏳ *ATK - VERIFIKASI (L2)*\nUser: {$row['fullname']}\nApproved by Head.\n📦 *Items:*\n$txtItems");
                if($reqPhone) sendWA($reqPhone, "✅ *ATK - L1 APPROVED*\nMenunggu verifikasi HRGA.");
            }
        }
        elseif ($role == 'HRGA' && $row['status'] == 'Pending HRGA') {
            $conn->query("UPDATE atk_requests SET status='Approved', app_hrga='Approved by $fullname' WHERE req_id='$id'");
            if($reqPhone) sendWA($reqPhone, "🎉 *ATK - DIPROSES*\nPermintaan akan diproses HRGA.\n🆔 *ID:* $id\n📍 *Lokasi:* GA/Stationary");
        }
        sendJson(['success'=>true]);
    }

    if($act == 'reject') {
        $reason = $input['reason'] ?? '-';
        $sql = "UPDATE atk_requests SET status='Rejected', reject_reason='$reason'";
        if ($row['status'] == 'Pending Head') $sql .= ", app_head='Rejected by $fullname'";
        elseif ($row['status'] == 'Pending HRGA') $sql .= ", app_hrga='Rejected by $fullname'";
        $sql .= " WHERE req_id='$id'";
        $conn->query($sql);
        if($reqPhone) sendWA($reqPhone, "❌ *ATK - DITOLAK*\nOleh: $fullname\nAlasan: $reason");
        sendJson(['success'=>true]);
    }
}

// --- 5. EDIT REQUEST ---
if($action == 'edit') {
    $reqId = $input['id'];
    $newItemsArr = $input['items'];
    $newReason = $conn->real_escape_string($input['reason']);
    
    $qry = $conn->query("SELECT * FROM atk_requests WHERE req_id = '$reqId'");
    if($qry->num_rows == 0) sendJson(['success'=>false, 'message'=>'Data not found']);
    $oldRow = $qry->fetch_assoc();
    
    if($oldRow['username'] !== $input['username']) sendJson(['success'=>false, 'message'=>'Unauthorized']);
    if($oldRow['status'] !== 'Pending Head') sendJson(['success'=>false, 'message'=>'Cannot edit processed request']);

    $newJsonItems = json_encode($newItemsArr);
    $sql = "UPDATE atk_requests SET items_json = '$newJsonItems', reason = '$newReason' WHERE req_id = '$reqId'";
    
    if($conn->query($sql)) {
        sendJson(['success'=>true, 'message'=>'Request Updated']);
    } else {
        sendJson(['success'=>false, 'message'=>$conn->error]);
    }
}
?>