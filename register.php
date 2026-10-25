<?php
/**
 * ==============================================================================
 * CHARUSAT STUDENT HUB - USER REGISTRATION SYSTEM & ENDPOINT
 * ==============================================================================
 * Features:
 * 1. Server-side validation (empty-field, format, email, mobile, password policy)
 * 2. Duplicate email and duplicate username validation against MySQL database
 * 3. Secure password hashing with password_hash(PASSWORD_DEFAULT)
 * 4. Prepared statements with PDO to eliminate SQL injection vulnerabilities
 * 5. Persistent database insertion into MySQL 'users' table
 * 6. Dual storage synchronization (MySQL + JSON/CSV data backups)
 * 7. Post-Redirect-Get (PRG) pattern with session flash messaging
 * 8. Sanitized output via htmlspecialchars() and field-specific error states
 * ==============================================================================
 */

// Start session for Post-Redirect-Get success messaging
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

// Determine relative paths based on execution context
$isInsidePages = (basename(__DIR__) === 'pages') || (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/pages/') !== false);
$assetPrefix   = $isInsidePages ? '../' : './';
$actionUrl     = $isInsidePages ? 'register.php' : 'register.php';
$rootDir       = $isInsidePages ? dirname(__DIR__) : __DIR__;
$dataDir       = $rootDir . '/data';
$dataFile      = $dataDir . '/registrations.json';
$csvFile       = $dataDir . '/registrations.csv';

// Include centralized database configuration
require_once $rootDir . '/config/db.php';

// Whitelists of recognized university degree programs and academic years
$validCourses = [
    'B.Tech Computer Engineering (CSPIT)',
    'B.Tech Information Technology (CSPIT)',
    'B.Tech Computer Engineering (DEPSTAR)',
    'B.Tech Information Technology (DEPSTAR)',
    'B.Tech CSE - AI & ML (DEPSTAR)',
    'MCA / BCA (CMPICA)',
    'B.Pharm / M.Pharm (RPCP)',
    'BBA / MBA (I2IM)'
];

$validYears = [
    '1st Year (Semester 1 & 2)',
    '2nd Year (Semester 3 & 4)',
    '3rd Year (Semester 5 & 6)',
    '4th Year (Semester 7 & 8)'
];

$validGenders = ['Male', 'Female', 'Other'];

// View state variables
$errors        = [];
$globalError   = '';
$isSuccessView = false;
$successData   = null;

$formData = [
    'fullName' => '',
    'username' => '',
    'email'    => '',
    'mobile'   => '',
    'course'   => '',
    'year'     => '',
    'gender'   => '',
    'terms'    => false
];

// ------------------------------------------------------------------------------
// 1. Handle Post-Redirect-Get Success Retrieval (GET ?status=success)
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['status']) && $_GET['status'] === 'success') {
    if (isset($_SESSION['charusat_registration_success'])) {
        $isSuccessView = true;
        $successData   = $_SESSION['charusat_registration_success'];
        // One-time flash message: unset so refresh returns cleanly
        unset($_SESSION['charusat_registration_success']);
    } else {
        // Direct GET with ?status=success but no session -> redirect to fresh form
        header("Location: " . $actionUrl);
        exit;
    }
}

// ------------------------------------------------------------------------------
// 2. Handle POST Submission & Server-Side Validation
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2.1 Trim and sanitize incoming input values
    $fullName        = isset($_POST['fullName']) ? trim((string)$_POST['fullName']) : '';
    $username        = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $email           = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $mobile          = isset($_POST['mobile']) ? trim((string)$_POST['mobile']) : '';
    $course          = isset($_POST['course']) ? trim((string)$_POST['course']) : '';
    $year            = isset($_POST['year']) ? trim((string)$_POST['year']) : '';
    $gender          = isset($_POST['gender']) ? trim((string)$_POST['gender']) : '';
    $password        = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? (string)$_POST['confirmPassword'] : '';
    $termsAccepted   = isset($_POST['terms']) && in_array($_POST['terms'], ['on', '1', 'true', 'yes'], true);

    // Retain safe values for repopulation (passwords are NEVER repopulated)
    $formData = [
        'fullName' => $fullName,
        'username' => $username,
        'email'    => $email,
        'mobile'   => $mobile,
        'course'   => $course,
        'year'     => $year,
        'gender'   => $gender,
        'terms'    => $termsAccepted
    ];

    // 2.2 Field 1: Full Name Validation
    if ($fullName === '') {
        $errors['fullName'] = 'Full Name is required.';
    } elseif (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 70) {
        $errors['fullName'] = 'Full Name must be between 3 and 70 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s'\-]+$/", $fullName)) {
        $errors['fullName'] = 'Full Name can only contain letters, spaces, hyphens, and apostrophes.';
    }

    // 2.3 Field 2: Username / Student ID Validation
    if ($username === '') {
        $errors['username'] = 'Username / Student ID is required.';
    } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
        $errors['username'] = 'Username must be between 3 and 30 characters.';
    } elseif (!preg_match("/^[a-zA-Z0-9_\-\.]+$/", $username)) {
        $errors['username'] = 'Username can only contain alphanumeric characters, dots, hyphens, and underscores.';
    }

    // 2.4 Field 3: Email Address Validation
    if ($email === '') {
        $errors['email'] = 'Email Address is required.';
    } elseif (mb_strlen($email) > 100) {
        $errors['email'] = 'Email Address must not exceed 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address (e.g. student@charusat.edu.in).';
    }

    // 2.5 Field 4: Mobile Number Validation
    $cleanMobile = preg_replace('/[\s\-]/', '', $mobile);
    if ($mobile === '') {
        $errors['mobile'] = 'Mobile Number is required.';
    } elseif (!preg_match('/^(\+91)?[0]?[6-9]\d{9}$/', $cleanMobile)) {
        $errors['mobile'] = 'Mobile Number must be a valid 10-digit number starting with 6, 7, 8, or 9.';
    } else {
        if (str_starts_with($cleanMobile, '+91')) {
            $cleanMobile = substr($cleanMobile, 3);
        } elseif (str_starts_with($cleanMobile, '0') && strlen($cleanMobile) === 11) {
            $cleanMobile = substr($cleanMobile, 1);
        }
        $formData['mobile'] = $cleanMobile;
    }

    // 2.6 Field 5: Enrolled Course Whitelist Validation
    if ($course === '') {
        $errors['course'] = 'Please select your enrolled degree program.';
    } elseif (!in_array($course, $validCourses, true)) {
        $errors['course'] = 'Selected degree program is invalid. Please select from the dropdown.';
    }

    // 2.7 Field 6: Academic Year Whitelist Validation
    if ($year === '') {
        $errors['year'] = 'Please select your academic year.';
    } elseif (!in_array($year, $validYears, true)) {
        $errors['year'] = 'Selected academic year is invalid. Please select from the dropdown.';
    }

    // 2.8 Field 7: Gender Identity Whitelist Validation
    if ($gender === '') {
        $errors['gender'] = 'Please select your gender identity.';
    } elseif (!in_array($gender, $validGenders, true)) {
        $errors['gender'] = 'Selected gender identity is invalid.';
    }

    // 2.9 Field 8: Password Policy Validation
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    } elseif (strlen($password) > 64) {
        $errors['password'] = 'Password must not exceed 64 characters.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors['password'] = 'Password must include uppercase, lowercase, numbers, and special symbols.';
    }

    // 2.10 Field 9: Confirm Password Match
    if ($confirmPassword === '') {
        $errors['confirmPassword'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }

    // 2.11 Field 10: Terms & Conditions Agreement
    if (!$termsAccepted) {
        $errors['terms'] = 'You must accept the CHARUSAT Academic Code of Conduct & Portal Terms to proceed.';
    }

    // 2.12 Database Uniqueness Validation (Prepared Statements for Email & Username)
    $pdo = null;
    try {
        $pdo = getDbConnection();

        // Check duplicate email in MySQL
        if (empty($errors['email'])) {
            $stmtEmail = $pdo->prepare("SELECT id FROM `users` WHERE `email` = :email LIMIT 1");
            $stmtEmail->execute([':email' => $email]);
            if ($stmtEmail->fetch()) {
                $errors['email'] = 'This email address is already registered. Please sign in or use another email.';
            }
        }

        // Check duplicate username in MySQL
        if (empty($errors['username'])) {
            $stmtUser = $pdo->prepare("SELECT id FROM `users` WHERE `username` = :username LIMIT 1");
            $stmtUser->execute([':username' => $username]);
            if ($stmtUser->fetch()) {
                $errors['username'] = 'This username or student ID is already registered. Please choose another or sign in.';
            }
        }
    } catch (Exception $dbEx) {
        error_log("Database Uniqueness Check Error: " . $dbEx->getMessage());
        $globalError = "Database connectivity notice: Unable to verify account availability. Please try again.";
    }

    // --------------------------------------------------------------------------
    // 3. Process Valid Submission: Password Hashing & MySQL Insertion
    // --------------------------------------------------------------------------
    if (empty($errors) && empty($globalError)) {
        try {
            if (!$pdo) {
                $pdo = getDbConnection();
            }

            // Generate unique Registration Reference Identifier
            $regRefId = 'REG-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            // Hash the password securely using password_hash(PASSWORD_DEFAULT)
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into MySQL with prepared statement
            $insertSql = "
                INSERT INTO `users` (
                    `registration_id`, `username`, `full_name`, `email`, `mobile`,
                    `course`, `academic_year`, `gender`, `password_hash`, `status`
                ) VALUES (
                    :reg_id, :username, :full_name, :email, :mobile,
                    :course, :academic_year, :gender, :password_hash, 'Active'
                )
            ";
            $stmtInsert = $pdo->prepare($insertSql);
            $stmtInsert->execute([
                ':reg_id'        => $regRefId,
                ':username'      => $username,
                ':full_name'     => $fullName,
                ':email'         => $email,
                ':mobile'        => $cleanMobile,
                ':course'        => $course,
                ':academic_year' => $year,
                ':gender'        => $gender,
                ':password_hash' => $passwordHash
            ]);

            // Synchronize with JSON storage file with exclusive concurrency lock
            if (!is_dir($dataDir)) {
                @mkdir($dataDir, 0755, true);
            }
            $htaccessPath = $dataDir . '/.htaccess';
            if (!file_exists($htaccessPath)) {
                @file_put_contents($htaccessPath, "<FilesMatch \"\\.(json|csv|log)$\">\n    Require all denied\n</FilesMatch>\n");
            }

            $fp = @fopen($dataFile, 'c+');
            if ($fp && flock($fp, LOCK_EX)) {
                $existingData = [];
                $fileSize = @filesize($dataFile);
                if ($fileSize && $fileSize > 0) {
                    $raw = fread($fp, $fileSize);
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $existingData = $decoded;
                    }
                }

                $newRecord = [
                    'registrationId' => $regRefId,
                    'username'       => $username,
                    'fullName'       => $fullName,
                    'email'          => $email,
                    'mobile'         => $cleanMobile,
                    'course'         => $course,
                    'year'           => $year,
                    'gender'         => $gender,
                    'submittedAt'    => date('Y-m-d H:i:s'),
                    'status'         => 'Submitted'
                ];
                $existingData[] = $newRecord;

                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
            }

            // Synchronize with CSV storage
            $isNewCsv = !file_exists($csvFile) || (@filesize($csvFile) === 0);
            $csvFp = @fopen($csvFile, 'a');
            if ($csvFp && flock($csvFp, LOCK_EX)) {
                if ($isNewCsv) {
                    fputcsv($csvFp, ['Registration ID', 'Username', 'Full Name', 'Email Address', 'Mobile Number', 'Enrolled Course', 'Academic Year', 'Gender Identity', 'Submitted At', 'Status']);
                }
                fputcsv($csvFp, [
                    $regRefId,
                    $username,
                    $fullName,
                    $email,
                    $cleanMobile,
                    $course,
                    $year,
                    $gender,
                    date('Y-m-d H:i:s'),
                    'Submitted'
                ]);
                fflush($csvFp);
                flock($csvFp, LOCK_UN);
                fclose($csvFp);
            }

            // Save confirmation details in session for Post-Redirect-Get display
            $_SESSION['charusat_registration_success'] = [
                'registrationId' => $regRefId,
                'username'       => $username,
                'fullName'       => $fullName,
                'email'          => $email,
                'mobile'         => $cleanMobile,
                'course'         => $course,
                'year'           => $year,
                'gender'         => $gender,
                'submittedAt'    => date('d M Y, h:i A')
            ];

            // Redirect to prevent duplicate form submissions on browser reload
            header("Location: " . $actionUrl . "?status=success");
            exit;

        } catch (PDOException $pdoEx) {
            error_log("CHARUSAT Registration Database Error: " . $pdoEx->getMessage());
            if ($pdoEx->errorInfo[1] === 1062) {
                // Duplicate entry caught by MySQL UNIQUE constraint
                if (strpos($pdoEx->getMessage(), 'email') !== false) {
                    $errors['email'] = 'This email address is already registered.';
                } elseif (strpos($pdoEx->getMessage(), 'username') !== false) {
                    $errors['username'] = 'This username or student ID is already taken.';
                }
                $globalError = "Registration could not be completed because this account already exists.";
            } else {
                $globalError = "A database error occurred while creating your account. Please try again later.";
            }
        } catch (Exception $ex) {
            error_log("CHARUSAT Registration Processing Error: " . $ex->getMessage());
            $globalError = "An unexpected server error occurred. Please try again later.";
        }
    } else {
        if (empty($globalError)) {
            $globalError = "Registration could not be submitted. Please correct the highlighted errors below.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - CHARUSAT Student Portal</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($assetPrefix) ?>css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="landing-body">
    <!-- Ambient Background Mesh Glow Orbs -->
    <div class="ambient-glow-orb ambient-glow-1"></div>
    <div class="ambient-glow-orb ambient-glow-2"></div>

    <div class="login-container" style="max-width: 650px;">
        <div class="login-brand">
            <div class="login-brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
            </div>
            <h1>New Student Registration</h1>
            <p class="login-subtitle">Enroll your profile on CHARUSAT e-Governance Portal</p>
        </div>

        <?php if ($isSuccessView && $successData): ?>
            <!-- ================================================================
                 Post-Redirect-Get Success Confirmation Card
                 ================================================================ -->
            <div class="registration-success-card" role="status" aria-live="polite">
                <div class="success-icon-badge">✓</div>
                <h3>Registration Submitted Successfully!</h3>
                <p>Your student profile has been verified and permanently registered in the CHARUSAT MySQL Database.</p>
                
                <div class="registration-summary-box">
                    <div class="registration-summary-row">
                        <span>Registration Reference:</span>
                        <span style="font-family: var(--font-mono); color: var(--primary-color); font-weight: 700;"><?= htmlspecialchars($successData['registrationId'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Username / Student ID:</span>
                        <span style="font-family: var(--font-mono); font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($successData['username'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Full Name:</span>
                        <span><?= htmlspecialchars($successData['fullName'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Registered Email:</span>
                        <span><?= htmlspecialchars($successData['email'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Contact Mobile:</span>
                        <span><?= htmlspecialchars($successData['mobile'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Enrolled Degree:</span>
                        <span><?= htmlspecialchars($successData['course'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Academic Year:</span>
                        <span><?= htmlspecialchars($successData['year'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Gender Identity:</span>
                        <span><?= htmlspecialchars($successData['gender'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Database Status:</span>
                        <span style="color: #10b981; font-weight: 600;">Active (Hashed Password Stored)</span>
                    </div>
                    <div class="registration-summary-row">
                        <span>Submission Time:</span>
                        <span><?= htmlspecialchars($successData['submittedAt'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <div style="width: 100%; display: flex; gap: 12px; margin-top: 14px; flex-wrap: wrap;">
                    <a href="<?= htmlspecialchars($assetPrefix) ?>index.html#login" class="login-button" style="flex: 1; min-width: 200px; text-decoration: none; justify-content: center;">
                        <span>Proceed to Sign In</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                    <a href="<?= htmlspecialchars($actionUrl) ?>" class="btn-toggle-pwd" style="padding: 10px 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: var(--text-main); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; font-size: 13.5px;">
                        <span>Register Another Student</span>
                    </a>
                </div>
            </div>

        <?php else: ?>

            <?php if (!empty($globalError)): ?>
                <!-- Global Server Validation Alert Banner -->
                <div class="form-alert-banner alert-danger" role="alert">
                    <div class="alert-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div>
                        <strong>Validation Notice:</strong>
                        <div><?= htmlspecialchars($globalError, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form with Server-Side Handling -->
            <form id="registrationForm" method="POST" action="<?= htmlspecialchars($actionUrl) ?>" novalidate>
                
                <!-- Row 1: Full Name & Username / Student ID (2-col grid) -->
                <div class="form-row-2col">
                    <div class="form-group">
                        <label for="regFullName">
                            <span>Full Name <span class="required-star">*</span></span>
                        </label>
                        <div class="input-icon-wrapper">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <input type="text" id="regFullName" name="fullName" class="<?= isset($errors['fullName']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($formData['fullName'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Kakadiya Rudri" required autocomplete="name" <?= isset($errors['fullName']) ? 'aria-invalid="true"' : '' ?>>
                        </div>
                        <?php if (isset($errors['fullName'])): ?>
                            <p class="field-error visible" id="fullNameError" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= htmlspecialchars($errors['fullName'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php else: ?>
                            <p class="field-error" id="fullNameError" role="alert"></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="regUsername">
                            <span>Username / Student ID <span class="required-star">*</span></span>
                        </label>
                        <div class="input-icon-wrapper">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>
                            </div>
                            <input type="text" id="regUsername" name="username" class="<?= isset($errors['username']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($formData['username'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. 25CS027 or rudri" required autocomplete="username" <?= isset($errors['username']) ? 'aria-invalid="true"' : '' ?>>
                        </div>
                        <?php if (isset($errors['username'])): ?>
                            <p class="field-error visible" id="usernameError" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= htmlspecialchars($errors['username'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php else: ?>
                            <p class="field-error" id="usernameError" role="alert"></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Row 2: Email & Mobile (2-col grid) -->
                <div class="form-row-2col">
                    <div class="form-group">
                        <label for="regEmail">
                            <span>Email Address <span class="required-star">*</span></span>
                        </label>
                        <div class="input-icon-wrapper">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <input type="email" id="regEmail" name="email" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. student@charusat.edu.in" required autocomplete="email" <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <p class="field-error visible" id="emailError" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php else: ?>
                            <p class="field-error" id="emailError" role="alert"></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="regMobile">
                            <span>Mobile Number <span class="required-star">*</span></span>
                        </label>
                        <div class="input-icon-wrapper">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <input type="tel" id="regMobile" name="mobile" class="<?= isset($errors['mobile']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($formData['mobile'], ENT_QUOTES, 'UTF-8') ?>" placeholder="10-digit mobile number" maxlength="15" required autocomplete="tel" <?= isset($errors['mobile']) ? 'aria-invalid="true"' : '' ?>>
                        </div>
                        <?php if (isset($errors['mobile'])): ?>
                            <p class="field-error visible" id="mobileError" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= htmlspecialchars($errors['mobile'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php else: ?>
                            <p class="field-error" id="mobileError" role="alert"></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Row 3: Course & Year (2-col grid) -->
                <div class="form-row-2col">
                    <div class="form-group">
                        <label for="regCourse">
                            <span>Enrolled Course <span class="required-star">*</span></span>
                        </label>
                        <select id="regCourse" name="course" class="<?= isset($errors['course']) ? 'is-invalid' : '' ?>" required <?= isset($errors['course']) ? 'aria-invalid="true"' : '' ?>>
                            <option value="">-- Select Degree Program --</option>
                            <?php foreach ($validCourses as $courseOption): ?>
                                <option value="<?= htmlspecialchars($courseOption, ENT_QUOTES, 'UTF-8') ?>" <?= ($formData['course'] === $courseOption) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($courseOption, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['course'])): ?>
                            <p class="field-error visible" id="courseError" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= htmlspecialchars($errors['course'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php else: ?>
                            <p class="field-error" id="courseError" role="alert"></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="regYear">
                            <span>Academic Year <span class="required-star">*</span></span>
                        </label>
                        <select id="regYear" name="year" class="<?= isset($errors['year']) ? 'is-invalid' : '' ?>" required <?= isset($errors['year']) ? 'aria-invalid="true"' : '' ?>>
                            <option value="">-- Select Academic Year --</option>
                            <?php foreach ($validYears as $yearOption): ?>
                                <option value="<?= htmlspecialchars($yearOption, ENT_QUOTES, 'UTF-8') ?>" <?= ($formData['year'] === $yearOption) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($yearOption, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['year'])): ?>
                            <p class="field-error visible" id="yearError" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= htmlspecialchars($errors['year'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php else: ?>
                            <p class="field-error" id="yearError" role="alert"></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Row 4: Gender Identity Radio Group -->
                <fieldset class="gender-fieldset <?= isset($errors['gender']) ? 'is-invalid' : '' ?>" id="genderFieldset">
                    <legend class="gender-legend">Gender Identity <span class="required-star">*</span></legend>
                    <div class="gender-radio-group">
                        <label class="gender-radio-item" for="genderMale">
                            <input type="radio" id="genderMale" name="gender" value="Male" <?= ($formData['gender'] === 'Male') ? 'checked' : '' ?> required>
                            <span>Male</span>
                        </label>
                        <label class="gender-radio-item" for="genderFemale">
                            <input type="radio" id="genderFemale" name="gender" value="Female" <?= ($formData['gender'] === 'Female') ? 'checked' : '' ?>>
                            <span>Female</span>
                        </label>
                        <label class="gender-radio-item" for="genderOther">
                            <input type="radio" id="genderOther" name="gender" value="Other" <?= ($formData['gender'] === 'Other') ? 'checked' : '' ?>>
                            <span>Other</span>
                        </label>
                    </div>
                </fieldset>
                <?php if (isset($errors['gender'])): ?>
                    <p class="field-error visible" id="genderError" role="alert" style="margin-top: -12px; margin-bottom: 14px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span><?= htmlspecialchars($errors['gender'], ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                <?php else: ?>
                    <p class="field-error" id="genderError" role="alert" style="margin-top: -12px; margin-bottom: 14px;"></p>
                <?php endif; ?>

                <!-- Row 5: Password & Confirm Password (2-col grid) -->
                <div class="form-row-2col">
                    <div class="form-group">
                        <label for="regPassword">
                            <span>Password <span class="required-star">*</span></span>
                        </label>
                        <div class="input-icon-wrapper password-box">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <!-- NEVER repopulate password field for security -->
                            <input type="password" id="regPassword" name="password" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="Min. 8 characters" minlength="8" required autocomplete="new-password" <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                            <button type="button" class="btn-toggle-pwd" id="toggleRegPassword" title="Toggle password visibility" aria-label="Toggle password visibility">
                                <svg id="regEyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        
                        <!-- Dynamic Password Strength Indicator -->
                        <div class="password-strength-container" id="passwordStrengthContainer" aria-live="polite">
                            <div class="strength-status-row">
                                <span>Strength:</span>
                                <span class="strength-badge" id="strengthBadge">None</span>
                            </div>
                            <div class="strength-meter-track">
                                <div class="strength-meter-fill" id="strengthMeterFill"></div>
                            </div>
                            <p class="password-hint-text">Req: 8+ chars (upper, lower, number & symbol).</p>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <p class="field-error visible" id="passwordError" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php else: ?>
                            <p class="field-error" id="passwordError" role="alert"></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="regConfirmPassword">
                            <span>Confirm Password <span class="required-star">*</span></span>
                        </label>
                        <div class="input-icon-wrapper password-box">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <!-- NEVER repopulate confirm password field for security -->
                            <input type="password" id="regConfirmPassword" name="confirmPassword" class="<?= isset($errors['confirmPassword']) ? 'is-invalid' : '' ?>" placeholder="Re-type password" required autocomplete="new-password" <?= isset($errors['confirmPassword']) ? 'aria-invalid="true"' : '' ?>>
                            <button type="button" class="btn-toggle-pwd" id="toggleRegConfirmPassword" title="Toggle confirm password visibility" aria-label="Toggle confirm password visibility">
                                <svg id="regConfirmEyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="field-success" id="confirmPasswordSuccess">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            <span>Passwords match</span>
                        </p>
                        <?php if (isset($errors['confirmPassword'])): ?>
                            <p class="field-error visible" id="confirmPasswordError" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span><?= htmlspecialchars($errors['confirmPassword'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        <?php else: ?>
                            <p class="field-error" id="confirmPasswordError" role="alert"></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Row 6: Terms & Conditions Checkbox -->
                <div class="terms-group">
                    <label class="auth-checkbox-label" for="regTerms">
                        <input type="checkbox" id="regTerms" name="terms" <?= $formData['terms'] ? 'checked' : '' ?> required>
                        <span>I agree to the CHARUSAT Academic Code of Conduct & Portal Terms <span class="required-star">*</span></span>
                    </label>
                    <?php if (isset($errors['terms'])): ?>
                        <p class="field-error visible" id="termsError" role="alert">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span><?= htmlspecialchars($errors['terms'], ENT_QUOTES, 'UTF-8') ?></span>
                        </p>
                    <?php else: ?>
                        <p class="field-error" id="termsError" role="alert"></p>
                    <?php endif; ?>
                </div>

                <!-- Row 7: Submit / Create Account Button -->
                <button type="submit" class="login-button" id="regSubmitBtn">
                    <span>Complete Student Registration</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </form>
        <?php endif; ?>

        <a href="<?= htmlspecialchars($assetPrefix) ?>index.html" class="back-link" style="margin-top: 22px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg>
            <span>Back to Portal Sign In</span>
        </a>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <script src="<?= htmlspecialchars($assetPrefix) ?>js/script.js"></script>
</body>
</html>
