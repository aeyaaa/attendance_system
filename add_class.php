<?php
require 'db.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_code = $_POST['class_code'];
    $course_name = $_POST['course_name'];
    $section = $_POST['section'];
    $schedule = $_POST['schedule'];
    $room_no = $_POST['room_no'];
    $students = $_POST['students']; // Array of selected student IDs

    // Insert the class into the classes table
    $sql = "INSERT INTO classes (class_code, course_name, section, schedule, room_no) VALUES (:class_code, :course_name, :section, :schedule, :room_no)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':class_code', $class_code);
    $stmt->bindParam(':course_name', $course_name);
    $stmt->bindParam(':section', $section);
    $stmt->bindParam(':schedule', $schedule);
    $stmt->bindParam(':room_no', $room_no);
    
    if ($stmt->execute()) {
        // Get the last inserted class ID
        $class_id = $pdo->lastInsertId();

        // Insert each selected student into the class_students junction table
        $sql = "INSERT INTO class_students (class_id, student_id) VALUES (:class_id, :student_id)";
        $stmt = $pdo->prepare($sql);

        foreach ($students as $student_id) {
            $stmt->bindParam(':class_id', $class_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
        }

        echo "<script>alert('Class and students added successfully'); window.location.href='teacher_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error adding class'); window.location.href='teacher_dashboard.php';</script>";
    }
}
?>
