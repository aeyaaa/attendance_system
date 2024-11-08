<?php
session_start();
require '../db.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_name = $_POST['section_name'];
    $total_students = $_POST['total_students'];
    $student_names = $_POST['student_names'] ?? []; // Retrieve the student names array

    // Insert the section into the database
    $sql = "INSERT INTO sections (section_name, total_students) VALUES (:section_name, :total_students)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':section_name', $section_name);
    $stmt->bindParam(':total_students', $total_students);

    try {
        $pdo->beginTransaction();

        if ($stmt->execute()) {
            $section_id = $pdo->lastInsertId(); // Get the newly inserted section's ID

            // Insert each student into the students table, associated with the section
            $sql_student = "INSERT INTO students (section_id, name) VALUES (:section_id, :name)";
            $stmt_student = $pdo->prepare($sql_student);

            foreach ($student_names as $student_name) {
                $stmt_student->bindParam(':section_id', $section_id);
                $stmt_student->bindParam(':name', $student_name);
                $stmt_student->execute();
            }

            $pdo->commit();
            $_SESSION['success_message'] = "Section and students added successfully!";
            header("Location: /attendance_system/admin_dashboard.php"); // Redirect to admin dashboard
            exit();
        } else {
            echo "Error: Could not add the section.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
