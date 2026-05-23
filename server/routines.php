<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

// GET para obtener rutinas del usuario
if ($method === 'GET') {

    // detalle completo de una rutina por id
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

        // cargar los pasos del checklist
        $stmt3 = $conn->prepare(
            "SELECT * FROM routine_checklist WHERE routine_id = ? ORDER BY sort_order ASC"
        );
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $r3        = $stmt3->get_result();
        $checklist = [];
        while ($row = $r3->fetch_assoc()) {
            // paso solo considerado si se marca hoy
            $row['done'] = ($row['done'] == 1 && $row['last_done_date'] == date('Y-m-d')) ? 1 : 0;
            $checklist[] = $row;
        }
        $stmt3->close();

        //se carga los dias de la semana si es semanal
        $stmt4 = $conn->prepare("SELECT dayOfWeek FROM routine_day WHERE routine_id = ?");
        $stmt4->bind_param("i", $id);
        $stmt4->execute();
        $r4   = $stmt4->get_result();
        $days = [];
        while ($d = $r4->fetch_assoc()) $days[] = $d['dayOfWeek'];
        $stmt4->close();

        $routine['checklist'] = $checklist;
        $routine['days']      = $days;

        echo json_encode($routine, JSON_UNESCAPED_UNICODE);

    //comprobar si toca hoy
    } elseif (isset($_GET['user_id']) && isset($_GET['today'])) {
        $user_id      = intval($_GET['user_id']);
        $today_name   = strtolower(date('l'));
        $day_of_month = intval(date('j'));

        //stament de la rutina si toca hoy
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

        // se cargan los checklist
        foreach ($routines as &$routine) {
            $sc = $conn->prepare(
                "SELECT * FROM routine_checklist WHERE routine_id = ? ORDER BY sort_order ASC"
            );
            $sc->bind_param("i", $routine['id']);
            $sc->execute();
            $rc        = $sc->get_result();
            $checklist = [];
            while ($c = $rc->fetch_assoc()) {
                $c['done'] = ($c['done'] == 1 && $c['last_done_date'] == date('Y-m-d')) ? 1 : 0;
                $checklist[] = $c;
            }
            $sc->close();

            $routine['checklist']   = $checklist;
            $routine['total_steps'] = count($checklist);
            $routine['done_steps']  = count(array_filter($checklist, fn($c) => $c['done']));
        }

        echo json_encode($routines, JSON_UNESCAPED_UNICODE);

    // todas las rutinas del usuario con todos sus atributos
    } elseif (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        $stmt = $conn->prepare("SELECT * FROM routine WHERE user_id = ? ORDER BY hour ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result   = $stmt->get_result();
        $routines = [];
        while ($row = $result->fetch_assoc()) $routines[] = $row;
        $stmt->close();

        foreach ($routines as &$routine) {

            //dias de la semana con frecuencial semanal
            $s1 = $conn->prepare("SELECT dayOfWeek FROM routine_day WHERE routine_id = ?");
            $s1->bind_param("i", $routine['id']);
            $s1->execute();
            $r1   = $s1->get_result();
            $days = [];
            while ($d = $r1->fetch_assoc()) $days[] = $d['dayOfWeek'];
            $s1->close();
            $routine['days'] = $days;

            // pasos del checklist
            $s3 = $conn->prepare(
                "SELECT * FROM routine_checklist WHERE routine_id = ? ORDER BY sort_order ASC"
            );
            $s3->bind_param("i", $routine['id']);
            $s3->execute();
            $r3        = $s3->get_result();
            $checklist = [];
            while ($c = $r3->fetch_assoc()) {
                //paso solo considerado si es marcado hoy
                $c['done'] = ($c['done'] == 1 && $c['last_done_date'] == date('Y-m-d')) ? 1 : 0;
                $checklist[] = $c;
            }
            $s3->close();
            $routine['checklist']   = $checklist;
            $routine['total_steps'] = count($checklist);
            $routine['done_steps']  = count(array_filter($checklist, fn($c) => $c['done']));

            // estado de hoy desde routine_record
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
                WHERE routine_id = ? ORDER BY dateOfRoutine DESC
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


// POST para crear rutina con dias y pasos
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $user_id    = intval($data['user_id']);
    $title      = $data['title'];
    $descrip    = isset($data['descrip'])    ? $data['descrip']    : null;
    $icon       = isset($data['icon'])       ? $data['icon']       : null;
    $hour       = isset($data['hour'])       ? $data['hour']       : null;
    $color      = isset($data['color'])      ? $data['color']      : '#6B8FA3';
    $frecuency  = isset($data['frecuency'])  ? $data['frecuency']  : 'daily';
    $dayOfMonth = isset($data['dayOfMonth']) ? intval($data['dayOfMonth']) : null;
    $days       = isset($data['days'])       ? $data['days']       : [];
    $steps      = isset($data['steps'])      ? $data['steps']      : [];

    $conn->begin_transaction();

    try {
        //insertamos la rutina principal
        $stmt = $conn->prepare(
            "INSERT INTO routine (user_id, title, descrip, icon, hour, color, frecuency, dayOfMonth)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issssssi", $user_id, $title, $descrip, $icon, $hour, $color, $frecuency, $dayOfMonth);
        $stmt->execute();
        $routine_id = $conn->insert_id;
        $stmt->close();

        // insertamos los dias si es semanal
        if ($frecuency === 'weekly' && !empty($days)) {
            $s2 = $conn->prepare("INSERT INTO routine_day (routine_id, dayOfWeek) VALUES (?, ?)");
            foreach ($days as $day) { $s2->bind_param("is", $routine_id, $day); $s2->execute(); }
            $s2->close();
        }

        // insertamos los pasos del checklist
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
    $icon       = isset($data['icon'])       ? $data['icon']       : null;
    $hour       = isset($data['hour'])       ? $data['hour']       : null;
    $color      = isset($data['color'])      ? $data['color']      : '#6B8FA3';
    $frecuency  = isset($data['frecuency'])  ? $data['frecuency']  : 'daily';
    $dayOfMonth = isset($data['dayOfMonth']) ? intval($data['dayOfMonth']) : null;
    $days       = isset($data['days'])       ? $data['days']       : [];

    $conn->begin_transaction();

    try {
        // actualizacion de datos principales
        $stmt = $conn->prepare(
            "UPDATE routine SET title=?, descrip=?, icon=?, hour=?, color=?, frecuency=?, dayOfMonth=? WHERE id=?"
        );
        $stmt->bind_param("ssssssii", $title, $descrip, $icon, $hour, $color, $frecuency, $dayOfMonth, $id);
        $stmt->execute();
        $stmt->close();

        // borrados de dias anteriores y lo vovlvemos a poner
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


// PATCH para recalcular y guardar el estado 
if ($method === 'PATCH') {
    $id    = intval($_GET['id']);
    $data  = json_decode(file_get_contents('php://input'), true);
    $today = date('Y-m-d');

    $done_steps  = intval($data['done_steps']);
    $total_steps = intval($data['total_steps']);

    // pending,tried o done segun porentaje
    $pct  = $total_steps > 0 ? ($done_steps / $total_steps) * 100 : 0;
    $done = 0;
    if ($pct >= 100)    $done = 2;
    elseif ($pct >= 50) $done = 1;

    // actualizacion o registro de hoy
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

    // calcular la racha actual
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

    // actualizar la mejor racha si se supera
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


// DELETE para eliminar rutina y todo lo relacionado por cascade
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