<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/crypto.php';

function encryptField($value) {
    if ($value === null) {
        return null;
    }
    try {
        return encryptValue((string)$value);
    } catch (RuntimeException $e) {
        throw $e;
    }
}

function decryptField($value) {
    if ($value === null || $value === '') {
        return null;
    }
    try {
        $plain = decryptValue($value);
        return $plain !== null ? $plain : $value;
    } catch (RuntimeException $e) {
        return $value;
    }
}

function normalizeHandlerIds($value): array {
    $ids = [];
    if (is_array($value)) {
        $ids = $value;
    } elseif ($value !== null && $value !== '') {
        $ids = [$value];
    }

    $ids = array_map(function ($id) {
        return (int)$id;
    }, $ids);

    $ids = array_filter($ids, function ($id) {
        return $id > 0;
    });

    return array_values(array_unique($ids));
}

function caseHandlersTableExists($conn): bool {
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    $result = $conn->query("SHOW TABLES LIKE 'case_handlers'");
    $exists = $result && $result->num_rows > 0;
    return $exists;
}

function fetchCaseHandlersMap($conn, array $caseIds): array {
    $map = [];
    if (empty($caseIds) || !caseHandlersTableExists($conn)) {
        return $map;
    }

    $placeholders = implode(',', array_fill(0, count($caseIds), '?'));
    $types = str_repeat('i', count($caseIds));
    $sql = "SELECT ch.case_id, u.id AS user_id, u.name AS full_name
            FROM case_handlers ch
            JOIN tbl_users u ON ch.user_id = u.id
            WHERE ch.case_id IN ($placeholders)
            ORDER BY u.name";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $map;
    }
    $stmt->bind_param($types, ...$caseIds);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $caseId = (int)$row['case_id'];
        if (!isset($map[$caseId])) {
            $map[$caseId] = ['ids' => [], 'names' => []];
        }
        $map[$caseId]['ids'][] = (int)$row['user_id'];
        $map[$caseId]['names'][] = $row['full_name'];
    }

    $stmt->close();
    return $map;
}

function applyCaseHandlers(array &$cases, array $handlerMap): void {
    foreach ($cases as &$case) {
        $caseId = (int)($case['id'] ?? 0);
        $handlerIds = $handlerMap[$caseId]['ids'] ?? [];
        $handlerNames = $handlerMap[$caseId]['names'] ?? [];

        if (empty($handlerIds) && !empty($case['assigned_to'])) {
            $handlerIds = [(int)$case['assigned_to']];
            if (!empty($case['assignee_name'])) {
                $handlerNames = [$case['assignee_name']];
            }
        }

        $case['handler_ids'] = $handlerIds;
        $case['handler_names'] = $handlerNames;

        if (!empty($handlerNames)) {
            $case['assignee_name'] = implode(', ', $handlerNames);
        }
    }
    unset($case);
}

function syncCaseHandlers($conn, int $caseId, array $handlerIds): void {
    if (!caseHandlersTableExists($conn)) {
        return;
    }

    $handlerIds = normalizeHandlerIds($handlerIds);

    $stmt = $conn->prepare("DELETE FROM case_handlers WHERE case_id = ?");
    $stmt->bind_param("i", $caseId);
    $stmt->execute();
    $stmt->close();

    if (empty($handlerIds)) {
        return;
    }

    $stmt = $conn->prepare("INSERT INTO case_handlers (case_id, user_id) VALUES (?, ?)");
    foreach ($handlerIds as $handlerId) {
        $stmt->bind_param("ii", $caseId, $handlerId);
        $stmt->execute();
    }
    $stmt->close();
}

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

function statusToDbValue($value) {
    $status = normalizeStatusValue($value);
    return strcasecmp($status, 'no_action') === 0 ? 'new' : $status;
}

function statusFromDbValue($value) {
    if (is_string($value) && strcasecmp($value, 'new') === 0) {
        return 'no_action';
    }
    return normalizeStatusValue($value);
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

    // Keep plain text as-is
    if (is_string($memberData)) {
        return $memberData;
    }

    // Encode arrays/objects, everything else coerced to string
    if (is_array($memberData) || is_object($memberData)) {
        return json_encode($memberData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return (string)$memberData;
}

function mapCaseRow($row) {
    if (!$row) return null;
    // Decrypt sensitive fields at rest
    $row['caseheader'] = isset($row['caseheader']) ? decryptField($row['caseheader']) : '';
    $row['case_data'] = isset($row['case_data']) ? decryptField($row['case_data']) : null;
    $row['member_data'] = isset($row['member_data']) ? decryptField($row['member_data']) : null;
    // Generate a virtual case number: YY-MM-#### (serial padded to 4)
    $year = isset($row['created']) ? date('y', strtotime($row['created'])) : date('y');
    $month = isset($row['created']) ? date('m', strtotime($row['created'])) : date('m');
    $row['case_number'] = $year . '-' . $month . '-' . str_pad((int)$row['id'], 4, '0', STR_PAD_LEFT);
    $row['title'] = $row['caseheader'] ?? '';
    $row['priority'] = normalizePriorityValue($row['prio'] ?? 'medium');
    $row['status'] = statusFromDbValue($row['status'] ?? 'in_progress');
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

    try {
        $encTitle = encryptField($title);
        $encCaseData = encryptField($caseDataJson);
        $encMemberData = encryptField($memberDataJson);
    } catch (RuntimeException $e) {
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'error_general'];
    }

    $handlerIds = normalizeHandlerIds($assignedTo);
    // Default taker to the creator so the case is immediately assigned
    $assignedToValue = $handlerIds[0] ?? $createdBy;

    $stmt = $conn->prepare("INSERT INTO tbl_cases (user_id, caseheader, taker_id, member_data, case_data, status, prio, created, changed) VALUES (?, ?, ?, ?, ?, 'in_progress', ?, NOW(), NOW())");
    $stmt->bind_param("isisss", $createdBy, $encTitle, $assignedToValue, $encMemberData, $encCaseData, $priority);

    if ($stmt->execute()) {
        $caseId = $stmt->insert_id;
        $now = new DateTimeImmutable();
        $caseNumber = $now->format('y-m') . '-' . str_pad($caseId, 4, '0', STR_PAD_LEFT);
        if (empty($handlerIds) && $assignedToValue) {
            $handlerIds = [$assignedToValue];
        }
        syncCaseHandlers($conn, $caseId, $handlerIds);
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
                   u1.name AS creator_name, COALESCE(NULLIF(u1.pic, ''), 'default.png') AS creator_picture,
                   u2.name AS assignee_name, COALESCE(NULLIF(u2.pic, ''), 'default.png') AS assignee_picture
            FROM tbl_cases c
            LEFT JOIN tbl_users u1 ON c.user_id = u1.id
            LEFT JOIN tbl_users u2 ON c.taker_id = u2.id
            WHERE 1=1";

    $params = [];
    $types = "";
    $hasCaseHandlers = caseHandlersTableExists($conn);

    if ($userId) {
        if ($scope === 'created') {
            $sql .= " AND c.user_id = ?";
            $params[] = $userId;
            $types .= "i";
        } elseif ($scope === 'assigned') {
            if ($hasCaseHandlers) {
                $sql .= " AND (c.taker_id = ? OR EXISTS (SELECT 1 FROM case_handlers ch WHERE ch.case_id = c.id AND ch.user_id = ?))";
                $params[] = $userId;
                $params[] = $userId;
                $types .= "ii";
            } else {
                $sql .= " AND c.taker_id = ?";
                $params[] = $userId;
                $types .= "i";
            }
        } else {
            if ($hasCaseHandlers) {
                $sql .= " AND (c.user_id = ? OR c.taker_id = ? OR EXISTS (SELECT 1 FROM case_handlers ch WHERE ch.case_id = c.id AND ch.user_id = ?))";
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
                $types .= "iii";
            } else {
                $sql .= " AND (c.user_id = ? OR c.taker_id = ?)";
                $params[] = $userId;
                $params[] = $userId;
                $types .= "ii";
            }
        }
    }

    if ($status) {
        $status = statusToDbValue($status);
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

    if (!empty($cases)) {
        $caseIds = array_map(function ($case) {
            return (int)$case['id'];
        }, $cases);
        $handlerMap = fetchCaseHandlersMap($conn, $caseIds);
        applyCaseHandlers($cases, $handlerMap);
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
                                   u1.name AS creator_name, COALESCE(NULLIF(u1.pic, ''), 'default.png') AS creator_picture, 
                                   u2.name AS assignee_name, COALESCE(NULLIF(u2.pic, ''), 'default.png') AS assignee_picture
                            FROM tbl_cases c
                            LEFT JOIN tbl_users u1 ON c.user_id = u1.id
                            LEFT JOIN tbl_users u2 ON c.taker_id = u2.id
                            WHERE c.id = ?");
    $stmt->bind_param("i", $caseId);
    $stmt->execute();
    $result = $stmt->get_result();

    $case = mapCaseRow($result->fetch_assoc());
    if ($case) {
        $cases = [$case];
        $handlerMap = fetchCaseHandlersMap($conn, [(int)$case['id']]);
        applyCaseHandlers($cases, $handlerMap);
        $case = $cases[0];
    }

    $stmt->close();
    closeDBConnection($conn);

    return $case;
}

// Update case
function updateCase($caseId, $title, $description, $status, $priority, $assignedTo = null, $caseData = [], $memberData = null) {
    $conn = getDBConnection();

    $status = normalizeStatusValue($status);
    $statusForDb = statusToDbValue($status);
    $priority = normalizePriorityValue($priority);
    $caseData = buildCaseDataPayload($caseData, $description);
    $caseDataJson = formatCaseData($caseData);
    $memberDataJson = formatMemberData($memberData);
    if (is_null($memberDataJson)) {
        $memberDataJson = '{}';
    }

    try {
        $encTitle = encryptField($title);
        $encCaseData = encryptField($caseDataJson);
        $encMemberData = encryptField($memberDataJson);
    } catch (RuntimeException $e) {
        closeDBConnection($conn);
        return false;
    }

    $handlerIds = normalizeHandlerIds($assignedTo);
    $assignedToValue = $handlerIds[0] ?? null;

    $stmt = $conn->prepare("UPDATE tbl_cases 
                            SET caseheader = ?, status = ?, prio = ?, taker_id = ?, case_data = ?, member_data = ?, changed = NOW() 
                            WHERE id = ?");
    $stmt->bind_param("sssissi", $encTitle, $statusForDb, $priority, $assignedToValue, $encCaseData, $encMemberData, $caseId);

    $success = $stmt->execute();
    if ($success) {
        syncCaseHandlers($conn, $caseId, $handlerIds);
    }

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

    $stmt = $conn->prepare("SELECT cc.*, u.name AS full_name, COALESCE(NULLIF(u.pic, ''), 'default.png') AS profile_picture 
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
                  FROM tbl_cases c";

    if ($userId) {
        if (caseHandlersTableExists($conn)) {
            $baseQuery .= " WHERE c.user_id = ? OR c.taker_id = ? OR EXISTS (SELECT 1 FROM case_handlers ch WHERE ch.case_id = c.id AND ch.user_id = ?)";
            $stmt = $conn->prepare($baseQuery);
            $stmt->bind_param("iii", $userId, $userId, $userId);
        } else {
            $baseQuery .= " WHERE c.user_id = ? OR c.taker_id = ?";
            $stmt = $conn->prepare($baseQuery);
            $stmt->bind_param("ii", $userId, $userId);
        }
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
