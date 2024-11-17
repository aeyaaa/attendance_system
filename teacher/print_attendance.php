<?php
session_start();
require '../db.php';

$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class ID is required');
}

// Fetch class, section, and teacher information
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        s.section_name,
        COALESCE(CONCAT('MR. ', u.name), 'Teacher Not Assigned') as teacher_name
    FROM classes c
    JOIN sections s ON c.section_id = s.id
    LEFT JOIN users u ON c.teacher_id = u.id AND u.role = 'teacher'
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$classInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$classInfo) {
    die('Class not found');
}

// Update the teacher_id for the class if it's not set
if (!$classInfo['teacher_id']) {
    $updateStmt = $pdo->prepare("
        UPDATE classes 
        SET teacher_id = (SELECT id FROM users WHERE role = 'teacher' LIMIT 1)
        WHERE id = ?
    ");
    $updateStmt->execute([$class_id]);
}

// Fetch attendance records
$stmt = $pdo->prepare("
    SELECT 
        students.name AS student_name,
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
        AND attendance.class_id = ?
    WHERE students.section_id = ?
    ORDER BY students.name ASC
");
$stmt->execute([$class_id, $classInfo['section_id']]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>
    <style>
        @media print {
            body {
                padding: 20px;
                font-family: Arial, sans-serif;
            }
            .no-print {
                display: none;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid black;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
            }
            .class-info {
                margin-bottom: 20px;
            }
            .class-info p {
                margin: 5px 0;
            }
        }

        /* Styles for screen view */
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .print-button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .class-info {
            margin-bottom: 20px;
        }
        .class-info p {
            margin: 5px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">Print Report</button>
    
    <div class="header">
        <h2>UNIVERSITY OF NEGROS OCCIDENTAL – RECOLETOS, INCORPORATED</h2>
    </div>

    <div class="class-info">
        <p>Teacher: <?php echo htmlspecialchars($classInfo['teacher_name']); ?></p>
        <p>Section: <?php echo htmlspecialchars($classInfo['section_name']); ?></p>
        <p>Period Covered: <?php 
            $start_date = date('F d, Y', strtotime($classInfo['period_start']));
            $end_date = date('F d, Y', strtotime($classInfo['period_end']));
            echo "$start_date - $end_date"; 
        ?></p>
        <p>Class Schedule: <?php echo htmlspecialchars($classInfo['schedule']); ?> | 
           Time: <?php 
               $time_start = date('h:i A', strtotime($classInfo['time_start']));
               $time_end = date('h:i A', strtotime($classInfo['time_end']));
               echo "$time_start - $time_end"; 
           ?> | 
           Room No.: <?php echo htmlspecialchars($classInfo['room_number']); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $record): ?>
            <tr>
                <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                <td><?php echo htmlspecialchars($record['time_in'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($record['time_out'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($record['status']); ?></td>
                <td><?php echo htmlspecialchars($record['remarks'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        // Automatically open print dialog when page loads
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html> 