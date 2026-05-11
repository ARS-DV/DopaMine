<?php
include 'db.php';

// get para el admin o usuarios
if ($method == 'GET') {
    if (isset($_GET['admin'])) {
        // admin
        $result = $conn->query("SELECT id, nickName, email, energy FROM user");
        $users = [];
        while ($row = $result->fetch_assoc()) $users[] = $row;
        echo json_encode($users, JSON_UNESCAPED_UNICODE);

    } elseif (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT id, nickName, email, energy FROM user WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
    }
}

// post del login
if ($method == 'POST' && isset($_GET['login'])) {
    $data  = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email']);
    $pswd  = $data['pswd'];

    $stmt = $conn->prepare("SELECT id, nickName, email, pswd, energy, role FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    if (password_verify($pswd, $user['pswd'])) {
        unset($user['pswd']);
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Wrong password']);
    }
}

// post register
if ($method == 'POST' && !isset($_GET['login'])) {
    $data     = json_decode(file_get_contents('php://input'), true);
    $nickName = $data['nickName'];
    $email    = $data['email'];
    $pswd     = password_hash($data['pswd'], PASSWORD_BCRYPT); // hash seguro
    $role     = 'user'; // por defecto

    $stmt = $conn->prepare(
        "INSERT INTO user (nickName, email, pswd, role) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $nickName, $email, $pswd, $role);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User created']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    }
}

// put del user
if ($method == 'PUT') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);

    // cambiar contraseña
    if (isset($data['pswd'])) {
        $hashed = password_hash($data['pswd'], PASSWORD_BCRYPT);
        $stmt   = $conn->prepare("UPDATE user SET pswd = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $id);

    // cambiar nickname
    } elseif (isset($data['nickName'])) {
        $nick = $data['nickName'];
        $stmt = $conn->prepare("UPDATE user SET nickName = ? WHERE id = ?");
        $stmt->bind_param("si", $nick, $id);

    // cambiar energy
    } elseif (isset($data['energy'])) {
        $energy = $data['energy'];
        $stmt   = $conn->prepare("UPDATE user SET energy = ? WHERE id = ?");
        $stmt->bind_param("si", $energy, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating user']);
    }
}

// delete
if ($method == 'DELETE') {
    $id   = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'User deleted'])
        : json_encode(['status' => 'error', 'message' => 'Error deleting user']);
}
?>