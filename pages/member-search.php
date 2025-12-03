<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/members.php';

requireLogin();
header('Content-Type: application/json');

$query = trim((string)($_GET['q'] ?? ''));
$lower = function (string $value): string {
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value);
    }
    return strtolower($value);
};

if ($query === '') {
    echo json_encode(['success' => false, 'error' => 'Missing search term'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$members = fetchMembers();
$needle = $lower($query);
$results = [];

foreach ($members as $member) {
    $name = $lower((string)($member['namn'] ?? ''));
    $number = (string)($member['medlnr'] ?? '');
    if (strpos($name, $needle) === false && strpos($number, $needle) === false) {
        continue;
    }

    $results[] = [
        'id' => $member['id'] ?? null,
        'medlnr' => $member['medlnr'] ?? '',
        'namn' => $member['namn'] ?? '',
        'forening' => $member['forening'] ?? '',
        'medlemsform' => $member['medlemsform'] ?? '',
        'befattning' => $member['befattning'] ?? '',
        'verksamhetsform' => $member['verksamhetsform'] ?? '',
        'arbetsplats' => $member['arbetsplats'] ?? '',
    ];

    if (count($results) >= 10) {
        break;
    }
}

echo json_encode(['success' => true, 'results' => $results], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
