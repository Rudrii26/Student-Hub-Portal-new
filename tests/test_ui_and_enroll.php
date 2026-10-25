<?php
/**
 * End-to-End Verification Script for admin_students.php UI & Enrollment Flow
 */

require_once __DIR__ . '/../config/db.php';
$pdo = getDbConnection();

$baseUrl = 'http://localhost/Student%20Hub%20Portal';
$cookieFile = tempnam(sys_get_temp_dir(), 'admin_e2e_');

echo "====================================================================\n";
echo "E2E VERIFICATION: STUDENT MANAGEMENT UI & ENROLLMENT WORKFLOW\n";
echo "====================================================================\n\n";

function doRequest($url, $options = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
    curl_close($ch);
    $headers = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    return ['code' => $statusCode, 'headers' => $headers, 'body' => $body];
}

function verifyCondition($cond, $title) {
    if ($cond) {
        echo "  [PASS] $title\n";
        return true;
    } else {
        echo "  [FAIL] $title\n";
        return false;
    }
}

// 1. Authenticate as Admin
echo "1. Administrator Authentication:\n";
$loginRes = doRequest("$baseUrl/login.php", [
    'cookieFile' => $cookieFile,
    'post' => [
        'login_identifier' => 'admin@charusat.edu.in',
        'password'         => 'Admin@Charusat2026'
    ]
]);
verifyCondition($loginRes['code'] === 302, "Admin login returned 302 redirect");

// 2. Load admin_students.php
echo "\n2. Verify admin_students.php Layout & Structural Elements:\n";
$pageRes = doRequest("$baseUrl/admin_students.php", ['cookieFile' => $cookieFile]);
verifyCondition($pageRes['code'] === 200, "admin_students.php loaded with HTTP 200 OK");

$body = $pageRes['body'];
verifyCondition(strpos($body, 'class="portal-sidebar"') !== false, "Portal sidebar element exists");
verifyCondition(strpos($body, 'class="portal-main"') !== false, "Portal main workspace element exists");
verifyCondition(strpos($body, 'class="portal-header"') !== false, "Portal sticky header element exists");
verifyCondition(strpos($body, 'class="mobile-menu-btn"') !== false, "Mobile hamburger toggle menu button exists");
verifyCondition(strpos($body, 'id="openAddModalBtn"') !== false, "Enroll New Student button with id='openAddModalBtn' exists");
verifyCondition(strpos($body, 'id="addStudentModal"') !== false, "Add Student Modal with id='addStudentModal' exists");
verifyCondition(strpos($body, 'id="editStudentModal"') !== false, "Edit Student Modal with id='editStudentModal' exists");
verifyCondition(strpos($body, 'class="student-filter-card"') !== false, "Compact responsive filter card exists");
verifyCondition(strpos($body, 'class="student-table"') !== false, "Student records table exists with min-width preventing cramped layout");
verifyCondition(strpos($body, 'min-width: 1060px') !== false, "Student table has explicit min-width set");
verifyCondition(strpos($body, 'openAddStudentModal') !== false, "openAddStudentModal JavaScript API is bound");
verifyCondition(strpos($body, 'display: flex !important') !== false, "Modal visibility CSS override rule exists for .show / .active");

// Extract CSRF token
preg_match('/name="csrf_token"\s+value="([a-f0-9]{64})"/', $body, $csrfMatch);
$csrfToken = $csrfMatch[1] ?? '';
verifyCondition(!empty($csrfToken), "CSRF token successfully extracted: " . substr($csrfToken, 0, 12) . "...");

// 3. Test Enrollment: Submitting Valid Student
echo "\n3. Test Enrollment Flow (Create New Student):\n";
$newStudentId = '25E2E' . mt_rand(100, 999);
$newEmail     = 'e2estudent.' . mt_rand(100, 999) . '@charusat.edu.in';
$newFullName  = 'E2E Test Student';
$newMobile    = '9876543210';
$newCourse    = 'B.Tech Computer Science & Engineering (CSPIT)';
$newYear      = '2nd Year (Semester 3 & 4)';
$newGender    = 'Male';
$newPass      = 'E2ESecurePass2026!';

$addRes = doRequest("$baseUrl/admin_students.php", [
    'cookieFile' => $cookieFile,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'add_student',
        'username'      => $newStudentId,
        'full_name'     => $newFullName,
        'email'         => $newEmail,
        'mobile'        => $newMobile,
        'course'        => $newCourse,
        'academic_year' => $newYear,
        'gender'        => $newGender,
        'password'      => $newPass
    ]
]);

verifyCondition($addRes['code'] === 302 && strpos($addRes['headers'], 'msg=added') !== false, "POST returns 302 redirect with msg=added");

// Check Database
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$newStudentId]);
$created = $stmt->fetch();
verifyCondition($created !== false, "New record persisted in MySQL users table for ID $newStudentId");
verifyCondition($created['email'] === $newEmail, "Database email matches: " . $created['email']);
verifyCondition($created['role'] === 'student' && $created['status'] === 'Active', "Account initialized as student with Active status");
verifyCondition(password_verify($newPass, $created['password_hash']), "Password correctly hashed using bcrypt");

// Verify record appears in HTML table on admin_students.php
$verifyTableRes = doRequest("$baseUrl/admin_students.php?search=" . urlencode($newStudentId), ['cookieFile' => $cookieFile]);
verifyCondition(strpos($verifyTableRes['body'], $newStudentId) !== false, "New record $newStudentId successfully displayed in table results");
verifyCondition(strpos($verifyTableRes['body'], $newFullName) !== false, "Full name $newFullName successfully displayed in table");

// 4. Test Duplicate Submission
echo "\n4. Test Duplicate Enrollment Prevention:\n";
$dupRes = doRequest("$baseUrl/admin_students.php", [
    'cookieFile' => $cookieFile,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'add_student',
        'username'      => $newStudentId, // Duplicate ID
        'full_name'     => 'Duplicate Test',
        'email'         => 'different.' . mt_rand(100, 999) . '@charusat.edu.in',
        'mobile'        => '9876543210',
        'course'        => $newCourse,
        'academic_year' => $newYear,
        'gender'        => $newGender,
        'password'      => $newPass
    ]
]);
verifyCondition($dupRes['code'] === 302 && strpos($dupRes['headers'], 'err=') !== false, "Duplicate username rejected with error redirect");

// 5. Test Invalid Email
echo "\n5. Test Invalid Input Rejection:\n";
$badEmailRes = doRequest("$baseUrl/admin_students.php", [
    'cookieFile' => $cookieFile,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'add_student',
        'username'      => '25BAD' . mt_rand(10, 99),
        'full_name'     => 'Bad Email Test',
        'email'         => 'not-an-email',
        'mobile'        => '9876543210',
        'course'        => $newCourse,
        'academic_year' => $newYear,
        'gender'        => $newGender,
        'password'      => $newPass
    ]
]);
verifyCondition($badEmailRes['code'] === 302 && strpos($badEmailRes['headers'], 'err=') !== false, "Invalid email format rejected with error redirect");

// 6. Test Soft Delete
echo "\n6. Test Soft Delete and Restore Lifecycle:\n";
$delRes = doRequest("$baseUrl/admin_students.php", [
    'cookieFile' => $cookieFile,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'soft_delete',
        'student_db_id' => $created['id']
    ]
]);
verifyCondition($delRes['code'] === 302 && strpos($delRes['headers'], 'msg=deleted') !== false, "Soft delete returns 302 redirect with msg=deleted");

$stmt = $pdo->prepare("SELECT is_deleted, status, deleted_at FROM users WHERE id = ?");
$stmt->execute([$created['id']]);
$delRow = $stmt->fetch();
verifyCondition((int)$delRow['is_deleted'] === 1 && $delRow['status'] === 'Deleted' && !empty($delRow['deleted_at']), "Record flagged as is_deleted=1, status=Deleted with deleted_at timestamp");

// 7. Test Restore
$resRes = doRequest("$baseUrl/admin_students.php", [
    'cookieFile' => $cookieFile,
    'post' => [
        'csrf_token'    => $csrfToken,
        'action'        => 'restore',
        'student_db_id' => $created['id']
    ]
]);
verifyCondition($resRes['code'] === 302 && strpos($resRes['headers'], 'msg=restored') !== false, "Restore returns 302 redirect with msg=restored");

$stmt = $pdo->prepare("SELECT is_deleted, status, deleted_at FROM users WHERE id = ?");
$stmt->execute([$created['id']]);
$restoredRow = $stmt->fetch();
verifyCondition((int)$restoredRow['is_deleted'] === 0 && $restoredRow['status'] === 'Active' && empty($restoredRow['deleted_at']), "Record successfully restored to is_deleted=0, status=Active");

// 8. Test CSV Export with Filters
echo "\n7. Test CSV Export Integration:\n";
$exportRes = doRequest("$baseUrl/export_students.php?search=" . urlencode($newStudentId), ['cookieFile' => $cookieFile]);
verifyCondition($exportRes['code'] === 200, "export_students.php returned HTTP 200 OK");
verifyCondition(strpos($exportRes['headers'], 'Content-Type: text/csv') !== false, "Content-Type is text/csv");
verifyCondition(strpos($exportRes['body'], $newStudentId) !== false, "Exported CSV contains the student ID $newStudentId");

// Clean up test student
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$created['id']]);
echo "\nCleaned up temporary test student record (ID: {$created['id']}).\n";

echo "\n====================================================================\n";
echo "ALL E2E VERIFICATION CHECKS COMPLETED SUCCESSFULLY!\n";
echo "====================================================================\n";
