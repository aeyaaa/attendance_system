<?php
require './db.php';

if (isset($_GET['section_id'])) {
    $section_id = $_GET['section_id'];

    // Fetch students in the selected section
    $sql = "SELECT name FROM students WHERE section_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$section_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($students);
}
?>
