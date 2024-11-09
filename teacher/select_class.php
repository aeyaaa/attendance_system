<?php
session_start();
require '../db.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];

    // You can add any logic here to process the class selection
    // For example, saving the selection or displaying class details

    // Redirecting back to the teacher dashboard
    header("Location: /attendance_system/teacher_dashboard.php");
    exit();
}
?>
