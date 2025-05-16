    <?php
    session_start();
    include "../db_conn.php";

    if (!isset($_SESSION['user_role'])) {
        header("Location: ../index.php"); // Redirect to login if not authenticated
        exit;
    }

    // Restrict access to Inventory Clerk, Super Admin, and Admin only
    if ($_SESSION['user_role'] !== "Inventory Clerk" && $_SESSION['user_role'] !== "Super Admin" && $_SESSION['user_role'] !== "Admin") {
        header("Location: ../403.php"); // Redirect unauthorized users
        exit;
    }

    // Fetch branches and categories
    $sql_branches = "SELECT id, branch_name FROM branches";
    $result_branches = $con->query($sql_branches);

    $sql_categories = "SELECT id, category_name FROM categories";
    $result_categories = $con->query($sql_categories);

    // Get filtering values from GET parameters
    $user_role = $_SESSION['user_role']; 
    $branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : ($_SESSION['user_role'] !== "Super Admin" ? $_SESSION['branches_id'] : '');
    $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';

    $sql = "SELECT i.id, i.avail_stock, i.price, i.damage_stock, i.delivery_price, 
                p.product_name, i.expiration_date, b.branch_name, c.category_name,
                i.batch, i.old_price, i.generic_name, i.received, p.vatable, i.strength,
                i.dosage_form_id

            FROM inventory i
            INNER JOIN products p ON i.products_id = p.id
            INNER JOIN branches b ON i.branches_id = b.id
            INNER JOIN categories c ON p.categories_id = c.id";

    // Apply filtering conditions dynamically
    $conditions = [];
    $params = [];
    $types = "";

    if (!empty($branch_id) && $user_role === "Super Admin") {
        $conditions[] = "b.id = ?";
        $params[] = $branch_id;
        $types .= "i";
    } elseif ($user_role !== "Super Admin") {
        $conditions[] = "b.id = ?";
        $params[] = $_SESSION['branches_id'];
        $types .= "i";
    }

    if (!empty($category_id)) {
        $conditions[] = "c.id = ?";
        $params[] = $category_id;
        $types .= "i";
    }

    // Append conditions to SQL query
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY i.id DESC";

    $stmt = $con->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die("SQL error: " . $con->error);
    }

    include '../includes/header.php';
    include '../includes/navbar.php';
    include '../includes/sidebar.php';
    include '../includes/footer.php';
    ?>

    <style>
        /* Minimal table styling */
        #myTable {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(0,0,0,0.02);
            --bs-table-hover-bg: rgba(0,0,0,0.04);
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }
        
        #myTable th {
            font-weight: 500;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem 1rem;
            white-space: nowrap;
        }
        
        #myTable td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        #myTable tr:last-child td {
            border-bottom: none;
        }
        
        /* Compact action buttons */
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            #myTable {
                font-size: 0.85rem;
            }
            
            #myTable th, 
            #myTable td {
                padding: 0.5rem;
            }
        }
    </style>

    <main id="main" class="main">
        <section class="dashboard section">
            <div class="container">
                <div class="justify-content-center row">
                    <div class="col-lg-12">
                        <div class="shadow-sm card">
                            <div class="text-black card-header">
                                <h3 class="mb-0">Inventory List</h3>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['message'])): ?>
                                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                                        <?php echo $_SESSION['message']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                                <?php endif; ?>

                                <!-- Add Inventory Button -->
                                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                                    <i class="bi bi-plus-circle"></i> Add Inventory
                                </button>

                                <!-- Add Inventory Modal -->
                                <div class="modal fade" id="addInventoryModal" tabindex="-1" aria-labelledby="addInventoryModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST" action="add_inventory.php" id="addInventoryForm">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="addInventoryModalLabel">Add Inventory</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                                            <select class="form-select" id="add_category_id" name="category_id" required onchange="toggleFieldsBasedOnCategory()">
                                                                <option value="">Select Category</option>
                                                                <?php
                                                                $cat_res = $con->query("SELECT id, category_name FROM categories");
                                                                while ($cat = $cat_res->fetch_assoc()) {
                                                                    echo "<option value='{$cat['id']}'>{$cat['category_name']}</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_product_id" class="form-label">Product <span class="text-danger">*</span></label>
                                                            <select class="form-select" id="add_product_id" name="product_id" required>
                                                                <option value="">Select Product</option>
                                                                <!-- Options will be loaded by JS -->
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_price" class="form-label">Selling Price <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" class="form-control" id="add_price" name="price" required onchange="validatePrice(this, 'add_old_price')">
                                                            <div class="invalid-feedback">New price cannot be lower than old price</div>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_old_price" class="form-label">Old Price</label>
                                                            <input type="number" step="0.01" class="form-control" id="add_old_price" name="old_price" onchange="validatePrice('add_price', this)">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_delivery_price" class="form-label">Delivery Price <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" class="form-control" id="add_delivery_price" name="delivery_price" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" id="add_delivery_date" name="delivery_date" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_avail_stock" class="form-label">Available Stock <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control" id="add_avail_stock" name="avail_stock" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_damage_stock" class="form-label">Damaged Stock</label>
                                                            <input type="number" class="form-control" id="add_damage_stock" name="damage_stock" value="0">
                                                        </div>
                                                        
                                                        <!-- Medicine-specific fields (initially hidden) -->
                                                         <div class="col-md-6 mb-3 medicine-field" style="display:none;">
                                                            <label for="add_generic_name" class="form-label">Generic Name<span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="add_generic_name" name="generic_name">
                                                        </div>
                                                        <div class="col-md-6 mb-3 medicine-field" style="display:none;">
                                                            <label for="add_batch" class="form-label">Batch No. / Lot No. <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="add_batch" name="batch">
                                                        </div>
                                                        <div class="col-md-6 mb-3 medicine-field" style="display:none;">
                                                            <label for="add_expiration_date" class="form-label">Expiration Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" id="add_expiration_date" name="expiration_date">
                                                        </div>
                                                        
                                                        <!-- Supplies-specific fields (initially hidden) -->
                                                        <div class="col-md-6 mb-3 supplies-field" style="display:none;">
                                                            <label for="add_supplier_info" class="form-label">Supplier Information</label>
                                                            <input type="text" class="form-control" id="add_supplier_info" name="supplier_info">
                                                        </div>
                                                        
                                                        <!-- Common fields -->
                                                        <div class="col-md-6 mb-3">
                                                            <label for="add_critical_level" class="form-label">Critical Level Stock <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control" id="add_critical_level" name="critical_level" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Add Inventory</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                function toggleFieldsBasedOnCategory() {
                                    const categorySelect = document.getElementById('add_category_id');
                                    const selectedCategory = categorySelect.options[categorySelect.selectedIndex].text.toLowerCase();
                                    
                                    // Hide all category-specific fields first
                                    document.querySelectorAll('.medicine-field, .supplies-field').forEach(field => {
                                        field.style.display = 'none';
                                        field.querySelector('input, select').removeAttribute('required');
                                    });
                                    
                                    // Show fields based on selected category
                                    if (selectedCategory.includes('medicine') || selectedCategory.includes('med')) {
                                        document.querySelectorAll('.medicine-field').forEach(field => {
                                            field.style.display = 'block';
                                            field.querySelector('input, select').setAttribute('required', 'required');
                                        });
                                    } else if (selectedCategory.includes('supplies') || selectedCategory.includes('supply')) {
                                        document.querySelectorAll('.supplies-field').forEach(field => {
                                            field.style.display = 'block';
                                        });
                                    }
                                }

                                // Call this function on page load in case there's a preselected value
                                document.addEventListener('DOMContentLoaded', function() {
                                    toggleFieldsBasedOnCategory();
                                });
                                </script>

                                <form method="GET" action="" class="col-md-12 row">
                                <?php if ($_SESSION['user_role'] === "Super Admin"): ?>
                                    <div class="mt-3 mb-3 col-md-4">
                                        <label for="branchFilter" class="form-label">Filter by Branch</label>
                                        <select id="branchFilter" name="branch_id" class="form-select" onchange="this.form.submit()">
                                            <option value="">Select Branch</option>
                                            <?php while ($branch = $result_branches->fetch_assoc()): ?>
                                                <option value="<?php echo $branch['id']; ?>" <?php echo ($branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3 mb-3 col-md-4">
                                    <label for="categoryFilter" class="form-label">Filter by Category</label>
                                    <select id="categoryFilter" name="category_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">Select Category</option>
                                        <?php while ($category = $result_categories->fetch_assoc()): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </form>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Branch</th>
                                                <th>Item</th>
                                                <th>Dosage</th>
                                                <th>Preperation</th>
                                                <th>Category</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th class="medicine-column">Expiry</th>
                                                <th class="medicine-column">Batch</th>
                                                <th>Vatable</th>
                                                <th>Updated</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $isMedicine = stripos($row['category_name'], 'medicine') !== false;
                                                $net_stock = max(0, $row['avail_stock'] - $row['damage_stock']);
                                                
                                                echo "<tr>
                                                    <td>" . htmlspecialchars($row['branch_name']) . "</td>
                                                    <td>
                                                        <strong>" . htmlspecialchars($row['product_name']) . "</strong>"
                                                        . ($row['generic_name'] ? "<br><small class='text-muted'><b>Brand: </b>" . htmlspecialchars($row['generic_name']) . "</small>" : "") .
                                                    "</td>
                                                    <td>" . htmlspecialchars($row['strength']) . "</td>
                                                    <td>" . (isset($dosage_forms[$row['dosage_form_id']]) ? htmlspecialchars($dosage_forms[$row['dosage_form_id']]) : htmlspecialchars($row['dosage_form_id'])) . "</td>
                                                    <td>" . htmlspecialchars($row['category_name']) . "</td>
                                                    <td style='text-align: right;'>"
                                                        . (($row['price'] && $row['price'] != 0)
                                                            ? '<strong>₱' . number_format($row['price'], 2) . '</strong>'
                                                            : '-')
                                                        . (($row['old_price'] && $row['old_price'] != $row['price'] && $row['old_price'] != 0)
                                                            ? "<br><small class='text-muted text-decoration-line-through'>₱" . number_format($row['old_price'], 2) . "</small>"
                                                            : "") .
                                                    "</td>
                                                    <td>"
                                                        . "<span class='badge bg-" . ($net_stock > 10 ? 'success' : ($net_stock > 0 ? 'warning' : 'danger')) . "'>" . $net_stock . "</span>"
                                                        . ($row['damage_stock'] > 0 ? "<br><small class='text-danger'>Damaged: " . $row['damage_stock'] . "</small>" : "") .
                                                    "</td>"
                                                    . "<td class='medicine-column'>" . (($row['expiration_date'] && $row['expiration_date'] != '0000-00-00') ? htmlspecialchars($row['expiration_date']) : '-') . "</td>"
                                                    . "<td class='medicine-column'>" . (($row['batch'] && $row['batch'] != '0') ? htmlspecialchars($row['batch']) : '-') . "</td>"
                                                    . "<td>" . htmlspecialchars($row['vatable']) . "</td>"
                                                    . "<td><small>" . $row['received'] . "</small></td>"
                                                    . "<td>
                                                        <div class='btn-group btn-group-sm'>
                                                            <a href='update_inventory.php?id=" . $row['id'] . "' class='btn btn-outline-primary'>
                                                                <i class='bi bi-pencil'></i>
                                                            </a>
                                                            <a href='delete.php?id=" . $row['id'] . "' class='btn btn-outline-danger' onclick='return confirm(\"Are you sure?\")'>
                                                                <i class='bi bi-trash'></i>
                                                            </a>
                                                        </div>
                                                    </td>"
                                                . "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='11' class='text-center py-4 text-muted'>No inventory records found</td></tr>";
                                        }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div> <!-- End Card -->
                    </div> <!-- End col-lg-12 -->
                </div> <!-- End row -->
            </div> <!-- End container -->
        </section>
    </main>

    <!-- Price Validation Modal -->
    <div class="modal fade" id="priceValidationModal" tabindex="-1" aria-labelledby="priceValidationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="priceValidationModalLabel">Price Validation Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        New price cannot be lower than old price.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Required Scripts -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="../assets/js/simple-datatables.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        const dataTable = new simpleDatatables.DataTable("#myTable", {
            perPageSelect: [5, 10, 15, ["All", -1]],
            columns: [{
                select: 2,
                sortSequence: ["desc", "asc"]
            }]
        });

        // Function to toggle columns
        window.toggleColumns = function() {
            dataTable.refresh();
        };

        // Price validation function
        window.validatePrice = function(newPriceInput, oldPriceInputId) {
            const newPrice = parseFloat(document.getElementById(newPriceInput).value) || 0;
            const oldPrice = parseFloat(document.getElementById(oldPriceInputId).value) || 0;
            const input = document.getElementById(newPriceInput);
            const form = input.closest('form');

            if (oldPrice > 0 && newPrice < oldPrice) {
                input.classList.add('is-invalid');
                form.querySelector('button[type="submit"]').disabled = true;
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('priceValidationModal'));
                modal.show();
                return false;
            } else {
                input.classList.remove('is-invalid');
                form.querySelector('button[type="submit"]').disabled = false;
                return true;
            }
        };

        // Add form submission validation
        document.getElementById('addInventoryForm').addEventListener('submit', function(e) {
            const newPrice = parseFloat(document.getElementById('add_price').value) || 0;
            const oldPrice = parseFloat(document.getElementById('add_old_price').value) || 0;

            if (oldPrice > 0 && newPrice < oldPrice) {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('priceValidationModal'));
                modal.show();
            }
        });

        // Category change event handler
        document.getElementById('add_category_id').addEventListener('change', function() {
            var categoryId = this.value;
            var branchId = <?php echo json_encode($_SESSION['branches_id']); ?>;
            var productSelect = document.getElementById('add_product_id');
            
            if (categoryId) {
                productSelect.innerHTML = '<option value="">Loading...</option>';
                fetch(`fetch_products_by_category.php?category_id=${categoryId}&branch_id=${branchId}`)
                    .then(response => response.json())
                    .then(data => {
                        productSelect.innerHTML = '<option value="">Select Product</option>';
                        if (data.products && data.products.length > 0) {
                            data.products.forEach(product => {
                                productSelect.innerHTML += `<option value="${product.id}">${product.product_name}</option>`;
                            });
                        } else {
                            productSelect.innerHTML = '<option value="">No products available</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        productSelect.innerHTML = '<option value="">Error loading products</option>';
                    });
            } else {
                productSelect.innerHTML = '<option value="">Select Product</option>';
            }
        });
    });
    </script>
