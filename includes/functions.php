<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Guard: only logged-in residents can view this page
function require_resident_login() {
    if (!isset($_SESSION["resident_id"])) {
        redirect("../resident/login.php");
    }
}

// Guard: only logged-in staff can view this page
function require_staff_login() {
    if (!isset($_SESSION["staff_id"])) {
        redirect("../staff/login.php");
    }
}

// Escape output to prevent XSS when printing user data in HTML
function e($string) {
    return htmlspecialchars($string ?? "", ENT_QUOTES, "UTF-8");
}

// Backend guard: a survey start date can never be earlier than today,
// even if the date picker on the frontend gets bypassed.
function is_valid_start_date($start_date) {
    $today = date("Y-m-d");
    return $start_date >= $today;
}

// ---------------------------------------------------------
// Dashboard helpers: everything below reads real rows from the
// database (using created_at / submitted_at / start_date / end_date)
// so the "vs last month" trends and sparklines on the staff
// dashboard reflect actual data, not placeholders.
// ---------------------------------------------------------

// [start, end, label] for the calendar month that is $monthsAgo months
// before the current one. monthsAgo = 0 is the current (partial) month.
function month_bounds($monthsAgo) {
    $start = new DateTime("first day of -$monthsAgo month");
    $end = ($monthsAgo === 0) ? new DateTime() : new DateTime("last day of -$monthsAgo month");
    return [$start->format("Y-m-d 00:00:00"), $end->format("Y-m-d 23:59:59"), $start->format("M Y")];
}

// Percentage change between two counts, safe against division by zero.
function pct_change($current, $previous) {
    if ($previous <= 0) {
        return ["pct" => $current > 0 ? 100 : 0, "up" => $current >= $previous];
    }
    $pct = (($current - $previous) / $previous) * 100;
    return ["pct" => round(abs($pct), 1), "up" => $pct >= 0];
}

// Builds a small inline SVG sparkline from an array of numeric points.
function sparkline_svg($points, $width = 90, $height = 32, $color = "#1fae82") {
    $count = count($points);
    if ($count < 2) return "";
    $min = min($points);
    $max = max($points);
    $range = ($max - $min) ?: 1;
    $step = $width / ($count - 1);
    $coords = [];
    foreach ($points as $i => $p) {
        $x = round($i * $step, 1);
        $y = round($height - (($p - $min) / $range) * ($height - 4) - 2, 1);
        $coords[] = "$x,$y";
    }
    $lastX = ($count - 1) * $step;
    $lastY = round($height - (($points[$count - 1] - $min) / $range) * ($height - 4) - 2, 1);
    $polyline = implode(" ", $coords);
    return '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="sparkline" preserveAspectRatio="none">'
        . '<polyline points="' . $polyline . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
        . '<circle cx="' . $lastX . '" cy="' . $lastY . '" r="2.5" fill="' . $color . '"/>'
        . '</svg>';
}
?>
