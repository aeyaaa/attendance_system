<?php
session_start();
require '../db.php';

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Insert section
    $stmt = $pdo->prepare("INSERT INTO sections (section_name, course, total_students) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['section_name'],
        $_POST['course'],
        $_POST['total_students']
    ]);
    
    $section_id = $pdo->lastInsertId();

    // Insert students
    $stmt = $pdo->prepare("INSERT INTO students (name, student_uid, section_id) VALUES (?, ?, ?)");
    
    // Get the arrays from POST
    $student_names = $_POST['student_names'];
    $rfid_tags = $_POST['rfid_tags'];

    // Insert each student with their RFID tag
    foreach ($student_names as $index => $name) {
        $rfid_tag = $rfid_tags[$index];
        $stmt->execute([$name, $rfid_tag, $section_id]);
    }

    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success_message'] = "Section and students added successfully!";
    header("Location: ../admin_dashboard.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: ../admin_dashboard.php");
    exit();
}
?>
