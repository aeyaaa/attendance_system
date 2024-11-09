<?php
session_start();
require './db.php'; // Include the database connection file

// Fetch attendance records from the 'attendance' table, joining with 'students' table to get the student name
$sql = "
    SELECT students.name AS student_name, attendance.time_in, attendance.time_out, attendance.remarks 
    FROM attendance
    JOIN students ON attendance.student_id = students.id
";
$stmt = $pdo->query($sql);
$attendanceRecords = $stmt->fetchAll();

// Fetch sections from the 'sections' table to populate the 'Add Class' modal form
$sectionSql = "SELECT id, section_name FROM sections";
$sectionStmt = $pdo->query($sectionSql);
$sections = $sectionStmt->fetchAll();

// Fetch all classes from the 'classes' table to populate the 'Select Class' dropdown
$classSql = "
    SELECT classes.id, sections.section_name 
    FROM classes
    JOIN sections ON classes.section_id = sections.id
";
$classStmt = $pdo->query($classSql);
$classes = $classStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="./css/tdashh.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            padding-top: 100px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
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
                <button class="select-section" onclick="openModal()">ADD CLASS</button>
                
                <!-- Class Selection Dropdown -->
                <div class="class-selection">
                    <label for="select_class">Select Class:</label>
                    <select id="select_class">
                        <option value="">Select a class...</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['section_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- End of Class Selection Dropdown -->
            </div>

            <!-- Class Info Section -->
            <div class="class-info">
                <p>Section: <span id="class-section">N/A</span></p>
                <p>Period Covered: <span id="class-period-covered">N/A</span></p>
                <p>Class Schedule: <span id="class-schedule">N/A</span> | Time: <span id="class-time">N/A</span> | Room No.: <span id="class-room-number">N/A</span></p>
            </div>
            
        </div>
        
        <!-- STUDENT attendance -->
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

        <!-- Modal for Adding Class -->
        <div id="addClassModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Add Class</h2>
                <form action="./teacher/add_class.php" method="post">
                    <label for="section">Section:</label>
                    <select name="section_id" id="section" required>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section['id']; ?>"><?php echo htmlspecialchars($section['section_name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="period_start">Period Start:</label>
                    <input type="date" name="period_start" required>

                    <label for="period_end">Period End:</label>
                    <input type="date" name="period_end" required>

                    <label for="schedule">Class Schedule:</label>
                    <select name="schedule" required>
                        <option value="M">Monday</option>
                        <option value="T">Tuesday</option>
                        <option value="W">Wednesday</option>
                        <option value="TH">Thursday</option>
                        <option value="F">Friday</option>
                        <option value="S">Saturday</option>
                    </select>

                    <label for="room_number">Room No.:</label>
                    <input type="text" name="room_number" required>

                    <button type="submit">Add Class</button>
                </form>
            </div>
        </div>

        <script>
            // Modal functionality
            function openModal() {
                document.getElementById("addClassModal").style.display = "block";
            }

            function closeModal() {
                document.getElementById("addClassModal").style.display = "none";
            }

            window.onclick = function(event) {
                const modal = document.getElementById("addClassModal");
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            }

            // Fetch class info when a class is selected
document.getElementById("select_class").addEventListener("change", function() {
        const classId = this.value;
        
        if (classId) {
            fetch(`./teacher/fetch_class_info.php?class_id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    console.log("Class Info Response:", data);
                    if (data.error) {
                        console.error("Error:", data.error);
                        alert("Unable to fetch class info. Please try again.");
                        return;
                    }

                    // Update class info display
                    document.getElementById("class-section").innerText = data.section_name || 'N/A';
                    document.getElementById("class-period-covered").innerText = 
                        `${data.period_start || 'N/A'} - ${data.period_end || 'N/A'}`;
                    document.getElementById("class-schedule").innerText = data.schedule || 'N/A';
                    document.getElementById("class-time").innerText = data.class_time || 'N/A';
                    document.getElementById("class-room-number").innerText = data.room_number || 'N/A';

                    // Update student list
                    const studentTable = document.querySelector('table');  // Assuming the table is used for displaying student records
                    const studentRows = data.students.map(student => {
                        return `<tr>
                            <td>${student.name}</td>
                            <td>-</td> <!-- You can add other student-specific data here -->
                            <td>-</td> <!-- You can add other student-specific data here -->
                            <td>-</td> <!-- You can add other student-specific data here -->
                        </tr>`;
                    }).join('');
                    studentTable.innerHTML = `
                        <tr>
                            <th>Student’s Name</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Remarks</th>
                        </tr>
                        ${studentRows}
                    `;
                })
                .catch(error => {
                    console.error("Error fetching class info:", error);
                    alert("There was an error fetching the class information.");
                });
        } else {
            // Reset class info and student table
            document.getElementById("class-section").innerText = 'N/A';
            document.getElementById("class-period-covered").innerText = 'N/A';
            document.getElementById("class-schedule").innerText = 'N/A';
            document.getElementById("class-time").innerText = 'N/A';
            document.getElementById("class-room-number").innerText = 'N/A';

            // Clear student table
            const studentTable = document.querySelector('table');
            studentTable.innerHTML = `
                <tr>
                    <th>Student’s Name</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Remarks</th>
                </tr>
            `;
        }
    });
</script>
    </div>
</body>
</html>
