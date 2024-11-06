<?php
session_start();
require '../db.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $name = $_POST['name']; // Added name field

    // Check if the username already exists in the database
    $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch();

    // If username exists, show an error message
    if ($user) {
        echo "Username already exists. Please choose another one.";
    } else {
        // Hash the password for security
        $passwordHash = password_hash($password, PASSWORD_DEFAULT); 

        // Prepare the SQL insert query to include plain_password
        $sql = "INSERT INTO users (username, password, plain_password, role, name) VALUES (:username, :password, :plain_password, 'teacher', :name)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':plain_password', $password); // Store plain password as entered by the user

        try {
            if ($stmt->execute()) {
                // Redirect with a success message
                $_SESSION['success_message'] = "Teacher account added successfully!";
                header("Location: /attendance_system/admin_dashboard.php");
                exit();
            } else {
                echo "Error: Could not add the teacher account.";
            }
        } catch (Exception $e) {
            // Display error message if something goes wrong with the SQL
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
