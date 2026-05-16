<?php
include 'db.php';

// GET — obtener checklist de una rutina
if ($method === 'GET') {
    if (!isset($_GET['routine_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'routine_id is required']);
        exit;
    }

    $routine_id = intval($_GET['routine_id']);
    $stmt       = $conn->prepare(
        "SELECT * FROM routine_checklist WHERE routine_id = ? ORDER BY sort_order ASC"
    );
    $stmt->bind_param("i", $routine_id);
    $stmt->execute();

    $items = [];
    while ($row = $stmt->get_result()->fetch_assoc()) $items[] = $row;
    echo json_encode($items, JSON_UNESCAPED_UNICODE);
}


// POST — añadir paso al checklist de una rutina
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $routine_id = intval($data['routine_id']);
    $title      = $data['title'];

    // Calcular el siguiente sort_order
    $stmt = $conn->prepare(
        "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order
         FROM routine_checklist WHERE routine_id = ?"
    );
    $stmt->bind_param("i", $routine_id);
    $stmt->execute();
    $next_order = intval($stmt->get_result()->fetch_assoc()['next_order']);

    $stmt2 = $conn->prepare(
        "INSERT INTO routine_checklist (routine_id, title, sort_order) VALUES (?, ?, ?)"
    );
    $stmt2->bind_param("isi", $routine_id, $title, $next_order);

    if ($stmt2->execute()) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Step added',
            'id'      => $conn->insert_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding step']);
    }
}


// PATCH — marcar paso como hecho o no hecho
if ($method === 'PATCH') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);
    $done = intval($data['done']);

    $stmt = $conn->prepare("UPDATE routine_checklist SET done = ? WHERE id = ?");
    $stmt->bind_param("ii", $done, $id);

    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'done' => (bool)$done])
        : json_encode(['status' => 'error', 'message' => 'Error updating step']);
}


// PUT — editar título de un paso o reordenar
if ($method === 'PUT') {
    $id   = intval($_GET['id']);
    $data = json_decode(file_get_contents('php://input'), true);

    // Reordenar (recibe array de ids en el nuevo orden)
    if (isset($data['order']) && is_array($data['order'])) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "UPDATE routine_checklist SET sort_order = ? WHERE id = ?"
            );
            foreach ($data['order'] as $position => $item_id) {
                $sort = $position + 1;
                $stmt->bind_param("ii", $sort, $item_id);
                $stmt->execute();
            }
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Order updated']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error updating order']);
        }

    // Editar título
    } elseif (isset($data['title'])) {
        $title = $data['title'];
        $stmt  = $conn->prepare("UPDATE routine_checklist SET title = ? WHERE id = ?");
        $stmt->bind_param("si", $title, $id);

        echo $stmt->execute()
            ? json_encode(['status' => 'success', 'message' => 'Step updated'])
            : json_encode(['status' => 'error', 'message' => 'Error updating step']);
    }
}


// DELETE — eliminar paso del checklist
if ($method === 'DELETE') {
    $id   = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM routine_checklist WHERE id = ?");
    $stmt->bind_param("i", $id);

    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'Step deleted'])
        : json_encode(['status' => 'error', 'message' => 'Error deleting step']);
}
?>
