<?php
require '../db.php';

if (isset($_GET['class_id'])) {
    $classId = $_GET['class_id'];
    $sql = "
        SELECT sections.section_name, classes.period_start, classes.period_end, classes.schedule, classes.class_time, classes.room_number
        FROM classes
        JOIN sections ON classes.section_id = sections.id
        WHERE classes.id = :class_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['class_id' => $classId]);
    $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($classInfo) {
        echo json_encode($classInfo);
    } else {
        echo json_encode(['error' => 'Class not found']);
    }
} else {
    echo json_encode(['error' => 'Class ID not provided']);
}
?>
