<?php
// api/vms.php - FINAL FIX V10 (Detailed WhatsApp Notifications: Purpose & Cancel Notes)
// Matikan error display agar tidak merusak format JSON
error_reporting(0);
ini_set('display_errors', 0);

// Mulai buffer output
if (function_exists('ob_clean')) { ob_end_clean(); }
ob_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require 'db.php'; 
require 'helper.php';

// Bersihkan buffer apapun sebelum kita memproses logic
ob_clean(); 

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Fungsi Helper untuk kirim respon JSON yang bersih
function sendResponse($data) {
    if (ob_get_length()) ob_clean(); 
    echo json_encode($data);
    exit;
}

try {
    // 1. GET DATA
    if ($action == 'getData') {
        $role = $input['role']; 
        $username = $input['username']; 
        $dept = $input['department'];
        
        // Data Kendaraan
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

        // Data Booking
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
        
        $stmt = $conn->prepare("INSERT INTO vms_bookings (req_id, username, fullname, role, department, vehicle, purpose, status, app_ga) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssss", $reqId, $input['username'], $input['fullname'], $input['role'], $input['department'], $input['vehicle'], $input['purpose'], $status, $appGa);
        
        if($stmt->execute()) {
            $conn->query("UPDATE vms_vehicles SET status = 'Reserved' WHERE plant_plat = '{$input['vehicle']}'");
            
            try {
                // Notif ke User
                $userPhone = getUserPhone($conn, $input['username']);
                $msgUser = "📋 *VMS - SUBMITTED*\nUnit: {$input['vehicle']}\nTujuan: {$input['purpose']}\nStatus: Menunggu Approval HRGA.";
                if($userPhone) sendWA($userPhone, $msgUser);

                // Notif ke HRGA (Approver L1)
                $phones = getPhones($conn, 'HRGA'); 
                $msgHrga = "🚗 *VMS - APPROVAL L1 (HRGA)*\nUser: {$input['fullname']}\nUnit: {$input['vehicle']}\nTujuan: {$input['purpose']}\n👉 _Mohon dicek & Approve._";
                if(is_array($phones)) {
                    foreach($phones as $ph) sendWA($ph, $msgHrga);
                }
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
            
            // L1 APPROVAL (GA)
            if ($currentStatus == 'Pending GA') {
                $sql = "UPDATE vms_bookings SET status = 'Pending Section Head', app_ga = 'Approved', ga_time = NOW(), ga_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if(!$conn->query($sql)) sendResponse(['success'=>false, 'message'=>'DB Error L1']);
                
                try {
                    // Notif ke User
                    if($userPhone) sendWA($userPhone, "✅ *VMS - L1 APPROVED*\nHRGA ($approverName) telah menyetujui.$waNote\nMenunggu approval Final.");
                    
                    // Notif ke Next Approver (L2)
                    $phones = getPhones($conn, 'TeamLeader', 'HRGA');
                    if(empty($phones)) $phones = getPhones($conn, 'HRGA'); 
                    if(is_array($phones)) {
                        foreach($phones as $ph) sendWA($ph, "🚗 *VMS - APPROVAL L2*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\nTujuan: {$reqData['purpose']}\n✅ *L1 Approved by: $approverName*$waNote\n👉 _Mohon persetujuan Final._");
                    }
                } catch (Exception $e) {}
            }
            // L2 APPROVAL (HEAD)
            elseif ($currentStatus == 'Pending Section Head') {
                $sql = "UPDATE vms_bookings SET status = 'Approved', app_head = 'Approved', head_time = NOW(), head_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if(!$conn->query($sql)) sendResponse(['success'=>false, 'message'=>'DB Error L2']);

                try {
                    // Notif ke User
                    if($userPhone) sendWA($userPhone, "✅ *VMS - FULL APPROVED*\nDisetujui oleh: $approverName.$waNote\nUnit: {$reqData['vehicle']}\nTujuan: {$reqData['purpose']}\n🔑 _Silakan ambil kunci & Start Trip._");
                    
                    // Notif ke HRGA
                    $phones = getPhones($conn, 'HRGA');
                    if(is_array($phones)) foreach($phones as $ph) sendWA($ph, "ℹ️ *VMS - APPROVED*\nRequest {$reqData['fullname']}\nTujuan: {$reqData['purpose']}\nTelah FULL APPROVED.$waNote");
                } catch (Exception $e) {}
            }
        }
        elseif($act == 'reject') {
            $reason = $conn->real_escape_string($extra['comment'] ?? '-');
            $fullComment = "Rejected by {$approverName}: {$reason}";
            
            if($currentStatus == 'Pending GA') {
                $sql = "UPDATE vms_bookings SET status = 'Rejected', app_ga = 'Rejected', ga_by = '$approverName', action_comment = '$fullComment' WHERE req_id = '$id'";
            } elseif ($currentStatus == 'Pending Section Head') {
                $sql = "UPDATE vms_bookings SET status = 'Rejected', app_head = 'Rejected', head_by = '$approverName', action_comment = '$fullComment' WHERE req_id = '$id'";
            } else {
                $sql = "UPDATE vms_bookings SET status = 'Rejected', action_comment = '$fullComment' WHERE req_id = '$id'";
            }
            
            $conn->query($sql);
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            
            try {
                if($userPhone) sendWA($userPhone, "❌ *VMS - REJECTED*\nDitolak oleh {$approverName}.\nAlasan: {$reason}");
            } catch (Exception $e) {}
        }
        elseif($act == 'cancel') {
            $comment = $conn->real_escape_string($extra['comment'] ?? 'User Cancelled');
            $userFullname = $reqData['fullname'];
            $unit = $reqData['vehicle'];
            $purpose = $reqData['purpose'];
            $oldStatus = $reqData['status'];
            
            // 1. Update Database
            $conn->query("UPDATE vms_bookings SET status = 'Cancelled', action_comment = '$comment' WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '$unit'");
            
            // 2. Logic Notifikasi berdasarkan Status Terakhir
            try {
                $phonesToNotify = [];
                $msg = "";
                
                // CASE A: User Cancel saat Status APPROVED
                if ($oldStatus == 'Approved') {
                     $phonesToNotify = getPhones($conn, 'HRGA');
                     $msg = "⚠️ *VMS - CANCELLED (By User)*\nUser: $userFullname\nUnit: $unit\nTujuan: $purpose\nStatus Awal: Approved\n📝 *Alasan Batal:* $comment\n_Unit kembali Available._";
                }
                // CASE B: User Cancel saat PENDING GA (Level 1)
                elseif ($oldStatus == 'Pending GA') {
                     $phonesToNotify = getPhones($conn, 'HRGA');
                     $msg = "🚫 *VMS - REQUEST CANCELLED*\nUser: $userFullname\nUnit: $unit\nTujuan: $purpose\n📝 *Alasan Batal:* $comment\n👉 _User membatalkan request ini. Approval tidak diperlukan._";
                }
                // CASE C: User Cancel saat PENDING HEAD (Level 2)
                elseif ($oldStatus == 'Pending Section Head') {
                     // Notify L2 (Current Holder) AND HRGA (Previous Approver/Admin)
                     $l2 = getPhones($conn, 'TeamLeader', 'HRGA');
                     $hrga = getPhones($conn, 'HRGA');
                     
                     // Gabungkan nomor telepon & hapus duplikat
                     $phonesToNotify = array_merge(is_array($l2)?$l2:[], is_array($hrga)?$hrga:[]);
                     $phonesToNotify = array_unique($phonesToNotify);
                     
                     $msg = "🚫 *VMS - REQUEST CANCELLED*\nUser: $userFullname\nUnit: $unit\nTujuan: $purpose\n📝 *Alasan Batal:* $comment\n👉 _User membatalkan request ini. Approval tidak diperlukan._";
                }
                
                // Kirim Pesan
                if(is_array($phonesToNotify) && !empty($msg)) {
                    foreach($phonesToNotify as $ph) {
                        sendWA($ph, $msg);
                    }
                }
            } catch (Exception $e) {}
        }
        elseif($act == 'startTrip') {
            $url = saveBase64Image($extra['photoBase64'], "START_$id", "vms");
            $conn->query("UPDATE vms_bookings SET status = 'Active', start_km = '{$extra['km']}', start_photo = '$url', depart_time = NOW() WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'In Use' WHERE plant_plat = '{$reqData['vehicle']}'");
        }
        elseif($act == 'endTrip') {
            $url = saveBase64Image($extra['photoBase64'], "END_$id", "vms");
            $route = $conn->real_escape_string($extra['route'] ?? '-');
            $conn->query("UPDATE vms_bookings SET status = 'Done', end_km = '{$extra['km']}', end_photo = '$url', action_comment = '$route', return_time = NOW() WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
        }
        elseif($act == 'requestCorrection') {
            $reason = $conn->real_escape_string($extra['comment']);
            $conn->query("UPDATE vms_bookings SET status = 'Correction Needed', action_comment = 'Correction requested by $approverName: $reason' WHERE req_id = '$id'");
            try { if($userPhone) sendWA($userPhone, "⚠️ *VMS - REVISI*\nMohon koreksi data trip.\nNote: $reason"); } catch(Exception $e) {}
        }
        
        sendResponse(['success' => true]);
    }

} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => $e->getMessage()]);
}
?>