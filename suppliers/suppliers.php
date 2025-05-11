<?php
session_start();
include '../db_conn.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ["Super Admin", "Admin"])) {
    header("Location: ../403.php");
    exit;
}

// Handle archive supplier
if (isset($_GET['archive_id'])) {
    $archive_id = intval($_GET['archive_id']);
    $con->query("UPDATE suppliers SET is_active = 0 WHERE id = $archive_id");
    $_SESSION['message'] = "Supplier archived (inactivated).";
    $_SESSION['message_type'] = "warning";
    header("Location: suppliers.php");
    exit;
}

// Handle activate supplier
if (isset($_GET['activate_id'])) {
    $activate_id = intval($_GET['activate_id']);
    $con->query("UPDATE suppliers SET is_active = 1 WHERE id = $activate_id");
    $_SESSION['message'] = "Supplier activated.";
    $_SESSION['message_type'] = "success";
    header("Location: suppliers.php");
    exit;
}

// Handle update supplier
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $name = trim($_POST['edit_name']);
    $contact_person = trim($_POST['edit_contact_person']);
    $contact_number = trim($_POST['edit_contact_number']);
    if ($name && $contact_person && $contact_number) {
        $stmt = $con->prepare("UPDATE suppliers SET name=?, contact_person=?, contact_number=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $contact_person, $contact_number, $edit_id);
        $stmt->execute();
        $_SESSION['message'] = "Supplier updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: suppliers.php");
        exit;
    } else {
        $_SESSION['message'] = "All fields are required for update.";
        $_SESSION['message_type'] = "danger";
    }
}

// Handle add supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit_id'])) {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_number = trim($_POST['contact_number']);
    if ($name && $contact_person && $contact_number) {
        $stmt = $con->prepare("INSERT INTO suppliers (name, contact_person, contact_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $contact_person, $contact_number);
        $stmt->execute();
        $_SESSION['message'] = "Supplier added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: suppliers.php");
        exit;
    } else {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['message_type'] = "danger";
    }
}

// Fetch all suppliers
$suppliers = [];
$result = $con->query("SELECT * FROM suppliers ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row;
}

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>
<main id="main" class="main">
    <section class="dashboard section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">Add Supplier</div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                                    <?= $_SESSION['message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Supplier Name / Company <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_person" class="form-label">Contact Person <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Supplier</button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">Supplier List</div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Name / Company</th>
                                        <th>Contact Person</th>
                                        <th>Contact Number</th>
                                        <th>Date Added</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($supplier['name']) ?></td>
                                            <td><?= htmlspecialchars($supplier['contact_person']) ?></td>
                                            <td><?= htmlspecialchars($supplier['contact_number']) ?></td>
                                            <td><?= htmlspecialchars($supplier['created_at']) ?></td>
                                            <td>
                                                <?php if (isset($supplier['is_active']) && !$supplier['is_active']): ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!isset($supplier['is_active']) || $supplier['is_active']): ?>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $supplier['id'] ?>">Edit</button>
                                                        &nbsp;
                                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#archiveModal<?= $supplier['id'] ?>">Archive</button>
                                                    </div>
                                                <?php else: ?>
                                                    <a href="?activate_id=<?= $supplier['id'] ?>" class="btn btn-sm btn-success">Activate</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?= $supplier['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $supplier['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?= $supplier['id'] ?>">Edit Supplier</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="edit_id" value="<?= $supplier['id'] ?>">
                                                            <div class="mb-3">
                                                                <label for="edit_name<?= $supplier['id'] ?>" class="form-label">Supplier Name / Company</label>
                                                                <input type="text" class="form-control" id="edit_name<?= $supplier['id'] ?>" name="edit_name" value="<?= htmlspecialchars($supplier['name']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_contact_person<?= $supplier['id'] ?>" class="form-label">Contact Person</label>
                                                                <input type="text" class="form-control" id="edit_contact_person<?= $supplier['id'] ?>" name="edit_contact_person" value="<?= htmlspecialchars($supplier['contact_person']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_contact_number<?= $supplier['id'] ?>" class="form-label">Contact Number</label>
                                                                <input type="text" class="form-control" id="edit_contact_number<?= $supplier['id'] ?>" name="edit_contact_number" value="<?= htmlspecialchars($supplier['contact_number']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Archive Modal -->
                                        <div class="modal fade" id="archiveModal<?= $supplier['id'] ?>" tabindex="-1" aria-labelledby="archiveModalLabel<?= $supplier['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="archiveModalLabel<?= $supplier['id'] ?>">Archive Supplier</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to archive <b><?= htmlspecialchars($supplier['name']) ?></b>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="?archive_id=<?= $supplier['id'] ?>" class="btn btn-danger">Archive</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($suppliers)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No suppliers found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include '../includes/footer.php'; ?> 