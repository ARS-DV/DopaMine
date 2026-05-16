<?php
// ── AÑADIR ESTE BLOQUE PUT A users.php ──────────────────────
// Colócalo después del bloque POST y antes del DELETE
// PUT — actualizar perfil del usuario (nickname, email, contraseña)

if ($method === 'PUT') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);

    $nickName = isset($data['nickName']) ? trim($data['nickName']) : null;
    $email    = isset($data['email'])    ? trim($data['email'])    : null;
    $pswd     = isset($data['pswd'])     ? $data['pswd']           : null;

    // Validaciones básicas
    if (!$nickName || !$email) {
        echo json_encode(['status' => 'error', 'message' => 'Nickname and email are required']);
        exit;
    }

    // Comprobar que el email no lo usa otro usuario
    $check = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if ($exists) {
        echo json_encode(['status' => 'error', 'message' => 'That email is already in use']);
        exit;
    }

    // Actualizar sin cambiar contraseña
    if (!$pswd) {
        $stmt = $conn->prepare("UPDATE user SET nickName = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nickName, $email, $id);

    // Actualizar con nueva contraseña
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
        // Devolver los datos actualizados para que Pinia los sincronice
        $stmt2 = $conn->prepare("SELECT id, nickName, email, role, avatar FROM user WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $updated = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        echo json_encode(['status' => 'success', 'message' => 'Profile updated', 'user' => $updated]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating profile']);
    }
    $stmt->close();
}
?>