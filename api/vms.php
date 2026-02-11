<?php
// api/vms.php - 3 LEVEL APPROVAL SYSTEM
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');
ini_set('memory_limit', '512M');
ini_set('post_max_size', '64M');
ini_set('upload_max_filesize', '64M');

if (function_exists('ob_clean')) { ob_end_clean(); }
ob_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require 'db.php'; 
require 'helper.php';

ob_clean(); 

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$currentDateTime = date('Y-m-d H:i:s');

function sendResponse($data) {
    if (ob_get_length()) ob_clean(); 
    echo json_encode($data);
    exit;
}

// Fungsi Upload Internal
function uploadImageInternal($base64Data, $prefix) {
    $uploadDir = "../uploads/vms/"; 
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
    if (strpos($base64Data, 'base64,') !== false) {
        $base64Data = explode('base64,', $base64Data)[1];
    }
    $decodedData = base64_decode($base64Data);
    if ($decodedData === false) return false;
    $fileName = $prefix . "_" . time() . "_" . rand(100,999) . ".jpg";
    $filePath = $uploadDir . $fileName;
    if (file_put_contents($filePath, $decodedData)) {
        return "uploads/vms/" . $fileName; 
    }
    return false;
}

try {
    // 1. GET DATA (NORMAL & EXPORT)
    if ($action == 'getData' || $action == 'exportData') {
        $role = $input['role']; 
        $username = $input['username']; 
        $dept = $input['department'];
        
        // A. Get Vehicles Status
        $vRes = $conn->query("SELECT plant_plat as plant, merk_tipe as model, status FROM vms_vehicles");
        $vehicles = [];
        if($vRes) {
            while($v = $vRes->fetch_assoc()) {
                if($v['status'] != 'Available') {
                    $qInfo = $conn->query("SELECT fullname, department FROM vms_bookings WHERE vehicle = '{$v['plant']}' AND status NOT IN ('Done', 'Cancelled', 'Rejected') ORDER BY id DESC LIMIT 1");
                    if($qInfo && $info = $qInfo->fetch_assoc()) {
                        $v['holder_name'] = $info['fullname'];
                        $v['holder_dept'] = $info['department'];
                    }
                }
                $vehicles[] = $v;
            }
        }

        // B. Get Bookings Data
        $sql = "SELECT * FROM vms_bookings WHERE 1=1";
        
        // Logic Export
        if ($action == 'exportData') {
            if (!empty($input['startDate']) && !empty($input['endDate'])) {
                $start = $input['startDate'] . " 00:00:00";
                $end = $input['endDate'] . " 23:59:59";
                $sql .= " AND created_at BETWEEN '$start' AND '$end'";
            }
            $sql .= " ORDER BY created_at ASC"; 
        } else {
            $sql .= " ORDER BY id DESC LIMIT 100";
        }

        $bRes = $conn->query($sql);
        $bookings = [];
        
        if($bRes) {
            while($row = $bRes->fetch_assoc()) {
                $include = false;
                
                // --- PERMISSION LOGIC ---
                // Admin & PlantHead see all
                if (in_array($role, ['Administrator', 'PlantHead'])) $include = true;
                // User sees own
                elseif ($row['username'] == $username) $include = true;
                
                // L1: Dept Head (SectionHead / TeamLeader in same Dept)
                elseif ($row['department'] == $dept && ($role == 'SectionHead' || $role == 'TeamLeader')) $include = true;
                
                // L2: HRGA (Staff)
                elseif ($role == 'HRGA' && $dept == 'HRGA') $include = true;
                
                // L3: TeamLeader HRGA
                elseif ($role == 'TeamLeader' && $dept == 'HRGA') $include = true;

                // Export Override
                if ($action == 'exportData' && in_array($role, ['Administrator', 'HRGA', 'TeamLeader'])) $include = true;

                if($include) {
                    // Mapping Data
                    $row['id'] = $row['req_id'];
                    $row['timestamp'] = $row['created_at'];
                    
                    // Approver Details Mapping
                    // L1 (Dept) -> app_head
                    // L2 (HRGA) -> app_ga
                    // L3 (Final) -> app_final
                    
                    $row['appL1'] = $row['app_head']; // Dept Head
                    $row['l1Time'] = $row['head_time'];
                    $row['l1By'] = $row['head_by'] ?? '-';

                    $row['appL2'] = $row['app_ga']; // HRGA
                    $row['l2Time'] = $row['ga_time'];
                    $row['l2By'] = $row['ga_by'] ?? '-';
                    
                    $row['appL3'] = $row['app_final']; // Final/TL
                    $row['l3Time'] = $row['final_time'];
                    $row['l3By'] = $row['final_by'] ?? '-';
                    
                    $row['appGa'] = $row['app_ga']; // Keep backward compat just in case
                    $row['appHead'] = $row['app_head']; 

                    // Trip Details
                    $row['actionComment'] = $row['action_comment'];
                    $row['startKm'] = $row['start_km'];
                    $row['endKm'] = $row['end_km'];
                    $row['departTime'] = $row['depart_time'];
                    $row['returnTime'] = $row['return_time'];
                    $row['startPhoto'] = $row['start_photo'];
                    $row['endPhoto'] = $row['end_photo'];
                    
                    $bookings[] = $row;
                }
            }
        }
        sendResponse(['success' => true, 'vehicles' => $vehicles, 'bookings' => $bookings]);
    }

    // 2. SUBMIT (LEVEL 1 Logic)
    if ($action == 'submit') {
        $reqId = "VMS-" . time();
        $status = 'Pending Dept Head'; // Default start status
        $reqDept = $input['department'];

        // Init Approvals
        $appHead = 'Pending';
        $appGa = 'Pending';
        $appFinal = 'Pending';
        
        // Prepare Statement
        // app_head = L1, app_ga = L2, app_final = L3
        $stmt = $conn->prepare("INSERT INTO vms_bookings (req_id, username, fullname, role, department, vehicle, purpose, status, app_head, app_ga, app_final, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssss", $reqId, $input['username'], $input['fullname'], $input['role'], $input['department'], $input['vehicle'], $input['purpose'], $status, $appHead, $appGa, $appFinal, $currentDateTime);
        
        if($stmt->execute()) {
            $conn->query("UPDATE vms_vehicles SET status = 'Reserved' WHERE plant_plat = '{$input['vehicle']}'");
            
            try {
                // Notif User
                $userPhone = getUserPhone($conn, $input['username']);
                if($userPhone) sendWA($userPhone, "📋 *VMS - SUBMITTED*\nUnit: {$input['vehicle']}\nStatus: Menunggu Approval Dept Head.");

                // Notif Level 1 (Dept Head)
                // Logic: Cari SectionHead dept user. Jika tidak ada, cari TeamLeader dept user.
                $phonesL1 = [];
                
                // Khusus jika user sendiri adalah HRGA, L1 nya langsung TeamLeader HRGA
                if ($reqDept == 'HRGA') {
                     $phonesL1 = getPhones($conn, 'TeamLeader', 'HRGA');
                } else {
                    // Cari SectionHead Dept User
                    $phonesL1 = getPhones($conn, 'SectionHead', $reqDept);
                    
                    // Jika SectionHead kosong, fallback ke TeamLeader Dept User
                    if (empty($phonesL1)) {
                        $phonesL1 = getPhones($conn, 'TeamLeader', $reqDept);
                    }
                }

                $msgL1 = "🚗 *VMS - APPROVAL LEVEL 1*\nUser: {$input['fullname']} ({$reqDept})\nUnit: {$input['vehicle']}\nTujuan: {$input['purpose']}\n👉 _Mohon Approve sebagai Dept Head._";
                if(is_array($phonesL1)) foreach($phonesL1 as $ph) sendWA($ph, $msgL1);

            } catch (Exception $e) {}
            sendResponse(['success' => true]);
        } else {
            sendResponse(['success' => false, 'message' => $stmt->error]);
        }
    }

    // 3. UPDATE STATUS (APPROVAL FLOW)
    if ($action == 'updateStatus') {
        $id = $conn->real_escape_string($input['id']); 
        $act = $input['act']; 
        $extra = $input['extraData'] ?? [];
        $approverName = $conn->real_escape_string($input['approverName'] ?? 'System');
        
        $qry = $conn->query("SELECT * FROM vms_bookings WHERE req_id = '$id'");
        $reqData = $qry->fetch_assoc();
        if(!$reqData) sendResponse(['success' => false, 'message' => 'Data not found']);

        $userPhone = getUserPhone($conn, $reqData['username']);
        $currentStatus = $reqData['status'];
        $userDept = $reqData['department'];

        if($act == 'approve') {
            $rawComment = $conn->real_escape_string($extra['comment'] ?? '');
            $waNote = $rawComment ? "\n📝 *Note:* $rawComment" : "";
            
            // --- LEVEL 1 APPROVAL (DEPT HEAD) ---
            if ($currentStatus == 'Pending Dept Head') {
                $dbComment = $rawComment ? "L1 Approved by $approverName: $rawComment" : "";
                
                // Khusus Dept HRGA: Jika L1 (TeamLeader) approve -> Auto Approved (Bypass L2 & L3)
                if ($userDept == 'HRGA') {
                     $sql = "UPDATE vms_bookings SET status = 'Approved', app_head = 'Approved', head_time = '$currentDateTime', head_by = '$approverName', app_ga = 'Auto-Skip', app_final = 'Auto-Skip', action_comment = '$dbComment' WHERE req_id = '$id'";
                     if($conn->query($sql)) {
                         try {
                            if($userPhone) sendWA($userPhone, "✅ *VMS - APPROVED*\nTrip Anda disetujui (HRGA Auto).\nUnit: {$reqData['vehicle']}\n🔑 _Silakan Start Trip._");
                         } catch (Exception $e) {}
                     }
                } 
                // Dept Lain: Masuk Level 2 (HRGA)
                else {
                    $sql = "UPDATE vms_bookings SET status = 'Pending HRGA', app_head = 'Approved', head_time = '$currentDateTime', head_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                    if($conn->query($sql)) {
                        try {
                            if($userPhone) sendWA($userPhone, "✅ *VMS - L1 APPROVED*\nDept Head menyetujui.$waNote\nNext: Approval HRGA.");
                            
                            // Kirim ke Role HRGA (Dept HRGA)
                            $phonesL2 = getPhones($conn, 'HRGA', 'HRGA');
                            foreach($phonesL2 as $ph) sendWA($ph, "🚗 *VMS - APPROVAL LEVEL 2 (HRGA)*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\n👉 _Mohon dicek & Approve._");
                        } catch (Exception $e) {}
                    }
                }
            }
            
            // --- LEVEL 2 APPROVAL (HRGA STAFF) ---
            elseif ($currentStatus == 'Pending HRGA') {
                $dbComment = $rawComment ? "L2 Approved by $approverName: $rawComment" : "";
                
                $sql = "UPDATE vms_bookings SET status = 'Pending Final', app_ga = 'Approved', ga_time = '$currentDateTime', ga_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if($conn->query($sql)) {
                    try {
                        if($userPhone) sendWA($userPhone, "✅ *VMS - HRGA APPROVED*\nHRGA menyetujui.$waNote\nNext: Approval Final (TL HRGA).");
                        
                        // Kirim ke TeamLeader HRGA (Level 3)
                        $phonesL3 = getPhones($conn, 'TeamLeader', 'HRGA');
                        // Backup: Role HRGA juga bisa approve di level final jika diperlukan (opsional, tapi notif ke TL utama)
                        $backupL3 = getPhones($conn, 'HRGA', 'HRGA'); 
                        $allL3 = array_unique(array_merge($phonesL3, $backupL3));
                        
                        foreach($allL3 as $ph) sendWA($ph, "🚗 *VMS - APPROVAL FINAL (L3)*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\n👉 _Mohon persetujuan Final._");
                    } catch (Exception $e) {}
                }
            }

            // --- LEVEL 3 APPROVAL (TEAM LEADER HRGA / BACKUP) ---
            elseif ($currentStatus == 'Pending Final') {
                $dbComment = $rawComment ? "L3 Approved by $approverName: $rawComment" : "";
                
                $sql = "UPDATE vms_bookings SET status = 'Approved', app_final = 'Approved', final_time = '$currentDateTime', final_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if($conn->query($sql)) {
                    try {
                        if($userPhone) sendWA($userPhone, "✅ *VMS - FULL APPROVED*\nDisetujui Final oleh: $approverName.$waNote\nUnit: {$reqData['vehicle']}\n🔑 _Silakan ambil kunci & Start Trip._");
                    } catch (Exception $e) {}
                }
            }
            sendResponse(['success' => true]);
        }
        
        // REJECT LOGIC
        elseif($act == 'reject') {
            $reason = $conn->real_escape_string($extra['comment'] ?? '-');
            $fullComment = "Rejected by {$approverName}: {$reason}";
            
            $updatePart = "";
            if($currentStatus == 'Pending Dept Head') $updatePart = ", app_head = 'Rejected', head_by = '$approverName'";
            elseif($currentStatus == 'Pending HRGA') $updatePart = ", app_ga = 'Rejected', ga_by = '$approverName'";
            elseif($currentStatus == 'Pending Final') $updatePart = ", app_final = 'Rejected', final_by = '$approverName'";
            
            $sql = "UPDATE vms_bookings SET status = 'Rejected', action_comment = '$fullComment' $updatePart WHERE req_id = '$id'";
            $conn->query($sql);
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            
            try { if($userPhone) sendWA($userPhone, "❌ *VMS - REJECTED*\nDitolak oleh {$approverName}.\nAlasan: {$reason}"); } catch (Exception $e) {}
            sendResponse(['success' => true]);
        }

        // --- EXISTING LOGIC (START/END TRIP, CANCEL, VERIFY) ---
        elseif($act == 'cancel') {
            $comment = $conn->real_escape_string($extra['comment'] ?? 'User Cancelled');
            $conn->query("UPDATE vms_bookings SET status = 'Cancelled', action_comment = '$comment' WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            sendResponse(['success' => true]);
        }
        elseif($act == 'startTrip') {
            $url = uploadImageInternal($extra['photoBase64'], "START_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $conn->query("UPDATE vms_bookings SET status = 'Active', start_km = '{$extra['km']}', start_photo = '$url', depart_time = '$currentDateTime' WHERE req_id = '$id'");
                $conn->query("UPDATE vms_vehicles SET status = 'In Use' WHERE plant_plat = '{$reqData['vehicle']}'");
                try {
                    $msgStart = "🚗 *VMS - VEHICLE OUT*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\nODO Awal: {$extra['km']} km";
                    if($userPhone) sendWA($userPhone, $msgStart);
                } catch (Exception $e) {}
                sendResponse(['success' => true]);
            }
        }
        elseif($act == 'endTrip') {
            $url = uploadImageInternal($extra['photoBase64'], "END_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $route = $conn->real_escape_string($extra['route'] ?? '-');
                $endKM = intval($extra['km']);
                $conn->query("UPDATE vms_bookings SET status = 'Pending Review', end_km = '$endKM', end_photo = '$url', action_comment = '$route', return_time = '$currentDateTime' WHERE req_id = '$id'");
                try {
                    $phones = getPhones($conn, 'HRGA');
                    if(is_array($phones)) foreach($phones as $ph) sendWA($ph, "🏁 *VMS - TRIP FINISHED*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\nODO Akhir: $endKM km\n👉 _Mohon Verifikasi._");
                } catch (Exception $e) {}
                sendResponse(['success' => true]);
            }
        }
        elseif($act == 'verifyTrip') {
            $conn->query("UPDATE vms_bookings SET status = 'Done' WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            try { if($userPhone) sendWA($userPhone, "✅ *VMS - TRIP VERIFIED*\nStatus: DONE\n_Unit kembali Available._"); } catch(Exception $e) {}
            sendResponse(['success' => true]);
        }
        elseif($act == 'requestCorrection') {
            $reason = $conn->real_escape_string($extra['comment']);
            $conn->query("UPDATE vms_bookings SET status = 'Correction Needed', action_comment = 'Correction requested by $approverName: $reason' WHERE req_id = '$id'");
            try { if($userPhone) sendWA($userPhone, "⚠️ *VMS - REVISI DATA TRIP*\nMohon perbaiki data trip Anda.\nNote: $reason"); } catch(Exception $e) {}
            sendResponse(['success' => true]);
        }
        elseif($act == 'submitCorrection') {
            $url = uploadImageInternal($extra['photoBase64'], "FIX_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $route = $conn->real_escape_string($extra['route'] ?? '-');
                $endKM = intval($extra['km']);
                $conn->query("UPDATE vms_bookings SET status = 'Pending Review', end_km = '$endKM', end_photo = '$url', action_comment = '$route' WHERE req_id = '$id'");
                sendResponse(['success' => true]);
            }
        }
    }
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => $e->getMessage()]);
}
?>