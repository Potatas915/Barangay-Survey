<?php
require_once __DIR__ . "/../includes/functions.php";
require_resident_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submission Confirmed</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<div class="login-wrapper">
    <div class="login-box" style="text-align:center;">
        <h2>Thank You!</h2>
        <p>Your survey response has been submitted successfully.</p>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</div>
</body>
</html>