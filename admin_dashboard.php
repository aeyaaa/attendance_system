<?php
session_start();
require './db.php'; // Include the database connection file

// Check if there's a success message in the session
if (isset($_SESSION['success_message'])) {
    echo "<div class='success-message'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']); // Clear the message after it's displayed
}

// Query to get all users
$sql = "SELECT id, username, role, name, plain_password FROM users"; // Adjust the columns as needed
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll();

// Query to get all sections
$sql_sections = "SELECT id, section_name, total_students FROM sections"; 
$stmt_sections = $pdo->prepare($sql_sections);
$stmt_sections->execute();
$sections = $stmt_sections->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="./css/admin_dashh.css">
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
                <a href="admin_dashboard.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/admin_dashboard.php') ? 'active' : ''; ?>">
                    <span class="material-icons">dashboard</span> Dashboard
                </a>
            </li>
            <li>
                <a href="record.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/admin1/record.php') ? 'active' : ''; ?>">
                    <span class="material-icons">library_books</span> Records
                </a>
            </li>
            <li>
                <a href="account.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/admin1/account.php') ? 'active' : ''; ?>">
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
                <h1>ADMIN</h1>
                <!-- Buttons to trigger modals -->
                <button class="select-section" id="addTeacherBtn">ADD TEACHER</button>
                <button class="select-section" id="addSectionBtn">ADD SECTION</button>
            </div>
        </div>

        <!-- Table to Display Users -->
        <div class="table-container">
            <h2>Users List</h2>
            <table border="1">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['plain_password']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

 <!-- Table to Display Sections -->
<div class="table-container">
    <h2>Sections List</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Section Name</th>
                <th>Total Students</th>
                <th>Student List</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sections as $section): ?>
                <tr>
                    <td><?php echo htmlspecialchars($section['id']); ?></td>
                    <td><?php echo htmlspecialchars($section['section_name']); ?></td>
                    <td><?php echo htmlspecialchars($section['total_students']); ?></td>
                    <td>
                        <button class="view-students" data-section-id="<?php echo $section['id']; ?>">View Students</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


    <!-- Add Teacher Modal -->
    <div id="addTeacherModal" class="modal">
        <div class="modal-content">
            <h4>Add Teacher Account</h4>
            <form action="./admin1/add_teacher.php" method="POST">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required><br><br>

                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required><br><br>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br><br>

                <input type="submit" value="Add Teacher">
                <button type="button" class="close-modal">Close</button>
            </form>
        </div>
    </div>

    <!-- Add Section Modal -->
    <div id="addSectionModal" class="modal">
        <div class="modal-content">
            <h4>Add Section</h4>
            <form action="./admin1/add_section.php" method="POST" id="addSectionForm">
                <label for="course">Course:</label>
                <input type="text" id="course" name="course" required><br><br>

                <label for="section_name">Section Name:</label>
                <input type="text" id="section_name" name="section_name" required><br><br>

                <label for="total_students">Total Students:</label>
                <input type="number" id="total_students" name="total_students" required><br><br>

                <div id="student_inputs">
                    <!-- Student inputs will be dynamically generated here -->
                </div>

                <input type="submit" value="Save Section">
                <button type="button" class="close-modal">Close</button>
            </form>
        </div>
    </div>

    <!-- Student List Modal -->
    <div id="studentListModal" class="modal">
        <div class="modal-content">
            <h4>Student List</h4>
            <div id="studentListContainer"></div>
            <button type="button" class="close-modal">Close</button>
        </div>
    </div>

    <script>
    // Open the "Add Teacher" modal
    document.getElementById('addTeacherBtn').addEventListener('click', function() {
        document.getElementById('addTeacherModal').style.display = 'block';
        document.getElementById('addSectionModal').style.display = 'none'; // Hide "Add Section" modal if open
    });

    // Open the "Add Section" modal
    document.getElementById('addSectionBtn').addEventListener('click', function() {
        document.getElementById('addSectionModal').style.display = 'block';
        document.getElementById('addTeacherModal').style.display = 'none'; // Hide "Add Teacher" modal if open
    });

    // Close the modals
    document.querySelectorAll('.close-modal').forEach(function(button) {
        button.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
    // Handle "View Students" button clicks
// Open the "View Students" modal and fetch student data
document.querySelectorAll('.view-students').forEach(function(button) {
    button.addEventListener('click', function() {
        const sectionId = this.getAttribute('data-section-id');

        fetch(`./admin1/fetch_students.php?section_id=${sectionId}`)
            .then(response => response.json())
            .then(data => {
                console.log(data);
                const studentListContainer = document.getElementById('studentListContainer');
                studentListContainer.innerHTML = ''; // Clear previous data

                // Create table for student data
                if (data.length > 0) {
                    const table = document.createElement('table');
                    table.innerHTML = `
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>RFID Tag</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(student => `
                                <tr>
                                    <td>${student.name}</td>
                                    <td>${student.student_uid || 'No RFID Tag'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    `;
                    studentListContainer.appendChild(table);
                } else {
                    studentListContainer.innerHTML = '<p>No students found in this section.</p>';
                }

                document.getElementById('studentListModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching students:', error);
            });
    });
});

// Close the student list modal
document.querySelector('#studentListModal .close-modal').addEventListener('click', function() {
    document.getElementById('studentListModal').style.display = 'none';
});
//

// Close the modal when clicking "Close"
document.querySelectorAll('.close-modal').forEach(function(button) {
    button.addEventListener('click', function() {
        this.closest('.modal').style.display = 'none';
    });
});


    // Generate student name input fields dynamically based on the "Total Students" input
    document.getElementById('total_students').addEventListener('input', function() {
        const totalStudents = parseInt(this.value) || 0;
        const studentInputsDiv = document.getElementById('student_inputs');
        studentInputsDiv.innerHTML = '';

        for (let i = 1; i <= totalStudents; i++) {
            const container = document.createElement('div');
            container.className = 'student-container';

            // Create the HTML structure
            container.innerHTML = `
                <div class="student-row">
                    <div class="student-info">
                        <label>Student ${i} Name:</label>
                        <input type="text" name="student_names[]" required>
                    </div>
                    <div class="rfid-info">
                        <button type="button" class="scan-rfid-btn" onclick="startRFIDScan(${i})">Scan RFID</button>
                        <span id="scan_status_${i}" class="scan-status"></span>
                        <input type="hidden" name="rfid_tags[]" id="rfid_tag_${i}">
                    </div>
                </div>
            `;

            studentInputsDiv.appendChild(container);
        }
    });

    // Function to handle RFID scanning
    function startRFIDScan(studentNumber) {
        const statusSpan = document.getElementById(`scan_status_${studentNumber}`);
        const rfidInput = document.getElementById(`rfid_tag_${studentNumber}`);
        
        statusSpan.textContent = 'Scanning... Please tap card';
        statusSpan.style.color = 'blue';
        
        // Start scanning mode
        fetch('http://192.168.254.133/start_scan')
            .then(response => response.json())
            .then(() => {
                console.log('Scanning started');
                pollForCard();
            })
            .catch(error => {
                console.error('Error starting scan:', error);
                statusSpan.textContent = '✗ Error starting scan';
                statusSpan.style.color = 'red';
            });
        
        function pollForCard() {
            fetch('http://192.168.254.133/check_card')
                .then(response => response.json())
                .then(data => {
                    console.log('Poll response:', data); // Debug log
                    
                    if (data.success && data.rfid_tag) {
                        // Card detected
                        rfidInput.value = data.rfid_tag;
                        statusSpan.textContent = '✓ Tag: ' + data.rfid_tag;
                        statusSpan.style.color = 'green';
                    } else if (data.status === "scanning") {
                        // Still scanning, continue polling
                        setTimeout(pollForCard, 500); // Poll every 500ms
                    } else {
                        setTimeout(pollForCard, 500);
                    }
                })
                .catch(error => {
                    console.error('Error checking card:', error);
                    statusSpan.textContent = '✗ Error checking card';
                    statusSpan.style.color = 'red';
                });
        }
    }
</script>

<style>
.student-container {
    margin-bottom: 20px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.student-row {
    display: flex;
    align-items: center;
    gap: 20px;
}

.student-info {
    flex: 1;
}

.student-info label {
    display: block;
    margin-bottom: 5px;
}

.student-info input {
    width: 100%;
    padding: 5px;
}

.rfid-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.scan-rfid-btn {
    padding: 8px 15px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.scan-rfid-btn:hover {
    background-color: #45a049;
}

.scan-status {
    min-width: 200px;
    display: inline-block;
    font-size: 14px;
}

#studentListContainer table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

#studentListContainer th,
#studentListContainer td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

#studentListContainer th {
    background-color: #f4f4f4;
}

#studentListContainer tr:nth-child(even) {
    background-color: #f9f9f9;
}

#studentListContainer tr:hover {
    background-color: #f5f5f5;
}
</style>
