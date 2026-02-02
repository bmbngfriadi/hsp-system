<?php
// FILE: api/helper.php

// Footer Standar agar terlihat profesional
define('WA_FOOTER', "\n\n--------------------------------\n🤖 _System Notification. Do Not Reply._");

// Fungsi Kirim Response JSON
function sendJson($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Fungsi Log (Cek file debug_wa.txt jika notif gagal)
function writeLog($msg) {
    file_put_contents('debug_wa.txt', "[" . date('Y-m-d H:i:s') . "] " . $msg . PHP_EOL, FILE_APPEND);
}

// Fungsi Kirim WA (Fonnte API)
function sendWA($target, $message) {
    if (empty($target)) {
        writeLog("Failed: Target empty.");
        return false;
    }

    $apiKey = "jkPPFevPkw4DBtPQMsDn"; // Pastikan API Key Anda Benar

    // Sanitasi Nomor HP (+62)
    $target = preg_replace('/[^0-9]/', '', $target);
    if (substr($target, 0, 1) == '0') {
        $target = '62' . substr($target, 1);
    } elseif (substr($target, 0, 1) == '8') {
        $target = '62' . $target;
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.fonnte.com/send',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array(
        'target' => $target,
        'message' => $message . WA_FOOTER,
        'countryCode' => '62'
      ),
      CURLOPT_HTTPHEADER => array("Authorization: $apiKey"),
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        writeLog("Curl Error to $target: $err");
    } else {
        writeLog("Sent to $target: $response");
    }
    return $response;
}

// Helper: Format List Item untuk WhatsApp
function formatItemList($itemsJson, $style = 'normal') {
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) return "-";
    
    $txt = "";
    foreach($items as $it) {
        if ($style == 'strikethrough') {
            // Format coret untuk item lama (WA support ~text~)
            $txt .= "~• {$it['name']} ({$it['qty']} {$it['unit']})~\n"; 
        } else {
            $txt .= "• {$it['name']} ({$it['qty']} {$it['unit']})\n";
        }
    }
    return $txt;
}

// Helper: Ambil Nomor HP Berdasarkan Prioritas Role
// $roles bisa array ['SectionHead', 'TeamLeader']
function getPhones($conn, $roles, $dept = null) {
    $phones = [];
    if (!is_array($roles)) $roles = [$roles];

    foreach ($roles as $role) {
        $sql = "SELECT phone FROM users WHERE role = '$role'";
        
        // Filter Dept (Kecuali Admin/PlantHead yang mungkin global)
        if ($dept && !in_array($role, ['PlantHead', 'Administrator'])) {
            $dept = $conn->real_escape_string($dept);
            $sql .= " AND LOWER(department) = LOWER('$dept')";
        }

        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while($row = $res->fetch_assoc()) { 
                if(!empty($row['phone'])) $phones[] = $row['phone']; 
            }
        }
        
        // Jika sudah dapat nomor di role prioritas utama, stop (agar tidak double notif ke level bawah)
        if (!empty($phones)) break; 
    }
    return $phones;
}

// Helper: Ambil Nomor HP Requester
function getUserPhone($conn, $username) {
    $u = $conn->real_escape_string($username);
    $sql = "SELECT phone FROM users WHERE LOWER(username) = LOWER('$u') LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) return $row['phone'];
    return null;
}
?>