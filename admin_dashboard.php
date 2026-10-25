<?php
/**
 * CHARUSAT Student Hub - Administrator Management Dashboard (admin_dashboard.php)
 * Protected portal page accessible exclusively to users with role = 'admin'.
 * Employs the identical CHARUSAT e-Governance UI/UX design system (bento grid, dark/light themes,
 * sidebar navigation, aura hero banner) customized for university administration.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Enforce 15-minute inactivity session timeout on every request
require_once __DIR__ . '/timeout.php';

// 2. Enforce authentication guard: Unauthenticated visitors redirected to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// 3. Enforce strict role boundary check (HTTP 403 on role mismatch)
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - Administrator Access Denied</title>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/portal.css">
        <link rel="stylesheet" href="style.css">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    </head>
    <body class="landing-body">
        <div class="ambient-glow-orb ambient-glow-1"></div>
        <div class="access-denied-container" role="alert" aria-live="assertive">
            <div class="access-denied-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <h1 style="font-size: 24px; color: var(--danger, #f43f5e); margin-bottom: 8px;">HTTP 403: Access Denied</h1>
            <p style="color: var(--text-muted); font-size: 15px; margin-bottom: 24px;">
                You are currently signed in as a <strong>Student</strong> (<?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>). The requested Administrator Management Dashboard requires elevated staff administrative privileges.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="student_dashboard.php" class="btn btn-primary" style="text-decoration: none; padding: 10px 20px; font-size: 14px;">
                    <span>Return to Student Dashboard</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
                <a href="logout.php" class="btn btn-secondary" style="text-decoration: none; padding: 10px 18px; font-size: 14px; font-weight: 600;">
                    <span>Sign Out</span>
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 4. Authorized Admin Flow: Extract session details
$adminName     = $_SESSION['full_name'] ?? 'CHARUSAT Administrator';
$adminUsername = $_SESSION['username'] ?? 'ADMIN_CHARUSAT';
$adminEmail    = $_SESSION['email'] ?? 'admin@charusat.edu.in';
$adminRef      = $_SESSION['registration_id'] ?? 'REG-ADMIN-01';

// Dynamic initials
$parts = preg_split('/\s+/', trim($adminName));
$initials = '';
foreach ($parts as $p) {
    if ($p !== '') $initials .= strtoupper(mb_substr($p, 0, 1));
}
if (strlen($initials) > 2) {
    $initials = substr($initials, 0, 1) . substr($initials, -1);
}
if ($initials === '') $initials = 'AD';

$shortName = $adminName;
if (count($parts) > 2) {
    $shortName = $parts[0] . ' ' . $parts[count($parts) - 1];
}

$currentHour = (int)date('G');
$greeting = ($currentHour >= 5 && $currentHour < 12) ? 'Good Morning' : (($currentHour >= 12 && $currentHour < 17) ? 'Good Afternoon' : 'Good Evening');

// 5. Query metrics and users from MySQL
require_once __DIR__ . '/db.php';
$conn = getMysqliConnection();

$totalUsers = 0;
$totalStudents = 0;
$totalAdmins = 0;
$totalDeleted = 0;
$recentUsers = [];

try {
    $resStats = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN role = 'student' AND (is_deleted = 0 OR is_deleted IS NULL) THEN 1 ELSE 0 END) AS students,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admins,
            SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) AS deleted_students
        FROM users
    ");
    if ($resStats && $row = $resStats->fetch_assoc()) {
        $totalUsers    = (int)$row['total'];
        $totalStudents = (int)$row['students'];
        $totalAdmins   = (int)$row['admins'];
        $totalDeleted  = (int)($row['deleted_students'] ?? 0);
    }

    $resRecent = $conn->query("
        SELECT id, registration_id, username, full_name, email, role, status, is_deleted, created_at
        FROM users
        ORDER BY id DESC
        LIMIT 10
    ");
    if ($resRecent) {
        while ($userRow = $resRecent->fetch_assoc()) {
            $recentUsers[] = $userRow;
        }
    }

    $totalEvents = 0;
    $totalRegistrations = 0;
    $resEvents = $conn->query("SELECT COUNT(*) AS total FROM events WHERE is_deleted = 0");
    if ($resEvents && $row = $resEvents->fetch_assoc()) {
        $totalEvents = (int)$row['total'];
    }
    $resRegs = $conn->query("SELECT COUNT(*) AS total FROM event_registrations");
    if ($resRegs && $row = $resRegs->fetch_assoc()) {
        $totalRegistrations = (int)$row['total'];
    }
} catch (Exception $e) {
    error_log("Admin Dashboard Metrics Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard - CHARUSAT Student Hub Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/portal.css">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="portal-body">
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>

    <div class="portal-layout">
        <!-- Sidebar Navigation -->
        <aside class="portal-sidebar">
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
                <a href="admin_dashboard.php" class="nav-link active">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg></span>
                    <span>Admin Dashboard</span>
                </a>
                <a href="index.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                    <span>Public Homepage</span>
                </a>

                <div class="nav-section-title">Student Management (CRUD)</div>
                <a href="admin_students.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <span>Manage Students (CRUD)</span>
                </a>
                <a href="admin_students.php?action=new#add" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></span>
                    <span>Add New Student</span>
                </a>
                <a href="export_students.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
                    <span>Export Students (CSV)</span>
                </a>

                <div class="nav-section-title">Practical 3: Event Management</div>
                <a href="admin_events.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <span>Manage Events (CRUD)</span>
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
                <a href="#user-directory" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg></span>
                    <span>Database Directory</span>
                </a>
                <a href="timeout.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg></span>
                    <span>Session Security (15m)</span>
                </a>

                <div class="nav-section-title">External University Portals</div>
                <a href="https://support.charusat.edu.in/egov/home.aspx" target="_blank" rel="noopener noreferrer" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span>
                    <span>Official e-Gov Portal</span>
                </a>
                <a href="https://support.charusat.edu.in/UniExamResult/" target="_blank" rel="noopener noreferrer" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg></span>
                    <span>Exam Results Portal</span>
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

        <!-- Main Portal Content -->
        <main class="portal-main">
            <!-- Header -->
            <header class="portal-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                    <div class="header-title-box">
                        <h1>CHARUSAT Administrative Command Center</h1>
                        <div class="header-breadcrumb">
                            <a href="index.html" style="color: inherit; text-decoration: none;">CHARUSAT</a>
                            <span>/</span>
                            <span>Administrative Console</span>
                            <span>/</span>
                            <span style="color: var(--accent-amber); font-weight: 600;">System Management</span>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Role Badge Pill for Test & RBAC Identity -->
                    <span class="role-badge admin">ADMIN</span>

                    <!-- Quick Links to Full Student & Event CRUD -->
                    <a href="admin_students.php" class="btn btn-sm btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Student Management</span>
                    </a>
                    <a href="admin_events.php" class="btn btn-sm btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Event Management</span>
                    </a>

                    <!-- Theme Switcher -->
                    <button class="header-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Theme">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div style="position: relative;">
                        <button class="header-btn" id="notifBtn" onclick="toggleNotifications()" title="Notifications">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            <span class="notification-badge-count">2</span>
                        </button>

                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h4>Administrative Alerts</h4>
                                <button class="btn-clear-notif" onclick="markAllNotificationsRead()">Mark all read</button>
                            </div>
                            <div class="notification-list">
                                <div class="notification-item unread" onclick="window.location.href='admin_events.php'">
                                    <div class="notif-title">Campus Events Live</div>
                                    <div class="notif-desc">Database has <?= $totalEvents ?> active campus events published.</div>
                                    <div class="notif-time">Active</div>
                                </div>
                                <div class="notification-item unread" onclick="window.location.href='admin_students.php'">
                                    <div class="notif-title">Database Status Active</div>
                                    <div class="notif-desc">MySQL database connected with <?= $totalUsers ?> registered user accounts.</div>
                                    <div class="notif-time">Just now</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Dropdown -->
                    <div style="position: relative;">
                        <button class="header-btn" id="profileDropdownBtn" onclick="toggleProfileMenu()" title="Account">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </button>
                        <div class="profile-dropdown" id="profileDropdown">
                            <div style="padding: 12px 14px; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 700; font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="font-size: 11.5px; color: var(--text-muted); font-family: var(--font-mono);"><?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="margin-top: 6px;"><span class="role-badge admin">ADMINISTRATOR</span></div>
                            </div>
                            <a href="admin_events.php" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span>Manage Events (CRUD)</span>
                            </a>
                            <a href="admin_students.php" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                <span>Manage Students</span>
                            </a>
                            <a href="export_students.php" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span>Export Students CSV</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item" style="color: var(--danger); text-decoration: none;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Bento Canvas -->
            <div class="portal-content">
                <!-- Aura Hero Banner -->
                <div class="hero-aura-banner" style="background: linear-gradient(135deg, #1e1b4b 0%, #1e293b 35%, #0f172a 70%, #312e81 100%);">
                    <div class="hero-banner-flex">
                        <div>
                            <div class="hero-meta-pill">
                                <span class="live-pulse-dot" style="background: var(--accent-amber); box-shadow: 0 0 12px var(--accent-amber);"></span>
                                <span>CHARUSAT &bull; System Administration &bull; RBAC Protected</span>
                            </div>
                            <div class="hero-greeting">
                                <h2><?= $greeting ?>, <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?> 🛡️</h2>
                            </div>
                            <div class="hero-details-text">
                                Admin Account: <strong><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?></strong> &bull; Ref: <strong><?= htmlspecialchars($adminRef, ENT_QUOTES, 'UTF-8') ?></strong> &bull; Official Email: <strong><?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') ?></strong> &bull; Session Active (15m Inactivity Window)
                            </div>
                        </div>
                        <div class="hero-cta-group">
                            <a href="admin_events.php" class="btn btn-hero-white">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span>Manage Events (CRUD)</span>
                            </a>
                            <a href="admin_students.php" class="btn btn-hero-glass">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                <span>Manage Students</span>
                            </a>
                            <a href="export_students.php" class="btn btn-hero-glass">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span>Export CSV</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bento Grid Container -->
                <div class="bento-grid">
                    <!-- Stat Card 1: Total Users -->
                    <div class="portal-card stat-card-bento">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Registered Accounts</span>
                            <div class="stat-card-icon" style="background: rgba(99, 102, 241, 0.12); color: var(--primary-color);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                        </div>
                        <div class="stat-card-value"><?= $totalUsers ?></div>
                        <div class="stat-card-footer">
                            <span class="badge badge-primary">Total Database Records</span>
                            <a href="#user-directory" class="stat-card-link">View Directory &rarr;</a>
                        </div>
                    </div>

                    <!-- Stat Card 2: Enrolled Students -->
                    <div class="portal-card stat-card-bento">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Enrolled Students</span>
                            <div class="stat-card-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent-cyan);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            </div>
                        </div>
                        <div class="stat-card-value" style="color: var(--accent-cyan);"><?= $totalStudents ?></div>
                        <div class="stat-card-footer">
                            <span class="badge badge-info">Active Student Profiles</span>
                            <a href="admin_students.php" class="stat-card-link">Manage CRUD &rarr;</a>
                        </div>
                    </div>

                    <!-- Stat Card 3: Staff Administrators -->
                    <div class="portal-card stat-card-bento">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Staff Administrators</span>
                            <div class="stat-card-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--accent-amber);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                        </div>
                        <div class="stat-card-value" style="color: var(--accent-amber);"><?= $totalAdmins ?></div>
                        <div class="stat-card-footer">
                            <span class="badge badge-warning">Elevated Privileges</span>
                            <span style="font-size: 11.5px; color: var(--text-muted); font-family: var(--font-mono);">Admin Access</span>
                        </div>
                    </div>

                    <!-- Stat Card 4: Database Health -->
                    <div class="portal-card stat-card-bento">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Database Status</span>
                            <div class="stat-card-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                            </div>
                        </div>
                        <div class="stat-card-value" style="font-size: 20px; color: var(--success); padding-top: 4px;">MySQL Connected</div>
                        <div class="stat-card-footer">
                            <span class="badge badge-success">student_hub_db</span>
                            <span style="font-size: 11.5px; color: var(--text-muted); font-family: var(--font-mono);">UTF8MB4</span>
                        </div>
                    </div>

                    <!-- Stat Card 5: Campus Events & Registrations -->
                    <div class="portal-card stat-card-bento">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Campus Events</span>
                            <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.12); color: #0284c7;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                        </div>
                        <div class="stat-card-value" style="color: #0284c7;"><?= $totalEvents ?> <span style="font-size: 14px; font-weight: 500; color: var(--text-muted);">Events</span></div>
                        <div class="stat-card-footer">
                            <span class="badge badge-primary"><?= $totalRegistrations ?> Passes Issued</span>
                            <a href="admin_events.php" class="stat-card-link">Manage Events &rarr;</a>
                        </div>
                    </div>

                    <!-- Main Section: Database User Directory -->
                    <div class="portal-card bento-col-span-12" id="user-directory">
                        <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
                            <div>
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                                    <span>Database User Directory</span>
                                </h3>
                                <p style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">
                                    Real-time accounts registered in <code>student_hub_db</code>. Use Student Management for full CRUD operations.
                                </p>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <a href="admin_students.php" class="btn btn-sm btn-primary">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <span>Full Student Management (CRUD) &rarr;</span>
                                </a>
                                <a href="export_students.php" class="btn btn-sm btn-secondary">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <span>Download CSV</span>
                                </a>
                            </div>
                        </div>

                        <div class="card-body" style="padding: 0;">
                            <div class="table-responsive">
                                <table class="portal-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username / Student ID</th>
                                            <th>Full Name</th>
                                            <th>Email Address</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Registered On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentUsers)): ?>
                                            <tr>
                                                <td colspan="8" style="padding: 32px; text-align: center; color: var(--text-muted);">
                                                    No registered accounts found in the database.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentUsers as $usr): ?>
                                                <tr>
                                                    <td style="font-family: var(--font-mono); font-size: 12.5px;"><?= (int)$usr['id'] ?></td>
                                                    <td style="font-family: var(--font-mono); font-weight: 700; color: var(--text-main);">
                                                        <?= htmlspecialchars($usr['username'], ENT_QUOTES, 'UTF-8') ?>
                                                    </td>
                                                    <td style="font-weight: 600;">
                                                        <?= htmlspecialchars($usr['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </td>
                                                    <td style="font-family: var(--font-mono); font-size: 12.5px; color: var(--text-muted);">
                                                        <?= htmlspecialchars($usr['email'], ENT_QUOTES, 'UTF-8') ?>
                                                    </td>
                                                    <td>
                                                        <span class="role-badge <?= ($usr['role'] === 'admin') ? 'admin' : 'student' ?>">
                                                            <?= htmlspecialchars(strtoupper($usr['role']), ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($usr['is_deleted'])): ?>
                                                            <span class="badge badge-danger">Deleted</span>
                                                        <?php elseif (($usr['status'] ?? 'Active') === 'Active'): ?>
                                                            <span class="badge badge-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning"><?= htmlspecialchars($usr['status'] ?? 'Pending', ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="font-size: 12px; color: var(--text-muted);">
                                                        <?= htmlspecialchars($usr['created_at'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($usr['role'] === 'student'): ?>
                                                            <a href="admin_students.php?search=<?= urlencode($usr['username']) ?>" class="btn btn-sm btn-secondary" style="padding: 4px 10px; font-size: 12px;">Manage</a>
                                                        <?php else: ?>
                                                            <span style="font-size: 11.5px; color: var(--text-muted); font-style: italic;">Admin Staff</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Security & System Governance Card -->
                    <div class="portal-card bento-col-span-12">
                        <div class="card-header">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <span>Administrative Security & Governance Enforcement</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                                <div style="padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-card-solid);">
                                    <div style="font-weight: 700; color: var(--success); font-size: 13.5px; margin-bottom: 4px;">✓ Inactivity Timeout Active</div>
                                    <div style="font-size: 12.5px; color: var(--text-muted);">Enforcing 15-minute idle expiration with automatic redirection to login.php.</div>
                                </div>
                                <div style="padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-card-solid);">
                                    <div style="font-weight: 700; color: var(--success); font-size: 13.5px; margin-bottom: 4px;">✓ Strict RBAC Boundary</div>
                                    <div style="font-size: 12.5px; color: var(--text-muted);">HTTP 403 Forbidden checks preventing unauthorized role elevation or boundary crossing.</div>
                                </div>
                                <div style="padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-card-solid);">
                                    <div style="font-weight: 700; color: var(--success); font-size: 13.5px; margin-bottom: 4px;">✓ Soft Delete Audit Trail</div>
                                    <div style="font-size: 12.5px; color: var(--text-muted);">Students are flagged with is_deleted=1 and timestamped without dropping rows.</div>
                                </div>
                                <div style="padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-card-solid);">
                                    <div style="font-weight: 700; color: var(--success); font-size: 13.5px; margin-bottom: 4px;">✓ CSV Formula Sanitization</div>
                                    <div style="font-size: 12.5px; color: var(--text-muted);">Neutralizes dangerous formula injection characters (=, +, -, @) upon export.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Alerts Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="js/portal.js"></script>
</body>
</html>
