<?php
session_start();
require '../db.php';

// Set default time zone (adjust based on your location)
date_default_timezone_set('Asia/Manila'); 

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$class_id = $data['class_id'] ?? null;
$student_uid = $data['student_uid'] ?? null;

if (!$class_id || !$student_uid) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    // Clean and format the student_uid
    $student_uid = trim($student_uid);
    
    // First, check if the student exists and get class schedule
    $stmt = $pdo->prepare("
        SELECT 
            students.*, 
            classes.time_start,
            classes.time_end
        FROM students 
        JOIN classes ON students.section_id = classes.section_id
        WHERE students.student_uid = :student_uid 
        AND classes.id = :class_id
    ");
    
    $stmt->execute([
        'student_uid' => $student_uid,
        'class_id' => $class_id
    ]);
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        error_log("Student not found - UID: $student_uid, Class ID: $class_id");
        echo json_encode([
            'success' => false,
            'message' => 'Student not found or not enrolled in this class'
        ]);
        exit;
    }

    // Check for existing attendance today
    $stmt = $pdo->prepare("
        SELECT * FROM attendance 
        WHERE student_uid = :student_uid 
        AND class_id = :class_id 
        AND DATE(date) = CURRENT_DATE()
    ");
    
    $stmt->execute([
        'student_uid' => $student_uid,
        'class_id' => $class_id
    ]);
    
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attendance) {
        // Combine current date with class start time
        $currentDate = date('Y-m-d');
        $timeStart = strtotime("$currentDate " . $student['time_start']);
        $currentTime = time();

        // Calculate the difference in minutes
        $timeDiff = ($currentTime - $timeStart) / 60;

        // Ensure timeDiff is always positive
        $timeDiff = abs($timeDiff);

        $remarks = '';
        if ($timeDiff <= 0) {
            $remarks = 'Present'; // Before class starts
        } elseif ($timeDiff <= 15) {
            $remarks = 'Present'; // Time in within 15 minutes
        } elseif ($timeDiff <= 30) {
            $remarks = 'Late'; // Time in between 16 and 30 minutes
        } else {
            $remarks = 'Absent'; // After 30 minutes
        }

        // Record time in with remarks and time_gap
        $stmt = $pdo->prepare("
            INSERT INTO attendance (
                student_uid,
                class_id,
                date,
                time_in,
                remarks,
                time_gap
            ) VALUES (
                :student_uid,
                :class_id,
                CURRENT_DATE(),
                CURRENT_TIME(),
                :remarks,
                :time_gap
            )
        ");
        
        $result = $stmt->execute([
            'student_uid' => $student_uid,
            'class_id' => $class_id,
            'remarks' => $remarks,
            'time_gap' => $timeDiff
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Time in recorded successfully ($remarks)",
            'action' => 'time_in',
            'student_name' => $student['name'],
            'remarks' => $remarks,
            'time_gap' => round($timeDiff, 2) // Include rounded time gap in the response
        ]);
    } elseif (!$attendance['time_out']) {
        // Record time out without changing remarks
        $stmt = $pdo->prepare("
            UPDATE attendance 
            SET time_out = CURRENT_TIME()
            WHERE student_uid = :student_uid 
            AND class_id = :class_id 
            AND DATE(date) = CURRENT_DATE()
        ");
        
        $result = $stmt->execute([
            'student_uid' => $student_uid,
            'class_id' => $class_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Time out recorded successfully',
            'action' => 'time_out',
            'student_name' => $student['name'],
            'remarks' => $attendance['remarks']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Attendance already completed for today'
        ]);
    }

} catch (Exception $e) {
    error_log("Error in scan_tag.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
