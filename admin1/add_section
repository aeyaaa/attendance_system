<?php
session_start();
require '../db.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_name = $_POST['section_name'];
    $total_students = $_POST['total_students'];

    // Prepare and execute the SQL insert query for the section
    $sql = "INSERT INTO sections (section_name, total_students) VALUES (:section_name, :total_students)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':section_name', $section_name);
    $stmt->bindParam(':total_students', $total_students);

    try {
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Section added successfully!";
            header("Location: /attendance_system/admin_dashboard.php"); // Redirect to admin dashboard
            exit();
        } else {
            echo "Error: Could not add the section.";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
