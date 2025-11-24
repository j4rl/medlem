<?php
require_once __DIR__ . '/../config/database.php';

// Create case
function createCase($title, $description, $priority, $createdBy, $assignedTo = null) {
    $conn = getDBConnection();
    
    // Generate case number - get last case number and increment
    $year = date('Y');
    $result = $conn->query("SELECT case_number FROM cases WHERE case_number LIKE 'CASE-{$year}-%' ORDER BY id DESC LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $lastCase = $result->fetch_assoc();
        $lastNumber = (int)substr($lastCase['case_number'], -4);
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $newNumber = '0001';
    }
    
    $caseNumber = 'CASE-' . $year . '-' . $newNumber;
    
    // Insert case
    if ($assignedTo) {
        $stmt = $conn->prepare("INSERT INTO cases (case_number, title, description, priority, created_by, assigned_to) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $caseNumber, $title, $description, $priority, $createdBy, $assignedTo);
    } else {
        $stmt = $conn->prepare("INSERT INTO cases (case_number, title, description, priority, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $caseNumber, $title, $description, $priority, $createdBy);
    }
    
    if ($stmt->execute()) {
        $caseId = $stmt->insert_id;
        $stmt->close();
        closeDBConnection($conn);
        return ['success' => true, 'case_id' => $caseId, 'case_number' => $caseNumber];
    }
    
    $stmt->close();
    closeDBConnection($conn);
    return ['success' => false, 'error' => 'error_general'];
}

// Get all cases
function getAllCases($userId = null, $status = null) {
    $conn = getDBConnection();
    
    $sql = "SELECT c.*, u1.full_name as creator_name, u2.full_name as assignee_name 
            FROM cases c 
            LEFT JOIN users u1 ON c.created_by = u1.id 
            LEFT JOIN users u2 ON c.assigned_to = u2.id 
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($userId) {
        $sql .= " AND (c.created_by = ? OR c.assigned_to = ?)";
        $params[] = $userId;
        $params[] = $userId;
        $types .= "ii";
    }
    
    if ($status) {
        $sql .= " AND c.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
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
        $cases[] = $row;
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
    
    $stmt = $conn->prepare("SELECT c.*, u1.full_name as creator_name, u1.profile_picture as creator_picture, 
                            u2.full_name as assignee_name, u2.profile_picture as assignee_picture 
                            FROM cases c 
                            LEFT JOIN users u1 ON c.created_by = u1.id 
                            LEFT JOIN users u2 ON c.assigned_to = u2.id 
                            WHERE c.id = ?");
    $stmt->bind_param("i", $caseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $case = $result->fetch_assoc();
    
    $stmt->close();
    closeDBConnection($conn);
    
    return $case;
}

// Update case
function updateCase($caseId, $title, $description, $status, $priority, $assignedTo = null) {
    $conn = getDBConnection();
    
    if ($assignedTo) {
        $stmt = $conn->prepare("UPDATE cases SET title = ?, description = ?, status = ?, priority = ?, assigned_to = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $title, $description, $status, $priority, $assignedTo, $caseId);
    } else {
        $stmt = $conn->prepare("UPDATE cases SET title = ?, description = ?, status = ?, priority = ?, assigned_to = NULL WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $description, $status, $priority, $caseId);
    }
    
    $success = $stmt->execute();
    
    $stmt->close();
    closeDBConnection($conn);
    
    return $success;
}

// Delete case
function deleteCase($caseId) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("DELETE FROM cases WHERE id = ?");
    $stmt->bind_param("i", $caseId);
    
    $success = $stmt->execute();
    
    $stmt->close();
    closeDBConnection($conn);
    
    return $success;
}

// Add comment to case
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
    
    $stmt = $conn->prepare("SELECT cc.*, u.full_name, u.profile_picture 
                            FROM case_comments cc 
                            JOIN users u ON cc.user_id = u.id 
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
    
    if ($userId) {
        $stmt = $conn->prepare("SELECT 
                                COUNT(*) as total,
                                SUM(CASE WHEN status IN ('new', 'in_progress') THEN 1 ELSE 0 END) as open,
                                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
                                FROM cases 
                                WHERE created_by = ? OR assigned_to = ?");
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT 
                                COUNT(*) as total,
                                SUM(CASE WHEN status IN ('new', 'in_progress') THEN 1 ELSE 0 END) as open,
                                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
                                FROM cases");
    }
    
    $stats = $result->fetch_assoc();
    
    if (isset($stmt)) {
        $stmt->close();
    }
    closeDBConnection($conn);
    
    return $stats;
}

// Get all users for assignment
function getAllUsers() {
    $conn = getDBConnection();
    
    $result = $conn->query("SELECT id, username, full_name FROM users ORDER BY full_name");
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    closeDBConnection($conn);
    
    return $users;
}
?>
