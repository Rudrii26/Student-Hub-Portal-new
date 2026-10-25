<?php
/**
 * ==============================================================================
 * CHARUSAT STUDENT HUB - PRACTICAL 2 AUTOMATED TEST SUITE
 * ==============================================================================
 * Automatically verifies:
 * 1. Admin RBAC access restrictions on admin_students.php & export_students.php
 * 2. Unauthenticated & student access denial (HTTP 403 / 401 / 302)
 * 3. Adding a new student via admin handler with validation & password hashing
 * 4. Duplicate email / username prevention
 * 5. Editing student profile details and verifying database update
 * 6. Search and course/status filtering functionality
 * 7. Soft delete workflow: record preserved in DB, is_deleted=1, status='Deleted'
 * 8. Authentication prevention: soft-deleted student CANNOT sign in
 * 9. Restore workflow: re-activating student, is_deleted=0, status='Active'
 * 10. Post-restore authentication: student can sign in again successfully
 * 11. CSV Export: proper text/csv headers, file attachment, and formula injection protection
 * ==============================================================================
 */

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/db.php';

$totalPassed = 0;
$totalFailed = 0;

function assertCrudTest(bool $condition, string $message): void {
    global $totalPassed, $totalFailed;
    if ($condition) {
        echo "  ✓ $message\n";
        $totalPassed++;
    } else {
        echo "  ✗ FAIL: $message\n";
        $totalFailed++;
    }
}

echo "\n==================================================================\n";
echo "CHARUSAT STUDENT HUB - PRACTICAL 2: STUDENT MANAGEMENT TEST SUITE\n";
echo "==================================================================\n\n";

$pdo = getDbConnection();

// Check if live Apache server is available in XAMPP
$apacheCheck = @file_get_contents('http://localhost/Student%20Hub%20Portal/login.php');
$serverProcess = null;
$pipes = [];

if ($apacheCheck !== false) {
    $baseUrl = 'http://localhost/Student%20Hub%20Portal';
    echo "Using live Apache server for end-to-end verification ($baseUrl)\n\n";
} else {
    // Spin up a localized PHP server on port 8092 for end-to-end HTTP testing
    $serverCommand = sprintf('php -S 127.0.0.1:8092 -t "%s"', addslashes($rootDir));
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    $serverProcess = proc_open($serverCommand, $descriptors, $pipes, $rootDir);
    usleep(300000); // 300ms wait for server to bind
    $baseUrl = 'http://127.0.0.1:8092';
    echo "Running tests against built-in test server ($baseUrl)\n\n";
}

register_shutdown_function(function() use (&$serverProcess, &$pipes) {
    if (is_array($pipes)) {
        foreach ($pipes as $p) {
            if (is_resource($p)) @fclose($p);
        }
    }
    if (is_resource($serverProcess)) {
        proc_terminate($serverProcess);
        proc_close($serverProcess);
    }
});

// Helper for cURL HTTP requests with cookie jar
function testHttp(string $url, array $options = []): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: close']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

    if (isset($options['cookieFile'])) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $options['cookieFile']);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $options['cookieFile']);
    }

    if (isset($options['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['post']));
    }

    $raw = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $headers = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);

    return [
        'code'    => $statusCode,
        'headers' => $headers,
        'body'    => $body,
        'error'   => $curlErr
    ];
}

// Ensure soft-delete columns exist in database
try {
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER status
    ");
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL AFTER is_deleted
    ");
} catch (Exception $e) {
    // Columns already present
}

// Clean up any lingering test student from prior runs
$testUname = '25TEST01';
$testEmail = 'test.student1@charusat.edu.in';
$pdo->prepare("DELETE FROM users WHERE username = ? OR email = ?")->execute([$testUname, $testEmail]);

// ------------------------------------------------------------------------------
// Step 1: Access Restrictions on admin_students.php & export_students.php
// ------------------------------------------------------------------------------
echo "1. Testing Access Restrictions & Role-Based Access Control:\n";

// 1.1 Unauthenticated access to admin_students.php
$unauthRes = testHttp("$baseUrl/admin_students.php");
assertCrudTest(
    $unauthRes['code'] === 302 && strpos($unauthRes['headers'], 'login.php?error=unauthorized') !== false,
    "Unauthenticated visitor accessing admin_students.php is redirected to login.php"
);

// 1.2 Unauthenticated access to export_students.php
$unauthExport = testHttp("$baseUrl/export_students.php");
assertCrudTest(
    $unauthExport['code'] === 401 || ($unauthExport['code'] === 302 && strpos($unauthExport['headers'], 'login.php?error=unauthorized') !== false),
    "Unauthenticated visitor accessing export_students.php is denied (HTTP 401 / redirect)"
);

// Authenticate student session
$studentCookie = tempnam(sys_get_temp_dir(), 'stu_ck_');
$loginStudent = testHttp("$baseUrl/login.php", [
    'cookieFile' => $studentCookie,
    'post' => [
        'login_identifier' => 'priya2026',
        'password'         => 'Charusat@Pass2026'
    ]
]);

// 1.3 Student accessing admin_students.php -> HTTP 403 Forbidden
$studentAccessStudents = testHttp("$baseUrl/admin_students.php", ['cookieFile' => $studentCookie]);
assertCrudTest(
    $studentAccessStudents['code'] === 403,
    "Student session accessing admin_students.php receives HTTP 403 Forbidden"
);

// 1.4 Student accessing export_students.php -> HTTP 403 Forbidden
$studentAccessExport = testHttp("$baseUrl/export_students.php", ['cookieFile' => $studentCookie]);
assertCrudTest(
    $studentAccessExport['code'] === 403,
    "Student session accessing export_students.php receives HTTP 403 Forbidden"
);

// Authenticate administrator session
$adminCookie = tempnam(sys_get_temp_dir(), 'adm_ck_');
$loginAdmin = testHttp("$baseUrl/login.php", [
    'cookieFile' => $adminCookie,
    'post' => [
        'login_identifier' => 'admin@charusat.edu.in',
        'password'         => 'Admin@Charusat2026'
    ]
]);

// 1.5 Admin accessing admin_students.php -> HTTP 200 OK
$adminAccessStudents = testHttp("$baseUrl/admin_students.php", ['cookieFile' => $adminCookie]);
assertCrudTest(
    $adminAccessStudents['code'] === 200,
    "Administrator session accessing admin_students.php returns HTTP 200 OK"
);

assertCrudTest(
    strpos($adminAccessStudents['body'], 'Student Management System') !== false,
    "admin_students.php renders Student Management System header"
);

assertCrudTest(
    strpos($adminAccessStudents['body'], 'id="exportCsvBtn"') !== false,
    "admin_students.php contains Export to CSV action trigger"
);

// Extract CSRF token from admin session page
preg_match('/name="csrf_token"\s+value="([a-f0-9]{64})"/', $adminAccessStudents['body'], $csrfMatches);
$csrfToken = $csrfMatches[1] ?? '';
assertCrudTest(
    strlen($csrfToken) === 64,
    "Extracted valid 64-character CSRF token from administrator session"
);

// ------------------------------------------------------------------------------
// Step 2: Add Student (Create) Testing
// ------------------------------------------------------------------------------
echo "\n2. Testing Student Enrollment (Create Action):\n";

$testPassword = 'Pass@Charusat2026';
$addResponse = testHttp("$baseUrl/admin_students.php", [
    'cookieFile' => $adminCookie,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'add_student',
        'username'      => $testUname,
        'full_name'     => 'Test Student One',
        'email'         => $testEmail,
        'mobile'        => '9988776655',
        'course'        => 'B.Tech Computer Engineering (CSPIT)',
        'academic_year' => '2nd Year (Semester 3 & 4)',
        'gender'        => 'Female',
        'password'      => $testPassword
    ]
]);

assertCrudTest(
    $addResponse['code'] === 302 && strpos($addResponse['headers'], 'msg=added') !== false,
    "Add student POST returns HTTP 302 redirect with msg=added confirmation"
);

// Verify insertion in database
$stmt = $pdo->prepare("SELECT id, username, full_name, email, mobile, course, role, status, is_deleted, password_hash FROM users WHERE username = ?");
$stmt->execute([$testUname]);
$createdStudent = $stmt->fetch();

assertCrudTest(
    $createdStudent !== false,
    "Student record $testUname successfully exists in MySQL database"
);

assertCrudTest(
    $createdStudent['full_name'] === 'Test Student One',
    "Stored full name matches submitted input ('Test Student One')"
);

assertCrudTest(
    $createdStudent['email'] === $testEmail,
    "Stored email matches submitted input ('$testEmail')"
);

assertCrudTest(
    $createdStudent['role'] === 'student' && $createdStudent['status'] === 'Active' && (int)$createdStudent['is_deleted'] === 0,
    "Student initialized with role='student', status='Active', is_deleted=0"
);

assertCrudTest(
    password_verify($testPassword, $createdStudent['password_hash']),
    "Stored password is a secure bcrypt hash that verifies via password_verify()"
);

$studentDbId = (int)$createdStudent['id'];

// Test duplicate rejection
$dupResponse = testHttp("$baseUrl/admin_students.php", [
    'cookieFile' => $adminCookie,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'add_student',
        'username'      => $testUname, // duplicate
        'full_name'     => 'Duplicate Test',
        'email'         => 'different.email@charusat.edu.in',
        'mobile'        => '9988776655',
        'course'        => 'B.Tech Computer Engineering (CSPIT)',
        'academic_year' => '2nd Year (Semester 3 & 4)',
        'gender'        => 'Female',
        'password'      => 'Password@123'
    ]
]);

assertCrudTest(
    $dupResponse['code'] === 302 && strpos($dupResponse['headers'], 'err=') !== false,
    "Duplicate username/student ID is rejected with error redirect"
);

// ------------------------------------------------------------------------------
// Step 3: Edit Student (Update) Testing
// ------------------------------------------------------------------------------
echo "\n3. Testing Student Modification (Update Action):\n";

$editResponse = testHttp("$baseUrl/admin_students.php", [
    'cookieFile' => $adminCookie,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'edit_student',
        'student_db_id' => $studentDbId,
        'full_name'     => 'Test Student One (Updated)',
        'email'         => 'test.student1.updated@charusat.edu.in',
        'mobile'        => '9876543219',
        'course'        => 'B.Tech Information Technology (CSPIT)',
        'academic_year' => '3rd Year (Semester 5 & 6)',
        'gender'        => 'Female',
        'new_password'  => '' // Keep unchanged
    ]
]);

assertCrudTest(
    $editResponse['code'] === 302 && strpos($editResponse['headers'], 'msg=updated') !== false,
    "Edit student POST returns HTTP 302 redirect with msg=updated confirmation"
);

// Verify update in database
$stmt = $pdo->prepare("SELECT full_name, email, mobile, course, academic_year FROM users WHERE id = ?");
$stmt->execute([$studentDbId]);
$updatedStudent = $stmt->fetch();

assertCrudTest(
    $updatedStudent['full_name'] === 'Test Student One (Updated)',
    "Updated full name correctly saved in MySQL database"
);

assertCrudTest(
    $updatedStudent['email'] === 'test.student1.updated@charusat.edu.in',
    "Updated email address correctly saved in MySQL database"
);

assertCrudTest(
    $updatedStudent['course'] === 'B.Tech Information Technology (CSPIT)',
    "Updated course program correctly saved in MySQL database"
);

// ------------------------------------------------------------------------------
// Step 4: Search & Filtering Testing
// ------------------------------------------------------------------------------
echo "\n4. Testing Dynamic Search and Course/Status Filtering:\n";

// Search by username
$searchRes = testHttp("$baseUrl/admin_students.php?search=$testUname", ['cookieFile' => $adminCookie]);
assertCrudTest(
    strpos($searchRes['body'], $testUname) !== false && strpos($searchRes['body'], 'Test Student One (Updated)') !== false,
    "Search by Student ID '$testUname' accurately returns student record"
);

// Negative search
$negSearch = testHttp("$baseUrl/admin_students.php?search=XYZNONEXISTENT999", ['cookieFile' => $adminCookie]);
assertCrudTest(
    strpos($negSearch['body'], $testUname) === false && strpos($negSearch['body'], 'No matching student records found') !== false,
    "Search for non-existent student displays clean empty-state message"
);

// Filter by Course
$courseFilter = testHttp("$baseUrl/admin_students.php?course=" . urlencode('B.Tech Information Technology (CSPIT)'), ['cookieFile' => $adminCookie]);
assertCrudTest(
    strpos($courseFilter['body'], $testUname) !== false,
    "Course filter 'B.Tech Information Technology (CSPIT)' matches updated student"
);

// ------------------------------------------------------------------------------
// Step 5: Soft Delete Workflow
// ------------------------------------------------------------------------------
echo "\n5. Testing Soft Delete Mechanism:\n";

$deleteResponse = testHttp("$baseUrl/admin_students.php", [
    'cookieFile' => $adminCookie,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'soft_delete',
        'student_db_id' => $studentDbId
    ]
]);

assertCrudTest(
    $deleteResponse['code'] === 302 && strpos($deleteResponse['headers'], 'msg=deleted') !== false,
    "Soft delete POST returns HTTP 302 redirect with msg=deleted confirmation"
);

// Verify database record is PRESERVED, not dropped
$stmt = $pdo->prepare("SELECT id, is_deleted, deleted_at, status FROM users WHERE id = ?");
$stmt->execute([$studentDbId]);
$softDeletedRow = $stmt->fetch();

assertCrudTest(
    $softDeletedRow !== false,
    "CRITICAL: Record was NOT dropped from MySQL table (data integrity preserved)"
);

assertCrudTest(
    (int)$softDeletedRow['is_deleted'] === 1 && $softDeletedRow['status'] === 'Deleted',
    "Record state successfully updated to is_deleted=1 and status='Deleted'"
);

assertCrudTest(
    !empty($softDeletedRow['deleted_at']),
    "Audit timestamp deleted_at was successfully recorded in database"
);

// Filter by status=deleted shows DELETED badge and Restore button
$deletedView = testHttp("$baseUrl/admin_students.php?status=deleted", ['cookieFile' => $adminCookie]);
assertCrudTest(
    strpos($deletedView['body'], $testUname) !== false && strpos($deletedView['body'], 'Restore Active') !== false,
    "Status filter 'deleted' displays student with 'Restore Active' action button"
);

// Filter by status=active hides soft-deleted student
$activeView = testHttp("$baseUrl/admin_students.php?status=active", ['cookieFile' => $adminCookie]);
assertCrudTest(
    strpos($activeView['body'], $testUname) === false,
    "Status filter 'active' correctly excludes soft-deleted student record"
);

// ------------------------------------------------------------------------------
// Step 6: Authentication Prevention for Soft-Deleted Student
// ------------------------------------------------------------------------------
echo "\n6. Verifying Authentication Prevention for Soft-Deleted Student:\n";

$softDelLogin = testHttp("$baseUrl/login.php", [
    'post' => [
        'login_identifier' => $testUname,
        'password'         => $testPassword
    ]
]);

assertCrudTest(
    $softDelLogin['code'] === 302 && strpos($softDelLogin['headers'], 'error=') !== false,
    "Soft-deleted student attempting to log in is rejected with error redirect"
);

$softDelLoginErrorPage = testHttp("$baseUrl/login.php?error=" . urlencode("Your account is currently inactive or deactivated. Please contact the portal administrator."));
assertCrudTest(
    strpos($softDelLoginErrorPage['body'], 'inactive or deactivated') !== false,
    "login.php accurately displays deactivated account notification"
);

// ------------------------------------------------------------------------------
// Step 7: Restore Workflow
// ------------------------------------------------------------------------------
echo "\n7. Testing Student Restore Action:\n";

$restoreResponse = testHttp("$baseUrl/admin_students.php", [
    'cookieFile' => $adminCookie,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'restore',
        'student_db_id' => $studentDbId
    ]
]);

assertCrudTest(
    $restoreResponse['code'] === 302 && strpos($restoreResponse['headers'], 'msg=restored') !== false,
    "Restore POST returns HTTP 302 redirect with msg=restored confirmation"
);

// Verify restored state in database
$stmt = $pdo->prepare("SELECT is_deleted, deleted_at, status FROM users WHERE id = ?");
$stmt->execute([$studentDbId]);
$restoredRow = $stmt->fetch();

assertCrudTest(
    (int)$restoredRow['is_deleted'] === 0 && $restoredRow['status'] === 'Active' && $restoredRow['deleted_at'] === null,
    "Database record successfully restored to is_deleted=0, status='Active', deleted_at=NULL"
);

// ------------------------------------------------------------------------------
// Step 8: Post-Restore Authentication Testing
// ------------------------------------------------------------------------------
echo "\n8. Verifying Successful Login After Student Restoration:\n";

$restoredCookie = tempnam(sys_get_temp_dir(), 'res_stu_');
$restoredLogin = testHttp("$baseUrl/login.php", [
    'cookieFile' => $restoredCookie,
    'post' => [
        'login_identifier' => $testUname,
        'password'         => $testPassword
    ]
]);

assertCrudTest(
    $restoredLogin['code'] === 302 && strpos($restoredLogin['headers'], 'student_dashboard.php') !== false,
    "Restored student successfully authenticates and redirects to student_dashboard.php"
);

$studentDash = testHttp("$baseUrl/student_dashboard.php", ['cookieFile' => $restoredCookie]);
assertCrudTest(
    $studentDash['code'] === 200 && strpos($studentDash['body'], 'Test Student One (Updated)') !== false,
    "student_dashboard.php displays restored student's updated profile name"
);

// ------------------------------------------------------------------------------
// Step 9: CSV Export Testing
// ------------------------------------------------------------------------------
echo "\n9. Testing CSV Export Endpoint & Formula Injection Protection:\n";

// Insert a student with formula injection attack vector
$vectorUname = '25ATTACK';
$vectorEmail = 'attack.vector@charusat.edu.in';
$formulaPayload = '=cmd|/c calc!A0';
$pdo->prepare("DELETE FROM users WHERE username = ? OR email = ?")->execute([$vectorUname, $vectorEmail]);

$pdo->prepare("
    INSERT INTO users (
        registration_id, username, full_name, email, mobile,
        course, academic_year, gender, role, password_hash, status, is_deleted
    ) VALUES (
        'REG-2026-ATTACK', ?, ?, ?, '9999999999',
        'B.Tech Computer Engineering (CSPIT)', '1st Year (Semester 1 & 2)', 'Other', 'student', 'hash', 'Active', 0
    )
")->execute([$vectorUname, $formulaPayload, $vectorEmail]);

$exportRes = testHttp("$baseUrl/export_students.php", ['cookieFile' => $adminCookie]);

assertCrudTest(
    $exportRes['code'] === 200,
    "export_students.php returns HTTP 200 OK"
);

assertCrudTest(
    strpos($exportRes['headers'], 'Content-Type: text/csv') !== false,
    "HTTP response contains 'Content-Type: text/csv; charset=UTF-8' header"
);

assertCrudTest(
    strpos($exportRes['headers'], 'attachment; filename="students_export.csv"') !== false,
    "HTTP response specifies 'attachment; filename=\"students_export.csv\"'"
);

// Verify CSV content and columns
$csvLines = explode("\n", trim($exportRes['body']));
$headerLine = $csvLines[0] ?? '';

assertCrudTest(
    strpos($headerLine, 'Registration ID') !== false && strpos($headerLine, 'Student ID') !== false && strpos($headerLine, 'Full Name') !== false,
    "CSV header line contains all required standard column labels"
);

assertCrudTest(
    strpos($exportRes['body'], $testUname) !== false,
    "CSV export output includes enrolled student record '$testUname'"
);

// Verify Formula Injection Sanitization
// Payload starts with '=', should be prefixed with "'"
assertCrudTest(
    strpos($exportRes['body'], "'=cmd|/c calc!A0") !== false,
    "CRITICAL SECURITY: Formula injection '=cmd|/c calc!A0' neutralized with leading quote"
);

// Clean up attack vector student & test student
$pdo->prepare("DELETE FROM users WHERE username = ? OR email = ?")->execute([$vectorUname, $vectorEmail]);
$pdo->prepare("DELETE FROM users WHERE username = ? OR email = ?")->execute([$testUname, $testEmail]);
$pdo->prepare("DELETE FROM users WHERE email = 'test.student1.updated@charusat.edu.in'")->execute();

// Save a copy of the generated CSV file to project root for user review
file_put_contents($rootDir . '/students_export.csv', $exportRes['body']);

// Clean up temporary cookie files
@unlink($studentCookie);
@unlink($adminCookie);
@unlink($restoredCookie);

echo "\n==================================================================\n";
echo "PRACTICAL 2 TEST SUMMARY: $totalPassed Passed, $totalFailed Failed\n";
echo "==================================================================\n\n";

if ($totalFailed > 0) {
    exit(1);
}
exit(0);
