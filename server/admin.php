<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

// GET — obtener todos los usuarios con sus estadísticas
if ($method === 'GET') {

    // Solo permite acceso si se confirma que el solicitante es admin
    if (!isset($_GET['requester_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'requester_id is required']);
        exit;
    }

    // Verificar que el solicitante es admin
    $requester_id = intval($_GET['requester_id']);
    $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check->bind_param("i", $requester_id);
    $check->execute();
    $requester = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$requester || $requester['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    // Obtener todos los usuarios
    $stmt = $conn->prepare("SELECT id, nickName, email, role, createdDate FROM users ORDER BY createdDate DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $users  = [];
    while ($row = $result->fetch_assoc()) $users[] = $row;
    $stmt->close();

    // Añadir estadísticas a cada usuario
    foreach ($users as &$user) {
        // Número de hábitos
        $s1 = $conn->prepare("SELECT COUNT(*) AS total FROM habit WHERE user_id = ?");
        $s1->bind_param("i", $user['id']);
        $s1->execute();
        $user['habits_count'] = intval($s1->get_result()->fetch_assoc()['total']);
        $s1->close();

        // Número de tareas
        $s2 = $conn->prepare("SELECT COUNT(*) AS total FROM task WHERE user_id = ?");
        $s2->bind_param("i", $user['id']);
        $s2->execute();
        $user['tasks_count'] = intval($s2->get_result()->fetch_assoc()['total']);
        $s2->close();

        // Número de rutinas
        $s3 = $conn->prepare("SELECT COUNT(*) AS total FROM routine WHERE user_id = ?");
        $s3->bind_param("i", $user['id']);
        $s3->execute();
        $user['routines_count'] = intval($s3->get_result()->fetch_assoc()['total']);
        $s3->close();

        // Ocultar datos sensibles
        unset($user['email']);
    }

    echo json_encode($users, JSON_UNESCAPED_UNICODE);
}


// PATCH — cambiar el rol de un usuario
if ($method === 'PATCH') {

    $data         = json_decode(file_get_contents('php://input'), true);
    $target_id    = intval($_GET['id']);
    $requester_id = intval($data['requester_id']);
    $new_role     = $data['role'];

    // Validar que el rol sea válido
    if ($new_role !== 'user' && $new_role !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
        exit;
    }

    // Verificar que el solicitante es admin
    $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check->bind_param("i", $requester_id);
    $check->execute();
    $requester = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$requester || $requester['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    // No permitir que el admin se quite el rol a sí mismo
    if ($target_id === $requester_id) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot change your own role']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $target_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Role updated', 'role' => $new_role]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating role']);
    }
    $stmt->close();
}


// DELETE — eliminar un usuario y todos sus datos
if ($method === 'DELETE') {

    $target_id    = intval($_GET['id']);
    $data         = json_decode(file_get_contents('php://input'), true);
    $requester_id = intval($data['requester_id']);

    // Verificar que el solicitante es admin
    $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check->bind_param("i", $requester_id);
    $check->execute();
    $requester = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$requester || $requester['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    // No permitir que el admin se elimine a sí mismo
    if ($target_id === $requester_id) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account']);
        exit;
    }

    // El CASCADE de las FK se encarga de borrar hábitos, tareas, rutinas, etc.
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $target_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting user']);
    }
    $stmt->close();
}
?>