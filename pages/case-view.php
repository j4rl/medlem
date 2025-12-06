<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$caseId = $_GET['id'] ?? 0;
$case = getCaseById($caseId);
$statusOptions = ['no_action', 'in_progress', 'resolved', 'closed'];
$priorityOptions = ['low', 'medium', 'high', 'urgent'];
$updateError = '';
$updateSuccess = isset($_GET['updated']) ? 'Changes saved.' : '';
$noteValue = '';

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

// Handle updates for status, priority, and notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = normalizeStatusValue($_POST['status'] ?? $case['status']);
    $newPriority = normalizePriorityValue($_POST['priority'] ?? $case['priority']);
    $noteValue = trim($_POST['note'] ?? '');

    // Normalize existing case data to array
    $updatedCaseData = is_array($caseData) ? $caseData : [];
    if (isset($updatedCaseData['sections']) && !isset($updatedCaseData['entries'])) {
        $updatedCaseData['entries'] = $updatedCaseData['sections'];
        unset($updatedCaseData['sections']);
    }

    if ($noteValue !== '') {
        if (!isset($updatedCaseData['entries']) || !is_array($updatedCaseData['entries'])) {
            $updatedCaseData['entries'] = [];
        }
        $updatedCaseData['entries'][] = [
            'id' => 'entry-' . (count($updatedCaseData['entries']) + 1),
            'title' => $case['title'] ?? 'Anteckning',
            'body' => $noteValue,
            'changed_at' => date('c'),
            'actor' => $user['full_name'] ?? ($user['username'] ?? ''),
        ];
    }

    $memberDataRaw = $case['member_data'] ?? null;
    $description = $case['description'] ?? '';
    $updatedCaseData = buildCaseDataPayload($updatedCaseData, $description);

    if (updateCase($caseId, $case['title'], $description, $newStatus, $newPriority, $case['assigned_to'], $updatedCaseData, $memberDataRaw)) {
        header('Location: case-view.php?id=' . $caseId . '&updated=1');
        exit();
    } else {
        $updateError = __('error_general');
        $case['status'] = $newStatus;
        $case['priority'] = $newPriority;
        $caseData = $updatedCaseData;
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
            <?php if ($updateSuccess): ?>
                <div class="alert alert-success" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($updateSuccess); ?></div>
            <?php endif; ?>
            <?php if ($updateError): ?>
                <div class="alert alert-error" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($updateError); ?></div>
            <?php endif; ?>
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

            <div class="card" style="margin: 0 0 1rem 0; position: relative; overflow: visible;">
                <form id="case-update-form" method="post">
                    <input type="hidden" name="meta_confirmed" id="meta-confirmed" value="0">
                    <div class="grid grid-3" style="gap: 1rem; align-items: flex-end;">
                        <div>
                            <label for="case-status" class="form-label"><?php echo __('status'); ?></label>
                            <select id="case-status" name="status" class="form-select" data-original-status="<?php echo htmlspecialchars($case['status']); ?>">
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $case['status'] === $status ? 'selected' : ''; ?>>
                                        <?php echo __('status_' . $status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="case-priority" class="form-label"><?php echo __('priority'); ?></label>
                            <select id="case-priority" name="priority" class="form-select" data-original-priority="<?php echo htmlspecialchars($case['priority']); ?>">
                                <?php foreach ($priorityOptions as $priority): ?>
                                    <option value="<?php echo $priority; ?>" <?php echo $case['priority'] === $priority ? 'selected' : ''; ?>>
                                        <?php echo __('priority_' . $priority); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="grid-column: span 3;">
                            <label for="case-note" class="form-label"><?php echo __('case_body'); ?></label>
                            <textarea id="case-note" name="note" class="form-textarea" rows="3" placeholder="<?php echo __('case_body'); ?>" style="width: 100%;"><?php echo htmlspecialchars($noteValue); ?></textarea>
                            <p class="muted" style="margin-top: 0.35rem; font-size: 0.9rem;">New notes are saved with a timestamp.</p>
                        </div>
                    </div>
                    <div class="flex-between" style="margin-top: 0.75rem;">
                        <div class="muted" style="font-size: 0.9rem;">Status &amp; priority changes need confirmation.</div>
                        <div class="flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm"><?php echo __('save'); ?></button>
                            <a href="case-view.php?id=<?php echo $caseId; ?>" class="btn btn-secondary btn-sm"><?php echo __('cancel'); ?></a>
                        </div>
                    </div>
                </form>
                <div id="change-popover" style="display: none; position: absolute; right: 1rem; top: -0.5rem; background: var(--surface); border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 0.9rem; border-radius: 0.5rem; z-index: 20; width: min(320px, 90%);">
                    <p style="margin: 0 0 0.75rem 0; font-weight: 600;">Confirm status/priority changes?</p>
                    <div class="flex gap-1">
                        <button id="confirm-change" class="btn btn-primary btn-sm" type="button"><?php echo __('save'); ?></button>
                        <button id="cancel-change" class="btn btn-secondary btn-sm" type="button"><?php echo __('cancel'); ?></button>
                    </div>
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

<script>
(function() {
    const form = document.getElementById('case-update-form');
    if (!form) return;

    const statusSelect = document.getElementById('case-status');
    const prioritySelect = document.getElementById('case-priority');
    const popover = document.getElementById('change-popover');
    const confirmBtn = document.getElementById('confirm-change');
    const cancelBtn = document.getElementById('cancel-change');
    const originalStatus = statusSelect ? statusSelect.dataset.originalStatus : '';
    const originalPriority = prioritySelect ? prioritySelect.dataset.originalPriority : '';
    const metaConfirmed = document.getElementById('meta-confirmed');
    let popoverVisible = false;

    const hasMetaChange = () => {
        return (statusSelect && statusSelect.value !== originalStatus) ||
               (prioritySelect && prioritySelect.value !== originalPriority);
    };

    const togglePopover = (show) => {
        if (!popover) return;
        popover.style.display = show ? 'flex' : 'none';
        popover.style.flexDirection = 'column';
        popoverVisible = show;
    };

    const resetMeta = () => {
        if (statusSelect) statusSelect.value = originalStatus;
        if (prioritySelect) prioritySelect.value = originalPriority;
        if (metaConfirmed) metaConfirmed.value = '0';
        togglePopover(false);
    };

    if (statusSelect) {
        statusSelect.addEventListener('change', () => togglePopover(hasMetaChange()));
    }
    if (prioritySelect) {
        prioritySelect.addEventListener('change', () => togglePopover(hasMetaChange()));
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            resetMeta();
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (metaConfirmed) metaConfirmed.value = '1';
            togglePopover(false);
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }

    form.addEventListener('submit', (e) => {
        if (hasMetaChange() && (!metaConfirmed || metaConfirmed.value !== '1')) {
            if (popoverVisible) {
                // Second submit attempt: treat as confirmed
                if (metaConfirmed) metaConfirmed.value = '1';
                togglePopover(false);
                return;
            }
            e.preventDefault();
            togglePopover(true);
            if (metaConfirmed) metaConfirmed.value = '0';
        }
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
