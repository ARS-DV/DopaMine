<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

// GET para obtener tareas del usuario
if ($method === 'GET') {

    // detalle de una tarea 
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("SELECT * FROM task WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$task) {
            echo json_encode(['status' => 'error', 'message' => 'Task not found']);
            exit;
        }

        $stmt2 = $conn->prepare(
            "SELECT * FROM task_checklist WHERE task_id = ? ORDER BY sort_order ASC"
        );
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $result    = $stmt2->get_result();
        $checklist = [];
        while ($row = $result->fetch_assoc()) $checklist[] = $row;
        $stmt2->close();

        $task['checklist'] = $checklist;
        echo json_encode($task, JSON_UNESCAPED_UNICODE);

    // tasks de hoy
    } elseif (isset($_GET['user_id']) && isset($_GET['today'])) {
        $user_id = intval($_GET['user_id']);
        $today   = date('Y-m-d');

        $stmt = $conn->prepare(
            "SELECT * FROM task WHERE user_id = ? AND DATE(expDate) = ? ORDER BY difficulty ASC"
        );
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks  = [];
        while ($row = $result->fetch_assoc()) $tasks[] = $row;
        $stmt->close();

        echo json_encode($tasks, JSON_UNESCAPED_UNICODE);

    // tasks de esta semana
    } elseif (isset($_GET['user_id']) && isset($_GET['week'])) {
        $user_id  = intval($_GET['user_id']);
        $week_end = date('Y-m-d', strtotime('sunday this week'));
        $today    = date('Y-m-d');

        $stmt = $conn->prepare(
            "SELECT * FROM task
             WHERE user_id = ? AND done = 0
             AND DATE(expDate) BETWEEN ? AND ?
             ORDER BY expDate ASC"
        );
        $stmt->bind_param("iss", $user_id, $today, $week_end);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks  = [];
        while ($row = $result->fetch_assoc()) $tasks[] = $row;
        $stmt->close();

        echo json_encode($tasks, JSON_UNESCAPED_UNICODE);

    // todas las takss del usuario
    } elseif (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        $stmt = $conn->prepare(
            "SELECT * FROM task WHERE user_id = ? ORDER BY expDate ASC"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks  = [];
        while ($row = $result->fetch_assoc()) $tasks[] = $row;
        $stmt->close();

        echo json_encode($tasks, JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
    }
}


// POST para crear tarea
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $user_id    = intval($data['user_id']);
    $title      = $data['title'];
    $icon       = isset($data['icon'])       ? $data['icon']       : null;
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $startDate  = isset($data['startDate'])  ? $data['startDate']  : null;
    $difficulty = isset($data['difficulty']) ? $data['difficulty'] : 'medium';
    $expDate    = $data['expDate'];
    $url        = isset($data['url'])        ? $data['url']        : null;
    $url2       = isset($data['url2'])       ? $data['url2']       : null;
    $url3       = isset($data['url3'])       ? $data['url3']       : null;

    $stmt = $conn->prepare(
        "INSERT INTO task (user_id, title, icon, descrip, startDate, difficulty, expDate, url, url2, url3)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssssssss", $user_id, $title, $icon, $descrip, $startDate, $difficulty, $expDate, $url, $url2, $url3);

    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Task created', 'id' => $id]);
    } else {
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Error creating task']);
    }
}


// PUT para editar tarea
if ($method === 'PUT') {
    $id         = intval($_GET['id']);
    $data       = json_decode(file_get_contents('php://input'), true);
    $title      = $data['title'];
    $icon       = isset($data['icon'])       ? $data['icon']       : null;
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $startDate  = isset($data['startDate'])  ? $data['startDate']  : null;
    $difficulty = isset($data['difficulty']) ? $data['difficulty'] : 'medium';
    $expDate    = $data['expDate'];
    $url        = isset($data['url'])        ? $data['url']        : null;
    $url2       = isset($data['url2'])       ? $data['url2']       : null;
    $url3       = isset($data['url3'])       ? $data['url3']       : null;

    $stmt = $conn->prepare(
        "UPDATE task SET title=?, icon=?, descrip=?, startDate=?, difficulty=?, expDate=?, url=?, url2=?, url3=?
         WHERE id=?"
    );
    $stmt->bind_param("sssssssssi", $title, $icon, $descrip, $startDate, $difficulty, $expDate, $url, $url2, $url3, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Task updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating task']);
    }
    $stmt->close();
}


// PATCH para marcar tarea como completada
if ($method === 'PATCH') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);
    $done = intval($data['done']);

    $stmt = $conn->prepare("UPDATE task SET done = ? WHERE id = ?");
    $stmt->bind_param("ii", $done, $id);
    $stmt->execute();
    $stmt->close();

    if ($done === 1) {
        $stmt2 = $conn->prepare("SELECT expDate FROM task WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $task = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        $onTime = (new DateTime()) <= (new DateTime($task['expDate'])) ? 1 : 0;

        $stmt3 = $conn->prepare("SELECT id FROM task_record WHERE task_id = ?");
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $exists = $stmt3->get_result()->fetch_assoc();
        $stmt3->close();

        if (!$exists) {
            $stmt4 = $conn->prepare("INSERT INTO task_record (task_id, onTime) VALUES (?, ?)");
            $stmt4->bind_param("ii", $id, $onTime);
            $stmt4->execute();
            $stmt4->close();
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Task updated', 'done' => $done]);
}


// DELETE para eliminar tarea
if ($method === 'DELETE') {
    $id   = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM task WHERE id = ?");
    $stmt->bind_param("i", $id);

    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'Task deleted'])
        : json_encode(['status' => 'error', 'message' => 'Error deleting task']);

    $stmt->close();
}
?>