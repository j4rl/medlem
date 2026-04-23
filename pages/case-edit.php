<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$caseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!userCanAccessCaseId($caseId, (int)$user['id'], userHasAdminAccess($user))) {
    http_response_code(403);
    exit('Access denied.');
}

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

function normalizeEntries(array $caseData, array $case): array {
    $entries = [];
    $source = $caseData['entries'] ?? ($caseData['sections'] ?? []);
    if (is_array($source)) {
        foreach ($source as $idx => $entry) {
            if (!is_array($entry)) {
                continue;
            }
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

function decodeCaseData(array $case): array {
    if (empty($case['case_data'])) {
        return [];
    }
    $decoded = json_decode($case['case_data'], true);
    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
}

$caseData = decodeCaseData($case);
$entries = normalizeEntries($caseData, $case);
$memberDataDisplay = is_string($case['member_data'] ?? null) ? $case['member_data'] : '';
$selectedAssignees = $case['handler_ids'] ?? [];
if (empty($selectedAssignees) && !empty($case['assigned_to'])) {
    $selectedAssignees = [(int)$case['assigned_to']];
}

$error = '';
$success = '';
if (isset($_GET['updated'])) {
    $success = __('case_saved');
} elseif (isset($_GET['note'])) {
    $success = __('case_note_added');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_meta';

    if ($action === 'delete') {
        if (deleteCase($caseId)) {
            header('Location: cases.php?deleted=1');
            exit();
        }
        $error = __('error_general');
    } elseif ($action === 'add_note') {
        $noteValue = sanitizeRichTextHtml($_POST['case_note'] ?? '');
        if (richTextContentIsEmpty($noteValue)) {
            $error = __('error_required');
        } else {
            $newEntries = $entries;
            $newEntries[] = [
                'id' => 'entry-' . (count($newEntries) + 1) . '-' . substr(sha1(uniqid('', true)), 0, 6),
                'body' => $noteValue,
                'changed_at' => date('c'),
                'author_name' => $user['full_name'] ?? ($user['username'] ?? ''),
            ];

            $newCaseData = $caseData;
            $newCaseData['entries'] = $newEntries;
            $newCaseData['last_edited_at'] = date('c');
            $newCaseData = buildCaseDataPayload($newCaseData, $noteValue);

            if (updateCase($caseId, $case['title'], $case['description'] ?? '', $case['status'], $case['priority'], $selectedAssignees, $newCaseData, $memberDataDisplay)) {
                header('Location: case-edit.php?id=' . $caseId . '&note=1');
                exit();
            }
            $error = __('error_general');
        }
    } else {
        $memberDataDisplay = $_POST['member_data'] ?? '';
        $newStatus = normalizeStatusValue($_POST['status'] ?? $case['status'], $case['status']);
        $newPriority = normalizePriorityValue($_POST['priority'] ?? $case['priority'], $case['priority']);
        $selectedAssignees = normalizeHandlerIds($_POST['assigned_to'] ?? []);

        if (updateCase($caseId, $case['title'], $case['description'] ?? '', $newStatus, $newPriority, $selectedAssignees, $caseData, $memberDataDisplay)) {
            header('Location: case-edit.php?id=' . $caseId . '&updated=1');
            exit();
        }
        $error = __('error_general');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex-between mb-3" style="align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
            <div>
                <p class="eyebrow"><?php echo __('edit'); ?> <?php echo __('case'); ?></p>
                <h1><?php echo htmlspecialchars($case['case_number']); ?></h1>
                <div class="flex gap-1" style="margin-top: 0.5rem; flex-wrap: wrap;">
                    <?php echo renderCaseIndicators($case); ?>
                </div>
            </div>
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-secondary btn-sm"><?php echo __('back'); ?></a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="case-layout">
            <div>
                <section class="case-section">
                    <h2><?php echo htmlspecialchars($case['title']); ?></h2>
                    <div class="meta-grid">
                        <div><strong><?php echo __('case_number'); ?></strong><br><?php echo htmlspecialchars($case['case_number']); ?></div>
                        <div><strong><?php echo __('created_by'); ?></strong><br><?php echo htmlspecialchars($case['creator_name'] ?? ''); ?></div>
                        <div><strong><?php echo __('created_at'); ?></strong><br><?php echo date('Y-m-d H:i', strtotime($case['created_at'])); ?></div>
                        <div><strong><?php echo __('updated_at'); ?></strong><br><?php echo date('Y-m-d H:i', strtotime($case['updated_at'] ?? $case['created_at'])); ?></div>
                    </div>
                </section>

                <section class="case-section">
                    <div class="section-header">
                        <h2><?php echo __('case_history_section'); ?></h2>
                        <span class="muted"><?php echo __('latest_activity'); ?></span>
                    </div>
                    <div class="history-list">
                        <?php foreach ($entries as $entry): ?>
                            <article class="history-entry">
                                <div class="history-entry__meta">
                                    <?php echo date('Y-m-d H:i', strtotime($entry['changed_at'])); ?> &bull; <?php echo htmlspecialchars($entry['author_name'] ?? ''); ?>
                                </div>
                                <div class="history-entry__body"><?php echo renderRichTextContent($entry['body']); ?></div>
                            </article>
                        <?php endforeach; ?>
                        <?php if (empty($entries)): ?>
                            <p class="muted"><?php echo __('no_cases'); ?></p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="case-section">
                    <h2><?php echo __('case_note_section'); ?></h2>
                    <form method="POST" action="">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="add_note">
                        <div class="form-group">
                            <label class="form-label" for="case_note"><?php echo __('add_note'); ?></label>
                            <textarea id="case_note" name="case_note" class="form-textarea tall" data-rich-text="true" placeholder="<?php echo htmlspecialchars(__('case_note_placeholder')); ?>"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo __('add_note'); ?></button>
                    </form>
                </section>
            </div>

            <aside class="case-sidebar">
                <section class="case-section">
                    <h2><?php echo __('case_meta_section'); ?></h2>
                    <form method="POST" action="">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="save_meta">

                        <div class="form-group">
                            <label class="form-label" for="status"><?php echo __('status'); ?></label>
                            <select id="status" name="status" class="form-input">
                                <?php foreach ($statusOptions as $code => $label): ?>
                                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $case['status'] === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="priority"><?php echo __('priority'); ?></label>
                            <select id="priority" name="priority" class="form-input">
                                <?php foreach ($priorityOptions as $code => $label): ?>
                                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $case['priority'] === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="assigned_to"><?php echo __('case_handlers'); ?></label>
                            <select
                                id="assigned_to"
                                name="assigned_to[]"
                                class="form-input"
                                multiple
                                data-enhance="multi-select"
                                data-search-label="<?php echo htmlspecialchars(__('search_handlers')); ?>"
                                data-empty-label="<?php echo htmlspecialchars(__('no_handler_selected')); ?>"
                                data-clear-label="<?php echo htmlspecialchars(__('clear')); ?>">
                                <?php foreach ($allUsers as $assignee): ?>
                                    <option value="<?php echo (int)$assignee['id']; ?>" <?php echo in_array((int)$assignee['id'], $selectedAssignees, true) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($assignee['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="flex-between" style="align-items: center; gap: 0.5rem;">
                                <label class="form-label" for="member_data" style="margin: 0;"><?php echo __('member_data'); ?></label>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-secondary btn-sm" id="memberPickerEdit"><?php echo __('fetch_member'); ?></button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="userPickerBtn"><?php echo __('fetch_user'); ?></button>
                                </div>
                            </div>
                            <textarea id="member_data" name="member_data" class="form-textarea" rows="4" placeholder="<?php echo __('member_data'); ?>..." spellcheck="false"><?php echo htmlspecialchars($memberDataDisplay); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary"><?php echo __('save_status'); ?></button>
                    </form>
                </section>

                <section class="case-section danger-zone">
                    <h3><?php echo __('delete'); ?></h3>
                    <form method="POST" action="" onsubmit="return confirmDelete('<?php echo __('confirm_delete_case'); ?>');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger"><?php echo __('delete'); ?></button>
                    </form>
                </section>
            </aside>
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
                const strong = document.createElement('strong');
                strong.textContent = user.name || '';
                meta.appendChild(strong);
                ['username', 'email', 'phone'].forEach((key) => {
                    if (!user[key]) return;
                    const line = document.createElement('div');
                    line.className = 'muted';
                    line.textContent = key === 'username' ? `${labels.username}: ${user[key]}` : user[key];
                    meta.appendChild(line);
                });

                const actions = document.createElement('div');
                actions.className = 'member-hit__actions';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-primary btn-sm';
                btn.textContent = 'Kopiera';
                btn.addEventListener('click', () => {
                    copyToClipboard(formatUserContact(user), () => {
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
            const hits = userContacts.filter((u) => [u.name, u.email, u.phone].some((val) => (val || '').toLowerCase().includes(q)));
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
