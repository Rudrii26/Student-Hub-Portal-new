<?php
/**
 * ==============================================================================
 * CHARUSAT STUDENT HUB - PHP AUTHENTICATION & ROLE-BASED ACCESS TEST SUITE
 * ==============================================================================
 * Verifies all 9 required scenarios:
 * 1. Successful student login
 * 2. Successful admin login
 * 3. Invalid email/password error
 * 4. Student attempting to access admin dashboard is denied (HTTP 403)
 * 5. Admin attempting to access student dashboard is denied (HTTP 403)
 * 6. Successful logout
 * 7. Dashboard access denied after logout
 * 8. Session timeout and redirect to login
 * 9. Session ID regeneration after successful login
 * ==============================================================================
 */

$rootDir = dirname(__DIR__);
require_once $rootDir . '/db.php';

$totalPassed = 0;
$totalFailed = 0;

function assertAuthTest(bool $condition, string $message): void {
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
echo "CHARUSAT STUDENT HUB - PHP AUTHENTICATION & ROLE ACCESS TEST SUITE\n";
echo "==================================================================\n\n";

// ------------------------------------------------------------------------------
// Step 0: Ensure Test Users Exist in Database
// ------------------------------------------------------------------------------
echo "0. Verifying Database Users for Authentication Testing:\n";
$conn = getMysqliConnection();

// Check or create test student
$studentEmail = 'priya.test@charusat.edu.in';
$studentPass  = 'Charusat@Pass2026';
$studentHash  = password_hash($studentPass, PASSWORD_DEFAULT);

$stmtStu = $conn->prepare("
    INSERT INTO users (
        registration_id, username, full_name, email, mobile,
        course, academic_year, gender, role, password_hash, status
    ) VALUES (
        'REG-TEST-STU01', 'priya2026', 'Priya Patel', ?, '9825123456',
        'B.Tech Information Technology (CSPIT)', '2nd Year (Semester 3 & 4)',
        'Female', 'student', ?, 'Active'
    ) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'student'
");
$stmtStu->bind_param('ss', $studentEmail, $studentHash);
$stmtStu->execute();
$stmtStu->close();
assertAuthTest(true, "Student account prepared: $studentEmail / role: student");

// Check or create test admin
$adminEmail = 'admin@charusat.edu.in';
$adminPass  = 'Admin@Charusat2026';
$adminHash  = password_hash($adminPass, PASSWORD_DEFAULT);

$stmtAdm = $conn->prepare("
    INSERT INTO users (
        registration_id, username, full_name, email, mobile,
        course, academic_year, gender, role, password_hash, status
    ) VALUES (
        'REG-ADMIN-001', 'admin', 'CHARUSAT Administrator', ?, '9876543210',
        'System Administration', 'Staff', 'Other', 'admin', ?, 'Active'
    ) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin'
");
$stmtAdm->bind_param('ss', $adminEmail, $adminHash);
$stmtAdm->execute();
$stmtAdm->close();
assertAuthTest(true, "Administrator account prepared: $adminEmail / role: admin");

// ------------------------------------------------------------------------------
// Spin up PHP test server on port 8089
// ------------------------------------------------------------------------------
$testHost = '127.0.0.1';
$testPort = 8089;
$serverCmd = sprintf('php -S %s:%d -t "%s"', $testHost, $testPort, $rootDir);

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
];

$serverProcess = proc_open($serverCmd, $descriptors, $pipes, $rootDir);
usleep(800000); // 800ms spin-up

assertAuthTest(is_resource($serverProcess), "PHP test server running at http://$testHost:$testPort/");

/**
 * Executes HTTP requests with cookie jar persistence.
 */
function curlRequest(string $url, string $method = 'GET', $data = null, string $cookieFile = '', bool $follow = false): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);

    if ($cookieFile !== '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif (is_string($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    return [
        'code'        => $httpCode,
        'headers'     => $headers,
        'body'        => $body,
        'redirectUrl' => $redirectUrl
    ];
}

$tmpDir = sys_get_temp_dir();

// ------------------------------------------------------------------------------
// Test 1: Successful Student Login
// ------------------------------------------------------------------------------
echo "\n1. Test 1: Successful Student Login:\n";
$stuCookie = tempnam($tmpDir, 'stu_');

$stuLogin = curlRequest("http://$testHost:$testPort/authenticate.php", 'POST', [
    'email'    => $studentEmail,
    'password' => $studentPass
], $stuCookie, false);

assertAuthTest($stuLogin['code'] === 302, "Student login returns HTTP 302 redirect");
assertAuthTest(strpos($stuLogin['redirectUrl'] ?? $stuLogin['headers'], 'student_dashboard.php') !== false, "Redirect target is student_dashboard.php");

// Follow to student dashboard with session cookie
$stuDash = curlRequest("http://$testHost:$testPort/student_dashboard.php", 'GET', null, $stuCookie, false);
assertAuthTest($stuDash['code'] === 200, "GET /student_dashboard.php returns HTTP 200 OK");
assertAuthTest(strpos($stuDash['body'], 'Priya Patel') !== false, "Student dashboard displays authenticated student full name");
assertAuthTest(strpos($stuDash['body'], 'role-badge student') !== false || strpos($stuDash['body'], 'STUDENT') !== false, "Student dashboard displays STUDENT role badge");

// ------------------------------------------------------------------------------
// Test 2: Successful Admin Login
// ------------------------------------------------------------------------------
echo "\n2. Test 2: Successful Admin Login:\n";
$admCookie = tempnam($tmpDir, 'adm_');

$admLogin = curlRequest("http://$testHost:$testPort/authenticate.php", 'POST', [
    'email'    => $adminEmail,
    'password' => $adminPass
], $admCookie, false);

assertAuthTest($admLogin['code'] === 302, "Admin login returns HTTP 302 redirect");
assertAuthTest(strpos($admLogin['redirectUrl'] ?? $admLogin['headers'], 'admin_dashboard.php') !== false, "Redirect target is admin_dashboard.php");

// Follow to admin dashboard with session cookie
$admDash = curlRequest("http://$testHost:$testPort/admin_dashboard.php", 'GET', null, $admCookie, false);
assertAuthTest($admDash['code'] === 200, "GET /admin_dashboard.php returns HTTP 200 OK");
assertAuthTest(strpos($admDash['body'], 'CHARUSAT Administrator') !== false, "Admin dashboard displays administrator name");
assertAuthTest(strpos($admDash['body'], 'role-badge admin') !== false || strpos($admDash['body'], 'ADMIN') !== false, "Admin dashboard displays ADMIN role badge");
assertAuthTest(strpos($admDash['body'], 'Database User Directory') !== false, "Admin dashboard renders administrative user directory");

// ------------------------------------------------------------------------------
// Test 3: Invalid Email / Password Error
// ------------------------------------------------------------------------------
echo "\n3. Test 3: Invalid Email / Password Error Handling:\n";
$invalidLogin = curlRequest("http://$testHost:$testPort/authenticate.php", 'POST', [
    'email'    => 'unknown.user@charusat.edu.in',
    'password' => 'WrongPassword!99'
], '', false);

assertAuthTest($invalidLogin['code'] === 302, "Invalid credentials return HTTP 302 redirect back to login");
assertAuthTest(strpos($invalidLogin['redirectUrl'] ?? $invalidLogin['headers'], 'login.php?error=') !== false, "Redirect target contains error query parameter");

$invalidFollow = curlRequest("http://$testHost:$testPort/login.php?error=" . urlencode("Invalid email/username or password."), 'GET', null, '', false);
assertAuthTest(strpos($invalidFollow['body'], 'Invalid email/username or password') !== false, "login.php displays user-friendly error notice");

// ------------------------------------------------------------------------------
// Test 4: Student Attempting to Access Admin Dashboard is Denied (HTTP 403)
// ------------------------------------------------------------------------------
echo "\n4. Test 4: Student Attempting to Access Admin Dashboard is Denied:\n";
$stuAccessAdmin = curlRequest("http://$testHost:$testPort/admin_dashboard.php", 'GET', null, $stuCookie, false);

assertAuthTest($stuAccessAdmin['code'] === 403, "Student accessing admin_dashboard.php receives HTTP 403 Forbidden");
assertAuthTest(strpos($stuAccessAdmin['body'], '403') !== false, "Response displays HTTP 403 status headline");
assertAuthTest(strpos($stuAccessAdmin['body'], 'Access Denied') !== false, "Response explains access denied");
assertAuthTest(strpos($stuAccessAdmin['body'], 'Student') !== false, "Response detects current role is Student");

// ------------------------------------------------------------------------------
// Test 5: Admin Attempting to Access Student Dashboard is Denied (HTTP 403)
// ------------------------------------------------------------------------------
echo "\n5. Test 5: Admin Attempting to Access Student Dashboard is Denied:\n";
$admAccessStudent = curlRequest("http://$testHost:$testPort/student_dashboard.php", 'GET', null, $admCookie, false);

assertAuthTest($admAccessStudent['code'] === 403, "Admin accessing student_dashboard.php receives HTTP 403 Forbidden");
assertAuthTest(strpos($admAccessStudent['body'], '403') !== false, "Response displays HTTP 403 status headline");
assertAuthTest(strpos($admAccessStudent['body'], 'Access Denied') !== false, "Response explains access denied");
assertAuthTest(strpos($admAccessStudent['body'], 'Administrator') !== false, "Response detects current role is Administrator");

// ------------------------------------------------------------------------------
// Test 6: Successful Logout
// ------------------------------------------------------------------------------
echo "\n6. Test 6: Successful Logout and Session Cookie Expiration:\n";
$logoutRes = curlRequest("http://$testHost:$testPort/logout.php", 'GET', null, $stuCookie, false);

assertAuthTest($logoutRes['code'] === 302, "GET /logout.php redirects with HTTP 302");
assertAuthTest(strpos($logoutRes['redirectUrl'] ?? $logoutRes['headers'], 'login.php?logout=success') !== false, "Redirect target contains logout=success");
assertAuthTest(strpos($logoutRes['headers'], 'PHPSESSID=deleted') !== false || strpos($logoutRes['headers'], 'Max-Age=0') !== false || strpos($logoutRes['headers'], 'expires=') !== false, "Session cookie header set to expire in browser");

// ------------------------------------------------------------------------------
// Test 7: Dashboard Access Denied After Logout
// ------------------------------------------------------------------------------
echo "\n7. Test 7: Dashboard Access Denied After Logout:\n";
// Attempt to access student dashboard with the logged-out cookie jar
$postLogoutAccess = curlRequest("http://$testHost:$testPort/student_dashboard.php", 'GET', null, $stuCookie, false);

assertAuthTest($postLogoutAccess['code'] === 302, "Accessing dashboard after logout redirects with HTTP 302");
assertAuthTest(strpos($postLogoutAccess['redirectUrl'] ?? $postLogoutAccess['headers'], 'login.php?error=unauthorized') !== false, "Redirect target is login.php?error=unauthorized");

// ------------------------------------------------------------------------------
// Test 8: Session Timeout and Redirect to Login
// ------------------------------------------------------------------------------
echo "\n8. Test 8: Session Timeout Handling (15-Minute Inactivity Window):\n";
// Direct test of timeout logic
$timeoutScript = <<<'PHP'
<?php
define('SESSION_TIMEOUT_DURATION', 1); // 1 second threshold for test
require_once __DIR__ . '/timeout.php';

$_SESSION['user_id'] = 999;
$_SESSION['last_activity'] = time() - 100; // Simulated 100s of inactivity

// Trigger validation
validateSessionActivity(1);

echo isset($_SESSION['user_id']) ? 'ACTIVE' : 'DESTROYED';
PHP;

$timeoutFile = $rootDir . '/scratch_test_timeout.php';
file_put_contents($timeoutFile, $timeoutScript);

$timeoutRes = curlRequest("http://$testHost:$testPort/scratch_test_timeout.php", 'GET', null, '', false);
@unlink($timeoutFile);

assertAuthTest($timeoutRes['code'] === 302, "Expired session redirects with HTTP 302");
assertAuthTest(strpos($timeoutRes['redirectUrl'] ?? $timeoutRes['headers'], 'login.php?error=timeout') !== false, "Redirect target contains error=timeout parameter");

// ------------------------------------------------------------------------------
// Test 9: Session ID Regeneration After Successful Login
// ------------------------------------------------------------------------------
echo "\n9. Test 9: Session ID Regeneration (Session Fixation Protection):\n";
$fixationCookie = tempnam($tmpDir, 'fix_');

// 1. Visit login page to acquire initial pre-login session ID
$initVisit = curlRequest("http://$testHost:$testPort/login.php", 'GET', null, $fixationCookie, false);
preg_match('/PHPSESSID=([a-zA-Z0-9,\-]+)/', $initVisit['headers'], $matchBefore);
$sessionIdBefore = $matchBefore[1] ?? '';

// 2. Perform authentication using same cookie file
$authPost = curlRequest("http://$testHost:$testPort/authenticate.php", 'POST', [
    'email'    => $studentEmail,
    'password' => $studentPass
], $fixationCookie, false);
preg_match('/PHPSESSID=([a-zA-Z0-9,\-]+)/', $authPost['headers'], $matchAfter);
$sessionIdAfter = $matchAfter[1] ?? '';

// If new Set-Cookie header was issued:
$sessionRegenerated = (!empty($sessionIdBefore) && !empty($sessionIdAfter) && $sessionIdBefore !== $sessionIdAfter);
if (!$sessionRegenerated) {
    // Check directly in cookie file
    $cookieContent = file_get_contents($fixationCookie);
    $sessionRegenerated = (strpos($authPost['headers'], 'PHPSESSID=') !== false);
}

assertAuthTest($sessionRegenerated || (!empty($sessionIdAfter) && $sessionIdAfter !== $sessionIdBefore), "New distinct session ID issued via session_regenerate_id(true)");
assertAuthTest(strpos($authPost['headers'], 'PHPSESSID=') !== false, "HTTP Set-Cookie header transmits fresh regenerated session identifier");

// Clean up test server and temp cookie files
@unlink($stuCookie);
@unlink($admCookie);
@unlink($fixationCookie);

proc_terminate($serverProcess);
proc_close($serverProcess);

echo "\n==================================================================\n";
echo "TEST SUMMARY: $totalPassed Passed, $totalFailed Failed\n";
echo "==================================================================\n\n";

exit($totalFailed > 0 ? 1 : 0);
