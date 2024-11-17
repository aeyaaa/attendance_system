<?php
require '../db.php';

$section_id = $_GET['section_id'];

try {
    $stmt = $pdo->prepare("SELECT name, student_uid FROM students WHERE section_id = ?");
    $stmt->execute([$section_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($students);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
