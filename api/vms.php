<?php
// api/vms.php - FINAL FIX V24 (Trip Verification Workflow & Route Notification)
error_reporting(0);
ini_set('display_errors', 0);

// 1. SYNC WAKTU REALTIME (WIB)
date_default_timezone_set('Asia/Jakarta');

// Limit Resource
ini_set('memory_limit', '512M');
ini_set('post_max_size', '64M');
ini_set('upload_max_filesize', '64M');

if (function_exists('ob_clean')) { ob_end_clean(); }
ob_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require 'db.php'; 
require 'helper.php'; // Menggunakan helper.php untuk sendWA

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
    // 1. GET DATA
    if ($action == 'getData') {
        $role = $input['role']; 
        $username = $input['username']; 
        $dept = $input['department'];
        
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

        $sql = "SELECT * FROM vms_bookings ORDER BY id DESC LIMIT 100";
        $bRes = $conn->query($sql);
        $bookings = [];
        
        if($bRes) {
            while($row = $bRes->fetch_assoc()) {
                $include = false;
                if (in_array($role, ['Administrator', 'HRGA', 'PlantHead'])) $include = true;
                elseif ($row['username'] == $username) $include = true;
                elseif ($role == 'TeamLeader' && $dept == 'HRGA') $include = true; 
                elseif ($row['department'] == $dept && ($role == 'SectionHead' || $role == 'TeamLeader')) $include = true;
                
                if($include) {
                    $row['id'] = $row['req_id'];
                    $row['timestamp'] = $row['created_at'];
                    $row['appGa'] = $row['app_ga'];
                    $row['appHead'] = $row['app_head'];
                    $row['gaTime'] = $row['ga_time'];
                    $row['headTime'] = $row['head_time'];
                    $row['gaBy'] = $row['ga_by'] ?? '';
                    $row['headBy'] = $row['head_by'] ?? '';
                    $row['actionComment'] = $row['action_comment'];
                    $row['startKm'] = $row['start_km'];
                    $row['endKm'] = $row['end_km'];
                    $row['startPhoto'] = $row['start_photo'];
                    $row['endPhoto'] = $row['end_photo'];
                    $bookings[] = $row;
                }
            }
        }
        sendResponse(['success' => true, 'vehicles' => $vehicles, 'bookings' => $bookings]);
    }

    // 2. SUBMIT
    if ($action == 'submit') {
        $reqId = "VMS-" . time();
        $status = 'Pending GA';
        $appGa = 'Pending';
        
        $stmt = $conn->prepare("INSERT INTO vms_bookings (req_id, username, fullname, role, department, vehicle, purpose, status, app_ga, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssss", $reqId, $input['username'], $input['fullname'], $input['role'], $input['department'], $input['vehicle'], $input['purpose'], $status, $appGa, $currentDateTime);
        
        if($stmt->execute()) {
            $conn->query("UPDATE vms_vehicles SET status = 'Reserved' WHERE plant_plat = '{$input['vehicle']}'");
            
            try {
                $userPhone = getUserPhone($conn, $input['username']);
                $msgUser = "📋 *VMS - SUBMITTED*\nUnit: {$input['vehicle']}\nTujuan: {$input['purpose']}\nStatus: Menunggu Approval HRGA.\nWaktu: " . date('d-m-Y H:i');
                if($userPhone) sendWA($userPhone, $msgUser);

                $phones = getPhones($conn, 'HRGA'); 
                $msgHrga = "🚗 *VMS - APPROVAL (HRGA)*\nUser: {$input['fullname']} ({$input['department']})\nUnit: {$input['vehicle']}\nTujuan: {$input['purpose']}\nWaktu: " . date('d-m-Y H:i') . "\n👉 _Mohon dicek & Approve._";
                if(is_array($phones)) foreach($phones as $ph) sendWA($ph, $msgHrga);
            } catch (Exception $e) {}
            
            sendResponse(['success' => true]);
        } else {
            sendResponse(['success' => false, 'message' => $stmt->error]);
        }
    }

    // 3. UPDATE STATUS
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

        if($act == 'approve') {
            $rawComment = $conn->real_escape_string($extra['comment'] ?? '');
            $dbComment = $rawComment ? "Approved by $approverName: $rawComment" : ""; 
            $waNote = $rawComment ? "\n📝 *Note:* $rawComment" : "";
            
            if ($currentStatus == 'Pending GA') {
                $sql = "UPDATE vms_bookings SET status = 'Pending Section Head', app_ga = 'Approved', ga_time = '$currentDateTime', ga_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if(!$conn->query($sql)) sendResponse(['success'=>false, 'message'=>'DB Error L1']);
                
                try {
                    if($userPhone) sendWA($userPhone, "✅ *VMS - GA APPROVED*\nHRGA ($approverName) telah menyetujui.$waNote\nMenunggu approval Final (TeamLeader HRGA).");
                    
                    $tlHrga = getPhones($conn, 'TeamLeader', 'HRGA'); 
                    $roleHrga = getPhones($conn, 'HRGA');
                    $phonesL2 = array_unique(array_merge(is_array($tlHrga)?$tlHrga:[], is_array($roleHrga)?$roleHrga:[]));

                    foreach($phonesL2 as $ph) {
                        sendWA($ph, "🚗 *VMS - APPROVAL TL*\nUser: {$reqData['fullname']} ({$reqData['department']})\nUnit: {$reqData['vehicle']}\nTujuan: {$reqData['purpose']}\n✅ *GA Approved by: $approverName*$waNote\n👉 _Mohon persetujuan Final._");
                    }
                } catch (Exception $e) {}
            }
            elseif ($currentStatus == 'Pending Section Head') {
                $sql = "UPDATE vms_bookings SET status = 'Approved', app_head = 'Approved', head_time = '$currentDateTime', head_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if(!$conn->query($sql)) sendResponse(['success'=>false, 'message'=>'DB Error L2']);

                try {
                    if($userPhone) sendWA($userPhone, "✅ *VMS - FULL APPROVED*\nDisetujui oleh: $approverName.$waNote\nUnit: {$reqData['vehicle']}\nTujuan: {$reqData['purpose']}\n🔑 _Silakan ambil kunci & Start Trip._");
                    $phones = getPhones($conn, 'HRGA');
                    if(is_array($phones)) foreach($phones as $ph) sendWA($ph, "ℹ️ *VMS - APPROVED*\nRequest {$reqData['fullname']}\nTujuan: {$reqData['purpose']}\nTelah FULL APPROVED oleh $approverName.$waNote");
                } catch (Exception $e) {}
            }
        }
        elseif($act == 'reject') {
            $reason = $conn->real_escape_string($extra['comment'] ?? '-');
            $fullComment = "Rejected by {$approverName}: {$reason}";
            $updatePart = "";
            if($currentStatus == 'Pending GA') $updatePart = ", app_ga = 'Rejected', ga_by = '$approverName'";
            elseif($currentStatus == 'Pending Section Head') $updatePart = ", app_head = 'Rejected', head_by = '$approverName'";
            $sql = "UPDATE vms_bookings SET status = 'Rejected', action_comment = '$fullComment' $updatePart WHERE req_id = '$id'";
            $conn->query($sql);
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            try { if($userPhone) sendWA($userPhone, "❌ *VMS - REJECTED*\nDitolak oleh {$approverName}.\nAlasan: {$reason}"); } catch (Exception $e) {}
        }
        elseif($act == 'cancel') {
            $comment = $conn->real_escape_string($extra['comment'] ?? 'User Cancelled');
            $userFullname = $reqData['fullname']; $unit = $reqData['vehicle']; $purpose = $reqData['purpose']; $oldStatus = $reqData['status'];
            $conn->query("UPDATE vms_bookings SET status = 'Cancelled', action_comment = '$comment' WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '$unit'");
            
            try {
                $phonesToNotify = getPhones($conn, 'HRGA'); 
                if(!is_array($phonesToNotify)) $phonesToNotify = [];
                if ($oldStatus == 'Pending Section Head') {
                    $tlHrga = getPhones($conn, 'TeamLeader', 'HRGA');
                    if(is_array($tlHrga)) $phonesToNotify = array_merge($phonesToNotify, $tlHrga);
                }
                $phonesToNotify = array_unique($phonesToNotify);
                $msg = "🚫 *VMS - CANCELLED (By User)*\nUser: $userFullname\nUnit: $unit\nTujuan: $purpose\nStatus Awal: $oldStatus\n📝 *Alasan:* $comment\n_Tidak perlu approval._";
                foreach($phonesToNotify as $ph) sendWA($ph, $msg);
            } catch (Exception $e) {}
        }
        elseif($act == 'startTrip') {
            $url = uploadImageInternal($extra['photoBase64'], "START_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $conn->query("UPDATE vms_bookings SET status = 'Active', start_km = '{$extra['km']}', start_photo = '$url', depart_time = '$currentDateTime' WHERE req_id = '$id'");
                $conn->query("UPDATE vms_vehicles SET status = 'In Use' WHERE plant_plat = '{$reqData['vehicle']}'");

                try {
                    $msgStart = "🚗 *VMS - VEHICLE OUT (Start Trip)*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\nODO Awal: {$extra['km']} km\nWaktu: " . date('d-m-Y H:i');
                    if($userPhone) sendWA($userPhone, $msgStart);
                    $phones = getPhones($conn, 'HRGA');
                    if(is_array($phones)) foreach($phones as $ph) sendWA($ph, $msgStart);
                } catch (Exception $e) {}
            } else {
                sendResponse(['success' => false, 'message' => 'Gagal simpan foto start trip']);
            }
        }
        // --- 4. END TRIP: Status -> Pending Review, Mobil -> In Use ---
        elseif($act == 'endTrip') {
            $url = uploadImageInternal($extra['photoBase64'], "END_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $route = $conn->real_escape_string($extra['route'] ?? '-');
                $startKM = intval($reqData['start_km']);
                $endKM = intval($extra['km']);
                $totalDist = $endKM - $startKM;

                // UPDATE: Status jadi 'Pending Review', Mobil TETAP 'In Use'
                $conn->query("UPDATE vms_bookings SET status = 'Pending Review', end_km = '$endKM', end_photo = '$url', action_comment = '$route', return_time = '$currentDateTime' WHERE req_id = '$id'");
                
                // NOTIF KE HRGA UNTUK VERIFIKASI (Dengan Info Route)
                try {
                    $msgVerif = "🏁 *VMS - TRIP FINISHED (Need Verification)*\n" .
                                "User: {$reqData['fullname']}\n" .
                                "Unit: {$reqData['vehicle']}\n" .
                                "Rute: $route\n" .
                                "ODO Awal: $startKM km\n" .
                                "ODO Akhir: $endKM km\n" .
                                "Total Jarak: $totalDist km\n" .
                                "👉 _Mohon Login ke Dashboard HRGA untuk verifikasi._";

                    $phones = getPhones($conn, 'HRGA');
                    if(is_array($phones)) foreach($phones as $ph) sendWA($ph, $msgVerif);
                } catch (Exception $e) {}

            } else {
                sendResponse(['success' => false, 'message' => 'Gagal simpan foto end trip']);
            }
        }
        // --- 5. HRGA: VERIFY TRIP (DONE) ---
        elseif($act == 'verifyTrip') {
            // Status jadi 'Done', Mobil jadi 'Available'
            $conn->query("UPDATE vms_bookings SET status = 'Done' WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            
            try {
                $msgDone = "✅ *VMS - TRIP VERIFIED*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\nStatus: DONE\n_Unit kembali Available._";
                if($userPhone) sendWA($userPhone, $msgDone);
            } catch(Exception $e) {}
        }
        // --- 6. HRGA: REQUEST CORRECTION ---
        elseif($act == 'requestCorrection') {
            $reason = $conn->real_escape_string($extra['comment']);
            // Status jadi 'Correction Needed', Mobil TETAP 'In Use'
            $conn->query("UPDATE vms_bookings SET status = 'Correction Needed', action_comment = 'Correction requested by $approverName: $reason' WHERE req_id = '$id'");
            
            try { 
                if($userPhone) sendWA($userPhone, "⚠️ *VMS - REVISI DATA TRIP*\nMohon perbaiki data trip Anda.\nNote: $reason\n👉 _Buka aplikasi dan klik tombol Fix Data._"); 
            } catch(Exception $e) {}
        }
        // --- 7. USER: SUBMIT CORRECTION ---
        elseif($act == 'submitCorrection') {
            // User upload ulang foto/data
            $url = uploadImageInternal($extra['photoBase64'], "FIX_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $route = $conn->real_escape_string($extra['route'] ?? '-');
                $endKM = intval($extra['km']);
                
                // Status kembali ke 'Pending Review'
                $conn->query("UPDATE vms_bookings SET status = 'Pending Review', end_km = '$endKM', end_photo = '$url', action_comment = '$route' WHERE req_id = '$id'");
                
                try {
                    $phones = getPhones($conn, 'HRGA');
                    if(is_array($phones)) foreach($phones as $ph) sendWA($ph, "🔄 *VMS - REVISI SUBMITTED*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\nData telah diperbaiki.\n👉 _Mohon verifikasi ulang._");
                } catch (Exception $e) {}
            } else {
                sendResponse(['success' => false, 'message' => 'Gagal simpan foto revisi']);
            }
        }
        
        sendResponse(['success' => true]);
    }
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => $e->getMessage()]);
}
?>