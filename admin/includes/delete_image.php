<?php
include "auth.php";
include "db.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit("Invalid image ID");
}
$stmt = $conn->prepare("SELECT image_url, product_id FROM product_images WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) { http_response_code(404); exit("Image not found"); }

$imagePath = __DIR__ . "/../public/" . $res['image_url'];
$productId = (int)$res['product_id'];

$stmt = $conn->prepare("DELETE FROM product_images WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();
if (is_file($imagePath)) { unlink($imagePath); }
header("Location: ../product_edit.php?id=" . $productId);
exit;
