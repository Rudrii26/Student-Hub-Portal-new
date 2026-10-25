<?php
/**
 * CHARUSAT Student Hub - Public & Student Events REST API Endpoint
 * Provides dynamic database-backed events feed for campus.html and js/events.js.
 * Handles active events query, multi-parameter filtering, and event registration.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

try {
    $conn = getMysqliConnection();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    // --------------------------------------------------------------------------
    // Query active, non-deleted events
    // --------------------------------------------------------------------------
    $category = trim((string)($_GET['category'] ?? ''));
    $status   = trim((string)($_GET['status'] ?? ''));
    $search   = trim((string)($_GET['q'] ?? ''));

    $sql = "
        SELECT 
            e.id AS db_id,
            e.event_code AS id,
            e.title,
            e.category,
            e.event_date AS date,
            e.event_time AS time,
            e.location,
            e.organizer,
            e.max_capacity,
            e.status,
            e.description,
            e.poster_path,
            COUNT(r.id) AS registered_count
        FROM events e
        LEFT JOIN event_registrations r ON e.id = r.event_id
        WHERE e.is_deleted = 0
    ";

    $params = [];
    $types  = '';

    if ($category !== '' && strtolower($category) !== 'all') {
        $sql .= " AND LOWER(e.category) = LOWER(?)";
        $params[] = $category;
        $types .= 's';
    }

    if ($status !== '' && strtolower($status) !== 'all') {
        $sql .= " AND LOWER(e.status) = LOWER(?)";
        $params[] = $status;
        $types .= 's';
    }

    if ($search !== '') {
        $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ? OR e.organizer LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ssss';
    }

    $sql .= " GROUP BY e.id ORDER BY e.event_date ASC, e.id ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id'               => $row['id'], // e.g. EVT-2026-001
            'db_id'            => (int)$row['db_id'],
            'title'            => $row['title'],
            'category'         => $row['category'],
            'date'             => $row['date'],
            'time'             => $row['time'] ?? '',
            'location'         => $row['location'],
            'organizer'        => $row['organizer'] ?? '',
            'max_capacity'     => (int)($row['max_capacity'] ?? 100),
            'registered_count' => (int)$row['registered_count'],
            'status'           => $row['status'] ?? 'Upcoming',
            'description'      => $row['description'] ?? '',
            'poster_path'      => $row['poster_path'] ?? null
        ];
    }
    $stmt->close();

    echo json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;

} elseif ($method === 'POST') {
    // --------------------------------------------------------------------------
    // Handle Event Registration
    // --------------------------------------------------------------------------
    // Support JSON or Form Data
    $rawInput = file_get_contents('php://input');
    $input = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
    if (empty($input)) {
        $input = $_POST;
    }

    $action       = trim((string)($input['action'] ?? 'register'));
    $eventIdInput = trim((string)($input['event_id'] ?? $input['eventId'] ?? ''));

    if ($action !== 'register' || $eventIdInput === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameter: event_id']);
        exit;
    }

    // Look up event in database by event_code or numerical id
    $evtStmt = $conn->prepare("SELECT id, event_code, title, max_capacity FROM events WHERE (event_code = ? OR id = ?) AND is_deleted = 0 LIMIT 1");
    $evtStmt->bind_param('ss', $eventIdInput, $eventIdInput);
    $evtStmt->execute();
    $evtRes = $evtStmt->get_result();
    $eventData = $evtRes ? $evtRes->fetch_assoc() : null;
    $evtStmt->close();

    if (!$eventData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Event not found or has been closed.']);
        exit;
    }

    $eventId = (int)$eventData['id'];

    // Session or payload identification
    $userId       = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $studentId    = trim((string)($input['student_id'] ?? $_SESSION['username'] ?? 'GUEST-' . strtoupper(substr(uniqid(), 7))));
    $studentName  = trim((string)($input['student_name'] ?? $_SESSION['full_name'] ?? 'Guest Participant'));
    $studentEmail = trim((string)($input['student_email'] ?? $_SESSION['email'] ?? 'guest@charusat.edu.in'));
    $teamName     = trim((string)($input['team_name'] ?? ''));
    $track        = trim((string)($input['track_interest'] ?? 'General'));

    // Check duplicate registration
    $checkReg = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND student_id = ? LIMIT 1");
    $checkReg->bind_param('is', $eventId, $studentId);
    $checkReg->execute();
    $checkRegRes = $checkReg->get_result();
    if ($checkRegRes && $checkRegRes->num_rows > 0) {
        $checkReg->close();
        echo json_encode([
            'success'   => false,
            'isDuplicate' => true,
            'message'   => "Student {$studentId} is already registered for this event."
        ]);
        exit;
    }
    $checkReg->close();

    // Check capacity
    $capStmt = $conn->prepare("SELECT COUNT(*) AS total FROM event_registrations WHERE event_id = ?");
    $capStmt->bind_param('i', $eventId);
    $capStmt->execute();
    $capRes = $capStmt->get_result()->fetch_assoc();
    $capStmt->close();

    $currentCount = (int)($capRes['total'] ?? 0);
    $maxCap = (int)$eventData['max_capacity'];
    $status = ($maxCap > 0 && $currentCount >= $maxCap) ? 'Waitlisted' : 'Confirmed';

    // Insert registration
    $insertReg = $conn->prepare("
        INSERT INTO event_registrations (
            event_id, user_id, student_id, student_name, student_email, team_name, track_interest, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertReg->bind_param(
        'iissssss',
        $eventId, $userId, $studentId, $studentName, $studentEmail, $teamName, $track, $status
    );

    if ($insertReg->execute()) {
        $regId = $insertReg->insert_id;
        $insertReg->close();
        echo json_encode([
            'success'         => true,
            'message'         => "Registration confirmed! Digital entry pass generated.",
            'registration_id' => $regId,
            'event_code'      => $eventData['event_code'],
            'event_title'     => $eventData['title'],
            'status'          => $status
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error'   => "Failed to complete registration: " . $insertReg->error
        ]);
        $insertReg->close();
    }
    exit;
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
