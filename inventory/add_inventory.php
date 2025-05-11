<?php
session_start();
include '../db_conn.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ["Inventory Clerk", "Super Admin", "Admin"])) {
    header("Location: ../403.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $branch_id = $_SESSION['branches_id'];
    $price = floatval($_POST['price']);
    $old_price = isset($_POST['old_price']) ? floatval($_POST['old_price']) : 0;
    $delivery_price = floatval($_POST['delivery_price']);
    $received = $_POST['delivery_date'];
    $avail_stock = intval($_POST['avail_stock']);
    $damage_stock = isset($_POST['damage_stock']) ? intval($_POST['damage_stock']) : 0;
    $batch = $_POST['batch'];
    $expiration_date = $_POST['expiration_date'];
    $critical_level = isset($_POST['critical_level']) ? intval($_POST['critical_level']) : 0;

    // Insert into inventory
    $sql = "INSERT INTO inventory (products_id, branches_id, price, old_price, delivery_price, received, avail_stock, damage_stock, batch, expiration_date, critical_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("iiddssisssi", $product_id, $branch_id, $price, $old_price, $delivery_price, $received, $avail_stock, $damage_stock, $batch, $expiration_date, $critical_level);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Inventory added successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding inventory: " . $stmt->error;
        $_SESSION['message_type'] = "danger";
    }
    header("Location: inventory_table.php");
    exit;
} else {
    header("Location: inventory_table.php");
    exit;
} 