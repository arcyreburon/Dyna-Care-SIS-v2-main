<?php
include '../db_conn.php';
header('Content-Type: application/json');

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

$debug = [
    'received_category_id' => $category_id,
    'received_branch_id' => $branch_id,
    'products' => [],
    'sql' => '',
    'error' => ''
];

if (!$category_id || !$branch_id) {
    $debug['error'] = 'Missing category_id or branch_id';
    echo json_encode($debug);
    exit;
}

// Allow multiple inventory records per product per branch
$sql = "SELECT p.id, p.product_name
        FROM products p
        WHERE p.categories_id = ? AND p.branches_id = ?";
$debug['sql'] = $sql;
$stmt = $con->prepare($sql);
if (!$stmt) {
    $debug['error'] = $con->error;
    echo json_encode($debug);
    exit;
}
$stmt->bind_param("ii", $category_id, $branch_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $debug['products'][] = $row;
}
echo json_encode($debug); 