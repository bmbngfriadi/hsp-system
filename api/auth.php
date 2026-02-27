<?php
require 'db.php';
require 'helper.php';

// Deteksi Domain Otomatis (untuk Link WA)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']); 
$path = str_replace('/api', '', $path); 
$baseUrl = "$protocol://$host$path";

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// --- 1. LOGIN (WITH SEAMLESS BCRYPT MIGRATION) ---
if ($action == 'login') {
    $u = $conn->real_escape_string($input['username']);
    $p = $input['password'];

    $sql = "SELECT * FROM users WHERE username = '$u'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $db_password = $user['password'];
        $is_valid = false;

        // Cek jika password sudah berupa HASH
        if (password_verify($p, $db_password)) {
            $is_valid = true;
        } 
        // Fallback: Jika password di DB masih Plain Text (Belum di Hash)
        elseif ($db_password === $p) {
            $is_valid = true;
            // Seamless Migration: Langsung ubah plain text jadi Hash di DB
            $newHash = password_hash($p, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$newHash' WHERE id = " . $user['id']);
        }

        if ($is_valid) {
            unset($user['password']); 
            unset($user['reset_token']); 
            $user['allowedApps'] = explode(',', $user['allowed_apps']); 
            sendJson(['success' => true, 'user' => $user]);
        } else {
            sendJson(['success' => false, 'message' => 'Username atau Password Salah']);
        }
    } else {
        sendJson(['success' => false, 'message' => 'Username atau Password Salah']);
    }
}

// --- 2. REQUEST RESET PASSWORD (Kirim Link WA) ---
if ($action == 'requestReset') {
    $u = $conn->real_escape_string($input['username']);
    
    $sql = "SELECT * FROM users WHERE username = '$u'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $phone = $user['phone'];

        if (empty($phone)) {
            sendJson(['success' => false, 'message' => 'User ini tidak memiliki nomor WA terdaftar. Hubungi Admin.']);
        }

        $token = bin2hex(random_bytes(16));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $conn->query("UPDATE users SET reset_token = '$token', reset_expiry = '$expiry' WHERE username = '$u'");
        $resetLink = "$baseUrl/reset.php?token=$token";

        $msg = "🔐 *RESET PASSWORD REQUEST*\n" .
               "--------------------------------\n" .
               "Halo {$user['fullname']},\n" .
               "Kami menerima permintaan reset password untuk akun Anda.\n\n" .
               "Klik link di bawah ini untuk membuat password baru:\n" .
               "$resetLink\n\n" .
               "⚠️ _Link ini berlaku selama 1 jam._\n" .
               "Abaikan jika Anda tidak memintanya.";
        
        sendWA($phone, $msg);
        sendJson(['success' => true, 'message' => 'Link reset password telah dikirim ke WhatsApp Anda.']);
    } else {
        sendJson(['success' => false, 'message' => 'Username tidak ditemukan.']);
    }
}

// --- 3. CONFIRM RESET PASSWORD (Simpan Password Hash Baru) ---
if ($action == 'confirmReset') {
    $token = $conn->real_escape_string($input['token']);
    $newPass = $input['newPassword']; // Ambil raw password
    
    // Hash password baru dengan Bcrypt
    $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);

    $now = date('Y-m-d H:i:s');
    $sql = "SELECT id FROM users WHERE reset_token = '$token' AND reset_expiry > '$now'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        // Simpan Hash Password
        $conn->query("UPDATE users SET password = '$hashedPass', reset_token = NULL, reset_expiry = NULL WHERE reset_token = '$token'");
        sendJson(['success' => true, 'message' => 'Password berhasil diubah. Silakan login.']);
    } else {
        sendJson(['success' => false, 'message' => 'Link tidak valid atau sudah kadaluarsa.']);
    }
}
?>