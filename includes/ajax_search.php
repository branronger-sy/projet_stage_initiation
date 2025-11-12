<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

header('Content-Type: application/json; charset=utf-8');

$term = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
if ($term === '') {
    echo json_encode([]);
    exit;
}

$like = '%' . $term . '%';

$sql = "
SELECT DISTINCT p.id,
       COALESCE(v.var_name_en, p.name_en) AS name_en,
       COALESCE(v.var_name_fr, p.name_fr) AS name_fr
FROM products p
LEFT JOIN product_variants v ON p.id = v.product_id
WHERE p.name_en LIKE :term1
   OR p.name_fr LIKE :term2
   OR p.description_en LIKE :term3
   OR p.description_fr LIKE :term4
   OR v.var_name_en LIKE :term5
   OR v.var_name_fr LIKE :term6
LIMIT 10
";
    $stmt = $pdo->prepare($sql);
    for ($i=1; $i<=6; $i++) {
        $stmt->bindValue(':term'.$i, $like, PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    $lang = $_SESSION['lang'] ?? 'en';
    foreach ($rows as $r) {
        $id = (int)($r['id'] ?? 0);
        $name = ($lang === 'fr') ? ($r['name_fr'] ?? $r['name_en']) : ($r['name_en'] ?? $r['name_fr']);
        $results[] = ['id' => $id, 'name' => $name];
    }

    echo json_encode($results, JSON_UNESCAPED_UNICODE);

