<?php
require_once __DIR__ . '/../config/database.php';

// Fetch a member by ID or membership number
function findMember($identifier) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT * FROM tbl_members WHERE id = ? OR medlnr = ? LIMIT 1");
    $stmt->bind_param("ii", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    $stmt->close();
    closeDBConnection($conn);

    if (!$member) {
        return null;
    }

    foreach (['namn', 'fodelsedatum', 'primar_forening', 'primar_verksamhetsform'] as $field) {
        if (isset($member[$field]) && !is_null($member[$field])) {
            // Encode binary ciphertext so it is JSON safe
            $member[$field] = base64_encode($member[$field]);
        }
    }

    return $member;
}
