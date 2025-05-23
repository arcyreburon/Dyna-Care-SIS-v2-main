<title>Reports</title>
<?php
session_start();
include "../db_conn.php";

// Fetch all branches
$branches_sql = "SELECT id, branch_name FROM branches"; 
$branches_result = $con->query($branches_sql);
$branches = [];
while ($row = $branches_result->fetch_assoc()) {
    $branches[] = $row;
}

// Fetch all categories
$categories_sql = "SELECT id, category_name FROM categories";
$categories_result = $con->query($categories_sql);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Handle the selected filters
$selectedBranch = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';
$selectedCategory = isset($_GET['category_id']) ? $_GET['category_id'] : '';

$sql = "SELECT i.id, i.avail_stock, i.damage_stock, p.product_name, p.expiration_date, 
               b.branch_name, c.category_name, o.brand
        FROM inventory i
        INNER JOIN products p ON i.products_id = p.id
        INNER JOIN branches b ON p.branches_id = b.id
        INNER JOIN categories c ON p.categories_id = c.id
        INNER JOIN others o ON p.id = o.products_id
        WHERE 1=1";

// Apply branch filter if selected
if ($selectedBranch) {
    $sql .= " AND b.id = $selectedBranch";
}

// Apply category filter if selected
if ($selectedCategory) {
    $sql .= " AND c.id = $selectedCategory";
}

$result = $con->query($sql);

// Include layout components
include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
include '../includes/footer.php';
?>

<style>
    /* Minimal table styling */
    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    
    .report-table th {
        text-align: left;
        padding: 0.75rem;
        border-bottom: 1px solid #e0e0e0;
        font-weight: 500;
        background-color: #f8f9fa;
    }
    
    .report-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .report-table tr:last-child td {
        border-bottom: none;
    }
    
    /* Minimal card styling */
    .report-card {
        background: white;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .card-header {
        padding: 1rem;
        border-bottom: 1px solid #e0e0e0;
    }
    
    /* Minimal button styling */
    .btn {
        padding: 0.375rem 0.75rem;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    
    /* Filter controls */
    .filter-control {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .filter-select {
        flex: 1;
        max-width: 200px;
    }
</style>

<main id="main" class="main">
    <div class="container py-3">
        <div class="report-card">
            <div class="card-header">
                <h3>Re-Order Lists</h3>
            </div>
            
            <div class="p-3">
                <div class="filter-control">
                    <form method="GET" action="" class="d-flex gap-2">
                        <select name="type_filter" class="form-select filter-select" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="medicine" <?php echo (isset($_GET['type_filter']) && $_GET['type_filter'] == 'medicine') ? 'selected' : ''; ?>>Medicine</option>
                            <option value="supplies" <?php echo (isset($_GET['type_filter']) && $_GET['type_filter'] == 'supplies') ? 'selected' : ''; ?>>Supplies</option>
                        </select>
                        <button type="button" onclick="exportToExcel()" class="btn btn-primary">Export to Excel</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table id="myTable" class="report-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Type</th>
                                <th>Recipient Email</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Include database connection
                            include "../db_conn.php";

                            // Handle type filter
                            $typeFilter = isset($_GET['type_filter']) ? $_GET['type_filter'] : '';

                            // SQL query to fetch data from orders table with type filter
                            $sql = "SELECT * FROM orders";
                            if (!empty($typeFilter)) {
                                $sql .= " WHERE type = ?";
                            }

                            $stmt = $con->prepare($sql);
                            if (!empty($typeFilter)) {
                                $stmt->bind_param("s", $typeFilter);
                            }
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $type = ucfirst($row['type']); // Capitalize the first letter of the type
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['product_name']) . "</td>
                                        <td>" . $row['quantity'] . "</td>
                                        <td>" . htmlspecialchars($type) . "</td>
                                        <td>" . htmlspecialchars($row['recipient_email']) . "</td>
                                        <td>" . $row['order_date'] . "</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center py-3'>No orders found</td></tr>";
                            }

                            $stmt->close();
                            $con->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- DataTable Script -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        new simpleDatatables.DataTable("#myTable", {
            perPage: 10,
            labels: {
                placeholder: "Search...",
                perPage: "{select} entries per page",
                noRows: "No entries found",
                info: "Showing {start} to {end} of {rows} entries"
            }
        });
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
    function exportToExcel() {
        // Get the table element
        var table = document.getElementById("myTable");

        // Convert the table to a worksheet
        var ws = XLSX.utils.table_to_sheet(table);

        // Create a new workbook and append the worksheet
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Reports");

        // Write the workbook and save it
        var wbout = XLSX.write(wb, { bookType: "xlsx", type: "array" });
        saveAs(new Blob([wbout], { type: "application/octet-stream" }), "Reports.xlsx");
    }
</script>