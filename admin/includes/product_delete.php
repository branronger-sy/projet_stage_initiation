<?php
session_start();
require "auth.php";
require "db.php";
if (!isset($_GET['id'])) {
    header("Location: ../products.php?error=invalid_id");
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

while ($img = $res->fetch_assoc()) {
    $filePath = "../public/" . $img['image_url'];
    if (is_file($filePath)) {
        unlink($filePath); 
    }
}
$stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt = $conn->prepare("DELETE FROM product_variants WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    header("Location: ../products.php?error=not_found");
    exit;
}

$stmt->close();

header("Location: ../products.php?success=deleted");
exit;
?>
