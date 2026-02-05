<?php
require 'db.php'; 
require 'helper.php';

date_default_timezone_set('Asia/Jakarta'); 
$conn->query("SET time_zone = '+07:00'");

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 1. GET DATA (VIEW & EXPORT)
if ($action == 'getData' || $action == 'exportData') {
    $role = $input['role']; 
    $username = $input['username']; 
    $dept = $input['department'];
    
    $sql = "SELECT * FROM eps_permits WHERE 1=1";
    
    // Filter Date Export
    if ($action == 'exportData') {
        if (!empty($input['startDate']) && !empty($input['endDate'])) {
            $start = $input['startDate'];
            $end = $input['endDate'];
            $sql .= " AND date_permit BETWEEN '$start' AND '$end'";
        }
        $sql .= " ORDER BY date_permit ASC, created_at ASC";
    } else {
        $sql .= " ORDER BY id DESC LIMIT 50";
    }

    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $include = false;
        // Logic Visibility View
        if (in_array($role, ['Administrator', 'Security', 'Plant Head'])) $include = true;
        elseif ($role == 'HRGA' && $dept == 'HRGA') $include = true;
        elseif ($role == 'SectionHead' && $row['department'] == $dept) $include = true;
        elseif ($row['username'] == $username) $include = true;
        
        // Export Override: Admin/HRGA can export all data filtered by query
        if ($action == 'exportData' && in_array($role, ['Administrator', 'HRGA'])) $include = true;

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
        $phones = getPhones($conn, 'SectionHead', $input['department']);
        $msg = "📄 *EPS - NEW REQUEST*\nUser: {$input['fullname']}\nDept: {$input['department']}\nTujuan: {$input['purpose']}\nWaktu: {$input['datePermit']} ({$input['timeOut']} - {$input['timeIn']})\n👉 Login untuk Approve.";
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
            $phones = getPhones($conn, 'HRGA');
            foreach($phones as $ph) sendWA($ph, "⏳ *EPS - APPROVAL (HRGA)*\nUser: {$reqData['fullname']}\nHead Approved.\nMohon verifikasi.");
        } elseif ($role == 'HRGA') {
            $conn->query("UPDATE eps_permits SET status = 'Approved', app_hrga = 'Approved by $fullname' WHERE req_id = '$id'");
            if($requesterPhone) sendWA($requesterPhone, "✅ *EPS - DISETUJUI*\nSilakan tunjukkan ke Security saat keluar.");
        }
    } 
    elseif ($act == 'reject') {
        $conn->query("UPDATE eps_permits SET status = 'Rejected' WHERE req_id = '$id'");
        if($requesterPhone) sendWA($requesterPhone, "❌ *EPS - DITOLAK*\nOleh: $fullname");
    } 
    elseif ($act == 'security_out') {
        $conn->query("UPDATE eps_permits SET status = 'On Leave', actual_out = NOW(), sec_out_name = '$fullname' WHERE req_id = '$id'");
        if($requesterPhone) sendWA($requesterPhone, "👋 *EPS - GATE OUT*\nAnda tercatat keluar.");
    } 
    elseif ($act == 'security_in') {
        $conn->query("UPDATE eps_permits SET status = 'Returned', actual_in = NOW(), sec_in_name = '$fullname' WHERE req_id = '$id'");
        if($requesterPhone) sendWA($requesterPhone, "🏠 *EPS - GATE IN*\nAnda tercatat kembali.");
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