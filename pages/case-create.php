<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$users = getAllUsers();
$error = '';
$success = '';
$now = date('Y-m-d\TH:i');
// Default taker is the current user for faster handoff
$defaultAssignee = $user['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $caseBody = $_POST['case_body'] ?? '';
    $description = $caseBody; // keep legacy field populated
    $priority = $_POST['priority'] ?? 'medium';
    $assignedTo = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $receivedAt = $_POST['received_at'] ?? '';
    $recipient = $_POST['recipient'] ?? '';
    $handlerLabel = $_POST['handler'] ?? '';
$memberLookup = $_POST['member_lookup'] ?? '';
$memberDataRaw = $_POST['member_data'] ?? '';
$caseDataRaw = $_POST['case_data'] ?? '';
    
    if (empty($title) || empty($description)) {
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

        $memberData = null;
        if (!empty($memberDataRaw)) {
            $memberDataDecoded = json_decode($memberDataRaw, true);
            $memberData = json_last_error() === JSON_ERROR_NONE ? $memberDataDecoded : $memberDataRaw;
        }

        $result = createCase($title, $description, $priority, $user['id'], $assignedTo, $caseData, $memberData);
        if ($result['success']) {
            header('Location: case-view.php?id=' . $result['case_id']);
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
                    <h1>Ärendehantering</h1>
                    <p class="muted"><?php echo __('case_body'); ?> + JSON-data sparas i `case_data`.</p>
                </div>
                <div class="hero-actions">
                    <a href="cases.php" class="btn btn-secondary btn-sm"><?php echo __('cancel'); ?></a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="caseForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="title"><?php echo __('title'); ?> *</label>
                        <input type="text" id="title" name="title" class="form-input" required autofocus
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
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

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="received_at"><?php echo __('received_at'); ?></label>
                        <input type="datetime-local" id="received_at" name="received_at" class="form-input"
                               value="<?php echo htmlspecialchars($_POST['received_at'] ?? $now); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="recipient"><?php echo __('recipient'); ?></label>
                        <input type="text" id="recipient" name="recipient" class="form-input"
                               value="<?php echo htmlspecialchars($_POST['recipient'] ?? ($user['full_name'] ?? '')); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="assigned_to"><?php echo __('handler'); ?></label>
                        <select id="assigned_to" name="assigned_to" class="form-select">
                            <option value=""><?php echo __('assigned_to'); ?></option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php
                                $selectedAssignee = $_POST['assigned_to'] ?? $defaultAssignee;
                                echo ($selectedAssignee == $u['id']) ? 'selected' : '';
                            ?>>
                                <?php echo htmlspecialchars($u['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="handler" id="handler" value="">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="member_lookup"><?php echo __('member_lookup'); ?></label>
                        <div class="flex gap-1">
                            <input type="number" id="member_lookup" name="member_lookup" class="form-input" 
                                   placeholder="12345" value="<?php echo htmlspecialchars($_POST['member_lookup'] ?? ''); ?>" style="flex: 1;">
                            <button type="button" id="fetchMemberBtn" class="btn btn-secondary btn-sm"><?php echo __('fetch_member'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="member_data"><?php echo __('member_data'); ?></label>
                    <textarea id="member_data" name="member_data" class="form-textarea" rows="3" placeholder="JSON..." spellcheck="false"><?php echo htmlspecialchars($_POST['member_data'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="case_body"><?php echo __('case_body'); ?> *</label>
                    <textarea id="case_body" name="case_body" class="form-textarea tall" required><?php echo htmlspecialchars($_POST['case_body'] ?? ''); ?></textarea>
                </div>

                <input type="hidden" name="case_data" id="case_data">

                <div class="flex gap-2">
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
    const caseDataField = document.getElementById('case_data');
    const handlerSelect = document.getElementById('assigned_to');
    const handlerHidden = document.getElementById('handler');
    const fetchBtn = document.getElementById('fetchMemberBtn');
    const recipientInput = document.getElementById('recipient');

    const insertAtCaret = (el, text) => {
        if (!el) return;
        const start = el.selectionStart ?? el.value.length;
        const end = el.selectionEnd ?? el.value.length;
        const before = el.value.substring(0, start);
        const after = el.value.substring(end);
        const needsNewlineBefore = before && !before.endsWith('\n');
        const needsNewlineAfter = after && !text.endsWith('\n');
        const insertion = `${needsNewlineBefore ? '\n' : ''}${text}${needsNewlineAfter ? '\n' : ''}`;
        el.value = before + insertion + after;
        const caret = (before + insertion).length;
        el.selectionStart = el.selectionEnd = caret;
        el.focus();
    };

    if (handlerSelect && handlerHidden) {
        handlerHidden.value = handlerSelect.options[handlerSelect.selectedIndex]?.text || '';
        handlerSelect.addEventListener('change', () => {
            handlerHidden.value = handlerSelect.options[handlerSelect.selectedIndex]?.text || '';
        });
    }

    if (fetchBtn) {
        fetchBtn.addEventListener('click', async () => {
            const id = memberLookup.value;
            if (!id) return;
            fetchBtn.disabled = true;
            fetchBtn.textContent = '...';
            try {
                const res = await fetch(`member-fetch.php?id=${encodeURIComponent(id)}`);
                const data = await res.json();
                if (data.success && data.member) {
                    insertAtCaret(memberField, JSON.stringify(data.member, null, 2));
                } else {
                    alert(data.error || 'Member not found');
                }
            } catch (err) {
                alert('Could not fetch member data');
            } finally {
                fetchBtn.disabled = false;
                fetchBtn.textContent = '<?php echo __('fetch_member'); ?>';
            }
        });
    }

    form.addEventListener('submit', () => {
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
