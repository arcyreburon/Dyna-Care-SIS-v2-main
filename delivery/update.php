<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== "Inventory Clerk" && 
     $_SESSION['user_role'] !== "Super Admin" && 
     $_SESSION['user_role'] !== "Admin")) {
    header("Location: ../403.php");
    exit;
}

// Initialize variables
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$delivery = [];

// Fetch delivery details
if ($id) {
    $sql = "SELECT d.*, c.category_name 
            FROM delivery d
            LEFT JOIN categories c ON d.categories_id = c.id
            WHERE d.id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $delivery = $result->fetch_assoc();
    $stmt->close();
    
    if (!$delivery) {
        $_SESSION['message'] = "Delivery record not found";
        $_SESSION['message_type'] = "danger";
        header("Location: delivery.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $product_name = $_POST['product_name'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $batch = $_POST['batch'] ?? '';
    $received = $_POST['received'] ?? '';
    $expiration_date = $_POST['expiration_date'] ?? '';
    $supplier = $_POST['supplier'] ?? '';
    $delivery_man = $_POST['delivery_man'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $category_id = intval($_POST['category_id'] ?? 0);

    $sql = "UPDATE delivery SET
            product_name = ?,
            price = ?,
            batch = ?,
            received = ?,
            expiration_date = ?,
            supplier = ?,
            delivery_man = ?,
            contact_number = ?,
            categories_id = ?
            WHERE id = ?";

    $stmt = $con->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sdssssssii", 
            $product_name,
            $price,
            $batch,
            $received,
            $expiration_date,
            $supplier,
            $delivery_man,
            $contact_number,
            $category_id,
            $id
        );
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Delivery updated successfully";
            $_SESSION['message_type'] = "success";
            header("Location: delivery.php");
            exit;
        } else {
            $_SESSION['message'] = "Error updating delivery: " . $con->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database error: " . $con->error;
        $_SESSION['message_type'] = "danger";
    }
}

// Fetch categories for dropdown
$categories = [];
$sql_categories = "SELECT id, category_name FROM categories";
$result_categories = $con->query($sql_categories);
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch suppliers for dropdown
$suppliers = [];
$supplier_result = $con->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name ASC");
while ($row = $supplier_result->fetch_assoc()) {
    $suppliers[] = $row;
}

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<main id="main" class="main">
    <section class="dashboard section">
        <div class="container" style="margin-top: -30px;">
            <div class="justify-content-center row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Update Delivery</h5>

                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                                    <?= $_SESSION['message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                            <?php endif; ?>

                            <form class="g-3 needs-validation row" method="POST" action="update.php?id=<?= $id ?>">

                                <div class="col-md-6">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required onchange="loadProducts()">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                <?= ($category['id'] == ($delivery['categories_id'] ?? 0)) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['category_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <select class="form-control" id="product_name" name="product_name" required>
                                        <option value="">Select Product</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                           value="<?= htmlspecialchars($delivery['price'] ?? 0) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="batch" class="form-label">Batch Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="batch" name="batch" 
                                           value="<?= htmlspecialchars($delivery['batch'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="received" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="received" name="received" 
                                           value="<?= htmlspecialchars($delivery['received'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="expiration_date" class="form-label">Expiration Date</label>
                                    <input type="date" class="form-control" id="expiration_date" name="expiration_date" 
                                           value="<?= htmlspecialchars($delivery['expiration_date'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="supplier" class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <select class="form-select" id="supplier" name="supplier" required onchange="showSupplierDetails()">
                                        <option value="">Select Supplier</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= htmlspecialchars($supplier['name']) ?>" 
                                                data-contact-person="<?= htmlspecialchars($supplier['contact_person']) ?>" 
                                                data-contact-number="<?= htmlspecialchars($supplier['contact_number']) ?>"
                                                <?= ($supplier['name'] === ($delivery['supplier'] ?? '')) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($supplier['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="delivery_man" class="form-label">Delivery Man <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="delivery_man" name="delivery_man" 
                                           value="<?= htmlspecialchars($delivery['delivery_man'] ?? '') ?>" required readonly>
                                </div>

                                <div class="col-md-6">
                                    <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_number" name="contact_number" 
                                           value="<?= htmlspecialchars($delivery['contact_number'] ?? '') ?>" 
                                           maxlength="11" oninput="formatPhoneNumber(this)" readonly required>
                                </div>

                                <div class="col-12 mt-3">
                                    <button class="btn btn-primary" type="submit">Update Delivery</button>
                                    <a href="delivery.php" class="btn btn-danger">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        input.value = value.replace(/(\d{4})(\d{3})(\d{4})/, '$1 $2 $3');
    }

    function showSupplierDetails() {
        var select = document.getElementById('supplier');
        var selected = select.options[select.selectedIndex];
        var contactPerson = selected.getAttribute('data-contact-person');
        var contactNumber = selected.getAttribute('data-contact-number');
        document.getElementById('delivery_man').value = contactPerson || '';
        document.getElementById('contact_number').value = contactNumber || '';
    }

    function loadProducts() {
        var categoryId = document.getElementById('category_id').value;
        var productSelect = document.getElementById('product_name');
        var currentProduct = '<?= htmlspecialchars($delivery['product_name'] ?? '') ?>';
        
        productSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch('fetch_products_by_category.php?category_id=' + categoryId)
            .then(response => response.json())
            .then(data => {
                productSelect.innerHTML = '<option value="">Select Product</option>';
                data.forEach(function(product) {
                    var selected = (product.product_name === currentProduct) ? 'selected' : '';
                    productSelect.innerHTML += '<option value="' + product.product_name + '" ' + selected + '>' + product.product_name + '</option>';
                });
            })
            .catch(error => {
                console.error('Error:', error);
                productSelect.innerHTML = '<option value="">Error loading products</option>';
            });
    }

    // Load products on page load if category is selected
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('category_id').value) {
            loadProducts();
        }
        // Trigger supplier details on page load if supplier is selected
        if (document.getElementById('supplier').value) {
            showSupplierDetails();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>