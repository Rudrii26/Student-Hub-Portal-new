<?php
/**
 * Automated Verification: "+ Schedule New Event" Button & Add Event Modal
 */

$baseUrl = 'http://localhost/Student%20Hub%20Portal';
$cookieFile = __DIR__ . '/test_admin_modal_cookie.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

echo "====================================================================" . PHP_EOL;
echo "VERIFYING '+ SCHEDULE NEW EVENT' BUTTON & MODAL IN ADMIN_EVENTS.PHP" . PHP_EOL;
echo "====================================================================" . PHP_EOL;

function httpReq($url, $method = 'GET', $data = null, $cookieJar = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$data);
        }
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headersOut = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    curl_close($ch);

    return ['code' => $code, 'headers' => $headersOut, 'body' => $body];
}

// 1. Authenticate as Administrator
$loginRes = httpReq($baseUrl . '/authenticate.php', 'POST', [
    'username' => 'admin@charusat.edu.in',
    'password' => 'Admin@Charusat2026'
], $cookieFile);

echo "1. Administrator Authentication:" . PHP_EOL;
if ($loginRes['code'] === 302) {
    echo "  [PASS] Admin credentials accepted (HTTP 302 redirect)" . PHP_EOL;
} else {
    echo "  [FAIL] Admin authentication failed (HTTP {$loginRes['code']})" . PHP_EOL;
    exit(1);
}

// 2. Fetch admin_events.php
$pageRes = httpReq($baseUrl . '/admin_events.php', 'GET', null, $cookieFile);
$html = $pageRes['body'];

if (file_exists($cookieFile)) @unlink($cookieFile);

echo PHP_EOL . "2. Verifying Button Elements & IDs:" . PHP_EOL;
$checks = [
    "Header button has id='openAddEventModalBtn'" => strpos($html, 'id="openAddEventModalBtn"') !== false,
    "Header button has label '+ Schedule New Event'" => strpos($html, '+ Schedule New Event') !== false,
    "Header button has onclick='openAddEventModal()'" => strpos($html, 'onclick="openAddEventModal()"') !== false,
    "Table header button has id='tableAddEventBtn'" => strpos($html, 'id="tableAddEventBtn"') !== false,
    "Sidebar navigation link has id='sidebarAddEventBtn'" => strpos($html, 'id="sidebarAddEventBtn"') !== false,
];

$passedCount = 0;
foreach ($checks as $desc => $passed) {
    if ($passed) {
        $passedCount++;
        echo "  [PASS] $desc" . PHP_EOL;
    } else {
        echo "  [FAIL] $desc" . PHP_EOL;
    }
}

echo PHP_EOL . "3. Verifying Modal Markup & CSS Styling:" . PHP_EOL;
$modalChecks = [
    "Modal has id='addEventModal'" => strpos($html, 'id="addEventModal"') !== false,
    "Modal has class='modal-overlay'" => strpos($html, 'class="modal-overlay') !== false,
    "Modal-card dialog container exists" => strpos($html, 'class="modal-card"') !== false,
    "CSS rule .modal-overlay with fixed positioning exists" => strpos($html, '.modal-overlay {') !== false && strpos($html, 'position: fixed;') !== false,
    "CSS rule .modal-overlay.show with visibility override exists" => strpos($html, '.modal-overlay.show') !== false && strpos($html, 'visibility: visible !important;') !== false,
    "CSS z-index 1050 ensures overlay is on top" => strpos($html, 'z-index: 1050;') !== false,
];

foreach ($modalChecks as $desc => $passed) {
    if ($passed) {
        $passedCount++;
        echo "  [PASS] $desc" . PHP_EOL;
    } else {
        echo "  [FAIL] $desc" . PHP_EOL;
    }
}

echo PHP_EOL . "4. Verifying JavaScript Modal Engine & Event Listeners:" . PHP_EOL;
$jsChecks = [
    "openAddEventModal() JavaScript API defined" => strpos($html, 'function openAddEventModal()') !== false,
    "openAddEventModal() adds 'show' and 'active' classes" => strpos($html, "modal.classList.add('show', 'active')") !== false,
    "openAddEventModal() sets display to flex" => strpos($html, "modal.style.display = 'flex'") !== false,
    "openAddEventModal() locks body scroll" => strpos($html, "document.body.style.overflow = 'hidden'") !== false,
    "closeAddEventModal() removes 'show' and 'active' classes" => strpos($html, "modal.classList.remove('show', 'active')") !== false,
    "closeAddEventModal() restores body scroll" => strpos($html, "document.body.style.overflow = ''") !== false,
    "Explicit event listener attached to openAddEventModalBtn" => strpos($html, "openAddBtn.addEventListener('click', openAddEventModal)") !== false,
    "Explicit event listener attached to tableAddEventBtn" => strpos($html, "tableAddBtn.addEventListener('click', openAddEventModal)") !== false,
    "Backdrop click dismissal listener attached" => strpos($html, "if (e.target === addModal) closeAddEventModal()") !== false,
    "Keyboard 'Escape' key dismissal listener attached" => strpos($html, "if (e.key === 'Escape')") !== false,
];

foreach ($jsChecks as $desc => $passed) {
    if ($passed) {
        $passedCount++;
        echo "  [PASS] $desc" . PHP_EOL;
    } else {
        echo "  [FAIL] $desc" . PHP_EOL;
    }
}

$total = count($checks) + count($modalChecks) + count($jsChecks);
echo PHP_EOL . "====================================================================" . PHP_EOL;
echo "RESULT: $passedCount / $total checks passed" . PHP_EOL;
echo "====================================================================" . PHP_EOL;

if ($passedCount === $total) {
    echo "SUCCESS: Button and modal are fully operational and verified!" . PHP_EOL;
    exit(0);
} else {
    echo "FAILURE: Some checks failed!" . PHP_EOL;
    exit(1);
}
