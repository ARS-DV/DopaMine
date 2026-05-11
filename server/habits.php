<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

// GET para obtener habitos del usuario
if ($method === 'GET') {

    //detalles habito 
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("SELECT * FROM habit WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $habit = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$habit) {
            echo json_encode(['status' => 'error', 'message' => 'Habit not found']);
            exit;
        }

        $stmt2 = $conn->prepare("SELECT dayOfWeek FROM habit_day WHERE habit_id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $days_result = $stmt2->get_result();
        $days = [];
        while ($row = $days_result->fetch_assoc()) $days[] = $row['dayOfWeek'];
        $stmt2->close();

        $habit['days'] = $days;
        echo json_encode($habit, JSON_UNESCAPED_UNICODE);

    // habitos de hoy del usuario
    } elseif (isset($_GET['user_id']) && isset($_GET['today'])) {
        $user_id      = intval($_GET['user_id']);
        $today_name   = strtolower(date('l'));
        $day_of_month = intval(date('j'));

        $stmt = $conn->prepare("
            SELECT h.*,
                   (SELECT done FROM habit_record
                    WHERE habit_id = h.id AND dateOfHabit = CURDATE()) AS done_today
            FROM habit h
            WHERE h.user_id = ?
            AND (
                h.frecuency = 'daily'
                OR (h.frecuency = 'weekly' AND h.id IN (
                    SELECT habit_id FROM habit_day WHERE dayOfWeek = ?
                ))
                OR (h.frecuency = 'monthly' AND h.dayOfMonth = ?)
            )
        ");
        $stmt->bind_param("isi", $user_id, $today_name, $day_of_month);
        $stmt->execute();
        $result = $stmt->get_result();
        $habits = [];
        while ($row = $result->fetch_assoc()) $habits[] = $row;
        $stmt->close();

        echo json_encode($habits, JSON_UNESCAPED_UNICODE);

    // todos los habitos del usuario
    } elseif (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        $stmt = $conn->prepare("SELECT * FROM habit WHERE user_id = ? ORDER BY frecuency ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // se guarda todo en array y se cierra antes de hacer sub-queries
        $habits = [];
        while ($row = $result->fetch_assoc()) $habits[] = $row;
        $stmt->close();

        foreach ($habits as &$habit) {

            //dias especificos
            $stmt2 = $conn->prepare("SELECT dayOfWeek FROM habit_day WHERE habit_id = ?");
            $stmt2->bind_param("i", $habit['id']);
            $stmt2->execute();
            $days_result = $stmt2->get_result();
            $days = [];
            while ($d = $days_result->fetch_assoc()) $days[] = $d['dayOfWeek'];
            $stmt2->close();
            $habit['days'] = $days;

            //racha actual
            $stmt3 = $conn->prepare("
                SELECT COUNT(*) AS streak
                FROM (
                    SELECT dateOfHabit,
                           @row := @row + 1 AS rn,
                           DATEDIFF(CURDATE(), dateOfHabit) AS diff
                    FROM habit_record, (SELECT @row := 0) r
                    WHERE habit_id = ? AND done = 1
                    ORDER BY dateOfHabit DESC
                ) t
                WHERE diff = rn - 1
            ");
            $stmt3->bind_param("i", $habit['id']);
            $stmt3->execute();
            $streak = $stmt3->get_result()->fetch_assoc();
            $stmt3->close();
            $habit['streak'] = intval($streak['streak']);

            //total completado
            $stmt4 = $conn->prepare(
                "SELECT COUNT(*) AS total FROM habit_record WHERE habit_id = ? AND done = 1"
            );
            $stmt4->bind_param("i", $habit['id']);
            $stmt4->execute();
            $total = $stmt4->get_result()->fetch_assoc();
            $stmt4->close();
            $habit['total_done'] = intval($total['total']);

            //saber si se completo hoy
            $stmt5 = $conn->prepare(
                "SELECT done FROM habit_record WHERE habit_id = ? AND dateOfHabit = CURDATE()"
            );
            $stmt5->bind_param("i", $habit['id']);
            $stmt5->execute();
            $today_rec = $stmt5->get_result()->fetch_assoc();
            $stmt5->close();
            $habit['done_today'] = $today_rec ? (bool)$today_rec['done'] : false;
        }

        echo json_encode($habits, JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
    }
}


// POST para crear habito
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $user_id    = intval($data['user_id']);
    $title      = $data['title'];
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $icon       = isset($data['icon'])       ? $data['icon']       : null;
    $frecuency  = isset($data['frecuency'])  ? $data['frecuency']  : 'daily';
    $dayOfMonth = isset($data['dayOfMonth']) ? intval($data['dayOfMonth']) : null;
    $days       = isset($data['days'])       ? $data['days']       : [];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "INSERT INTO habit (user_id, title, descrip, icon, frecuency, dayOfMonth)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issssi", $user_id, $title, $descrip, $icon, $frecuency, $dayOfMonth);
        $stmt->execute();
        $habit_id = $conn->insert_id;
        $stmt->close();

        if ($frecuency === 'weekly' && !empty($days)) {
            $stmt2 = $conn->prepare("INSERT INTO habit_day (habit_id, dayOfWeek) VALUES (?, ?)");
            foreach ($days as $day) {
                $stmt2->bind_param("is", $habit_id, $day);
                $stmt2->execute();
            }
            $stmt2->close();
        }

        $conn->commit();
        echo json_encode([
            'status'  => 'success',
            'message' => 'Habit created',
            'id'      => $habit_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


// PUT para editar habito
if ($method === 'PUT') {
    $id         = intval($_GET['id']);
    $data       = json_decode(file_get_contents('php://input'), true);
    $title      = $data['title'];
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $icon       = isset($data['icon'])       ? $data['icon']       : null;
    $frecuency  = isset($data['frecuency'])  ? $data['frecuency']  : 'daily';
    $dayOfMonth = isset($data['dayOfMonth']) ? intval($data['dayOfMonth']) : null;
    $days       = isset($data['days'])       ? $data['days']       : [];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "UPDATE habit SET title = ?, descrip = ?, icon = ?, frecuency = ?, dayOfMonth = ?
             WHERE id = ?"
        );
        $stmt->bind_param("ssssii", $title, $descrip, $icon, $frecuency, $dayOfMonth, $id);
        $stmt->execute();
        $stmt->close();

        $stmt2 = $conn->prepare("DELETE FROM habit_day WHERE habit_id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

        if ($frecuency === 'weekly' && !empty($days)) {
            $stmt3 = $conn->prepare("INSERT INTO habit_day (habit_id, dayOfWeek) VALUES (?, ?)");
            foreach ($days as $day) {
                $stmt3->bind_param("is", $id, $day);
                $stmt3->execute();
            }
            $stmt3->close();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Habit updated']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error updating habit']);
    }
}


// PATCH para marcar hábito 
if ($method === 'PATCH') {
    $id    = intval($_GET['id']);
    $data  = json_decode(file_get_contents('php://input'), true);
    $done  = intval($data['done']); // 0, 1 o 2
    $today = date('Y-m-d');

    //insertar o actualizar habit_record de hoy
    $stmt = $conn->prepare(
        "SELECT id FROM habit_record WHERE habit_id = ? AND dateOfHabit = ?"
    );
    $stmt->bind_param("is", $id, $today);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $stmt2 = $conn->prepare(
            "UPDATE habit_record SET done = ? WHERE habit_id = ? AND dateOfHabit = ?"
        );
        $stmt2->bind_param("iis", $done, $id, $today);
        $stmt2->execute();
        $stmt2->close();
    } else {
        $stmt2 = $conn->prepare(
            "INSERT INTO habit_record (habit_id, dateOfHabit, done) VALUES (?, ?, ?)"
        );
        $stmt2->bind_param("isi", $id, $today, $done);
        $stmt2->execute();
        $stmt2->close();
    }

    //calcular racha actual, tried (1) no rompe la racha pero tampoco la aumenta
    $stmt3 = $conn->prepare("
        SELECT dateOfHabit, done FROM habit_record
        WHERE habit_id = ?
        ORDER BY dateOfHabit DESC
    ");
    $stmt3->bind_param("i", $id);
    $stmt3->execute();
    $result = $stmt3->get_result();
    $stmt3->close();

    $streak   = 0;
    $expected = new DateTime($today);

    while ($row = $result->fetch_assoc()) {
        $date      = new DateTime($row['dateOfHabit']);
        $row_done  = intval($row['done']);

        if ($date == $expected) {
            if ($row_done == 2) {
                // done, suma a la racha y avanza
                $streak++;
                $expected->modify('-1 day');
            } elseif ($row_done == 1) {
                // Intentado, ni suma ni rompe, avanza sin sumar
                $expected->modify('-1 day');
            } else {
                // no hecho, rompe la racha
                break;
            }
        } elseif ($date < $expected) {
            // si hay un hueco sin registro, rompe la racha
            break;
        }
    }

    //actualizar best_streak si la racha actual la supera
    if ($done == 2) {
        $stmt4 = $conn->prepare(
            "UPDATE habit SET best_streak = GREATEST(best_streak, ?) WHERE id = ?"
        );
        $stmt4->bind_param("ii", $streak, $id);
        $stmt4->execute();
        $stmt4->close();
    }

    echo json_encode([
        'status'         => 'success',
        'done'           => $done,
        'current_streak' => $streak
    ]);
}



// DELETE para eliminar hábito
if ($method === 'DELETE') {
    $id   = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM habit WHERE id = ?");
    $stmt->bind_param("i", $id);

    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'Habit deleted'])
        : json_encode(['status' => 'error', 'message' => 'Error deleting habit']);

    $stmt->close();
}
?>