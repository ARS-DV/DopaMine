<?php
include_once 'db.php';
//header para el env de infinityfree
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_GET['entity'])) {
    echo json_encode(['status' => 'error', 'message' => 'No entity specified']);
    exit;
}

$entity = $_GET['entity'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($entity) {
    case 'users':
        include 'users.php';
        break;
    case 'tasks':
        include 'tasks.php';
        break;
    case 'habits':
        include 'habits.php';
        break;
    case 'routines':
        include 'routines.php';
        break;
    case 'task_checklist':
        include 'task_checklist.php';
        break;
    case 'routine_checklist':
        include 'routine_checklist.php';
        break;
    case 'records':
        include 'records.php';
        break;
    case 'calendar':
        include 'calendar.php';
        break;
    case 'admin':
        include 'admin.php';
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Entity not found']);
        break;
}
?>