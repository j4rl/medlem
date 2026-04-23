<?php
// API endpoints for AJAX requests
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
require_once __DIR__ . '/../includes/user.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';
$user = getCurrentUser();
$isAdmin = userHasAdminAccess($user);

function denyCaseAccess(): void
{
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

switch ($action) {
    case 'get_stats':
        $stats = getCaseStatistics($user['id']);
        echo json_encode($stats);
        break;
        
    case 'get_cases':
        $status = $_GET['status'] ?? null;
        $cases = getAllCases($user['id'], $status);
        echo json_encode($cases);
        break;
        
    case 'get_case':
        $caseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!userCanAccessCaseId($caseId, (int)$user['id'], $isAdmin)) {
            denyCaseAccess();
        }
        $case = getCaseById($caseId);
        if ($case) {
            $comments = getCaseComments($caseId);
            $case['comments'] = $comments;
            echo json_encode($case);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Case not found']);
        }
        break;
        
    case 'add_comment':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $caseId = isset($_POST['case_id']) ? (int)$_POST['case_id'] : 0;
            $comment = $_POST['comment'] ?? '';

            if (!userCanAccessCaseId($caseId, (int)$user['id'], $isAdmin)) {
                denyCaseAccess();
            }
            
            if (empty($comment)) {
                http_response_code(400);
                echo json_encode(['error' => 'Comment cannot be empty']);
                exit();
            }
            
            if (addCaseComment($caseId, $user['id'], $comment)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add comment']);
            }
        }
        break;
        
    case 'update_case_status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $caseId = isset($_POST['case_id']) ? (int)$_POST['case_id'] : 0;
            $status = $_POST['status'] ?? '';

            if (!userCanAccessCaseId($caseId, (int)$user['id'], $isAdmin)) {
                denyCaseAccess();
            }
            
            $case = getCaseById($caseId);
            $handlerIds = $case['handler_ids'] ?? ($case['assigned_to'] ?? null);
            if ($case && updateCase($caseId, $case['title'], $case['description'], 
                                    $status, $case['priority'], $handlerIds)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update status']);
            }
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
