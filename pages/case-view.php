<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$caseId = $_GET['id'] ?? 0;
$case = getCaseById($caseId);

function normalizeCaseEntriesView(array $caseData, array $case): array {
    $entries = [];
    $source = null;
    if (isset($caseData['entries']) && is_array($caseData['entries'])) {
        $source = $caseData['entries'];
    } elseif (isset($caseData['sections']) && is_array($caseData['sections'])) {
        $source = $caseData['sections'];
    }

    if (is_array($source)) {
        foreach ($source as $idx => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $entries[] = [
                'id' => $entry['id'] ?? 'entry-' . ($idx + 1),
                'title' => $entry['title'] ?? ($entry['header'] ?? ($case['title'] ?? 'Anteckning')),
                'body' => (string)($entry['body'] ?? $entry['text'] ?? $entry['case_body'] ?? ''),
                'changed_at' => $entry['changed_at'] ?? $entry['date'] ?? $entry['last_edited_at'] ?? ($caseData['last_edited_at'] ?? $case['updated_at'] ?? date('c')),
                'actor' => $entry['actor'] ?? $case['assignee_name'] ?? $case['creator_name'] ?? '',
            ];
        }
    }

    if (empty($entries) && !empty($caseData)) {
        $entries[] = [
            'id' => 'entry-1',
            'title' => $case['title'] ?? 'Anteckning',
            'body' => (string)($caseData['case_body'] ?? $case['description'] ?? ''),
            'changed_at' => $caseData['last_edited_at'] ?? $case['updated_at'] ?? date('c'),
            'actor' => $case['assignee_name'] ?? $case['creator_name'] ?? '',
        ];
    }

    if (empty($entries)) {
        $entries[] = [
            'id' => 'entry-1',
            'title' => $case['title'] ?? 'Anteckning',
            'body' => (string)($case['description'] ?? ''),
            'changed_at' => $case['updated_at'] ?? date('c'),
            'actor' => $case['assignee_name'] ?? $case['creator_name'] ?? '',
        ];
    }

    usort($entries, function ($a, $b) {
        $timeA = strtotime($a['changed_at'] ?? '') ?: 0;
        $timeB = strtotime($b['changed_at'] ?? '') ?: 0;
        return $timeB <=> $timeA;
    });

    return array_values($entries);
}

if (!$case) {
    header('Location: cases.php');
    exit();
}

$caseData = [];
if (!empty($case['case_data'])) {
    $decoded = json_decode($case['case_data'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $caseData = $decoded;
    }
}
$caseEntries = normalizeCaseEntriesView($caseData, $case);
$caseMeta = $caseData;
unset($caseMeta['entries'], $caseMeta['sections']);
$memberDataPretty = '';
if (!empty($case['member_data'])) {
    $decodedMember = json_decode($case['member_data'], true);
    $memberDataPretty = json_last_error() === JSON_ERROR_NONE
        ? json_encode($decodedMember, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $case['member_data'];
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="card" style="padding: 1.5rem;">
            <div class="flex-between mb-2" style="align-items: flex-start;">
                <div>
                    <p class="eyebrow" style="margin-bottom: 0.35rem;">Ärendehantering</p>
                    <h1 style="margin: 0;"><?php echo htmlspecialchars($case['title']); ?></h1>
                </div>
                <div class="flex gap-2">
                    <a href="case-edit.php?id=<?php echo $case['id']; ?>" class="btn btn-secondary btn-sm">
                        <?php echo __('edit'); ?>
                    </a>
                    <a href="cases.php" class="btn btn-secondary btn-sm">
                        <?php echo __('back'); ?>
                    </a>
                </div>
            </div>

            <div class="grid grid-2" style="gap: 1rem; margin-bottom: 1.25rem;">
                <div class="meta-grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
                    <div>
                        <strong>Ärendenummer</strong><br>
                        <?php echo htmlspecialchars($case['case_number']); ?>
                    </div>
                    <div>
                        <strong>Rubrik</strong><br>
                        <?php echo htmlspecialchars($case['title']); ?>
                    </div>
                    <div>
                        <strong>Mottaget</strong><br>
                        <?php echo date('Ymd H:i', strtotime($case['created_at'])); ?> <?php echo htmlspecialchars($case['creator_name'] ?? ''); ?>
                    </div>
                    <div>
                        <strong>Prioritet</strong><br>
                        <?php echo __('priority_' . $case['priority']); ?>
                    </div>
                    <div>
                        <strong>Senast ändrad</strong><br>
                        <?php echo date('Ymd H:i', strtotime($case['updated_at'])); ?> <?php echo htmlspecialchars($case['assignee_name'] ?? $case['creator_name'] ?? ''); ?>
                    </div>
                    <div>
                        <strong>Status</strong><br>
                        <?php echo __('status_' . $case['status']); ?>
                    </div>
                    <div style="grid-column: span 2;">
                        <strong>Handläggare</strong><br>
                        <?php echo htmlspecialchars($case['assignee_name'] ?? '-'); ?>
                    </div>
                </div>

                <div class="card" style="margin: 0; background: var(--surface-alt, #f8f8f8);">
                    <div class="flex-between" style="align-items: center; margin-bottom: 0.5rem;">
                        <h3 style="margin: 0;"><?php echo __('member_data'); ?></h3>
                        <a class="btn btn-secondary btn-sm" href="case-edit.php?id=<?php echo $case['id']; ?>#member_data"><?php echo __('edit'); ?></a>
                    </div>
                    <?php if ($memberDataPretty): ?>
                        <pre class="code-block" style="margin: 0;"><?php echo htmlspecialchars($memberDataPretty); ?></pre>
                    <?php else: ?>
                        <p class="muted" style="margin: 0;"><?php echo __('no_cases'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="margin: 0; background: #e0e0e0;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid rgba(0,0,0,0.08);">
                    <strong>Ärende</strong>
                </div>
                <div style="padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($caseEntries as $entry): ?>
                        <div style="background: white; padding: 0.75rem 0.85rem; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
                            <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.35rem;">
                                <?php echo date('Ymd H:i', strtotime($entry['changed_at'])); ?> <?php echo htmlspecialchars($entry['actor']); ?>
                            </div>
                            <div style="color: #444; white-space: pre-wrap;"><?php echo htmlspecialchars($entry['body']); ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($caseEntries)): ?>
                        <p class="muted"><?php echo __('no_cases'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
