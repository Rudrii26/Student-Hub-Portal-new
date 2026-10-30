<?php
/**
 * CHARUSAT Student Hub - Student Portal Dashboard (student_dashboard.php)
 * Protected portal page accessible exclusively to users with role = 'student'.
 * Restores the complete unified Student Hub Portal UI/UX design,
 * sidebar navigation, hero aura banner, bento grid telemetry, and dynamic profile data.
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
if (($_SESSION['role'] ?? '') !== 'student') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - Student Access Denied</title>
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
                You are currently signed in as an <strong>Administrator</strong> (<?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>). The requested Student Dashboard is designated exclusively for enrolled student accounts.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="admin_dashboard.php" class="btn btn-primary" style="text-decoration: none; padding: 10px 20px; font-size: 14px;">
                    <span>Return to Admin Dashboard</span>
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

// 4. Authorized Student Session Data Extraction
$studentName     = $_SESSION['full_name'] ?? 'Kakadiya Rudri Alpeshbhai';
$studentId       = $_SESSION['username'] ?? '25CS027';
$studentEmail    = $_SESSION['email'] ?? '25cs027@charusat.edu.in';
$course          = !empty($_SESSION['course']) ? $_SESSION['course'] : 'B.Tech Computer Engineering (CSPIT)';
$year            = !empty($_SESSION['academic_year']) ? $_SESSION['academic_year'] : '2nd Year (Semester 3 & 4)';
$registrationRef = !empty($_SESSION['registration_id']) ? $_SESSION['registration_id'] : 'REG-2026-A18B22';

// Dynamic initials
$parts = preg_split('/\s+/', trim($studentName));
$initials = '';
foreach ($parts as $p) {
    if ($p !== '') $initials .= strtoupper(mb_substr($p, 0, 1));
}
if (strlen($initials) > 2) {
    $initials = substr($initials, 0, 1) . substr($initials, -1);
}
if ($initials === '') $initials = 'ST';

// Short name
$shortName = $studentName;
if (count($parts) > 2) {
    $shortName = $parts[0] . ' ' . $parts[count($parts) - 1];
}

// Time-based greeting
$currentHour = (int)date('G');
$greeting = ($currentHour >= 5 && $currentHour < 12) ? 'Good Morning' : (($currentHour >= 12 && $currentHour < 17) ? 'Good Afternoon' : 'Good Evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Student Hub Portal</title>
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
                <div class="sidebar-logo-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <div class="sidebar-brand-text">
                    <span class="sidebar-brand-title">CHARUSAT</span>
                    <span class="sidebar-brand-sub">Student Hub Portal</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">Overview</div>
                <a href="student_dashboard.php" class="nav-link active">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg></span>
                    <span>Dashboard</span>
                </a>
                <a href="pages/profile.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <span>My Profile</span>
                </a>

                <div class="nav-section-title">Academics</div>
                <a href="pages/courses.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg></span>
                    <span>Courses & Subjects</span>
                </a>
                <a href="pages/timetable.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg></span>
                    <span>Timetable</span>
                </a>
                <a href="pages/attendance.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                    <span>Attendance</span>
                </a>
                <a href="pages/assignments.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg></span>
                    <span>Assignments</span>
                </a>
                <a href="pages/materials.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg></span>
                    <span>Study Material</span>
                </a>
                <a href="pages/exams-results.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg></span>
                    <span>Exams & Results</span>
                </a>

                <div class="nav-section-title">Finance & Residence</div>
                <a href="pages/college-fees.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg></span>
                    <span>College Fees</span>
                </a>
                <a href="pages/hostel.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                    <span>Hostel & Mess</span>
                </a>
                <a href="pages/payments.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M14 8H8"/><path d="M16 12H8"/></svg></span>
                    <span>Payments & Receipts</span>
                </a>

                <div class="nav-section-title">Services & Campus</div>
                <a href="pages/parents.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <span>Parents / Guardian</span>
                </a>
                <a href="pages/documents.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg></span>
                    <span>Documents & Certificates</span>
                </a>
                <a href="pages/applications.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
                    <span>Applications & Requests</span>
                </a>
                <a href="pages/verification.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                    <span>Academic Verification</span>
                </a>
                <a href="pages/campus.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg></span>
                    <span>Campus Life & SPOURAL</span>
                </a>
                <a href="pages/settings.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                    <span>Settings & Helpdesk</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="pages/profile.html" class="sidebar-user">
                    <div class="sidebar-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="sidebar-user-id"><?= htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') ?> &bull; CSPIT</span>
                    </div>
                </a>
                <button class="btn-sidebar-logout" onclick="logoutUser()" title="Logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
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
                        <h1>Student Dashboard</h1>
                        <div class="header-breadcrumb">
                            <a href="index.html" style="color: inherit; text-decoration: none;">CHARUSAT</a>
                            <span>/</span>
                            <span>CSPIT</span>
                            <span>/</span>
                            <span style="color: var(--primary-color); font-weight: 600;">Student Portal</span>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Role Badge Pill for Test & Access Identity -->
                    <span class="role-badge student">STUDENT</span>

                    <!-- Global Command Search Trigger -->
                    <button class="search-trigger-btn" onclick="openGlobalSearch()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <span>Search Student Hub...</span>
                        <span class="search-shortcut">⌘K</span>
                    </button>

                    <!-- Theme Switcher -->
                    <button class="header-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Theme">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div style="position: relative;">
                        <button class="header-btn" id="notifBtn" onclick="toggleNotifications()" title="Notifications">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            <span class="notification-badge-count">3</span>
                        </button>

                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h4>CHARUSAT Announcements</h4>
                                <button class="btn-clear-notif" onclick="markAllNotificationsRead()">Mark all read</button>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="notification-item unread" onclick="window.location.href='pages/exams-results.html'">
                                    <div class="notif-title">Mid-Semester Exam Schedule</div>
                                    <div class="notif-desc">CHARUSAT 3rd Sem CS Mid-Sem theory schedule released.</div>
                                    <div class="notif-time">1 hour ago</div>
                                </div>
                                <div class="notification-item unread" onclick="window.location.href='pages/college-fees.html'">
                                    <div class="notif-title">Semester 3 Tuition Fee Clearance</div>
                                    <div class="notif-desc">Pending fee for Sem 3 is due by 15th October 2026.</div>
                                    <div class="notif-time">3 hours ago</div>
                                </div>
                                <div class="notification-item unread" onclick="window.location.href='pages/assignments.html'">
                                    <div class="notif-title">CS201 Data Structures Assignment 2</div>
                                    <div class="notif-desc">BST & AVL Balancing deadline is tomorrow at 11:59 PM.</div>
                                    <div class="notif-time">5 hours ago</div>
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
                                <div style="font-weight: 700; font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="font-size: 11.5px; color: var(--text-muted); font-family: var(--font-mono);"><?= htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <a href="pages/profile.html" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span>Student Profile</span>
                            </a>
                            <a href="https://support.charusat.edu.in/UniExamResult/" target="_blank" rel="noopener noreferrer" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                                <span>Official Exam Result Portal</span>
                            </a>
                            <a href="https://support.charusat.edu.in/FeesPaymentApp/" target="_blank" rel="noopener noreferrer" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                <span>Pay Fees Gateway</span>
                            </a>
                            <a href="pages/settings.html" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                <span>Settings & Helpdesk</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item" onclick="logoutUser()" style="color: var(--danger); width: 100%; border: none; background: none; text-align: left; cursor: pointer;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                <span>Sign Out</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Bento Canvas -->
            <div class="portal-content">
                <!-- Aura Hero Banner -->
                <div class="hero-aura-banner">
                    <div class="hero-banner-flex">
                        <div>
                            <div class="hero-meta-pill">
                                <span class="live-pulse-dot"></span>
                                <span>CHARUSAT &bull; CSPIT &bull; <?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="hero-greeting">
                                <h2 id="welcomeGreeting"><?= $greeting ?>, <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?> 👋</h2>
                            </div>
                            <div class="hero-details-text">
                                Student ID: <strong><?= htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') ?></strong> &bull; Ref: <strong><?= htmlspecialchars($registrationRef, ENT_QUOTES, 'UTF-8') ?></strong> &bull; Mentor: <strong>Prof. Vaibhavi Patel</strong> &bull; Tej Smruti Girls Hostel
                            </div>
                        </div>
                        <div class="hero-cta-group">
                            <a href="pages/timetable.html" class="btn btn-hero-white">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                <span>Weekly Timetable</span>
                            </a>
                            <a href="pages/college-fees.html" class="btn btn-hero-glass">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                <span>Pay Fees</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Floating Quick Actions Dock -->
                <div class="quick-actions-bar">
                    <a href="pages/college-fees.html" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </div>
                        <div>
                            <div class="quick-action-title">Pay Tuition Fees</div>
                            <div class="quick-action-sub">₹35,000 pending</div>
                        </div>
                    </a>
                    <a href="pages/exams-results.html" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                        </div>
                        <div>
                            <div class="quick-action-title">Exam Results</div>
                            <div class="quick-action-sub">SGPA: 8.56 &bull; CGPA: 8.53</div>
                        </div>
                    </a>
                    <a href="pages/payments.html" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M14 8H8"/><path d="M16 12H8"/></svg>
                        </div>
                        <div>
                            <div class="quick-action-title">Fee Receipts</div>
                            <div class="quick-action-sub">Download Vouchers</div>
                        </div>
                    </a>
                    <a href="pages/assignments.html" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div>
                            <div class="quick-action-title">Assignments</div>
                            <div class="quick-action-sub">3 submissions due</div>
                        </div>
                    </a>
                    <a href="pages/hostel.html" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <div>
                            <div class="quick-action-title">Tej Smruti Hostel</div>
                            <div class="quick-action-sub">Room 505-H5 & Mess</div>
                        </div>
                    </a>
                    <a href="pages/campus.html" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
                        </div>
                        <div>
                            <div class="quick-action-title">SPOURAL 2026</div>
                            <div class="quick-action-sub">Fest & CSI Events</div>
                        </div>
                    </a>
                </div>

                <!-- Bento Stat Grid (Original Four-Card Layout) -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-label">Overall Attendance</span>
                            <span class="stat-value" id="statAttendance">89.0%</span>
                            <span class="stat-sub" style="color: var(--success);">
                                <span class="status-dot active"></span> 187 / 210 Classes (Safe Margin: +28)
                            </span>
                        </div>
                        <div class="stat-icon-box green">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-label">Cumulative CGPA</span>
                            <span class="stat-value" id="statCGPA">8.53</span>
                            <span class="stat-sub" style="color: var(--accent-cyan);">
                                <span class="status-dot active"></span> Sem 2 SGPA: 8.56 (Rank #5 in CE)
                            </span>
                        </div>
                        <div class="stat-icon-box cyan">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-label">Pending Tuition Fee</span>
                            <span class="stat-value" id="statCollegeFee">₹ 35,000</span>
                            <span class="stat-sub" style="color: var(--warning);">
                                <span class="status-dot warning"></span> Due by: 15 October 2026
                            </span>
                        </div>
                        <div class="stat-icon-box amber">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-label">Active Assignments</span>
                            <span class="stat-value" id="statAssignments">3 Tasks</span>
                            <span class="stat-sub" style="color: var(--danger);">
                                <span class="status-dot danger"></span> DSA Assignment 2 due tomorrow
                            </span>
                        </div>
                        <div class="stat-icon-box rose">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Two-Column Bento Layout -->
                <div class="dashboard-grid">
                    <!-- Left Bento Column -->
                    <div style="display: flex; flex-direction: column; gap: 26px;">
                        <!-- Today's Schedule Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                    <span>Today's CSPIT Lecture Schedule (Monday)</span>
                                </h3>
                                <a href="pages/timetable.html" class="btn btn-sm btn-outline">Full Week &rarr;</a>
                            </div>
                            <div class="table-responsive">
                                <table class="portal-table">
                                    <thead>
                                        <tr>
                                            <th>Time Slot</th>
                                            <th>Subject & Course Code</th>
                                            <th>Faculty</th>
                                            <th>Venue</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="todayTimetableBody">
                                        <tr>
                                            <td><strong>09:15 - 10:15 AM</strong></td>
                                            <td>Data Structures & Algorithms <span class="badge badge-primary">CS201</span></td>
                                            <td>Prof. Vaibhavi Patel</td>
                                            <td>CSPIT LT-104</td>
                                            <td><span class="badge badge-success">Completed</span></td>
                                        </tr>
                                        <tr style="background: var(--primary-light);">
                                            <td><strong>10:15 - 11:15 AM</strong></td>
                                            <td><strong>Database Management Systems</strong> <span class="badge badge-primary">CS202</span></td>
                                            <td>Prof. Hiren Mewada</td>
                                            <td>CSPIT LT-104</td>
                                            <td><span class="badge badge-info"><span class="live-pulse-dot" style="display:inline-block; margin-right:4px;"></span> Ongoing</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>11:30 AM - 01:30 PM</strong></td>
                                            <td>Data Structures Lab (CS-1) <span class="badge badge-primary">CS201P</span></td>
                                            <td>Prof. Vaibhavi Patel</td>
                                            <td>CSPIT Lab-04</td>
                                            <td><span class="badge badge-warning">Next Up</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>02:15 - 03:15 PM</strong></td>
                                            <td>Object-Oriented Programming (Java) <span class="badge badge-primary">CS203</span></td>
                                            <td>Prof. Parth Goel</td>
                                            <td>CSPIT LT-104</td>
                                            <td><span class="badge badge-primary">Upcoming</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>03:15 - 04:15 PM</strong></td>
                                            <td>Discrete Mathematics <span class="badge badge-primary">MA201</span></td>
                                            <td>Dr. Bharat Patel</td>
                                            <td>CSPIT LT-104</td>
                                            <td><span class="badge badge-primary">Upcoming</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Subject Attendance Telemetry -->
                        <div class="card">
                            <div class="card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    <span>Subject Attendance Telemetry (CSPIT Computer Engineering)</span>
                                </h3>
                                <a href="pages/attendance.html" class="btn btn-sm btn-outline">Full Analytics &rarr;</a>
                            </div>
                            <div class="card-body">
                                <div style="display: flex; flex-direction: column; gap: 18px;" id="dashboardAttendanceList"></div>
                            </div>
                        </div>

                        <!-- Priority Tasks -->
                        <div class="card">
                            <div class="card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span>Pending Lab & Theory Submissions</span>
                                </h3>
                                <a href="pages/assignments.html" class="btn btn-sm btn-outline">All Tasks &rarr;</a>
                            </div>
                            <div class="table-responsive">
                                <table class="portal-table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Task Name</th>
                                            <th>Deadline</th>
                                            <th>Max Marks</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dashboardAssignmentsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Bento Column -->
                    <div style="display: flex; flex-direction: column; gap: 26px;">
                        <!-- Official Announcements -->
                        <div class="card">
                            <div class="card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                                    <span>CHARUSAT Circulars</span>
                                </h3>
                                <a href="pages/campus.html" class="btn btn-sm btn-outline">All Notices</a>
                            </div>
                            <div class="card-body" style="padding: 0;">
                                <div style="display: flex; flex-direction: column;">
                                    <div style="padding: 18px 22px; border-bottom: 1px solid var(--border-color);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <span class="badge badge-danger">Exam Cell</span>
                                            <span style="font-size: 11px; color: var(--text-light); font-family: var(--font-mono);">01 Oct 2026</span>
                                        </div>
                                        <div style="font-weight: 700; font-size: 13.5px; color: var(--text-main);">Mid-Semester Examination Schedule - October 2026</div>
                                        <p style="font-size: 12.5px; color: var(--text-muted); margin-top: 4px; line-height: 1.45;">
                                            Theory exams commence October 20th for 3rd Sem CS. Digital hall tickets available on <a href="https://support.charusat.edu.in/UniExamResult/" target="_blank" style="color: var(--primary-color); text-decoration: underline;">UniExamResult</a>.
                                        </p>
                                    </div>
                                    <div style="padding: 18px 22px; border-bottom: 1px solid var(--border-color);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <span class="badge badge-primary">SPOURAL 2026</span>
                                            <span style="font-size: 11px; color: var(--text-light); font-family: var(--font-mono);">28 Sep 2026</span>
                                        </div>
                                        <div style="font-weight: 700; font-size: 13.5px; color: var(--text-main);">CHARUSAT Annual Cultural & Tech Fest Registration</div>
                                        <p style="font-size: 12.5px; color: var(--text-muted); margin-top: 4px; line-height: 1.45;">
                                            Register for Code-A-Thon, Web Wizards, Robotics, and Stage Events at CSPIT Auditorium.
                                        </p>
                                    </div>
                                    <div style="padding: 18px 22px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <span class="badge badge-info">CSI Chapter</span>
                                            <span style="font-size: 11px; color: var(--text-light); font-family: var(--font-mono);">25 Sep 2026</span>
                                        </div>
                                        <div style="font-weight: 700; font-size: 13.5px; color: var(--text-main);">Hands-On Competitive Coding & DSA Bootcamp</div>
                                        <p style="font-size: 12.5px; color: var(--text-muted); margin-top: 4px; line-height: 1.45;">
                                            Interactive masterclass on LeetCode & Codeforces problem solving for Computer Engineering students.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hostel & Mess Widget -->
                        <div class="card">
                            <div class="card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                    <span>Tej Smruti Girls Hostel & Mess (H5)</span>
                                </h3>
                                <a href="pages/hostel.html" class="btn btn-sm btn-outline">Details</a>
                            </div>
                            <div class="card-body">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13.5px;">
                                    <span style="color: var(--text-muted);">Residence:</span>
                                    <span style="font-weight: 700;">505-H5 (Fifth Floor)</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13.5px;">
                                    <span style="color: var(--text-muted);">Hostel Warden:</span>
                                    <span>Dr. Gitaben Patel</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 13.5px;">
                                    <span style="color: var(--text-muted);">Warden Contact:</span>
                                    <span>+91 2697 265140</span>
                                </div>
                                <div style="background: var(--bg-alt); padding: 14px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-size: 13px;">
                                    <strong style="color: var(--primary-color);">🍽️ Today's Special Dinner:</strong><br>
                                    <span style="color: var(--text-muted);">Paneer Butter Masala, Dal Makhani, Tandoori Roti & Gulab Jamun</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity Log -->
                        <div class="card">
                            <div class="card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <span>Recent Activity</span>
                                </h3>
                            </div>
                            <div class="card-body" style="padding: 16px 22px;">
                                <div style="display: flex; flex-direction: column; gap: 16px;" id="recentActivityList"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Alerts Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="js/portal-data.js"></script>
    <script src="js/portal.js"></script>
    <script>
        // Synchronize authenticated student session into client state
        try {
            localStorage.setItem("studentHubLoggedIn", "true");
            localStorage.setItem("studentHubUser", JSON.stringify({
                id: <?= json_encode($studentId) ?>,
                name: <?= json_encode($studentName) ?>,
                email: <?= json_encode($studentEmail) ?>,
                course: <?= json_encode($course) ?>,
                year: <?= json_encode($year) ?>,
                role: "student",
                registrationId: <?= json_encode($registrationRef) ?>
            }));
        } catch (e) {
            console.error("Session sync error", e);
        }

        document.addEventListener("DOMContentLoaded", function() {
            const data = getPortalData();

            // Dynamic Stats from CHARUSAT data
            const totalPendFee = data.collegeFees?.pendingAmount || 35000;
            const pendingAsns = (data.assignments || []).filter(a => a.status === "pending").length;

            const feeEl = document.getElementById("statCollegeFee");
            if (feeEl) feeEl.textContent = `₹ ${totalPendFee.toLocaleString('en-IN')}`;

            const asnEl = document.getElementById("statAssignments");
            if (asnEl) asnEl.textContent = `${pendingAsns} Tasks`;

            const attEl = document.getElementById("statAttendance");
            if (attEl) attEl.textContent = `${data.attendanceData?.overall || 91.4}%`;

            const cgpaEl = document.getElementById("statCGPA");
            if (cgpaEl) cgpaEl.textContent = `${data.student?.cgpa || 9.42}`;

            // Render Attendance Telemetry
            const attContainer = document.getElementById("dashboardAttendanceList");
            if (attContainer && data.attendanceData?.subjects) {
                attContainer.innerHTML = data.attendanceData.subjects.slice(0, 4).map(sub => `
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 13.5px; margin-bottom: 6px;">
                            <span><strong>${sub.name}</strong> <span style="color: var(--text-muted); font-size: 12px; font-family: var(--font-mono);">(${sub.code})</span></span>
                            <span style="font-weight: 700; font-family: var(--font-mono); color: ${sub.percentage >= 85 ? 'var(--success)' : (sub.percentage >= 75 ? 'var(--primary-color)' : 'var(--danger)')};">
                                ${sub.percentage}% (${sub.present}/${sub.total})
                            </span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill ${sub.percentage >= 85 ? 'success' : (sub.percentage >= 75 ? '' : 'danger')}" style="width: ${sub.percentage}%;"></div>
                        </div>
                    </div>
                `).join("");
            }

            // Render Priority Assignments
            const asnContainer = document.getElementById("dashboardAssignmentsBody");
            if (asnContainer && data.assignments) {
                asnContainer.innerHTML = data.assignments.filter(a => a.status === "pending").slice(0, 3).map(a => `
                    <tr>
                        <td><strong>${a.code}</strong><br><span style="font-size: 11.5px; color: var(--text-muted);">${a.subject}</span></td>
                        <td style="font-weight: 600;">${a.title}</td>
                        <td><span class="badge badge-warning">${a.dueDate}</span></td>
                        <td><strong style="font-family: var(--font-mono);">${a.maxMarks}</strong></td>
                        <td>
                            <a href="pages/assignments.html" class="btn btn-sm btn-primary">Submit</a>
                        </td>
                    </tr>
                `).join("");
            }

            // Render Recent Activity
            const actContainer = document.getElementById("recentActivityList");
            if (actContainer && data.recentActivity) {
                actContainer.innerHTML = data.recentActivity.map(act => `
                    <div style="display: flex; align-items: center; gap: 14px; font-size: 13.5px;">
                        <div style="width: 34px; height: 34px; border-radius: var(--radius-xs); background: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--text-main);">${act.title}</div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">${act.time}</div>
                        </div>
                    </div>
                `).join("");
            }
        });
    </script>
</body>
</html>
