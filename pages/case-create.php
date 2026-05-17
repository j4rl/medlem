<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$users = getAllUsers();
$error = '';
$success = '';
$now = date('Y-m-d\TH:i');
// Default taker is the current user for faster handoff
$defaultAssignee = $user['id'] ?? null;
$selectedAssignees = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedAssignees = normalizeHandlerIds($_POST['assigned_to'] ?? []);
} elseif ($defaultAssignee) {
    $selectedAssignees = [$defaultAssignee];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $caseBody = sanitizeRichTextHtml($_POST['case_body'] ?? '');
    $description = $caseBody; // keep legacy field populated
    $priority = $_POST['priority'] ?? 'medium';
    $assignedTo = normalizeHandlerIds($_POST['assigned_to'] ?? []);
    $receivedAt = $_POST['received_at'] ?? '';
    $recipient = $_POST['recipient'] ?? '';
    $handlerLabel = $_POST['handler'] ?? '';
    $memberLookup = $_POST['member_lookup'] ?? '';
    $memberDataRaw = $_POST['member_data'] ?? '';
    $caseDataRaw = $_POST['case_data'] ?? '';
    
    if (empty($title) || richTextContentIsEmpty($description)) {
        $error = __('error_required');
    } else {
        $caseData = [];
        if (!empty($caseDataRaw)) {
            $decoded = json_decode($caseDataRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $caseData = $decoded;
            }
        }

        $caseData = buildCaseDataPayload($caseData, $caseBody, [
            'received_at' => $receivedAt,
            'recipient' => $recipient,
            'handler' => $handlerLabel,
            'member_lookup' => $memberLookup,
        ]);

        $memberData = $memberDataRaw !== '' ? $memberDataRaw : null;

        $result = createCase($title, $description, $priority, $user['id'], $assignedTo, $caseData, $memberData);
        if ($result['success']) {
            header('Location: case-edit.php?id=' . $result['case_id']);
            exit();
        } else {
            $error = __('error_general');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="card mt-3 case-builder">
            <div class="section-header">
                <div>
                    <p class="eyebrow"><?php echo __('create_case'); ?></p>
                    <h1><?php echo __('case_create_heading'); ?></h1>
                    <p class="muted"><?php echo __('case_create_hint'); ?></p>
                </div>
                <div class="hero-actions">
                    <a href="cases.php" class="btn btn-secondary btn-sm"><?php echo __('cancel'); ?></a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="caseForm">
                <?php echo csrfField(); ?>
                <section class="case-section">
                    <div class="section-header">
                        <div>
                            <h2><?php echo __('case_member_section'); ?></h2>
                            <p class="muted"><?php echo __('member_lookup_hint'); ?></p>
                        </div>
                    </div>

                    <div class="member-linker">
                        <div class="member-linker__search">
                            <label class="form-label" for="member_lookup"><?php echo __('member_search_input'); ?></label>
                            <div class="flex gap-1 member-linker__search-row">
                                <input type="search" id="member_lookup" name="member_lookup" class="form-input"
                                       placeholder="<?php echo htmlspecialchars(__('member_search_placeholder')); ?>"
                                       value="<?php echo htmlspecialchars($_POST['member_lookup'] ?? ''); ?>"
                                       autocomplete="off">
                                <button type="button" id="memberSearchBtn" class="btn btn-secondary"><?php echo __('search'); ?></button>
                            </div>
                            <div id="memberSearchResults" class="member-linker__results" aria-live="polite">
                                <p class="muted"><?php echo __('member_search_min_chars'); ?></p>
                            </div>
                        </div>

                        <div id="selectedMemberPanel" class="member-linker__selected <?php echo empty($_POST['member_data'] ?? '') ? 'is-empty' : ''; ?>">
                            <p class="eyebrow"><?php echo __('linked_member'); ?></p>
                            <div id="selectedMemberSummary" class="member-linker__summary">
                                <?php if (!empty($_POST['member_data'])): ?>
                                    <pre><?php echo htmlspecialchars($_POST['member_data']); ?></pre>
                                <?php else: ?>
                                    <p class="muted"><?php echo __('no_data'); ?></p>
                                <?php endif; ?>
                            </div>
                            <p class="muted"><?php echo __('member_selected_hint'); ?></p>
                            <label class="form-label" for="member_data"><?php echo __('edit_member_snapshot'); ?></label>
                            <textarea id="member_data" name="member_data" class="form-textarea" rows="5" placeholder="<?php echo __('member_data'); ?>..." spellcheck="false"><?php echo htmlspecialchars($_POST['member_data'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="case-section">
                    <h2><?php echo __('case_details_section'); ?></h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="title"><?php echo __('title'); ?> *</label>
                            <input type="text" id="title" name="title" class="form-input" required autofocus
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="received_at"><?php echo __('received_at'); ?></label>
                            <input type="datetime-local" id="received_at" name="received_at" class="form-input"
                                   value="<?php echo htmlspecialchars($_POST['received_at'] ?? $now); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="case_body"><?php echo __('case_body'); ?> *</label>
                        <textarea id="case_body" name="case_body" class="form-textarea tall" data-rich-text="true" required><?php echo htmlspecialchars(sanitizeRichTextHtml($_POST['case_body'] ?? '')); ?></textarea>
                    </div>
                </section>

                <section class="case-section">
                    <h2><?php echo __('case_assignment_section'); ?></h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="recipient"><?php echo __('recipient'); ?></label>
                            <input type="text" id="recipient" name="recipient" class="form-input"
                                   value="<?php echo htmlspecialchars($_POST['recipient'] ?? ($user['full_name'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="priority"><?php echo __('priority'); ?></label>
                            <select id="priority" name="priority" class="form-select">
                                <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>
                                    <?php echo __('priority_low'); ?>
                                </option>
                                <option value="medium" <?php echo ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>
                                    <?php echo __('priority_medium'); ?>
                                </option>
                                <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>
                                    <?php echo __('priority_high'); ?>
                                </option>
                                <option value="urgent" <?php echo ($_POST['priority'] ?? '') === 'urgent' ? 'selected' : ''; ?>>
                                    <?php echo __('priority_urgent'); ?>
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="assigned_to"><?php echo __('case_handlers'); ?></label>
                        <select
                            id="assigned_to"
                            name="assigned_to[]"
                            class="form-select"
                            multiple
                            data-enhance="multi-select"
                            data-search-label="<?php echo htmlspecialchars(__('search_handlers')); ?>"
                            data-empty-label="<?php echo htmlspecialchars(__('no_handler_selected')); ?>"
                            data-clear-label="<?php echo htmlspecialchars(__('clear')); ?>">
                            <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php
                                echo in_array((int)$u['id'], $selectedAssignees, true) ? 'selected' : '';
                            ?>>
                                <?php echo htmlspecialchars($u['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="handler" id="handler" value="">
                    </div>
                </section>

                <input type="hidden" name="case_data" id="case_data">

                <div class="case-actions">
                    <button type="submit" class="btn btn-primary"><?php echo __('create_case'); ?></button>
                    <a href="cases.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('caseForm');
    const memberField = document.getElementById('member_data');
    const memberLookup = document.getElementById('member_lookup');
    const memberSearchBtn = document.getElementById('memberSearchBtn');
    const memberSearchResults = document.getElementById('memberSearchResults');
    const selectedMemberPanel = document.getElementById('selectedMemberPanel');
    const selectedMemberSummary = document.getElementById('selectedMemberSummary');
    const caseDataField = document.getElementById('case_data');
    const handlerSelect = document.getElementById('assigned_to');
    const handlerHidden = document.getElementById('handler');
    const recipientInput = document.getElementById('recipient');

    const handlerLabels = () => {
        if (!handlerSelect) return [];
        return Array.from(handlerSelect.selectedOptions)
            .map((option) => option.text)
            .filter((label) => label);
    };

    if (handlerSelect && handlerHidden) {
        const syncHandler = () => {
            handlerHidden.value = handlerLabels().join(', ');
        };
        syncHandler();
        handlerSelect.addEventListener('change', syncHandler);
    }

    const formatMemberText = (member) => {
        if (!member || typeof member !== 'object') return '';
        const fields = [
            ['Namn', member.namn],
            ['Medlemsnr', member.medlnr],
            ['Förening', member.forening],
            ['Befattning', member.befattning],
            ['Medlemsform', member.medlemsform],
            ['Verksamhetsform', member.verksamhetsform],
            ['Arbetsplats', member.arbetsplats],
        ];
        return fields
            .filter(([, val]) => val && String(val).trim() !== '')
            .map(([label, val]) => `${label}: ${val}`)
            .join('\n');
    };

    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    const renderMemberSummary = (member) => {
        const rows = [
            ['<?php echo __('full_name'); ?>', member.namn],
            ['<?php echo __('member_lookup'); ?>', member.medlnr],
            ['<?php echo __('organization'); ?>', member.forening],
            ['<?php echo __('position'); ?>', member.befattning],
            ['<?php echo __('workplace'); ?>', member.arbetsplats],
        ].filter(([, value]) => value && String(value).trim() !== '');

        selectedMemberSummary.innerHTML = rows.map(([label, value]) => `
            <div class="member-summary-row">
                <span>${label}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>
        `).join('');
    };

    const selectMember = (member) => {
        const text = formatMemberText(member);
        memberLookup.value = member.medlnr || member.id || memberLookup.value;
        memberField.value = text || JSON.stringify(member, null, 2);
        selectedMemberPanel.classList.remove('is-empty');
        renderMemberSummary(member);
        const title = document.getElementById('title');
        if (title && !title.value.trim()) {
            title.focus();
        }
    };

    const renderMemberResults = (members) => {
        memberSearchResults.innerHTML = '';
        if (!members || members.length === 0) {
            memberSearchResults.innerHTML = '<p class="muted"><?php echo __('no_members_found'); ?></p>';
            return;
        }

        members.forEach((member) => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'member-result';
            row.innerHTML = `
                <span>
                    <strong>${escapeHtml(member.namn)}</strong>
                    <small>${escapeHtml(member.medlnr)}${member.arbetsplats ? ' · ' + escapeHtml(member.arbetsplats) : ''}</small>
                </span>
                <span class="btn btn-secondary btn-sm"><?php echo __('select_member'); ?></span>
            `;
            row.addEventListener('click', () => selectMember(member));
            memberSearchResults.appendChild(row);
        });
    };

    const searchMembers = async () => {
        const q = memberLookup.value.trim();
        if (q.length < 2) {
            memberSearchResults.innerHTML = '<p class="muted"><?php echo __('member_search_min_chars'); ?></p>';
            return;
        }

        memberSearchBtn.disabled = true;
        memberSearchResults.innerHTML = '<p class="muted"><?php echo __('searching'); ?></p>';
        try {
            const res = await fetch(`member-search.php?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            renderMemberResults(data.success ? data.results : []);
        } catch (err) {
            memberSearchResults.innerHTML = '<p class="muted"><?php echo __('error_general'); ?></p>';
        } finally {
            memberSearchBtn.disabled = false;
        }
    };

    memberSearchBtn.addEventListener('click', searchMembers);
    memberLookup.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchMembers();
        }
    });

    form.addEventListener('submit', () => {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }

        const payload = {
            received_at: document.getElementById('received_at').value,
            recipient: document.getElementById('recipient').value,
            handler: handlerHidden.value,
            member_lookup: memberLookup.value,
            case_body: document.getElementById('case_body').value,
            last_edited_at: new Date().toISOString()
        };

        if (memberField.value.trim()) {
            try {
                payload.member_data = JSON.parse(memberField.value);
            } catch (_) {
                payload.member_data = memberField.value;
            }
        }

        caseDataField.value = JSON.stringify(payload);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
