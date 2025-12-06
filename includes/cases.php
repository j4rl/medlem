<?php
require_once __DIR__ . '/../config/database.php';

function getStatusOptions(): array {
    $codes = ['no_action', 'in_progress', 'resolved', 'closed'];
    $options = [];
    foreach ($codes as $code) {
        $label = function_exists('__') ? __('status_' . $code) : $code;
        $options[$code] = $label;
    }
    return $options;
}

function getPriorityOptions(): array {
    $codes = ['low', 'medium', 'high', 'urgent'];
    $options = [];
    foreach ($codes as $code) {
        $label = function_exists('__') ? __('priority_' . $code) : $code;
        $options[$code] = $label;
    }
    return $options;
}

function normalizeStatusValue($value, $fallback = 'in_progress') {
    if (!is_string($value)) return $fallback;
    $value = substr(trim($value), 0, 20);
    if ($value === '') return $fallback;

    if (strcasecmp($value, 'new') === 0 || strcasecmp($value, 'status_new') === 0) {
        return 'no_action';
    }

    $options = getStatusOptions();
    if (array_key_exists($value, $options)) {
        return $value;
    }

    foreach ($options as $code => $label) {
        if (strcasecmp($value, $label) === 0) {
            return $code;
        }
    }

    return $fallback;
}

function normalizePriorityValue($value, $fallback = 'medium') {
    if (!is_string($value)) return $fallback;
    $value = substr(trim($value), 0, 20);
    if ($value === '') return $fallback;

    $options = getPriorityOptions();
    if (array_key_exists($value, $options)) {
        return $value;
    }

    foreach ($options as $code => $label) {
        if (strcasecmp($value, $label) === 0) {
            return $code;
        }
    }

    return $fallback;
}

// Normalize and enrich a case_data array
function buildCaseDataPayload($caseData = [], string $caseBody = '', array $meta = []) {
    if (is_string($caseData) && trim($caseData) !== '') {
        $decoded = json_decode($caseData, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $caseData = $decoded;
        }
    }

    if (!is_array($caseData)) {
        $caseData = [];
    }

    // Keep or set case body
    if ($caseBody !== '') {
        $caseData['case_body'] = $caseBody;
    } elseif (!isset($caseData['case_body']) && isset($caseData['description'])) {
        $caseData['case_body'] = $caseData['description'];
    }

    // Merge meta fields such as handler, recipient, etc.
    foreach ($meta as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $caseData[$key] = $value;
    }

    return $caseData;
}

// Normalize case_data payload to a JSON string and stamp last edit time
function formatCaseData($caseData = [], bool $touchEditTime = true) {
    if (is_string($caseData) && trim($caseData) !== '') {
        $decoded = json_decode($caseData, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $caseData = $decoded;
        }
    }

    if (!is_array($caseData)) {
        $caseData = [];
    }

    if ($touchEditTime) {
        $caseData['last_edited_at'] = date('c');
    }

    return json_encode($caseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Normalize member_data payload to a JSON string
function formatMemberData($memberData = null) {
    if (is_null($memberData)) {
        return null;
    }

    if (is_string($memberData) && trim($memberData) !== '') {
        $decoded = json_decode($memberData, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $memberData = $decoded;
        }
    }

    if (!is_array($memberData)) {
        return (string)$memberData;
    }

    return json_encode($memberData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function mapCaseRow($row) {
    if (!$row) return null;
    // Generate a virtual case number: YY-MM-#### (serial padded to 4)
    $year = isset($row['created']) ? date('y', strtotime($row['created'])) : date('y');
    $month = isset($row['created']) ? date('m', strtotime($row['created'])) : date('m');
    $row['case_number'] = $year . '-' . $month . '-' . str_pad((int)$row['id'], 4, '0', STR_PAD_LEFT);
    $row['title'] = $row['caseheader'] ?? '';
    $row['priority'] = normalizePriorityValue($row['prio'] ?? 'medium');
    $row['status'] = normalizeStatusValue($row['status'] ?? 'in_progress');
    $row['created_at'] = $row['created'] ?? null;
    $row['updated_at'] = $row['changed'] ?? $row['created'] ?? null;
    $row['assigned_to'] = $row['taker_id'] ?? null;
    // Decode description from case_data JSON if present
    $description = '';
    if (!empty($row['case_data'])) {
        $decoded = json_decode($row['case_data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $description = $decoded['case_body'] ?? ($decoded['description'] ?? '');
        }
    }
    $row['description'] = $description;
    return $row;
}

// Create case
function createCase($title, $description, $priority, $createdBy, $assignedTo = null, $caseData = [], $memberData = null) {
    $conn = getDBConnection();

    $priority = normalizePriorityValue($priority);
    $caseData = buildCaseDataPayload($caseData, $description);
    $caseDataJson = formatCaseData($caseData);
    $memberDataJson = formatMemberData($memberData);
    if (is_null($memberDataJson)) {
        $memberDataJson = '{}';
    }
    // Default taker to the creator so the case is immediately assigned
    $assignedToValue = $assignedTo ?: $createdBy;

    $stmt = $conn->prepare("INSERT INTO tbl_cases (user_id, caseheader, taker_id, member_data, case_data, status, prio, created, changed) VALUES (?, ?, ?, ?, ?, 'in_progress', ?, NOW(), NOW())");
    $stmt->bind_param("isisss", $createdBy, $title, $assignedToValue, $memberDataJson, $caseDataJson, $priority);

    if ($stmt->execute()) {
        $caseId = $stmt->insert_id;
        $now = new DateTimeImmutable();
        $caseNumber = $now->format('y-m') . '-' . str_pad($caseId, 4, '0', STR_PAD_LEFT);
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => true, 'case_id' => $caseId, 'case_number' => $caseNumber];
    }

    $stmt->close();
    closeDBConnection($conn);
    return ['success' => false, 'error' => 'error_general'];
}

// Get all cases
function getAllCases($userId = null, $status = null, $scope = 'related') {
    $conn = getDBConnection();

    $sql = "SELECT c.*, 
                   u1.name AS creator_name, u1.pic AS creator_picture,
                   u2.name AS assignee_name, u2.pic AS assignee_picture
            FROM tbl_cases c
            LEFT JOIN tbl_users u1 ON c.user_id = u1.id
            LEFT JOIN tbl_users u2 ON c.taker_id = u2.id
            WHERE 1=1";

    $params = [];
    $types = "";

    if ($userId) {
        if ($scope === 'created') {
            $sql .= " AND c.user_id = ?";
            $params[] = $userId;
            $types .= "i";
        } elseif ($scope === 'assigned') {
            $sql .= " AND c.taker_id = ?";
            $params[] = $userId;
            $types .= "i";
        } else {
            $sql .= " AND (c.user_id = ? OR c.taker_id = ?)";
            $params[] = $userId;
            $params[] = $userId;
            $types .= "ii";
        }
    }

    if ($status) {
        $status = normalizeStatusValue($status);
        $sql .= " AND c.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $sql .= " ORDER BY c.created DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $cases = [];
    while ($row = $result->fetch_assoc()) {
        $cases[] = mapCaseRow($row);
    }

    if (isset($stmt)) {
        $stmt->close();
    }
    closeDBConnection($conn);

    return $cases;
}

// Get case by ID
function getCaseById($caseId) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT c.*, 
                                   u1.name AS creator_name, u1.pic AS creator_picture, 
                                   u2.name AS assignee_name, u2.pic AS assignee_picture
                            FROM tbl_cases c
                            LEFT JOIN tbl_users u1 ON c.user_id = u1.id
                            LEFT JOIN tbl_users u2 ON c.taker_id = u2.id
                            WHERE c.id = ?");
    $stmt->bind_param("i", $caseId);
    $stmt->execute();
    $result = $stmt->get_result();

    $case = mapCaseRow($result->fetch_assoc());

    $stmt->close();
    closeDBConnection($conn);

    return $case;
}

// Update case
function updateCase($caseId, $title, $description, $status, $priority, $assignedTo = null, $caseData = [], $memberData = null) {
    $conn = getDBConnection();

    $status = normalizeStatusValue($status);
    $priority = normalizePriorityValue($priority);
    $caseData = buildCaseDataPayload($caseData, $description);
    $caseDataJson = formatCaseData($caseData);
    $memberDataJson = formatMemberData($memberData);
    if (is_null($memberDataJson)) {
        $memberDataJson = '{}';
    }
    $assignedToValue = $assignedTo ?: null;

    $stmt = $conn->prepare("UPDATE tbl_cases 
                            SET caseheader = ?, status = ?, prio = ?, taker_id = ?, case_data = ?, member_data = ?, changed = NOW() 
                            WHERE id = ?");
    $stmt->bind_param("sssissi", $title, $status, $priority, $assignedToValue, $caseDataJson, $memberDataJson, $caseId);

    $success = $stmt->execute();

    $stmt->close();
    closeDBConnection($conn);

    return $success;
}

// Delete case
function deleteCase($caseId) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("DELETE FROM tbl_cases WHERE id = ?");
    $stmt->bind_param("i", $caseId);

    $success = $stmt->execute();

    $stmt->close();
    closeDBConnection($conn);

    return $success;
}

// Add comment to case (keeps existing comment table)
function addCaseComment($caseId, $userId, $comment) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("INSERT INTO case_comments (case_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $caseId, $userId, $comment);

    $success = $stmt->execute();

    $stmt->close();
    closeDBConnection($conn);

    return $success;
}

// Get case comments
function getCaseComments($caseId) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT cc.*, u.name AS full_name, u.pic AS profile_picture 
                            FROM case_comments cc 
                            JOIN tbl_users u ON cc.user_id = u.id 
                            WHERE cc.case_id = ? 
                            ORDER BY cc.created_at ASC");
    $stmt->bind_param("i", $caseId);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    $stmt->close();
    closeDBConnection($conn);

    return $comments;
}

// Get case statistics
function getCaseStatistics($userId = null) {
    $conn = getDBConnection();

    $baseQuery = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status IN ('no_action','new') THEN 1 ELSE 0 END) AS no_action_count,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS closed_count,
                    SUM(CASE WHEN prio = 'low' THEN 1 ELSE 0 END) AS low_count,
                    SUM(CASE WHEN prio = 'medium' THEN 1 ELSE 0 END) AS medium_count,
                    SUM(CASE WHEN prio = 'high' THEN 1 ELSE 0 END) AS high_count,
                    SUM(CASE WHEN prio = 'urgent' THEN 1 ELSE 0 END) AS urgent_count
                  FROM tbl_cases";

    if ($userId) {
        $baseQuery .= " WHERE user_id = ? OR taker_id = ?";
        $stmt = $conn->prepare($baseQuery);
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($baseQuery);
    }

    $row = $result->fetch_assoc() ?: [];

    if (isset($stmt)) {
        $stmt->close();
    }
    closeDBConnection($conn);

    $noActionCount = (int)($row['no_action_count'] ?? 0);
    $inProgressCount = (int)($row['in_progress_count'] ?? 0);
    $resolvedCount = (int)($row['resolved_count'] ?? 0);
    $closedCount = (int)($row['closed_count'] ?? 0);
    $lowCount = (int)($row['low_count'] ?? 0);
    $mediumCount = (int)($row['medium_count'] ?? 0);
    $highCount = (int)($row['high_count'] ?? 0);
    $urgentCount = (int)($row['urgent_count'] ?? 0);
    $totalCount = (int)($row['total'] ?? 0);

    return [
        'total' => $totalCount,
        'open' => $noActionCount + $inProgressCount,
        'resolved' => $resolvedCount,
        'closed' => $closedCount,
        'status_counts' => [
            'no_action' => $noActionCount,
            'in_progress' => $inProgressCount,
            'resolved' => $resolvedCount,
            'closed' => $closedCount,
        ],
        'priority_counts' => [
            'low' => $lowCount,
            'medium' => $mediumCount,
            'high' => $highCount,
            'urgent' => $urgentCount,
        ],
    ];
}

// Get all users for assignment
function getAllUsers() {
    $conn = getDBConnection();

    $result = $conn->query("SELECT id, username, name AS full_name FROM tbl_users ORDER BY name");

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    closeDBConnection($conn);

    return $users;
}
?>
