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
                <!-- Button to trigger modal -->
                <button class="select-section" id="addTeacherBtn">ADD TEACHER</button>
                <button class="select-section">ADD SECTION</button>
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
    </div>

    <!-- Modal Structure -->
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

    <script>
    // Open the modal when the "Add Teacher" button is clicked
    document.getElementById('addTeacherBtn').addEventListener('click', function() {
        document.getElementById('addTeacherModal').style.display = 'block';
    });

    // Close the modal when the close button is clicked
    document.querySelector('.close-modal').addEventListener('click', function() {
        document.getElementById('addTeacherModal').style.display = 'none';
    });

    // Check if there is a success message and display it in the success modal
    <?php if (isset($_SESSION['success_message'])): ?>
        document.getElementById('successMessageContent').textContent = "<?php echo $_SESSION['success_message']; ?>";
        document.getElementById('successMessageModal').style.display = 'block';
        // Clear success message after displaying it
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    // Close the success message modal when the close button is clicked
    document.querySelector('#successMessageModal .close-modal').addEventListener('click', function() {
        document.getElementById('successMessageModal').style.display = 'none';
    });
</script>

</body>
</html>
