<?php
require 'db.php'; 
require 'helper.php';

// --- SYNC WAKTU WIB (REALTIME) ---
// Set timezone PHP ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta'); 
// Set timezone sesi MySQL ke +07:00 (WIB) agar 'created_at' database sinkron
$conn->query("SET time_zone = '+07:00'");
// ---------------------------------

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

// --- 2. GET DATA TRANSACTIONS (VISIBILITY LOGIC FIXED) ---
if($action == 'getData') {
    // Ambil parameter user dari frontend
    $userRole = $input['role'] ?? '';
    $userDept = $conn->real_escape_string($input['department'] ?? '');

    // Base Query
    $sql = "SELECT * FROM atk_requests";

    // LOGIC VISIBILITY:
    // Hanya Role HRGA, PlantHead (dan Admin) yang bisa lihat SEMUA data.
    // Role lain (User, TeamLeader, SectionHead) hanya bisa melihat data departemennya sendiri.
    $globalViewRoles = ['HRGA', 'PlantHead', 'Administrator'];

    if (!in_array($userRole, $globalViewRoles)) {
        // Filter by Department
        $sql .= " WHERE department = '$userDept'";
    }

    $sql .= " ORDER BY id DESC LIMIT 50";

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
    
    // Generate Text List Barang
    $txtItems = formatItemList($itemsJson, 'normal');

    // Tentukan Level 1 Approver
    // Default: SectionHead -> Fallback: TeamLeader
    // Khusus Dept HRGA: PlantHead -> Fallback: TeamLeader
    $targetRoles = ['SectionHead', 'TeamLeader']; 
    if (strtoupper($dept) === 'HRGA') {
        $targetRoles = ['PlantHead', 'TeamLeader'];
    }

    $stmt = $conn->prepare("INSERT INTO atk_requests (req_id, username, fullname, department, items_json, reason, status, app_head, app_hrga, reject_reason) VALUES (?, ?, ?, ?, ?, ?, 'Pending Head', 'Pending', 'Pending', '')");
    $stmt->bind_param("ssssss", $reqId, $username, $fullname, $dept, $itemsJson, $reason);
    
    if($stmt->execute()) {
        // A. Notif ke Approver Level 1
        $approverPhones = getPhones($conn, $targetRoles, $dept);
        $msgHead = "📝 *ATK - NEW REQUEST*\n" .
                   "--------------------------------\n" .
                   "Mohon persetujuan untuk permintaan berikut:\n\n" .
                   "👤 *Requester:* $fullname\n" .
                   "🏢 *Dept:* $dept\n" .
                   "💬 *Alasan:* $reason\n\n" .
                   "📦 *DAFTAR BARANG:*\n" . $txtItems . "\n" .
                   "👉 _Login ke Portal untuk Approve/Reject._";
        
        foreach($approverPhones as $ph) sendWA($ph, $msgHead);

        // B. Notif Konfirmasi ke Requester
        $userPhone = getUserPhone($conn, $username);
        if($userPhone) {
            $msgUser = "✅ *ATK - TERKIRIM*\n" .
                       "--------------------------------\n" .
                       "Request Anda berhasil dibuat.\n\n" .
                       "🆔 *ID:* $reqId\n" .
                       "⏳ *Status:* Menunggu Approval Atasan\n\n" .
                       "📦 *BARANG:*\n" . $txtItems;
            sendWA($userPhone, $msgUser);
        }
        
        sendJson(['success'=>true, 'message'=>'Request Submitted']);
    } else {
        sendJson(['success'=>false, 'message'=>$conn->error]);
    }
}

// --- 4. UPDATE STATUS (Approve / Reject / Cancel) ---
if($action == 'updateStatus') {
    $id = $input['id'];
    $act = $input['act'];
    $role = $input['role'] ?? ''; 
    $fullname = $input['fullname'] ?? ''; 
    
    $qry = $conn->query("SELECT * FROM atk_requests WHERE req_id = '$id'");
    if($qry->num_rows == 0) sendJson(['success'=>false, 'message'=>'Data not found']);
    
    $row = $qry->fetch_assoc();
    $reqUser = $row['username'];
    $reqFullname = $row['fullname'];
    $reqDept = $row['department'];
    $reqPhone = getUserPhone($conn, $reqUser);
    
    $txtItems = formatItemList($row['items_json'], 'normal');

    // >> CASE: CANCEL
    if($act == 'cancel') {
        if($row['username'] !== $input['username']) sendJson(['success'=>false, 'message'=>'Unauthorized']);
        if($row['status'] !== 'Pending Head') sendJson(['success'=>false, 'message'=>'Cannot cancel processed request']);

        $conn->query("UPDATE atk_requests SET status='Canceled', reject_reason='Canceled by User' WHERE req_id='$id'");
        
        if($reqPhone) {
            sendWA($reqPhone, "🚫 *ATK - CANCELED*\nAnda membatalkan request #$id.\n\n📦 *Items:*\n$txtItems");
        }
        sendJson(['success'=>true, 'message'=>'Request Canceled']);
    }

    // >> CASE: APPROVE
    if($act == 'approve') {
        
        // APPROVAL LEVEL 1 (Head / Leader)
        $l1Roles = ['SectionHead', 'TeamLeader', 'PlantHead'];
        if (in_array($role, $l1Roles) && $row['status'] == 'Pending Head') {
            
            // SPECIAL: Dept HRGA (Bypass L2)
            if (strtoupper($reqDept) == 'HRGA') {
                $conn->query("UPDATE atk_requests SET status='Approved', app_head='Approved by $fullname (L1)', app_hrga='Auto-Approved (Internal)' WHERE req_id='$id'");
                
                if($reqPhone) {
                    $msg = "🎉 *ATK - APPROVED*\n" .
                           "--------------------------------\n" .
                           "Permintaan disetujui. Permintaan ATK anda akan segera diproses.\n\n" .
                           "📦 *BARANG:*\n" . $txtItems;
                    sendWA($reqPhone, $msg);
                }
            } else {
                // NORMAL: Lanjut ke L2 (HRGA)
                $conn->query("UPDATE atk_requests SET status='Pending HRGA', app_head='Approved by $fullname' WHERE req_id='$id'");
                
                $hrgaPhones = getPhones($conn, 'HRGA');
                $msgHRGA = "⏳ *ATK - VERIFIKASI (L2)*\n" .
                           "--------------------------------\n" .
                           "Request Approved by Section Head Dept. Mohon Verifikasi Permintaan ATK.\n\n" .
                           "👤 *User:* $reqFullname ($reqDept)\n" .
                           "📦 *BARANG:*\n" . $txtItems . "\n" .
                           "👉 _Login Portal untuk Finalisasi._";
                foreach($hrgaPhones as $ph) sendWA($ph, $msgHRGA);

                if($reqPhone) sendWA($reqPhone, "✅ *ATK - L1 APPROVED*\nMenunggu verifikasi HRGA.\n\n📦 *Items:*\n$txtItems");
            }
        }
        
        // APPROVAL LEVEL 2 (HRGA)
        elseif ($role == 'HRGA' && $row['status'] == 'Pending HRGA') {
            $conn->query("UPDATE atk_requests SET status='Approved', app_hrga='Approved by $fullname' WHERE req_id='$id'");
            
            if($reqPhone) {
                $msg = "🎉 *ATK - DIPROSES*\n" .
                       "--------------------------------\n" .
                       "Permintaan Anda akan segera diproses, Tunggu informasi lebih lanjut dari tim HRGA.\n\n" .
                       "🆔 *ID:* $id\n" .
                       "📍 *Lokasi:* Ruang GA/Stationary\n" .
                       "🕒 *Waktu:* Segera ambil pada jam kerja saat ATK sudah datang :)\n\n" .
                       "📦 *BARANG:*\n" . $txtItems;
                sendWA($reqPhone, $msg);
            }
        }
        sendJson(['success'=>true]);
    }

    // >> CASE: REJECT
    if($act == 'reject') {
        $reason = $input['reason'] ?? '-';
        $sql = "UPDATE atk_requests SET status='Rejected', reject_reason='$reason'";
        
        if ($row['status'] == 'Pending Head') $sql .= ", app_head='Rejected by $fullname'";
        elseif ($row['status'] == 'Pending HRGA') $sql .= ", app_hrga='Rejected by $fullname'";
        $sql .= " WHERE req_id='$id'";
        
        $conn->query($sql);

        if($reqPhone) {
            $msg = "❌ *ATK - REQUEST DITOLAK*\n" .
                   "--------------------------------\n" .
                   "Maaf, permintaan Anda ditolak.\n\n" .
                   "🚫 *Oleh:* $fullname\n" .
                   "💬 *Alasan:* $reason\n\n" .
                   "📦 *BARANG:*\n" . $txtItems;
            sendWA($reqPhone, $msg);
        }
        sendJson(['success'=>true]);
    }
}

// --- 5. EDIT REQUEST (REVISI) ---
if($action == 'edit') {
    $reqId = $input['id'];
    $newItemsArr = $input['items'];
    $newReason = $conn->real_escape_string($input['reason']);
    
    $qry = $conn->query("SELECT * FROM atk_requests WHERE req_id = '$reqId'");
    if($qry->num_rows == 0) sendJson(['success'=>false, 'message'=>'Data not found']);
    
    $oldRow = $qry->fetch_assoc();
    
    if($oldRow['username'] !== $input['username']) sendJson(['success'=>false, 'message'=>'Unauthorized']);
    if($oldRow['status'] !== 'Pending Head') sendJson(['success'=>false, 'message'=>'Cannot edit processed request']);

    $txtOld = formatItemList($oldRow['items_json'], 'strikethrough'); 
    $txtNew = "";
    foreach($newItemsArr as $n) {
        $txtNew .= "• {$n['name']} ({$n['qty']} {$n['unit']})\n";
    }

    $newJsonItems = json_encode($newItemsArr);
    $sql = "UPDATE atk_requests SET items_json = '$newJsonItems', reason = '$newReason' WHERE req_id = '$reqId'";
    
    if($conn->query($sql)) {
        $targetRoles = ['SectionHead', 'TeamLeader'];
        if (strtoupper($oldRow['department']) === 'HRGA') $targetRoles = ['PlantHead', 'TeamLeader'];
        
        $phones = getPhones($conn, $targetRoles, $oldRow['department']);
        
        $msgRevisi = "♻️ *ATK - REVISI DATA*\n" .
                     "--------------------------------\n" .
                     "User telah mengubah detail request:\n\n" .
                     "👤 *User:* {$oldRow['fullname']}\n" .
                     "🆔 *ID:* $reqId\n\n" .
                     "❌ *SEMULA (DICORET):*\n" . $txtOld . "\n" .
                     "✅ *MENJADI (BARU):*\n" . $txtNew . "\n" .
                     "📝 *Alasan Baru:* $newReason\n\n" .
                     "👉 _Mohon Cek Kembali di Portal._";
        
        foreach($phones as $ph) sendWA($ph, $msgRevisi);

        sendJson(['success'=>true, 'message'=>'Request Updated']);
    } else {
        sendJson(['success'=>false, 'message'=>$conn->error]);
    }
}
?>