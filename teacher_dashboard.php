<?php
session_start();
require './db.php'; // Include the database connection file

// Fetch attendance records from the 'attendance' table
$sql = "SELECT students.name, attendance.date, attendance.time_in, attendance.time_out, attendance.remarks 
        FROM attendance 
        JOIN students ON attendance.student_id = students.id";
$stmt = $pdo->query($sql);

// Store records in an array
$attendanceRecords = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="./css/tdashh.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

</head>
<body>
    <div class="sidebar">
        <div class="name">
            <img src="./assets/images/logo.png" alt="Logo" class="logo">
            <text> UNIVERSITY OF NEGROS OCCIDENTAL – RECOLETOS, INCORPORATED</text>
            <hr>
        </div>
        <ul>
            <li>
                <a href="teacher_dashboard.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/teacher_dashboard.php') ? 'active' : ''; ?>">
                    <span class="material-icons">dashboard</span> Dashboard
                </a>
            </li>
            <li>
                <a href="record.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/teacher/record.php') ? 'active' : ''; ?>">
                    <span class="material-icons">library_books</span> Records
                </a>
            </li>
            <li>
                <a href="account.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/teacher/account.php') ? 'active' : ''; ?>">
                    <span class="material-icons">account_circle</span> Account
                </a>
            </li>
        </ul>

        <form action="logout.php" method="post" class="logout-form">
            <input type="submit" value="Logout">
        </form>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="welcome">
                <h1>WELCOME MR. CRUZ</h1>
                <button class="select-section">SELECT CLASS</button>
            </div>
            <div class="class-info">
                <p>Course: REEDCS101</p>
                <p>Section: C01</p>
                <p>Period Covered: Nov. 1-7, 2024</p>
                <p>Class Schedule: MWF | Time: 7:30AM - 8:30AM | Room No.: LC01</p>
            </div>
        </div>
        <div class="table-container">
            <input type="text" placeholder="Search..." class="search-bar">
            <table>
                <tr>
                    <th>Student’s Name</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Remarks</th>
                </tr>
                <?php foreach ($attendanceRecords as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($record['time_in']); ?></td>
                    <td><?php echo htmlspecialchars($record['time_out']); ?></td>
                    <td><?php echo htmlspecialchars($record['remarks']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <button class="print-button">PRINT</button>
        </div>
    </div>
</body>
</html>
