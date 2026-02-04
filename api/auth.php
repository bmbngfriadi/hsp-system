<?php
require 'db.php';
require 'helper.php';

// Deteksi Domain Otomatis (untuk Link WA)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Sesuaikan path jika folder project ada di subfolder (misal: /portal)
// Jika di root (public_html), biarkan kosong.
$path = dirname($_SERVER['PHP_SELF']); 
$path = str_replace('/api', '', $path); // Hapus '/api' agar link mengarah ke root
$baseUrl = "$protocol://$host$path";

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// --- 1. LOGIN (EXISTING) ---
if ($action == 'login') {
    $u = $conn->real_escape_string($input['username']);
    $p = $input['password'];

    $sql = "SELECT * FROM users WHERE username = '$u' AND password = '$p'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        unset($user['password']); 
        unset($user['reset_token']); // Jangan kirim token ke frontend
        $user['allowedApps'] = explode(',', $user['allowed_apps']); 
        sendJson(['success' => true, 'user' => $user]);
    } else {
        sendJson(['success' => false, 'message' => 'Username atau Password Salah']);
    }
}

// --- 2. REQUEST RESET PASSWORD (Kirim Link WA) ---
if ($action == 'requestReset') {
    $u = $conn->real_escape_string($input['username']);
    
    // Cek apakah user ada
    $sql = "SELECT * FROM users WHERE username = '$u'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $phone = $user['phone'];

        if (empty($phone)) {
            sendJson(['success' => false, 'message' => 'User ini tidak memiliki nomor WA terdaftar. Hubungi Admin.']);
        }

        // Generate Token Unik & Expiry (1 Jam)
        $token = bin2hex(random_bytes(16));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Simpan Token ke DB
        $conn->query("UPDATE users SET reset_token = '$token', reset_expiry = '$expiry' WHERE username = '$u'");

        // Buat Link Reset
        // Link akan mengarah ke file baru bernama 'reset.php'
        $resetLink = "$baseUrl/reset.php?token=$token";

        // Kirim WA
        $msg = "🔐 *RESET PASSWORD REQUEST*\n" .
               "--------------------------------\n" .
               "Halo {$user['fullname']},\n" .
               "Kami menerima permintaan reset password untuk akun Anda.\n\n" .
               "Klik link di bawah ini untuk membuat password baru:\n" .
               "$resetLink\n\n" .
               "⚠️ _Link ini berlaku selama 1 jam._\n" .
               "abaikan jika Anda tidak memintanya.";
        
        sendWA($phone, $msg);

        sendJson(['success' => true, 'message' => 'Link reset password telah dikirim ke WhatsApp Anda.']);
    } else {
        // Demi keamanan, tetap berikan pesan umum atau spesifik jika username tidak ditemukan
        sendJson(['success' => false, 'message' => 'Username tidak ditemukan.']);
    }
}

// --- 3. CONFIRM RESET PASSWORD (Simpan Password Baru) ---
if ($action == 'confirmReset') {
    $token = $conn->real_escape_string($input['token']);
    $newPass = $conn->real_escape_string($input['newPassword']);

    // Cek Token Valid & Belum Expired
    $now = date('Y-m-d H:i:s');
    $sql = "SELECT id FROM users WHERE reset_token = '$token' AND reset_expiry > '$now'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        // Update Password & Hapus Token
        $conn->query("UPDATE users SET password = '$newPass', reset_token = NULL, reset_expiry = NULL WHERE reset_token = '$token'");
        sendJson(['success' => true, 'message' => 'Password berhasil diubah. Silakan login.']);
    } else {
        sendJson(['success' => false, 'message' => 'Link tidak valid atau sudah kadaluarsa.']);
    }
}
?>