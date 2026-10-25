<?php
/**
 * CHARUSAT Student Hub - Student Records CSV Export (export_students.php)
 * Securely streams filtered or complete student records as a downloadable CSV.
 * Enforces admin-only RBAC, prepared statements, and spreadsheet formula injection protection.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Enforce 15-minute inactivity session timeout
require_once __DIR__ . '/timeout.php';

// 2. Enforce Authentication & Admin Role Boundary Guard
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header("Location: login.php?error=unauthorized");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    header("Content-Type: text/plain; charset=UTF-8");
    die("HTTP 403 Forbidden: Administrator role required to export student records.");
}

// 2.1 Release session file lock immediately so downstream streaming never blocks
session_write_close();

// 3. Connect to Database
require_once __DIR__ . '/db.php';
$conn = getMysqliConnection();

// 4. Capture & Sanitize Filter Parameters
$search = trim($_GET['search'] ?? '');
$course = trim($_GET['course'] ?? '');
$status = trim($_GET['status'] ?? 'all'); // 'all', 'active', 'deleted'

// Build dynamic WHERE clause using prepared statements
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
$sql = "
    SELECT `id`, `registration_id`, `username`, `full_name`, `email`, 
           `mobile`, `course`, `academic_year`, `gender`, `status`, `is_deleted`, `created_at`
    FROM `users`
    WHERE {$whereSql}
    ORDER BY `id` ASC
";

$stmt = $conn->prepare($sql);
if (!empty($paramValues)) {
    $stmt->bind_param($paramTypes, ...$paramValues);
}
$stmt->execute();
$result = $stmt->get_result();

// 5. Formula Injection Sanitization Helper
// Neutralizes CSV injection (CWE-1236) by prepending a single quote to dangerous leading chars
function sanitizeCsvValue($val): string {
    if ($val === null) {
        return '';
    }
    $str = (string)$val;
    if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $str;
    }
    return $str;
}

// 6. Set HTTP Download Headers
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="students_export.csv"');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Connection: close');

// 7. Stream CSV Output with UTF-8 BOM for Microsoft Excel Compatibility
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM

// Column Headers
fputcsv($output, [
    'ID',
    'Registration ID',
    'Student ID / Username',
    'Full Name',
    'Official Email',
    'Mobile',
    'Course / Department',
    'Academic Year',
    'Gender',
    'Status',
    'Record Created At'
]);

// Stream Rows
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $displayStatus = ((int)$row['is_deleted'] === 1 || $row['status'] === 'Deleted') ? 'Deleted' : $row['status'];
        fputcsv($output, [
            sanitizeCsvValue($row['id']),
            sanitizeCsvValue($row['registration_id']),
            sanitizeCsvValue($row['username']),
            sanitizeCsvValue($row['full_name']),
            sanitizeCsvValue($row['email']),
            sanitizeCsvValue($row['mobile']),
            sanitizeCsvValue($row['course']),
            sanitizeCsvValue($row['academic_year']),
            sanitizeCsvValue($row['gender']),
            sanitizeCsvValue($displayStatus),
            sanitizeCsvValue($row['created_at'])
        ]);
    }
}

fclose($output);
$stmt->close();
exit;
