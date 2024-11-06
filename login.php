<?php
session_start();
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    require './db.php'; // Include your database connection file

    // Prepare SQL to get the user by username
    $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // If the password matches, create session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on the role (admin or teacher)
        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: teacher_dashboard.php");
        }
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>
<body>
    <div class="container">
        <div class="left-side">
            <div class="logo-container">
                <img src="./assets/images/logo.png" alt="Logo" class="logo">
                <p></p>
            </div>
        </div>
        <div class="right-side">
            <div class="login-container">
                <h1>User Login</h1>
                <form method="post" action="">
                    <div class="input-group">
                        <i class="material-icons">person</i>
                        <input type="text" name="username" required placeholder="Username">
                    </div>
                    <div class="input-group">
                        <i class="material-icons">lock</i>
                        <input type="password" name="password" required placeholder="Password">
                    </div>

                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember Me</label>
                    </div>
                    <input type="submit" value="Login" class="login-btn">
                </form>
                <div class="error"><?php echo $error; ?></div>
            </div>
        </div>
    </div>
    
</body>
</html>
