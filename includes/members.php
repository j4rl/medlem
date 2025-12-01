<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/crypto.php';

function tableExists(mysqli $conn, string $table): bool
{
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");
    return $result && $result->num_rows > 0;
}

function normalizeHeaderLabel(string $header): string
{
    // Normalize common encodings (UTF-8 / Windows-1252) and strip any BOM
    $header = str_replace("\xEF\xBB\xBF", '', $header);
    if (function_exists('mb_detect_encoding')) {
        $encoding = mb_detect_encoding($header, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $header = iconv($encoding, 'UTF-8//IGNORE', $header);
        }
    }

    $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
    if ($clean === false) {
        $clean = $header;
    }

    $clean = strtolower($clean);
    $clean = preg_replace('/[^a-z0-9# ]/', ' ', $clean);
    $clean = preg_replace('/\s+/', ' ', $clean);

    return trim($clean);
}

function valueLooksEncrypted(?string $value): bool
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    $decoded = base64_decode($value, true);
    return $decoded !== false && strlen($decoded) >= 28;
}

function decryptMemberRow(?array $row): ?array
{
    if (!$row) {
        return null;
    }

    $fields = ['link', 'namn', 'fodelsedatum', 'forening', 'medlemsform', 'befattning', 'verksamhetsform', 'arbetsplats'];

    foreach ($fields as $field) {
        if (!array_key_exists($field, $row)) {
            continue;
        }

        $value = $row[$field];
        if (!valueLooksEncrypted($value)) {
            continue;
        }

        try {
            $row[$field] = decryptValue($value);
        } catch (Throwable $e) {
            // Leave the stored value intact if decryption fails
            $row[$field] = $value;
        }
    }

    // Normalize nulls to empty string for display
    foreach ($fields as $field) {
        if (array_key_exists($field, $row) && $row[$field] === null) {
            $row[$field] = '';
        }
    }

    return $row;
}

// Fetch a member by ID or membership number
function findMember($identifier)
{
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT * FROM tbl_members WHERE id = ? OR medlnr = ? LIMIT 1");
    $stmt->bind_param("ii", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    $stmt->close();
    closeDBConnection($conn);

    return decryptMemberRow($member);
}

function getMemberImportHistory(int $limit = 20): array
{
    $conn = getDBConnection();
    if (!tableExists($conn, 'tbl_member_imports')) {
        closeDBConnection($conn);
        return [];
    }

    $stmt = $conn->prepare("SELECT mi.*, u.name AS imported_by_name 
                            FROM tbl_member_imports mi 
                            LEFT JOIN tbl_users u ON mi.imported_by = u.id 
                            ORDER BY mi.imported_at DESC 
                            LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $stmt->close();
    closeDBConnection($conn);

    return $history;
}

function parseBirthdate(?string $value): ?DateTimeImmutable
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^(\\d{4})[- ]?(\\d{2})[- ]?(\\d{2})$/', $value, $matches)) {
        try {
            return new DateTimeImmutable(sprintf('%s-%s-%s', $matches[1], $matches[2], $matches[3]));
        } catch (Throwable $e) {
            return null;
        }
    }

    try {
        return new DateTimeImmutable($value);
    } catch (Throwable $e) {
        return null;
    }
}

function getFiftiethBirthday(?string $birthdate): ?DateTimeImmutable
{
    $date = parseBirthdate($birthdate);
    if (!$date) {
        return null;
    }

    try {
        return $date->modify('+50 years');
    } catch (Throwable $e) {
        return null;
    }
}

function fetchMembers(): array
{
    $conn = getDBConnection();
    if (!tableExists($conn, 'tbl_members')) {
        closeDBConnection($conn);
        return [];
    }

    $members = [];
    $result = $conn->query("SELECT * FROM tbl_members");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $members[] = decryptMemberRow($row);
        }
        $result->free();
    }

    closeDBConnection($conn);
    return $members;
}

function filterAndSortMembers(array $members, array $options = []): array
{
    $search = trim((string)($options['search'] ?? ''));
    $fieldsToMatch = [
        'arbetsplats' => trim((string)($options['arbetsplats'] ?? '')),
        'medlemsform' => trim((string)($options['medlemsform'] ?? '')),
        'befattning' => trim((string)($options['befattning'] ?? '')),
        'verksamhetsform' => trim((string)($options['verksamhetsform'] ?? '')),
    ];
    $turns50Months = isset($options['turns50_months']) ? (int)$options['turns50_months'] : null;

    $filtered = array_filter($members, function ($member) use ($search, $fieldsToMatch, $turns50Months) {
        if ($search !== '' && stripos((string)($member['namn'] ?? ''), $search) === false) {
            return false;
        }

        foreach ($fieldsToMatch as $field => $value) {
            if ($value === '') {
                continue;
            }
            if (stripos((string)($member[$field] ?? ''), $value) === false) {
                return false;
            }
        }

        if ($turns50Months !== null) {
            $fifty = getFiftiethBirthday($member['fodelsedatum'] ?? null);
            if (!$fifty) {
                return false;
            }

            $now = new DateTimeImmutable('today');
            $cutoff = $now->modify('+' . $turns50Months . ' months');
            if ($fifty < $now || $fifty > $cutoff) {
                return false;
            }
        }

        return true;
    });

    $sortBy = $options['sort_by'] ?? 'namn';
    $allowedSorts = ['medlnr', 'namn', 'fodelsedatum', 'forening', 'medlemsform', 'befattning', 'verksamhetsform', 'arbetsplats'];
    if (!in_array($sortBy, $allowedSorts, true)) {
        $sortBy = 'namn';
    }

    $direction = strtolower($options['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

    usort($filtered, function ($a, $b) use ($sortBy, $direction) {
        $valueA = $a[$sortBy] ?? '';
        $valueB = $b[$sortBy] ?? '';

        if ($sortBy === 'medlnr') {
            $cmp = (int)$valueA <=> (int)$valueB;
        } elseif ($sortBy === 'fodelsedatum') {
            $dateA = parseBirthdate($valueA);
            $dateB = parseBirthdate($valueB);
            $tsA = $dateA ? $dateA->getTimestamp() : 0;
            $tsB = $dateB ? $dateB->getTimestamp() : 0;
            $cmp = $tsA <=> $tsB;
        } else {
            $cmp = strcasecmp((string)$valueA, (string)$valueB);
        }

        return $direction === 'asc' ? $cmp : -$cmp;
    });

    return array_values($filtered);
}

function mapHeadersToFields(array $headers): array
{
    $map = [];
    // Expected CSV headers -> DB fields (normalized)
    $headerFieldMap = [
        '#' => 'link',
        'medlemsnummer' => 'medlnr',
        'namn' => 'namn',
        'fodelsedatum' => 'fodelsedatum',
        'födelsedatum' => 'fodelsedatum',
        'primar forening' => 'forening',
        'primär förening' => 'forening',
        'primaer forening' => 'forening',
        'medlemsform' => 'medlemsform',
        'primar befattning' => 'befattning',
        'primär befattning' => 'befattning',
        'primaer befattning' => 'befattning',
        'primar verksamhetsform' => 'verksamhetsform',
        'primär verksamhetsform' => 'verksamhetsform',
        'primaer verksamhetsform' => 'verksamhetsform',
        'primar arbetsplats' => 'arbetsplats',
        'primär arbetsplats' => 'arbetsplats',
        'primaer arbetsplats' => 'arbetsplats',
        'primar abetsplats' => 'arbetsplats', // common typo (missing r)
        // Fallbacks (if someone drops "primar/primär" from the header)
        'forening' => 'forening',
        'förening' => 'forening',
        'befattning' => 'befattning',
        'verksamhetsform' => 'verksamhetsform',
        'arbetsplats' => 'arbetsplats',
    ];

    // Allow matching headers that lose characters (e.g. �) by collapsing spaces
    $normalizedFieldMap = $headerFieldMap;
    foreach ($headerFieldMap as $key => $field) {
        $compactKey = str_replace(' ', '', $key);
        if (!isset($normalizedFieldMap[$compactKey])) {
            $normalizedFieldMap[$compactKey] = $field;
        }
    }

    foreach ($headers as $index => $header) {
        $normalized = normalizeHeaderLabel($header);
        $normalizedCompacted = str_replace(' ', '', $normalized);

        foreach ([$normalized, $normalizedCompacted] as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (isset($normalizedFieldMap[$candidate])) {
                $map[$normalizedFieldMap[$candidate]] = $index;
                continue 2; // matched this header, move to the next one
            }
        }

        // Fuzzy fallback: match by keyword fragments
        $rawLower = strtolower($header);
        if (strpos($rawLower, 'befatt') !== false && !isset($map['befattning'])) {
            $map['befattning'] = $index;
            continue;
        }
        if (strpos($rawLower, 'arbetsplats') !== false && !isset($map['arbetsplats'])) {
            $map['arbetsplats'] = $index;
            continue;
        }
        if (strpos($rawLower, 'betsplats') !== false && !isset($map['arbetsplats'])) {
            $map['arbetsplats'] = $index;
            continue;
        }
    }

    return $map;
}

function importMembersFromCsv(string $filePath, int $userId, string $originalFileName = ''): array
{
    if (!encryptionIsConfigured()) {
        return [
            'success' => false,
            'error' => 'encryption_not_configured',
            'errors' => []
        ];
    }

    if (!file_exists($filePath) || !is_readable($filePath)) {
        return [
            'success' => false,
            'error' => 'file_not_readable',
            'errors' => []
        ];
    }

    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return [
            'success' => false,
            'error' => 'file_not_readable',
            'errors' => []
        ];
    }

    $firstLine = fgets($handle);
    if ($firstLine === false) {
        fclose($handle);
        return [
            'success' => false,
            'error' => 'empty_file',
            'errors' => []
        ];
    }

    $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    $headerLine = ltrim($firstLine, "\xEF\xBB\xBF"); // strip BOM if present
    $headers = str_getcsv(trim($headerLine), $delimiter);
    $headerMap = mapHeadersToFields($headers);

    $required = ['link', 'medlnr', 'namn', 'medlemsform', 'befattning', 'arbetsplats'];
    $missing = array_filter($required, fn($field) => !isset($headerMap[$field]));
    if (!empty($missing)) {
        fclose($handle);
        return [
            'success' => false,
            'error' => 'missing_columns',
            'missing' => $missing,
            'errors' => []
        ];
    }

    $idxLink = $headerMap['link'];
    $idxMedlnr = $headerMap['medlnr'];
    $idxNamn = $headerMap['namn'];
    $idxFodelsedatum = $headerMap['fodelsedatum'] ?? null;
    $idxForening = $headerMap['forening'] ?? null;
    $idxMedlemsform = $headerMap['medlemsform'];
    $idxBefattning = $headerMap['befattning'];
    $idxVerksamhetsform = $headerMap['verksamhetsform'] ?? null;
    $idxArbetsplats = $headerMap['arbetsplats'];

    $conn = getDBConnection();
    if (!tableExists($conn, 'tbl_members')) {
        fclose($handle);
        closeDBConnection($conn);
        return [
            'success' => false,
            'error' => 'missing_table',
            'errors' => []
        ];
    }

    $stmt = $conn->prepare("
        INSERT INTO tbl_members (link, medlnr, namn, fodelsedatum, forening, medlemsform, befattning, verksamhetsform, arbetsplats)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            link = VALUES(link),
            namn = VALUES(namn),
            fodelsedatum = VALUES(fodelsedatum),
            forening = VALUES(forening),
            medlemsform = VALUES(medlemsform),
            befattning = VALUES(befattning),
            verksamhetsform = VALUES(verksamhetsform),
            arbetsplats = VALUES(arbetsplats)
    ");

    $total = 0;
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $inactivated = 0;
    $errors = [];
    $lineNumber = 1; // already read header
    $seenMedlnr = [];

    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $lineNumber++;

        // Skip empty lines
        $isEmptyRow = true;
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') {
                $isEmptyRow = false;
                break;
            }
        }
        if ($isEmptyRow) {
            continue;
        }

        $total++;
        $medlnrRaw = $row[$idxMedlnr] ?? '';
        $medlnr = is_numeric($medlnrRaw) ? (int)$medlnrRaw : null;
        if (!$medlnr) {
            $skipped++;
            $errors[] = "Rad {$lineNumber}: saknar giltigt medlemsnummer.";
            continue;
        }

        $payload = [
            'link' => trim((string)($row[$idxLink] ?? '')),
            'namn' => trim((string)($row[$idxNamn] ?? '')),
            'fodelsedatum' => $idxFodelsedatum === null ? '' : trim((string)($row[$idxFodelsedatum] ?? '')),
            'forening' => $idxForening === null ? '' : trim((string)($row[$idxForening] ?? '')),
            'medlemsform' => trim((string)($row[$idxMedlemsform] ?? '')),
            'befattning' => trim((string)($row[$idxBefattning] ?? '')),
            'verksamhetsform' => $idxVerksamhetsform === null ? '' : trim((string)($row[$idxVerksamhetsform] ?? '')),
            'arbetsplats' => trim((string)($row[$idxArbetsplats] ?? '')),
        ];

        try {
            $link = $payload['link'] === '' ? null : encryptValue($payload['link']);
            $namn = $payload['namn'] === '' ? null : encryptValue($payload['namn']);
            $fodelsedatum = $payload['fodelsedatum'] === '' ? null : encryptValue($payload['fodelsedatum']);
            $forening = $payload['forening'] === '' ? null : encryptValue($payload['forening']);
            $medlemsform = $payload['medlemsform'] === '' ? null : encryptValue($payload['medlemsform']);
            $befattning = $payload['befattning'] === '' ? null : encryptValue($payload['befattning']);
            $verksamhetsform = $payload['verksamhetsform'] === '' ? null : encryptValue($payload['verksamhetsform']);
            $arbetsplats = $payload['arbetsplats'] === '' ? null : encryptValue($payload['arbetsplats']);
        } catch (Throwable $e) {
            $skipped++;
            $errors[] = "Rad {$lineNumber}: " . $e->getMessage();
            continue;
        }

        $stmt->bind_param(
            "sisssssss",
            $link,
            $medlnr,
            $namn,
            $fodelsedatum,
            $forening,
            $medlemsform,
            $befattning,
            $verksamhetsform,
            $arbetsplats
        );

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            if ($affected === 1) {
                $inserted++;
            } elseif ($affected >= 2) {
                $updated++;
            }
            $seenMedlnr[] = $medlnr;
        } else {
            $skipped++;
            $errors[] = "Rad {$lineNumber}: " . $stmt->error;
        }
    }

    fclose($handle);

    // Mark members not present in this import as Inaktiv
    $seenMedlnr = array_values(array_unique($seenMedlnr));
    if (!empty($seenMedlnr)) {
        try {
            $inactiveValue = encryptValue('Inaktiv');
            $placeholders = implode(',', array_fill(0, count($seenMedlnr), '?'));
            $sql = "UPDATE tbl_members SET medlemsform = ? WHERE medlnr NOT IN ($placeholders)";
            $inactStmt = $conn->prepare($sql);
            $types = 's' . str_repeat('i', count($seenMedlnr));
            $params = array_merge([$inactiveValue], $seenMedlnr);
            $inactStmt->bind_param($types, ...$params);
            $inactStmt->execute();
            $inactivated = $inactStmt->affected_rows;
            $inactStmt->close();
        } catch (Throwable $e) {
            $errors[] = "Inaktiv-markering misslyckades: " . $e->getMessage();
        }
    }

    if (tableExists($conn, 'tbl_member_imports')) {
        $logStmt = $conn->prepare("INSERT INTO tbl_member_imports (filename, imported_by, total_rows, inserted_rows, updated_rows, skipped_rows) VALUES (?, ?, ?, ?, ?, ?)");
        $logStmt->bind_param("siiiii", $originalFileName, $userId, $total, $inserted, $updated, $skipped);
        $logStmt->execute();
        $logStmt->close();
    }

    $stmt->close();
    closeDBConnection($conn);

    return [
        'success' => true,
        'filename' => $originalFileName,
        'total' => $total,
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => $skipped,
        'inactivated' => $inactivated,
        'errors' => $errors
    ];
}
