<?php
require '../db.php'; // Adjust path as needed

// Get the section ID from the query string
$sectionId = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if ($sectionId > 0) {
    $sql = "SELECT name FROM students WHERE section_id = :section_id"; // Adjust to your table and column names
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['section_id' => $sectionId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($students);
} else {
    echo json_encode([]);
}
?>
