<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 
date_default_timezone_set('Asia/Jakarta');
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require 'db.php'; 
require 'helper.php';

// Cek Jika Payload Terlalu Besar
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input && !empty($rawInput)) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar. Gagal memproses data.']);
    exit;
}

$action = $input['action'] ?? '';
$now = date('Y-m-d H:i:s');

// --- AUTO MIGRATION TABLES & COLUMNS ---
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

// Tambah Kolom Kategori Baru (Abaikan jika sudah ada)
$newColsPlafond = [
    "initial_kacamata" => "DECIMAL(15,2) DEFAULT 0",
    "current_kacamata" => "DECIMAL(15,2) DEFAULT 0",
    "initial_persalinan" => "DECIMAL(15,2) DEFAULT 0",
    "current_persalinan" => "DECIMAL(15,2) DEFAULT 0",
    "initial_inap" => "DECIMAL(15,2) DEFAULT 0",
    "current_inap" => "DECIMAL(15,2) DEFAULT 0",
    "harga_kamar" => "DECIMAL(15,2) DEFAULT 0"
];
foreach($newColsPlafond as $col => $def) {
    try { $conn->query("ALTER TABLE med_plafond ADD COLUMN $col $def"); } catch (Exception $e) {}
}

try { $conn->query("ALTER TABLE med_claims ADD COLUMN remaining_balance DECIMAL(15,2) NULL DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE med_claims ADD COLUMN claim_type VARCHAR(50) DEFAULT 'Rawat Jalan'"); } catch (Exception $e) {}

// Helper Upload Image / PDF
function uploadMedicalFile($base64Data, $prefix) {
    $uploadDir = "../uploads/med/"; 
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
    $ext = ".jpg";
    if (strpos($base64Data, 'application/pdf') !== false) $ext = ".pdf";
    elseif (strpos($base64Data, 'image/png') !== false) $ext = ".png";
    if (strpos($base64Data, 'base64,') !== false) $base64Data = explode('base64,', $base64Data)[1];
    
    $decodedData = base64_decode($base64Data);
    if ($decodedData === false) return false;
    
    $fileName = $prefix . "_" . time() . "_" . rand(100,999) . $ext;
    $filePath = $uploadDir . $fileName;
    if (file_put_contents($filePath, $decodedData)) return "uploads/med/" . $fileName; 
    return false;
}

function getCol($type, $isInitial = false) {
    if ($type === 'Kacamata') return $isInitial ? 'initial_kacamata' : 'current_kacamata';
    if ($type === 'Persalinan') return $isInitial ? 'initial_persalinan' : 'current_persalinan';
    if ($type === 'Rawat Inap') return $isInitial ? 'initial_inap' : 'current_inap';
    return $isInitial ? 'initial_budget' : 'current_budget'; // Rawat Jalan
}

try {
    if ($action == 'getUsers') {
        $res = $conn->query("SELECT username, fullname, department FROM users ORDER BY fullname ASC");
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]); exit;
    }

    if ($action == 'getPlafond') {
        $role = $input['role'];
        $username = $conn->real_escape_string($input['username']);
        $dept = $conn->real_escape_string($input['department'] ?? '');
        
        $globalRoles = ['Administrator', 'HRGA', 'PlantHead'];
        $isHrgaLeader = ($role === 'TeamLeader' && $dept === 'HRGA');

        if (in_array($role, $globalRoles) || $isHrgaLeader) {
            $sql = "SELECT u.username, u.fullname, u.department, 
                           IFNULL(p.initial_budget, 0) as init_jalan, IFNULL(p.current_budget, 0) as curr_jalan,
                           IFNULL(p.initial_kacamata, 0) as init_kacamata, IFNULL(p.current_kacamata, 0) as curr_kacamata,
                           IFNULL(p.initial_persalinan, 0) as init_persalinan, IFNULL(p.current_persalinan, 0) as curr_persalinan,
                           IFNULL(p.initial_inap, 0) as init_inap, IFNULL(p.current_inap, 0) as curr_inap,
                           IFNULL(p.harga_kamar, 0) as harga_kamar
                    FROM users u LEFT JOIN med_plafond p ON u.username = p.username 
                    ORDER BY u.department, u.fullname";
            $res = $conn->query($sql);
            echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        } else {
            $sql = "SELECT * FROM med_plafond WHERE username = '$username'";
            $res = $conn->query($sql);
            if ($row = $res->fetch_assoc()) echo json_encode(['success' => true, 'data' => $row]);
            else echo json_encode(['success' => true, 'data' => null]);
        }
        exit;
    }

    if ($action == 'setBudget') {
        if ($input['role'] !== 'Administrator') { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        $u = $conn->real_escape_string($input['target_username']);
        $ij = floatval($input['init_jalan']); $cj = floatval($input['curr_jalan']);
        $ik = floatval($input['init_kacamata']); $ck = floatval($input['curr_kacamata']);
        $ip = floatval($input['init_persalinan']); $cp = floatval($input['curr_persalinan']);
        $ii = floatval($input['init_inap']); $ci = floatval($input['curr_inap']);
        $hk = floatval($input['harga_kamar']);
        
        $check = $conn->query("SELECT id FROM med_plafond WHERE username = '$u'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE med_plafond SET initial_budget=$ij, current_budget=$cj, initial_kacamata=$ik, current_kacamata=$ck, initial_persalinan=$ip, current_persalinan=$cp, initial_inap=$ii, current_inap=$ci, harga_kamar=$hk, last_updated='$now' WHERE username='$u'");
        } else {
            $conn->query("INSERT INTO med_plafond (username, initial_budget, current_budget, initial_kacamata, current_kacamata, initial_persalinan, current_persalinan, initial_inap, current_inap, harga_kamar, last_updated) VALUES ('$u', $ij, $cj, $ik, $ck, $ip, $cp, $ii, $ci, $hk, '$now')");
        }
        echo json_encode(['success' => true]); exit;
    }

    if ($action == 'importBudgetBulk') {
        if ($input['role'] !== 'Administrator') { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
        $items = $input['data'];
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO med_plafond (username, initial_budget, current_budget, initial_kacamata, current_kacamata, initial_persalinan, current_persalinan, initial_inap, current_inap, harga_kamar, last_updated) VALUES (?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE initial_budget=VALUES(initial_budget), current_budget=VALUES(current_budget), initial_kacamata=VALUES(initial_kacamata), current_kacamata=VALUES(current_kacamata), initial_persalinan=VALUES(initial_persalinan), current_persalinan=VALUES(current_persalinan), initial_inap=VALUES(initial_inap), current_inap=VALUES(current_inap), harga_kamar=VALUES(harga_kamar), last_updated=VALUES(last_updated)");
            foreach ($items as $it) {
                $u = $conn->real_escape_string($it['username']);
                $ij=floatval($it['init_jalan']); $cj=floatval($it['curr_jalan']);
                $ik=floatval($it['init_kaca']); $ck=floatval($it['curr_kaca']);
                $ip=floatval($it['init_salin']); $cp=floatval($it['curr_salin']);
                $ii=floatval($it['init_inap']); $ci=floatval($it['curr_inap']);
                $hk=floatval($it['kamar']);
                if (!empty($u)) {
                    $stmt->bind_param("sddddddddds", $u, $ij, $cj, $ik, $ck, $ip, $cp, $ii, $ci, $hk, $now);
                    $stmt->execute();
                }
            }
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) { $conn->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    if ($action == 'getClaims' || $action == 'exportData') {
        $role = $input['role'];
        $username = $conn->real_escape_string($input['username']);
        $dept = $conn->real_escape_string($input['department'] ?? '');
        
        $sql = "SELECT c.*, 
                COALESCE(
                    NULLIF(c.remaining_balance, 0), 
                    CASE c.claim_type 
                        WHEN 'Kacamata' THEN p.current_kacamata
                        WHEN 'Persalinan' THEN p.current_persalinan
                        WHEN 'Rawat Inap' THEN p.current_inap
                        ELSE p.current_budget 
                    END, 
                    0
                ) as display_balance,
                CASE c.claim_type 
                    WHEN 'Kacamata' THEN p.initial_kacamata
                    WHEN 'Persalinan' THEN p.initial_persalinan
                    WHEN 'Rawat Inap' THEN p.initial_inap
                    ELSE p.initial_budget 
                END as user_initial_budget
                FROM med_claims c 
                LEFT JOIN med_plafond p ON c.username = p.username 
                WHERE 1=1 ";
        
        if ($action == 'exportData' && !empty($input['startDate']) && !empty($input['endDate'])) {
            $start = $conn->real_escape_string($input['startDate']) . " 00:00:00";
            $end = $conn->real_escape_string($input['endDate']) . " 23:59:59";
            $sql .= " AND c.created_at BETWEEN '$start' AND '$end' ";
        }

        $globalRoles = ['Administrator', 'HRGA', 'PlantHead'];
        $isHrgaLeader = ($role === 'TeamLeader' && $dept === 'HRGA');
        if (!in_array($role, $globalRoles) && !$isHrgaLeader) {
            $sql .= " AND c.username = '$username' ";
        }
        
        if ($action == 'getClaims') {
            $sql .= "ORDER BY CASE WHEN c.status = 'Pending HRGA' THEN 0 ELSE 1 END, c.created_at DESC LIMIT 100";
        } else {
            $sql .= " ORDER BY c.created_at ASC"; 
        }
        
        $res = $conn->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]); exit;
    }

    if ($action == 'submit') {
        $reqId = "MED-" . time();
        $submitterUser = $conn->real_escape_string($input['username']);
        $role = $input['role'];
        $invoiceNo = $conn->real_escape_string($input['invoiceNo']);
        $amount = floatval($input['amount']);
        $claimType = $conn->real_escape_string($input['claimType']);
        $photoBase64 = $input['photoBase64'] ?? '';
        
        $targetUser = $submitterUser;
        $fullname = $conn->real_escape_string($input['fullname']);
        $dept = $conn->real_escape_string($input['department']);
        
        if (in_array($role, ['HRGA', 'Administrator']) && !empty($input['targetUsername'])) {
            $targetUser = $conn->real_escape_string($input['targetUsername']);
            $uData = $conn->query("SELECT fullname, department FROM users WHERE username='$targetUser'")->fetch_assoc();
            if($uData) { $fullname = $uData['fullname']; $dept = $uData['department']; }
        }

        $currCol = getCol($claimType, false);
        $chk = $conn->query("SELECT $currCol FROM med_plafond WHERE username = '$targetUser'");
        $currBudget = $chk->num_rows > 0 ? floatval($chk->fetch_assoc()[$currCol]) : 0;
        
        if ($currBudget < $amount) { echo json_encode(['success' => false, 'message' => 'Plafond ('.$claimType.') tidak mencukupi!']); exit; }

        $photoUrl = uploadMedicalFile($photoBase64, "INV_$reqId");
        if (!$photoUrl) { echo json_encode(['success' => false, 'message' => 'Gagal upload file.']); exit; }

        $status = in_array($role, ['HRGA', 'Administrator']) ? 'Confirmed' : 'Pending HRGA';
        $hrgaByVal = ($status === 'Confirmed') ? "'" . $conn->real_escape_string($input['fullname']) . "'" : "NULL";
        $hrgaTime = ($status === 'Confirmed') ? "'$now'" : "NULL";
        $newBudget = $currBudget - $amount;

        $conn->begin_transaction();
        try {
            if(!$conn->query("UPDATE med_plafond SET $currCol = $newBudget, last_updated = '$now' WHERE username = '$targetUser'")) {
                throw new Exception("Gagal update plafond master.");
            }
            
            $sqlInsert = "INSERT INTO med_claims (req_id, username, fullname, department, claim_type, invoice_no, amount, photo_url, status, hrga_by, hrga_time, remaining_balance, created_at) 
                          VALUES ('$reqId', '$targetUser', '$fullname', '$dept', '$claimType', '$invoiceNo', $amount, '$photoUrl', '$status', $hrgaByVal, $hrgaTime, $newBudget, '$now')";
            
            if(!$conn->query($sqlInsert)) {
                throw new Exception("Insert Claim Gagal: " . $conn->error);
            }
            
            $conn->commit();

            // NOTIFIKASI WA SUBMIT
            $hrgaPhones = getPhones($conn, 'HRGA');
            $userPhone = getUserPhone($conn, $targetUser);
            $amtFormatted = number_format($amount, 0, ',', '.');
            $remFormatted = number_format($newBudget, 0, ',', '.');

            if ($status === 'Pending HRGA') {
                $msgHrga = "🩺 *MEDICAL CLAIM - NEW REQUEST*\n"
                         . "--------------------------------\n"
                         . "User: $fullname ($dept)\n"
                         . "Category: $claimType\n"
                         . "Invoice: $invoiceNo\n"
                         . "Amount: Rp $amtFormatted\n"
                         . "*Rem. Plafond: Rp $remFormatted*\n\n"
                         . "👉 _Plafond deducted temporarily. Please login to review._";
                foreach($hrgaPhones as $ph) sendWA($ph, $msgHrga);
                
                if($userPhone) {
                    $msgUser = "🩺 *MEDICAL CLAIM - SUBMITTED*\n"
                             . "--------------------------------\n"
                             . "Your claim has been submitted.\n\n"
                             . "Category: $claimType\n"
                             . "Invoice: $invoiceNo\n"
                             . "Amount: Rp $amtFormatted\n"
                             . "*Rem. Plafond: Rp $remFormatted*\n"
                             . "Status: Pending HRGA Review\n\n"
                             . "👉 _Your current plafond has been deducted temporarily._";
                    sendWA($userPhone, $msgUser);
                }
            } else {
                $msgHrga = "✅ *MEDICAL CLAIM - AUTO CONFIRMED*\n"
                         . "--------------------------------\n"
                         . "Inputted By: $submitterUser\n"
                         . "Target User: $fullname\n"
                         . "Category: $claimType\n"
                         . "Invoice: $invoiceNo\n"
                         . "Amount: Rp $amtFormatted\n"
                         . "*Rem. Plafond: Rp $remFormatted*";
                foreach($hrgaPhones as $ph) sendWA($ph, $msgHrga);
                
                if($userPhone && $submitterUser !== $targetUser) {
                    $msgUser = "✅ *MEDICAL CLAIM - APPROVED*\n"
                             . "--------------------------------\n"
                             . "HRGA has inputted and approved a medical claim for you.\n\n"
                             . "Category: $claimType\n"
                             . "Invoice: $invoiceNo\n"
                             . "Amount: Rp $amtFormatted\n"
                             . "*Rem. Plafond: Rp $remFormatted*\n\n"
                             . "👉 _Your plafond has been deducted._";
                    sendWA($userPhone, $msgUser);
                }
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) { $conn->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    if ($action == 'edit') {
        $reqId = $conn->real_escape_string($input['reqId']);
        $newAmount = floatval($input['amount']);
        $newInvoice = $conn->real_escape_string($input['invoiceNo']);
        $newType = $conn->real_escape_string($input['claimType']);
        $photoBase64 = $input['photoBase64'] ?? '';

        $claim = $conn->query("SELECT * FROM med_claims WHERE req_id = '$reqId'")->fetch_assoc();
        if($claim['status'] !== 'Pending HRGA') { echo json_encode(['success' => false, 'message' => 'Hanya status Pending yang bisa diedit.']); exit; }

        $oldAmount = floatval($claim['amount']);
        $oldType = $claim['claim_type'];
        $u = $claim['username'];

        $colOld = getCol($oldType, false);
        $colNew = getCol($newType, false);

        $conn->begin_transaction();
        try {
            if(!$conn->query("UPDATE med_plafond SET $colOld = $colOld + $oldAmount WHERE username='$u'")) throw new Exception("Refund old failed.");
            
            $chk = $conn->query("SELECT $colNew FROM med_plafond WHERE username='$u'")->fetch_assoc();
            if ($chk[$colNew] < $newAmount) {
                $conn->query("UPDATE med_plafond SET $colOld = $colOld - $oldAmount WHERE username='$u'"); 
                throw new Exception("Plafond (".$newType.") tidak mencukupi untuk penambahan ini.");
            }
            
            $newRem = floatval($chk[$colNew]) - $newAmount;
            if(!$conn->query("UPDATE med_plafond SET $colNew = $newRem WHERE username='$u'")) throw new Exception("Update new budget failed.");

            $photoQuery = "";
            if (!empty($photoBase64)) {
                $photoUrl = uploadMedicalFile($photoBase64, "INV_$reqId");
                $photoQuery = ", photo_url='$photoUrl'";
            }

            $updSql = "UPDATE med_claims SET claim_type='$newType', invoice_no='$newInvoice', amount=$newAmount, remaining_balance=$newRem $photoQuery WHERE req_id='$reqId'";
            if(!$conn->query($updSql)) throw new Exception("Update claim error: " . $conn->error);
            
            $conn->commit();

            // NOTIFIKASI WA EDIT
            $hrgaPhones = getPhones($conn, 'HRGA');
            $userPhone = getUserPhone($conn, $u);
            $amtFormatted = number_format($newAmount, 0, ',', '.');
            $remFormatted = number_format($newRem, 0, ',', '.');

            $msgHrga = "✏️ *MEDICAL CLAIM - UPDATED*\n"
                     . "--------------------------------\n"
                     . "User: {$claim['fullname']} ({$claim['department']})\n"
                     . "Request ID: $reqId\n"
                     . "New Category: $newType\n"
                     . "New Invoice: $newInvoice\n"
                     . "New Amount: Rp $amtFormatted\n"
                     . "*Rem. Plafond: Rp $remFormatted*\n\n"
                     . "👉 _Please login to review the updated data._";
            foreach($hrgaPhones as $ph) sendWA($ph, $msgHrga);

            if($userPhone) {
                $msgUser = "✏️ *MEDICAL CLAIM - UPDATED*\n"
                         . "--------------------------------\n"
                         . "You have updated your claim.\n\n"
                         . "Request ID: $reqId\n"
                         . "Category: $newType\n"
                         . "Amount: Rp $amtFormatted\n"
                         . "*Rem. Plafond: Rp $remFormatted*\n"
                         . "Status: Pending HRGA Review";
                sendWA($userPhone, $msgUser);
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) { $conn->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    if ($action == 'updateStatus') {
        $reqId = $conn->real_escape_string($input['id']);
        $act = $input['act'];
        $hrgaName = $conn->real_escape_string($input['approverName']);
        $reason = $conn->real_escape_string($input['reason'] ?? '');
        
        $claim = $conn->query("SELECT * FROM med_claims WHERE req_id = '$reqId'")->fetch_assoc();
        $targetUser = $claim['username'];
        $amount = floatval($claim['amount']);
        $col = getCol($claim['claim_type'], false);
        $amtFormatted = number_format($amount, 0, ',', '.');

        $hrgaPhones = getPhones($conn, 'HRGA');
        $userPhone = getUserPhone($conn, $targetUser);

        if ($act == 'confirm') {
            $conn->query("UPDATE med_claims SET status = 'Confirmed', hrga_by = '$hrgaName', hrga_time = '$now' WHERE req_id = '$reqId'");
            
            // Get Current Balance to show
            $plafond = $conn->query("SELECT $col FROM med_plafond WHERE username='$targetUser'")->fetch_assoc();
            $currBal = floatval($plafond[$col]);
            $remFormatted = number_format($currBal, 0, ',', '.');

            $msgHrga = "✅ *MEDICAL CLAIM - CONFIRMED*\n"
                     . "--------------------------------\n"
                     . "By: $hrgaName\n"
                     . "User: {$claim['fullname']}\n"
                     . "Req ID: $reqId\n"
                     . "Amount: Rp $amtFormatted\n"
                     . "*Rem. Plafond: Rp $remFormatted*";
            foreach($hrgaPhones as $ph) sendWA($ph, $msgHrga);
            
            if($userPhone) {
                $msgUser = "✅ *MEDICAL CLAIM - APPROVED*\n"
                         . "--------------------------------\n"
                         . "Your claim has been approved by HRGA ($hrgaName).\n\n"
                         . "Req ID: $reqId\n"
                         . "Category: {$claim['claim_type']}\n"
                         . "Invoice: {$claim['invoice_no']}\n"
                         . "Amount: Rp $amtFormatted\n"
                         . "*Rem. Plafond: Rp $remFormatted*";
                sendWA($userPhone, $msgUser);
            }

            echo json_encode(['success' => true]);
        } 
        elseif ($act == 'reject') {
            $conn->begin_transaction();
            try {
                $conn->query("UPDATE med_plafond SET $col = $col + $amount, last_updated = '$now' WHERE username = '$targetUser'");
                
                $plafond = $conn->query("SELECT $col FROM med_plafond WHERE username='$targetUser'")->fetch_assoc();
                $newBudget = floatval($plafond[$col]);

                $conn->query("UPDATE med_claims SET status = 'Rejected', hrga_by = '$hrgaName', hrga_time = '$now', reject_reason = '$reason', remaining_balance = $newBudget WHERE req_id = '$reqId'");
                $conn->commit();

                $remFormatted = number_format($newBudget, 0, ',', '.');

                $msgHrga = "❌ *MEDICAL CLAIM - REJECTED*\n"
                         . "--------------------------------\n"
                         . "By: $hrgaName\n"
                         . "User: {$claim['fullname']}\n"
                         . "Req ID: $reqId\n"
                         . "Reason: $reason\n"
                         . "*Rem. Plafond: Rp $remFormatted* (Refunded)";
                foreach($hrgaPhones as $ph) sendWA($ph, $msgHrga);
                
                if($userPhone) {
                    $msgUser = "❌ *MEDICAL CLAIM - REJECTED*\n"
                             . "--------------------------------\n"
                             . "Your claim was rejected by HRGA ($hrgaName).\n\n"
                             . "Req ID: $reqId\n"
                             . "Invoice: {$claim['invoice_no']}\n"
                             . "Reason: $reason\n\n"
                             . "👉 _Your plafond has been refunded._\n"
                             . "*Rem. Plafond: Rp $remFormatted*";
                    sendWA($userPhone, $msgUser);
                }

                echo json_encode(['success' => true]);
            } catch (Exception $e) { $conn->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        }
        exit;
    }

} catch (Exception $e) { echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]); }
?>