<?php
session_start();
require_once '../db.php'; // Include the database connection file

// Get the selected class ID from the URL parameter
$classId = isset($_GET['class_id']) ? $_GET['class_id'] : null;

// Check if class_id is provided
if (!$classId) {
    echo json_encode(["error" => "Class ID is required"]);
    exit;
}

// Fetch class details from the 'classes' table
$sql = "
    SELECT c.id, s.section_name, c.period_start, c.period_end, c.schedule, c.class_time, c.room_number
    FROM classes c
    JOIN sections s ON c.section_id = s.id
    WHERE c.id = :class_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['class_id' => $classId]);
$class = $stmt->fetch();

// Check if class exists
if (!$class) {
    echo json_encode(["error" => "Class not found"]);
    exit;
}

// Fetch students in the selected class
$studentSql = "
    SELECT st.id, st.name
    FROM students st
    WHERE st.section_id = :section_id
";
$studentStmt = $pdo->prepare($studentSql);
$studentStmt->execute(['section_id' => $class['id']]);
$students = $studentStmt->fetchAll();

// Prepare response data
$response = [
    'section_name' => $class['section_name'],
    'period_start' => $class['period_start'],
    'period_end' => $class['period_end'],
    'schedule' => $class['schedule'],
    'class_time' => $class['class_time'],
    'room_number' => $class['room_number'],
    'students' => $students
];

// Return the data as JSON
echo json_encode($response);
