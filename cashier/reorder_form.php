<title>Dashboard</title>
<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php"); // Redirect to login if not authenticated
    exit;
}

// Restrict access to Super Admin or Admin only
if ($_SESSION['user_role'] !== 'Cashier') {
    header("Location: ../403.php"); // Redirect unauthorized users
    exit;
}

$branch_id = $_SESSION['branches_id'];

// Fetch low-stock products for this branch
$sql = "SELECT p.product_name, c.category_name, i.avail_stock, i.critical_level, i.products_id
        FROM inventory i
        INNER JOIN products p ON i.products_id = p.id
        INNER JOIN categories c ON p.categories_id = c.id
        WHERE i.branches_id = ? AND i.avail_stock <= i.critical_level";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();
$medicines = [];
$supplies = [];
while ($row = $result->fetch_assoc()) {
    if (strtolower($row['category_name']) === 'medicine') {
        $medicines[] = $row;
    } else {
        $supplies[] = $row;
    }
}
$stmt->close();

// Include layout components
include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
include '../includes/footer.php';
?>
<main id="main" class="main">
    <section class="dashboard section">
        <div class="container">
            <div class="justify-content-center row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="mt-3 mb-4 text-center">Re-Order Form - Low Stock Products</h2>
                            <div class="print-header">
                                <div><strong>Branch:</strong> <?php echo htmlspecialchars($_SESSION['branch_name'] ?? ''); ?></div>
                                <div><strong>Date/Time:</strong> <?php echo date('Y-m-d H:i'); ?></div>
                            </div>
                            <div class="mb-3 d-flex flex-wrap gap-3 justify-content-end">
                                <form id="emailForm" class="d-flex align-items-center gap-2" method="POST" action="">
                                    <input type="email" name="email_recipient" class="form-control" placeholder="Recipient Email" required style="max-width:220px;">
                                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-envelope"></i> Email Order List</button>
                                </form>
                                <button type="button" class="btn btn-outline-secondary" onclick="exportCSV()"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
                            </div>
                            <form id="reorderForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4>Medicine</h4>
                                        <table class="table table-bordered">
                                            <thead><tr><th>Select</th><th>Product</th><th>Current Stock</th><th>Critical Level</th><th>Order Qty</th></tr></thead>
                                            <tbody>
                                            <?php if (count($medicines) > 0): foreach ($medicines as $med): ?>
                                                <tr>
                                                    <td><input type="checkbox" class="order-checkbox" name="order_medicine[]" value="<?= htmlspecialchars($med['product_name']) ?>" checked></td>
                                                    <td><?= htmlspecialchars($med['product_name']) ?></td>
                                                    <td><?= $med['avail_stock'] ?></td>
                                                    <td><?= $med['critical_level'] ?></td>
                                                    <td><input type="number" name="qty_medicine_<?= htmlspecialchars($med['product_name']) ?>" min="1" value="<?= max(1, $med['critical_level'] - $med['avail_stock'] + 1) ?>" class="form-control" style="width:80px;"></td>
                                                </tr>
                                            <?php endforeach; else: ?>
                                                <tr><td colspan="5" class="text-center text-muted">No low-stock medicines</td></tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h4>Supplies</h4>
                                        <table class="table table-bordered">
                                            <thead><tr><th>Select</th><th>Product</th><th>Current Stock</th><th>Critical Level</th><th>Order Qty</th></tr></thead>
                                            <tbody>
                                            <?php if (count($supplies) > 0): foreach ($supplies as $sup): ?>
                                                <tr>
                                                    <td><input type="checkbox" class="order-checkbox" name="order_supplies[]" value="<?= htmlspecialchars($sup['product_name']) ?>" checked></td>
                                                    <td><?= htmlspecialchars($sup['product_name']) ?></td>
                                                    <td><?= $sup['avail_stock'] ?></td>
                                                    <td><?= $sup['critical_level'] ?></td>
                                                    <td><input type="number" name="qty_supplies_<?= htmlspecialchars($sup['product_name']) ?>" min="1" value="<?= max(1, $sup['critical_level'] - $sup['avail_stock'] + 1) ?>" class="form-control" style="width:80px;"></td>
                                                </tr>
                                            <?php endforeach; else: ?>
                                                <tr><td colspan="5" class="text-center text-muted">No low-stock supplies</td></tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="form-group text-end mt-4">
                                    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print Order List</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #main, #main * {
        visibility: visible !important;
    }
    #main {
        position: absolute;
        left: 0;
        top: 0;
        width: 100vw;
        background: white;
        z-index: 9999;
        padding: 0;
        margin: 0;
    }
    #main .dashboard.section > .container > .justify-content-center {
        margin: 0 !important;
        padding: 0 !important;
    }
    .card, .card-body, .form-group, .row, .col-md-6, .table {
        box-shadow: none !important;
        border: none !important;
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .btn, .form-group.text-end, .form-group.text-end.mt-4, .order-checkbox, input[type=number], thead th:nth-child(1), tbody td:nth-child(1) {
        display: none !important;
    }
    h2, h4 {
        text-align: center;
        margin-bottom: 10px;
    }
    .print-signatory {
        display: block !important;
        margin-top: 40px;
        text-align: right;
        font-size: 1.1em;
    }
    .print-header {
        display: block !important;
        text-align: left;
        margin-bottom: 20px;
        font-size: 1.1em;
    }
    #emailForm {
        display: none !important;
    }
}
.print-signatory {
    display: none;
}
.print-header {
    display: none;
}
</style>

<div class="print-signatory">
    <strong>Prepared by:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?>
</div>

<script>
function exportCSV() {
    let csv = 'Category,Product,Current Stock,Critical Level,Order Qty\n';
    document.querySelectorAll('#reorderForm table').forEach(table => {
        const category = table.previousElementSibling.textContent.trim();
        table.querySelectorAll('tbody tr').forEach(row => {
            if (row.querySelector('input[type=checkbox]') && !row.querySelector('input[type=checkbox]').checked) return;
            const cells = row.querySelectorAll('td');
            if (cells.length < 5) return;
            csv += [category,
                cells[1].textContent.trim(),
                cells[2].textContent.trim(),
                cells[3].textContent.trim(),
                cells[4].querySelector('input') ? cells[4].querySelector('input').value : ''
            ].join(',') + '\n';
        });
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reorder_list.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>

<?php
// Email handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_recipient'])) {
    ob_start();
    echo "Branch: " . htmlspecialchars($_SESSION['branch_name'] ?? '') . "<br>";
    echo "Date/Time: " . date('Y-m-d H:i') . "<br><br>";
    echo "<table border='1' cellpadding='5' cellspacing='0'><tr><th>Category</th><th>Product</th><th>Current Stock</th><th>Critical Level</th><th>Order Qty</th></tr>";
    foreach ([['medicines','Medicine'],['supplies','Supplies']] as $group) {
        foreach (${$group[0]} as $item) {
            $qtyField = 'qty_' . strtolower($group[1]) . '_' . $item['product_name'];
            $qty = $_POST[$qtyField] ?? max(1, $item['critical_level'] - $item['avail_stock'] + 1);
            if (!isset($_POST['order_' . strtolower($group[1])]) || !in_array($item['product_name'], $_POST['order_' . strtolower($group[1])])) continue;
            echo "<tr><td>{$group[1]}</td><td>" . htmlspecialchars($item['product_name']) . "</td><td>{$item['avail_stock']}</td><td>{$item['critical_level']}</td><td>{$qty}</td></tr>";
        }
    }
    echo "</table><br>Prepared by: " . htmlspecialchars($_SESSION['name']);
    $body = ob_get_clean();
    $to = $_POST['email_recipient'];
    $subject = "Re-Order List - " . ($_SESSION['branch_name'] ?? '');
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@dyna-care-sis.local\r\n";
    if (mail($to, $subject, $body, $headers)) {
        echo "<script>alert('Order list sent to " . addslashes($to) . "');</script>";
    } else {
        echo "<script>alert('Failed to send email.');</script>";
    }
}
?>
