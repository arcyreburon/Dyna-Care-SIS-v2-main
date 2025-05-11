<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Inventory Clerk', 'Admin', 'Super Admin'])) {
    header("Location: ../index.php");
    exit;
}
$branch_id = $_SESSION['branches_id'];
$branch_name = $_SESSION['branch_name'] ?? '';
$name = $_SESSION['name'] ?? '';

// Branch filter for Super Admin
$selected_branch = $_GET['branch_id'] ?? ($branch_id ?? '');
$branches = [];
if ($_SESSION['user_role'] === 'Super Admin') {
    $branches_sql = "SELECT id, branch_name FROM branches ORDER BY branch_name";
    $branches_result = $con->query($branches_sql);
    while ($b = $branches_result->fetch_assoc()) {
        $branches[] = $b;
    }
}

// Fetch inventory for this branch
if ($_SESSION['user_role'] === 'Super Admin') {
    if ($selected_branch) {
        $sql = "SELECT p.product_name, c.category_name, i.avail_stock, i.damage_stock, i.critical_level, i.expiration_date, b.branch_name
                FROM inventory i
                INNER JOIN products p ON i.products_id = p.id
                INNER JOIN categories c ON p.categories_id = c.id
                INNER JOIN branches b ON i.branches_id = b.id
                WHERE i.branches_id = ?
                ORDER BY c.category_name, p.product_name";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $selected_branch);
    } else {
        $sql = "SELECT p.product_name, c.category_name, i.avail_stock, i.damage_stock, i.critical_level, i.expiration_date, b.branch_name
                FROM inventory i
                INNER JOIN products p ON i.products_id = p.id
                INNER JOIN categories c ON p.categories_id = c.id
                INNER JOIN branches b ON i.branches_id = b.id
                ORDER BY b.branch_name, c.category_name, p.product_name";
        $stmt = $con->prepare($sql);
    }
} else {
    $sql = "SELECT p.product_name, c.category_name, i.avail_stock, i.damage_stock, i.critical_level, i.expiration_date
            FROM inventory i
            INNER JOIN products p ON i.products_id = p.id
            INNER JOIN categories c ON p.categories_id = c.id
            WHERE i.branches_id = ?
            ORDER BY c.category_name, p.product_name";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $branch_id);
}
$stmt->execute();
$result = $stmt->get_result();
$inventory = [];
while ($row = $result->fetch_assoc()) {
    $inventory[] = $row;
}
$stmt->close();

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>
<main id="main" class="main">
    <section class="dashboard section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="report-print-wrapper">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="mt-2 mb-1 text-center report-title">Inventory Report</h2>
                                <?php if ($_SESSION['user_role'] === 'Super Admin'): ?>
                                <form method="get" class="mb-3 d-flex gap-2 flex-wrap justify-content-end">
                                    <select name="branch_id" class="form-select" style="max-width:220px;">
                                        <option value="">All Branches</option>
                                        <?php foreach ($branches as $b): ?>
                                            <option value="<?= $b['id'] ?>" <?= ($selected_branch == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                                    <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                                </form>
                                <?php else: ?>
                                <div class="mb-3 d-flex justify-content-end no-print">
                                    <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                                </div>
                                <?php endif; ?>
                                <div class="text-center mb-2 report-subtitle">
                                    <span class="fw-bold">Branch:</span> <?= htmlspecialchars($branch_name) ?>
                                </div>
                                <div class="text-center mb-3 report-meta">
                                    <span class="fw-bold">Report generated on:</span> <?= date('Y-m-d H:i') ?>
                                </div>
                                <table class="table table-bordered table-striped w-100 report-table" id="inventory-table" style="min-width:900px;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <?php if ($_SESSION['user_role'] === 'Super Admin' && !$selected_branch): ?>
                                            <th>Branch</th>
                                            <?php endif; ?>
                                            <th>Available Stock</th>
                                            <th>Damaged Stock</th>
                                            <th>Critical Level</th>
                                            <th>Expiration Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($inventory as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                                            <td><?= htmlspecialchars($row['category_name']) ?></td>
                                            <?php if ($_SESSION['user_role'] === 'Super Admin' && !$selected_branch): ?>
                                            <td><?= htmlspecialchars($row['branch_name']) ?></td>
                                            <?php endif; ?>
                                            <td><?= htmlspecialchars($row['avail_stock']) ?></td>
                                            <td><?= htmlspecialchars($row['damage_stock']) ?></td>
                                            <td><?= htmlspecialchars($row['critical_level']) ?></td>
                                            <td><?= htmlspecialchars($row['expiration_date']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($inventory)): ?>
                                        <tr><td colspan="<?= ($_SESSION['user_role'] === 'Super Admin' && !$selected_branch) ? 7 : 6 ?>" class="text-center text-muted">No inventory found for this branch.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                                <!-- Print-only signatory -->
                                <div class="print-signatory">
                                    Prepared by: <strong><?= htmlspecialchars($name) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<style>
@media print {
    body, html {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .navbar, nav, header, .topbar, .main-header, [role='navigation'], [class*='nav'], [class*='header'] {
        display: none !important;
    }
    *[style*='position:fixed'], *[style*='position: absolute'] {
        display: none !important;
    }
    .sidebar, footer, .no-print, .btn, form, .form-select {
        display: none !important;
    }
    img {
        display: none !important;
    }
    main.main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100vw !important;
    }
    .container, .row, .col-lg-12 {
        margin: 0 !important;
        padding: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
    }
    .report-print-wrapper {
        margin: 0 auto !important;
        padding: 0 !important;
        max-width: 100vw !important;
        box-shadow: none !important;
        background: white !important;
    }
    .card, .card-body {
        box-shadow: none !important;
        border: none !important;
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .print-signatory {
        display: block !important;
        margin-top: 18px !important;
        margin-bottom: 0 !important;
        text-align: right !important;
        font-size: 1.1em !important;
        font-family: 'Segoe UI', Arial, sans-serif !important;
    }
}
.print-signatory {
    display: none;
}
</style> 