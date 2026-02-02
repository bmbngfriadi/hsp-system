<?php
require 'db.php';
require 'helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// --- 1. UPDATE PROFILE (User Sendiri) ---
if ($action == 'updateProfile') {
    $username = $input['username'];
    $phone = $conn->real_escape_string($input['phone']);
    $newPass = $input['newPass'] ?? '';

    $sql = "UPDATE users SET phone = '$phone'";
    
    // Hanya update password jika diisi
    if (!empty($newPass)) {
        $sql .= ", password = '$newPass'"; 
    }
    
    $sql .= " WHERE username = '$username'";

    if ($conn->query($sql)) {
        sendJson(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        sendJson(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
    }
}

// --- 2. GET ALL USERS (Admin Only) ---
if ($action == 'getAllUsers') {
    $res = $conn->query("SELECT * FROM users ORDER BY id ASC");
    $users = [];
    while ($row = $res->fetch_assoc()) {
        // Hilangkan password dari list agar aman
        $row['apps'] = $row['allowed_apps']; // Mapping agar sesuai frontend lama
        $users[] = $row;
    }
    sendJson($users);
}

// --- 3. GET DROPDOWN OPTIONS ---
if ($action == 'getOptions') {
    // Ambil unique department & role
    $deptRes = $conn->query("SELECT DISTINCT department FROM users WHERE department != '' ORDER BY department");
    $roleRes = $conn->query("SELECT DISTINCT role FROM users WHERE role != '' ORDER BY role");
    
    $depts = [];
    $roles = [];
    
    while($r = $deptRes->fetch_assoc()) $depts[] = $r['department'];
    while($r = $roleRes->fetch_assoc()) $roles[] = $r['role'];
    
    sendJson(['departments' => $depts, 'roles' => $roles]);
}

// --- 4. SAVE USER (Create / Update via Admin) ---
if ($action == 'saveUser') {
    $isEdit = $input['isEdit'];
    $data = $input['data'];
    
    $u = $conn->real_escape_string($data['username']);
    $p = $conn->real_escape_string($data['password']);
    $f = $conn->real_escape_string($data['fullname']);
    $n = $conn->real_escape_string($data['nik']);
    $d = $conn->real_escape_string($data['department']);
    $r = $conn->real_escape_string($data['role']);
    $a = $conn->real_escape_string($data['apps']);
    $ph = $conn->real_escape_string($data['phone']);

    if (!$isEdit) {
        // Mode: NEW USER
        // Cek duplicate
        $check = $conn->query("SELECT id FROM users WHERE username = '$u'");
        if ($check->num_rows > 0) {
            sendJson(['success' => false, 'message' => 'Username already exists!']);
        }

        $sql = "INSERT INTO users (username, password, fullname, nik, department, role, allowed_apps, phone) 
                VALUES ('$u', '$p', '$f', '$n', '$d', '$r', '$a', '$ph')";
        
        if ($conn->query($sql)) {
            sendJson(['success' => true, 'message' => 'User created successfully.']);
        } else {
            sendJson(['success' => false, 'message' => $conn->error]);
        }

    } else {
        // Mode: UPDATE USER
        $sql = "UPDATE users SET 
                password='$p', fullname='$f', nik='$n', department='$d', role='$r', allowed_apps='$a', phone='$ph' 
                WHERE username='$u'";
        
        if ($conn->query($sql)) {
            sendJson(['success' => true, 'message' => 'User updated successfully.']);
        } else {
            sendJson(['success' => false, 'message' => $conn->error]);
        }
    }
}

// --- 5. DELETE USER ---
if ($action == 'deleteUser') {
    $u = $conn->real_escape_string($input['username']);
    if (strtolower($u) == 'admin') {
        sendJson(['success' => false, 'message' => 'Cannot delete Super Admin.']);
    }
    
    if ($conn->query("DELETE FROM users WHERE username = '$u'")) {
        sendJson(['success' => true, 'message' => 'User deleted.']);
    } else {
        sendJson(['success' => false, 'message' => $conn->error]);
    }
}
?>