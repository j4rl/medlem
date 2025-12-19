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

$backUrl = 'cases.php';
if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'case-edit.php') === false) {
    $backUrl = $_SERVER['HTTP_REFERER'];
}

$statusOptions = getStatusOptions();
$priorityOptions = getPriorityOptions();
$allUsers = getAllUsers();
$userContacts = [];
foreach ($allUsers as $u) {
    $userContacts[] = [
        'id' => (int)($u['id'] ?? 0),
        'name' => $u['full_name'] ?? '',
        'username' => $u['username'] ?? '',
        'email' => $u['email'] ?? '',
        'phone' => $u['phone'] ?? '',
    ];
}
$selectedAssignees = $case['handler_ids'] ?? [];
if (empty($selectedAssignees) && !empty($case['assigned_to'])) {
    $selectedAssignees = [(int)$case['assigned_to']];
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
        return (strtotime($a['changed_at'] ?? '') ?: 0) <=> (strtotime($b['changed_at'] ?? '') ?: 0);
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
    $newStatus = normalizeStatusValue($_POST['status'] ?? $case['status'], $case['status']);
    $newPriority = normalizePriorityValue($_POST['priority'] ?? $case['priority'], $case['priority']);
    $newAssignees = normalizeHandlerIds($_POST['assigned_to'] ?? []);
    $selectedAssignees = $newAssignees;
    $newAssignee = $newAssignees[0] ?? null;
    $case['status'] = $newStatus;
    $case['priority'] = $newPriority;
    $case['assigned_to'] = $newAssignee;
    $case['handler_ids'] = $newAssignees;

    // Normalize existing entries so the original body is preserved, then append the new note
    $existingEntries = normalizeEntries($caseData, $case);
    $newEntries = $existingEntries;

    if ($noteValue !== '') {
        $newEntries[] = [
            'id' => 'entry-' . (count($newEntries) + 1) . '-' . substr(sha1(uniqid('', true)), 0, 6),
            'body' => $noteValue,
            'changed_at' => date('c'),
            'author_name' => $user['full_name'] ?? ($user['username'] ?? ''),
        ];
    }

    $newCaseData = $caseData;
    $newCaseData['entries'] = $newEntries;
    $latestBody = $noteValue !== '' ? $noteValue : ($newEntries ? end($newEntries)['body'] : ($caseData['case_body'] ?? $case['description'] ?? ''));
    $newCaseData['last_edited_at'] = date('c');
    $newCaseData = buildCaseDataPayload($newCaseData, $latestBody);

    $memberData = ($memberDataRaw !== '') ? $memberDataRaw : ($case['member_data'] ?? '');

    $description = $case['description'] ?? '';

    if (updateCase($caseId, $case['title'], $description, $newStatus, $newPriority, $newAssignees, $newCaseData, $memberData)) {
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
            <form method="POST" action="">
            <div class="section-header">
                <div>
                    <p class="eyebrow"><?php echo __('edit'); ?> <?php echo __('case'); ?></p>
                    <h1><?php echo htmlspecialchars($case['case_number']); ?></h1>
                    <div class="flex gap-2" style="margin-top: 0.35rem; align-items: center; flex-wrap: wrap;">
                        <span class="badge badge-<?php echo htmlspecialchars($case['status']); ?>"><?php echo __('status_' . $case['status']); ?></span>
                        <span class="badge badge-<?php echo htmlspecialchars($case['priority']); ?>"><?php echo __('priority_' . $case['priority']); ?></span>
                    </div>
                    <p class="muted"><?php echo __('case_body'); ?> + JSON-data sparas i <code>case_data</code>.</p>
                </div>
                <div class="hero-actions">
                    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-secondary btn-sm"><?php echo __('back'); ?></a>
                </div>
            </div>

            <div class="card" style="margin-top: 0; margin-bottom: 1rem;">
                <div class="meta-grid" style="grid-template-columns: repeat(3, minmax(0,1fr)); gap: 1rem;">
                    <div><strong><?php echo __('case_number'); ?></strong><br><?php echo htmlspecialchars($case['case_number']); ?></div>
                    <div><strong><?php echo __('title'); ?></strong><br><?php echo htmlspecialchars($case['title']); ?></div>
                    <div><strong><?php echo __('created_at'); ?></strong><br><?php echo date('Y-m-d H:i', strtotime($case['created_at'])); ?></div>
                    <div>
                        <label class="form-label" for="assigned_to"><?php echo __('case_handlers'); ?></label>
                        <select id="assigned_to" name="assigned_to[]" class="form-input" multiple size="6">
                            <?php foreach ($allUsers as $assignee): ?>
                                <option value="<?php echo (int)$assignee['id']; ?>" <?php echo in_array((int)$assignee['id'], $selectedAssignees, true) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($assignee['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="muted" style="margin-top: 0.35rem;">Håll ned Ctrl/Cmd för att markera flera.</p>
                    </div>
                    <div>
                        <label class="form-label" for="status"><?php echo __('status'); ?></label>
                        <select id="status" name="status" class="form-input">
                            <?php foreach ($statusOptions as $code => $label): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $case['status'] === $code ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="muted" style="margin-top: 0.35rem;">
                            <span class="badge badge-<?php echo htmlspecialchars($case['status']); ?>"><?php echo __('status_' . $case['status']); ?></span>
                        </p>
                    </div>
                    <div>
                        <label class="form-label" for="priority"><?php echo __('priority'); ?></label>
                        <select id="priority" name="priority" class="form-input">
                            <?php foreach ($priorityOptions as $code => $label): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $case['priority'] === $code ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="muted" style="margin-top: 0.35rem;">
                            <span class="badge badge-<?php echo htmlspecialchars($case['priority']); ?>"><?php echo __('priority_' . $case['priority']); ?></span>
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
                <div class="form-group">
                    <div class="flex-between" style="align-items: center; gap: 0.5rem;">
                        <label class="form-label" for="member_data"><?php echo __('member_data'); ?></label>
                        <div class="flex gap-1">
                            <button type="button" class="btn btn-secondary btn-sm" id="memberPickerEdit">Sök medlem &amp; kopiera</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="userPickerBtn"><?php echo __('fetch_user'); ?></button>
                        </div>
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

    const userPickerBtn = document.getElementById('userPickerBtn');
    const userContacts = <?php echo json_encode($userContacts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const labels = {
        name: '<?php echo __('full_name'); ?>',
        username: '<?php echo __('username'); ?>',
        email: '<?php echo __('email'); ?>',
        phone: '<?php echo __('phone'); ?>'
    };

    const fallbackCopy = (text) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.top = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        try { document.execCommand('copy'); } catch (_) {}
        document.body.removeChild(textarea);
    };

    const copyToClipboard = (text, onDone) => {
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(onDone).catch(() => {
                fallbackCopy(text);
                onDone && onDone();
            });
        } else {
            fallbackCopy(text);
            onDone && onDone();
        }
    };

    const formatUserContact = (user) => {
        if (!user || typeof user !== 'object') return '';
        const lines = [];
        if (user.name) lines.push(`${labels.name}: ${user.name}`);
        if (user.username) lines.push(`${labels.username}: ${user.username}`);
        if (user.email) lines.push(`${labels.email}: ${user.email}`);
        if (user.phone) lines.push(`${labels.phone}: ${user.phone}`);
        return lines.join('\n');
    };

    function openUserPicker() {
        const overlay = document.createElement('div');
        overlay.className = 'member-popover-backdrop';
        overlay.innerHTML = `
            <div class="member-popover" role="dialog" aria-modal="true">
                <div class="member-popover__header">
                    <div>
                        <p class="eyebrow"><?php echo __('users'); ?></p>
                        <h3 style="margin: 0;"><?php echo __('fetch_user'); ?></h3>
                        <p class="muted" style="margin: 0;"><?php echo __('fetch_user_hint'); ?></p>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm member-popover__close" aria-label="Close">&times;</button>
                </div>
                <div class="form-group" style="margin-bottom: 0.75rem;">
                    <input type="text" class="form-input" id="userPopoverSearch" placeholder="<?php echo __('search'); ?>..." autocomplete="off">
                </div>
                <div class="member-popover__results" id="userPopoverResults">
                    <p class="muted" style="margin: 0;">Börja skriva för att söka.</p>
                </div>
            </div>
        `;

        const close = () => overlay.remove();
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close();
        });
        overlay.querySelector('.member-popover__close').addEventListener('click', close);

        const searchInput = overlay.querySelector('#userPopoverSearch');
        const resultsBox = overlay.querySelector('#userPopoverResults');

        const render = (items) => {
            resultsBox.innerHTML = '';
            if (!items || items.length === 0) {
                resultsBox.innerHTML = '<p class="muted" style="margin: 0;">Inga träffar.</p>';
                return;
            }
            items.forEach((user) => {
                const div = document.createElement('div');
                div.className = 'member-hit';

                const meta = document.createElement('div');
                meta.className = 'member-hit__meta';
                meta.innerHTML = `
                    <strong>${user.name || ''}</strong><br>
                    ${user.username ? `<div class="muted">${labels.username}: ${user.username}</div>` : ''}
                    ${user.email ? `<div class="muted">${user.email}</div>` : ''}
                    ${user.phone ? `<div class="muted">${user.phone}</div>` : ''}
                `;

                const checks = document.createElement('div');
                checks.className = 'member-hit__checks';
                const fields = [
                    { key: 'name', label: labels.name, value: user.name },
                    { key: 'username', label: labels.username, value: user.username },
                    { key: 'email', label: labels.email, value: user.email },
                    { key: 'phone', label: labels.phone, value: user.phone },
                ].filter(f => f.value && f.value !== '');

                fields.forEach((f) => {
                    const id = `user-chk-${f.key}-${Math.random().toString(36).slice(2)}`;
                    const label = document.createElement('label');
                    label.className = 'member-hit__check';
                    label.innerHTML = `<input type="checkbox" data-value="${f.value}" id="${id}"> ${f.label}`;
                    checks.appendChild(label);
                });

                const actions = document.createElement('div');
                actions.className = 'member-hit__actions';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-primary btn-sm';
                btn.textContent = 'Kopiera';
                btn.addEventListener('click', () => {
                    const checked = Array.from(checks.querySelectorAll('input[type="checkbox"]:checked')).map(c => c.dataset.value || '').filter(Boolean);
                    const text = checked.length > 0 ? checked.join('\n') : formatUserContact(user);
                    if (!text) return;
                    copyToClipboard(text, () => {
                        const original = btn.textContent;
                        btn.textContent = 'Kopierat';
                        btn.disabled = true;
                        setTimeout(() => {
                            btn.disabled = false;
                            btn.textContent = original;
                        }, 900);
                    });
                });
                actions.appendChild(btn);

                div.appendChild(meta);
                if (fields.length > 0) {
                    div.appendChild(checks);
                }
                div.appendChild(actions);
                resultsBox.appendChild(div);
            });
        };

        searchInput.addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase();
            if (!q || q.length < 1) {
                resultsBox.innerHTML = '<p class="muted" style="margin: 0;">Börja skriva för att söka.</p>';
                return;
            }
            const hits = userContacts.filter((u) => {
                return [u.name, u.email, u.phone].some((val) => (val || '').toLowerCase().includes(q));
            });
            render(hits.slice(0, 50));
        });

        document.body.appendChild(overlay);
        setTimeout(() => searchInput.focus(), 50);
    }

    if (userPickerBtn) {
        userPickerBtn.addEventListener('click', openUserPicker);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
