<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$caseId = $_GET['id'] ?? 0;

function normalizeCaseEntries(array $caseData, array $case): array {
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
            ];
        }
    }

    if (empty($entries) && !empty($caseData)) {
        $entries[] = [
            'id' => 'entry-1',
            'title' => $case['title'] ?? 'Anteckning',
            'body' => (string)($caseData['case_body'] ?? $case['description'] ?? ''),
            'changed_at' => $caseData['last_edited_at'] ?? $case['updated_at'] ?? date('c'),
        ];
    }

    if (empty($entries)) {
        $entries[] = [
            'id' => 'entry-1',
            'title' => $case['title'] ?? 'Anteckning',
            'body' => (string)($case['description'] ?? ''),
            'changed_at' => $case['updated_at'] ?? date('c'),
        ];
    }

    usort($entries, function ($a, $b) {
        $timeA = strtotime($a['changed_at'] ?? '') ?: 0;
        $timeB = strtotime($b['changed_at'] ?? '') ?: 0;
        return $timeA <=> $timeB;
    });

    return array_values($entries);
}

function formatIsoForInput(?string $value): string {
    $ts = $value ? strtotime($value) : false;
    if ($ts === false) {
        return date('Y-m-d\TH:i');
    }
    return date('Y-m-d\TH:i', $ts);
}

$case = getCaseById($caseId);

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

$caseEntries = normalizeCaseEntries($caseData, $case);
$caseMeta = $caseData;
unset($caseMeta['entries'], $caseMeta['sections']);
$activeEntry = $caseEntries[count($caseEntries) - 1] ?? [];
$activeEntryId = $_POST['entry_id'] ?? ($activeEntry['id'] ?? '');
$activeEntryTitle = $_POST['entry_title'] ?? ($activeEntry['title'] ?? ($case['title'] ?? ''));
$activeEntryBody = $_POST['case_body'] ?? ($activeEntry['body'] ?? ($case['description'] ?? ''));
$activeEntryDate = formatIsoForInput($_POST['entry_date'] ?? ($activeEntry['changed_at'] ?? $case['updated_at'] ?? date('c')));

$memberDataDisplay = $_POST['member_data'] ?? '';
if ($memberDataDisplay === '' && !empty($case['member_data'])) {
    $decodedMember = json_decode($case['member_data'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMember)) {
        $prettyLines = [];
        foreach ($decodedMember as $key => $value) {
            $prettyLines[] = $key . ': ' . (is_scalar($value) ? $value : json_encode($value));
        }
        $memberDataDisplay = implode("\n", $prettyLines);
    } else {
        $memberDataDisplay = $case['member_data'];
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        if (deleteCase($caseId)) {
            header('Location: cases.php');
            exit();
        } else {
            $error = __('error_general');
        }
    } else {
        $title = $_POST['title'] ?? ($case['title'] ?? '');
        $caseBody = $_POST['case_body'] ?? '';
        $description = $caseBody;
        $status = $_POST['status'] ?? 'new';
        $priority = $_POST['priority'] ?? 'medium';
        $assignedTo = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : ($case['assigned_to'] ?? null);
        $handlerLabel = $_POST['handler'] ?? ($case['assignee_name'] ?? '');
        $memberDataRaw = $_POST['member_data'] ?? '';
        $caseDataRaw = $_POST['case_data'] ?? '';
        $entryTitle = $_POST['entry_title'] ?? '';
        $entryId = $_POST['entry_id'] ?? '';
        $entryDate = $_POST['entry_date'] ?? '';
        
        if (empty($title) || empty($description)) {
            $error = __('error_required');
        } else {
            $newCaseData = $caseData;
            if (!empty($caseDataRaw)) {
                $decoded = json_decode($caseDataRaw, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $newCaseData = $decoded;
                }
            }

            if (empty($newCaseData)) {
                $newCaseData = [];
            }

            if (!is_array($newCaseData)) {
                $newCaseData = [];
            }

            if ($handlerLabel !== '') {
                $newCaseData['handler'] = $handlerLabel;
            }
            if (!empty($caseBody)) {
                $newCaseData['case_body'] = $caseBody;
            }
            if ($entryTitle !== '') {
                $newCaseData['active_entry_title'] = $entryTitle;
            }
            if ($entryId !== '') {
                $newCaseData['active_entry_id'] = $entryId;
            }
            if ($entryDate !== '') {
                $newCaseData['active_entry_date'] = $entryDate;
            }
            if (!isset($newCaseData['last_edited_at'])) {
                $newCaseData['last_edited_at'] = date('c');
            }

            $memberData = $memberDataRaw !== '' ? $memberDataRaw : null;

            if (updateCase($caseId, $title, $description, $status, $priority, $assignedTo, $newCaseData, $memberData)) {
                header('Location: case-view.php?id=' . $caseId);
                exit();
            } else {
                $error = __('error_general');
            }
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
                    <p class="eyebrow"><?php echo __('edit'); ?> <?php echo __('case'); ?></p>
                    <h1><?php echo htmlspecialchars($case['case_number']); ?></h1>
                    <p class="muted"><?php echo __('case'); ?> <?php echo __('details'); ?> &amp; <?php echo __('case_body'); ?>.</p>
                </div>
                <div class="hero-actions">
                    <a href="case-view.php?id=<?php echo $caseId; ?>" class="btn btn-secondary btn-sm"><?php echo __('back'); ?></a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="caseForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="title"><?php echo __('title'); ?> *</label>
                        <input type="text" id="title" name="title" class="form-input" required readonly
                               value="<?php echo htmlspecialchars($case['title']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="created_at"><?php echo __('created_at'); ?></label>
                        <input type="text" id="created_at" class="form-input" 
                               value="<?php echo date('Y-m-d H:i', strtotime($case['created_at'])); ?>" readonly>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('handler'); ?></label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo htmlspecialchars($case['assignee_name'] ?? __('assigned_to')); ?>">
                        <input type="hidden" name="assigned_to" value="<?php echo htmlspecialchars($case['assigned_to'] ?? ''); ?>">
                        <input type="hidden" name="handler" id="handler" value="<?php echo htmlspecialchars($caseData['handler'] ?? ($case['assignee_name'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status"><?php echo __('status'); ?></label>
                        <select id="status" name="status" class="form-select">
                            <option value="new" <?php echo ($_POST['status'] ?? $case['status']) === 'new' ? 'selected' : ''; ?>>
                                <?php echo __('status_new'); ?>
                            </option>
                            <option value="in_progress" <?php echo ($_POST['status'] ?? $case['status']) === 'in_progress' ? 'selected' : ''; ?>>
                                <?php echo __('status_in_progress'); ?>
                            </option>
                            <option value="resolved" <?php echo ($_POST['status'] ?? $case['status']) === 'resolved' ? 'selected' : ''; ?>>
                                <?php echo __('status_resolved'); ?>
                            </option>
                            <option value="closed" <?php echo ($_POST['status'] ?? $case['status']) === 'closed' ? 'selected' : ''; ?>>
                                <?php echo __('status_closed'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="priority"><?php echo __('priority'); ?></label>
                        <select id="priority" name="priority" class="form-select">
                            <option value="low" <?php echo ($_POST['priority'] ?? $case['priority']) === 'low' ? 'selected' : ''; ?>>
                                <?php echo __('priority_low'); ?>
                            </option>
                            <option value="medium" <?php echo ($_POST['priority'] ?? $case['priority']) === 'medium' ? 'selected' : ''; ?>>
                                <?php echo __('priority_medium'); ?>
                            </option>
                            <option value="high" <?php echo ($_POST['priority'] ?? $case['priority']) === 'high' ? 'selected' : ''; ?>>
                                <?php echo __('priority_high'); ?>
                            </option>
                            <option value="urgent" <?php echo ($_POST['priority'] ?? $case['priority']) === 'urgent' ? 'selected' : ''; ?>>
                                <?php echo __('priority_urgent'); ?>
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="updated_at"><?php echo __('updated_at'); ?></label>
                        <input type="text" id="updated_at" class="form-input" 
                               value="<?php echo date('Y-m-d H:i', strtotime($case['updated_at'])); ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex-between" style="align-items: center;">
                        <label class="form-label" for="member_data"><?php echo __('member_data'); ?></label>
                        <button type="button" id="memberDataBtn" class="btn btn-secondary btn-sm">Medlemsdata</button>
                    </div>
                    <textarea id="member_data" name="member_data" class="form-textarea" rows="3" placeholder="Fritext..." spellcheck="false"><?php echo htmlspecialchars($memberDataDisplay); ?></textarea>
                </div>

                <div class="card" style="margin-top: 1rem;">
                    <div class="section-header" style="margin-bottom: 0.5rem;">
                        <div>
                            <h3 style="margin: 0;"><?php echo __('case_body'); ?></h3>
                            <p class="muted" style="margin: 0;"><?php echo __('case'); ?> <?php echo __('details'); ?></p>
                        </div>
                        <button type="button" id="newEntryBtn" class="btn btn-secondary btn-sm">Ny sektion</button>
                    </div>

                    <div id="caseDataList" class="case-list" style="gap: 0.75rem; display: flex; flex-direction: column;">
                        <?php foreach ($caseEntries as $entry): ?>
                        <div class="case-row" data-entry-id="<?php echo htmlspecialchars($entry['id']); ?>"
                             data-entry-title="<?php echo htmlspecialchars($entry['title']); ?>"
                             data-entry-date="<?php echo htmlspecialchars($entry['changed_at']); ?>"
                             data-entry-body="<?php echo htmlspecialchars($entry['body']); ?>" style="cursor: default;">
                            <div>
                                <p class="case-title" style="margin-bottom: 0.15rem;"><?php echo htmlspecialchars($entry['title']); ?></p>
                                <p class="muted" style="margin: 0;"><?php echo date('Y-m-d H:i', strtotime($entry['changed_at'] ?? $case['updated_at'])); ?></p>
                            </div>
                            <div class="row-right" style="gap: 0.5rem;">
                                <span class="badge"><?php echo __('last_edited_at'); ?></span>
                                <button type="button" class="btn btn-secondary btn-sm edit-entry" data-entry-id="<?php echo htmlspecialchars($entry['id']); ?>">
                                    <?php echo __('edit'); ?>
                                </button>
                            </div>
                            <?php
                                $preview = $entry['body'];
                                if (function_exists('mb_strimwidth')) {
                                    $preview = mb_strimwidth($preview, 0, 220, '...');
                                } else {
                                    $preview = strlen($preview) > 220 ? substr($preview, 0, 220) . '...' : $preview;
                                }
                            ?>
                            <p class="muted" style="width: 100%; margin-top: 0.5rem; white-space: pre-wrap;"><?php echo htmlspecialchars($preview); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($caseEntries)): ?>
                            <p class="muted"><?php echo __('no_cases'); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="card" style="margin-top: 1rem; background: var(--surface-alt, #f8f8f8);">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="entry_title"><?php echo __('title'); ?></label>
                                <input type="text" id="entry_title" name="entry_title" class="form-input"
                                       value="<?php echo htmlspecialchars($activeEntryTitle); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="entry_date"><?php echo __('last_edited_at'); ?></label>
                                <input type="datetime-local" id="entry_date" name="entry_date" class="form-input"
                                       value="<?php echo htmlspecialchars($activeEntryDate); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="case_body"><?php echo __('case_body'); ?> *</label>
                            <textarea id="case_body" name="case_body" class="form-textarea tall" required><?php echo htmlspecialchars($activeEntryBody); ?></textarea>
                            <p class="muted" style="margin-top: 0.25rem;"><?php echo __('status_in_progress'); ?> <?php echo __('case_body'); ?></p>
                        </div>
                        <input type="hidden" id="entry_id" name="entry_id" value="<?php echo htmlspecialchars($activeEntryId); ?>">
                    </div>
                </div>

                <input type="hidden" name="case_data" id="case_data">

                <div class="flex-between">
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
                        <a href="case-view.php?id=<?php echo $caseId; ?>" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                    </div>
                    <button type="submit" name="delete" class="btn btn-danger" 
                            onclick="return confirmDelete('<?php echo __('confirm_delete_case'); ?>');">
                        <?php echo __('delete'); ?>
                    </button>
                </div>
            </form>

            <div id="memberDataPopover" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:999; align-items:center; justify-content:center;">
                <div class="card" style="max-width: 620px; width: 92%; padding: 1.25rem; position: relative;">
                    <div class="flex-between" style="align-items: center; margin-bottom: 0.75rem;">
                        <h3 style="margin: 0;">Medlemsdata</h3>
                        <button type="button" class="btn btn-secondary btn-sm" id="closeMemberPopover">Close</button>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="memberSearchInput">Sok medlem</label>
                        <div class="flex gap-1">
                            <input type="search" id="memberSearchInput" class="form-input" placeholder="Namn eller nummer">
                            <button type="button" id="memberSearchBtn" class="btn btn-primary btn-sm"><?php echo __('search'); ?></button>
                        </div>
                    </div>
                    <div id="memberResultList" class="case-list" style="max-height: 180px; overflow-y: auto; margin-top: 0.5rem;"></div>
                    <div id="memberFieldSelector" class="form-group" style="margin-top: 1rem; display: none;">
                        <p class="muted" id="memberSelectedLabel" style="margin-bottom: 0.35rem;"></p>
                        <div id="memberFieldOptions" class="flex" style="flex-wrap: wrap; gap: 0.5rem;"></div>
                    </div>
                    <div class="flex gap-2" style="margin-top: 1rem;">
                        <button type="button" class="btn btn-primary btn-sm" id="copyMemberDataBtn">Copy data</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="closeMemberPopoverAlt">Close</button>
                    </div>
                </div>
            </div>


        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('caseForm');
    const caseDataField = document.getElementById('case_data');
    const entryIdField = document.getElementById('entry_id');
    const entryTitleField = document.getElementById('entry_title');
    const entryDateField = document.getElementById('entry_date');
    const caseBodyField = document.getElementById('case_body');
    const newEntryBtn = document.getElementById('newEntryBtn');
    const editButtons = Array.from(document.querySelectorAll('.edit-entry'));
    const memberDataBtn = document.getElementById('memberDataBtn');
    const memberField = document.getElementById('member_data');
    const memberPopover = document.getElementById('memberDataPopover');
    const memberSearchInput = document.getElementById('memberSearchInput');
    const memberSearchBtn = document.getElementById('memberSearchBtn');
    const memberResultList = document.getElementById('memberResultList');
    const memberFieldSelector = document.getElementById('memberFieldSelector');
    const memberFieldOptions = document.getElementById('memberFieldOptions');
    const memberSelectedLabel = document.getElementById('memberSelectedLabel');
    const copyMemberDataBtn = document.getElementById('copyMemberDataBtn');
    const closePopoverButtons = [document.getElementById('closeMemberPopover'), document.getElementById('closeMemberPopoverAlt')].filter(Boolean);
    const fieldLabels = {
        medlnr: 'Medlemsnr',
        namn: 'Namn',
        forening: 'Forening',
        medlemsform: 'Medlemsform',
        befattning: 'Befattning',
        verksamhetsform: 'Verksamhetsform',
        arbetsplats: 'Arbetsplats'
    };
    let selectedMember = null;
    const caseEntries = <?php echo json_encode($caseEntries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const caseMeta = <?php echo json_encode($caseMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || {};

    const toIsoString = (value) => {
        if (!value) return new Date().toISOString();
        const asDate = new Date(value);
        if (Number.isNaN(asDate.getTime())) {
            return new Date().toISOString();
        }
        return asDate.toISOString();
    };

    const toLocalValue = (value) => {
        if (!value) return '';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) {
            return '';
        }
        const pad = (n) => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    };

    const setEditor = (entry) => {
        entryIdField.value = entry.id || '';
        entryTitleField.value = entry.title || '';
        entryDateField.value = toLocalValue(entry.changed_at || entry.date) || toLocalValue(new Date().toISOString());
        caseBodyField.value = entry.body || '';
        caseBodyField.focus();
    };

    editButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-entry-id');
            const found = caseEntries.find((e) => e.id === targetId);
            if (found) {
                setEditor(found);
            }
        });
    });

    if (newEntryBtn) {
        newEntryBtn.addEventListener('click', () => {
            setEditor({ id: '', title: '', body: '', changed_at: new Date().toISOString() });
        });
    }

    const closePopover = () => {
        if (!memberPopover) return;
        memberPopover.style.display = 'none';
        selectedMember = null;
        if (memberResultList) memberResultList.innerHTML = '';
        if (memberFieldOptions) memberFieldOptions.innerHTML = '';
        if (memberFieldSelector) memberFieldSelector.style.display = 'none';
    };

    const openPopover = () => {
        if (!memberPopover) return;
        memberPopover.style.display = 'flex';
        if (memberSearchInput) {
            memberSearchInput.focus();
        }
    };

    closePopoverButtons.forEach((btn) => btn.addEventListener('click', closePopover));
    if (memberPopover) {
        memberPopover.addEventListener('click', (evt) => {
            if (evt.target === memberPopover) {
                closePopover();
            }
        });
    }

    const selectMember = (member) => {
        selectedMember = member;
        if (memberSelectedLabel) {
            memberSelectedLabel.textContent = `${member.namn || ''} (${member.medlnr || ''})`;
        }
        if (memberFieldOptions) {
            memberFieldOptions.innerHTML = '';
            Object.keys(fieldLabels).forEach((key) => {
                if (!(key in member)) {
                    return;
                }
                const label = document.createElement('label');
                label.style.display = 'flex';
                label.style.alignItems = 'center';
                label.style.gap = '0.35rem';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'member-field-option';
                checkbox.value = key;
                checkbox.checked = key === 'medlnr' || key === 'namn';
                const text = document.createElement('span');
                text.textContent = `${fieldLabels[key]}: ${member[key] || ''}`;
                label.appendChild(checkbox);
                label.appendChild(text);
                memberFieldOptions.appendChild(label);
            });
        }
        if (memberFieldSelector) {
            memberFieldSelector.style.display = 'block';
        }
    };

    const renderResults = (members) => {
        if (!memberResultList) return;
        memberResultList.innerHTML = '';
        if (!Array.isArray(members) || members.length === 0) {
            memberResultList.innerHTML = '<p class="muted">Inga traffar</p>';
            return;
        }
        members.forEach((member) => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'case-row';
            row.style.justifyContent = 'space-between';
            row.style.textAlign = 'left';
            row.innerHTML = `<span>${member.namn || ''}</span><span class="muted">${member.medlnr || ''}</span>`;
            row.addEventListener('click', () => selectMember(member));
            memberResultList.appendChild(row);
        });
    };

    const performSearch = async () => {
        if (!memberSearchInput) return;
        const term = memberSearchInput.value.trim();
        if (!term) return;
        if (memberResultList) {
            memberResultList.innerHTML = '<p class="muted">Soker...</p>';
        }
        try {
            const res = await fetch(`member-search.php?q=${encodeURIComponent(term)}`);
            const data = await res.json();
            if (!data.success) {
                throw new Error(data.error || 'Search failed');
            }
            renderResults(data.results || []);
        } catch (err) {
            if (memberResultList) {
                memberResultList.innerHTML = `<p class="muted">${err.message}</p>`;
            }
        }
    };

    if (memberDataBtn) {
        memberDataBtn.addEventListener('click', openPopover);
    }
    if (memberSearchBtn) {
        memberSearchBtn.addEventListener('click', performSearch);
    }
    if (memberSearchInput) {
        memberSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }
    if (copyMemberDataBtn) {
        copyMemberDataBtn.addEventListener('click', async () => {
            if (!selectedMember) return;
            const checked = Array.from(document.querySelectorAll('.member-field-option:checked'));
            const lines = checked.map((input) => {
                const key = input.value;
                return `${fieldLabels[key] || key}: ${selectedMember[key] ?? ''}`.trim();
            }).filter(Boolean);
            const text = lines.join('\n');
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
            } catch (err) {
                console.warn('clipboard', err);
            }
            if (memberField) {
                memberField.value = text;
            }
        });
    }

    form.addEventListener('submit', () => {
        const updatedEntries = Array.isArray(caseEntries) ? [...caseEntries] : [];
        const editorEntry = {
            id: entryIdField.value || `entry-${Date.now()}`,
            title: entryTitleField.value || '<?php echo addslashes($case['title']); ?>',
            body: caseBodyField.value,
            changed_at: toIsoString(entryDateField.value),
        };
        const existingIndex = updatedEntries.findIndex((e) => e.id === editorEntry.id);
        if (editorEntry.body && editorEntry.body.trim() !== '') {
            if (existingIndex >= 0) {
                updatedEntries[existingIndex] = editorEntry;
            } else {
                updatedEntries.push(editorEntry);
            }
        }

        updatedEntries.sort((a, b) => new Date(a.changed_at).getTime() - new Date(b.changed_at).getTime());
        const latestBody = updatedEntries.length > 0 ? updatedEntries[updatedEntries.length - 1].body : editorEntry.body;

        const payload = Object.assign({}, caseMeta, {
            entries: updatedEntries,
            case_body: latestBody,
            last_edited_at: new Date().toISOString(),
        });

        caseDataField.value = JSON.stringify(payload);
    });
});
</script>



<?php include __DIR__ . '/../includes/footer.php'; ?>
