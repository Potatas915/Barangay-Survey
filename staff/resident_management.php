<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

// Reset a resident's password back to their resident number (matches the
// "default password is also their Resident Number" rule from first login).
if (isset($_GET["reset_password"])) {
    $resident_id = (int)$_GET["reset_password"];
    $stmt = $conn->prepare("SELECT resident_number FROM residents WHERE resident_id = ?");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $hashed = password_hash($row["resident_number"], PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE residents SET password = ?, is_first_login = 1 WHERE resident_id = ?");
        $update->bind_param("si", $hashed, $resident_id);
        $update->execute();
    }
    redirect("resident_management.php?reset=1");
}

// Delete a resident record entirely (their photo file and children rows
// are cleaned up too; children cascade automatically via the FK).
if (isset($_GET["delete"])) {
    $resident_id = (int)$_GET["delete"];
    $stmt = $conn->prepare("SELECT photo FROM residents WHERE resident_id = ?");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        delete_resident_photo($row["photo"]);
        $del = $conn->prepare("DELETE FROM residents WHERE resident_id = ?");
        $del->bind_param("i", $resident_id);
        $del->execute();
    }
    redirect("resident_management.php?deleted=1");
}

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT resident_id, resident_number, first_name, middle_name, last_name, extension_name, email, contact_number, updated_at FROM residents WHERE resident_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? ORDER BY last_name");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $residents = $stmt->get_result();
} else {
    $residents = $conn->query("SELECT resident_id, resident_number, first_name, middle_name, last_name, extension_name, email, contact_number, updated_at FROM residents ORDER BY last_name");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resident Management</title>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>
    <div class="card card-resident">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <h2>Registered Residents</h2>
            <div class="table-actions">
                <a class="btn btn-sm btn-secondary" href="updated_records.php">View Updated Records</a>
                <a class="btn btn-sm btn-secondary" href="resident_export.php">Export CSV</a>
                <a class="btn" href="register.php">+ Register New Resident</a>
            </div>
        </div>

        <?php if (isset($_GET["reset"])): ?>
            <div class="success">Resident's password has been reset to their Resident Number.</div>
        <?php endif; ?>
        <?php if (isset($_GET["deleted"])): ?>
            <div class="success">Resident record deleted.</div>
        <?php endif; ?>

        <form method="GET" style="margin-bottom:16px;">
            <input type="text" name="search" placeholder="Search by name or resident number" value="<?= e($search) ?>">
        </form>
        <div class="table-scroll">
        <table>
            <tr><th>Resident Number</th><th>Name</th><th>Email</th><th>Contact</th><th>Last Updated</th><th>Actions</th></tr>
            <?php while ($r = $residents->fetch_assoc()): ?>
            <tr>
                <td><?= e($r["resident_number"]) ?></td>
                <td><?= e(full_resident_name($r)) ?></td>
                <td><?= e($r["email"]) ?></td>
                <td><?= e($r["contact_number"]) ?></td>
                <td><?= $r["updated_at"] ? e(date("M d, Y g:i A", strtotime($r["updated_at"]))) : "&mdash;" ?></td>
                <td>
                    <div class="table-actions">
                        <a class="btn btn-sm btn-secondary" href="resident_view.php?resident_id=<?= (int)$r["resident_id"] ?>">View</a>
                        <a class="btn btn-sm btn-secondary" href="resident_edit.php?resident_id=<?= (int)$r["resident_id"] ?>">Edit</a>
                        <?php
                            $resident_name_js = htmlspecialchars(addslashes(full_resident_name($r)), ENT_QUOTES, "UTF-8");
                        ?>
                        <button type="button" class="btn btn-sm btn-success-soft"
                            onclick="openConfirmModal({
                                url: 'resident_management.php?reset_password=<?= (int)$r["resident_id"] ?>',
                                title: 'Reset password?',
                                message: 'This will reset \u201c<?= $resident_name_js ?>\u201d\'s password back to their Resident Number and require them to change it on next login.',
                                confirmLabel: 'Reset Password',
                                danger: false
                            })">Reset Password</button>
                        <button type="button" class="btn btn-sm btn-danger"
                            onclick="openConfirmModal({
                                url: 'resident_management.php?delete=<?= (int)$r["resident_id"] ?>',
                                title: 'Delete this resident?',
                                message: 'This will permanently delete \u201c<?= $resident_name_js ?>\u201d and all of their saved information. This cannot be undone.',
                                confirmLabel: 'Delete',
                                danger: true
                            })">Delete</button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="confirmModal">
    <div class="modal-box modal-sm">
        <div class="modal-icon" id="confirmModalIcon"></div>
        <h3 id="confirmModalTitle">Are you sure?</h3>
        <p class="modal-message" id="confirmModalMessage"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmModalConfirmBtn" onclick="proceedConfirmModal()">Confirm</button>
        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
</body>
</html>
