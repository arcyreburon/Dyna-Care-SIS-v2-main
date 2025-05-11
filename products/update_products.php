<title>DynaCareSIS - update products</title>
<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php"); // Redirect to login if not authenticated
    exit;
}

// Restrict access to Inventory Clerk, Super Admin, and Admin only
if (!in_array($_SESSION['user_role'], ["Inventory Clerk", "Admin"])) {
    header("Location: ../403.php"); // Redirect unauthorized users
    exit;
}

// Fetch product details to update
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $branch_id = $_SESSION['branches_id'];

    // SQL query to fetch product and inventory details for the logged-in user's branch
    $sql = "SELECT p.id, p.product_name, p.expiration_date, c.id AS category_id, c.category_name, i.avail_stock, i.damage_stock, i.price, i.old_price, i.delivery_price, i.received, i.batch, i.strength, i.generic_name, i.dosage_form_id, p.vatable
            FROM products p
            INNER JOIN categories c ON p.categories_id = c.id
            INNER JOIN inventory i ON p.id = i.products_id
            WHERE p.id = ? AND p.branches_id = ?";
    $stmt = $con->prepare($sql);
    if (!$stmt) { die("SQL error: " . $con->error); }
    $stmt->bind_param("ii", $id, $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        echo "Product not found or not in your branch!";
        exit;
    }
    // Fetch dosage forms for dropdown
    $dosage_forms = [];
    $dosage_sql = "SELECT * FROM dosage_forms";
    $dosage_result = $con->query($dosage_sql);
    while ($row = $dosage_result->fetch_assoc()) {
        $dosage_forms[] = $row;
    }
} else {
    echo "Product ID is missing!";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handling form submission
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category_id'];
    $id = $_POST['id'];
    $branch_id = $_SESSION['branches_id'];
    $dosage_form_id = $_POST['dosage_form_id'];
    $strength = $_POST['strength'];
    $generic_name = $_POST['generic_name'];
    $vatable = $_POST['vatable'];

    // Update products table
    $update_product_sql = "UPDATE products SET product_name = ?, categories_id = ?, vatable = ? WHERE id = ? AND branches_id = ?";
    $update_product_stmt = $con->prepare($update_product_sql);
    if (!$update_product_stmt) { die("SQL error: " . $con->error); }
    $update_product_stmt->bind_param("sisii", $product_name, $category_id, $vatable, $id, $branch_id);
    $update_product_stmt->execute();

    // Update inventory table (only product-specific fields)
    $update_inventory_sql = "UPDATE inventory SET dosage_form_id = ?, strength = ?, generic_name = ? WHERE products_id = ? AND branches_id = ?";
    $update_inventory_stmt = $con->prepare($update_inventory_sql);
    if (!$update_inventory_stmt) { die("SQL error: " . $con->error); }
    $update_inventory_stmt->bind_param("issii", $dosage_form_id, $strength, $generic_name, $id, $branch_id);
    $update_inventory_stmt->execute();

    header("Location: products_table.php?success=Product updated successfully!");
    exit;
}

// Include layout components
include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
include '../includes/footer.php';
?>




<!-- HTML Update Form -->
<main id="main" class="main">
    <section class="dashboard section">
        <div class="container">
            <div class="justify-content-center row">
                <div class="col-lg-12">
                    <div class="shadow-sm card">
                        <div class="text-black card-header">
                            <h3 class="mb-0">Update Product</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= htmlspecialchars($product['id']) ?>">
                                <!-- Hidden input for the product ID -->
                                <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">

                                <div class="row">
                                    <!-- First Column -->
                                    <div class="col-md-6 mb-3">
                                        <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-control" id="category_id" name="category_id" required>
                                            <option value="<?= $product['category_id'] ?>" selected><?= htmlspecialchars($product['category_name']) ?></option>
                                            <?php
                                            $category_sql = "SELECT * FROM categories";
                                            $category_result = $con->query($category_sql);
                                            while ($category = $category_result->fetch_assoc()) {
                                                echo "<option value='{$category['id']}'>{$category['category_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="dosage_form_id" class="form-label">Dosage Form</label>
                                        <select class="form-control" id="dosage_form_id" name="dosage_form_id">
                                            <option value="">Select Dosage Form</option>
                                            <?php foreach ($dosage_forms as $form): ?>
                                                <option value="<?= $form['id'] ?>" <?= ($product['dosage_form_id'] == $form['id']) ? 'selected' : '' ?>><?= htmlspecialchars($form['form_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="strength" class="form-label">Strength</label>
                                        <input type="text" class="form-control" id="strength" name="strength" value="<?= htmlspecialchars($product['strength'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="generic_name" class="form-label">Generic Name</label>
                                        <input type="text" class="form-control" id="generic_name" name="generic_name" value="<?= htmlspecialchars($product['generic_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="vatable" class="form-label">Vatable <span class="text-danger">*</span></label>
                                        <select class="form-control" id="vatable" name="vatable" required>
                                            <option value="No" <?= (isset($product['vatable']) && $product['vatable'] == 'No') ? 'selected' : '' ?>>No</option>
                                            <option value="Yes" <?= (isset($product['vatable']) && $product['vatable'] == 'Yes') ? 'selected' : '' ?>>Yes</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Update Product</button>
                                <a href="products_table.php" class="btn btn-danger">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

