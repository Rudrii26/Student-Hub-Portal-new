<?php
/**
 * CHARUSAT Student Hub - Secure Session Termination & Logout
 * Fully purges session state, expires session cookies, and destroys session on the server.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Unset all session variables
$_SESSION = [];

// 2. Clear session cookie from user browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destroy session data in server storage
session_destroy();

// 4. Redirect to login with confirmation parameter
header("Location: login.php?logout=success");
exit;
