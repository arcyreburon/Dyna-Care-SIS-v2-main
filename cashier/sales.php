<!DOCTYPE html>
<html lang="en">
<head>
    <title>DynaCareSIS - Sales</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4bb543;
            --danger: #f72585;
            --warning: #ffc107;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        .badge {
            font-weight: 500;
            padding: 5px 8px;
        }
        
        .badge-success {
            background-color: var(--success);
        }
        
        .badge-danger {
            background-color: var(--danger);
        }
        
        .badge-warning {
            background-color: var(--warning);
            color: #212529;
        }
        
        .quantity-control {
            width: 120px;
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .total-display {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        #orderTable {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(0,0,0,0.02);
            --bs-table-hover-bg: rgba(0,0,0,0.04);
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.95rem;
        }
        #orderTable th {
            font-weight: 500;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem 1rem;
            white-space: nowrap;
            text-align: center;
        }
        #orderTable td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
            text-align: center;
        }
        #orderTable tr:last-child td {
            border-bottom: none;
        }
        @media (max-width: 768px) {
            #orderTable {
                font-size: 0.85rem;
            }
            #orderTable th, #orderTable td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php
    session_start();
    include "../db_conn.php";
    
    if ($_SESSION['user_role'] !== "Cashier") {
        header("Location: ../403.php");
        exit;
    }
    
    include '../includes/header.php';
    include '../includes/navbar.php';
    include '../includes/sidebar.php';
    ?>

<main id="main" class="main">
    <div class="container-fluid">
        <!-- Alerts Section -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
        <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="row">
            <!-- Products Column -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-box-seam me-2"></i>Available Products</h4>
                        <a href="../cashier/reorder_form.php" class="btn btn-outline-light">
                            <i class="bi bi-plus-circle me-1"></i> Re-Order
                        </a>
                    </div>
                    <div class="card-body">
                    <div class="d-flex justify-content-end mb-2">
        <a href="../cashier/reorder_form.php" class="btn btn-warning">
            <i class="bi bi-exclamation-circle"></i> Re-Order Low Stock
        </a>
    </div>
    <!-- Add this search input -->
    <div class="input-group mb-3">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" id="product-search" class="form-control" placeholder="Search products...">
    </div>

        <div id="no-products-message" class="alert alert-info d-none text-center">
        <i class="bi bi-exclamation-circle"></i> No products found matching your search.
    </div>
                        <div class="table-responsive">
                            <table id="products-table" class="custom-table table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Brand</th>
                                        <th>Category</th>
                                        <th>Stock</th>
                                        <th>Price</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT p.id, p.product_name, i.price, c.category_name, i.avail_stock, p.vatable, i.critical_level, i.generic_name
                                            FROM products p
                                            INNER JOIN categories c ON p.categories_id = c.id
                                            INNER JOIN inventory i ON p.id = i.products_id
                                            WHERE p.branches_id = ?";
                                    
                                    $stmt = $con->prepare($sql);
                                    $stmt->bind_param("i", $_SESSION['branches_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    while ($row = $result->fetch_assoc()) {
                                        $disabled = ($row["avail_stock"] == 0) ? "disabled" : "";
                                        $stockClass = ($row["avail_stock"] == 0) ? "badge-danger" : 
                                                     ($row["avail_stock"] < 10 ? "badge-warning" : "badge-success");
                                        $isLowStock = ($row["avail_stock"] <= $row["critical_level"]);
                                        
                                        echo "<tr data-id='{$row["id"]}' data-name='{$row["product_name"]}' 
                                              data-category='{$row["category_name"]}' data_generic_name='{$row["generic_name"]}' data-stock='{$row["avail_stock"]}' 
                                              data-price='{$row["price"]}' data-vatable='{$row["vatable"]}'>";
                                        echo "<td>{$row["product_name"]}";
                                        if ($isLowStock) {
                                            echo " <span class='badge bg-danger ms-2'>Low Stock!</span>";
                                        }
                                        echo "</td>";
                                        echo "<td>{$row["generic_name"]}</td>";
                                        echo "<td>{$row["category_name"]}</td>";
                                        echo "<td><span class='badge $stockClass'>{$row["avail_stock"]}</span></td>";
                                        echo "<td>₱" . number_format($row["price"], 2) . "</td>";
                                        echo "<td>";
                                        echo "<button class='add-to-cart btn btn-sm btn-primary' data-id='{$row["id"]}' $disabled><i class='bi bi-cart-plus'></i> Add</button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Column -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="bi bi-receipt me-2"></i>Customer Order</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="order-form">
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-bold">Transaction No.</label>
                                <div class="col-sm-8">
                                    <span class="form-control-plaintext fw-bold text-primary" id="transaction-id-display"></span>
                                    <input type="hidden" id="hidden-transaction-id" name="transaction_no">
                                    
                                    <div style="text-align: left;">
                                        <?php
                                            date_default_timezone_set('Asia/Manila');
                                        ?>
                                        <small><b>Date:</b> <?php echo date('m/d/Y'); ?></small>
                                        <br>
                                        <small><b>Time:</b> <?php echo date('h:i A'); ?></small>
                                    </div>

                                </div>
                            </div>
                            
                            <div class="table-responsive mb-3">
                                <table id="orderTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Item</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-center">Price</th>
                                            <th class="text-center">Total</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items">
                                        <!-- Cart items will be dynamically inserted here -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="total-display">
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Subtotal:</div>
                                    <div class="col-6 text-end fw-bold">₱<span id="subtotal">0.00</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">Discount:</div>
                                    <div class="col-6 text-end fw-bold">-₱<span id="discount-amount">0.00</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted">VAT (12%):</div>
                                    <div class="col-6 text-end fw-bold">₱<span id="vat-amount">0.00</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-6 h5">Grand Total:</div>
                                    <div class="col-6 text-end h4 text-primary fw-bold">₱<span id="grand-total">0.00</span></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Discount Type</label>
                                <div class="col-sm-8">
                                    <select class="form-select" id="discount" name="discount">
                                        <option value="0">0%</option>
                                        <option value="20">20%</option>
                                        <option value="40">40%</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Cash Tendered</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="cashTendered" name="cash_tendered" min="0" step="0.01" placeholder="0.00">
                                    </div>
                                    <small class="text-danger d-none" id="insufficient-balance">
                                        <i class="bi bi-exclamation-circle"></i> Insufficient amount
                                    </small>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label">Change Due</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="text" class="form-control bg-light" id="change" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-primary me-md-2" id="print-receipt" disabled>
                                    <i class="bi bi-printer me-1"></i>Print Receipt
                                </button>
                                <button type="button" class="btn btn-success" id="process-order" disabled>
                                    <i class="bi bi-check-circle me-1"></i>Process Order
                                </button>
                            </div>
                            
                            <input type="hidden" name="cart" id="cart-data">
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Modal -->
        <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Order Receipt</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="receipt-content" class="receipt">
                            <div class="text-center mb-3">
                                <h4 class="mb-0">DynaCare: Medical Trading</h4>
                                <small><?php echo $_SESSION['branch_name']; ?></small>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <small>Transaction #: <span id="receipt-transaction-no"></span></small>
                                    <small>Date: <?php echo date('m/d/Y'); ?></small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small>Cashier: <?php echo $_SESSION['name']; ?></small>
                                    <small>Time: <?php echo date('h:i A'); ?></small>
                                </div>
                                <hr>
                            </div>
                            
                            <table class="table table-sm mb-2">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="receipt-items">
                                    <!-- Receipt items will appear here -->
                                </tbody>
                            </table>
                            
                            <div class="border-top pt-2">
                                <div class="d-flex justify-content-between">
                                    <span>Subtotal:</span>
                                    <span>₱<span id="receipt-subtotal">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Discount:</span>
                                    <span>-₱<span id="receipt-discount">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>VAT (12%):</span>
                                    <span>₱<span id="receipt-vat">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Grand Total:</span>
                                    <span>₱<span id="receipt-total">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Cash Tendered:</span>
                                    <span>₱<span id="receipt-cash">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Change:</span>
                                    <span>₱<span id="receipt-change">0.00</span></span>
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary" id="print-receipt-btn">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Order</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to process this order?</p>
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Total Amount:</strong>
                                <span>₱<span id="modal-total">0.00</span></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Cash Received:</strong>
                                <span>₱<span id="modal-cash">0.00</span></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Change Due:</strong>
                                <span>₱<span id="modal-change">0.00</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="confirm-process">
                            <i class="bi bi-check-circle me-1"></i>Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
// Make cartItems global so all scripts can access it
let cartItems = [];
</script>

<script>
// Product search functionality
document.getElementById('product-search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#products-table tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const productName = row.dataset.name.toLowerCase();
        const category = row.dataset.category.toLowerCase();
        const stock = row.dataset.stock;
        const price = row.dataset.price;
        
        if (productName.includes(searchTerm) || 
            category.includes(searchTerm) || 
            stock.includes(searchTerm) || 
            price.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide no products message
    const noProductsMsg = document.getElementById('no-products-message');
    if (visibleCount === 0 && searchTerm.length > 0) {
        noProductsMsg.classList.remove('d-none');
    } else {
        noProductsMsg.classList.add('d-none');
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const vatRate = 0.12;
    const discountElement = document.getElementById('discount');
    const cashTenderedElement = document.getElementById('cashTendered');
    const processOrderButton = document.getElementById('process-order');
    const printReceiptButton = document.getElementById('print-receipt');
    const insufficientBalanceSpan = document.getElementById('insufficient-balance');
    const transactionIdDisplay = document.getElementById('transaction-id-display');
    const transactionIdInput = document.getElementById('hidden-transaction-id');
    const subtotalElement = document.getElementById('subtotal');
    const discountAmountElement = document.getElementById('discount-amount');
    const vatAmountElement = document.getElementById('vat-amount');
    const grandTotalElement = document.getElementById('grand-total');
    const changeElement = document.getElementById('change');
    
    // Generate random transaction number
    function generateTransactionNumber() {
        const now = new Date();
        const datePart = now.getFullYear().toString().substr(-2) + 
                       (now.getMonth() + 1).toString().padStart(2, '0') + 
                       now.getDate().toString().padStart(2, '0');
        const randomPart = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
        return `TRX-${datePart}-${randomPart}`;
    }
    
    // Calculate all financial values
    function calculateFinancials() {
        // Subtotal for all items
        const subtotal = cartItems.reduce((total, item) => total + (item.quantity * item.unitPrice), 0);
        const discountPercent = parseFloat(discountElement.value);
        const discountAmount = subtotal * (discountPercent / 100);
        const discountedSubtotal = subtotal - discountAmount;
        // Calculate VAT only for vatable items
        const vatableSubtotal = cartItems.filter(item => item.vatable === 'Yes').reduce((total, item) => total + (item.quantity * item.unitPrice), 0);
        const discountedVatableSubtotal = vatableSubtotal - (vatableSubtotal * (discountPercent / 100));
        const vatAmount = discountedVatableSubtotal * vatRate;
        const grandTotal = discountedSubtotal + vatAmount;
        const cashTendered = parseFloat(cashTenderedElement.value) || 0;
        const change = cashTendered - grandTotal;
        
        return {
            subtotal,
            discountAmount,
            vatAmount,
            grandTotal,
            change,
            isPaymentValid: cashTendered >= grandTotal && cashTendered > 0
        };
    }
    
    // Update all financial displays
    function updateFinancialDisplay() {
        const { subtotal, discountAmount, vatAmount, grandTotal, change, isPaymentValid } = calculateFinancials();
        
        subtotalElement.textContent = subtotal.toFixed(2);
        discountAmountElement.textContent = discountAmount.toFixed(2);
        vatAmountElement.textContent = vatAmount.toFixed(2);
        grandTotalElement.textContent = grandTotal.toFixed(2);
        changeElement.value = Math.max(0, change).toFixed(2);
        
        insufficientBalanceSpan.classList.toggle('d-none', isPaymentValid);
        processOrderButton.disabled = cartItems.length === 0 || !isPaymentValid;
        printReceiptButton.disabled = cartItems.length === 0;
    }
    
    // Update cart items display
    function updateCartItems() {
        const cartItemsContainer = document.getElementById('cart-items');
        cartItemsContainer.innerHTML = '';
        
        if (cartItems.length === 0) {
            cartItemsContainer.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="bi bi-cart-x fs-1"></i>
                        <p class="mt-2">No items in cart</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        cartItems.forEach((item, index) => {
            const row = document.createElement('tr');
            row.dataset.id = item.id;
            row.innerHTML = `
                <td>${item.name}</td>
                <td>
                    <div class="d-flex quantity-control">
                        <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity(${index})">
                            <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" value="${item.quantity}" min="1" max="${item.maxQuantity}" 
                               class="form-control text-center mx-1" 
                               onchange="updateQuantity(${index}, this.value)" 
                               style="padding-right: 0.5rem; width: 60px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity(${index})">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </td>
                <td>₱${item.unitPrice.toFixed(2)}</td>
                <td>₱${(item.quantity * item.unitPrice).toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            cartItemsContainer.appendChild(row);
        });
        
        updateFinancialDisplay();
    }
    
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.dataset.id;
            const name = row.dataset.name;
            const stock = parseInt(row.dataset.stock);
            const price = parseFloat(row.dataset.price);
            const vatable = row.dataset.vatable === 'Yes';
            
            const existingItem = cartItems.find(item => item.id === id);
            
            if (existingItem) {
                if (existingItem.quantity < stock) {
                    existingItem.quantity += 1;
                } else {
                    alert('Not enough stock available.');
                    return;
                }
            } else {
                if (stock > 0) {
                    cartItems.push({
                        id, 
                        name, 
                        quantity: 1, 
                        unitPrice: price,
                        maxQuantity: stock,
                        vatable
                    });
                } else {
                    alert('This product is out of stock.');
                    return;
                }
            }
            
            // Update stock display
            const newStock = stock - 1;
            row.dataset.stock = newStock;
            const badge = row.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
            badge.textContent = newStock;
            
            if (newStock === 0) {
                badge.classList.remove('badge-success', 'badge-warning');
                badge.classList.add('badge-danger');
                this.disabled = true;
            } else if (newStock < 10) {
                badge.classList.remove('badge-success', 'badge-danger');
                badge.classList.add('badge-warning');
            }
            
            updateCartItems();
        });
    });
    
    // Quantity adjustment functions
    window.increaseQuantity = function(index) {
        const item = cartItems[index];
        if (item.quantity < item.maxQuantity) {
            item.quantity += 1;
            
            // Update stock display
            const productRow = document.querySelector(`#products-table tr[data-id="${item.id}"]`);
            const newStock = parseInt(productRow.dataset.stock) - 1;
            productRow.dataset.stock = newStock;
            const badge = productRow.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
            badge.textContent = newStock;
            
            if (newStock === 0) {
                badge.classList.remove('badge-success', 'badge-warning');
                badge.classList.add('badge-danger');
                productRow.querySelector('.add-to-cart').disabled = true;
            } else if (newStock < 10) {
                badge.classList.remove('badge-success', 'badge-danger');
                badge.classList.add('badge-warning');
            }
            
            updateCartItems();
        } else {
            alert('Cannot exceed available stock');
        }
    };
    
    window.decreaseQuantity = function(index) {
        const item = cartItems[index];
        if (item.quantity > 1) {
            item.quantity -= 1;
            
            // Update stock display
            const productRow = document.querySelector(`#products-table tr[data-id="${item.id}"]`);
            const newStock = parseInt(productRow.dataset.stock) + 1;
            productRow.dataset.stock = newStock;
            const badge = productRow.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
            badge.textContent = newStock;
            
            if (newStock > 0) {
                badge.classList.remove('badge-danger');
                if (newStock < 10) {
                    badge.classList.add('badge-warning');
                } else {
                    badge.classList.add('badge-success');
                }
                productRow.querySelector('.add-to-cart').disabled = false;
            }
            
            updateCartItems();
        }
    };
    
    window.updateQuantity = function(index, quantity) {
        const newQuantity = parseInt(quantity);
        if (isNaN(newQuantity)) return;
        
        const item = cartItems[index];
        const productRow = document.querySelector(`#products-table tr[data-id="${item.id}"]`);
        const currentStock = parseInt(productRow.dataset.stock) + item.quantity;
        
        if (newQuantity < 1 || newQuantity > currentStock) {
            alert('Invalid quantity');
            return;
        }
        
        const stockDiff = item.quantity - newQuantity;
        item.quantity = newQuantity;
        
        // Update stock display
        const newStock = parseInt(productRow.dataset.stock) + stockDiff;
        productRow.dataset.stock = newStock;
        const badge = productRow.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
        badge.textContent = newStock;
        
        if (newStock === 0) {
            badge.classList.remove('badge-success', 'badge-warning');
            badge.classList.add('badge-danger');
            productRow.querySelector('.add-to-cart').disabled = true;
        } else if (newStock < 10) {
            badge.classList.remove('badge-success', 'badge-danger');
            badge.classList.add('badge-warning');
        } else {
            badge.classList.remove('badge-warning', 'badge-danger');
            badge.classList.add('badge-success');
        }
        
        updateCartItems();
    };
    
    window.removeItem = function(index) {
        const item = cartItems[index];
        const productRow = document.querySelector(`#products-table tr[data-id="${item.id}"]`);
        
        // Update stock display
        const newStock = parseInt(productRow.dataset.stock) + item.quantity;
        productRow.dataset.stock = newStock;
        const badge = productRow.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
        badge.textContent = newStock;
        
        if (newStock > 0) {
            badge.classList.remove('badge-danger');
            if (newStock < 10) {
                badge.classList.add('badge-warning');
            } else {
                badge.classList.add('badge-success');
            }
            productRow.querySelector('.add-to-cart').disabled = false;
        }
        
        cartItems.splice(index, 1);
        updateCartItems();
    };

    // Process order confirmation
    document.getElementById('process-order').addEventListener('click', function() {
        const { grandTotal, change } = calculateFinancials();
        const cashTendered = parseFloat(cashTenderedElement.value) || 0;
        
        if (cashTendered < grandTotal) {
            alert('Insufficient cash tendered');
            return;
        }
        
        // Update modal values
        document.getElementById('modal-total').textContent = grandTotal.toFixed(2);
        document.getElementById('modal-cash').textContent = cashTendered.toFixed(2);
        document.getElementById('modal-change').textContent = change.toFixed(2);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    });
    
    // Confirm order processing
    document.getElementById('confirm-process').addEventListener('click', function() {
        document.getElementById('cart-data').value = JSON.stringify(cartItems);
        document.getElementById('order-form').submit();
    });
    
    // Event listeners for financial updates
    discountElement.addEventListener('change', updateFinancialDisplay);
    cashTenderedElement.addEventListener('input', updateFinancialDisplay);
    
    // Initialize transaction number
    const transactionNo = generateTransactionNumber();
    transactionIdDisplay.textContent = transactionNo;
    transactionIdInput.value = transactionNo;
    
    // Initialize empty cart display
    updateCartItems();

    // Print Receipt Button in Modal Click Handler
    document.getElementById('print-receipt-btn').addEventListener('click', function() {
        // Create a print-friendly version
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt</title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 12px; padding: 10px; }
                    .receipt { width: 100%; max-width: 300px; margin: 0 auto; }
                    .text-center { text-align: center; }
                    .text-end { text-align: right; }
                    table { width: 100%; border-collapse: collapse; margin: 5px 0; }
                    th, td { padding: 3px 0; }
                    hr { border-top: 1px dashed #000; margin: 5px 0; }
                    .fw-bold { font-weight: bold; }
                </style>
            </head>
            <body>
                ${document.getElementById('receipt-content').innerHTML}
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 100);
                    };
                <\/script>
            </body>
            </html>
        `;
        const printWindow = window.open('', '_blank', 'width=600,height=600');
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
    });
});

// Print Receipt Button Click Handler (move this outside DOMContentLoaded and use cartItems)
document.getElementById('print-receipt').addEventListener('click', function() {
    const receiptItems = document.getElementById('receipt-items');
    receiptItems.innerHTML = '';
    if (cartItems.length > 0) {
        cartItems.forEach((item, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name}</td>
                <td class="text-end">${item.quantity}</td>
                <td class="text-end">₱${item.unitPrice.toFixed(2)}</td>
                <td class="text-end">₱${(item.quantity * item.unitPrice).toFixed(2)}</td>
            `;
            receiptItems.appendChild(row);
        });
    } else {
        // If cart is empty, show the same empty state as order-form
        receiptItems.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                    <i class="bi bi-cart-x fs-1"></i>
                    <p class="mt-2">No items in cart</p>
                </td>
            </tr>
        `;
    }
    // Update receipt totals with the same formatting as order-form
    document.getElementById('receipt-transaction-no').textContent = document.getElementById('transaction-id-display').textContent;
    document.getElementById('receipt-subtotal').textContent = document.getElementById('subtotal').textContent;
    document.getElementById('receipt-discount').textContent = document.getElementById('discount-amount').textContent;
    document.getElementById('receipt-vat').textContent = document.getElementById('vat-amount').textContent;
    document.getElementById('receipt-total').textContent = document.getElementById('grand-total').textContent;
    document.getElementById('receipt-cash').textContent = document.getElementById('cashTendered').value || '0.00';
    document.getElementById('receipt-change').textContent = document.getElementById('change').value || '0.00';
    // Show receipt modal
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
});
</script>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        let cartItems = [];
        const vatRate = 0.12;
        const discountElement = document.getElementById('discount');
        const cashTenderedElement = document.getElementById('cashTendered');
        const processOrderButton = document.getElementById('process-order');
        const printReceiptButton = document.getElementById('print-receipt');
        const insufficientBalanceSpan = document.getElementById('insufficient-balance');
        const transactionIdDisplay = document.getElementById('transaction-id-display');
        const transactionIdInput = document.getElementById('hidden-transaction-id');
        const subtotalElement = document.getElementById('subtotal');
        const discountAmountElement = document.getElementById('discount-amount');
        const vatAmountElement = document.getElementById('vat-amount');
        const grandTotalElement = document.getElementById('grand-total');
        const changeElement = document.getElementById('change');
        
        // Generate random transaction number
        function generateTransactionNumber() {
            const now = new Date();
            const datePart = now.getFullYear().toString().substr(-2) + 
                           (now.getMonth() + 1).toString().padStart(2, '0') + 
                           now.getDate().toString().padStart(2, '0');
            const randomPart = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
            return `TRX-${datePart}-${randomPart}`;
        }
        
        // Calculate all financial values
        function calculateFinancials() {
            // Subtotal for all items
            const subtotal = cartItems.reduce((total, item) => total + (item.quantity * item.unitPrice), 0);
            const discountPercent = parseFloat(discountElement.value);
            const discountAmount = subtotal * (discountPercent / 100);
            const discountedSubtotal = subtotal - discountAmount;
            // Calculate VAT only for vatable items
            const vatableSubtotal = cartItems.filter(item => item.vatable === 'Yes').reduce((total, item) => total + (item.quantity * item.unitPrice), 0);
            const discountedVatableSubtotal = vatableSubtotal - (vatableSubtotal * (discountPercent / 100));
            const vatAmount = discountedVatableSubtotal * vatRate;
            const grandTotal = discountedSubtotal + vatAmount;
            const cashTendered = parseFloat(cashTenderedElement.value) || 0;
            const change = cashTendered - grandTotal;
            
            return {
                subtotal,
                discountAmount,
                vatAmount,
                grandTotal,
                change,
                isPaymentValid: cashTendered >= grandTotal && cashTendered > 0
            };
        }
        
        // Update all financial displays
        function updateFinancialDisplay() {
            const { subtotal, discountAmount, vatAmount, grandTotal, change, isPaymentValid } = calculateFinancials();
            
            subtotalElement.textContent = subtotal.toFixed(2);
            discountAmountElement.textContent = discountAmount.toFixed(2);
            vatAmountElement.textContent = vatAmount.toFixed(2);
            grandTotalElement.textContent = grandTotal.toFixed(2);
            changeElement.value = Math.max(0, change).toFixed(2);
            
            insufficientBalanceSpan.classList.toggle('d-none', isPaymentValid);
            processOrderButton.disabled = cartItems.length === 0 || !isPaymentValid;
            printReceiptButton.disabled = cartItems.length === 0;
        }
        
        // Update cart items display
        function updateCartItems() {
            const cartItemsContainer = document.getElementById('cart-items');
            cartItemsContainer.innerHTML = '';
            
            if (cartItems.length === 0) {
                cartItemsContainer.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-cart-x fs-1"></i>
                            <p class="mt-2">No items in cart</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            cartItems.forEach((item, index) => {
                const row = document.createElement('tr');
                row.dataset.id = item.id;
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>
                        <div class="d-flex quantity-control">
                            <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity(${index})">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" value="${item.quantity}" min="1" max="${item.maxQuantity}" 
                                   class="form-control text-center mx-1" 
                                   onchange="updateQuantity(${index}, this.value)" 
                                   style="padding-right: 0.5rem; width: 60px;">
                            <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity(${index})">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td>₱${item.unitPrice.toFixed(2)}</td>
                    <td>₱${(item.quantity * item.unitPrice).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                cartItemsContainer.appendChild(row);
            });
            
            updateFinancialDisplay();
        }
        
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const id = row.dataset.id;
                const name = row.dataset.name;
                const stock = parseInt(row.dataset.stock);
                const price = parseFloat(row.dataset.price);
                const vatable = row.dataset.vatable === 'Yes';
                
                const existingItem = cartItems.find(item => item.id === id);
                
                if (existingItem) {
                    if (existingItem.quantity < stock) {
                        existingItem.quantity += 1;
                    } else {
                        alert('Not enough stock available.');
                        return;
                    }
                } else {
                    if (stock > 0) {
                        cartItems.push({
                            id, 
                            name, 
                            quantity: 1, 
                            unitPrice: price,
                            maxQuantity: stock,
                            vatable
                        });
                    } else {
                        alert('This product is out of stock.');
                        return;
                    }
                }
                
                // Update stock display
                const newStock = stock - 1;
                row.dataset.stock = newStock;
                const badge = row.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
                badge.textContent = newStock;
                
                if (newStock === 0) {
                    badge.classList.remove('badge-success', 'badge-warning');
                    badge.classList.add('badge-danger');
                    this.disabled = true;
                } else if (newStock < 10) {
                    badge.classList.remove('badge-success', 'badge-danger');
                    badge.classList.add('badge-warning');
                }
                
                updateCartItems();
            });
        });
        
        // Quantity adjustment functions
        window.increaseQuantity = function(index) {
            const item = cartItems[index];
            if (item.quantity < item.maxQuantity) {
                item.quantity += 1;
                
                // Update stock display
                const productRow = document.querySelector(`#products-table tr[data-id="${item.id}"]`);
                const newStock = parseInt(productRow.dataset.stock) - 1;
                productRow.dataset.stock = newStock;
                const badge = productRow.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
                badge.textContent = newStock;
                
                if (newStock === 0) {
                    badge.classList.remove('badge-success', 'badge-warning');
                    badge.classList.add('badge-danger');
                    productRow.querySelector('.add-to-cart').disabled = true;
                } else if (newStock < 10) {
                    badge.classList.remove('badge-success', 'badge-danger');
                    badge.classList.add('badge-warning');
                }
                
                updateCartItems();
            } else {
                alert('Cannot exceed available stock');
            }
        };
        
        window.decreaseQuantity = function(index) {
            const item = cartItems[index];
            if (item.quantity > 1) {
                item.quantity -= 1;
                
                // Update stock display
                const productRow = document.querySelector(`#products-table tr[data-id="${item.id}"]`);
                const newStock = parseInt(productRow.dataset.stock) + 1;
                productRow.dataset.stock = newStock;
                const badge = productRow.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
                badge.textContent = newStock;
                
                if (newStock > 0) {
                    badge.classList.remove('badge-danger');
                    if (newStock < 10) {
                        badge.classList.add('badge-warning');
                    } else {
                        badge.classList.add('badge-success');
                    }
                    productRow.querySelector('.add-to-cart').disabled = false;
                }
                
                updateCartItems();
            }
        };
        
        window.updateQuantity = function(index, quantity) {
            const newQuantity = parseInt(quantity);
            if (isNaN(newQuantity)) return;
            
            const item = cartItems[index];
            const productRow = document.querySelector(`#products-table tr[data-id="${item.id}"]`);
            const currentStock = parseInt(productRow.dataset.stock) + item.quantity;
            
            if (newQuantity < 1 || newQuantity > currentStock) {
                alert('Invalid quantity');
                return;
            }
            
            const stockDiff = item.quantity - newQuantity;
            item.quantity = newQuantity;
            
            // Update stock display
            const newStock = parseInt(productRow.dataset.stock) + stockDiff;
            productRow.dataset.stock = newStock;
            const badge = productRow.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
            badge.textContent = newStock;
            
            if (newStock === 0) {
                badge.classList.remove('badge-success', 'badge-warning');
                badge.classList.add('badge-danger');
                productRow.querySelector('.add-to-cart').disabled = true;
            } else if (newStock < 10) {
                badge.classList.remove('badge-success', 'badge-danger');
                badge.classList.add('badge-warning');
            } else {
                badge.classList.remove('badge-warning', 'badge-danger');
                badge.classList.add('badge-success');
            }
            
            updateCartItems();
        };
        
        window.removeItem = function(index) {
            const item = cartItems[index];
            const productRow = document.querySelector(`#products-table tr[data-id="${item.id}"]`);
            
            // Update stock display
            const newStock = parseInt(productRow.dataset.stock) + item.quantity;
            productRow.dataset.stock = newStock;
            const badge = productRow.querySelector('td:nth-child(4) .badge'); // Fixed: was 3, should be 4
            badge.textContent = newStock;
            
            if (newStock > 0) {
                badge.classList.remove('badge-danger');
                if (newStock < 10) {
                    badge.classList.add('badge-warning');
                } else {
                    badge.classList.add('badge-success');
                }
                productRow.querySelector('.add-to-cart').disabled = false;
            }
            
            cartItems.splice(index, 1);
            updateCartItems();
        };
        
        // Process order confirmation
        document.getElementById('process-order').addEventListener('click', function() {
            const { grandTotal, change } = calculateFinancials();
            const cashTendered = parseFloat(cashTenderedElement.value) || 0;
            
            if (cashTendered < grandTotal) {
                alert('Insufficient cash tendered');
                return;
            }
            
            // Update modal values
            document.getElementById('modal-total').textContent = grandTotal.toFixed(2);
            document.getElementById('modal-cash').textContent = cashTendered.toFixed(2);
            document.getElementById('modal-change').textContent = change.toFixed(2);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        });
        
        // Confirm order processing
        document.getElementById('confirm-process').addEventListener('click', function() {
            document.getElementById('cart-data').value = JSON.stringify(cartItems);
            document.getElementById('order-form').submit();
        });
        
        // Event listeners for financial updates
        discountElement.addEventListener('change', updateFinancialDisplay);
        cashTenderedElement.addEventListener('input', updateFinancialDisplay);
        
        // Initialize transaction number
        const transactionNo = generateTransactionNumber();
        transactionIdDisplay.textContent = transactionNo;
        transactionIdInput.value = transactionNo;
        
        // Initialize empty cart display
        updateCartItems();
    });
    </script>
</body>
</html>

        <style>
            /* Add this to your existing styles */
#no-products-message {
    margin-top: 10px;
    margin-bottom: 10px;
    border-radius: 8px;
}
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .btn i {
                margin-right: 5px;
            }

            .table-responsive {
                max-height: 400px;
                overflow-y: auto;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-control {
                height: calc(2.25rem + 2px);
                padding: .375rem .75rem;
            }

            .btn-primary,
            .btn-danger,
            .btn-success {
                color: #fff;
            }

            .btn-primary {
                background-color: #007bff;
                border-color: #007bff;
            }

            .btn-danger {
                background-color: #dc3545;
                border-color: #dc3545;
            }

            .btn-success {
                background-color: #28a745;
                border-color: #28a745;
            }

            .btn-secondary {
                background-color: #6c757d;
                border-color: #6c757d;
                color: #fff;
            }

            .btn-secondary:hover,
            .btn-secondary:focus {
                background-color: #565e64;
                border-color: #4e555b;
            }

            /* Add this to your existing styles */
#product-search {
    max-width: 300px;

}

.input-group-text {
    background-color: var(--primary);
    color: white;
    border-color: var(--primary);
}

#product-search:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
}
        </style>

    </section>
</main>