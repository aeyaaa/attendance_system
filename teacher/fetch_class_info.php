<?php
session_start();
require '../db.php';

$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    echo json_encode(['error' => 'No class ID provided']);
    exit;
}

try {
    // Get class information
    $stmt = $pdo->prepare("
        SELECT 
            classes.*,
            sections.section_name
        FROM classes 
        JOIN sections ON classes.section_id = sections.id
        WHERE classes.id = :class_id
    ");
    
    $stmt->execute(['class_id' => $class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        echo json_encode(['error' => 'Class not found']);
        exit;
    }

    // Get students and their attendance
    $stmt = $pdo->prepare("
        SELECT 
            students.name as student_name,
            students.student_uid,
            TIME_FORMAT(attendance.time_in, '%h:%i %p') as time_in,
            TIME_FORMAT(attendance.time_out, '%h:%i %p') as time_out,
            attendance.remarks,
            CASE 
                WHEN attendance.time_in IS NOT NULL AND attendance.time_out IS NULL THEN 'Pending Time-out'
                WHEN attendance.time_in IS NOT NULL AND attendance.time_out IS NOT NULL THEN 'Complete'
                ELSE 'Not Yet Time-in'
            END as status
        FROM students
        LEFT JOIN attendance ON students.student_uid = attendance.student_uid 
            AND DATE(attendance.date) = CURRENT_DATE()
            AND attendance.class_id = :class_id
        WHERE students.section_id = :section_id
        ORDER BY students.name ASC
    ");

    $stmt->execute([
        'class_id' => $class_id,
        'section_id' => $class['section_id']
    ]);

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $response = [
        'section_name' => $class['section_name'],
        'period_start' => $class['period_start'],
        'period_end' => $class['period_end'],
        'schedule' => $class['schedule'],
        'time_start' => date('h:i A', strtotime($class['time_start'])),
        'time_end' => date('h:i A', strtotime($class['time_end'])),
        'room_number' => $class['room_number'],
        'students' => array_map(function($student) {
            return [
                'student_name' => $student['student_name'],
                'time_in' => $student['time_in'],
                'time_out' => $student['time_out'],
                'status' => $student['status'],
                'remarks' => $student['remarks']
            ];
        }, $students)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in fetch_class_info.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
