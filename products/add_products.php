<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php"); // Redirect to login if not authenticated
    exit;
}

// Restrict access to Inventory Clerk and Admin only
if ($_SESSION['user_role'] !== "Inventory Clerk" && $_SESSION['user_role'] !== "Admin") {
    header("Location: ../403.php"); // Redirect unauthorized users
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission to add product
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category_id'];
    $dosage_form_id = $_POST['dosage_form_id'];
    $strength = $_POST['strength'];
    $generic_name = $_POST['generic_name'];
    $branch_id = $_SESSION['branches_id'];
    $vatable = $_POST['vatable'];

    // Step 1: Insert new product into the products table
    $insert_product_sql = "INSERT INTO products (product_name, categories_id, branches_id, vatable) VALUES (?, ?, ?, ?)";
    $stmt = $con->prepare($insert_product_sql);
    $stmt->bind_param("siis", $product_name, $category_id, $branch_id, $vatable);

    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;
        // Step 2: Insert into inventory table for the same branch (only with product id and branch)
        $insert_inventory_sql = "INSERT INTO inventory (products_id, branches_id, dosage_form_id, strength, generic_name) VALUES (?, ?, ?, ?, ?)";
        $stmt_inventory = $con->prepare($insert_inventory_sql);
        $stmt_inventory->bind_param("iiiss", $product_id, $branch_id, $dosage_form_id, $strength, $generic_name);
        if (!$stmt_inventory->execute()) {
            echo "Error adding product to inventory: " . $stmt_inventory->error;
        }
        header("Location: products_table.php?success=Product added successfully to branch $branch_id!");
        exit;
    } else {
        echo "Error adding product: " . $stmt->error;
    }
}

// Include layout components
include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
include '../includes/footer.php';
?>




<!-- HTML Add Product Form -->
<main id="main" class="main">
    <section class="dashboard section">
        <div class="container">
            <div class="justify-content-center row">
                <div class="col-lg-12">
                    <div class="shadow-sm card">
                        <div class="text-black card-header">
                            <h3 class="mb-0">Add Product</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="add_products.php">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-control" id="category" name="category_id" required>
                                            <option value="">Select Category</option>
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
                                            <?php
                                            $dosage_sql = "SELECT * FROM dosage_forms";
                                            $dosage_result = $con->query($dosage_sql);
                                            $dosage = null;
                                            while ($dosage = $dosage_result->fetch_assoc()) {
                                                echo "<option value='{$dosage['id']}'>{$dosage['form_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="strength" class="form-label">Strength</label>
                                        <input type="text" class="form-control" id="strength" name="strength">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="generic_name" class="form-label">Generic Name</label>
                                        <input type="text" class="form-control" id="generic_name" name="generic_name">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="vatable" class="form-label">Vatable <span class="text-danger">*</span></label>
                                        <select class="form-control" id="vatable" name="vatable" required>
                                            <option value="No">No</option>
                                            <option value="Yes">Yes</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">Add Product</button>
                                <a href="products_table.php" class="btn btn-danger">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
