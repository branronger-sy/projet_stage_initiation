<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require '../includes/db.php';
require '../includes/currency.php';



if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (($_POST['action'] ?? '') === 'add') {

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $variant_id = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int) $_POST['variant_id'] : null;
    $quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
    

        if ($id <= 0 || $quantity <= 0) {
            echo json_encode(['status' => 'invalid_input']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                COALESCE(v.var_name_en, p.name_en) AS name_en, 
                COALESCE(v.price, p.price) AS price, 
                COALESCE(vi.image_url, i.image_url, 'images/default.png') AS image_url,
                COALESCE(v.bottle_type, '') AS type,
                v.id AS variant_id
            FROM products p
            LEFT JOIN product_variants v 
                ON p.id = v.product_id AND (v.id = ?)
            LEFT JOIN product_images vi 
                ON vi.variant_id = v.id
            LEFT JOIN product_images i 
                ON p.id = i.product_id AND i.is_main = 1
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$variant_id, $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $product['id'] && $item['variant_id'] == $product['variant_id']) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            unset($item);

            if (!$found) {
                $_SESSION['cart'][] = [
                    'id'        => (int)$product['id'],
                    'variant_id'=> $product['variant_id'] ? (int)$product['variant_id'] : null,
                    'name'      => $product['name_en'],
                    'price'     => (float)$product['price'],
                    'image'     => $product['image_url'],
                    'type'      => $product['type'],
                    'quantity'  => $quantity
                ];
            }

            echo json_encode(['status' => 'added']);
        } else {
            echo json_encode(['status' => 'not_found']);
        }
        exit;
}

if (($_POST['action'] ?? '') === 'remove') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $variant_id = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int) $_POST['variant_id'] : null;    

        if ($id <= 0) {
            echo json_encode(['status' => 'invalid_input']);
            exit;
        }

        $removed = false;
        foreach ($_SESSION['cart'] as $k => $item) {
            if ($item['id'] == $id && $item['variant_id'] == $variant_id) {
                unset($_SESSION['cart'][$k]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                $removed = true;
                break;
            }
        }

        echo json_encode(['status' => $removed ? 'removed' : 'not_found']);
        exit;
}

if (($_GET['action'] ?? '') === 'get') {
        $convertedCart = [];
        $total = 0;

        foreach ($_SESSION['cart'] as $item) {
            $convertedPrice = convertPrice($item['price']);
            $lineTotal      = $convertedPrice * $item['quantity'];
            $total         += $lineTotal;

            $convertedCart[] = [
                'id'        => $item['id'],
                'variant_id'=> $item['variant_id'],
                'name'      => $item['name'],
                'price'     => $convertedPrice,
                'image'     => $item['image'],
                'type'      => $item['type'],
                'quantity'  => $item['quantity'],
                'line_total'=> round($lineTotal, 2),
            ];
        }

        echo json_encode([
            'cart'     => $convertedCart,
            'total'    => round($total, 2),
            'currency' => $_SESSION['currency'] ?? 'MAD'
        ]);
        exit;
}

echo json_encode(['status' => 'invalid_action']);
exit;
