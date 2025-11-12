<?php
session_start();

require 'db.php'; 


$userId = (int)$_SESSION['user_id'];


    $stmt = $pdo->prepare('DELETE FROM favoris WHERE user_id = ?');
    $stmt->execute([$userId]);
    $deleted = $stmt->rowCount();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'deleted' => $deleted
    ]);
    exit;
