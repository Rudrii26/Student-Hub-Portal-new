<?php
/**
 * CHARUSAT Student Hub - Central Dashboard Dispatcher (dashboard.php)
 * Validates active session, enforces inactivity timeout, and dispatches to role-specific dashboards.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Enforce 15-minute session timeout guard
require_once __DIR__ . '/timeout.php';

// 2. Enforce authentication guard
if (!isset($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// 3. Dispatch to authorized role dashboard
if ($_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
} else {
    header("Location: student_dashboard.php");
    exit;
}
