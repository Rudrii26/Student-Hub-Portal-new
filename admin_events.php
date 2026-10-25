<?php
/**
 * CHARUSAT Student Hub - Practical 3: Campus Event Management (admin_events.php)
 * Administrative CRUD, secure poster uploads (JPEG/PNG/WebP <= 2MB, MIME verification),
 * search, category & status filtering, soft delete & restore workflows,
 * and student registration tracking.
 * Strictly enforces Admin RBAC, prepared statements, CSRF protection, and 15m session timeout.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Enforce 15-minute inactivity session timeout
require_once __DIR__ . '/timeout.php';

// 2. Enforce Authentication & Admin Role Boundary Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

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
                You are currently signed in as a <strong>Student</strong> (<?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>). The requested Event Management console requires elevated staff administrator privileges.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="student_dashboard.php" class="btn btn-primary" style="text-decoration: none; padding: 10px 20px; font-size: 14px;">
                    <span>Return to Student Dashboard</span>
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

// 3. Generate CSRF Token for state-changing operations
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

require_once __DIR__ . '/db.php';
$conn = getMysqliConnection();

// Flash messages
$errorMessage   = '';
$successMessage = '';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $successMessage = "Event successfully scheduled and published.";
            break;
        case 'updated':
            $successMessage = "Event details and assets updated successfully.";
            break;
        case 'deleted':
            $successMessage = "Event has been soft-deleted. It is hidden from the public campus page, but its registration history is preserved.";
            break;
        case 'restored':
            $successMessage = "Event has been restored and is once again visible on the public campus page.";
            break;
        case 'poster_deleted':
            $successMessage = "Event poster was safely removed.";
            break;
    }
}

if (isset($_GET['err'])) {
    $errorMessage = htmlspecialchars($_GET['err'], ENT_QUOTES, 'UTF-8');
}

/**
 * Safely sync active events from MySQL back to data/events.json
 * Preserves backward-compatibility with static test suites.
 */
function syncEventsJsonFromDb(mysqli $conn): void {
    try {
        $stmt = $conn->query("
            SELECT 
                event_code AS id,
                title,
                category,
                event_date AS date,
                event_time AS time,
                location,
                organizer,
                status,
                description,
                poster_path
            FROM events
            WHERE is_deleted = 0
            ORDER BY event_date ASC, id ASC
        ");
        if ($stmt) {
            $data = [];
            while ($row = $stmt->fetch_assoc()) {
                $data[] = [
                    'id'          => $row['id'],
                    'title'       => $row['title'],
                    'category'    => $row['category'],
                    'date'        => $row['date'],
                    'time'        => $row['time'] ?? '',
                    'location'    => $row['location'],
                    'organizer'   => $row['organizer'] ?? '',
                    'status'      => $row['status'] ?? 'Upcoming',
                    'description' => $row['description'] ?? '',
                    'poster_path' => $row['poster_path'] ?? null
                ];
            }
            // Only write if count is reasonable so we never overwrite with an empty file on transient DB failure
            if (count($data) >= 1) {
                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $localPath = __DIR__ . '/data/events.json';
                @file_put_contents($localPath, $json);

                $xamppPath = 'C:/xampp/htdocs/Student Hub Portal/data/events.json';
                if (file_exists(dirname($xamppPath))) {
                    @file_put_contents($xamppPath, $json);
                }
            }
        }
    } catch (Throwable $e) {
        error_log("Failed to sync events.json: " . $e->getMessage());
    }
}

/**
 * Securely processes uploaded event poster image.
 * Validates extension, file size (<= 2MB), MIME type via finfo, and generates unique safe filename.
 */
function handlePosterUpload(?array $file, ?string &$errorMsg, ?string $oldPoster = null): ?string {
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errorMsg = "Poster image exceeds the 2 MB maximum upload limit.";
        } else {
            $errorMsg = "Poster upload failed (Error code: {$file['error']}).";
        }
        return null;
    }

    // 1. Max size: 2 MB (2,097,152 bytes)
    $maxBytes = 2 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        $errorMsg = "Event poster size must not exceed 2 MB. Uploaded size was " . round($file['size'] / (1024 * 1024), 2) . " MB.";
        return null;
    }

    // 2. Extension check
    $origName = $file['name'] ?? '';
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        $errorMsg = "Invalid file format. Only JPEG, PNG, and WebP poster images are allowed.";
        return null;
    }

    // 3. Server-side MIME verification using Fileinfo
    if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
        $errorMsg = "Uploaded temporary file could not be read.";
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/webp'
    ];
    if (!in_array($detectedMime, $allowedMimes, true)) {
        $errorMsg = "Security error: File content does not match an allowed image type (Detected MIME: {$detectedMime}). Only genuine JPEG, PNG, and WebP images are permitted.";
        return null;
    }

    // Cross-verify extension with MIME
    if (($ext === 'png' && $detectedMime !== 'image/png') ||
        (in_array($ext, ['jpg', 'jpeg'], true) && !in_array($detectedMime, ['image/jpeg', 'image/pjpeg'], true)) ||
        ($ext === 'webp' && $detectedMime !== 'image/webp')) {
        $errorMsg = "File extension does not correspond to detected image content.";
        return null;
    }

    // 4. Ensure upload directory exists and is secured
    $uploadDir = __DIR__ . '/uploads/events';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Ensure .htaccess protection is present
    $htaccessPath = $uploadDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        file_put_contents($htaccessPath, "<FilesMatch \"(?i)\\.(php|phtml|php3|php4|php5|php7|phps|pht|phar|inc|pl|py|cgi|sh|bash|exe)$\">\nOrder Deny,Allow\nDeny from all\n</FilesMatch>\nOptions -Indexes -ExecCGI\n");
    }

    // 5. Generate cryptographically safe unique filename
    $canonicalExt = ($ext === 'jpeg') ? 'jpg' : $ext;
    $uniqueFilename = 'evt_poster_' . date('Ymd_His') . '_' . bin2hex(random_bytes(10)) . '.' . $canonicalExt;
    $targetPath = $uploadDir . '/' . $uniqueFilename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $errorMsg = "Failed to store uploaded poster in server filesystem.";
        return null;
    }

    // Mirror between workspace and live XAMPP htdocs if running in dual environment
    $workspaceUploadDir = 'C:/Users/Rudri/OneDrive/Desktop/Project/Student Hub Portal/uploads/events';
    if (is_dir($workspaceUploadDir) && realpath($uploadDir) !== realpath($workspaceUploadDir)) {
        @copy($targetPath, $workspaceUploadDir . '/' . $uniqueFilename);
    }
    $xamppUploadDir = 'C:/xampp/htdocs/Student Hub Portal/uploads/events';
    if (is_dir($xamppUploadDir) && realpath($uploadDir) !== realpath($xamppUploadDir)) {
        @copy($targetPath, $xamppUploadDir . '/' . $uniqueFilename);
    }

    // 6. Safe poster replacement: remove old poster if present and stored in uploads/events/
    if ($oldPoster) {
        deletePosterFile($oldPoster);
    }

    return 'uploads/events/' . $uniqueFilename;
}

function deletePosterFile(?string $posterPath): void {
    if (!$posterPath) return;
    $normalized = str_replace('\\', '/', $posterPath);
    if (strpos($normalized, 'uploads/events/') !== 0) {
        return;
    }
    $filename = basename($normalized);
    $fullPath = __DIR__ . '/uploads/events/' . $filename;
    if (file_exists($fullPath) && is_file($fullPath)) {
        @unlink($fullPath);
    }
    $workspacePath = 'C:/Users/Rudri/OneDrive/Desktop/Project/Student Hub Portal/uploads/events/' . $filename;
    if (file_exists($workspacePath) && is_file($workspacePath)) {
        @unlink($workspacePath);
    }
    $xamppPath = 'C:/xampp/htdocs/Student Hub Portal/uploads/events/' . $filename;
    if (file_exists($xamppPath) && is_file($xamppPath)) {
        @unlink($xamppPath);
    }
}

// ------------------------------------------------------------------------------
// 4. Handle State-Changing POST Actions
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        header("Location: admin_events.php?err=" . urlencode("Security verification failed (Invalid CSRF token). Please try again."));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // --------------------------------------------------------------------------
    // ACTION A: Add New Event
    // --------------------------------------------------------------------------
    if ($action === 'add_event') {
        $eventCode   = strtoupper(trim((string)($_POST['event_code'] ?? '')));
        $title       = trim((string)($_POST['title'] ?? ''));
        $category    = trim((string)($_POST['category'] ?? 'Campus'));
        $eventDate   = trim((string)($_POST['event_date'] ?? ''));
        $eventTime   = trim((string)($_POST['event_time'] ?? ''));
        $location    = trim((string)($_POST['location'] ?? ''));
        $organizer   = trim((string)($_POST['organizer'] ?? ''));
        $maxCapacity = max(1, (int)($_POST['max_capacity'] ?? 100));
        $status      = trim((string)($_POST['status'] ?? 'Upcoming'));
        $description = trim((string)($_POST['description'] ?? ''));

        // Basic validations
        if ($eventCode === '' || $title === '' || $category === '' || $eventDate === '' || $location === '') {
            header("Location: admin_events.php?err=" . urlencode("Event Code, Title, Category, Date, and Location are mandatory fields."));
            exit;
        }

        // Validate date format YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            header("Location: admin_events.php?err=" . urlencode("Event date must follow YYYY-MM-DD format."));
            exit;
        }

        // Check uniqueness of event_code
        $checkStmt = $conn->prepare("SELECT id FROM events WHERE event_code = ? LIMIT 1");
        $checkStmt->bind_param('s', $eventCode);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        if ($checkRes && $checkRes->num_rows > 0) {
            $checkStmt->close();
            header("Location: admin_events.php?err=" . urlencode("Event Code '{$eventCode}' already exists. Please specify a unique code."));
            exit;
        }
        $checkStmt->close();

        // Handle secure poster upload
        $posterPath = null;
        $uploadError = null;
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $posterPath = handlePosterUpload($_FILES['poster'], $uploadError);
            if ($uploadError) {
                header("Location: admin_events.php?err=" . urlencode($uploadError));
                exit;
            }
        }

        $insertStmt = $conn->prepare("
            INSERT INTO events (
                event_code, title, category, event_date, event_time,
                location, organizer, max_capacity, status, description,
                poster_path, is_deleted
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $insertStmt->bind_param(
            'sssssssisss',
            $eventCode, $title, $category, $eventDate, $eventTime,
            $location, $organizer, $maxCapacity, $status, $description,
            $posterPath
        );

        if ($insertStmt->execute()) {
            $insertStmt->close();
            syncEventsJsonFromDb($conn);
            header("Location: admin_events.php?msg=added");
            exit;
        } else {
            $err = $insertStmt->error;
            $insertStmt->close();
            if ($posterPath) deletePosterFile($posterPath);
            header("Location: admin_events.php?err=" . urlencode("Database insert failed: " . $err));
            exit;
        }
    }

    // --------------------------------------------------------------------------
    // ACTION B: Edit Existing Event
    // --------------------------------------------------------------------------
    if ($action === 'edit_event') {
        $id          = (int)($_POST['id'] ?? 0);
        $eventCode   = strtoupper(trim((string)($_POST['event_code'] ?? '')));
        $title       = trim((string)($_POST['title'] ?? ''));
        $category    = trim((string)($_POST['category'] ?? 'Campus'));
        $eventDate   = trim((string)($_POST['event_date'] ?? ''));
        $eventTime   = trim((string)($_POST['event_time'] ?? ''));
        $location    = trim((string)($_POST['location'] ?? ''));
        $organizer   = trim((string)($_POST['organizer'] ?? ''));
        $maxCapacity = max(1, (int)($_POST['max_capacity'] ?? 100));
        $status      = trim((string)($_POST['status'] ?? 'Upcoming'));
        $description = trim((string)($_POST['description'] ?? ''));
        $removePoster = isset($_POST['remove_existing_poster']) && $_POST['remove_existing_poster'] === '1';

        if ($id <= 0 || $eventCode === '' || $title === '' || $category === '' || $eventDate === '' || $location === '') {
            header("Location: admin_events.php?err=" . urlencode("All required fields must be supplied."));
            exit;
        }

        // Validate existing event
        $curStmt = $conn->prepare("SELECT id, poster_path FROM events WHERE id = ? LIMIT 1");
        $curStmt->bind_param('i', $id);
        $curStmt->execute();
        $curRes = $curStmt->get_result();
        $curRow = $curRes ? $curRes->fetch_assoc() : null;
        $curStmt->close();

        if (!$curRow) {
            header("Location: admin_events.php?err=" . urlencode("Event record not found."));
            exit;
        }

        $currentPoster = $curRow['poster_path'];

        // Check uniqueness of event_code excluding this id
        $checkStmt = $conn->prepare("SELECT id FROM events WHERE event_code = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param('si', $eventCode, $id);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        if ($checkRes && $checkRes->num_rows > 0) {
            $checkStmt->close();
            header("Location: admin_events.php?err=" . urlencode("Event Code '{$eventCode}' is already in use by another event."));
            exit;
        }
        $checkStmt->close();

        // Handle poster upload or deletion
        $finalPosterPath = $currentPoster;
        if ($removePoster) {
            deletePosterFile($currentPoster);
            $finalPosterPath = null;
        }

        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadError = null;
            $newPoster = handlePosterUpload($_FILES['poster'], $uploadError, $finalPosterPath);
            if ($uploadError) {
                header("Location: admin_events.php?err=" . urlencode($uploadError));
                exit;
            }
            if ($newPoster) {
                $finalPosterPath = $newPoster;
            }
        }

        $updateStmt = $conn->prepare("
            UPDATE events SET
                event_code = ?,
                title = ?,
                category = ?,
                event_date = ?,
                event_time = ?,
                location = ?,
                organizer = ?,
                max_capacity = ?,
                status = ?,
                description = ?,
                poster_path = ?
            WHERE id = ?
        ");
        $updateStmt->bind_param(
            'sssssssisssi',
            $eventCode, $title, $category, $eventDate, $eventTime,
            $location, $organizer, $maxCapacity, $status, $description,
            $finalPosterPath, $id
        );

        if ($updateStmt->execute()) {
            $updateStmt->close();
            syncEventsJsonFromDb($conn);
            header("Location: admin_events.php?msg=updated");
            exit;
        } else {
            $err = $updateStmt->error;
            $updateStmt->close();
            header("Location: admin_events.php?err=" . urlencode("Failed to update event: " . $err));
            exit;
        }
    }

    // --------------------------------------------------------------------------
    // ACTION C: Soft Delete Event
    // --------------------------------------------------------------------------
    if ($action === 'soft_delete_event') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: admin_events.php?err=" . urlencode("Invalid event ID specified."));
            exit;
        }

        $delStmt = $conn->prepare("UPDATE events SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
        $delStmt->bind_param('i', $id);
        if ($delStmt->execute()) {
            $delStmt->close();
            syncEventsJsonFromDb($conn);
            header("Location: admin_events.php?msg=deleted");
            exit;
        } else {
            $err = $delStmt->error;
            $delStmt->close();
            header("Location: admin_events.php?err=" . urlencode("Failed to soft-delete event: " . $err));
            exit;
        }
    }

    // --------------------------------------------------------------------------
    // ACTION D: Restore Soft-Deleted Event
    // --------------------------------------------------------------------------
    if ($action === 'restore_event') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: admin_events.php?err=" . urlencode("Invalid event ID specified."));
            exit;
        }

        $restoreStmt = $conn->prepare("UPDATE events SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
        $restoreStmt->bind_param('i', $id);
        if ($restoreStmt->execute()) {
            $restoreStmt->close();
            syncEventsJsonFromDb($conn);
            header("Location: admin_events.php?msg=restored");
            exit;
        } else {
            $err = $restoreStmt->error;
            $restoreStmt->close();
            header("Location: admin_events.php?err=" . urlencode("Failed to restore event: " . $err));
            exit;
        }
    }

    // --------------------------------------------------------------------------
    // ACTION E: Safe Poster Deletion
    // --------------------------------------------------------------------------
    if ($action === 'delete_poster') {
        $id = (int)($_POST['id'] ?? 0);
        $curStmt = $conn->prepare("SELECT poster_path FROM events WHERE id = ? LIMIT 1");
        $curStmt->bind_param('i', $id);
        $curStmt->execute();
        $row = $curStmt->get_result()->fetch_assoc();
        $curStmt->close();

        if ($row && !empty($row['poster_path'])) {
            deletePosterFile($row['poster_path']);
            $upd = $conn->prepare("UPDATE events SET poster_path = NULL WHERE id = ?");
            $upd->bind_param('i', $id);
            $upd->execute();
            $upd->close();
            syncEventsJsonFromDb($conn);
            header("Location: admin_events.php?msg=poster_deleted");
            exit;
        }
        header("Location: admin_events.php");
        exit;
    }
}

// ------------------------------------------------------------------------------
// 5. Query Filters, Stats & Events Dataset
// ------------------------------------------------------------------------------
$activeTab   = ($_GET['tab'] ?? 'active') === 'deleted' ? 'deleted' : 'active';
$searchQuery = trim((string)($_GET['q'] ?? ''));
$filterCat   = trim((string)($_GET['category'] ?? ''));
$filterStat  = trim((string)($_GET['status'] ?? ''));

// Fetch Aggregate Metrics
$stats = [
    'active'    => 0,
    'open'      => 0,
    'deleted'   => 0,
    'total_reg' => 0
];

try {
    $sRes = $conn->query("
        SELECT 
            SUM(CASE WHEN is_deleted = 0 THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN is_deleted = 0 AND (status = 'Open for Registration' OR status = 'Upcoming') THEN 1 ELSE 0 END) AS open_count,
            SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) AS deleted_count
        FROM events
    ");
    if ($sRes && $row = $sRes->fetch_assoc()) {
        $stats['active']  = (int)($row['active_count'] ?? 0);
        $stats['open']    = (int)($row['open_count'] ?? 0);
        $stats['deleted'] = (int)($row['deleted_count'] ?? 0);
    }

    $rRes = $conn->query("SELECT COUNT(*) AS total_reg FROM event_registrations");
    if ($rRes && $row = $rRes->fetch_assoc()) {
        $stats['total_reg'] = (int)($row['total_reg'] ?? 0);
    }
} catch (Exception $e) {
    error_log("Event stats error: " . $e->getMessage());
}

// Build Filtered Events Query
$whereClauses = [];
$params       = [];
$types        = '';

if ($activeTab === 'deleted') {
    $whereClauses[] = "e.is_deleted = 1";
} else {
    $whereClauses[] = "e.is_deleted = 0";
}

if ($searchQuery !== '') {
    $whereClauses[] = "(e.event_code LIKE ? OR e.title LIKE ? OR e.location LIKE ? OR e.organizer LIKE ? OR e.description LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sssss';
}

if ($filterCat !== '' && strtolower($filterCat) !== 'all') {
    $whereClauses[] = "e.category = ?";
    $params[] = $filterCat;
    $types .= 's';
}

if ($filterStat !== '' && strtolower($filterStat) !== 'all') {
    $whereClauses[] = "e.status = ?";
    $params[] = $filterStat;
    $types .= 's';
}

$whereSql = implode(' AND ', $whereClauses);

$listQuery = "
    SELECT 
        e.*,
        COUNT(r.id) AS reg_count
    FROM events e
    LEFT JOIN event_registrations r ON e.id = r.event_id
    WHERE {$whereSql}
    GROUP BY e.id
    ORDER BY " . ($activeTab === 'deleted' ? "e.deleted_at DESC, e.id DESC" : "e.event_date ASC, e.id ASC") . "
";

$stmt = $conn->prepare($listQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$eventsResult = $stmt->get_result();

$eventsList = [];
while ($row = $eventsResult->fetch_assoc()) {
    $eventsList[] = $row;
}
$stmt->close();

// Distinct categories for filter
$catList = ['Cultural', 'Technical', 'Workshop', 'Placement', 'Sports', 'Seminar', 'Academic', 'Club', 'Campus'];

// Admin session info
$adminName     = $_SESSION['full_name'] ?? 'CHARUSAT Administrator';
$adminUsername = $_SESSION['username'] ?? 'ADMIN_CHARUSAT';
$parts = preg_split('/\s+/', trim($adminName));
$initials = '';
foreach ($parts as $p) {
    if ($p !== '') $initials .= strtoupper(mb_substr($p, 0, 1));
}
if (strlen($initials) > 2) $initials = substr($initials, 0, 1) . substr($initials, -1);
if ($initials === '') $initials = 'AD';
$shortName = count($parts) > 2 ? $parts[0] . ' ' . $parts[count($parts) - 1] : $adminName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management (CRUD) - CHARUSAT Student Hub Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/portal.css">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .event-poster-preview {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.05);
            display: block;
        }
        .event-poster-placeholder {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            background: rgba(245, 158, 11, 0.12);
            color: var(--accent-amber);
            border: 1px dashed rgba(245, 158, 11, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .badge-cat {
            background: rgba(14, 165, 233, 0.12);
            color: #0284c7;
            border: 1px solid rgba(14, 165, 233, 0.3);
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        [data-theme="dark"] .badge-cat {
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.14);
            border-color: rgba(56, 189, 248, 0.3);
        }
        .modal-body-scroll {
            max-height: 70vh;
            overflow-y: auto;
            padding-right: 6px;
        }
        .file-upload-zone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px;
            text-align: center;
            background: rgba(255, 255, 255, 0.02);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .file-upload-zone:hover {
            border-color: var(--primary);
            background: rgba(26, 86, 219, 0.04);
        }

        /* Modal Window System - Bulletproof Fixed Overlay & Center Alignment */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(9, 13, 22, 0.82);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1050;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }

        .modal-overlay.show,
        .modal-overlay.active {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .modal-card {
            background: var(--bg-card-solid, #1e293b);
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.15));
            border-radius: var(--radius-lg, 14px);
            width: 100%;
            max-width: 640px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.5);
            animation: modalPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            z-index: 1051;
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            color: var(--text-muted, #94a3b8);
            font-size: 24px;
            cursor: pointer;
            padding: 4px 8px;
            line-height: 1;
            border-radius: var(--radius-sm, 6px);
            transition: all 0.2s;
        }
        .modal-close-btn:hover {
            color: var(--text-color, #ffffff);
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
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
                <a href="admin_dashboard.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg></span>
                    <span>Admin Dashboard</span>
                </a>
                <a href="index.html" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                    <span>Public Homepage</span>
                </a>

                <div class="nav-section-title">Practical 3: Event Management</div>
                <a href="admin_events.php" class="nav-link active">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <span>Manage Events (CRUD)</span>
                </a>
                <a href="javascript:void(0)" onclick="openAddEventModal()" class="nav-link" id="sidebarAddEventBtn">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></span>
                    <span>+ Schedule New Event</span>
                </a>
                <a href="pages/campus.html" target="_blank" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span>
                    <span>Public Campus Feed</span>
                </a>

                <div class="nav-section-title">Practical 2: Student Management</div>
                <a href="admin_students.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <span>Manage Students (CRUD)</span>
                </a>
                <a href="export_students.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
                    <span>Export Students (CSV)</span>
                </a>

                <div class="nav-section-title">Security & System</div>
                <a href="timeout.php" class="nav-link">
                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg></span>
                    <span>Session Security (15m)</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-avatar" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: white;"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="sidebar-user-id" style="color: var(--accent-amber);"><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?> &bull; Admin</span>
                    </div>
                </div>
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
                        <h1>Practical 3: Campus Event Management Console</h1>
                        <div class="header-breadcrumb">
                            <a href="admin_dashboard.php" style="color: inherit; text-decoration: none;">Admin Console</a>
                            <span>/</span>
                            <span>Practical 3</span>
                            <span>/</span>
                            <span style="color: var(--accent-amber); font-weight: 600;">Events & Registrations</span>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <span class="role-badge admin">ADMIN</span>
                    <button type="button" class="btn btn-sm btn-primary" id="openAddEventModalBtn" onclick="openAddEventModal()" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <span>+ Schedule New Event</span>
                    </button>
                    <button class="header-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Theme">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    </button>
                </div>
            </header>

            <div class="portal-content">
                <!-- Flash Alerts -->
                <?php if ($successMessage): ?>
                    <div class="alert alert-success" role="alert" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error" role="alert" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Metrics Bento Grid -->
                <div class="stats-grid" style="margin-bottom: 24px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(14, 165, 233, 0.12); color: #0284c7;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Active Events</span>
                            <span class="stat-value"><?= number_format($stats['active']) ?></span>
                            <span class="stat-sub">Live on campus portal</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Open for Registration</span>
                            <span class="stat-value"><?= number_format($stats['open']) ?></span>
                            <span class="stat-sub">Accepting student entries</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Student Passes</span>
                            <span class="stat-value"><?= number_format($stats['total_reg']) ?></span>
                            <span class="stat-sub">Confirmed registrations</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #dc2626;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Archived / Soft-Deleted</span>
                            <span class="stat-value"><?= number_format($stats['deleted']) ?></span>
                            <span class="stat-sub">Recoverable anytime</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs & Filter Bar -->
                <div class="card" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 16px;">
                        <div style="display: flex; gap: 8px;">
                            <a href="admin_events.php?tab=active" class="btn <?= $activeTab === 'active' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 8px 16px; font-size: 13px;">
                                Active Events (<?= $stats['active'] ?>)
                            </a>
                            <a href="admin_events.php?tab=deleted" class="btn <?= $activeTab === 'deleted' ? 'btn-danger' : 'btn-secondary' ?>" style="padding: 8px 16px; font-size: 13px;">
                                Soft-Deleted Archive (<?= $stats['deleted'] ?>)
                            </a>
                        </div>
                        <div>
                            <a href="pages/campus.html" target="_blank" class="btn btn-secondary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                <span>Preview Public Campus Page</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    <form method="GET" action="admin_events.php" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div style="flex: 1 1 240px; position: relative;">
                            <input type="text" name="q" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by event title, code, venue, organizer..." class="form-input" style="padding-left: 36px; width: 100%;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>

                        <div style="flex: 0 1 180px;">
                            <select name="category" class="form-input" onchange="this.form.submit()">
                                <option value="all">All Categories</option>
                                <?php foreach ($catList as $cat): ?>
                                    <option value="<?= $cat ?>" <?= strtolower($filterCat) === strtolower($cat) ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="flex: 0 1 180px;">
                            <select name="status" class="form-input" onchange="this.form.submit()">
                                <option value="all">All Statuses</option>
                                <option value="Upcoming" <?= strtolower($filterStat) === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                <option value="Open for Registration" <?= strtolower($filterStat) === 'open for registration' ? 'selected' : '' ?>>Open for Registration</option>
                                <option value="Ongoing" <?= strtolower($filterStat) === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="Completed" <?= strtolower($filterStat) === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= strtolower($filterStat) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-secondary" style="padding: 10px 16px;">
                            <span>Filter</span>
                        </button>
                        
                        <?php if ($searchQuery !== '' || $filterCat !== '' || $filterStat !== ''): ?>
                            <a href="admin_events.php?tab=<?= urlencode($activeTab) ?>" class="btn btn-secondary" style="padding: 10px 14px;" title="Clear Filters">
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Events Table Card -->
                <div class="card" style="padding: 0; overflow: hidden;">
                    <div style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px;">
                        <div style="font-weight: 700; font-size: 15px; color: var(--text-color);">
                            <?= $activeTab === 'deleted' ? 'Soft-Deleted Event Archive' : 'Published Campus Events' ?>
                            <span style="font-weight: 500; font-size: 13px; color: var(--text-muted); margin-left: 6px;">(<?= count($eventsList) ?> records found)</span>
                        </div>
                        <?php if ($activeTab === 'active'): ?>
                            <button type="button" class="btn btn-sm btn-primary" id="tableAddEventBtn" onclick="openAddEventModal()" style="display: inline-flex; align-items: center; gap: 6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                <span>+ Schedule New Event</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($eventsList)): ?>
                        <div style="padding: 48px 24px; text-align: center; color: var(--text-muted);">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; opacity: 0.6;"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <h3 style="font-size: 16px; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">No events match your criteria</h3>
                            <p style="font-size: 13.5px; margin-bottom: 16px;">Try adjusting your keyword search, category filter, or switch tabs.</p>
                            <?php if ($activeTab === 'active'): ?>
                                <button type="button" class="btn btn-primary" id="emptyStateAddEventBtn" onclick="openAddEventModal()" style="display: inline-flex; align-items: center; gap: 6px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                    <span>+ Schedule New Event</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="portal-table data-table" style="min-width: 1060px; width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">Poster</th>
                                        <th>Code</th>
                                        <th>Title & Details</th>
                                        <th>Category</th>
                                        <th>Date & Venue</th>
                                        <th>Registrations</th>
                                        <th>Status</th>
                                        <th style="text-align: right; width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eventsList as $evt): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($evt['poster_path']) && file_exists(__DIR__ . '/' . $evt['poster_path'])): ?>
                                                    <img src="<?= htmlspecialchars($evt['poster_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Poster" class="event-poster-preview" onclick="showPosterModal('<?= htmlspecialchars($evt['poster_path'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($evt['title']), ENT_QUOTES, 'UTF-8') ?>')" style="cursor: pointer;" title="Click to view poster">
                                                <?php else: ?>
                                                    <div class="event-poster-placeholder" title="No custom poster uploaded">
                                                        <span>📅</span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code style="font-family: 'JetBrains Mono', monospace; font-weight: 600; color: var(--accent-amber);"><?= htmlspecialchars($evt['event_code'], ENT_QUOTES, 'UTF-8') ?></code>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 2px;">
                                                    <?= htmlspecialchars($evt['title'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <?php if (!empty($evt['organizer'])): ?>
                                                    <div style="font-size: 12px; color: var(--text-muted);">
                                                        By: <?= htmlspecialchars($evt['organizer'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($evt['description'])): ?>
                                                    <div style="font-size: 11.5px; color: var(--text-muted); max-width: 340px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($evt['description'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($evt['description'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge-cat"><?= htmlspecialchars($evt['category'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; font-size: 13px; color: var(--text-color);">
                                                    <?= date('M d, Y', strtotime($evt['event_date'])) ?>
                                                </div>
                                                <div style="font-size: 12px; color: var(--text-muted);">
                                                    <?= htmlspecialchars($evt['event_time'] ?: 'Full Day', ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                                    📍 <?= htmlspecialchars($evt['location'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: var(--text-color);">
                                                    <?= (int)$evt['reg_count'] ?> / <?= (int)$evt['max_capacity'] ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-muted);">
                                                    <?php 
                                                        $cap = max(1, (int)$evt['max_capacity']);
                                                        $pct = round(((int)$evt['reg_count'] / $cap) * 100);
                                                    ?>
                                                    <?= $pct ?>% full
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                    $st = strtolower($evt['status']);
                                                    $pillClass = 'badge-primary';
                                                    if (strpos($st, 'open') !== false) $pillClass = 'badge-success';
                                                    elseif ($st === 'upcoming') $pillClass = 'badge-warning';
                                                    elseif ($st === 'ongoing') $pillClass = 'badge-info';
                                                    elseif ($st === 'completed') $pillClass = 'badge-secondary';
                                                    elseif ($st === 'cancelled') $pillClass = 'badge-danger';
                                                ?>
                                                <span class="badge <?= $pillClass ?>"><?= htmlspecialchars($evt['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                                    <?php if ($activeTab === 'active'): ?>
                                                        <!-- Edit Button -->
                                                        <button type="button" class="btn btn-sm btn-secondary" onclick='openEditEventModal(<?= json_encode($evt, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Event Details">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                        </button>

                                                        <!-- Soft Delete Form -->
                                                        <form method="POST" action="admin_events.php" style="display: inline;" onsubmit="return confirm('Archive this event? It will be hidden from the campus portal, but its registration history will remain intact.');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="action" value="soft_delete_event">
                                                            <input type="hidden" name="id" value="<?= (int)$evt['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Soft Delete / Archive Event">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <!-- Restore Form -->
                                                        <form method="POST" action="admin_events.php" style="display: inline;" onsubmit="return confirm('Restore this event to the active public catalog?');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="action" value="restore_event">
                                                            <input type="hidden" name="id" value="<?= (int)$evt['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary" title="Restore Event to Active">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                                                                <span>Restore</span>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL 1: ADD EVENT -->
    <div id="addEventModal" class="modal-overlay modal" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
        <div class="modal-card" style="max-width: 640px; width: 95%;">
            <div class="modal-header">
                <h2 id="addModalTitle" style="font-size: 18px; font-weight: 700; margin: 0; color: var(--text-color);">Create New Campus Event</h2>
                <button type="button" class="modal-close-btn" onclick="closeAddEventModal()" aria-label="Close modal">&times;</button>
            </div>
            
            <form method="POST" action="admin_events.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="add_event">
                
                <div class="modal-body modal-body-scroll">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label class="form-label">Event Code <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="event_code" required class="form-input" placeholder="e.g. EVT-2026-099" style="font-family: 'JetBrains Mono', monospace; text-transform: uppercase;">
                        </div>
                        <div>
                            <label class="form-label">Category <span style="color:var(--danger)">*</span></label>
                            <select name="category" required class="form-input">
                                <?php foreach ($catList as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label class="form-label">Event Title <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="title" required class="form-input" placeholder="e.g. CHARUSAT Hackathon 2026">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label class="form-label">Event Date <span style="color:var(--danger)">*</span></label>
                            <input type="date" name="event_date" required class="form-input" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="form-label">Time / Schedule</label>
                            <input type="text" name="event_time" class="form-input" placeholder="e.g. 10:00 AM - 04:00 PM">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label class="form-label">Venue / Location <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="location" required class="form-input" placeholder="e.g. CSPIT Auditorium">
                        </div>
                        <div>
                            <label class="form-label">Organizing Body</label>
                            <input type="text" name="organizer" class="form-input" placeholder="e.g. Student Activities Council">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label class="form-label">Max Seating Capacity</label>
                            <input type="number" name="max_capacity" min="1" max="10000" class="form-input" value="100">
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="Upcoming" selected>Upcoming</option>
                                <option value="Open for Registration">Open for Registration</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-input" placeholder="Provide full event overview, speaker line-up, rules, and prerequisites..."></textarea>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label class="form-label">Event Poster Image (JPEG, PNG, WebP &le; 2 MB)</label>
                        <div class="file-upload-zone" onclick="document.getElementById('addPosterInput').click()">
                            <input type="file" id="addPosterInput" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="display:none;" onchange="previewPoster(this, 'addPosterPreviewBox')">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color: var(--primary); margin-bottom: 6px;"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <div style="font-size: 13.5px; font-weight: 600; color: var(--text-color);">Click to select poster image</div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">Strictly JPEG, PNG, or WebP &bull; Maximum 2 MB &bull; Safe server-side MIME verification</div>
                        </div>
                        <div id="addPosterPreviewBox" style="margin-top: 10px; display: none;"></div>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" onclick="closeAddEventModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule & Publish Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT EVENT -->
    <div id="editEventModal" class="modal-overlay modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="modal-card" style="max-width: 640px; width: 95%;">
            <div class="modal-header">
                <h2 id="editModalTitle" style="font-size: 18px; font-weight: 700; margin: 0; color: var(--text-color);">Edit Event Details</h2>
                <button type="button" class="modal-close-btn" onclick="closeEditEventModal()" aria-label="Close modal">&times;</button>
            </div>
            
            <form method="POST" action="admin_events.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="edit_event">
                <input type="hidden" id="edit_id" name="id" value="">
                
                <div class="modal-body modal-body-scroll">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label class="form-label">Event Code <span style="color:var(--danger)">*</span></label>
                            <input type="text" id="edit_event_code" name="event_code" required class="form-input" style="font-family: 'JetBrains Mono', monospace; text-transform: uppercase;">
                        </div>
                        <div>
                            <label class="form-label">Category <span style="color:var(--danger)">*</span></label>
                            <select id="edit_category" name="category" required class="form-input">
                                <?php foreach ($catList as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label class="form-label">Event Title <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="edit_title" name="title" required class="form-input">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label class="form-label">Event Date <span style="color:var(--danger)">*</span></label>
                            <input type="date" id="edit_event_date" name="event_date" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Time / Schedule</label>
                            <input type="text" id="edit_event_time" name="event_time" class="form-input">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label class="form-label">Venue / Location <span style="color:var(--danger)">*</span></label>
                            <input type="text" id="edit_location" name="location" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Organizing Body</label>
                            <input type="text" id="edit_organizer" name="organizer" class="form-input">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label class="form-label">Max Seating Capacity</label>
                            <input type="number" id="edit_max_capacity" name="max_capacity" min="1" max="10000" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select id="edit_status" name="status" class="form-input">
                                <option value="Upcoming">Upcoming</option>
                                <option value="Open for Registration">Open for Registration</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label class="form-label">Description</label>
                        <textarea id="edit_description" name="description" rows="3" class="form-input"></textarea>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label class="form-label">Update Poster Image (JPEG, PNG, WebP &le; 2 MB)</label>
                        
                        <div id="currentPosterDisplay" style="margin-bottom: 10px; display: none; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 10px; border-radius: var(--radius-sm);">
                            <img id="currentPosterImg" src="" alt="Current Poster" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 600;">Current Poster Attached</div>
                                <div style="font-size: 11.5px; color: var(--text-muted);">Upload a new file to replace it, or check below to remove.</div>
                            </div>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--danger); cursor: pointer;">
                                <input type="checkbox" name="remove_existing_poster" value="1">
                                <span>Remove Poster</span>
                            </label>
                        </div>

                        <div class="file-upload-zone" onclick="document.getElementById('editPosterInput').click()">
                            <input type="file" id="editPosterInput" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="display:none;" onchange="previewPoster(this, 'editPosterPreviewBox')">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color: var(--primary); margin-bottom: 6px;"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <div style="font-size: 13.5px; font-weight: 600; color: var(--text-color);">Click to upload new poster</div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">Replaces current poster &bull; Max 2 MB &bull; JPEG/PNG/WebP only</div>
                        </div>
                        <div id="editPosterPreviewBox" style="margin-top: 10px; display: none;"></div>
                    </div>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" onclick="closeEditEventModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: FULL POSTER PREVIEW -->
    <div id="posterPreviewModal" class="modal-overlay modal" role="dialog" aria-modal="true">
        <div class="modal-card" style="max-width: 580px; width: 95%; padding: 0; overflow: hidden; background: #000;">
            <div style="padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.8); color: white;">
                <span id="posterModalTitle" style="font-weight: 600; font-size: 14px;">Event Poster</span>
                <button type="button" class="modal-close-btn" onclick="closePosterModal()" style="color: white;">&times;</button>
            </div>
            <div style="text-align: center; padding: 12px;">
                <img id="posterModalImg" src="" alt="Full Poster" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 4px;">
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-open');
        }
        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('portal_theme', next);
        }

        // Initialize Theme from localStorage
        (function() {
            const savedTheme = localStorage.getItem('portal_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();

        // Modal Controls with Bulletproof Fixed Overlay & Visibility Classes
        function openAddEventModal() {
            var modal = document.getElementById('addEventModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('show', 'active');
                document.body.style.overflow = 'hidden';
                setTimeout(function() {
                    var input = modal.querySelector('input[name="event_code"]');
                    if (input) input.focus();
                }, 50);
            }
        }
        function closeAddEventModal() {
            var modal = document.getElementById('addEventModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show', 'active');
                document.body.style.overflow = '';
            }
        }

        function openEditEventModal(evt) {
            document.getElementById('edit_id').value = evt.id;
            document.getElementById('edit_event_code').value = evt.event_code;
            document.getElementById('edit_title').value = evt.title;
            document.getElementById('edit_category').value = evt.category;
            document.getElementById('edit_event_date').value = evt.event_date;
            document.getElementById('edit_event_time').value = evt.event_time || '';
            document.getElementById('edit_location').value = evt.location;
            document.getElementById('edit_organizer').value = evt.organizer || '';
            document.getElementById('edit_max_capacity').value = evt.max_capacity || 100;
            document.getElementById('edit_status').value = evt.status;
            document.getElementById('edit_description').value = evt.description || '';

            const posterDisplay = document.getElementById('currentPosterDisplay');
            const posterImg = document.getElementById('currentPosterImg');
            if (evt.poster_path) {
                posterImg.src = evt.poster_path;
                posterDisplay.style.display = 'flex';
            } else {
                posterDisplay.style.display = 'none';
            }

            document.getElementById('editPosterPreviewBox').style.display = 'none';
            document.getElementById('editPosterPreviewBox').innerHTML = '';

            var modal = document.getElementById('editEventModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('show', 'active');
                document.body.style.overflow = 'hidden';
            }
        }
        function closeEditEventModal() {
            var modal = document.getElementById('editEventModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show', 'active');
                document.body.style.overflow = '';
            }
        }

        function showPosterModal(src, title) {
            document.getElementById('posterModalImg').src = src;
            document.getElementById('posterModalTitle').textContent = title || 'Event Poster';
            var modal = document.getElementById('posterPreviewModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('show', 'active');
                document.body.style.overflow = 'hidden';
            }
        }
        function closePosterModal() {
            var modal = document.getElementById('posterPreviewModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show', 'active');
                document.body.style.overflow = '';
            }
        }

        function previewPoster(input, targetBoxId) {
            const box = document.getElementById(targetBoxId);
            if (!box) return;
            box.innerHTML = '';
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > 2 * 1024 * 1024) {
                    alert('Selected image exceeds the 2 MB limit! Please choose a smaller file.');
                    input.value = '';
                    box.style.display = 'none';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    box.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 10px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.3); padding: 8px 12px; border-radius: var(--radius-sm);">
                            <img src="${e.target.result}" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px;">
                            <div style="font-size: 12px; color: var(--success); font-weight: 600;">
                                Ready to upload: ${file.name} (${(file.size / 1024).toFixed(1)} KB)
                            </div>
                        </div>
                    `;
                    box.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                box.style.display = 'none';
            }
        }

        // Interactive Event Bindings (Button clicks, Backdrops, and Keyboard Escape)
        document.addEventListener('DOMContentLoaded', function() {
            var openAddBtn = document.getElementById('openAddEventModalBtn');
            var tableAddBtn = document.getElementById('tableAddEventBtn');
            var sidebarAddBtn = document.getElementById('sidebarAddEventBtn');
            var emptyAddBtn = document.getElementById('emptyStateAddEventBtn');

            if (openAddBtn) openAddBtn.addEventListener('click', openAddEventModal);
            if (tableAddBtn) tableAddBtn.addEventListener('click', openAddEventModal);
            if (sidebarAddBtn) sidebarAddBtn.addEventListener('click', openAddEventModal);
            if (emptyAddBtn) emptyAddBtn.addEventListener('click', openAddEventModal);

            // Backdrop click dismissal
            var addModal = document.getElementById('addEventModal');
            var editModal = document.getElementById('editEventModal');
            var posterModal = document.getElementById('posterPreviewModal');

            window.addEventListener('click', function(e) {
                if (e.target === addModal) closeAddEventModal();
                if (e.target === editModal) closeEditEventModal();
                if (e.target === posterModal) closePosterModal();
            });

            // Keyboard Escape key dismissal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeAddEventModal();
                    closeEditEventModal();
                    closePosterModal();
                    closeSidebar();
                }
            });

            // Automatically open modal if #add or ?action=new is requested in URL
            if (window.location.hash === '#add' || window.location.search.indexOf('action=new') !== -1) {
                openAddEventModal();
            }
        });
    </script>
</body>
</html>
