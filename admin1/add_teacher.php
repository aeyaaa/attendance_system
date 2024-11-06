<?php
session_start();
require '../db.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];

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
        $sql = "INSERT INTO users (name, username, password, plain_password, role) VALUES (:name, :username, :password, :plain_password, 'teacher')";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':plain_password', $password); // Store plain password as entered by the user

        try {
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Teacher account added successfully!";
                header("Location: /attendance_system/admin_dashboard.php"); // Redirect to admin dashboard
                exit();
            } else {
                echo "Error: Could not add the teacher account.";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
