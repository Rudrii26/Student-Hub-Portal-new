<?php
/**
 * CHARUSAT Student Hub - User Login Portal (login.php)
 * Displays the responsive, accessible authentication form and delegates POST requests to authenticate.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect directly to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit;
    } else {
        header("Location: student_dashboard.php");
        exit;
    }
}

// Forward direct POST requests seamlessly to the authentication processor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/authenticate.php';
    exit;
}

// Parse status and notification messages
$errorMessage      = '';
$successMessage    = '';
$enteredIdentifier = '';

if (isset($_GET['identifier'])) {
    $enteredIdentifier = trim((string)$_GET['identifier']);
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'timeout') {
        $errorMessage = "Your session expired after 15 minutes of inactivity. Please sign in again.";
    } elseif ($_GET['error'] === 'unauthorized') {
        $errorMessage = "You must be signed in to access the portal dashboard.";
    } elseif ($_GET['error'] === 'denied') {
        $errorMessage = "Access denied: You do not possess the required permissions for that resource.";
    } else {
        $errorMessage = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
    }
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $successMessage = "You have been logged out securely. Your session has been fully terminated.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - CHARUSAT Student Hub Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="landing-body">
    <!-- Ambient Background Glow Accents -->
    <div class="ambient-glow-orb ambient-glow-1"></div>
    <div class="ambient-glow-orb ambient-glow-2"></div>

    <div class="login-container" style="max-width: 480px;">
        <div class="login-brand">
            <div class="login-brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            </div>
            <h1>Portal Sign In</h1>
            <p class="login-subtitle">CHARUSAT e-Governance & Student Career OS</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="form-alert-banner alert-danger" role="alert" style="margin-bottom: 20px;">
                <div class="alert-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <strong>Authentication Notice:</strong>
                    <div><?= $errorMessage ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="form-alert-banner alert-success" role="alert" style="margin-bottom: 20px; background: #ecfdf5; border-color: #a7f3d0; color: #065f46;">
                <div class="alert-icon" style="color: #059669;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <strong>Logged Out:</strong>
                    <div><?= $successMessage ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Primary Authentication Form -->
        <form id="loginForm" method="POST" action="authenticate.php" autocomplete="off" novalidate>
            <!-- Email / Identifier Input -->
            <div class="form-group" style="margin-bottom: 18px;">
                <label for="loginEmail">
                    <span>Email Address, Student/Admin ID, or Username <span class="required-star">*</span></span>
                </label>
                <div class="input-icon-wrapper">
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>
                    </div>
                    <input type="text" id="loginEmail" name="login_identifier" value="<?= htmlspecialchars($enteredIdentifier, ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. admin, 25CS027, admin@charusat.edu.in, or ID" required autocomplete="username" autofocus>
                </div>
            </div>

            <!-- Password Input -->
            <div class="form-group" style="margin-bottom: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="loginPassword" style="margin-bottom: 0;">
                        <span>Password <span class="required-star">*</span></span>
                    </label>
                    <a href="pages/forgot-password.html" class="forgot-link" style="font-size: 12.5px; color: var(--primary-color); text-decoration: none;">Forgot password?</a>
                </div>
                <div class="input-icon-wrapper password-box">
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required autocomplete="current-password">
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="login-button" id="loginSubmitBtn">
                <span>Sign In to Dashboard</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </form>

        <div style="margin-top: 24px; text-align: center; font-size: 13.5px; color: var(--text-muted);">
            Don't have an account yet?
            <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none; margin-left: 4px;">Register here</a>
        </div>

        <a href="index.html" class="back-link" style="margin-top: 18px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg>
            <span>Back to Portal Home</span>
        </a>
    </div>

    <script>
        // Client-side validation helper for immediate user feedback
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const identifier = document.getElementById('loginEmail')?.value.trim();
            const pass = document.getElementById('loginPassword')?.value;
            if (!identifier || !pass) {
                e.preventDefault();
                alert('Please enter your identifier (Student/Admin ID, username, or email) and password.');
            }
        });
    </script>
</body>
</html>
