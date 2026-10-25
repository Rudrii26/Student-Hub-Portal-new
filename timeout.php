<?php
/**
 * CHARUSAT Student Hub - Session Timeout Guard Module
 * Enforces a configurable 15-minute inactivity timeout on all protected dashboard requests.
 */

// Inactivity timeout threshold in seconds (15 minutes = 900 seconds)
if (!defined('SESSION_TIMEOUT_DURATION')) {
    define('SESSION_TIMEOUT_DURATION', 900);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates session activity and terminates the session if inactivity exceeds the threshold.
 *
 * @param int $timeoutThreshold Seconds before inactivity triggers timeout
 * @return void
 */
function validateSessionActivity(int $timeoutThreshold = SESSION_TIMEOUT_DURATION): void {
    if (!isset($_SESSION['user_id'])) {
        return; // Unauthenticated users are handled by authentication guards
    }

    $currentTime = time();

    if (isset($_SESSION['last_activity'])) {
        $inactiveSeconds = $currentTime - (int)$_SESSION['last_activity'];

        if ($inactiveSeconds > $timeoutThreshold) {
            // Inactivity threshold exceeded: perform complete secure session destruction
            $_SESSION = [];

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

            session_destroy();

            // Redirect unauthenticated user with clear timeout reason
            header("Location: login.php?error=timeout");
            exit;
        }
    }

    // Refresh last activity timestamp to current request time
    $_SESSION['last_activity'] = $currentTime;
}

// Automatically enforce timeout verification on file include if user session exists
validateSessionActivity();
