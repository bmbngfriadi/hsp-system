<?php
require 'db.php'; require 'helper.php';
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 1. GET DATA
if ($action == 'getData') {
    $role = $input['role']; $username = $input['username']; $dept = $input['department'];
    $vRes = $conn->query("SELECT plant_plat as plant, merk_tipe as model, status FROM vms_vehicles");
    $vehicles = $vRes->fetch_all(MYSQLI_ASSOC);
    $sql = "SELECT * FROM vms_bookings ORDER BY id DESC LIMIT 50";
    $bRes = $conn->query($sql);
    $bookings = [];
    while($row = $bRes->fetch_assoc()) {
        $include = false;
        if (in_array($role, ['Administrator', 'HRGA'])) $include = true;
        elseif ($row['username'] == $username) $include = true;
        elseif ($row['department'] == $dept && $role == 'SectionHead') $include = true;
        if($include) {
            $row['timestamp'] = $row['created_at']; $row['id'] = $row['req_id'];
            $bookings[] = $row;
        }
    }
    sendJson(['success' => true, 'vehicles' => $vehicles, 'bookings' => $bookings]);
}

// 2. SUBMIT
if ($action == 'submit') {
    $reqId = "VMS-" . time();
    $status = 'Pending GA';
    $appGa = 'Pending';
    if ($input['role'] == 'HRGA' && $input['department'] == 'HRGA') {
        $status = 'Pending Section Head'; $appGa = 'Auto-Approved';
    }

    $stmt = $conn->prepare("INSERT INTO vms_bookings (req_id, username, fullname, role, department, vehicle, purpose, status, app_ga) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssss", $reqId, $input['username'], $input['fullname'], $input['role'], $input['department'], $input['vehicle'], $input['purpose'], $status, $appGa);
    
    if($stmt->execute()) {
        $conn->query("UPDATE vms_vehicles SET status = 'Reserved' WHERE plant_plat = '{$input['vehicle']}'");
        
        // Notif ke GA (HRGA)
        $phones = getPhones($conn, 'HRGA'); 
        $msg = "🚗 *VMS - PERMINTAAN KENDARAAN*\n" .
               "--------------------------------\n" .
               "User requesting vehicle:\n\n" .
               "👤 *User:* {$input['fullname']}\n" .
               "📂 *Dept:* {$input['department']}\n" .
               "🚙 *Unit:* {$input['vehicle']}\n" .
               "📝 *Tujuan:* {$input['purpose']}\n\n" .
               "👉 _Mohon dicek ketersediaan & Approve._";
        
        foreach($phones as $ph) sendWA($ph, $msg);
        sendJson(['success' => true]);
    } else {
        sendJson(['success' => false, 'message' => $conn->error]);
    }
}

// 3. UPDATE STATUS
if ($action == 'updateStatus') {
    $id = $input['id']; $act = $input['act']; $extra = $input['extraData'] ?? [];
    
    $qry = $conn->query("SELECT username, fullname, vehicle FROM vms_bookings WHERE req_id = '$id'");
    $reqData = $qry->fetch_assoc();
    $userPhone = getUserPhone($conn, $reqData['username']);

    if($act == 'approve') {
        $sql = "UPDATE vms_bookings SET status = 'Approved', app_head = 'Approved' WHERE req_id = '$id'";
        if($input['userRole'] == 'HRGA') $sql = "UPDATE vms_bookings SET status = 'Pending Section Head', app_ga = 'Approved' WHERE req_id = '$id'";
        $conn->query($sql);

        // Notif ke User
        if($userPhone) {
            $msg = "✅ *VMS - APPROVED*\n" .
                   "--------------------------------\n" .
                   "Booking kendaraan disetujui.\n\n" .
                   "🚙 *Unit:* {$reqData['vehicle']}\n" .
                   "🔑 _Silakan ambil kunci di GA._";
            sendWA($userPhone, $msg);
        }
    }
    elseif($act == 'reject') {
        $conn->query("UPDATE vms_bookings SET status = 'Rejected' WHERE req_id = '$id'");
        $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
        
        if($userPhone) {
            $msg = "❌ *VMS - REJECTED*\n" .
                   "--------------------------------\n" .
                   "Permintaan kendaraan ditolak.\n" .
                   "💬 *Alasan:* " . ($extra['comment'] ?? '-');
            sendWA($userPhone, $msg);
        }
    }
    elseif($act == 'startTrip') {
        $url = saveBase64Image($extra['photoBase64'], "START_$id", "vms");
        $conn->query("UPDATE vms_bookings SET status = 'Active', start_km = '{$extra['km']}', start_photo = '$url', depart_time = NOW() WHERE req_id = '$id'");
        $conn->query("UPDATE vms_vehicles SET status = 'In Use' WHERE plant_plat = '{$reqData['vehicle']}'");
        
        // Notif ke HRGA (Monitoring)
        $phones = getPhones($conn, 'HRGA');
        $msg = "🛫 *VMS - TRIP START*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\nKM: {$extra['km']}";
        foreach($phones as $ph) sendWA($ph, $msg);
    }
    elseif($act == 'endTrip') {
        $url = saveBase64Image($extra['photoBase64'], "END_$id", "vms");
        $conn->query("UPDATE vms_bookings SET status = 'Done', end_km = '{$extra['km']}', end_photo = '$url', return_time = NOW() WHERE req_id = '$id'");
        $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
        
        // Notif ke HRGA
        $phones = getPhones($conn, 'HRGA');
        $msg = "🏁 *VMS - TRIP FINISHED*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\nKM Akhir: {$extra['km']}";
        foreach($phones as $ph) sendWA($ph, $msg);
    }
    elseif($act == 'cancel') {
        $conn->query("UPDATE vms_bookings SET status = 'Cancelled' WHERE req_id = '$id'");
        $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
    }
    
    sendJson(['success' => true]);
}
?>