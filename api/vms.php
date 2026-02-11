<?php
// api/vms.php - STABLE VERSION
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

function uploadImageInternal($base64Data, $prefix) {
    if (empty($base64Data)) return false;
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
    // GET SETTINGS
    if ($action == 'getSettings') {
        $q = $conn->query("SELECT setting_value FROM vms_settings WHERE setting_key = 'fuel_prices'");
        $defaultPrices = ["Pertamax Turbo" => 13250, "Pertamax" => 12400, "Pertalite" => 10000];
        $prices = ($q && $r = $q->fetch_assoc()) ? json_decode($r['setting_value'], true) : $defaultPrices;
        sendResponse(['success' => true, 'fuelPrices' => $prices]);
    }

    // SAVE SETTINGS
    if ($action == 'saveSettings') {
        if ($input['role'] !== 'Administrator') sendResponse(['success' => false, 'message' => 'Unauthorized']);
        $newPrices = json_encode($input['fuelPrices']);
        $stmt = $conn->prepare("INSERT INTO vms_settings (setting_key, setting_value) VALUES ('fuel_prices', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $newPrices, $newPrices);
        if($stmt->execute()) sendResponse(['success' => true]);
        else sendResponse(['success' => false, 'message' => 'DB Error']);
    }

    // GET DATA
    if ($action == 'getData' || $action == 'exportData') {
        $role = $input['role']; 
        $username = $input['username']; 
        $dept = $input['department'];
        
        $qSet = $conn->query("SELECT setting_value FROM vms_settings WHERE setting_key = 'fuel_prices'");
        $defaultPrices = ["Pertamax Turbo" => 13250, "Pertamax" => 12400, "Pertalite" => 10000];
        $fuelPrices = ($qSet && $rSet = $qSet->fetch_assoc()) ? json_decode($rSet['setting_value'], true) : $defaultPrices;

        // Vehicles
        $vRes = $conn->query("SELECT plant_plat as plant, merk_tipe as model, status, last_refuel_odo FROM vms_vehicles");
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

        // Bookings
        $sql = "SELECT * FROM vms_bookings WHERE 1=1";
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
                if (in_array($role, ['Administrator', 'PlantHead'])) $include = true;
                elseif ($row['username'] == $username) $include = true;
                elseif ($row['department'] == $dept && ($role == 'SectionHead' || $role == 'TeamLeader')) $include = true;
                elseif ($role == 'HRGA' && $dept == 'HRGA') $include = true;
                elseif ($role == 'TeamLeader' && $dept == 'HRGA') $include = true;
                if ($action == 'exportData' && in_array($role, ['Administrator', 'HRGA', 'TeamLeader'])) $include = true;

                if($include) {
                    $row['id'] = $row['req_id'];
                    $row['timestamp'] = $row['created_at'];
                    $row['updatedAt'] = $row['updated_at'];
                    $row['appL1'] = $row['app_head']; $row['l1Time'] = $row['head_time']; $row['l1By'] = $row['head_by'] ?? '-';
                    $row['appL2'] = $row['app_ga']; $row['l2Time'] = $row['ga_time']; $row['l2By'] = $row['ga_by'] ?? '-';
                    $row['appL3'] = $row['app_final']; $row['l3Time'] = $row['final_time']; $row['l3By'] = $row['final_by'] ?? '-';
                    $row['actionComment'] = $row['action_comment'];
                    $row['startKm'] = $row['start_km'];
                    $row['endKm'] = $row['end_km'];
                    $row['startPhoto'] = $row['start_photo'];
                    $row['endPhoto'] = $row['end_photo'];
                    $row['isRefuel'] = $row['is_refuel'];
                    $row['fuelType'] = $row['fuel_type'];
                    $row['fuelCost'] = $row['fuel_cost'];
                    $row['fuelLiters'] = $row['fuel_liters'];
                    $row['fuelRatio'] = $row['fuel_ratio'];
                    $row['fuelPhoto'] = $row['fuel_photo'];
                    $bookings[] = $row;
                }
            }
        }
        sendResponse(['success' => true, 'vehicles' => $vehicles, 'bookings' => $bookings, 'fuelPrices' => $fuelPrices]);
    }

    // SUBMIT
    if ($action == 'submit') {
        $reqId = "VMS-" . time();
        $status = 'Pending Dept Head'; 
        $stmt = $conn->prepare("INSERT INTO vms_bookings (req_id, username, fullname, role, department, vehicle, purpose, status, app_head, app_ga, app_final, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,'Pending','Pending','Pending',?,?)");
        $stmt->bind_param("ssssssssss", $reqId, $input['username'], $input['fullname'], $input['role'], $input['department'], $input['vehicle'], $input['purpose'], $status, $currentDateTime, $currentDateTime);
        if($stmt->execute()) {
            $conn->query("UPDATE vms_vehicles SET status = 'Reserved' WHERE plant_plat = '{$input['vehicle']}'");
            // Notifikasi WA (Standard Code)
            try {
                $userPhone = getUserPhone($conn, $input['username']);
                if($userPhone) sendWA($userPhone, "📋 *VMS - SUBMITTED*\nUnit: {$input['vehicle']}\nStatus: Menunggu Approval Dept Head.");
            } catch (Exception $e) {}
            sendResponse(['success' => true]);
        } else {
            sendResponse(['success' => false, 'message' => $stmt->error]);
        }
    }

    // UPDATE STATUS
    if ($action == 'updateStatus') {
        $id = $conn->real_escape_string($input['id']); 
        $act = $input['act']; 
        $extra = $input['extraData'] ?? [];
        $approverName = $conn->real_escape_string($input['approverName'] ?? 'System');
        
        $qry = $conn->query("SELECT * FROM vms_bookings WHERE req_id = '$id'");
        $reqData = $qry->fetch_assoc();
        if(!$reqData) sendResponse(['success' => false, 'message' => 'Data not found']);

        if($act == 'startTrip') {
            $url = uploadImageInternal($extra['photoBase64'], "START_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $conn->query("UPDATE vms_bookings SET status = 'Active', start_km = '{$extra['km']}', start_photo = '$url', depart_time = '$currentDateTime', updated_at = '$currentDateTime' WHERE req_id = '$id'");
                $conn->query("UPDATE vms_vehicles SET status = 'In Use' WHERE plant_plat = '{$reqData['vehicle']}'");
                sendResponse(['success' => true]);
            } else {
                sendResponse(['success' => false, 'message' => 'Gagal upload foto dashboard']);
            }
        }
        
        elseif($act == 'endTrip' || $act == 'submitCorrection') {
            $prefix = ($act == 'endTrip') ? "END_" : "FIX_";
            $dashUrl = uploadImageInternal($extra['photoBase64'], $prefix . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            
            if($dashUrl) {
                $route = $conn->real_escape_string($extra['route'] ?? '-');
                $endKM = intval($extra['km']);
                
                $isRefuel = isset($extra['isRefuel']) && $extra['isRefuel'] ? 1 : 0;
                $fuelType = $extra['fuelType'] ?? null;
                $fuelCost = $isRefuel ? floatval($extra['fuelCost']) : 0;
                $fuelLiters = 0;
                $ratio = 0;
                $fuelPhotoUrl = null;

                if($isRefuel) {
                    if(empty($extra['fuelPhotoBase64'])) {
                        sendResponse(['success' => false, 'message' => 'Foto Struk BBM Wajib Diisi!']);
                    }
                    $fuelPhotoUrl = uploadImageInternal($extra['fuelPhotoBase64'], "FUEL_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
                    
                    if($fuelCost > 0) {
                        $qP = $conn->query("SELECT setting_value FROM vms_settings WHERE setting_key = 'fuel_prices'");
                        $prices = ($qP && $rP = $qP->fetch_assoc()) ? json_decode($rP['setting_value'], true) : [];
                        $pricePerLiter = isset($prices[$fuelType]) ? floatval($prices[$fuelType]) : 10000;
                        if($pricePerLiter > 0) $fuelLiters = $fuelCost / $pricePerLiter;
                    }

                    $vQ = $conn->query("SELECT last_refuel_odo FROM vms_vehicles WHERE plant_plat = '{$reqData['vehicle']}'");
                    $vehData = $vQ->fetch_assoc();
                    $lastRefuelOdo = intval($vehData['last_refuel_odo']);

                    $distanceSinceRefuel = ($lastRefuelOdo == 0) ? ($endKM - intval($reqData['start_km'])) : ($endKM - $lastRefuelOdo);
                    if($fuelLiters > 0 && $distanceSinceRefuel > 0) {
                        $ratio = $distanceSinceRefuel / $fuelLiters;
                    }
                    $conn->query("UPDATE vms_vehicles SET last_refuel_odo = '$endKM' WHERE plant_plat = '{$reqData['vehicle']}'");
                }

                $newStatus = 'Pending Review';
                $stmt = $conn->prepare("UPDATE vms_bookings SET status = ?, end_km = ?, end_photo = ?, action_comment = ?, return_time = ?, updated_at = ?, is_refuel = ?, fuel_type = ?, fuel_cost = ?, fuel_liters = ?, fuel_ratio = ?, fuel_photo = ? WHERE req_id = ?");
                $stmt->bind_param("sissssdsdddss", $newStatus, $endKM, $dashUrl, $route, $currentDateTime, $currentDateTime, $isRefuel, $fuelType, $fuelCost, $fuelLiters, $ratio, $fuelPhotoUrl, $id);
                $stmt->execute();
                sendResponse(['success' => true]);
            } else {
                sendResponse(['success' => false, 'message' => 'Gagal upload foto dashboard']);
            }
        }
        
        elseif($act == 'approve') {
             $conn->query("UPDATE vms_bookings SET status = 'Approved', updated_at = '$currentDateTime' WHERE req_id = '$id'");
             sendResponse(['success' => true]);
        }
        elseif($act == 'reject' || $act == 'cancel') {
             $st = ($act == 'reject') ? 'Rejected' : 'Cancelled';
             $conn->query("UPDATE vms_bookings SET status = '$st', updated_at = '$currentDateTime' WHERE req_id = '$id'");
             $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
             sendResponse(['success' => true]);
        }
        elseif($act == 'verifyTrip') {
             $conn->query("UPDATE vms_bookings SET status = 'Done', updated_at = '$currentDateTime' WHERE req_id = '$id'");
             $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
             sendResponse(['success' => true]);
        }
        elseif($act == 'requestCorrection') {
             $conn->query("UPDATE vms_bookings SET status = 'Correction Needed', updated_at = '$currentDateTime' WHERE req_id = '$id'");
             sendResponse(['success' => true]);
        }
    }
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => $e->getMessage()]);
}
?>