<?php
include 'db.php';

// GET para obtener user
if ($method === 'GET') {

    // detalle de las tareas checklist
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("SELECT * FROM task WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();

        if (!$task) {
            echo json_encode(['status' => 'error', 'message' => 'Task not found']);
            exit;
        }

        //checklist de esa tarea
        $stmt2 = $conn->prepare(
            "SELECT * FROM task_checklist WHERE task_id = ? ORDER BY sort_order ASC"
        );
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $items_raw = $stmt2->get_result();
        $checklist = [];
        while ($row = $items_raw->fetch_assoc()) $checklist[] = $row;

        $task['checklist'] = $checklist;
        echo json_encode($task, JSON_UNESCAPED_UNICODE);

    //tareas de hoy de un usuario
    } elseif (isset($_GET['user_id']) && isset($_GET['today'])) {
        $user_id = intval($_GET['user_id']);
        $today   = date('Y-m-d');

        $stmt = $conn->prepare(
            "SELECT * FROM task
             WHERE user_id = ?
             AND DATE(expDate) = ?
             ORDER BY difficulty ASC"
        );
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $tasks = [];
        while ($row = $stmt->get_result()->fetch_assoc()) $tasks[] = $row;
        echo json_encode($tasks, JSON_UNESCAPED_UNICODE);

    //todas las tareas de un usuario con filtro opcional de dificultad
    } elseif (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $query   = "SELECT * FROM task WHERE user_id = ?";
        $types   = "i";
        $params  = [$user_id];

        if (isset($_GET['difficulty'])) {
            $query   .= " AND difficulty = ?";
            $types   .= "s";
            $params[] = $_GET['difficulty'];
        }

        if (isset($_GET['done'])) {
            $query   .= " AND done = ?";
            $types   .= "i";
            $params[] = intval($_GET['done']);
        }

        $query .= " ORDER BY expDate ASC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $tasks = [];
        while ($row = $stmt->get_result()->fetch_assoc()) $tasks[] = $row;
        echo json_encode($tasks, JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
    }
}


// POST,crear tarea
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $user_id    = intval($data['user_id']);
    $title      = $data['title'];
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $startDate  = isset($data['startDate'])  ? $data['startDate']  : null;
    $difficulty = isset($data['difficulty']) ? $data['difficulty'] : 'medium';
    $expDate    = $data['expDate'];

    $stmt = $conn->prepare(
        "INSERT INTO task (user_id, title, descrip, startDate, difficulty, expDate)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssss", $user_id, $title, $descrip, $startDate, $difficulty, $expDate);

    if ($stmt->execute()) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Task created',
            'id'      => $conn->insert_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error creating task']);
    }
}


// PUT para editar tarea completa
if ($method === 'PUT') {
    $id         = intval($_GET['id']);
    $data       = json_decode(file_get_contents('php://input'), true);
    $title      = $data['title'];
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $startDate  = isset($data['startDate'])  ? $data['startDate']  : null;
    $difficulty = isset($data['difficulty']) ? $data['difficulty'] : 'medium';
    $expDate    = $data['expDate'];

    $stmt = $conn->prepare(
        "UPDATE task
         SET title = ?, descrip = ?, startDate = ?, difficulty = ?, expDate = ?
         WHERE id = ?"
    );
    $stmt->bind_param("sssssi", $title, $descrip, $startDate, $difficulty, $expDate, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Task updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating task']);
    }
}


// PATCH para marcar tarea como completada 
if ($method === 'PATCH') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);
    $done = intval($data['done']); // 1 o 0

    // actualizar el campo
    $stmt = $conn->prepare("UPDATE task SET done = ? WHERE id = ?");
    $stmt->bind_param("ii", $done, $id);
    $stmt->execute();

    //si se completo se marca como done
    if ($done === 1) {
        // comprobar fecha limite
        $stmt2 = $conn->prepare("SELECT expDate FROM task WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $task    = $stmt2->get_result()->fetch_assoc();
        $onTime  = (new DateTime()) <= (new DateTime($task['expDate'])) ? 1 : 0;

        //evitar duplicados
        $stmt3 = $conn->prepare("SELECT id FROM task_record WHERE task_id = ?");
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $exists = $stmt3->get_result()->fetch_assoc();

        if (!$exists) {
            $stmt4 = $conn->prepare(
                "INSERT INTO task_record (task_id, onTime) VALUES (?, ?)"
            );
            $stmt4->bind_param("ii", $id, $onTime);
            $stmt4->execute();
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
}
?>
