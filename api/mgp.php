<?php
require 'db.php'; 
require 'helper.php';

date_default_timezone_set('Asia/Jakarta'); 
$conn->query("SET time_zone = '+07:00'");

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 1. GET DATA (NORMAL & EXPORT)
if($action == 'getData' || $action == 'exportData') {
    $sql = "SELECT * FROM mgp_transactions WHERE 1=1";
    
    // Filter Date Export
    if ($action == 'exportData') {
        if (!empty($input['startDate']) && !empty($input['endDate'])) {
            $start = $input['startDate'];
            $end = $input['endDate'];
            $sql .= " AND created_at BETWEEN '$start 00:00:00' AND '$end 23:59:59'";
        }
        $sql .= " ORDER BY created_at ASC";
    } else {
        $sql .= " ORDER BY id DESC LIMIT 50";
    }

    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $include = false;
        if(in_array($input['role'], ['Administrator','Management','Chief Security','Security', 'HRGA'])) $include = true;
        elseif($row['username'] == $input['username']) $include = true;
        
        // Admin Override for Export
        if($action == 'exportData' && in_array($input['role'], ['Administrator', 'HRGA'])) $include = true;

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
        $phones = getPhones($conn, 'Management');
        $msg = "📦 *MGP - NEW REQUEST*\nUser: {$input['username']}\nItem: {$input['itemName']}\nDest: {$input['destination']}\n👉 Approve di Portal.";
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
        $phones = getPhones($conn, 'Chief Security');
        foreach($phones as $ph) sendWA($ph, "🛡️ *MGP - VALIDASI*\nMgmt Approved.\nItem: {$reqData['item_name']}\nMohon validasi Chief.");
    }
    if($act == 'approve_chief') {
        $conn->query("UPDATE mgp_transactions SET status='Approved', app_chief='Approved by {$u['fullname']}' WHERE req_id='$id'");
        if($userPhone) sendWA($userPhone, "✅ *MGP - APPROVED*\nItem: {$reqData['item_name']}\nTunjukkan ke Security.");
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
        if($userPhone) sendWA($userPhone, "📥 *MGP - RETURNED*\nBarang telah kembali.");
    }
    sendJson(['success'=>true]);
}
?>