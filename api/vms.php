<?php
// api/vms.php - CONDITIONAL LEVEL 1 (PLANT HEAD OR DEPT HEAD)
error_reporting(E_ALL);
ini_set('display_errors', 0); 
date_default_timezone_set('Asia/Jakarta');
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
ob_start();

require 'db.php'; 
require 'helper.php';

// --- 1. ROBUST DATABASE AUTO-MIGRATION ---
function ensureColumn($conn, $table, $col, $def) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE $table ADD COLUMN $col $def");
        }
    } catch (Exception $e) {}
}

$conn->query("CREATE TABLE IF NOT EXISTS vms_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    req_id VARCHAR(50) UNIQUE,
    username VARCHAR(50),
    fullname VARCHAR(100),
    role VARCHAR(50),
    department VARCHAR(50),
    vehicle VARCHAR(50),
    purpose TEXT,
    status VARCHAR(50),
    created_at DATETIME
)");

$conn->query("CREATE TABLE IF NOT EXISTS vms_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plant_plat VARCHAR(50) UNIQUE,
    merk_tipe VARCHAR(100),
    status VARCHAR(50) DEFAULT 'Available',
    accumulated_km INT DEFAULT 0
)");

$conn->query("CREATE TABLE IF NOT EXISTS vms_settings (key_name VARCHAR(50) PRIMARY KEY, key_value VARCHAR(255))");

// Columns
ensureColumn($conn, 'vms_bookings', 'app_head', "VARCHAR(20) DEFAULT 'Pending'");
ensureColumn($conn, 'vms_bookings', 'head_time', "DATETIME DEFAULT NULL");
ensureColumn($conn, 'vms_bookings', 'head_by', "VARCHAR(100) DEFAULT NULL");
ensureColumn($conn, 'vms_bookings', 'app_plant', "VARCHAR(20) DEFAULT 'Pending'");
ensureColumn($conn, 'vms_bookings', 'plant_time', "DATETIME DEFAULT NULL");
ensureColumn($conn, 'vms_bookings', 'plant_by', "VARCHAR(100) DEFAULT NULL");
ensureColumn($conn, 'vms_bookings', 'app_ga', "VARCHAR(20) DEFAULT 'Pending'");
ensureColumn($conn, 'vms_bookings', 'ga_time', "DATETIME DEFAULT NULL");
ensureColumn($conn, 'vms_bookings', 'ga_by', "VARCHAR(100) DEFAULT NULL");
ensureColumn($conn, 'vms_bookings', 'app_final', "VARCHAR(20) DEFAULT 'Pending'");
ensureColumn($conn, 'vms_bookings', 'final_time', "DATETIME DEFAULT NULL");
ensureColumn($conn, 'vms_bookings', 'final_by', "VARCHAR(100) DEFAULT NULL");
ensureColumn($conn, 'vms_bookings', 'action_comment', "TEXT");
ensureColumn($conn, 'vms_bookings', 'start_km', "INT DEFAULT 0");
ensureColumn($conn, 'vms_bookings', 'end_km', "INT DEFAULT 0");
ensureColumn($conn, 'vms_bookings', 'start_photo', "VARCHAR(255)");
ensureColumn($conn, 'vms_bookings', 'end_photo', "VARCHAR(255)");
ensureColumn($conn, 'vms_bookings', 'depart_time', "DATETIME");
ensureColumn($conn, 'vms_bookings', 'return_time', "DATETIME");
ensureColumn($conn, 'vms_bookings', 'fuel_cost', 'DECIMAL(15,2) DEFAULT 0');
ensureColumn($conn, 'vms_bookings', 'fuel_type', 'VARCHAR(50) DEFAULT NULL');
ensureColumn($conn, 'vms_bookings', 'fuel_liters', 'DECIMAL(10,2) DEFAULT 0');
ensureColumn($conn, 'vms_bookings', 'fuel_receipt', 'VARCHAR(255) DEFAULT NULL');
ensureColumn($conn, 'vms_bookings', 'fuel_ratio', 'DECIMAL(10,2) DEFAULT 0');
ensureColumn($conn, 'vms_bookings', 'total_accumulated_km', 'INT(11) DEFAULT 0');

$chk = $conn->query("SELECT * FROM vms_settings LIMIT 1");
if ($chk && $chk->num_rows == 0) {
    $conn->query("INSERT INTO vms_settings VALUES ('price_pertamax_turbo', '13250')");
    $conn->query("INSERT INTO vms_settings VALUES ('price_pertamax', '12400')");
    $conn->query("INSERT INTO vms_settings VALUES ('price_pertalite', '10000')");
}

if (ob_get_length()) ob_clean();

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
$action = $input['action'] ?? '';
$currentDateTime = date('Y-m-d H:i:s');

function sendResponse($data) {
    if (ob_get_length()) ob_clean(); 
    echo json_encode($data);
    exit;
}

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

function getSettings($conn) {
    $res = $conn->query("SELECT * FROM vms_settings");
    $data = [];
    if($res){
        while($row = $res->fetch_assoc()) {
            $data[$row['key_name']] = $row['key_value'];
        }
    }
    return $data;
}

try {
    if ($action == 'getFuelPrices') {
        sendResponse(['success' => true, 'prices' => getSettings($conn)]);
    }

    if ($action == 'saveFuelPrices') {
        $prices = $input['prices'];
        foreach ($prices as $key => $val) {
            $k = $conn->real_escape_string($key);
            $v = $conn->real_escape_string($val);
            $conn->query("UPDATE vms_settings SET key_value = '$v' WHERE key_name = '$k'");
        }
        sendResponse(['success' => true]);
    }

    if ($action == 'getData' || $action == 'exportData') {
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
                $qLast = $conn->query("SELECT end_km, end_photo FROM vms_bookings WHERE vehicle = '{$v['plant']}' AND status = 'Done' ORDER BY return_time DESC LIMIT 1");
                if($qLast && $lastRow = $qLast->fetch_assoc()) {
                    $v['last_km'] = $lastRow['end_km'];
                    $v['last_photo'] = $lastRow['end_photo'];
                } else {
                    $v['last_km'] = 0; $v['last_photo'] = null;
                }
                $vehicles[] = $v;
            }
        }

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
                if (in_array($role, ['Administrator', 'PlantHead', 'HRGA'])) $include = true;
                elseif ($row['username'] == $username) $include = true;
                elseif ($role == 'SectionHead' && $row['department'] == $dept) $include = true;
                elseif ($role == 'TeamLeader') {
                     if ($dept == 'HRGA') $include = true; 
                     elseif ($row['department'] == $dept) $include = true; 
                }

                if($include) {
                    $row['id'] = $row['req_id'];
                    $row['timestamp'] = $row['created_at'];
                    $row['headStatus'] = $row['app_head'] ?? 'Pending';
                    $row['headTime'] = $row['head_time'] ?? null;
                    $row['headBy'] = $row['head_by'] ?? '-';
                    $row['plantStatus'] = $row['app_plant'] ?? 'Pending';
                    $row['plantTime'] = $row['plant_time'] ?? null;
                    $row['plantBy'] = $row['plant_by'] ?? '-';
                    $row['gaStatus'] = $row['app_ga'] ?? 'Pending';
                    $row['gaTime'] = $row['ga_time'] ?? null;
                    $row['gaBy'] = $row['ga_by'] ?? '-';
                    $row['finalStatus'] = $row['app_final'] ?? 'Pending';
                    $row['finalTime'] = $row['final_time'] ?? null;
                    $row['finalBy'] = $row['final_by'] ?? '-';
                    $row['actionComment'] = $row['action_comment'] ?? '';
                    $row['startKm'] = $row['start_km'] ?? 0;
                    $row['endKm'] = $row['end_km'] ?? 0;
                    $row['departTime'] = $row['depart_time'] ?? null;
                    $row['returnTime'] = $row['return_time'] ?? null;
                    $row['startPhoto'] = $row['start_photo'] ?? null;
                    $row['endPhoto'] = $row['end_photo'] ?? null;
                    $row['fuelCost'] = $row['fuel_cost'] ?? 0;
                    $row['fuelType'] = $row['fuel_type'] ?? null;
                    $row['fuelLiters'] = $row['fuel_liters'] ?? 0;
                    $row['fuelReceipt'] = $row['fuel_receipt'] ?? null;
                    $row['fuelRatio'] = $row['fuel_ratio'] ?? 0;
                    $row['totalAccumulatedKm'] = $row['total_accumulated_km'] ?? 0;
                    
                    $bookings[] = $row;
                }
            }
        }
        sendResponse(['success' => true, 'vehicles' => $vehicles, 'bookings' => $bookings]);
    }

    if ($action == 'submit') {
        $reqId = "VMS-" . time();
        $reqDept = $input['department'];
        $reqRole = $input['role'];
        
        $appHead = 'Pending'; $appPlant = 'Pending'; $appGa = 'Pending'; $appFinal = 'Pending';
        
        // --- LOGIC PENENTUAN STATUS AWAL ---
        if (in_array($reqRole, ['TeamLeader', 'SectionHead'])) {
            // Jalur Khusus: Langsung ke Plant Head
            $status = 'Pending Plant Head';
            $appHead = 'Auto-Skip'; // Bypass Dept Head
            $appPlant = 'Pending';
        } else {
            // Jalur Normal: Dept Head -> HRGA
            $status = 'Pending Dept Head';
            $appHead = 'Pending';
            $appPlant = 'Auto-Skip'; // Bypass Plant Head (kecuali nanti diubah logika lain, tapi sesuai request ini skip)
        }
        
        $stmt = $conn->prepare("INSERT INTO vms_bookings (req_id, username, fullname, role, department, vehicle, purpose, status, app_head, app_plant, app_ga, app_final, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssssss", $reqId, $input['username'], $input['fullname'], $input['role'], $input['department'], $input['vehicle'], $input['purpose'], $status, $appHead, $appPlant, $appGa, $appFinal, $currentDateTime);
        
        if($stmt->execute()) {
            $conn->query("UPDATE vms_vehicles SET status = 'Reserved' WHERE plant_plat = '{$input['vehicle']}'");
            
            try {
                $userPhone = getUserPhone($conn, $input['username']);
                if($userPhone) sendWA($userPhone, "📋 *VMS - SUBMITTED*\nUnit: {$input['vehicle']}\nStatus: Menunggu Approval Level 1.");
                
                // --- LOGIC NOTIFIKASI WA ---
                if ($status == 'Pending Plant Head') {
                    // Notif ke Plant Head
                    $phonesPH = getPhones($conn, 'PlantHead');
                    foreach($phonesPH as $ph) sendWA($ph, "🚗 *VMS - APPROVAL PLANT HEAD (L1)*\nUser: {$input['fullname']} ({$reqRole})\nUnit: {$input['vehicle']}\nTujuan: {$input['purpose']}\n👉 _Mohon Approve._");
                } else {
                    // Notif ke Dept Head (SectionHead/TeamLeader)
                    $phonesL1 = getPhones($conn, 'SectionHead', $reqDept);
                    if (empty($phonesL1) && $reqDept !== 'HRGA') $phonesL1 = getPhones($conn, 'TeamLeader', $reqDept);
                    if(is_array($phonesL1)) foreach($phonesL1 as $ph) sendWA($ph, "🚗 *VMS - APPROVAL DEPT HEAD (L1)*\nUser: {$input['fullname']}\nUnit: {$input['vehicle']}\nTujuan: {$input['purpose']}\n👉 _Mohon Approve._");
                }

            } catch (Exception $e) {}
            sendResponse(['success' => true]);
        } else {
            sendResponse(['success' => false, 'message' => $stmt->error]);
        }
    }

    if ($action == 'updateStatus') {
        $id = $conn->real_escape_string($input['id']); 
        $act = $input['act']; 
        $extra = $input['extraData'] ?? [];
        $approverName = $conn->real_escape_string($input['approverName'] ?? 'System');
        
        $qry = $conn->query("SELECT * FROM vms_bookings WHERE req_id = '$id'");
        if (!$qry || $qry->num_rows == 0) sendResponse(['success' => false, 'message' => 'Data not found']);
        $reqData = $qry->fetch_assoc();

        $userPhone = getUserPhone($conn, $reqData['username']);
        $currentStatus = $reqData['status'];

        if($act == 'approve') {
            $rawComment = $conn->real_escape_string($extra['comment'] ?? '');
            $waNote = $rawComment ? "\n📝 *Note:* $rawComment" : "";
            
            // 1. APPROVAL LEVEL 1 (Bisa Dept Head ATAU Plant Head)
            if ($currentStatus == 'Pending Dept Head') {
                $dbComment = $rawComment ? "Dept Head Approved by $approverName: $rawComment" : "";
                $sql = "UPDATE vms_bookings SET status = 'Pending HRGA', app_head = 'Approved', head_time = '$currentDateTime', head_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if($conn->query($sql)) {
                    try {
                        if($userPhone) sendWA($userPhone, "✅ *VMS - L1 APPROVED*\nDept Head menyetujui.$waNote\nNext: Approval HRGA.");
                        $phonesL2 = getPhones($conn, 'HRGA'); 
                        foreach($phonesL2 as $ph) sendWA($ph, "🚗 *VMS - APPROVAL HRGA*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\n👉 _Mohon dicek & Approve._");
                    } catch (Exception $e) {}
                }
            }
            elseif ($currentStatus == 'Pending Plant Head') {
                $dbComment = $rawComment ? "Plant Head Approved by $approverName: $rawComment" : "";
                // Setelah Plant Head, langsung ke HRGA
                $sql = "UPDATE vms_bookings SET status = 'Pending HRGA', app_plant = 'Approved', plant_time = '$currentDateTime', plant_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if($conn->query($sql)) {
                    try {
                        if($userPhone) sendWA($userPhone, "✅ *VMS - L1 APPROVED*\nPlant Head menyetujui.$waNote\nNext: Approval HRGA.");
                        $phonesL2 = getPhones($conn, 'HRGA'); 
                        foreach($phonesL2 as $ph) sendWA($ph, "🚗 *VMS - APPROVAL HRGA*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\n👉 _Mohon dicek & Approve._");
                    } catch (Exception $e) {}
                }
            }
            // 2. APPROVAL LEVEL 2 (HRGA)
            elseif ($currentStatus == 'Pending HRGA') {
                $dbComment = $rawComment ? "L2 Approved by $approverName: $rawComment" : "";
                $sql = "UPDATE vms_bookings SET status = 'Pending Final', app_ga = 'Approved', ga_time = '$currentDateTime', ga_by = '$approverName', action_comment = '$dbComment' WHERE req_id = '$id'";
                if($conn->query($sql)) {
                    try {
                        if($userPhone) sendWA($userPhone, "✅ *VMS - HRGA APPROVED*\nHRGA menyetujui.$waNote\nNext: Approval Final.");
                        $phonesTL = getPhones($conn, 'TeamLeader', 'HRGA');
                        $phonesBackup = getPhones($conn, 'HRGA');
                        $allL3 = array_unique(array_merge($phonesTL, $phonesBackup));
                        foreach($allL3 as $ph) sendWA($ph, "🚗 *VMS - APPROVAL FINAL*\nUser: {$reqData['fullname']}\nUnit: {$reqData['vehicle']}\n👉 _Mohon persetujuan Final._");
                    } catch (Exception $e) {}
                }
            }
            // 3. APPROVAL LEVEL 3 (FINAL)
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
        elseif($act == 'reject') {
            $reason = $conn->real_escape_string($extra['comment'] ?? '-');
            $fullComment = "Rejected by {$approverName}: {$reason}";
            $updatePart = "";
            
            if($currentStatus == 'Pending Dept Head') $updatePart = ", app_head = 'Rejected', head_by = '$approverName', head_time = '$currentDateTime'";
            elseif($currentStatus == 'Pending Plant Head') $updatePart = ", app_plant = 'Rejected', plant_by = '$approverName', plant_time = '$currentDateTime'";
            elseif($currentStatus == 'Pending HRGA') $updatePart = ", app_ga = 'Rejected', ga_by = '$approverName', ga_time = '$currentDateTime'";
            elseif($currentStatus == 'Pending Final') $updatePart = ", app_final = 'Rejected', final_by = '$approverName', final_time = '$currentDateTime'";
            
            $conn->query("UPDATE vms_bookings SET status = 'Rejected', action_comment = '$fullComment' $updatePart WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            try { if($userPhone) sendWA($userPhone, "❌ *VMS - REJECTED*\nDitolak oleh {$approverName}.\nAlasan: {$reason}"); } catch (Exception $e) {}
            sendResponse(['success' => true]);
        }
        elseif($act == 'cancel') {
            $comment = $conn->real_escape_string($extra['comment'] ?? 'User Cancelled');
            $conn->query("UPDATE vms_bookings SET status = 'Cancelled', action_comment = '$comment', final_time = '$currentDateTime' WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            sendResponse(['success' => true]);
        }
        elseif($act == 'startTrip') {
            $url = uploadImageInternal($extra['photoBase64'], "START_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $conn->query("UPDATE vms_bookings SET status = 'Active', start_km = '{$extra['km']}', start_photo = '$url', depart_time = '$currentDateTime' WHERE req_id = '$id'");
                $conn->query("UPDATE vms_vehicles SET status = 'In Use' WHERE plant_plat = '{$reqData['vehicle']}'");
                sendResponse(['success' => true]);
            } else { sendResponse(['success' => false, 'message' => 'Image upload failed']); }
        }
        elseif($act == 'endTrip') {
            $url = uploadImageInternal($extra['photoBase64'], "END_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $route = $conn->real_escape_string($extra['route'] ?? '-');
                $endKM = intval($extra['km']);
                $startKM = intval($reqData['start_km']);
                $currentTripDist = $endKM - $startKM;
                if ($currentTripDist < 0) $currentTripDist = 0;

                $fuelCost = 0; $fuelType = null; $fuelLiters = 0; $fuelRatio = 0; $receiptUrl = null;
                $totalAccumulatedKm = 0;

                $vehPlat = $reqData['vehicle'];
                $vehQ = $conn->query("SELECT accumulated_km FROM vms_vehicles WHERE plant_plat = '$vehPlat'");
                $vehRow = $vehQ->fetch_assoc();
                $prevAccumulated = intval($vehRow['accumulated_km'] ?? 0);

                if (!empty($extra['fuelCost']) && $extra['fuelCost'] > 0) {
                    $fuelCost = floatval($extra['fuelCost']);
                    $fuelType = $conn->real_escape_string($extra['fuelType']);
                    $settings = getSettings($conn);
                    $priceKey = 'price_' . strtolower(str_replace(' ', '_', $fuelType)); 
                    $pricePerLiter = floatval($settings[$priceKey] ?? 10000);
                    if ($pricePerLiter > 0) {
                        $fuelLiters = $fuelCost / $pricePerLiter;
                        if ($fuelLiters > 0) {
                            $totalAccumulatedKm = $prevAccumulated + $currentTripDist;
                            $fuelRatio = $totalAccumulatedKm / $fuelLiters;
                        }
                    }
                    if (!empty($extra['receiptBase64'])) {
                        $receiptUrl = uploadImageInternal($extra['receiptBase64'], "STRUK_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
                    }
                    $conn->query("UPDATE vms_vehicles SET accumulated_km = 0 WHERE plant_plat = '$vehPlat'");
                } else {
                    $newAccumulated = $prevAccumulated + $currentTripDist;
                    $conn->query("UPDATE vms_vehicles SET accumulated_km = $newAccumulated WHERE plant_plat = '$vehPlat'");
                    $totalAccumulatedKm = $newAccumulated; 
                }

                $stmt = $conn->prepare("UPDATE vms_bookings SET status = 'Pending Review', end_km = ?, end_photo = ?, action_comment = ?, return_time = ?, fuel_cost = ?, fuel_type = ?, fuel_liters = ?, fuel_receipt = ?, fuel_ratio = ?, total_accumulated_km = ? WHERE req_id = ?");
                $stmt->bind_param("isssdsdssds", $endKM, $url, $route, $currentDateTime, $fuelCost, $fuelType, $fuelLiters, $receiptUrl, $fuelRatio, $totalAccumulatedKm, $id);
                $stmt->execute();
                sendResponse(['success' => true]);
            } else { sendResponse(['success' => false, 'message' => 'Image upload failed']); }
        }
        elseif($act == 'verifyTrip') {
            $conn->query("UPDATE vms_bookings SET status = 'Done' WHERE req_id = '$id'");
            $conn->query("UPDATE vms_vehicles SET status = 'Available' WHERE plant_plat = '{$reqData['vehicle']}'");
            sendResponse(['success' => true]);
        }
        elseif($act == 'requestCorrection') {
            $reason = $conn->real_escape_string($extra['comment']);
            $conn->query("UPDATE vms_bookings SET status = 'Correction Needed', action_comment = 'Correction requested by $approverName: $reason' WHERE req_id = '$id'");
            sendResponse(['success' => true]);
        }
        elseif($act == 'submitCorrection') {
            $url = uploadImageInternal($extra['photoBase64'], "FIX_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
            if($url) {
                $route = $conn->real_escape_string($extra['route'] ?? '-');
                $endKM = intval($extra['km']);
                $fuelCost = 0; $fuelType = null; $fuelLiters = 0; $fuelRatio = 0; 
                $receiptUrl = $reqData['fuel_receipt']; 

                if (!empty($extra['fuelCost']) && $extra['fuelCost'] > 0) {
                    $fuelCost = floatval($extra['fuelCost']);
                    $fuelType = $conn->real_escape_string($extra['fuelType']);
                    $settings = getSettings($conn);
                    $priceKey = 'price_' . strtolower(str_replace(' ', '_', $fuelType)); 
                    $pricePerLiter = floatval($settings[$priceKey] ?? 10000);
                    if ($pricePerLiter > 0) {
                        $fuelLiters = $fuelCost / $pricePerLiter;
                        if ($fuelLiters > 0) {
                            $startKM = intval($reqData['start_km']);
                            $currentTripDist = $endKM - $startKM;
                            if($currentTripDist < 0) $currentTripDist = 0;
                            $fuelRatio = $currentTripDist / $fuelLiters; 
                        }
                    }
                    if (!empty($extra['receiptBase64'])) {
                        $receiptUrl = uploadImageInternal($extra['receiptBase64'], "STRUK_FIX_" . preg_replace('/[^a-zA-Z0-9]/', '', $id));
                    }
                }
                $stmt = $conn->prepare("UPDATE vms_bookings SET status = 'Pending Review', end_km = ?, end_photo = ?, action_comment = ?, fuel_cost = ?, fuel_type = ?, fuel_liters = ?, fuel_receipt = ?, fuel_ratio = ? WHERE req_id = ?");
                $stmt->bind_param("isssdsdss", $endKM, $url, $route, $fuelCost, $fuelType, $fuelLiters, $receiptUrl, $fuelRatio, $id);
                $stmt->execute();
                sendResponse(['success' => true]);
            }
        }
    }
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}
?>