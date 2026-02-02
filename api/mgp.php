<?php
require 'db.php'; require 'helper.php';
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 1. GET DATA
if($action == 'getData') {
    $res = $conn->query("SELECT * FROM mgp_transactions ORDER BY id DESC LIMIT 50");
    $data = [];
    while($row = $res->fetch_assoc()) {
        $include = false;
        if(in_array($input['role'], ['Administrator','Management','Chief Security','Security'])) $include = true;
        elseif($row['username'] == $input['username']) $include = true;
        if($include) {
            $row['timestamp'] = $row['created_at']; $row['id'] = $row['req_id'];
            $row['itemName'] = $row['item_name'];
            $row['appMgmt'] = $row['app_mgmt']; $row['appChief'] = $row['app_chief'];
            $data[] = $row;
        }
    }
    sendJson($data);
}

// 2. SUBMIT
if($action == 'submit') {
    $reqId = "MGP-".time();
    $sql = "INSERT INTO mgp_transactions (req_id, username, department, item_name, qty, unit, owner, destination, remarks, status, app_mgmt, app_chief, is_returnable) VALUES ('$reqId', '{$input['username']}', '{$input['department']}', '{$input['itemName']}', '{$input['qty']}', '{$input['unit']}', '{$input['owner']}', '{$input['destination']}', '{$input['remarks']}', 'Pending Management', 'Pending', 'Pending', '{$input['isReturnable']}')";
    
    if($conn->query($sql)) {
        // Notif ke Management
        $phones = getPhones($conn, 'Management');
        $msg = "📦 *MGP - NEW REQUEST*\n" .
               "--------------------------------\n" .
               "Izin Keluar Barang:\n\n" .
               "👤 *User:* {$input['username']}\n" .
               "📦 *Barang:* {$input['itemName']} ({$input['qty']} {$input['unit']})\n" .
               "🎯 *Tujuan:* {$input['destination']}\n" .
               "📝 *Ket:* {$input['remarks']}\n\n" .
               "👉 _Mohon Approval Management._";
        foreach($phones as $ph) sendWA($ph, $msg);
        
        sendJson(['success'=>true]);
    } else {
        sendJson(['success'=>false]);
    }
}

// 3. UPDATE STATUS
if($action == 'updateStatus') {
    $id = $input['id']; $act = $input['act']; $u = $input['user']; $extra = $input['extra'] ?? [];
    
    $qry = $conn->query("SELECT username, item_name FROM mgp_transactions WHERE req_id = '$id'");
    $reqData = $qry->fetch_assoc();
    $userPhone = getUserPhone($conn, $reqData['username']);

    if($act == 'approve_mgmt') {
        $conn->query("UPDATE mgp_transactions SET status='Pending Chief', app_mgmt='Approved by {$u['fullname']}' WHERE req_id='$id'");
        // Notif ke Chief Security
        $phones = getPhones($conn, 'Chief Security');
        foreach($phones as $ph) sendWA($ph, "🛡️ *MGP - VALIDASI*\nMgmt Approved.\nUser: {$reqData['username']}\nBarang: {$reqData['item_name']}\n\nMohon validasi Chief.");
    }
    if($act == 'approve_chief') {
        $conn->query("UPDATE mgp_transactions SET status='Approved', app_chief='Approved by {$u['fullname']}' WHERE req_id='$id'");
        
        // Notif ke User
        if($userPhone) sendWA($userPhone, "✅ *MGP - APPROVED*\nIzin barang disetujui (Final).\nBarang: {$reqData['item_name']}\n\n👮 _Tunjukkan ke Security._");
        
        // Notif ke Security
        $phones = getPhones($conn, 'Security');
        foreach($phones as $ph) sendWA($ph, "👮 *MGP INFO*\nBarang Approved akan keluar.\nItem: {$reqData['item_name']}");
    }
    if($act == 'reject') {
        $conn->query("UPDATE mgp_transactions SET status='Rejected' WHERE req_id='$id'");
        if($userPhone) sendWA($userPhone, "❌ *MGP - REJECTED*\nIzin barang ditolak.");
    }
    if($act == 'security_out') {
        $url = saveBase64Image($extra['photo'], "OUT_$id", "mgp");
        $conn->query("UPDATE mgp_transactions SET status='Out / On Loan', sec_out=NOW(), photo_out='$url' WHERE req_id='$id'");
        if($userPhone) sendWA($userPhone, "📤 *MGP - OUT*\nBarang tercatat keluar.\nCatatan: " . ($extra['notes']??'-'));
    }
    if($act == 'security_in') {
        $url = saveBase64Image($extra['photo'], "IN_$id", "mgp");
        $conn->query("UPDATE mgp_transactions SET status='Returned', sec_in=NOW(), photo_in='$url' WHERE req_id='$id'");
        if($userPhone) sendWA($userPhone, "📥 *MGP - RETURNED*\nBarang telah kembali/masuk.");
    }
    sendJson(['success'=>true]);
}
?>