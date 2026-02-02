<?php
require 'db.php'; require 'helper.php';
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 1. GET DATA
if ($action == 'getData') {
    $role = $input['role']; $username = $input['username']; $dept = $input['department'];
    $sql = "SELECT * FROM eps_permits ORDER BY id DESC LIMIT 50";
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $include = false;
        if (in_array($role, ['Administrator', 'Security', 'Plant Head'])) $include = true;
        elseif ($role == 'HRGA' && $dept == 'HRGA') $include = true;
        elseif ($role == 'SectionHead' && $row['department'] == $dept) $include = true;
        elseif ($row['username'] == $username) $include = true;

        if ($include) {
            $row['timestamp'] = $row['created_at']; $row['id'] = $row['req_id'];
            $row['actualOut'] = $row['actual_out']; $row['actualIn'] = $row['actual_in'];
            $row['appHead'] = $row['app_head']; $row['appHrga'] = $row['app_hrga'];
            $row['datePermit'] = $row['date_permit']; $row['planOut'] = $row['plan_out'];
            $row['planIn'] = $row['plan_in']; $row['typePermit'] = $row['type_permit'];
            $data[] = $row;
        }
    }
    sendJson($data);
}

// 2. SUBMIT
if ($action == 'submit') {
    $reqId = "EPS-" . time();
    $sql = "INSERT INTO eps_permits (req_id, username, fullname, nik, department, purpose, type_permit, date_permit, plan_out, plan_in, status, return_status)
            VALUES ('$reqId', '{$input['username']}', '{$input['fullname']}', '{$input['nik']}', '{$input['department']}', '{$input['purpose']}', '{$input['typePermit']}', '{$input['datePermit']}', '{$input['timeOut']}', '{$input['timeIn']}', 'Pending Head', '{$input['returnStatus']}')";
    
    if($conn->query($sql)) {
        // Notif ke Section Head
        $phones = getPhones($conn, 'SectionHead', $input['department']);
        $msg = "📄 *EPS - APPROVAL REQUEST*\n" .
               "--------------------------------\n" .
               "Mohon persetujuan izin keluar:\n\n" .
               "👤 *Nama:* {$input['fullname']}\n" .
               "📂 *Dept:* {$input['department']}\n" .
               "📝 *Tujuan:* {$input['purpose']}\n" .
               "📅 *Waktu:* {$input['datePermit']} ({$input['timeOut']} - {$input['timeIn']})\n" .
               "📌 *Tipe:* {$input['typePermit']}\n\n" .
               "👉 _Login ke Portal untuk Approve._";
        
        foreach($phones as $ph) sendWA($ph, $msg);
        sendJson(['success' => true]);
    } else {
        sendJson(['success' => false, 'message' => $conn->error]);
    }
}

// 3. UPDATE STATUS
if ($action == 'updateStatus') {
    $id = $input['id']; $act = $input['act']; $role = $input['role']; $fullname = $input['fullname']; $extra = $input['extra'] ?? [];

    $qry = $conn->query("SELECT * FROM eps_permits WHERE req_id = '$id'");
    $reqData = $qry->fetch_assoc();
    $requesterPhone = getUserPhone($conn, $reqData['username']);

    if ($act == 'approve') {
        if ($role == 'SectionHead') {
            $conn->query("UPDATE eps_permits SET status = 'Pending HRGA', app_head = 'Approved by $fullname' WHERE req_id = '$id'");
            
            // Notif ke HRGA
            $phones = getPhones($conn, 'HRGA');
            $msg = "⏳ *EPS - VERIFIKASI HRGA*\n" .
                   "--------------------------------\n" .
                   "Approved by Head ($fullname).\n" .
                   "Mohon verifikasi lanjutan.\n\n" .
                   "👤 *User:* {$reqData['fullname']}\n" .
                   "📝 *Tujuan:* {$reqData['purpose']}";
            foreach($phones as $ph) sendWA($ph, $msg);

        } elseif ($role == 'HRGA') {
            $conn->query("UPDATE eps_permits SET status = 'Approved', app_hrga = 'Approved by $fullname' WHERE req_id = '$id'");
            
            // Notif ke User
            if($requesterPhone) {
                $msg = "✅ *EPS - DISETUJUI*\n" .
                       "--------------------------------\n" .
                       "Izin keluar Anda telah disetujui (Final).\n" .
                       "🆔 *ID:* $id\n\n" .
                       "👮 _Tunjukkan pesan ini ke Security saat keluar._";
                sendWA($requesterPhone, $msg);
            }
        }
    } 
    elseif ($act == 'reject') {
        $conn->query("UPDATE eps_permits SET status = 'Rejected' WHERE req_id = '$id'");
        if($requesterPhone) {
            $msg = "❌ *EPS - DITOLAK*\n" .
                   "--------------------------------\n" .
                   "Izin keluar Anda ditolak oleh $fullname.\n" .
                   "💬 *Catatan:* " . ($extra['note'] ?? '-');
            sendWA($requesterPhone, $msg);
        }
    } 
    elseif ($act == 'security_out') {
        $conn->query("UPDATE eps_permits SET status = 'On Leave', actual_out = NOW(), sec_out_name = '$fullname' WHERE req_id = '$id'");
        if($requesterPhone) {
            $msg = "👋 *EPS - GATE OUT*\n" .
                   "--------------------------------\n" .
                   "Anda tercatat keluar area perusahaan.\n" .
                   "🕒 *Waktu:* " . date('H:i') . "\n" .
                   "👮 *Security:* $fullname";
            sendWA($requesterPhone, $msg);
        }
    } 
    elseif ($act == 'security_in') {
        $conn->query("UPDATE eps_permits SET status = 'Returned', actual_in = NOW(), sec_in_name = '$fullname' WHERE req_id = '$id'");
        if($requesterPhone) {
            $msg = "🏠 *EPS - GATE IN*\n" .
                   "--------------------------------\n" .
                   "Anda tercatat kembali ke perusahaan.\n" .
                   "🕒 *Waktu:* " . date('H:i');
            sendWA($requesterPhone, $msg);
        }
    } 
    elseif ($act == 'cancel') {
        $conn->query("UPDATE eps_permits SET status = 'Canceled' WHERE req_id = '$id'");
    }
    
    sendJson(['success' => true]);
}

// 4. STATS
if ($action == 'stats') {
    $res = $conn->query("SELECT status, count(*) as cnt FROM eps_permits GROUP BY status");
    $total = 0; $active = 0; $returned = 0; $rejected = 0;
    while($r = $res->fetch_assoc()) {
        $total += $r['cnt'];
        if($r['status'] == 'On Leave') $active += $r['cnt'];
        if($r['status'] == 'Returned') $returned += $r['cnt'];
        if($r['status'] == 'Rejected' || $r['status'] == 'Canceled') $rejected += $r['cnt'];
    }
    sendJson(['total' => $total, 'active' => $active, 'returned' => $returned, 'rejected' => $rejected]);
}
?>