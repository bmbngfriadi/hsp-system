<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 
date_default_timezone_set('Asia/Jakarta');
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require 'db.php'; 
require 'helper.php';

// --- AUTO MIGRATION TABLES ---
$conn->query("CREATE TABLE IF NOT EXISTS med_plafond (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    initial_budget DECIMAL(15,2) DEFAULT 0,
    current_budget DECIMAL(15,2) DEFAULT 0,
    last_updated DATETIME
)");

$conn->query("CREATE TABLE IF NOT EXISTS med_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    req_id VARCHAR(50) UNIQUE,
    username VARCHAR(50),
    fullname VARCHAR(100),
    department VARCHAR(50),
    invoice_no VARCHAR(100),
    amount DECIMAL(15,2) DEFAULT 0,
    photo_url VARCHAR(255),
    status VARCHAR(50),
    hrga_by VARCHAR(100),
    hrga_time DATETIME NULL,
    reject_reason TEXT,
    created_at DATETIME
)");

// AUTO ADD COLUMN UNTUK HISTORY REMAINING BALANCE
try {
    $conn->query("ALTER TABLE med_claims ADD COLUMN remaining_balance DECIMAL(15,2) DEFAULT 0");
} catch (Exception $e) { /* Abaikan jika kolom sudah ada */ }

// Helper Upload Image / PDF
function uploadMedicalFile($base64Data, $prefix) {
    $uploadDir = "../uploads/med/"; 
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
    
    $ext = ".jpg"; // default
    if (strpos($base64Data, 'application/pdf') !== false) {
        $ext = ".pdf";
    } elseif (strpos($base64Data, 'image/png') !== false) {
        $ext = ".png";
    }

    if (strpos($base64Data, 'base64,') !== false) {
        $base64Data = explode('base64,', $base64Data)[1];
    }
    $decodedData = base64_decode($base64Data);
    if ($decodedData === false) return false;
    
    $fileName = $prefix . "_" . time() . "_" . rand(100,999) . $ext;
    $filePath = $uploadDir . $fileName;
    if (file_put_contents($filePath, $decodedData)) {
        return "uploads/med/" . $fileName; 
    }
    return false;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$now = date('Y-m-d H:i:s');

try {
    // --- GET USERS FOR HRGA DROPDOWN ---
    if ($action == 'getUsers') {
        $res = $conn->query("SELECT username, fullname, department FROM users ORDER BY fullname ASC");
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        exit;
    }

    // --- 1. GET PLAFOND DATA ---
    if ($action == 'getPlafond') {
        $role = $input['role'];
        $username = $conn->real_escape_string($input['username']);
        
        if ($role === 'Administrator') {
            $sql = "SELECT u.username, u.fullname, u.department, 
                           IFNULL(p.initial_budget, 0) as initial_budget, 
                           IFNULL(p.current_budget, 0) as current_budget 
                    FROM users u LEFT JOIN med_plafond p ON u.username = p.username 
                    ORDER BY u.department, u.fullname";
            $res = $conn->query($sql);
            echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        } else {
            $sql = "SELECT initial_budget, current_budget FROM med_plafond WHERE username = '$username'";
            $res = $conn->query($sql);
            if ($row = $res->fetch_assoc()) echo json_encode(['success' => true, 'data' => $row]);
            else echo json_encode(['success' => true, 'data' => ['initial_budget' => 0, 'current_budget' => 0]]);
        }
        exit;
    }

    // --- 2. SET PLAFOND (ADMIN ONLY) ---
    if ($action == 'setBudget') {
        if ($input['role'] !== 'Administrator') { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        
        $u = $conn->real_escape_string($input['target_username']);
        $init_budget = floatval($input['initial_budget']);
        $curr_budget = floatval($input['current_budget']);
        
        $check = $conn->query("SELECT initial_budget, current_budget FROM med_plafond WHERE username = '$u'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE med_plafond SET initial_budget = $init_budget, current_budget = $curr_budget, last_updated = '$now' WHERE username = '$u'");
        } else {
            $conn->query("INSERT INTO med_plafond (username, initial_budget, current_budget, last_updated) VALUES ('$u', $init_budget, $curr_budget, '$now')");
        }
        echo json_encode(['success' => true]); exit;
    }

    // --- 3. GET CLAIMS DATA ---
    if ($action == 'getClaims') {
        $role = $input['role'];
        $username = $conn->real_escape_string($input['username']);
        $dept = $conn->real_escape_string($input['department'] ?? '');
        
        $sql = "SELECT c.*, 
                IF(c.remaining_balance > 0, c.remaining_balance, 
                   IFNULL((SELECT current_budget FROM med_plafond p WHERE p.username = c.username), 0)
                ) as display_balance 
                FROM med_claims c ";
        
        $globalRoles = ['Administrator', 'HRGA', 'PlantHead'];
        $isHrgaLeader = ($role === 'TeamLeader' && $dept === 'HRGA');
        
        if (!in_array($role, $globalRoles) && !$isHrgaLeader) {
            $sql .= "WHERE c.username = '$username' ";
        }
        $sql .= "ORDER BY c.id DESC LIMIT 100";
        
        $res = $conn->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]); exit;
    }

    // --- 4. EXPORT DATA (EXCEL & PDF) ---
    if ($action == 'exportData') {
        $role = $input['role'];
        $username = $conn->real_escape_string($input['username']);
        $dept = $conn->real_escape_string($input['department'] ?? '');
        
        $sql = "SELECT c.*, 
                IF(c.remaining_balance > 0, c.remaining_balance, 
                   IFNULL((SELECT current_budget FROM med_plafond p WHERE p.username = c.username), 0)
                ) as display_balance 
                FROM med_claims c WHERE 1=1 ";
        
        if (!empty($input['startDate']) && !empty($input['endDate'])) {
            $start = $conn->real_escape_string($input['startDate']) . " 00:00:00";
            $end = $conn->real_escape_string($input['endDate']) . " 23:59:59";
            $sql .= " AND c.created_at BETWEEN '$start' AND '$end' ";
        }
        
        $globalRoles = ['Administrator', 'HRGA', 'PlantHead'];
        $isHrgaLeader = ($role === 'TeamLeader' && $dept === 'HRGA');
        
        if (!in_array($role, $globalRoles) && !$isHrgaLeader) {
            $sql .= " AND c.username = '$username' ";
        }
        
        $sql .= " ORDER BY c.created_at ASC"; // Ascending untuk laporan audit berurutan
        
        $res = $conn->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]); exit;
    }

    // --- 5. SUBMIT CLAIM ---
    if ($action == 'submit') {
        $reqId = "MED-" . time();
        $submitterUser = $conn->real_escape_string($input['username']);
        $role = $input['role'];
        $invoiceNo = $conn->real_escape_string($input['invoiceNo']);
        $amount = floatval($input['amount']);
        $photoBase64 = $input['photoBase64'] ?? '';
        
        $targetUser = $submitterUser;
        $fullname = $conn->real_escape_string($input['fullname']);
        $dept = $conn->real_escape_string($input['department']);
        
        if (in_array($role, ['HRGA', 'Administrator']) && !empty($input['targetUsername'])) {
            $targetUser = $conn->real_escape_string($input['targetUsername']);
            $uData = $conn->query("SELECT fullname, department FROM users WHERE username='$targetUser'")->fetch_assoc();
            if($uData) {
                $fullname = $uData['fullname'];
                $dept = $uData['department'];
            }
        }

        $chk = $conn->query("SELECT current_budget FROM med_plafond WHERE username = '$targetUser'");
        $currBudget = $chk->num_rows > 0 ? floatval($chk->fetch_assoc()['current_budget']) : 0;
        
        if ($currBudget < $amount) { echo json_encode(['success' => false, 'message' => 'Plafond tidak mencukupi!']); exit; }

        $photoUrl = uploadMedicalFile($photoBase64, "INV_$reqId");
        if (!$photoUrl) { echo json_encode(['success' => false, 'message' => 'Gagal upload file.']); exit; }

        $status = in_array($role, ['HRGA', 'Administrator']) ? 'Confirmed' : 'Pending HRGA';
        $hrgaBy = ($status === 'Confirmed') ? $conn->real_escape_string($input['fullname']) : NULL;
        $hrgaTime = ($status === 'Confirmed') ? "'$now'" : "NULL";
        
        $newBudget = $currBudget - $amount;

        $conn->begin_transaction();
        try {
            $conn->query("UPDATE med_plafond SET current_budget = $newBudget, last_updated = '$now' WHERE username = '$targetUser'");
            $stmt = $conn->prepare("INSERT INTO med_claims (req_id, username, fullname, department, invoice_no, amount, photo_url, status, hrga_by, hrga_time, remaining_balance, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, $hrgaTime, ?, ?)");
            $stmt->bind_param("sssssdsssds", $reqId, $targetUser, $fullname, $dept, $invoiceNo, $amount, $photoUrl, $status, $hrgaBy, $newBudget, $now);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) { $conn->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    // --- 6. EDIT CLAIM (USER) ---
    if ($action == 'edit') {
        $reqId = $conn->real_escape_string($input['reqId']);
        $newAmount = floatval($input['amount']);
        $newInvoice = $conn->real_escape_string($input['invoiceNo']);
        $photoBase64 = $input['photoBase64'] ?? '';

        $claim = $conn->query("SELECT * FROM med_claims WHERE req_id = '$reqId'")->fetch_assoc();
        if($claim['status'] !== 'Pending HRGA') { echo json_encode(['success' => false, 'message' => 'Hanya status Pending yang bisa diedit.']); exit; }

        $oldAmount = floatval($claim['amount']);
        $u = $claim['username'];

        $plafond = $conn->query("SELECT current_budget FROM med_plafond WHERE username='$u'")->fetch_assoc();
        $currBudget = floatval($plafond['current_budget']);
        
        $diff = $newAmount - $oldAmount;
        if ($currBudget < $diff) { echo json_encode(['success' => false, 'message' => 'Plafond tidak mencukupi untuk penambahan nominal ini.']); exit; }
        
        $newBudget = $currBudget - $diff;

        $conn->begin_transaction();
        try {
            $conn->query("UPDATE med_plafond SET current_budget = $newBudget WHERE username='$u'");

            $photoQuery = "";
            if (!empty($photoBase64)) {
                $photoUrl = uploadMedicalFile($photoBase64, "INV_$reqId");
                $photoQuery = ", photo_url='$photoUrl'";
            }

            $conn->query("UPDATE med_claims SET invoice_no='$newInvoice', amount=$newAmount, remaining_balance=$newBudget $photoQuery WHERE req_id='$reqId'");
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) { $conn->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    // --- 7. UPDATE STATUS (HRGA ONLY) ---
    if ($action == 'updateStatus') {
        $reqId = $conn->real_escape_string($input['id']);
        $act = $input['act'];
        $hrgaName = $conn->real_escape_string($input['approverName']);
        $reason = $conn->real_escape_string($input['reason'] ?? '');
        
        $claim = $conn->query("SELECT * FROM med_claims WHERE req_id = '$reqId'")->fetch_assoc();
        $targetUser = $claim['username'];
        $amount = floatval($claim['amount']);

        if ($act == 'confirm') {
            $conn->query("UPDATE med_claims SET status = 'Confirmed', hrga_by = '$hrgaName', hrga_time = '$now' WHERE req_id = '$reqId'");
            echo json_encode(['success' => true]);
        } 
        elseif ($act == 'reject') {
            $plafond = $conn->query("SELECT current_budget FROM med_plafond WHERE username='$targetUser'")->fetch_assoc();
            $newBudget = floatval($plafond['current_budget']) + $amount;
            
            $conn->begin_transaction();
            try {
                $conn->query("UPDATE med_plafond SET current_budget = $newBudget, last_updated = '$now' WHERE username = '$targetUser'");
                $conn->query("UPDATE med_claims SET status = 'Rejected', hrga_by = '$hrgaName', hrga_time = '$now', reject_reason = '$reason', remaining_balance = $newBudget WHERE req_id = '$reqId'");
                $conn->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) { $conn->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        }
        exit;
    }

} catch (Exception $e) { echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]); }
?>