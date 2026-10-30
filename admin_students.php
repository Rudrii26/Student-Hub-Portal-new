<?php
/**
 * CHARUSAT Student Hub - Administrative Student Management (admin_students.php)
 * Full CRUD, multi-criteria search, department filtering, status toggling,
 * soft delete/restore workflows, and CSV export integration.
 * Enforces strict Admin RBAC, prepared statements, and CSRF protection.
 * Employs the unified CHARUSAT portal design system with responsive sidebar layout.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Enforce 15-minute inactivity session timeout
require_once __DIR__ . '/timeout.php';

// 2. Enforce Authentication & Admin Role Boundary Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - Access Denied</title>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/portal.css">
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="landing-body">
        <div class="access-denied-container">
            <h1 style="color: #b91c1c;">HTTP 403: Forbidden</h1>
            <p>You must be an authenticated Administrator to manage student records.</p>
            <a href="login.php" class="login-button" style="text-decoration:none; padding:10px 20px;">Return to Login</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Generate CSRF Token for state-changing operations
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

require_once __DIR__ . '/db.php';
$conn = getMysqliConnection();

// Admin session details for consistent sidebar display
$adminName     = $_SESSION['full_name'] ?? 'CHARUSAT Administrator';
$adminUsername = $_SESSION['username'] ?? 'ADMIN_CHARUSAT';
$parts = preg_split('/\s+/', trim($adminName));
$initials = '';
foreach ($parts as $p) {
    if ($p !== '') $initials .= strtoupper(mb_substr($p, 0, 1));
}
if (strlen($initials) > 2) $initials = substr($initials, 0, 1) . substr($initials, -1);
if ($initials === '') $initials = 'AD';
$shortName = count($parts) > 2 ? $parts[0] . ' ' . $parts[count($parts) - 1] : $adminName;

// Flash messages
$errorMessage   = '';
$successMessage = '';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $successMessage = "Student account created and enrolled successfully.";
            break;
        case 'updated':
            $successMessage = "Student details updated successfully.";
            break;
        case 'deleted':
            $successMessage = "Student record has been soft-deleted. The student can no longer sign in, but all records are safely preserved.";
            break;
        case 'restored':
            $successMessage = "Student record has been restored to Active status.";
            break;
    }
}

if (isset($_GET['err'])) {
    $errorMessage = htmlspecialchars($_GET['err'], ENT_QUOTES, 'UTF-8');
}

// ------------------------------------------------------------------------------
// 3. Handle POST Actions (Add, Edit, Soft Delete, Restore)
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        header("Location: admin_students.php?err=" . urlencode("Security verification failed (Invalid CSRF token). Please try again."));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Action A: Add New Student
    if ($action === 'add_student') {
        $username      = strtoupper(trim((string)($_POST['username'] ?? '')));
        $fullName      = trim((string)($_POST['full_name'] ?? ''));
        $email         = strtolower(trim((string)($_POST['email'] ?? '')));
        $mobile        = trim((string)($_POST['mobile'] ?? ''));
        $course        = trim((string)($_POST['course'] ?? ''));
        $academicYear  = trim((string)($_POST['academic_year'] ?? ''));
        $gender        = trim((string)($_POST['gender'] ?? 'Other'));
        $password      = (string)($_POST['password'] ?? '');

        if ($username === '' || $fullName === '' || $email === '' || $mobile === '' || $course === '' || $academicYear === '' || $password === '') {
            header("Location: admin_students.php?err=" . urlencode("All required fields, including password, must be provided."));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: admin_students.php?err=" . urlencode("Please enter a valid official email address."));
            exit;
        }

        if (strlen($password) < 8) {
            header("Location: admin_students.php?err=" . urlencode("Password must be at least 8 characters long."));
            exit;
        }

        // Check uniqueness of username and email
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $checkStmt->bind_param('ss', $username, $email);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        if ($checkRes && $checkRes->num_rows > 0) {
            $checkStmt->close();
            header("Location: admin_students.php?err=" . urlencode("A student account with this Student ID or Email already exists."));
            exit;
        }
        $checkStmt->close();

        // Create random registration reference
        $registrationId = 'REG-' . date('Y') . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
        $passwordHash   = password_hash($password, PASSWORD_DEFAULT);
        $role           = 'student';
        $status         = 'Active';
        $isDeleted      = 0;

        $insertStmt = $conn->prepare("
            INSERT INTO users (
                registration_id, username, full_name, email, mobile,
                course, academic_year, gender, role, password_hash, status, is_deleted
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->bind_param(
            'sssssssssssi',
            $registrationId, $username, $fullName, $email, $mobile,
            $course, $academicYear, $gender, $role, $passwordHash, $status, $isDeleted
        );

        if ($insertStmt->execute()) {
            $insertStmt->close();
            header("Location: admin_students.php?msg=added");
            exit;
        } else {
            $err = $insertStmt->error;
            $insertStmt->close();
            header("Location: admin_students.php?err=" . urlencode("Failed to enroll student: " . $err));
            exit;
        }
    }

    // Action B: Edit Student Details
    if ($action === 'edit_student') {
        $studentDbId   = (int)($_POST['student_db_id'] ?? 0);
        $fullName      = trim((string)($_POST['full_name'] ?? ''));
        $email         = strtolower(trim((string)($_POST['email'] ?? '')));
        $mobile        = trim((string)($_POST['mobile'] ?? ''));
        $course        = trim((string)($_POST['course'] ?? ''));
        $academicYear  = trim((string)($_POST['academic_year'] ?? ''));
        $gender        = trim((string)($_POST['gender'] ?? 'Other'));
        $newPassword   = (string)($_POST['new_password'] ?? '');

        if ($studentDbId <= 0 || $fullName === '' || $email === '' || $mobile === '' || $course === '' || $academicYear === '') {
            header("Location: admin_students.php?err=" . urlencode("Invalid update parameters. Please complete all required fields."));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: admin_students.php?err=" . urlencode("Please provide a valid email address."));
            exit;
        }

        // Verify duplicate email on other users
        $dupStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $dupStmt->bind_param('si', $email, $studentDbId);
        $dupStmt->execute();
        $dupRes = $dupStmt->get_result();
        if ($dupRes && $dupRes->num_rows > 0) {
            $dupStmt->close();
            header("Location: admin_students.php?err=" . urlencode("Email address is already in use by another user."));
            exit;
        }
        $dupStmt->close();

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                header("Location: admin_students.php?err=" . urlencode("New password must be at least 8 characters long."));
                exit;
            }
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upStmt = $conn->prepare("
                UPDATE users
                SET full_name = ?, email = ?, mobile = ?, course = ?, academic_year = ?, gender = ?, password_hash = ?
                WHERE id = ? AND role = 'student'
            ");
            $upStmt->bind_param('sssssssi', $fullName, $email, $mobile, $course, $academicYear, $gender, $newHash, $studentDbId);
        } else {
            $upStmt = $conn->prepare("
                UPDATE users
                SET full_name = ?, email = ?, mobile = ?, course = ?, academic_year = ?, gender = ?
                WHERE id = ? AND role = 'student'
            ");
            $upStmt->bind_param('ssssssi', $fullName, $email, $mobile, $course, $academicYear, $gender, $studentDbId);
        }

        if ($upStmt->execute()) {
            $upStmt->close();
            header("Location: admin_students.php?msg=updated");
            exit;
        } else {
            $err = $upStmt->error;
            $upStmt->close();
            header("Location: admin_students.php?err=" . urlencode("Failed to update student: " . $err));
            exit;
        }
    }

    // Action C: Soft Delete Student
    if ($action === 'soft_delete') {
        $studentDbId = (int)($_POST['student_db_id'] ?? 0);
        if ($studentDbId <= 0) {
            header("Location: admin_students.php?err=" . urlencode("Invalid student selection for soft delete."));
            exit;
        }

        $delStmt = $conn->prepare("
            UPDATE users 
            SET is_deleted = 1, deleted_at = NOW(), status = 'Deleted'
            WHERE id = ? AND role = 'student'
        ");
        $delStmt->bind_param('i', $studentDbId);
        $delStmt->execute();
        $delStmt->close();

        header("Location: admin_students.php?msg=deleted");
        exit;
    }

    // Action D: Restore Soft-Deleted Student
    if ($action === 'restore') {
        $studentDbId = (int)($_POST['student_db_id'] ?? 0);
        if ($studentDbId <= 0) {
            header("Location: admin_students.php?err=" . urlencode("Invalid student selection for restore."));
            exit;
        }

        $resStmt = $conn->prepare("
            UPDATE users 
            SET is_deleted = 0, deleted_at = NULL, status = 'Active'
            WHERE id = ? AND role = 'student'
        ");
        $resStmt->bind_param('i', $studentDbId);
        $resStmt->execute();
        $resStmt->close();

        header("Location: admin_students.php?msg=restored");
        exit;
    }
}

// ------------------------------------------------------------------------------
// 4. Query Students with Search & Filtering
// ------------------------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$course = trim($_GET['course'] ?? 'all');
$status = trim($_GET['status'] ?? 'all'); // 'all', 'active', 'deleted'

$whereClauses = ["`role` = 'student'"];
$paramTypes   = '';
$paramValues  = [];

if ($search !== '') {
    $whereClauses[] = "(`username` LIKE ? OR `full_name` LIKE ? OR `email` LIKE ?)";
    $searchWildcard = '%' . $search . '%';
    $paramTypes    .= 'sss';
    $paramValues[]  = $searchWildcard;
    $paramValues[]  = $searchWildcard;
    $paramValues[]  = $searchWildcard;
}

if ($course !== '' && $course !== 'all') {
    $whereClauses[] = "`course` = ?";
    $paramTypes    .= 's';
    $paramValues[]  = $course;
}

if ($status === 'active') {
    $whereClauses[] = "(`is_deleted` = 0 AND `status` != 'Deleted')";
} elseif ($status === 'deleted') {
    $whereClauses[] = "(`is_deleted` = 1 OR `status` = 'Deleted')";
}

$whereSql = implode(' AND ', $whereClauses);
$querySql = "
    SELECT `id`, `registration_id`, `username`, `full_name`, `email`, 
           `mobile`, `course`, `academic_year`, `gender`, `status`, `is_deleted`, `deleted_at`, `created_at`
    FROM `users`
    WHERE {$whereSql}
    ORDER BY `is_deleted` ASC, `id` DESC
";

$stmt = $conn->prepare($querySql);
if (!empty($paramValues)) {
    $stmt->bind_param($paramTypes, ...$paramValues);
}
$stmt->execute();
$studentsResult = $stmt->get_result();
$students = [];
if ($studentsResult) {
    while ($row = $studentsResult->fetch_assoc()) {
        $students[] = $row;
    }
}
$stmt->close();

// Distinct courses for dropdown filter
$courseList = [
    'B.Tech Computer Engineering (CSPIT)',
    'B.Tech Information Technology (CSPIT)',
    'B.Tech Computer Science & Engineering (CSPIT)',
    'B.Tech Information Technology (DEPSTAR)',
    'B.Tech Computer Engineering (DEPSTAR)',
    'B.Tech Artificial Intelligence & Machine Learning (DEPSTAR)',
    'Bachelor of Computer Applications (BCA)',
    'Master of Computer Applications (MCA)',
    'B.Tech Electronics & Communication (CSPIT)',
    'B.Tech Mechanical Engineering (CSPIT)'
];

// Metrics aggregation
$metricTotalActive = 0;
$metricTotalDeleted = 0;
$metricTotalAll = 0;
try {
    $mRes = $conn->query("
        SELECT 
            COUNT(*) AS total_all,
            SUM(CASE WHEN is_deleted = 0 AND status != 'Deleted' THEN 1 ELSE 0 END) AS total_active,
            SUM(CASE WHEN is_deleted = 1 OR status = 'Deleted' THEN 1 ELSE 0 END) AS total_deleted
        FROM users
        WHERE role = 'student'
    ");
    if ($mRes && $mRow = $mRes->fetch_assoc()) {
        $metricTotalAll     = (int)($mRow['total_all'] ?? 0);
        $metricTotalActive  = (int)($mRow['total_active'] ?? 0);
        $metricTotalDeleted = (int)($mRow['total_deleted'] ?? 0);
    }
} catch (Exception $e) {
    error_log("Student metrics error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Student Hub Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/portal.css">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ======================================================================
           PAGE-SCOPED RESPONSIVE STYLING & SIDEBAR INTEGRATION FIXES
           ====================================================================== */
        .portal-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
            background-color: var(--bg-body, #0b1120);
            position: relative;
        }

        .portal-sidebar {
            width: 275px;
            background: var(--bg-card, rgba(17, 24, 39, 0.95));
            border-right: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .portal-main {
            flex: 1;
            margin-left: 275px;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
            position: relative;
            z-index: 1;
            overflow-x: hidden;
        }

        .portal-header {
            height: 72px;
            background: var(--bg-card, rgba(17, 24, 39, 0.95));
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 90;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .portal-content {
            padding: 28px 32px 48px;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        /* Page Header Title & Action Bar Alignment */
        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title-box h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-main, #f8fafc);
            margin: 0 0 4px;
        }

        .page-title-box p {
            color: var(--text-muted, #94a3b8);
            font-size: 13.5px;
            margin: 0;
        }

        .page-actions-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        /* Metric Highlights Strip */
        .metrics-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
        }

        .metric-pill {
            background: var(--surface-color, rgba(17, 24, 39, 0.75));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            border-radius: var(--radius-md, 10px);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .metric-pill:hover {
            border-color: var(--border-hover, rgba(99, 102, 241, 0.4));
            transform: translateY(-2px);
        }

        .metric-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .metric-text {
            display: flex;
            flex-direction: column;
        }

        .metric-val {
            font-size: 22px;
            font-weight: 800;
            font-family: var(--font-mono, monospace);
            color: var(--text-main, #f8fafc);
            line-height: 1.2;
        }

        .metric-lbl {
            font-size: 11.5px;
            color: var(--text-muted, #94a3b8);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Compact Responsive Filter Panel */
        .student-filter-card {
            background: var(--surface-color, rgba(17, 24, 39, 0.75));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            border-radius: var(--radius-lg, 12px);
            padding: 18px 22px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
            justify-content: space-between;
        }

        .filter-inputs-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            flex: 1;
            min-width: 280px;
        }

        .filter-input-search {
            flex: 2;
            min-width: 220px;
        }

        .filter-input-course {
            flex: 2;
            min-width: 200px;
        }

        .filter-input-status {
            flex: 1;
            min-width: 140px;
        }

        .filter-controls-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-field {
            width: 100%;
            padding: 9px 14px;
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
            border-radius: var(--radius-sm, 8px);
            font-size: 13.5px;
            font-family: inherit;
            background: var(--bg-card-solid, #1e293b);
            color: var(--text-main, #f8fafc);
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        [data-theme="light"] .filter-field, body.light-theme .filter-field {
            background: #ffffff;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .filter-field:focus {
            border-color: var(--primary-color, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .filter-count-badge {
            font-size: 13px;
            color: var(--text-muted, #94a3b8);
            font-weight: 600;
            white-space: nowrap;
            padding: 7px 14px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 8px;
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
        }

        [data-theme="light"] .filter-count-badge, body.light-theme .filter-count-badge {
            background: #f1f5f9;
            color: #475569;
            border-color: #e2e8f0;
        }

        /* Student Table Shell - No Cramping & Generous Horizontal Scroll */
        .student-table-container {
            background: var(--surface-color, rgba(17, 24, 39, 0.75));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            border-radius: var(--radius-lg, 12px);
            overflow-x: auto;
            box-shadow: var(--shadow-sm);
            -webkit-overflow-scrolling: touch;
        }

        .student-table {
            width: 100%;
            min-width: 1060px; /* Crucial: Prevents column cramping on laptops & small viewports */
            border-collapse: collapse;
            font-size: 13.5px;
            text-align: left;
        }

        .student-table th {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted, #94a3b8);
            font-size: 11.5px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        [data-theme="light"] .student-table th, body.light-theme .student-table th {
            background: #f8fafc;
            color: #64748b;
            border-bottom-color: #e2e8f0;
        }

        .student-table td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.06));
            vertical-align: middle;
            color: var(--text-main, #f8fafc);
        }

        [data-theme="light"] .student-table td, body.light-theme .student-table td {
            color: #1e293b;
            border-bottom-color: #f1f5f9;
        }

        .student-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        [data-theme="light"] .student-table tr:hover td, body.light-theme .student-table tr:hover td {
            background: #f8fafc;
        }

        .student-table tr:last-child td {
            border-bottom: none;
        }

        .row-deleted {
            background: rgba(239, 68, 68, 0.06) !important;
            opacity: 0.85;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        [data-theme="light"] .status-badge.active, body.light-theme .status-badge.active {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .status-badge.deleted {
            background: rgba(239, 68, 68, 0.15);
            color: #f43f5e;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        [data-theme="light"] .status-badge.deleted, body.light-theme .status-badge.deleted {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }

        /* Portal Action Buttons */
        .btn-portal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 16px;
            border-radius: var(--radius-sm, 8px);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap;
            line-height: 1.2;
        }

        .btn-portal-primary {
            background: var(--primary-color, #2563eb);
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        .btn-portal-primary:hover {
            background: var(--primary-hover, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .btn-portal-success {
            background: #059669;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
        }

        .btn-portal-success:hover {
            background: #047857;
            transform: translateY(-1px);
        }

        .btn-portal-secondary {
            background: var(--bg-card-solid, #1e293b);
            color: var(--text-main, #f8fafc);
            border-color: var(--border-color, rgba(255, 255, 255, 0.12));
        }

        [data-theme="light"] .btn-portal-secondary, body.light-theme .btn-portal-secondary {
            background: #ffffff;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .btn-portal-secondary:hover {
            background: var(--bg-hover, rgba(255, 255, 255, 0.08));
            border-color: var(--primary-color, #3b82f6);
        }

        [data-theme="light"] .btn-portal-secondary:hover, body.light-theme .btn-portal-secondary:hover {
            background: #f1f5f9;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-danger-soft {
            background: rgba(239, 68, 68, 0.12);
            color: #f43f5e;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .btn-danger-soft:hover {
            background: rgba(239, 68, 68, 0.22);
            color: #ff4d6d;
        }

        [data-theme="light"] .btn-danger-soft, body.light-theme .btn-danger-soft {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .btn-restore-soft {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .btn-restore-soft:hover {
            background: rgba(16, 185, 129, 0.22);
            color: #34d399;
        }

        [data-theme="light"] .btn-restore-soft, body.light-theme .btn-restore-soft {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        /* ======================================================================
           MODAL WINDOW SYSTEM - BULLETPROOF VISIBILITY & INTERACTION
           ====================================================================== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100%;
            height: 100%;
            background: rgba(9, 13, 22, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1050;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }

        .modal-overlay.show,
        .modal-overlay.active {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .modal-card {
            background: var(--bg-card-solid, #1e293b);
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.15));
            border-radius: var(--radius-lg, 14px);
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            position: relative;
            box-sizing: border-box;
            color: var(--text-main, #f8fafc);
        }

        [data-theme="light"] .modal-card, body.light-theme .modal-card {
            background: #ffffff;
            border-color: #e2e8f0;
            color: #0f172a;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .modal-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
            padding-bottom: 16px;
            margin-bottom: 18px;
        }

        [data-theme="light"] .modal-header-bar, body.light-theme .modal-header-bar {
            border-bottom-color: #e2e8f0;
        }

        .modal-close-btn {
            background: transparent;
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.15));
            border-radius: 6px;
            color: var(--text-muted, #94a3b8);
            font-size: 16px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            color: var(--danger, #ef4444);
            border-color: var(--danger, #ef4444);
            background: rgba(239, 68, 68, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .modal-label {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-main, #f8fafc);
            display: block;
            margin-bottom: 6px;
        }

        [data-theme="light"] .modal-label, body.light-theme .modal-label {
            color: #334155;
        }

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .portal-sidebar {
                transform: translateX(-100%);
            }
            body.sidebar-open .portal-sidebar,
            .portal-sidebar.open {
                transform: translateX(0);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            }
            .portal-main {
                margin-left: 0 !important;
            }
            .mobile-menu-btn {
                display: flex !important;
            }
            .portal-content {
                padding: 20px 16px 40px;
            }
            .portal-header {
                padding: 0 16px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .page-header-row {
                flex-direction: column;
                align-items: flex-start;
            }
            .page-actions-group {
                width: 100%;
                justify-content: flex-start;
            }
            .student-filter-card {
                padding: 14px;
            }
            .filter-inputs-wrapper {
                flex-direction: column;
                width: 100%;
            }
            .filter-input-search,
            .filter-input-course,
            .filter-input-status {
                width: 100%;
                min-width: 100%;
            }
        }
    </style>
</head>

<body class="portal-body">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>

    <div class="portal-layout">
        <!-- Unified Administrator Sidebar Navigation -->
        <aside class="portal-sidebar" id="portalSidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo-icon" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="sidebar-brand-text">
                    <span class="sidebar-brand-title">CHARUSAT</span>
                    <span class="sidebar-brand-sub">Admin Console</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">Overview</div>
                <a href="admin_dashboard.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg></span>
                    <span>Admin Dashboard</span>
                </a>
                <a href="index.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                    <span>Public Homepage</span>
                </a>

                <div class="nav-section-title">Student Management</div>
                <a href="admin_students.php" class="nav-link active">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <span>Student Directory</span>
                </a>
                <a href="javascript:void(0)" onclick="openAddStudentModal()" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></span>
                    <span>Enroll New Student</span>
                </a>
                <a href="export_students.php?search=<?= urlencode($search) ?>&course=<?= urlencode($course) ?>&status=<?= urlencode($status) ?>" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
                    <span>Export Students (CSV)</span>
                </a>

                <div class="nav-section-title">Event Management</div>
                <a href="admin_events.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <span>Event Console</span>
                </a>
                <a href="admin_events.php#add" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></span>
                    <span>Create New Event</span>
                </a>
                <a href="pages/campus.html" target="_blank" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span>
                    <span>Public Campus Feed</span>
                </a>

                <div class="nav-section-title">Security & System</div>
                <a href="timeout.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg></span>
                    <span>Session Security (15m)</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="admin_students.php" class="sidebar-user">
                    <div class="sidebar-avatar" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: white;"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="sidebar-user-id" style="color: var(--accent-amber);"><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?> &bull; Admin</span>
                    </div>
                </a>
                <a href="logout.php" class="btn-sidebar-logout" title="Sign Out">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </a>
            </div>
        </aside>

        <!-- Main Portal Content Workspace -->
        <main class="portal-main">
            <!-- Top Sticky Navigation Header -->
            <header class="portal-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                    <div class="header-title-box">
                        <h1>Student Management System</h1>
                        <div class="header-breadcrumb">
                            <a href="admin_dashboard.php" style="color: inherit; text-decoration: none;">Admin Console</a>
                            <span>/</span>
                            <span>Students</span>
                            <span>/</span>
                            <span style="color: var(--accent-amber); font-weight: 600;">Student Records & Directory</span>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <span class="role-badge admin">ADMIN PRIVILEGES</span>
                    <button type="button" class="btn-portal btn-portal-primary btn-sm" id="headerEnrollBtn" onclick="openAddStudentModal()" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Enroll Student</span>
                    </button>
                    <button class="header-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Light/Dark Theme" style="background: none; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 8px 10px; cursor: pointer; color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    </button>
                    <a href="logout.php" class="btn-sidebar-logout" title="Sign Out" style="color: var(--danger, #ef4444); padding: 8px 10px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </a>
                </div>
            </header>

            <div class="portal-content">
                <!-- Page Title Header and Action Triggers -->
                <div class="page-header-row">
                    <div class="page-title-box">
                        <h1>Student Records & Directory</h1>
                        <p>Manage enrolled student records, perform soft deletes/restores, and export audit files.</p>
                    </div>
                    <div class="page-actions-group">
                        <button type="button" class="btn-portal btn-portal-primary" id="openAddModalBtn" onclick="openAddStudentModal()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            <span>Enroll New Student</span>
                        </button>
                        <a href="export_students.php?search=<?= urlencode($search) ?>&course=<?= urlencode($course) ?>&status=<?= urlencode($status) ?>" class="btn-portal btn-portal-success" id="exportCsvBtn" download="students_export.csv">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <span>Export to CSV</span>
                        </a>
                    </div>
                </div>

                <!-- Flash Notification Alerts -->
                <?php if ($successMessage): ?>
                    <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: var(--radius-sm, 8px); padding: 14px 18px; color: #10b981; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px;" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        <span><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-sm, 8px); padding: 14px 18px; color: #f43f5e; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px;" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Quick Metrics Strip -->
                <div class="metrics-strip">
                    <div class="metric-pill">
                        <div class="metric-icon-wrap" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div class="metric-text">
                            <span class="metric-val"><?= number_format($metricTotalAll) ?></span>
                            <span class="metric-lbl">Total Enrolled</span>
                        </div>
                    </div>

                    <div class="metric-pill">
                        <div class="metric-icon-wrap" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                        <div class="metric-text">
                            <span class="metric-val"><?= number_format($metricTotalActive) ?></span>
                            <span class="metric-lbl">Active Students</span>
                        </div>
                    </div>

                    <div class="metric-pill">
                        <div class="metric-icon-wrap" style="background: rgba(239, 68, 68, 0.12); color: #f43f5e;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </div>
                        <div class="metric-text">
                            <span class="metric-val"><?= number_format($metricTotalDeleted) ?></span>
                            <span class="metric-lbl">Soft-Deleted</span>
                        </div>
                    </div>

                    <div class="metric-pill">
                        <div class="metric-icon-wrap" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <div class="metric-text">
                            <span class="metric-val"><?= count($students) ?></span>
                            <span class="metric-lbl">Matching Records</span>
                        </div>
                    </div>
                </div>

                <!-- Compact Responsive Search & Filter Panel -->
                <form method="GET" action="admin_students.php" class="student-filter-card" id="studentFilterForm">
                    <div class="filter-inputs-wrapper">
                        <div class="filter-input-search">
                            <input type="text" name="search" class="filter-field" placeholder="Search by Student ID, Name, Email..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" id="searchInput">
                        </div>

                        <div class="filter-input-course">
                            <select name="course" class="filter-field" id="courseSelect">
                                <option value="all">All Degree Courses</option>
                                <?php foreach ($courseList as $c): ?>
                                    <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= ($course === $c) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-input-status">
                            <select name="status" class="filter-field" id="statusSelect">
                                <option value="all" <?= ($status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                                <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active Only</option>
                                <option value="deleted" <?= ($status === 'deleted') ? 'selected' : '' ?>>Deleted Only</option>
                            </select>
                        </div>

                        <div class="filter-controls-group">
                            <button type="submit" class="btn-portal btn-portal-primary">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <span>Apply Filter</span>
                            </button>

                            <?php if ($search !== '' || $course !== 'all' || $status !== 'all'): ?>
                                <a href="admin_students.php" class="btn-portal btn-portal-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="filter-count-badge">
                        Found: <strong style="color: var(--text-main);"><?= count($students) ?></strong> record(s)
                    </div>
                </form>

                <!-- Students Records Table -->
                <div class="student-table-container">
                    <table class="student-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Student ID</th>
                                <th style="width: 180px;">Full Name</th>
                                <th style="width: 220px;">Official Email</th>
                                <th style="min-width: 200px;">Department / Course</th>
                                <th style="width: 120px;">Mobile</th>
                                <th style="width: 130px;">Academic Year</th>
                                <th style="width: 100px; text-align: center;">Status</th>
                                <th style="width: 170px; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" style="padding: 44px 20px; text-align: center; color: var(--text-muted);">
                                        <p style="font-size: 15px; margin-bottom: 12px; font-weight: 500;">No matching student records found in MySQL database.</p>
                                        <a href="admin_students.php" class="btn-portal btn-portal-secondary btn-sm">Clear Search & Filters</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $stu): 
                                    $isDel = ((int)$stu['is_deleted'] === 1 || $stu['status'] === 'Deleted');
                                ?>
                                    <tr class="<?= $isDel ? 'row-deleted' : '' ?>">
                                        <td style="font-family: var(--font-mono); font-weight: 700; color: var(--primary-color, #3b82f6); white-space: nowrap;">
                                            <?= htmlspecialchars($stu['username'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="font-weight: 600;">
                                            <?= htmlspecialchars($stu['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="word-break: break-all;">
                                            <?= htmlspecialchars($stu['email'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="font-size: 12.5px;">
                                            <?= htmlspecialchars($stu['course'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="font-family: var(--font-mono); font-size: 12.5px; white-space: nowrap;">
                                            <?= htmlspecialchars($stu['mobile'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                                            <?= htmlspecialchars($stu['academic_year'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($isDel): ?>
                                                <span class="status-badge deleted">DELETED</span>
                                            <?php else: ?>
                                                <span class="status-badge active">ACTIVE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <?php if (!$isDel): ?>
                                                <!-- Edit Action Button -->
                                                <button type="button" class="btn-portal btn-portal-secondary btn-sm editStudentBtn"
                                                    data-id="<?= (int)$stu['id'] ?>"
                                                    data-username="<?= htmlspecialchars($stu['username'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-fullname="<?= htmlspecialchars($stu['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-email="<?= htmlspecialchars($stu['email'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-mobile="<?= htmlspecialchars($stu['mobile'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-course="<?= htmlspecialchars($stu['course'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-year="<?= htmlspecialchars($stu['academic_year'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-gender="<?= htmlspecialchars($stu['gender'], ENT_QUOTES, 'UTF-8') ?>"
                                                    onclick="openEditStudentModal(this)">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    <span>Edit</span>
                                                </button>

                                                <!-- Soft Delete Form with Confirmation -->
                                                <form method="POST" action="admin_students.php" style="display: inline-block; margin-left: 6px;" onsubmit="return confirm('Confirm Soft Delete: Mark student \'<?= htmlspecialchars(addslashes($stu['full_name']), ENT_QUOTES, 'UTF-8') ?>\' (<?= htmlspecialchars($stu['username'], ENT_QUOTES, 'UTF-8') ?>) as Deleted? This hides the record from active portals without dropping data.');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="soft_delete">
                                                    <input type="hidden" name="student_db_id" value="<?= (int)$stu['id'] ?>">
                                                    <button type="submit" class="btn-portal btn-danger-soft btn-sm">
                                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                        <span>Soft Delete</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Restore Action Form with Confirmation -->
                                                <form method="POST" action="admin_students.php" style="display: inline-block;" onsubmit="return confirm('Restore Student: Re-activate student \'<?= htmlspecialchars(addslashes($stu['full_name']), ENT_QUOTES, 'UTF-8') ?>\' (<?= htmlspecialchars($stu['username'], ENT_QUOTES, 'UTF-8') ?>)?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="restore">
                                                    <input type="hidden" name="student_db_id" value="<?= (int)$stu['id'] ?>">
                                                    <button type="submit" class="btn-portal btn-restore-soft btn-sm">
                                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                                        <span>Restore Active</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ========================================================================
         MODAL 1: ENROLL NEW STUDENT
         ======================================================================== -->
    <div class="modal-overlay" id="addStudentModal" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
        <div class="modal-card">
            <div class="modal-header-bar">
                <div>
                    <h2 id="addModalTitle" style="font-size: 18px; font-weight: 800; margin: 0;">Enroll New Student</h2>
                    <span style="font-size: 12px; color: var(--text-muted); display: block; margin-top: 2px;">Create and persist a new student account in MySQL database</span>
                </div>
                <button type="button" class="modal-close-btn closeAddModalBtn" onclick="closeAddStudentModal()" aria-label="Close dialog">✕</button>
            </div>

            <form method="POST" action="admin_students.php" id="addStudentForm" style="margin-top: 10px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="add_student">

                <div class="form-grid">
                    <div>
                        <label class="modal-label" for="add_username">Student ID / Enrollment *</label>
                        <input type="text" name="username" id="add_username" class="filter-field" placeholder="e.g. 25CS099" required autocomplete="off">
                    </div>

                    <div>
                        <label class="modal-label" for="add_full_name">Full Name *</label>
                        <input type="text" name="full_name" id="add_full_name" class="filter-field" placeholder="e.g. Kakadiya Shlok" required>
                    </div>

                    <div>
                        <label class="modal-label" for="add_email">Official Email Address *</label>
                        <input type="email" name="email" id="add_email" class="filter-field" placeholder="student@charusat.edu.in" required>
                    </div>

                    <div>
                        <label class="modal-label" for="add_mobile">Mobile Number *</label>
                        <input type="tel" name="mobile" id="add_mobile" class="filter-field" placeholder="10-digit mobile number" required pattern="[0-9]{10,12}">
                    </div>

                    <div class="form-full">
                        <label class="modal-label" for="add_course">Degree Program / Faculty *</label>
                        <select name="course" id="add_course" class="filter-field" required>
                            <?php foreach ($courseList as $c): ?>
                                <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="modal-label" for="add_academic_year">Academic Year *</label>
                        <select name="academic_year" id="add_academic_year" class="filter-field" required>
                            <option value="1st Year (Semester 1 & 2)">1st Year (Semester 1 & 2)</option>
                            <option value="2nd Year (Semester 3 & 4)" selected>2nd Year (Semester 3 & 4)</option>
                            <option value="3rd Year (Semester 5 & 6)">3rd Year (Semester 5 & 6)</option>
                            <option value="4th Year (Semester 7 & 8)">4th Year (Semester 7 & 8)</option>
                        </select>
                    </div>

                    <div>
                        <label class="modal-label" for="add_gender">Gender *</label>
                        <select name="gender" id="add_gender" class="filter-field" required>
                            <option value="Female">Female</option>
                            <option value="Male">Male</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-full">
                        <label class="modal-label" for="add_password">Initial Account Password (min 8 chars) *</label>
                        <input type="password" name="password" id="add_password" class="filter-field" placeholder="Secure password for student portal access" required minlength="8">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" class="btn-portal btn-portal-secondary closeAddModalBtn" onclick="closeAddStudentModal()">Cancel</button>
                    <button type="submit" class="btn-portal btn-portal-primary" id="submitEnrollBtn">Enroll Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================
         MODAL 2: EDIT STUDENT PROFILE
         ======================================================================== -->
    <div class="modal-overlay" id="editStudentModal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="modal-card">
            <div class="modal-header-bar">
                <div>
                    <h2 id="editModalTitle" style="font-size: 18px; font-weight: 800; margin: 0;">Edit Student Profile</h2>
                    <span id="editModalSubtitle" style="font-size: 12px; color: var(--text-muted); font-family: var(--font-mono); display: block; margin-top: 2px;"></span>
                </div>
                <button type="button" class="modal-close-btn closeEditModalBtn" onclick="closeEditStudentModal()" aria-label="Close dialog">✕</button>
            </div>

            <form method="POST" action="admin_students.php" id="editStudentForm" style="margin-top: 10px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="edit_student">
                <input type="hidden" name="student_db_id" id="edit_student_db_id" value="">

                <div class="form-grid">
                    <div class="form-full">
                        <label class="modal-label" for="edit_full_name">Full Name *</label>
                        <input type="text" name="full_name" id="edit_full_name" class="filter-field" required>
                    </div>

                    <div>
                        <label class="modal-label" for="edit_email">Official Email Address *</label>
                        <input type="email" name="email" id="edit_email" class="filter-field" required>
                    </div>

                    <div>
                        <label class="modal-label" for="edit_mobile">Mobile Number *</label>
                        <input type="tel" name="mobile" id="edit_mobile" class="filter-field" required>
                    </div>

                    <div class="form-full">
                        <label class="modal-label" for="edit_course">Degree Program / Faculty *</label>
                        <select name="course" id="edit_course" class="filter-field" required>
                            <?php foreach ($courseList as $c): ?>
                                <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="modal-label" for="edit_academic_year">Academic Year *</label>
                        <select name="academic_year" id="edit_academic_year" class="filter-field" required>
                            <option value="1st Year (Semester 1 & 2)">1st Year (Semester 1 & 2)</option>
                            <option value="2nd Year (Semester 3 & 4)">2nd Year (Semester 3 & 4)</option>
                            <option value="3rd Year (Semester 5 & 6)">3rd Year (Semester 5 & 6)</option>
                            <option value="4th Year (Semester 7 & 8)">4th Year (Semester 7 & 8)</option>
                        </select>
                    </div>

                    <div>
                        <label class="modal-label" for="edit_gender">Gender *</label>
                        <select name="gender" id="edit_gender" class="filter-field" required>
                            <option value="Female">Female</option>
                            <option value="Male">Male</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-full">
                        <label class="modal-label" for="edit_new_password">Change Password (Leave blank to keep unchanged)</label>
                        <input type="password" name="new_password" id="edit_new_password" class="filter-field" placeholder="Enter new password (optional, min 8 chars)" minlength="8">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" class="btn-portal btn-portal-secondary closeEditModalBtn" onclick="closeEditStudentModal()">Cancel</button>
                    <button type="submit" class="btn-portal btn-portal-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Alerts Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Client-side Interactive Bindings & Modal Engine -->
    <script>
        // Modal Control API
        function openAddStudentModal() {
            var addModal = document.getElementById('addStudentModal');
            if (addModal) {
                addModal.style.display = 'flex';
                addModal.classList.add('show', 'active');
                document.body.style.overflow = 'hidden';
                setTimeout(function() {
                    var input = document.getElementById('add_username');
                    if (input) input.focus();
                }, 50);
            }
        }

        function closeAddStudentModal() {
            var addModal = document.getElementById('addStudentModal');
            if (addModal) {
                addModal.style.display = 'none';
                addModal.classList.remove('show', 'active');
                document.body.style.overflow = '';
            }
        }

        function openEditStudentModal(btn) {
            var editModal = document.getElementById('editStudentModal');
            if (!editModal) return;

            document.getElementById('edit_student_db_id').value = btn.getAttribute('data-id') || '';
            document.getElementById('edit_full_name').value = btn.getAttribute('data-fullname') || '';
            document.getElementById('edit_email').value = btn.getAttribute('data-email') || '';
            document.getElementById('edit_mobile').value = btn.getAttribute('data-mobile') || '';
            document.getElementById('edit_course').value = btn.getAttribute('data-course') || '';
            document.getElementById('edit_academic_year').value = btn.getAttribute('data-year') || '';
            document.getElementById('edit_gender').value = btn.getAttribute('data-gender') || 'Other';
            document.getElementById('edit_new_password').value = '';

            var uname = btn.getAttribute('data-username') || '';
            var subtitle = document.getElementById('editModalSubtitle');
            if (subtitle) subtitle.textContent = 'Editing Student ID: ' + uname;

            editModal.style.display = 'flex';
            editModal.classList.add('show', 'active');
            document.body.style.overflow = 'hidden';
            setTimeout(function() {
                var input = document.getElementById('edit_full_name');
                if (input) input.focus();
            }, 50);
        }

        function closeEditStudentModal() {
            var editModal = document.getElementById('editStudentModal');
            if (editModal) {
                editModal.style.display = 'none';
                editModal.classList.remove('show', 'active');
                document.body.style.overflow = '';
            }
        }

        // Sidebar Navigation Controls
        function toggleSidebar() {
            var sidebar = document.querySelector('.portal-sidebar');
            var overlay = document.querySelector('.sidebar-overlay');
            document.body.classList.toggle('sidebar-open');
            if (sidebar) sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('show');
        }

        function closeSidebar() {
            var sidebar = document.querySelector('.portal-sidebar');
            var overlay = document.querySelector('.sidebar-overlay');
            document.body.classList.remove('sidebar-open');
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
        }

        // Theme Toggle System
        function toggleTheme() {
            var current = document.documentElement.getAttribute('data-theme') || 'dark';
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            document.body.classList.toggle('dark-theme', next === 'dark');
            document.body.classList.toggle('light-theme', next === 'light');
            localStorage.setItem('studentHubTheme', next);
            localStorage.setItem('portal_theme', next);
        }

        (function() {
            var savedTheme = localStorage.getItem('studentHubTheme') || localStorage.getItem('portal_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.body.classList.toggle('dark-theme', savedTheme === 'dark');
            document.body.classList.toggle('light-theme', savedTheme === 'light');
        })();

        // DOM Initializations
        document.addEventListener('DOMContentLoaded', function () {
            // Button Event Listeners
            var openAddBtn = document.getElementById('openAddModalBtn');
            var headerEnrollBtn = document.getElementById('headerEnrollBtn');

            if (openAddBtn) {
                openAddBtn.addEventListener('click', openAddStudentModal);
            }
            if (headerEnrollBtn) {
                headerEnrollBtn.addEventListener('click', openAddStudentModal);
            }

            // Backdrop click dismissal
            var addModal = document.getElementById('addStudentModal');
            var editModal = document.getElementById('editStudentModal');

            window.addEventListener('click', function (e) {
                if (e.target === addModal) closeAddStudentModal();
                if (e.target === editModal) closeEditStudentModal();
            });

            // Escape key listener
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeAddStudentModal();
                    closeEditStudentModal();
                    closeSidebar();
                }
            });

            // Automatically open modal if #add or ?action=new is requested
            if (window.location.hash === '#add' || window.location.search.indexOf('action=new') !== -1) {
                openAddStudentModal();
            }
        });
    </script>
</body>
</html>
