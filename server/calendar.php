<?php
include 'db.php';

// GET — obtener todos los eventos del calendario en un rango de fechas
// Uso: GET /api/calendar?user_id=1&start=2026-03-01&end=2026-03-31
if ($method === 'GET') {

    if (!isset($_GET['user_id'], $_GET['start'], $_GET['end'])) {
        echo json_encode(['status' => 'error', 'message' => 'user_id, start and end are required']);
        exit;
    }

    $user_id = intval($_GET['user_id']);
    $start   = $_GET['start'];
    $end     = $_GET['end'];
    $events  = [];

    // ── TAREAS ──────────────────────────────────────────────
    // Aparecen en su fecha de vencimiento con color según dificultad
    $stmt = $conn->prepare("
        SELECT id, title, descrip, startDate, expDate, difficulty, done
        FROM task
        WHERE user_id = ?
        AND DATE(expDate) BETWEEN ? AND ?
    ");
    $stmt->bind_param("iss", $user_id, $start, $end);
    $stmt->execute();
    $tasks_raw = $stmt->get_result();

    // Colores de dificultad de tu paleta
    $diff_colors = [
        'easy'   => '#7A9E7E',
        'medium' => '#C9A030',
        'hard'   => '#B05050',
    ];

    while ($task = $tasks_raw->fetch_assoc()) {
        $color = $diff_colors[$task['difficulty']] ?? '#C9A96E';
        $events[] = [
            'id'    => 'task-' . $task['id'],
            'title' => '✅ ' . $task['title'],
            'start' => $task['startDate'] ?? $task['expDate'],
            'end'   => $task['expDate'],
            'color' => $task['done'] ? '#7A9E7E' : $color,
            'extendedProps' => [
                'type'        => 'task',
                'descrip'     => $task['descrip'],
                'difficulty'  => $task['difficulty'],
                'done'        => (bool)$task['done'],
                'original_id' => $task['id'],
            ]
        ];
    }

    // ── HÁBITOS ─────────────────────────────────────────────
    // Se expanden por su frecuencia dentro del rango dado
    $stmt2 = $conn->prepare("
        SELECT h.id, h.title, h.icon, h.frecuency, h.dayOfMonth
        FROM habit h
        WHERE h.user_id = ?
    ");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $habits_raw = $stmt2->get_result();

    $habit_color = '#8B5E3C'; // cinnamon-mid de tu paleta

    while ($habit = $habits_raw->fetch_assoc()) {
        $days_in_range = get_days_in_range($habit, $start, $end, $conn);

        foreach ($days_in_range as $date) {
            // ¿Estaba completado ese día?
            $stmt3 = $conn->prepare(
                "SELECT done FROM habit_record WHERE habit_id = ? AND dateOfHabit = ?"
            );
            $stmt3->bind_param("is", $habit['id'], $date);
            $stmt3->execute();
            $record = $stmt3->get_result()->fetch_assoc();
            $done   = $record ? (bool)$record['done'] : false;

            $events[] = [
                'id'        => 'habit-' . $habit['id'] . '-' . $date,
                'title'     => ($habit['icon'] ?? '🔄') . ' ' . $habit['title'],
                'start'     => $date,
                'allDay'    => true, // hábitos son de día completo
                'color'     => $done ? '#7A9E7E' : $habit_color,
                'extendedProps' => [
                    'type'        => 'habit',
                    'done'        => $done,
                    'original_id' => $habit['id'],
                ]
            ];
        }
    }

    // ── RUTINAS ─────────────────────────────────────────────
    // Se expanden por frecuencia y se colocan en su hora si la tienen
    $stmt4 = $conn->prepare("
        SELECT id, title, hora, color, frecuency, dayOfMonth
        FROM routine
        WHERE user_id = ?
    ");
    $stmt4->bind_param("i", $user_id);
    $stmt4->execute();
    $routines_raw = $stmt4->get_result();

    while ($routine = $routines_raw->fetch_assoc()) {
        $days_in_range = get_days_in_range($routine, $start, $end, $conn, 'routine');

        foreach ($days_in_range as $date) {
            // ¿Estaba completada ese día?
            $stmt5 = $conn->prepare(
                "SELECT done FROM routine_record WHERE routine_id = ? AND dateOfRoutine = ?"
            );
            $stmt5->bind_param("is", $routine['id'], $date);
            $stmt5->execute();
            $record = $stmt5->get_result()->fetch_assoc();
            $done   = $record ? (bool)$record['done'] : false;

            // Si tiene hora, construir datetime completo
            $start_dt = $routine['hora']
                ? $date . 'T' . $routine['hora']
                : $date;

            $color = $done ? '#7A9E7E' : ($routine['color'] ?? '#6B8FA3');

            $events[] = [
                'id'     => 'routine-' . $routine['id'] . '-' . $date,
                'title'  => '📋 ' . $routine['title'],
                'start'  => $start_dt,
                'allDay' => !$routine['hora'],
                'color'  => $color,
                'extendedProps' => [
                    'type'        => 'routine',
                    'done'        => $done,
                    'original_id' => $routine['id'],
                ]
            ];
        }
    }

    echo json_encode($events, JSON_UNESCAPED_UNICODE);
}


// ── FUNCIÓN AUXILIAR ─────────────────────────────────────────
// Calcula qué días del rango le tocan a un hábito o rutina
// según su frecuencia (daily, weekly, monthly)

function get_days_in_range($item, $start, $end, $conn, $type = 'habit') {
    $days   = [];
    $current = new DateTime($start);
    $last    = new DateTime($end);

    // Para semanal, obtener los días específicos de la BD
    $specific_days = [];
    if ($item['frecuency'] === 'weekly') {
        $table  = $type === 'habit' ? 'habit_day' : 'routine_day';
        $col    = $type === 'habit' ? 'habit_id'  : 'routine_id';
        $stmt   = $conn->prepare("SELECT dayOfWeek FROM $table WHERE $col = ?");
        $stmt->bind_param("i", $item['id']);
        $stmt->execute();
        $raw = $stmt->get_result();
        while ($d = $raw->fetch_assoc()) $specific_days[] = strtolower($d['dayOfWeek']);
    }

    // Mapeo nombre de día en inglés (PHP) a los valores del ENUM
    $day_map = [
        'monday'    => 'monday',
        'tuesday'   => 'tuesday',
        'wednesday' => 'wednesday',
        'thursday'  => 'thursday',
        'friday'    => 'friday',
        'saturday'  => 'saturday',
        'sunday'    => 'sunday',
    ];

    while ($current <= $last) {
        $date_str    = $current->format('Y-m-d');
        $day_name    = strtolower($current->format('l')); // monday, tuesday...
        $day_of_month = intval($current->format('j'));

        $include = false;

        switch ($item['frecuency']) {
            case 'daily':
                $include = true;
                break;
            case 'weekly':
                $include = in_array($day_map[$day_name] ?? $day_name, $specific_days);
                break;
            case 'monthly':
                $include = ($day_of_month === intval($item['dayOfMonth']));
                break;
        }

        if ($include) $days[] = $date_str;
        $current->modify('+1 day');
    }

    return $days;
}
?>
