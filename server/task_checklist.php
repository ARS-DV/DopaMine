<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

// GET — obtener checklist de una tarea
if ($method === 'GET') {
    if (!isset($_GET['task_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'task_id is required']);
        exit;
    }

    $task_id = intval($_GET['task_id']);
    $stmt    = $conn->prepare(
        "SELECT * FROM task_checklist WHERE task_id = ? ORDER BY sort_order ASC"
    );
    $stmt->bind_param("i", $task_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $items  = [];
    while ($row = $result->fetch_assoc()) $items[] = $row;
    $stmt->close();

    echo json_encode($items, JSON_UNESCAPED_UNICODE);
}


// POST — añadir item al checklist de una tarea
if ($method === 'POST') {
    $data    = json_decode(file_get_contents('php://input'), true);
    $task_id = intval($data['task_id']);
    $title   = $data['title'];

    // calcular el siguiente sort_order
    $stmt = $conn->prepare(
        "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM task_checklist WHERE task_id = ?"
    );
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $next_order = intval($stmt->get_result()->fetch_assoc()['next_order']);
    $stmt->close();

    $stmt2 = $conn->prepare(
        "INSERT INTO task_checklist (task_id, title, sort_order) VALUES (?, ?, ?)"
    );
    $stmt2->bind_param("isi", $task_id, $title, $next_order);

    if ($stmt2->execute()) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Item added',
            'id'      => $conn->insert_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding item']);
    }
    $stmt2->close();
}


// PATCH — marcar item como hecho o no hecho
if ($method === 'PATCH') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);
    $done = intval($data['done']);

    $stmt = $conn->prepare("UPDATE task_checklist SET done = ? WHERE id = ?");
    $stmt->bind_param("ii", $done, $id);

    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'done' => (bool)$done])
        : json_encode(['status' => 'error', 'message' => 'Error updating item']);

    $stmt->close();
}


// PUT — editar título de un item o reordenar
if ($method === 'PUT') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);

    // reordenar (recibe array de ids en el nuevo orden)
    if (isset($data['order']) && is_array($data['order'])) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE task_checklist SET sort_order = ? WHERE id = ?");
            foreach ($data['order'] as $position => $item_id) {
                $sort = $position + 1;
                $stmt->bind_param("ii", $sort, $item_id);
                $stmt->execute();
            }
            $stmt->close();
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Order updated']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error updating order']);
        }

    // editar título
    } elseif (isset($data['title'])) {
        $title = $data['title'];
        $stmt  = $conn->prepare("UPDATE task_checklist SET title = ? WHERE id = ?");
        $stmt->bind_param("si", $title, $id);

        echo $stmt->execute()
            ? json_encode(['status' => 'success', 'message' => 'Item updated'])
            : json_encode(['status' => 'error', 'message' => 'Error updating item']);

        $stmt->close();
    }
}


// DELETE — eliminar item del checklist
if ($method === 'DELETE') {
    $id   = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM task_checklist WHERE id = ?");
    $stmt->bind_param("i", $id);

    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'Item deleted'])
        : json_encode(['status' => 'error', 'message' => 'Error deleting item']);

    $stmt->close();
}
?>