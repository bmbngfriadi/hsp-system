<?php
require 'db.php';
require 'helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action == 'login') {
    $u = $conn->real_escape_string($input['username']);
    $p = $input['password'];

    // Query simple (untuk production gunakan password_hash)
    $sql = "SELECT * FROM users WHERE username = '$u' AND password = '$p'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        unset($user['password']); 
        $user['allowedApps'] = explode(',', $user['allowed_apps']); 
        sendJson(['success' => true, 'user' => $user]);
    } else {
        sendJson(['success' => false, 'message' => 'Username atau Password Salah']);
    }
}
?>