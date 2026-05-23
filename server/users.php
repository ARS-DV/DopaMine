<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

// GET para perfil propio por id
if ($method == 'GET') {
    if (isset($_GET['id'])) {
        $id   = intval($_GET['id']);
        $stmt = $conn->prepare(
            "SELECT id, nickName, email, role, avatar FROM user WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc(), JSON_UNESCAPED_UNICODE);
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'id is required']);
    }
}


// POST login
if ($method == 'POST' && isset($_GET['login'])) {
    $data  = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email']);
    $pswd  = $data['pswd'];

    $stmt = $conn->prepare(
        "SELECT id, nickName, email, role, avatar, pswd FROM user WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    if (password_verify($pswd, $user['pswd'])) {
        // nunca se devuelve la contraseña por front
        unset($user['pswd']);
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Wrong password']);
    }
}


// POST register
if ($method == 'POST' && !isset($_GET['login'])) {
    $data     = json_decode(file_get_contents('php://input'), true);
    $nickName = $data['nickName'];
    $email    = $data['email'];
    $pswd     = password_hash($data['pswd'], PASSWORD_BCRYPT);
    $role     = 'user';

    $stmt = $conn->prepare(
        "INSERT INTO user (nickName, email, pswd, role) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $nickName, $email, $pswd, $role);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User created']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    }
    $stmt->close();
}


// PUT para actualizar perfil 
if ($method == 'PUT') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);

    $nickName = isset($data['nickName']) ? trim($data['nickName']) : null;
    $email    = isset($data['email'])    ? trim($data['email'])    : null;
    $pswd     = isset($data['pswd'])     ? $data['pswd']           : null;

    // validaciones básicas
    if (!$nickName || !$email) {
        echo json_encode(['status' => 'error', 'message' => 'Nickname and email are required']);
        exit;
    }

    // comprobar que el email no lo tenga otro usuario
    $check = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if ($exists) {
        echo json_encode(['status' => 'error', 'message' => 'That email is already in use']);
        exit;
    }

    // actualizar sin cambiar contraseña
    if (!$pswd) {
        $stmt = $conn->prepare("UPDATE user SET nickName = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nickName, $email, $id);

    // actualizar con nueva contraseña
    } else {
        if (strlen($pswd) < 7) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 7 characters']);
            exit;
        }
        $hashed = password_hash($pswd, PASSWORD_BCRYPT);
        $stmt   = $conn->prepare("UPDATE user SET nickName = ?, email = ?, pswd = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nickName, $email, $hashed, $id);
    }

    if ($stmt->execute()) {
        $stmt->close();

        $stmt2 = $conn->prepare(
            "SELECT id, nickName, email, role, avatar FROM user WHERE id = ?"
        );
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $updated = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        echo json_encode([
            'status'  => 'success',
            'message' => 'Profile updated',
            'user'    => $updated
        ]);
    } else {
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Error updating profile']);
    }
}


// DELETE para eliminar usuario
if ($method == 'DELETE') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);
    $requester_id = intval($data['requester_id']);

    // solo puede borrar si propio usuario o un admin
    $check = $conn->prepare("SELECT role FROM user WHERE id = ?");
    $check->bind_param("i", $requester_id);
    $check->execute();
    $requester = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$requester) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $is_own_account = ($requester_id === $id);
    $is_admin       = ($requester['role'] === 'admin');

    if (!$is_own_account && !$is_admin) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $id);

    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'Account deleted'])
        : json_encode(['status' => 'error', 'message' => 'Error deleting account']);

    $stmt->close();
}
?>