<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

// GET para obtener rutinas del usuario
if ($method === 'GET') {

    // detalles rutina
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("SELECT * FROM routine WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $routine = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$routine) {
            echo json_encode(['status' => 'error', 'message' => 'Routine not found']);
            exit;
        }

        $stmt2 = $conn->prepare("
            SELECT h.id, h.title, h.icon, h.frecuency, rh.sort_order,
                   COALESCE((SELECT done FROM habit_record
                    WHERE habit_id = h.id AND dateOfHabit = CURDATE()), 0) AS done_today
            FROM habit h
            JOIN routine_habit rh ON h.id = rh.habit_id
            WHERE rh.routine_id = ?
            ORDER BY rh.sort_order ASC
        ");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $r2     = $stmt2->get_result();
        $habits = [];
        while ($row = $r2->fetch_assoc()) $habits[] = $row;
        $stmt2->close();

        $stmt3 = $conn->prepare(
            "SELECT * FROM routine_checklist WHERE routine_id = ? ORDER BY sort_order ASC"
        );
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $r3        = $stmt3->get_result();
        $checklist = [];
        while ($row = $r3->fetch_assoc()) $checklist[] = $row;
        $stmt3->close();

        $stmt4 = $conn->prepare("SELECT dayOfWeek FROM routine_day WHERE routine_id = ?");
        $stmt4->bind_param("i", $id);
        $stmt4->execute();
        $r4   = $stmt4->get_result();
        $days = [];
        while ($d = $r4->fetch_assoc()) $days[] = $d['dayOfWeek'];
        $stmt4->close();

        $routine['habits']    = $habits;
        $routine['checklist'] = $checklist;
        $routine['days']      = $days;

        echo json_encode($routine, JSON_UNESCAPED_UNICODE);

    // rutinas de hoy
    } elseif (isset($_GET['user_id']) && isset($_GET['today'])) {
        $user_id      = intval($_GET['user_id']);
        $today_name   = strtolower(date('l'));
        $day_of_month = intval(date('j'));

        $stmt = $conn->prepare("
            SELECT r.*,
                   COALESCE((SELECT done FROM routine_record
                    WHERE routine_id = r.id AND dateOfRoutine = CURDATE()), 0) AS done_today
            FROM routine r
            WHERE r.user_id = ?
            AND (
                r.frecuency = 'daily'
                OR (r.frecuency = 'weekly' AND r.id IN (
                    SELECT routine_id FROM routine_day WHERE dayOfWeek = ?
                ))
                OR (r.frecuency = 'monthly' AND r.dayOfMonth = ?)
            )
            ORDER BY r.hour ASC
        ");
        $stmt->bind_param("isi", $user_id, $today_name, $day_of_month);
        $stmt->execute();
        $result   = $stmt->get_result();
        $routines = [];
        while ($row = $result->fetch_assoc()) $routines[] = $row;
        $stmt->close();

        echo json_encode($routines, JSON_UNESCAPED_UNICODE);

    // todas las rutinas del usuario
    } elseif (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        $stmt = $conn->prepare("SELECT * FROM routine WHERE user_id = ? ORDER BY hour ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // guardar todo en un array y cerrar antes de sub-queries
        $routines = [];
        while ($row = $result->fetch_assoc()) $routines[] = $row;
        $stmt->close();

        foreach ($routines as &$routine) {

            // dias
            $s1 = $conn->prepare("SELECT dayOfWeek FROM routine_day WHERE routine_id = ?");
            $s1->bind_param("i", $routine['id']);
            $s1->execute();
            $r1   = $s1->get_result();
            $days = [];
            while ($d = $r1->fetch_assoc()) $days[] = $d['dayOfWeek'];
            $s1->close();
            $routine['days'] = $days;

            // habitos con el estado de hoy
            $s2 = $conn->prepare("
                SELECT h.id, h.title, h.icon, rh.sort_order,
                       COALESCE((SELECT done FROM habit_record
                        WHERE habit_id = h.id AND dateOfHabit = CURDATE()), 0) AS done_today
                FROM habit h
                JOIN routine_habit rh ON h.id = rh.habit_id
                WHERE rh.routine_id = ?
                ORDER BY rh.sort_order ASC
            ");
            $s2->bind_param("i", $routine['id']);
            $s2->execute();
            $r2     = $s2->get_result();
            $habits = [];
            while ($h = $r2->fetch_assoc()) $habits[] = $h;
            $s2->close();
            $routine['habits'] = $habits;

            // checklist
            $s3 = $conn->prepare(
                "SELECT * FROM routine_checklist WHERE routine_id = ? ORDER BY sort_order ASC"
            );
            $s3->bind_param("i", $routine['id']);
            $s3->execute();
            $r3        = $s3->get_result();
            $checklist = [];
            while ($c = $r3->fetch_assoc()) $checklist[] = $c;
            $s3->close();
            $routine['checklist'] = $checklist;

            // pasos totales
            $routine['total_steps'] = count($habits) + count($checklist);

            // pasos hecho hoy
            $done_h = count(array_filter($habits,    fn($h) => intval($h['done_today']) === 2));
            $done_c = count(array_filter($checklist, fn($c) => $c['done']));
            $routine['done_steps'] = $done_h + $done_c;

            // estado hoy
            $s4 = $conn->prepare(
                "SELECT done FROM routine_record WHERE routine_id = ? AND dateOfRoutine = CURDATE()"
            );
            $s4->bind_param("i", $routine['id']);
            $s4->execute();
            $today_rec = $s4->get_result()->fetch_assoc();
            $s4->close();
            $routine['done_today'] = $today_rec ? intval($today_rec['done']) : 0;

            // racha actual
            $s5 = $conn->prepare("
                SELECT dateOfRoutine, done FROM routine_record
                WHERE routine_id = ?
                ORDER BY dateOfRoutine DESC
            ");
            $s5->bind_param("i", $routine['id']);
            $s5->execute();
            $r5       = $s5->get_result();
            $s5->close();

            $streak   = 0;
            $expected = new DateTime(date('Y-m-d'));
            while ($rec = $r5->fetch_assoc()) {
                $date     = new DateTime($rec['dateOfRoutine']);
                $rec_done = intval($rec['done']);
                if ($date == $expected) {
                    if ($rec_done === 2)     { $streak++; $expected->modify('-1 day'); }
                    elseif ($rec_done === 1) { $expected->modify('-1 day'); }
                    else break;
                } elseif ($date < $expected) break;
            }
            $routine['streak'] = $streak;
        }

        echo json_encode($routines, JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
    }
}


// POST para crear rutina
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $user_id    = intval($data['user_id']);
    $title      = $data['title'];
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $hour       = isset($data['hour'])       ? $data['hour']       : null;
    $color      = isset($data['color'])      ? $data['color']      : '#6B8FA3';
    $frecuency  = isset($data['frecuency'])  ? $data['frecuency']  : 'daily';
    $dayOfMonth = isset($data['dayOfMonth']) ? intval($data['dayOfMonth']) : null;
    $days       = isset($data['days'])       ? $data['days']       : [];
    $habit_ids  = isset($data['habit_ids'])  ? $data['habit_ids']  : [];
    $steps      = isset($data['steps'])      ? $data['steps']      : [];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "INSERT INTO routine (user_id, title, descrip, hour, color, frecuency, dayOfMonth)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isssssi", $user_id, $title, $descrip, $hour, $color, $frecuency, $dayOfMonth);
        $stmt->execute();
        $routine_id = $conn->insert_id;
        $stmt->close();

        if ($frecuency === 'weekly' && !empty($days)) {
            $s2 = $conn->prepare("INSERT INTO routine_day (routine_id, dayOfWeek) VALUES (?, ?)");
            foreach ($days as $day) { $s2->bind_param("is", $routine_id, $day); $s2->execute(); }
            $s2->close();
        }

        if (!empty($habit_ids)) {
            $s3 = $conn->prepare(
                "INSERT INTO routine_habit (routine_id, habit_id, sort_order) VALUES (?, ?, ?)"
            );
            foreach ($habit_ids as $i => $hid) {
                $sort = $i + 1;
                $s3->bind_param("iii", $routine_id, $hid, $sort);
                $s3->execute();
            }
            $s3->close();
        }

        if (!empty($steps)) {
            $s4 = $conn->prepare(
                "INSERT INTO routine_checklist (routine_id, title, sort_order) VALUES (?, ?, ?)"
            );
            foreach ($steps as $i => $step) {
                $sort = $i + 1;
                $s4->bind_param("isi", $routine_id, $step, $sort);
                $s4->execute();
            }
            $s4->close();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Routine created', 'id' => $routine_id]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


// PUT para editar rutina
if ($method === 'PUT') {
    $id         = intval($_GET['id']);
    $data       = json_decode(file_get_contents('php://input'), true);
    $title      = $data['title'];
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $hour       = isset($data['hour'])       ? $data['hour']       : null;
    $color      = isset($data['color'])      ? $data['color']      : '#6B8FA3';
    $frecuency  = isset($data['frecuency'])  ? $data['frecuency']  : 'daily';
    $dayOfMonth = isset($data['dayOfMonth']) ? intval($data['dayOfMonth']) : null;
    $days       = isset($data['days'])       ? $data['days']       : [];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "UPDATE routine SET title=?, descrip=?, hour=?, color=?, frecuency=?, dayOfMonth=? WHERE id=?"
        );
        $stmt->bind_param("sssssii", $title, $descrip, $hour, $color, $frecuency, $dayOfMonth, $id);
        $stmt->execute();
        $stmt->close();

        $s2 = $conn->prepare("DELETE FROM routine_day WHERE routine_id = ?");
        $s2->bind_param("i", $id);
        $s2->execute();
        $s2->close();

        if ($frecuency === 'weekly' && !empty($days)) {
            $s3 = $conn->prepare("INSERT INTO routine_day (routine_id, dayOfWeek) VALUES (?, ?)");
            foreach ($days as $day) { $s3->bind_param("is", $id, $day); $s3->execute(); }
            $s3->close();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Routine updated']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error updating routine']);
    }
}


// PATCH para actualizar estado según % completitud
if ($method === 'PATCH') {
    $id    = intval($_GET['id']);
    $data  = json_decode(file_get_contents('php://input'), true);
    $today = date('Y-m-d');

    $done_steps  = intval($data['done_steps']);
    $total_steps = intval($data['total_steps']);

    // calcular el estado
    $pct  = $total_steps > 0 ? ($done_steps / $total_steps) * 100 : 0;
    $done = 0;
    if ($pct >= 100)    $done = 2;
    elseif ($pct >= 50) $done = 1;

    // insertar o actualizar routine_record
    $stmt = $conn->prepare(
        "SELECT id FROM routine_record WHERE routine_id = ? AND dateOfRoutine = ?"
    );
    $stmt->bind_param("is", $id, $today);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $s2 = $conn->prepare(
            "UPDATE routine_record SET done=?, doneSubtasks=? WHERE routine_id=? AND dateOfRoutine=?"
        );
        $s2->bind_param("iiis", $done, $done_steps, $id, $today);
        $s2->execute();
        $s2->close();
    } else {
        $s2 = $conn->prepare(
            "INSERT INTO routine_record (routine_id, dateOfRoutine, totalSubtasks, doneSubtasks, done)
             VALUES (?, ?, ?, ?, ?)"
        );
        $s2->bind_param("isiii", $id, $today, $total_steps, $done_steps, $done);
        $s2->execute();
        $s2->close();
    }

    // calcular racha actual y actualizar best_streak
    $streak = 0;
    $s3 = $conn->prepare(
        "SELECT dateOfRoutine, done FROM routine_record WHERE routine_id = ? ORDER BY dateOfRoutine DESC"
    );
    $s3->bind_param("i", $id);
    $s3->execute();
    $r3       = $s3->get_result();
    $s3->close();

    $expected = new DateTime($today);
    while ($rec = $r3->fetch_assoc()) {
        $date     = new DateTime($rec['dateOfRoutine']);
        $rec_done = intval($rec['done']);
        if ($date == $expected) {
            if ($rec_done === 2)     { $streak++; $expected->modify('-1 day'); }
            elseif ($rec_done === 1) { $expected->modify('-1 day'); }
            else break;
        } elseif ($date < $expected) break;
    }

    if ($done === 2) {
        $s4 = $conn->prepare(
            "UPDATE routine SET best_streak = GREATEST(best_streak, ?) WHERE id = ?"
        );
        $s4->bind_param("ii", $streak, $id);
        $s4->execute();
        $s4->close();
    }

    echo json_encode([
        'status'         => 'success',
        'done'           => $done,
        'done_steps'     => $done_steps,
        'total_steps'    => $total_steps,
        'current_streak' => $streak
    ]);
}


// DELETE para eliminar rutina
if ($method === 'DELETE') {
    $id   = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM routine WHERE id = ?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'Routine deleted'])
        : json_encode(['status' => 'error', 'message' => 'Error deleting routine']);
    $stmt->close();
}
?>