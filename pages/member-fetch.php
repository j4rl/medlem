<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/members.php';

requireLogin();
header('Content-Type: application/json');

$identifier = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($identifier <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid member id']);
    exit();
}

$member = findMember($identifier);

if (!$member) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Member not found']);
    exit();
}

echo json_encode(['success' => true, 'member' => $member], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
