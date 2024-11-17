<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

try {
    // This is where you'd implement your RFID reading logic
    // For now, we'll simulate it with a database check
    $stmt = $pdo->prepare("
        SELECT tag_id 
        FROM rfid_queue 
        WHERE processed = 0 
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
    $stmt->execute();
    
    if ($tag = $stmt->fetch()) {
        // Mark as processed
        $updateStmt = $pdo->prepare("
            UPDATE rfid_queue 
            SET processed = 1 
            WHERE tag_id = ?
        ");
        $updateStmt->execute([$tag['tag_id']]);
        
        echo json_encode(['tag_id' => $tag['tag_id']]);
    } else {
        echo json_encode(['tag_id' => null]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 