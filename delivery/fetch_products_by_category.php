<?php
include '../db_conn.php';
session_start();

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$branch_id = isset($_SESSION['branches_id']) ? $_SESSION['branches_id'] : null;

$products = [];

if ($category_id) {
    $sql = "SELECT DISTINCT p.product_name 
            FROM products p 
            WHERE p.categories_id = ?";
    
    if ($branch_id) {
        $sql .= " AND p.branches_id = ?";
    }
    
    $stmt = $con->prepare($sql);
    
    if ($branch_id) {
        $stmt->bind_param("ii", $category_id, $branch_id);
    } else {
        $stmt->bind_param("i", $category_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($products); 