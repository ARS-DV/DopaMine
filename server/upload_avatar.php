<?php
// Endpoint separado para subida de imagen — no usa JSON sino multipart/form-data
include_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['user_id']) || !isset($_FILES['avatar'])) {
    echo json_encode(['status' => 'error', 'message' => 'user_id and avatar file are required']);
    exit;
}

$user_id = intval($_POST['user_id']);
$file    = $_FILES['avatar'];

// Validaciones del archivo
$allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$max_size      = 2 * 1024 * 1024; // 2MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload error']);
    exit;
}

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, WEBP and GIF images are allowed']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['status' => 'error', 'message' => 'Image must be smaller than 2MB']);
    exit;
}

// Crear carpeta de avatares si no existe
$upload_dir = __DIR__ . '/uploads/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Borrar avatar anterior si existía
$stmt_old = $conn->prepare("SELECT avatar FROM user WHERE id = ?");
$stmt_old->bind_param("i", $user_id);
$stmt_old->execute();
$old_user = $stmt_old->get_result()->fetch_assoc();
$stmt_old->close();

if ($old_user && $old_user['avatar']) {
    $old_file = $upload_dir . $old_user['avatar'];
    if (file_exists($old_file)) {
        unlink($old_file);
    }
}

// Nombre único para el archivo
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename  = 'avatar_' . $user_id . '_' . time() . '.' . strtolower($extension);
$dest      = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['status' => 'error', 'message' => 'Error saving image']);
    exit;
}

// Guardar nombre en BD
$stmt = $conn->prepare("UPDATE user SET avatar = ? WHERE id = ?");
$stmt->bind_param("si", $filename, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'status'   => 'success',
        'message'  => 'Avatar updated',
        'avatar'   => $filename,
        'url'      => 'http://localhost/DopaMine_Server/uploads/avatars/' . $filename
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating avatar in database']);
}
$stmt->close();
?>