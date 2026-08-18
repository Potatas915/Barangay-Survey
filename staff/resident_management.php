<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT resident_id, resident_number, first_name, last_name, email, contact_number FROM residents WHERE resident_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? ORDER BY last_name");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $residents = $stmt->get_result();
} else {
    $residents = $conn->query("SELECT resident_id, resident_number, first_name, last_name, email, contact_number FROM residents ORDER BY last_name");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resident Management</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>
    <div class="card card-resident">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Registered Residents</h2>
            <a class="btn" href="register.php">+ Register New Resident</a>
        </div>
        <form method="GET" style="margin-bottom:16px;">
            <input type="text" name="search" placeholder="Search by name or resident number" value="<?= e($search) ?>">
        </form>
        <div class="table-scroll">
        <table>
            <tr><th>Resident Number</th><th>Name</th><th>Email</th><th>Contact</th></tr>
            <?php while ($r = $residents->fetch_assoc()): ?>
            <tr>
                <td><?= e($r["resident_number"]) ?></td>
                <td><?= e($r["first_name"] . " " . $r["last_name"]) ?></td>
                <td><?= e($r["email"]) ?></td>
                <td><?= e($r["contact_number"]) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>