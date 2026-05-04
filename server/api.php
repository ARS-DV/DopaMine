<?php
//para llamar al programa que controla la db
include 'db.php';
//CORS para controlar desde que dominio aceptar las peticiones
header('Access-Control-Allow-Origin: *');
//indicador del metodo HTTP que estan permitidos
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
//explicame estos dos headers tambien
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
//peticion prefligth para preguntar al sefcidor si acepta la peticion
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$request_uri = explode('/', trim($_SERVER['PATH_INFO'], '/')); //esto es el enlace de la ruta
$entity      = $request_uri[0]; //variable donde se declara para hacer el switch
$method      = $_SERVER['REQUEST_METHOD']; //guarda el metodo de peticion que llega al servidor
//switch para llamar a los programas segun el case que se le pida
switch ($entity) {
    case 'users':              include 'users.php';              break;
    case 'tasks':              include 'tasks.php';              break;
    case 'habits':             include 'habits.php';             break;
    case 'routines':           include 'routines.php';           break;
    case 'task_checklist':     include 'task_checklist.php';     break;
    case 'routine_checklist':  include 'routine_checklist.php';  break;
    case 'records':            include 'records.php';            break;
    case 'calendar':           include 'calendar.php';           break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Entity not found']);
        break;
}
?>