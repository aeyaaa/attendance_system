<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $scanned_id = $_POST['id'];

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'teacher'");
    $stmt->execute(['id' => $scanned_id]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE teacher_id = :teacher_id AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1");
        $stmt->execute(['teacher_id' => $scanned_id]);
        $attendance = $stmt->fetch();

        if ($attendance) {
            $stmt = $pdo->prepare("UPDATE attendance SET logout_time = NOW() WHERE id = :attendance_id");
            $stmt->execute(['attendance_id' => $attendance['id']]);
            echo json_encode(["status" => "success", "message" => "Logout recorded"]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (teacher_id, login_time) VALUES (:teacher_id, NOW())");
            $stmt->execute(['teacher_id' => $scanned_id]);
            echo json_encode(["status" => "success", "message" => "Login recorded"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    }
}
?>
