<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

// GET para obtener todos los usuarios con sus estadisticas
if ($method === 'GET') {

    //acceso unico al admin
    if (!isset($_GET['requester_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'requester_id is required']);
        exit;
    }

    //verificacion del admin
    $requester_id = intval($_GET['requester_id']);
    $check = $conn->prepare("SELECT role FROM user WHERE id = ?");
    $check->bind_param("i", $requester_id);
    $check->execute();
    $requester = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$requester || $requester['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    //obtener todos los usuarios
    $stmt = $conn->prepare("SELECT id, nickName, email, role, createdDate FROM user ORDER BY createdDate DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $users  = [];
    while ($row = $result->fetch_assoc()) $users[] = $row;
    $stmt->close();

    //añadir estadisticas de usuarios
    foreach ($users as &$user) {
        //num habits
        $s1 = $conn->prepare("SELECT COUNT(*) AS total FROM habit WHERE user_id = ?");
        $s1->bind_param("i", $user['id']);
        $s1->execute();
        $user['habits_count'] = intval($s1->get_result()->fetch_assoc()['total']);
        $s1->close();

        //num tasks
        $s2 = $conn->prepare("SELECT COUNT(*) AS total FROM task WHERE user_id = ?");
        $s2->bind_param("i", $user['id']);
        $s2->execute();
        $user['tasks_count'] = intval($s2->get_result()->fetch_assoc()['total']);
        $s2->close();

        //num routines
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


// PATCH para cambiar rol usuario
if ($method === 'PATCH') {

    $data         = json_decode(file_get_contents('php://input'), true);
    $target_id    = intval($_GET['id']);
    $requester_id = intval($data['requester_id']);
    $new_role     = $data['role'];

    //validacion rol valido
    if ($new_role !== 'user' && $new_role !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
        exit;
    }

    //verificar que solicitante sea admin
    $check = $conn->prepare("SELECT role FROM user WHERE id = ?");
    $check->bind_param("i", $requester_id);
    $check->execute();
    $requester = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$requester || $requester['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    //prohiir que el admin se quite el rol a si mismo
    if ($target_id === $requester_id) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot change your own role']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE user SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $target_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Role updated', 'role' => $new_role]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating role']);
    }
    $stmt->close();
}


// DELETE para eliminar usuario y todos sus datos
if ($method === 'DELETE') {

    $target_id    = intval($_GET['id']);
    $data         = json_decode(file_get_contents('php://input'), true);
    $requester_id = intval($data['requester_id']);

    //verificar que sea admin quien lo elimine
    $check = $conn->prepare("SELECT role FROM user WHERE id = ?");
    $check->bind_param("i", $requester_id);
    $check->execute();
    $requester = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$requester || $requester['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    //prohibir al admin eliminarse a si mismo
    if ($target_id === $requester_id) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account']);
        exit;
    }

    //se borran los datos por el CASCADE
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $target_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting user']);
    }
    $stmt->close();
}
?>