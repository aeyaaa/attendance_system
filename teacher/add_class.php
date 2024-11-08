<?php
require '../db.php';  // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $section_id = $_POST['section_id'];
    $period_start = $_POST['period_start'];
    $period_end = $_POST['period_end'];
    $schedule = $_POST['schedule'];
    $room_number = $_POST['room_number'];

    // Insert data into the 'classes' table
    $sql = "INSERT INTO classes (section_id, period_start, period_end, schedule, room_number) 
            VALUES (:section_id, :period_start, :period_end, :schedule, :room_number)";

    $stmt = $pdo->prepare($sql);

    // Bind the parameters
    $stmt->bindParam(':section_id', $section_id);
    $stmt->bindParam(':period_start', $period_start);
    $stmt->bindParam(':period_end', $period_end);
    $stmt->bindParam(':schedule', $schedule);
    $stmt->bindParam(':room_number', $room_number);

    // Execute the query
    if ($stmt->execute()) {
        echo "Class added successfully!";
    } else {
        echo "Error adding class!";
    }
}
?>
