<?php
session_start();
require 'db.php';

$ip_address = $_SERVER['REMOTE_ADDR'];
if ($ip_address === '::1') {
    $ip_address = '127.0.0.1';
}

$max_attempts = 5;
$lockout_time = 900; 
$current_time = date("Y-m-d H:i:s");

$stmt = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$attempts = $row ? (int)$row['attempts'] : 0;
$last_attempt = $row ? strtotime($row['last_attempt']) : 0;
$is_locked = ($attempts >= $max_attempts && (time() - $last_attempt) < $lockout_time);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($is_locked) {
        $remaining = $lockout_time - (time() - $last_attempt);
        $_SESSION['lockout_seconds'] = $remaining;

        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $formatted_time = ($minutes > 0 ? "{$minutes}m " : "") . "{$seconds}s";

        $_SESSION['login_error'] = "Trop de tentatives échouées. Réessayez après {$formatted_time}.";
        header("Location: ../login.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        if ($row) {
            $stmt = $conn->prepare("UPDATE login_attempts SET attempts = 0, last_attempt = ? WHERE ip_address = ?");
            $stmt->bind_param("ss", $current_time, $ip_address);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 0, ?)");
            $stmt->bind_param("ss", $ip_address, $current_time);
            $stmt->execute();
        }

        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];

        header("Location: ../dashboard.php");
        exit();
    } 
    else {
        if ($row) {
            $stmt = $conn->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = ? WHERE ip_address = ?");
            $stmt->bind_param("ss", $current_time, $ip_address);
            $stmt->execute();
            $attempts++;
        } else {
            $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, ?)");
            $stmt->bind_param("ss", $ip_address, $current_time);
            $stmt->execute();
            $attempts = 1;
        }

        if ($attempts >= $max_attempts) {
            $_SESSION['lockout_seconds'] = $lockout_time;
            $_SESSION['login_error'] = "Trop de tentatives échouées. Réessayez après " .
                                       floor($lockout_time / 60) . "m " . ($lockout_time % 60) . "s.";
        } else {
            $_SESSION['login_error'] = "Identifiants invalides.";
        }

        header("Location: ../login.php");
        exit();
    }
}
?>
