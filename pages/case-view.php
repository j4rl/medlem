<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$caseId = $_GET['id'] ?? 0;
$case = getCaseById($caseId);

if (!$case) {
    header('Location: cases.php');
    exit();
}

$comments = getCaseComments($caseId);
$caseData = [];
if (!empty($case['case_data'])) {
    $decoded = json_decode($case['case_data'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $caseData = $decoded;
    }
}
$memberDataPretty = '';
if (!empty($case['member_data'])) {
    $decodedMember = json_decode($case['member_data'], true);
    $memberDataPretty = json_last_error() === JSON_ERROR_NONE
        ? json_encode($decodedMember, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $case['member_data'];
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment = $_POST['comment'] ?? '';
    if (!empty($comment)) {
        addCaseComment($caseId, $user['id'], $comment);
        header('Location: case-view.php?id=' . $caseId);
        exit();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex-between mb-3">
            <h1><?php echo htmlspecialchars($case['title']); ?></h1>
            <div class="flex gap-2">
                <a href="case-edit.php?id=<?php echo $case['id']; ?>" class="btn btn-secondary btn-sm">
                    <?php echo __('edit'); ?>
                </a>
                <a href="cases.php" class="btn btn-secondary btn-sm">
                    <?php echo __('back'); ?>
                </a>
            </div>
        </div>
        
        <div class="grid grid-2">
            <div>
                <div class="card">
                    <h2 class="card-header"><?php echo __('description'); ?></h2>
                    <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($case['description']); ?></p>
                </div>

                <?php if (!empty($caseData)): ?>
                <div class="card">
                    <h2 class="card-header"><?php echo __('case_data_json'); ?></h2>
                    <div class="meta-grid">
                        <div>
                            <strong><?php echo __('received_at'); ?>:</strong><br>
                            <?php echo htmlspecialchars($caseData['received_at'] ?? '-'); ?>
                        </div>
                        <div>
                            <strong><?php echo __('recipient'); ?>:</strong><br>
                            <?php echo htmlspecialchars($caseData['recipient'] ?? '-'); ?>
                        </div>
                        <div>
                            <strong><?php echo __('handler'); ?>:</strong><br>
                            <?php echo htmlspecialchars($caseData['handler'] ?? '-'); ?>
                        </div>
                        <div>
                            <strong><?php echo __('member_lookup'); ?>:</strong><br>
                            <?php echo htmlspecialchars($caseData['member_lookup'] ?? '-'); ?>
                        </div>
                        <div>
                            <strong><?php echo __('last_edited_at'); ?>:</strong><br>
                            <?php echo htmlspecialchars($caseData['last_edited_at'] ?? '-'); ?>
                        </div>
                    </div>
                    <pre class="code-block"><?php echo htmlspecialchars(json_encode($caseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <h2 class="card-header"><?php echo __('comments'); ?></h2>
                    
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <img src="<?php echo BASE_URL; ?>/assets/uploads/profiles/<?php echo htmlspecialchars($comment['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($comment['full_name']); ?>" 
                                 class="comment-avatar"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default.png'">
                            <div class="comment-content">
                                <div class="comment-header">
                                    <span class="comment-author"><?php echo htmlspecialchars($comment['full_name']); ?></span>
                                    <span class="comment-date"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($comment['comment']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center" style="color: var(--muted);"><?php echo __('no_comments_yet'); ?></p>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="mt-3">
                        <div class="form-group">
                            <label class="form-label" for="comment"><?php echo __('add_comment'); ?></label>
                            <textarea id="comment" name="comment" class="form-textarea" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="add_comment" class="btn btn-primary btn-sm">
                            <?php echo __('add_comment'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <div>
                <?php if (!empty($memberDataPretty)): ?>
                <div class="card">
                    <h2 class="card-header"><?php echo __('member_data'); ?></h2>
                    <pre class="code-block"><?php echo htmlspecialchars($memberDataPretty); ?></pre>
                </div>
                <?php endif; ?>

                <div class="card">
                    <h2 class="card-header"><?php echo __('case'); ?> <?php echo __('details'); ?></h2>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong><?php echo __('case_number'); ?>:</strong><br>
                        <?php echo htmlspecialchars($case['case_number']); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong><?php echo __('status'); ?>:</strong><br>
                        <span class="badge badge-<?php echo $case['status']; ?>">
                            <?php echo __('status_' . $case['status']); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong><?php echo __('priority'); ?>:</strong><br>
                        <span class="badge badge-<?php echo $case['priority']; ?>">
                            <?php echo __('priority_' . $case['priority']); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong><?php echo __('created_by'); ?>:</strong><br>
                        <div class="flex gap-2" style="align-items: center; margin-top: 0.5rem;">
                            <img src="<?php echo BASE_URL; ?>/assets/uploads/profiles/<?php echo htmlspecialchars($case['creator_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($case['creator_name']); ?>" 
                                 style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default.png'">
                            <?php echo htmlspecialchars($case['creator_name']); ?>
                        </div>
                    </div>
                    
                    <?php if ($case['assigned_to']): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong><?php echo __('assigned_to'); ?>:</strong><br>
                        <div class="flex gap-2" style="align-items: center; margin-top: 0.5rem;">
                            <img src="<?php echo BASE_URL; ?>/assets/uploads/profiles/<?php echo htmlspecialchars($case['assignee_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($case['assignee_name']); ?>" 
                                 style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default.png'">
                            <?php echo htmlspecialchars($case['assignee_name']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong><?php echo __('created_at'); ?>:</strong><br>
                        <?php echo date('Y-m-d H:i', strtotime($case['created_at'])); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong><?php echo __('updated_at'); ?>:</strong><br>
                        <?php echo date('Y-m-d H:i', strtotime($case['updated_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
