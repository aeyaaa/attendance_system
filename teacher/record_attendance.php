<?php
session_start();
require '../db.php';

// Receive POST data
$data = json_decode(file_get_contents('php://input'), true);
$studentUid = $data['student_uid'];
$classId = $data['class_id'];
$currentDate = date('Y-m-d');

try {
    // Check if student exists and belongs to the section
    $studentStmt = $pdo->prepare("
        SELECT id, name, section_id 
        FROM students 
        WHERE student_uid = ? AND section_id = ?
    ");
    $studentStmt->execute([$studentUid, $classId]);
    $student = $studentStmt->fetch();

    if (!$student) {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found or not in this section'
        ]);
        exit;
    }

    // Check for existing attendance record today
    $checkStmt = $pdo->prepare("
        SELECT id, time_in, time_out 
        FROM attendance 
        WHERE student_uid = ? AND DATE(date) = ? AND class_id = ?
    ");
    $checkStmt->execute([$studentUid, $currentDate, $classId]);
    $attendance = $checkStmt->fetch();

    if (!$attendance) {
        // Create time-in record
        $insertStmt = $pdo->prepare("
            INSERT INTO attendance (student_uid, date, time_in, class_id)
            VALUES (?, ?, NOW(), ?)
        ");
        $insertStmt->execute([$studentUid, $currentDate, $classId]);
        
        echo json_encode([
            'success' => true,
            'action' => 'time_in',
            'student_name' => $student['name']
        ]);
    } elseif ($attendance['time_in'] && !$attendance['time_out']) {
        // Update with time-out
        $updateStmt = $pdo->prepare("
            UPDATE attendance 
            SET time_out = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$attendance['id']]);
        
        echo json_encode([
            'success' => true,
            'action' => 'time_out',
            'student_name' => $student['name']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Attendance already completed for today'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 