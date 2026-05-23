<?php
include_once 'db.php';

if (!isset($method)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Direct access not allowed']);
    exit;
}

if ($method === 'GET') {

    if (!isset($_GET['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
        exit;
    }

    $user_id     = intval($_GET['user_id']);
    $month       = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
    $year        = isset($_GET['year'])  ? intval($_GET['year'])  : intval(date('Y'));
    $month_str   = str_pad($month, 2, '0', STR_PAD_LEFT);
    $month_start = "$year-$month_str-01";
    $month_end   = date('Y-m-t', strtotime($month_start));

    $result = [];

    //resumen general

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total FROM habit_record hr
        JOIN habit h ON hr.habit_id = h.id
        WHERE h.user_id = ? AND hr.done = 2
        AND hr.dateOfHabit BETWEEN ? AND ?
    ");
    $stmt->bind_param("iss", $user_id, $month_start, $month_end);
    $stmt->execute();
    $habits_done = intval($stmt->get_result()->fetch_assoc()['total']);
    $stmt->close();

    $stmt2 = $conn->prepare("
        SELECT COUNT(*) AS total FROM task_record tr
        JOIN task t ON tr.task_id = t.id
        WHERE t.user_id = ? AND tr.doneDate BETWEEN ? AND ?
    ");
    $stmt2->bind_param("iss", $user_id, $month_start, $month_end);
    $stmt2->execute();
    $tasks_done = intval($stmt2->get_result()->fetch_assoc()['total']);
    $stmt2->close();

    $stmt3 = $conn->prepare("
        SELECT COUNT(*) AS total FROM routine_record rr
        JOIN routine r ON rr.routine_id = r.id
        WHERE r.user_id = ? AND rr.done = 2
        AND rr.dateOfRoutine BETWEEN ? AND ?
    ");
    $stmt3->bind_param("iss", $user_id, $month_start, $month_end);
    $stmt3->execute();
    $routines_done = intval($stmt3->get_result()->fetch_assoc()['total']);
    $stmt3->close();

    $stmt4 = $conn->prepare("SELECT MAX(best_streak) AS best FROM habit WHERE user_id = ?");
    $stmt4->bind_param("i", $user_id);
    $stmt4->execute();
    $best_streak = intval($stmt4->get_result()->fetch_assoc()['best']);
    $stmt4->close();

    $result['summary'] = [
        'habits_done'   => $habits_done,
        'tasks_done'    => $tasks_done,
        'routines_done' => $routines_done,
        'best_streak'   => $best_streak
    ];

    // habitos por dia

    $days_in_month = intval(date('t', strtotime($month_start)));

    // obtener ids de los habits del usuario
    $stmt5 = $conn->prepare("SELECT id FROM habit WHERE user_id = ?");
    $stmt5->bind_param("i", $user_id);
    $stmt5->execute();
    $r5        = $stmt5->get_result();
    $habit_ids = [];
    while ($row = $r5->fetch_assoc()) { $habit_ids[] = $row['id']; }
    $stmt5->close();

    $total_habits  = count($habit_ids);
    $habit_labels  = [];
    $habit_done    = [];
    $habit_tried   = [];
    $habit_pending = [];

    for ($day = 1; $day <= $days_in_month; $day++) {
        $day_str        = "$year-$month_str-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        $habit_labels[] = (string)$day;

        if ($total_habits == 0) {
            $habit_done[]    = 0;
            $habit_tried[]   = 0;
            $habit_pending[] = 0;
            continue;
        }

        $placeholders = implode(',', array_fill(0, $total_habits, '?'));
        $types        = str_repeat('i', $total_habits) . 's';
        $params       = array_merge($habit_ids, [$day_str]);

        // done
        $s6 = $conn->prepare(
            "SELECT COUNT(*) AS c FROM habit_record
             WHERE habit_id IN ($placeholders) AND dateOfHabit = ? AND done = 2"
        );
        $s6->bind_param($types, ...$params);
        $s6->execute();
        $done_count = intval($s6->get_result()->fetch_assoc()['c']);
        $s6->close();

        // tried
        $s7 = $conn->prepare(
            "SELECT COUNT(*) AS c FROM habit_record
             WHERE habit_id IN ($placeholders) AND dateOfHabit = ? AND done = 1"
        );
        $s7->bind_param($types, ...$params);
        $s7->execute();
        $tried_count = intval($s7->get_result()->fetch_assoc()['c']);
        $s7->close();

        $habit_done[]    = $done_count;
        $habit_tried[]   = $tried_count;
        $habit_pending[] = max(0, $total_habits - $done_count - $tried_count);
    }

    $result['habits'] = [
        'labels'  => $habit_labels,
        'done'    => $habit_done,
        'tried'   => $habit_tried,
        'pending' => $habit_pending
    ];

    // grafico en dona de tasks

    $s8 = $conn->prepare("
        SELECT COUNT(*) AS c FROM task_record tr
        JOIN task t ON tr.task_id = t.id
        WHERE t.user_id = ? AND tr.onTime = 1
        AND tr.doneDate BETWEEN ? AND ?
    ");
    $s8->bind_param("iss", $user_id, $month_start, $month_end);
    $s8->execute();
    $on_time = intval($s8->get_result()->fetch_assoc()['c']);
    $s8->close();

    $s9 = $conn->prepare("
        SELECT COUNT(*) AS c FROM task_record tr
        JOIN task t ON tr.task_id = t.id
        WHERE t.user_id = ? AND tr.onTime = 0
        AND tr.doneDate BETWEEN ? AND ?
    ");
    $s9->bind_param("iss", $user_id, $month_start, $month_end);
    $s9->execute();
    $late = intval($s9->get_result()->fetch_assoc()['c']);
    $s9->close();

    $s10 = $conn->prepare("
        SELECT COUNT(*) AS c FROM task
        WHERE user_id = ? AND done = 0
        AND DATE(expDate) BETWEEN ? AND ?
    ");
    $s10->bind_param("iss", $user_id, $month_start, $month_end);
    $s10->execute();
    $pending_tasks = intval($s10->get_result()->fetch_assoc()['c']);
    $s10->close();

    $result['tasks'] = [
        'on_time' => $on_time,
        'late'    => $late,
        'pending' => $pending_tasks
    ];

    // rutinas con grafico de lineas

    $routine_labels     = [];
    $routine_completion = [];
    $current_day        = new DateTime($month_start);
    $end_day            = new DateTime($month_end);
    $week_num           = 1;

    while ($current_day <= $end_day) {
        $week_start_str = $current_day->format('Y-m-d');
        $week_end_date  = clone $current_day;
        $week_end_date->modify('+6 days');
        if ($week_end_date > $end_day) { $week_end_date = clone $end_day; }
        $week_end_str = $week_end_date->format('Y-m-d');

        $s11 = $conn->prepare("
            SELECT AVG(
                CASE WHEN totalSubtasks = 0 THEN 0
                ELSE (doneSubtasks / totalSubtasks) * 100 END
            ) AS avg_pct
            FROM routine_record rr
            JOIN routine r ON rr.routine_id = r.id
            WHERE r.user_id = ?
            AND rr.dateOfRoutine BETWEEN ? AND ?
        ");
        $s11->bind_param("iss", $user_id, $week_start_str, $week_end_str);
        $s11->execute();
        $avg_row = $s11->get_result()->fetch_assoc();
        $s11->close();

        $routine_labels[]     = 'Week ' . $week_num;
        $routine_completion[] = $avg_row['avg_pct'] !== null
            ? round(floatval($avg_row['avg_pct']))
            : 0;

        $week_num++;
        $current_day->modify('+7 days');
    }

    $result['routines'] = [
        'labels'     => $routine_labels,
        'completion' => $routine_completion
    ];

    // top 5 de rachas

    $s12 = $conn->prepare("
        SELECT id, title, best_streak FROM habit
        WHERE user_id = ?
        ORDER BY best_streak DESC
        LIMIT 5
    ");
    $s12->bind_param("i", $user_id);
    $s12->execute();
    $r12     = $s12->get_result();
    $streaks = [];
    while ($row = $r12->fetch_assoc()) { $streaks[] = $row; }
    $s12->close();

    // calculo de la racha habito
    foreach ($streaks as &$habit) {
        $s13 = $conn->prepare("
            SELECT dateOfHabit, done FROM habit_record
            WHERE habit_id = ? ORDER BY dateOfHabit DESC
        ");
        $s13->bind_param("i", $habit['id']);
        $s13->execute();
        $r13 = $s13->get_result();
        $s13->close();

        $current_streak = 0;
        $expected       = new DateTime(date('Y-m-d'));

        while ($rec = $r13->fetch_assoc()) {
            $date     = new DateTime($rec['dateOfHabit']);
            $rec_done = intval($rec['done']);

            if ($date == $expected) {
                if ($rec_done == 2)     { $current_streak++; $expected->modify('-1 day'); }
                elseif ($rec_done == 1) { $expected->modify('-1 day'); }
                else break;
            } elseif ($date < $expected) { break; }
        }

        $habit['current_streak'] = $current_streak;
    }

    $result['streaks'] = $streaks;

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>