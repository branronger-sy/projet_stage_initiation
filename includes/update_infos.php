<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db.php';

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_info') {

    function sanitize($value, $maxLen = 255) {
        $value = trim((string)$value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return mb_substr($value, 0, $maxLen);
    }

    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $new_password = !empty($_POST['new_password']) 
        ? password_hash($_POST['new_password'], PASSWORD_BCRYPT) 
        : null;
    $s_full_name = sanitize($_POST['s_full_name'] ?? '');
    $s_address   = sanitize($_POST['s_address'] ?? '', 500);
    $s_city      = sanitize($_POST['s_city'] ?? '');
    $s_zip_code  = sanitize($_POST['s_zip_code'] ?? '');
    $s_phone     = sanitize($_POST['s_phone'] ?? '');
    $b_full_name = sanitize($_POST['b_full_name'] ?? '');
    $b_address   = sanitize($_POST['b_address'] ?? '', 500);
    $b_city      = sanitize($_POST['b_city'] ?? '');
    $b_zip_code  = sanitize($_POST['b_zip_code'] ?? '');
    $b_phone     = sanitize($_POST['b_phone'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email address"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE users SET full_name=?, email=? " . ($new_password ? ", password_hash=? " : "") . " WHERE id=?";
        $params = [$full_name, $email];
        if ($new_password) $params[] = $new_password;
        $params[] = $user_id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $stmt = $pdo->prepare("SELECT id FROM shipping_addresses WHERE user_id=? LIMIT 1");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE shipping_addresses SET full_name=?, address=?, city=?, zip_code=?, phone=? WHERE user_id=?");
            $stmt->execute([$s_full_name, $s_address, $s_city, $s_zip_code, $s_phone, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO shipping_addresses (user_id, full_name, address, city, zip_code, phone) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$user_id, $s_full_name, $s_address, $s_city, $s_zip_code, $s_phone]);
        }

        $stmt = $pdo->prepare("SELECT id FROM billing_addresses WHERE user_id=? LIMIT 1");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE billing_addresses SET full_name=?, address=?, city=?, zip_code=?, phone=? WHERE user_id=?");
            $stmt->execute([$b_full_name, $b_address, $b_city, $b_zip_code, $b_phone, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO billing_addresses (user_id, full_name, address, city, zip_code, phone) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$user_id, $b_full_name, $b_address, $b_city, $b_zip_code, $b_phone]);
        }

        $pdo->commit();

        echo json_encode(["status" => "success", "message" => "All information updated successfully"]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("update_infos.php error: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "Something went wrong, please try again later"]);
    }

    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid request"]);
exit;
