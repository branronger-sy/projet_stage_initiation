<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

require 'db.php';

header('Content-Type: application/json');

$user_id = (int) $_SESSION['user_id'];


$shipping_fullName = trim($_POST['shipping_fullName'] ?? '');
$shipping_address  = trim($_POST['shipping_address'] ?? '');
$shipping_city     = trim($_POST['shipping_city'] ?? '');
$shipping_zip      = trim($_POST['shipping_zip'] ?? '');
$shipping_country  = (int) ($_POST['shipping_country'] ?? 0);
$shipping_phone    = trim($_POST['shipping_phone'] ?? '');

if (isset($_POST['same_as_shipping'])) {
    $billing_fullName = $shipping_fullName;
    $billing_address  = $shipping_address;
    $billing_city     = $shipping_city;
    $billing_zip      = $shipping_zip;
    $billing_country  = $shipping_country;
    $billing_phone    = $shipping_phone;
} else {
    $billing_fullName = trim($_POST['billing_fullName'] ?? '');
    $billing_address  = trim($_POST['billing_address'] ?? '');
    $billing_city     = trim($_POST['billing_city'] ?? '');
    $billing_zip      = trim($_POST['billing_zip'] ?? '');
    $billing_country  = (int) ($_POST['billing_country'] ?? 0);
    $billing_phone    = trim($_POST['billing_phone'] ?? '');
}

$requiredShipping = [
    "Full Name" => $shipping_fullName,
    "Address"   => $shipping_address,
    "City"      => $shipping_city,
    "Zip Code"  => $shipping_zip,
    "Country"   => $shipping_country,
    "Phone"     => $shipping_phone
];

foreach ($requiredShipping as $field => $value) {
    if (empty($value)) {
        echo json_encode(["status" => "error", "message" => "Shipping $field is required"]);
        exit;
    }
}

if (!isset($_POST['same_as_shipping'])) {
    $requiredBilling = [
        "Full Name" => $billing_fullName,
        "Address"   => $billing_address,
        "City"      => $billing_city,
        "Zip Code"  => $billing_zip,
        "Country"   => $billing_country,
        "Phone"     => $billing_phone
    ];

    foreach ($requiredBilling as $field => $value) {
        if (empty($value)) {
            echo json_encode(["status" => "error", "message" => "Billing $field is required"]);
            exit;
        }
    }
}

    $stmt = $pdo->prepare("SELECT 1 FROM shipping_addresses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $hasShipping = $stmt->fetchColumn();

    if ($hasShipping) {
        $stmt = $pdo->prepare("UPDATE shipping_addresses 
            SET full_name=?, address=?, city=?, zip_code=?, country_id=?, phone=? 
            WHERE user_id=?");
        $stmt->execute([
            $shipping_fullName, $shipping_address, $shipping_city, 
            $shipping_zip, $shipping_country, $shipping_phone, $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO shipping_addresses 
            (user_id, full_name, address, city, zip_code, country_id, phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id, $shipping_fullName, $shipping_address, $shipping_city, 
            $shipping_zip, $shipping_country, $shipping_phone
        ]);
    }

    $stmt = $pdo->prepare("SELECT 1 FROM billing_addresses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $hasBilling = $stmt->fetchColumn();

    if ($hasBilling) {
        $stmt = $pdo->prepare("UPDATE billing_addresses 
            SET full_name=?, address=?, city=?, zip_code=?, country_id=?, phone=? 
            WHERE user_id=?");
        $stmt->execute([
            $billing_fullName, $billing_address, $billing_city, 
            $billing_zip, $billing_country, $billing_phone, $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO billing_addresses 
            (user_id, full_name, address, city, zip_code, country_id, phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id, $billing_fullName, $billing_address, $billing_city, 
            $billing_zip, $billing_country, $billing_phone
        ]);
    }
    $_SESSION['checkout_progress']['address'] = true;

    echo json_encode([
        "status"   => "success",
        "redirect" => "index.php?page=shipping"
    ]);
    exit;

