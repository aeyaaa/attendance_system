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
                <button class="select-section">ADD STUDENT</button>
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
                        <th>Student List</th> <!-- Added column for Student List -->
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
                <label for="section_name">Section Name:</label>
                <input type="text" id="section_name" name="section_name" required><br><br>

                <label for="total_students">Total Students:</label>
                <input type="number" id="total_students" name="total_students" required><br><br>

                <div id="student_inputs"></div>

                <input type="submit" value="Add Section">
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

    // Generate student name input fields dynamically based on the "Total Students" input
    document.getElementById('total_students').addEventListener('input', function() {
        const totalStudents = parseInt(this.value) || 0;
        const studentInputsDiv = document.getElementById('student_inputs');
        studentInputsDiv.innerHTML = ''; // Clear any existing inputs

        for (let i = 1; i <= totalStudents; i++) {
            // Create a label for each student input
            const label = document.createElement('label');
            label.textContent = `Student ${i} Name:`;

            // Create an input field for each student name
            const input = document.createElement('input');
            input.type = 'text';
            input.name = `student_names[]`; // Use an array format to capture all student names
            input.placeholder = `Enter Student ${i} Name`;
            input.required = true;

            // Append the label and input field to the student inputs div
            studentInputsDiv.appendChild(label);
            studentInputsDiv.appendChild(input);
            studentInputsDiv.appendChild(document.createElement('br'));
        }
    });
</script>

</body>
</html>
