<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$caseId = $_GET['id'] ?? 0;
$case = getCaseById($caseId);

if (!$case) {
    header('Location: cases.php');
    exit();
}

function normalizeEntries(array $caseData, array $case): array {
    $entries = [];
    $source = $caseData['entries'] ?? ($caseData['sections'] ?? []);
    if (is_array($source)) {
        foreach ($source as $idx => $entry) {
            if (!is_array($entry)) continue;
            $entries[] = [
                'id' => $entry['id'] ?? 'entry-' . ($idx + 1),
                'body' => (string)($entry['body'] ?? $entry['text'] ?? $entry['case_body'] ?? ''),
                'changed_at' => $entry['changed_at'] ?? $entry['updated_at'] ?? $entry['date'] ?? $entry['last_edited_at'] ?? date('c'),
                'author_name' => $entry['author_name'] ?? $entry['actor'] ?? $entry['user'] ?? ($case['assignee_name'] ?? $case['creator_name'] ?? ''),
            ];
        }
    }

    if (empty($entries)) {
        $entries[] = [
            'id' => 'entry-1',
            'body' => (string)($caseData['case_body'] ?? $case['description'] ?? ''),
            'changed_at' => $caseData['last_edited_at'] ?? $case['updated_at'] ?? date('c'),
            'author_name' => $case['assignee_name'] ?? $case['creator_name'] ?? '',
        ];
    }

    usort($entries, function ($a, $b) {
        return (strtotime($b['changed_at'] ?? '') ?: 0) <=> (strtotime($a['changed_at'] ?? '') ?: 0);
    });

    return array_values($entries);
}

function formatIso(?string $value): string {
    $ts = $value ? strtotime($value) : false;
    if ($ts === false) return date('Y-m-d\TH:i');
    return date('Y-m-d\TH:i', $ts);
}

$caseData = [];
if (!empty($case['case_data'])) {
    $decoded = json_decode($case['case_data'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $caseData = $decoded;
    }
}

$entries = normalizeEntries($caseData, $case);
$memberDataDisplay = $_POST['member_data'] ?? '';
if ($memberDataDisplay === '' && isset($case['member_data'])) {
    $memberDataDisplay = is_string($case['member_data']) ? $case['member_data'] : '';
}

$noteValue = '';
$error = '';
$success = isset($_GET['updated']) ? __('status_in_progress') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noteValue = trim($_POST['case_note'] ?? '');
    $memberDataRaw = $_POST['member_data'] ?? '';

    $newCaseData = $caseData;
    if (!isset($newCaseData['entries']) || !is_array($newCaseData['entries'])) {
        $newCaseData['entries'] = [];
    }

    if ($noteValue !== '') {
        $newCaseData['entries'][] = [
            'id' => 'entry-' . (count($newCaseData['entries']) + 1) . '-' . substr(sha1(uniqid('', true)), 0, 6),
            'body' => $noteValue,
            'changed_at' => date('c'),
            'author_name' => $user['full_name'] ?? ($user['username'] ?? ''),
        ];
    }

    $newCaseData = buildCaseDataPayload($newCaseData, $noteValue ?: ($caseData['case_body'] ?? $case['description'] ?? ''));

    $memberData = ($memberDataRaw !== '') ? $memberDataRaw : ($case['member_data'] ?? '');

    $description = $case['description'] ?? '';

    if (updateCase($caseId, $case['title'], $description, $case['status'], $case['priority'], $case['assigned_to'], $newCaseData, $memberData)) {
        header('Location: case-edit.php?id=' . $caseId . '&updated=1');
        exit();
    } else {
        $error = __('error_general');
    }
}

// Refresh entries for display after potential append
$entries = normalizeEntries($newCaseData ?? $caseData, $case);

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="card mt-3 case-builder">
            <div class="section-header">
                <div>
                    <p class="eyebrow"><?php echo __('edit'); ?> <?php echo __('case'); ?></p>
                    <h1><?php echo htmlspecialchars($case['case_number']); ?></h1>
                    <p class="muted"><?php echo __('case_body'); ?> + JSON-data sparas i <code>case_data</code>.</p>
                </div>
                <div class="hero-actions">
                    <a href="case-edit.php?id=<?php echo $caseId; ?>" class="btn btn-secondary btn-sm"><?php echo __('back'); ?></a>
                </div>
            </div>

            <div class="card" style="margin-top: 0; margin-bottom: 1rem;">
                <div class="meta-grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
                    <div><strong><?php echo __('case_number'); ?></strong><br><?php echo htmlspecialchars($case['case_number']); ?></div>
                    <div><strong><?php echo __('title'); ?></strong><br><?php echo htmlspecialchars($case['title']); ?></div>
                    <div><strong><?php echo __('created_at'); ?></strong><br><?php echo date('Y-m-d H:i', strtotime($case['created_at'])); ?></div>
                    <div><strong><?php echo __('assigned_to'); ?></strong><br><?php echo htmlspecialchars($case['assignee_name'] ?? '-'); ?></div>
                    <div><strong><?php echo __('status'); ?></strong><br><?php echo __('status_' . $case['status']); ?></div>
                    <div><strong><?php echo __('priority'); ?></strong><br><?php echo __('priority_' . $case['priority']); ?></div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <div class="flex-between" style="align-items: center; gap: 0.5rem;">
                        <label class="form-label" for="member_data"><?php echo __('member_data'); ?></label>
                        <button type="button" class="btn btn-secondary btn-sm" id="memberPickerEdit">Sök medlem &amp; kopiera</button>
                    </div>
                    <textarea id="member_data" name="member_data" class="form-textarea" rows="3" placeholder="<?php echo __('member_data'); ?>..." spellcheck="false"><?php echo htmlspecialchars($memberDataDisplay); ?></textarea>
                    <p class="muted" style="margin-top: 0.35rem;">Sparas som fri text.</p>
                </div>

                <div class="card" style="margin-top: 1rem;">
                    <div class="section-header" style="margin-bottom: 0.5rem;">
                        <div>
                            <h3 style="margin: 0;"><?php echo __('case_body'); ?> (<?php echo __('history'); ?>)</h3>
                        </div>
                    </div>
                    <div class="case-list" style="gap: 0.75rem; display: flex; flex-direction: column;">
                        <?php foreach ($entries as $entry): ?>
                            <div class="case-row">
                                <div style="width: 100%;">
                                    <p class="muted" style="margin: 0; font-weight: 600;">
                                        <?php echo date('Y-m-d H:i', strtotime($entry['changed_at'])); ?> &bull; <?php echo htmlspecialchars($entry['author_name'] ?? ''); ?>
                                    </p>
                                    <p class="muted" style="width: 100%; margin-top: 0.5rem; white-space: pre-wrap;"><?php echo htmlspecialchars($entry['body']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($entries)): ?>
                            <p class="muted"><?php echo __('no_cases'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card card-accent" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="case_note"><?php echo __('case_body'); ?></label>
                        <textarea id="case_note" name="case_note" class="form-textarea tall" placeholder="<?php echo __('case_body'); ?>"></textarea>
                        <p class="muted" style="margin-top: 0.25rem;"><?php echo __('status_in_progress'); ?> <?php echo __('case_body'); ?></p>
                    </div>
                </div>

                <div class="flex gap-2" style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
                    <a href="case-edit.php?id=<?php echo $caseId; ?>" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                    <button type="submit" name="delete" class="btn btn-danger"
                            onclick="return confirmDelete('<?php echo __('confirm_delete_case'); ?>');">
                        <?php echo __('delete'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
window.addEventListener('load', () => {
    if (window.initMemberPicker) {
        window.initMemberPicker('#memberPickerEdit');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
