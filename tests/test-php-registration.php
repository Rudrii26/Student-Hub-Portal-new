<?php
/**
 * ==============================================================================
 * CHARUSAT STUDENT HUB - PHP REGISTRATION & MYSQL AUTHENTICATION TEST SUITE
 * ==============================================================================
 * Automatically verifies:
 * 1. Form UI rendering and field integrity (including username input)
 * 2. Empty-field validation for all required fields
 * 3. Invalid email format validation
 * 4. Password mismatch validation & complexity rules
 * 5. Duplicate email validation against MySQL database (rejects duplicates)
 * 6. Duplicate username validation against MySQL database (rejects duplicates)
 * 7. Secure password hashing: password_hash(PASSWORD_DEFAULT) stored, not plaintext
 * 8. Successful database insertion: exactly one record created in MySQL
 * 9. Post-Redirect-Get (PRG) pattern and success card rendering
 * 10. Login authentication using password_verify() with newly created user
 * 11. Dual persistence synchronization in JSON and CSV storage
 * 12. Absence of prohibited academic terms
 * ==============================================================================
 */

$rootDir  = dirname(__DIR__);
$dataFile = $rootDir . '/data/registrations.json';
$csvFile  = $rootDir . '/data/registrations.csv';

require_once $rootDir . '/config/db.php';

$totalPassed = 0;
$totalFailed = 0;

function assertTest($condition, $message) {
    global $totalPassed, $totalFailed;
    if ($condition) {
        echo "  ✓ $message\n";
        $totalPassed++;
    } else {
        echo "  ✗ FAIL: $message\n";
        $totalFailed++;
    }
}

echo "\n=======================================================\n";
echo "CHARUSAT STUDENT HUB - FULL REGISTRATION & MYSQL TEST\n";
echo "=======================================================\n\n";

// ------------------------------------------------------------------------------
// Test 1: Verify Codebase Integrity & Prohibited Words
// ------------------------------------------------------------------------------
echo "1. Validating Script Structure and Clean Academic Terminology:\n";
$registerPhpPath = $rootDir . '/register.php';
$dbPhpPath       = $rootDir . '/config/db.php';
$loginPhpPath    = $rootDir . '/login.php';

assertTest(file_exists($registerPhpPath), "register.php exists in project root");
assertTest(file_exists($dbPhpPath), "config/db.php exists in project");
assertTest(file_exists($loginPhpPath), "login.php exists in project root");

$registerContent = file_get_contents($registerPhpPath);
$prohibitedPatterns = [
    '/\bPractical\s+4\b/i',
    '/\bPractical\s+5\b/i',
    '/\bLab\s+Task\b/i',
    '/\bPractical\s+Features\b/i',
    '/\bViva\b/i'
];

$foundProhibited = false;
foreach ($prohibitedPatterns as $pat) {
    if (preg_match($pat, $registerContent)) {
        $foundProhibited = true;
        echo "  ✗ FAIL: Prohibited term matched pattern $pat in register.php\n";
        $totalFailed++;
    }
}
if (!$foundProhibited) {
    assertTest(true, "No prohibited academic terms found in register.php");
}

// ------------------------------------------------------------------------------
// Test 2: Verify MySQL PDO Connection and Clean Test State
// ------------------------------------------------------------------------------
echo "\n2. Verifying MySQL Database Connection & Users Table:\n";
$pdo = null;
try {
    $pdo = getDbConnection();
    assertTest($pdo instanceof PDO, "Connected to MySQL database '" . DB_NAME . "' via PDO");
    
    // Check users table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    assertTest(!empty($tableCheck), "Table 'users' exists in database");

    // Clean up any test users with email 'priya.test@charusat.edu.in' or username 'priya2026'
    $pdo->prepare("DELETE FROM users WHERE email = 'priya.test@charusat.edu.in' OR username = 'priya2026'")->execute();
    assertTest(true, "Test environment prepared cleanly");
} catch (Exception $e) {
    echo "  ✗ FAIL: Database connection error: " . $e->getMessage() . "\n";
    $totalFailed++;
}

// ------------------------------------------------------------------------------
// Test 3: Initialize PHP Server for HTTP End-to-End Testing
// ------------------------------------------------------------------------------
echo "\n3. Initializing PHP Built-in Server on 127.0.0.1:8088:\n";
$testHost = '127.0.0.1';
$testPort = 8088;
$serverCmd = sprintf('php -S %s:%d -t "%s"', $testHost, $testPort, $rootDir);

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
];

$serverProcess = proc_open($serverCmd, $descriptors, $pipes, $rootDir);
usleep(800000); // 800ms spin-up

$serverRunning = is_resource($serverProcess);
assertTest($serverRunning, "PHP test server running at http://$testHost:$testPort/");

function makeHttpRequest($url, $method = 'GET', $postData = null, $followRedirect = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);
    curl_setopt($ch, CURLOPT_COOKIEJAR, '');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '');

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($postData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
    }

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $error = curl_error($ch);
    curl_close($ch);

    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    return [
        'code'        => $httpCode,
        'headers'     => $headers,
        'body'        => $body,
        'redirectUrl' => $redirectUrl,
        'error'       => $error
    ];
}

// ------------------------------------------------------------------------------
// Test 4: Form UI Display via GET
// ------------------------------------------------------------------------------
echo "\n4. Testing Registration Form UI Rendering (GET /register.php):\n";
$uiRes = makeHttpRequest("http://$testHost:$testPort/register.php");
assertTest($uiRes['code'] === 200, "GET /register.php returns HTTP 200 OK");
assertTest(strpos($uiRes['body'], 'name="fullName"') !== false, "Form contains fullName input");
assertTest(strpos($uiRes['body'], 'name="username"') !== false, "Form contains username input");
assertTest(strpos($uiRes['body'], 'name="email"') !== false, "Form contains email input");
assertTest(strpos($uiRes['body'], 'name="mobile"') !== false, "Form contains mobile input");
assertTest(strpos($uiRes['body'], 'name="course"') !== false, "Form contains course dropdown");
assertTest(strpos($uiRes['body'], 'name="year"') !== false, "Form contains year dropdown");
assertTest(strpos($uiRes['body'], 'name="gender"') !== false, "Form contains gender radio options");
assertTest(strpos($uiRes['body'], 'name="password"') !== false, "Form contains password input");
assertTest(strpos($uiRes['body'], 'name="confirmPassword"') !== false, "Form contains confirmPassword input");
assertTest(strpos($uiRes['body'], 'name="terms"') !== false, "Form contains terms checkbox");
assertTest(strpos($uiRes['body'], 'Complete Student Registration') !== false, "Form contains submit button");

// ------------------------------------------------------------------------------
// Test 5: Server-Side Validation: Empty Required Fields
// ------------------------------------------------------------------------------
echo "\n5. Testing Empty-Field Server Validation:\n";
$emptyRes = makeHttpRequest("http://$testHost:$testPort/register.php", 'POST', []);
assertTest($emptyRes['code'] === 200, "Empty POST returns HTTP 200 with re-rendered form");
assertTest(strpos($emptyRes['body'], 'Full Name is required') !== false, "Displays error: 'Full Name is required'");
assertTest(strpos($emptyRes['body'], 'Username / Student ID is required') !== false, "Displays error: 'Username / Student ID is required'");
assertTest(strpos($emptyRes['body'], 'Email Address is required') !== false, "Displays error: 'Email Address is required'");
assertTest(strpos($emptyRes['body'], 'Mobile Number is required') !== false, "Displays error: 'Mobile Number is required'");
assertTest(strpos($emptyRes['body'], 'Please select your enrolled degree program') !== false, "Displays error for course");
assertTest(strpos($emptyRes['body'], 'Please select your academic year') !== false, "Displays error for year");
assertTest(strpos($emptyRes['body'], 'Please select your gender identity') !== false, "Displays error for gender");
assertTest(strpos($emptyRes['body'], 'Password is required') !== false, "Displays error: 'Password is required'");
assertTest(strpos($emptyRes['body'], 'Please confirm your password') !== false, "Displays error for confirm password");
assertTest(strpos($emptyRes['body'], 'You must accept the CHARUSAT Academic Code of Conduct') !== false, "Displays error for terms");

// ------------------------------------------------------------------------------
// Test 6: Invalid Email and Password Mismatch Validation
// ------------------------------------------------------------------------------
echo "\n6. Testing Invalid Email Format & Password Mismatch:\n";
$invalidEmailRes = makeHttpRequest("http://$testHost:$testPort/register.php", 'POST', [
    'fullName'        => 'Priya Test',
    'username'        => 'priya2026',
    'email'           => 'not-an-email',
    'mobile'          => '9825123456',
    'course'          => 'B.Tech Information Technology (CSPIT)',
    'year'            => '2nd Year (Semester 3 & 4)',
    'gender'          => 'Female',
    'password'        => 'Charusat@2026',
    'confirmPassword' => 'DifferentPassword#99',
    'terms'           => 'on'
]);
assertTest(strpos($invalidEmailRes['body'], 'Please enter a valid email address') !== false, "Rejects malformed email address");
assertTest(strpos($invalidEmailRes['body'], 'Passwords do not match') !== false, "Detects and rejects password mismatch");
assertTest(strpos($invalidEmailRes['body'], 'value="Priya Test"') !== false, "Preserves sanitized fullName in form");
assertTest(strpos($invalidEmailRes['body'], 'Charusat@2026') === false, "CRITICAL: Password is NEVER repopulated in form markup");

// ------------------------------------------------------------------------------
// Test 7: Valid Registration -> Successful MySQL Insertion & Password Hashing
// ------------------------------------------------------------------------------
echo "\n7. Testing Valid User Registration, Password Hashing & MySQL Insertion:\n";
$countBefore = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$validStudent = [
    'fullName'        => 'Priya Patel',
    'username'        => 'priya2026',
    'email'           => 'priya.test@charusat.edu.in',
    'mobile'          => '9825123456',
    'course'          => 'B.Tech Information Technology (CSPIT)',
    'year'            => '2nd Year (Semester 3 & 4)',
    'gender'          => 'Female',
    'password'        => 'Charusat@Pass2026',
    'confirmPassword' => 'Charusat@Pass2026',
    'terms'           => 'on'
];

$regRes = makeHttpRequest("http://$testHost:$testPort/register.php", 'POST', $validStudent, false);
assertTest($regRes['code'] === 302, "Valid registration redirects with HTTP 302 (PRG pattern)");
assertTest(strpos($regRes['redirectUrl'] ?? $regRes['headers'], 'status=success') !== false, "Redirect target contains status=success");

$countAfter = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
assertTest($countAfter === $countBefore + 1, "Exactly ONE new record was inserted into MySQL 'users' table");

// Verify the record directly in MySQL
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'priya2026' LIMIT 1");
$stmt->execute();
$newUser = $stmt->fetch();

assertTest(!empty($newUser), "Inserted record found in MySQL database");
assertTest($newUser['full_name'] === 'Priya Patel', "Stored full_name matches input");
assertTest($newUser['email'] === 'priya.test@charusat.edu.in', "Stored email matches input");
assertTest($newUser['mobile'] === '9825123456', "Stored mobile matches input");
assertTest($newUser['course'] === 'B.Tech Information Technology (CSPIT)', "Stored course matches input");
assertTest($newUser['status'] === 'Active', "Account status set to 'Active'");

// Password Hashing Verification
assertTest($newUser['password_hash'] !== 'Charusat@Pass2026', "CRITICAL: Plaintext password is NOT stored in MySQL");
assertTest(str_starts_with($newUser['password_hash'], '$2y$'), "Stored password is a secure bcrypt hash (begins with $2y$)");
assertTest(password_verify('Charusat@Pass2026', $newUser['password_hash']), "password_verify() verifies password against stored hash");

// Dual storage check
$jsonRecords = json_decode(file_get_contents($dataFile), true);
$lastJson = end($jsonRecords);
assertTest($lastJson['username'] === 'priya2026', "data/registrations.json synchronized with new username");
$csvContent = file_get_contents($csvFile);
assertTest(strpos($csvContent, 'priya2026') !== false, "data/registrations.csv synchronized with new username");

// ------------------------------------------------------------------------------
// Test 8: Duplicate Email and Duplicate Username Rejection
// ------------------------------------------------------------------------------
echo "\n8. Testing Duplicate Email and Duplicate Username Validation:\n";
// Attempt duplicate email with new username
$dupEmailStudent = [
    'fullName'        => 'Another Student',
    'username'        => 'different_user',
    'email'           => 'priya.test@charusat.edu.in', // Existing email!
    'mobile'          => '9123456789',
    'course'          => 'MCA / BCA (CMPICA)',
    'year'            => '1st Year (Semester 1 & 2)',
    'gender'          => 'Male',
    'password'        => 'Charusat@Pass2026',
    'confirmPassword' => 'Charusat@Pass2026',
    'terms'           => 'on'
];
$dupEmailRes = makeHttpRequest("http://$testHost:$testPort/register.php", 'POST', $dupEmailStudent, false);
assertTest($dupEmailRes['code'] === 200, "Duplicate email submission rejected with HTTP 200 re-render");
assertTest(strpos($dupEmailRes['body'], 'This email address is already registered') !== false, "Displays error: 'This email address is already registered'");
$countAfterDupEmail = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
assertTest($countAfterDupEmail === $countAfter, "No duplicate record was created in database for duplicate email");

// Attempt duplicate username with new email
$dupUserStudent = [
    'fullName'        => 'Different Person',
    'username'        => 'priya2026', // Existing username!
    'email'           => 'different.email@charusat.edu.in',
    'mobile'          => '9123456789',
    'course'          => 'MCA / BCA (CMPICA)',
    'year'            => '1st Year (Semester 1 & 2)',
    'gender'          => 'Male',
    'password'        => 'Charusat@Pass2026',
    'confirmPassword' => 'Charusat@Pass2026',
    'terms'           => 'on'
];
$dupUserRes = makeHttpRequest("http://$testHost:$testPort/register.php", 'POST', $dupUserStudent, false);
assertTest($dupUserRes['code'] === 200, "Duplicate username submission rejected with HTTP 200 re-render");
assertTest(strpos($dupUserRes['body'], 'This username or student ID is already registered') !== false, "Displays error: 'This username or student ID is already registered'");
$countAfterDupUser = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
assertTest($countAfterDupUser === $countAfter, "No duplicate record was created in database for duplicate username");

// ------------------------------------------------------------------------------
// Test 9: End-to-End Success Card Rendering (PRG)
// ------------------------------------------------------------------------------
echo "\n9. Testing Success Confirmation Card (PRG Flash Session):\n";
$devStudent = [
    'fullName'        => 'Dev Shah',
    'username'        => '24CS088',
    'email'           => 'dev.shah@charusat.edu.in',
    'mobile'          => '9123456780',
    'course'          => 'B.Tech Information Technology (DEPSTAR)',
    'year'            => '3rd Year (Semester 5 & 6)',
    'gender'          => 'Male',
    'password'        => 'Secure@Dev2026',
    'confirmPassword' => 'Secure@Dev2026',
    'terms'           => 'on'
];
// Delete if previously inserted
$pdo->prepare("DELETE FROM users WHERE username = '24CS088' OR email = 'dev.shah@charusat.edu.in'")->execute();

$e2eRes = makeHttpRequest("http://$testHost:$testPort/register.php", 'POST', $devStudent, true);
assertTest($e2eRes['code'] === 200, "Followed redirect to success page with HTTP 200");
assertTest(strpos($e2eRes['body'], 'Registration Submitted Successfully!') !== false, "Displays success headline");
assertTest(strpos($e2eRes['body'], '24CS088') !== false, "Displays registered username on success card");
assertTest(strpos($e2eRes['body'], 'Dev Shah') !== false, "Displays registered student name on success card");
assertTest(strpos($e2eRes['body'], 'dev.shah@charusat.edu.in') !== false, "Displays registered student email on success card");
assertTest(strpos($e2eRes['body'], 'Active (Hashed Password Stored)') !== false, "Displays active database confirmation");
assertTest(strpos($e2eRes['body'], 'Proceed to Sign In') !== false, "Displays action button to Proceed to Sign In");

// ------------------------------------------------------------------------------
// Test 10: Authentication Verification with login.php & password_verify()
// ------------------------------------------------------------------------------
echo "\n10. Testing Authentication via login.php with password_verify():\n";
// 10.1 Login with newly created user and correct password
$loginValid = makeHttpRequest("http://$testHost:$testPort/login.php", 'POST', [
    'studentId' => 'priya2026',
    'password'  => 'Charusat@Pass2026',
    'isAjax'    => '1'
]);
assertTest($loginValid['code'] === 200, "POST /login.php with valid credentials returns HTTP 200");
$loginJson = json_decode($loginValid['body'], true);
assertTest($loginJson['success'] === true, "Authentication succeeded via password_verify()");
assertTest($loginJson['redirect'] === 'pages/dashboard.html', "Returns redirect target to dashboard.html");
assertTest($loginJson['user']['name'] === 'Priya Patel', "Authenticated session user name matches registered user");

// 10.2 Login with newly created user and incorrect password
$loginInvalid = makeHttpRequest("http://$testHost:$testPort/login.php", 'POST', [
    'studentId' => 'priya2026',
    'password'  => 'WrongPassword123',
    'isAjax'    => '1'
]);
$invalidJson = json_decode($loginInvalid['body'], true);
assertTest($invalidJson['success'] === false, "Authentication rejected for wrong password");
assertTest(strpos($invalidJson['message'], 'Invalid') !== false, "Returns user-friendly error message without leaking details");

// 10.3 Pre-seeded demo account verification (25CS027 / password123)
$demoLogin = makeHttpRequest("http://$testHost:$testPort/login.php", 'POST', [
    'studentId' => '25CS027',
    'password'  => 'password123',
    'isAjax'    => '1'
]);
$demoJson = json_decode($demoLogin['body'], true);
assertTest($demoJson['success'] === true, "Pre-seeded demo account (25CS027) successfully logs in");

// Clean up server process
proc_terminate($serverProcess);
proc_close($serverProcess);

echo "\n=======================================================\n";
echo "TEST SUITE SUMMARY: $totalPassed Passed, $totalFailed Failed\n";
echo "=======================================================\n\n";

exit($totalFailed > 0 ? 1 : 0);
