<?php
require 'db.php'; 
require 'helper.php';

// --- CONFIG & HEADERS ---
date_default_timezone_set('Asia/Jakarta'); 
$conn->query("SET time_zone = '+07:00'");
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// --- 1. GET MASTER ITEMS ---
if($action == 'inventory') {
    $res = $conn->query("SELECT * FROM atk_items ORDER BY name ASC");
    sendJson($res->fetch_all(MYSQLI_ASSOC));
}

// --- 2. GET DEPT STOCK ---
if($action == 'getDeptStock') {
    $role = $input['role'] ?? '';
    $userDept = $conn->real_escape_string($input['department']);
    $targetDept = $conn->real_escape_string($input['targetDept'] ?? 'All');

    $viewDept = $userDept; 
    if (in_array($role, ['HRGA', 'Administrator', 'PlantHead'])) {
        $viewDept = ($targetDept !== 'All') ? $targetDept : 'All';
    }

    if ($viewDept === 'All') {
        $sql = "SELECT IFNULL(s.id, 0) as id, IFNULL(s.department, '-') as department, m.name as item_name, m.uom as unit, IFNULL(s.qty, 0) as qty, IFNULL(s.last_updated, '-') as last_updated FROM atk_items m LEFT JOIN atk_dept_stock s ON m.name = s.item_name ORDER BY m.name ASC, s.department ASC";
    } else {
        $sql = "SELECT IFNULL(s.id, 0) as id, '$viewDept' as department, m.name as item_name, m.uom as unit, IFNULL(s.qty, 0) as qty, IFNULL(s.last_updated, '-') as last_updated FROM atk_items m LEFT JOIN atk_dept_stock s ON m.name = s.item_name AND s.department = '$viewDept' ORDER BY m.name ASC";
    }
    $res = $conn->query($sql);
    sendJson($res->fetch_all(MYSQLI_ASSOC));
}

// --- 3. BULK UPDATE STOCK (ADMIN) ---
if($action == 'updateStockBulk') {
    $role = $input['role'] ?? '';
    if ($role !== 'Administrator') sendJson(['success'=>false, 'message'=>'Unauthorized. Admin only.']);
    $items = $input['data']; $now = date('Y-m-d H:i:s');
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO atk_dept_stock (department, item_name, qty, unit, last_updated) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE qty = VALUES(qty), last_updated = VALUES(last_updated)");
        foreach($items as $it) {
            $dept = $it['department']; $name = $it['item_name']; $qty = intval($it['qty']); $unit = $it['unit'];
            if($dept !== '-' && !empty($dept)) { $stmt->bind_param("ssiss", $dept, $name, $qty, $unit, $now); $stmt->execute(); }
        }
        $conn->commit();
        sendJson(['success'=>true, 'message'=>'Bulk stock updated successfully']);
    } catch (Exception $e) { $conn->rollback(); sendJson(['success'=>false, 'message'=>$e->getMessage()]); }
}

// --- 4. UPDATE STOCK MANUAL (ADMIN) ---
if($action == 'updateStock') {
    $role = $input['role'] ?? '';
    if ($role !== 'Administrator') sendJson(['success'=>false, 'message'=>'Unauthorized. Admin only.']);
    $dept = $conn->real_escape_string($input['department']); $item = $conn->real_escape_string($input['item_name']);
    $unit = $conn->real_escape_string($input['unit']); $qty = intval($input['qty']); $now = date('Y-m-d H:i:s');
    if($dept == '-' || empty($dept)) sendJson(['success'=>false, 'message'=>'Invalid Department']);
    $sql = "INSERT INTO atk_dept_stock (department, item_name, qty, unit, last_updated) VALUES ('$dept', '$item', $qty, '$unit', '$now') ON DUPLICATE KEY UPDATE qty = VALUES(qty), last_updated = VALUES(last_updated), unit = VALUES(unit)";
    if($conn->query($sql)) sendJson(['success'=>true, 'message'=>'Stock updated successfully']);
    else sendJson(['success'=>false, 'message'=>$conn->error]);
}

// --- 5. GET STOCK DEPTS ---
if($action == 'getStockDepts') {
    $res = $conn->query("SELECT DISTINCT department FROM users WHERE department != '' ORDER BY department ASC");
    $data = []; while($r = $res->fetch_assoc()) $data[] = $r['department'];
    sendJson($data);
}

// --- 6. IMPORT MASTER ITEMS (ADMIN) ---
if($action == 'importMasterItems') {
    $role = $input['role'] ?? '';
    if ($role !== 'Administrator') sendJson(['success'=>false, 'message'=>'Unauthorized.']);
    $items = $input['data']; $successCount = 0; $failCount = 0;
    $stmt = $conn->prepare("INSERT INTO atk_items (name, uom) VALUES (?, ?) ON DUPLICATE KEY UPDATE uom = VALUES(uom)");
    foreach ($items as $item) {
        $name = trim($item['name']); $unit = trim($item['unit']);
        if (!empty($name) && !empty($unit)) { $stmt->bind_param("ss", $name, $unit); if ($stmt->execute()) $successCount++; else $failCount++; }
    }
    sendJson(['success' => true, 'message' => "Import: $successCount Success"]);
}

// --- 7. GET DATA REQUESTS ---
if($action == 'getData' || $action == 'exportData') {
    $userRole = $input['role'] ?? '';
    $userDept = $conn->real_escape_string($input['department'] ?? '');
    $sql = "SELECT * FROM atk_requests WHERE 1=1";
    $globalViewRoles = ['HRGA', 'PlantHead', 'Administrator'];
    if (!in_array($userRole, $globalViewRoles)) $sql .= " AND department = '$userDept'";
    if ($action == 'exportData') {
        if (!empty($input['startDate']) && !empty($input['endDate'])) {
            $start = $input['startDate'] . " 00:00:00"; $end = $input['endDate'] . " 23:59:59";
            $sql .= " AND created_at BETWEEN '$start' AND '$end'";
        }
        $sql .= " ORDER BY created_at ASC";
    } else { $sql .= " ORDER BY id DESC LIMIT 50"; }
    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $row['timestamp'] = $row['created_at']; $row['id'] = $row['req_id'];
        $row['items'] = json_decode($row['items_json']);
        $row['appHead'] = $row['app_head']; $row['appHrga'] = $row['app_hrga'];
        $row['receivedAt'] = $row['received_at']; $row['headActionAt'] = $row['head_action_at'];
        $row['hrgaActionAt'] = $row['hrga_action_at']; $row['rejectedAt'] = $row['rejected_at'];
        $row['canceledAt'] = $row['canceled_at']; $row['rejectReason'] = $row['reject_reason'];
        $data[] = $row;
    }
    sendJson($data);
}

// --- 8. SUBMIT NEW REQUEST ---
if($action == 'submit') {
    $reqId = "ATK-" . time();
    $username = $input['username']; $fullname = $input['fullname']; $dept = $input['department'];
    $period = $conn->real_escape_string($input['period']); $reason = $conn->real_escape_string($input['reason']);
    $itemsArr = $input['items']; $itemsJson = json_encode($itemsArr);
    $targetRoles = ['SectionHead', 'TeamLeader']; 
    if (strtoupper($dept) === 'HRGA') $targetRoles = ['PlantHead', 'TeamLeader'];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO atk_requests (req_id, username, fullname, department, period, items_json, reason, status, app_head, app_hrga, reject_reason) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Head', 'Pending', 'Pending', '')");
        $stmt->bind_param("sssssss", $reqId, $username, $fullname, $dept, $period, $itemsJson, $reason);
        $stmt->execute();
        foreach($itemsArr as $it) {
            $usage = intval($it['last_usage']);
            if($usage > 0) {
                $iName = $conn->real_escape_string($it['name']);
                $conn->query("UPDATE atk_dept_stock SET qty = GREATEST(qty - $usage, 0), last_updated = NOW() WHERE department = '$dept' AND item_name = '$iName'");
            }
        }
        $conn->commit();

        $itemListWA = formatItemListForWA($itemsJson);

        // Notify Head (Pending Approval)
        $approverPhones = getPhones($conn, $targetRoles, $dept);
        $msgHead = "📝 *ATK REQUEST - APPROVAL NEEDED*\n" .
                   "--------------------------------\n" .
                   "User: $fullname\n" .
                   "Dept: $dept\n" .
                   "Period: $period\n" .
                   "Reason: $reason\n\n" .
                   "📦 *ITEMS REQUESTED:*\n" . $itemListWA . "\n" .
                   "👉 _Please Login to Portal to Approve._";
        foreach($approverPhones as $ph) sendWA($ph, $msgHead);
        
        // Notify User (Submitted)
        $userPhone = getUserPhone($conn, $username);
        if($userPhone) {
            $msgUser = "✅ *ATK REQUEST - SUBMITTED*\n" .
                       "ID: $reqId\n" .
                       "Period: $period\n" .
                       "Status: Pending Head Approval\n\n" .
                       "📦 *ITEMS:*\n" . $itemListWA;
            sendWA($userPhone, $msgUser);
        }

        sendJson(['success'=>true, 'message'=>'Request Submitted']);
    } catch (Exception $e) { $conn->rollback(); sendJson(['success'=>false, 'message'=>'Error: ' . $e->getMessage()]); }
}

// --- 9. EDIT REQUEST ---
if($action == 'edit') {
    $reqId = $input['id']; $username = $input['username']; $fullname = $input['fullname']; $dept = $input['department'];
    $period = $conn->real_escape_string($input['period']); $reason = $conn->real_escape_string($input['reason']);
    $itemsArr = $input['items']; $newItemsJson = json_encode($itemsArr);
    
    $qry = $conn->query("SELECT * FROM atk_requests WHERE req_id = '$reqId'");
    $oldRow = $qry->fetch_assoc();
    if($oldRow['username'] !== $username) sendJson(['success'=>false, 'message'=>'Unauthorized']);
    if($oldRow['status'] !== 'Pending Head') sendJson(['success'=>false, 'message'=>'Cannot edit approved request']);
    
    $conn->begin_transaction();
    try {
        // Rollback Old Stock
        $oldItems = json_decode($oldRow['items_json'], true);
        foreach($oldItems as $it) {
            $usage = intval($it['last_usage']); 
            if($usage > 0) { $iName = $conn->real_escape_string($it['name']); $conn->query("UPDATE atk_dept_stock SET qty = qty + $usage, last_updated = NOW() WHERE department = '$dept' AND item_name = '$iName'"); }
        }
        // Update Request
        $stmt = $conn->prepare("UPDATE atk_requests SET period = ?, items_json = ?, reason = ? WHERE req_id = ?");
        $stmt->bind_param("ssss", $period, $newItemsJson, $reason, $reqId);
        $stmt->execute();
        // Deduct New Stock
        foreach($itemsArr as $it) {
            $usage = intval($it['last_usage']); 
            if($usage > 0) { $iName = $conn->real_escape_string($it['name']); $conn->query("UPDATE atk_dept_stock SET qty = GREATEST(qty - $usage, 0), last_updated = NOW() WHERE department = '$dept' AND item_name = '$iName'"); }
        }
        $conn->commit();

        $itemListWA = formatItemListForWA($newItemsJson);

        // Notify Head (Edited)
        $targetRoles = ['SectionHead', 'TeamLeader']; 
        if (strtoupper($dept) === 'HRGA') $targetRoles = ['PlantHead', 'TeamLeader'];
        $approverPhones = getPhones($conn, $targetRoles, $dept);
        
        $msgUpdate = "✏️ *ATK REQUEST - UPDATED*\n" .
                     "User: $fullname has edited request #$reqId.\n\n" .
                     "📅 Period: $period\n" .
                     "💬 Reason: $reason\n\n" .
                     "📦 *UPDATED ITEMS LIST:*\n" . $itemListWA . "\n" .
                     "👉 _Please review the updated request._";
        foreach($approverPhones as $ph) sendWA($ph, $msgUpdate);

        sendJson(['success'=>true, 'message'=>'Request Updated']);
    } catch (Exception $e) { $conn->rollback(); sendJson(['success'=>false, 'message'=>'Error: ' . $e->getMessage()]); }
}

// --- 10. UPDATE STATUS (ACTIONS) ---
if($action == 'updateStatus') {
    $id = $input['id']; $act = $input['act']; $role = $input['role'] ?? ''; $fullname = $input['fullname'] ?? ''; 
    $reason = $conn->real_escape_string($input['reason'] ?? ''); $now = date('Y-m-d H:i:s');
    
    $qry = $conn->query("SELECT * FROM atk_requests WHERE req_id = '$id'");
    if($qry->num_rows == 0) sendJson(['success'=>false, 'message'=>'Data not found']);
    $row = $qry->fetch_assoc();
    $items = json_decode($row['items_json'], true); 
    $reqDept = $row['department'];
    $itemListWA = formatItemListForWA($row['items_json']);
    $reqPhone = getUserPhone($conn, $row['username']);

    // --- CONFIRM RECEIVE ---
    if($act == 'confirmReceive') {
        if($row['username'] !== $input['username']) sendJson(['success'=>false, 'message'=>'Unauthorized']);
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE atk_requests SET status='Completed', received_at='$now', received_by='$fullname' WHERE req_id='$id'");
            foreach($items as $it) {
                $iName = $conn->real_escape_string($it['name']); $iQty = intval($it['qty']); $iUnit = $conn->real_escape_string($it['unit']);
                $sqlStock = "INSERT INTO atk_dept_stock (department, item_name, qty, unit, last_updated) VALUES ('$reqDept', '$iName', $iQty, '$iUnit', '$now') ON DUPLICATE KEY UPDATE qty = qty + $iQty, last_updated = '$now'";
                $conn->query($sqlStock);
            }
            $conn->commit();
            
            if($reqPhone) {
                sendWA($reqPhone, "📦 *ATK REQUEST - RECEIVED*\n" .
                                  "You confirmed receipt for Request #$id.\n" .
                                  "Date: $now\n\n" .
                                  "📦 *ITEMS RECEIVED:*\n" . $itemListWA . "\n" .
                                  "✅ _Dept Inventory Updated._");
            }
            sendJson(['success'=>true, 'message'=>'Items Received & Stock Added']);
        } catch (Exception $e) { $conn->rollback(); sendJson(['success'=>false, 'message'=>$e->getMessage()]); }
        return;
    }

    // --- CANCEL ---
    if($act == 'cancel') {
        if($row['username'] !== $input['username']) sendJson(['success'=>false, 'message'=>'Unauthorized']);
        $conn->query("UPDATE atk_requests SET status='Canceled', reject_reason='User Cancelled: $reason', canceled_at='$now' WHERE req_id='$id'");
        
        // Notify User (Self confirmation)
        if($reqPhone) {
            sendWA($reqPhone, "🚫 *ATK REQUEST - CANCELLED*\n" .
                              "Request #$id has been cancelled.\n" .
                              "Reason: $reason\n\n" .
                              "📦 *ITEMS:*\n" . $itemListWA);
        }
        sendJson(['success'=>true, 'message'=>'Request Canceled']);
    }

    // --- APPROVE ---
    if($act == 'approve') {
        if ($row['status'] == 'Pending Head') {
             if (strtoupper($row['department']) == 'HRGA') {
                $conn->query("UPDATE atk_requests SET status='Approved', app_head='Approved by $fullname (L1)', head_action_at='$now', app_hrga='Auto-Approved (Internal)', hrga_action_at='$now' WHERE req_id='$id'");
                // Notify User (Fully Approved)
                if($reqPhone) sendWA($reqPhone, "🎉 *ATK REQUEST - APPROVED*\nRequest #$id is fully approved.\n\n📦 *ITEMS:*\n" . $itemListWA);
            } else {
                $conn->query("UPDATE atk_requests SET status='Pending HRGA', app_head='Approved by $fullname', head_action_at='$now' WHERE req_id='$id'");
                
                // Notify HRGA
                $hrgaPhones = getPhones($conn, 'HRGA');
                foreach($hrgaPhones as $ph) {
                    sendWA($ph, "⏳ *ATK REQUEST - VERIFICATION (L2)*\n" .
                                "User: {$row['fullname']}\n" .
                                "Dept: {$row['department']}\n" .
                                "Status: Approved by Head ($fullname).\n\n" .
                                "📦 *ITEMS:*\n" . $itemListWA . "\n" .
                                "👉 _Please verify stock & Approve._");
                }
                
                // Notify User (L1 Approved)
                if($reqPhone) sendWA($reqPhone, "✅ *ATK REQUEST - HEAD APPROVED*\nRequest #$id approved by Head.\nStatus: Waiting for HRGA Verification.\n\n📦 *ITEMS:*\n" . $itemListWA);
            }
        }
        elseif ($role == 'HRGA' && $row['status'] == 'Pending HRGA') {
            $conn->query("UPDATE atk_requests SET status='Approved', app_hrga='Approved by $fullname', hrga_action_at='$now' WHERE req_id='$id'");
            
            // Notify User (Ready)
            if($reqPhone) sendWA($reqPhone, "🎉 *ATK REQUEST - READY / APPROVED*\n" .
                                            "Request #$id is fully approved by HRGA.\n\n" .
                                            "📦 *ITEMS:*\n" . $itemListWA . "\n" .
                                            "👉 _Please collect items at GA/Stationary and Confirm Receipt in Portal._");
        }
        sendJson(['success'=>true]);
    }

    // --- REJECT ---
    if($act == 'reject') {
        $sql = "UPDATE atk_requests SET status='Rejected', reject_reason='$reason', rejected_at='$now'";
        if ($row['status'] == 'Pending Head') { $sql .= ", app_head='Rejected by $fullname', head_action_at='$now'"; } 
        elseif ($row['status'] == 'Pending HRGA') { $sql .= ", app_hrga='Rejected by $fullname', hrga_action_at='$now'"; }
        $sql .= " WHERE req_id='$id'";
        $conn->query($sql);
        
        // Notify User
        if($reqPhone) {
            sendWA($reqPhone, "❌ *ATK REQUEST - REJECTED*\n" .
                              "Request #$id was rejected by $fullname.\n\n" .
                              "💬 *Reason:* $reason\n\n" .
                              "📦 *ITEMS:*\n" . $itemListWA);
        }
        sendJson(['success'=>true]);
    }
}

// Helper untuk Format Item List di WA (English Standard)
function formatItemListForWA($itemsJson) {
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) return "-";
    $txt = "";
    foreach($items as $it) {
        $txt .= "• {$it['name']} (Req: {$it['qty']} {$it['unit']})\n";
    }
    return $txt;
}
?>