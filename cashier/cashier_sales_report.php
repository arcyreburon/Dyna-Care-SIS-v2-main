<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Cashier', 'Admin', 'Super Admin'])) {
    header("Location: ../index.php");
    exit;
}
$branch_id = $_SESSION['branches_id'];
$branch_name = $_SESSION['branch_name'] ?? '';
$name = $_SESSION['name'] ?? '';

// Date range filter
$period = $_GET['period'] ?? 'daily';
$today = date('Y-m-d');
$start = $end = $today;
$label = 'Today';

switch ($period) {
    case 'weekly':
        $start = date('Y-m-d', strtotime('monday this week'));
        $end = date('Y-m-d', strtotime('sunday this week'));
        $label = 'This Week';
        break;
    case 'monthly':
        $start = date('Y-m-01');
        $end = date('Y-m-t');
        $label = 'This Month';
        break;
    case 'yearly':
        $start = date('Y-01-01');
        $end = date('Y-12-31');
        $label = 'This Year';
        break;
    default:
        $start = $end = $today;
        $label = 'Today';
}

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

// Update SQL to filter by branch for Super Admin
if ($_SESSION['user_role'] === 'Super Admin') {
    if ($selected_branch) {
        $sql = "SELECT t.id, t.transaction_no, t.total_price, t.discount, t.date, p.product_name, b.branch_name
                FROM transaction t
                JOIN products p ON t.products_id = p.id
                JOIN branches b ON p.branches_id = b.id
                WHERE p.branches_id = ? AND DATE(t.date) BETWEEN ? AND ?
                ORDER BY t.date DESC";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("iss", $selected_branch, $start, $end);
    } else {
        $sql = "SELECT t.id, t.transaction_no, t.total_price, t.discount, t.date, p.product_name, b.branch_name
                FROM transaction t
                JOIN products p ON t.products_id = p.id
                JOIN branches b ON p.branches_id = b.id
                WHERE DATE(t.date) BETWEEN ? AND ?
                ORDER BY t.date DESC";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ss", $start, $end);
    }
} else {
    $sql = "SELECT t.id, t.transaction_no, t.total_price, t.discount, t.date, p.product_name
            FROM transaction t
            JOIN products p ON t.products_id = p.id
            WHERE p.branches_id = ? AND DATE(t.date) BETWEEN ? AND ?
            ORDER BY t.date DESC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("iss", $branch_id, $start, $end);
}
$stmt->execute();
$result = $stmt->get_result();
$sales = [];
while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
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
                                <h2 class="mt-2 mb-1 text-center report-title">Sales Report</h2>
                                <div class="text-center mb-2 report-subtitle">
                                    <span class="fw-bold">Branch:</span> <?= htmlspecialchars($branch_name) ?> &nbsp;|&nbsp;
                                    <span class="fw-bold">Period:</span> <?= htmlspecialchars($label) ?> (<?= $start ?> to <?= $end ?>)
                                </div>
                                <div class="text-center mb-3 report-meta">
                                    <span class="fw-bold">Report generated on:</span> <?= date('Y-m-d H:i') ?>
                                </div>
                                <?php if ($_SESSION['user_role'] === 'Super Admin'): ?>
                                <form method="get" class="mb-3 d-flex gap-2 flex-wrap justify-content-end">
                                    <select name="branch_id" class="form-select" style="max-width:220px;">
                                        <option value="">All Branches</option>
                                        <?php foreach ($branches as $b): ?>
                                            <option value="<?= $b['id'] ?>" <?= ($selected_branch == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="period" class="form-select" style="max-width:200px;">
                                        <option value="daily" <?= $period=='daily'?'selected':'' ?>>Daily</option>
                                        <option value="weekly" <?= $period=='weekly'?'selected':'' ?>>Weekly</option>
                                        <option value="monthly" <?= $period=='monthly'?'selected':'' ?>>Monthly</option>
                                        <option value="yearly" <?= $period=='yearly'?'selected':'' ?>>Yearly</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                                    <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                                </form>
                                <?php endif; ?>
                                <table class="table table-bordered table-striped w-100 report-table" id="sales-table" style="min-width:900px;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Transaction #</th>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Discount</th>
                                            <th>Net Amount</th>
                                            <th>Date/Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $total = 0;
                                    foreach ($sales as $row):
                                        $net = $row['total_price'] - ($row['total_price'] * ($row['discount']/100));
                                        $total += $net;
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['transaction_no']) ?></td>
                                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                                            <td>₱<?= number_format($row['total_price'],2) ?></td>
                                            <td><?= $row['discount'] ?>%</td>
                                            <td>₱<?= number_format($net,2) ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($row['date'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($sales)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No sales found for this period.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-secondary">
                                            <th colspan="4" class="text-end">Total Sales:</th>
                                            <th colspan="2">₱<?= number_format($total,2) ?></th>
                                        </tr>
                                    </tfoot>
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
.report-logo {
    display: block;
    margin: 0 auto 10px auto;
    max-width: 120px;
    max-height: 120px;
}
.report-title {
    text-align: center;
    margin-bottom: 8px;
    font-size: 2.1em;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-weight: 700;
    letter-spacing: 1px;
}
.report-subtitle, .report-meta {
    text-align: center;
    font-size: 1.08em;
    margin-bottom: 6px;
}
</style> 