<?php
session_start();
require '../db.php';

try {
    // Get the schedule array and convert it to a string
    $schedule = isset($_POST['schedule']) ? implode(',', $_POST['schedule']) : '';
    
    $stmt = $pdo->prepare("
        INSERT INTO classes (
            section_id, 
            period_start, 
            period_end, 
            time_start,
            time_end,
            schedule, 
            room_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['section_id'],
        $_POST['period_start'],
        $_POST['period_end'],
        $_POST['time_start'],
        $_POST['time_end'],
        $schedule,
        $_POST['room_number']
    ]);

    $_SESSION['success_message'] = "Class added successfully!";
    header("Location: ../teacher_dashboard.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: ../teacher_dashboard.php");
    exit();
}
?>
