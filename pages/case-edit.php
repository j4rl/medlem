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

$users = getAllUsers();
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
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'new';
        $priority = $_POST['priority'] ?? 'medium';
        $assignedTo = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
        
        if (empty($title) || empty($description)) {
            $error = __('error_required');
        } else {
            if (updateCase($caseId, $title, $description, $status, $priority, $assignedTo)) {
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
        <h1><?php echo __('edit'); ?> <?php echo __('case'); ?></h1>
        
        <div class="card mt-3">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="title"><?php echo __('title'); ?> *</label>
                    <input type="text" id="title" name="title" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? $case['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="description"><?php echo __('description'); ?> *</label>
                    <textarea id="description" name="description" class="form-textarea" required><?php echo htmlspecialchars($_POST['description'] ?? $case['description']); ?></textarea>
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
                    <label class="form-label" for="assigned_to"><?php echo __('assigned_to'); ?></label>
                    <select id="assigned_to" name="assigned_to" class="form-select">
                        <option value="">-</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($_POST['assigned_to'] ?? $case['assigned_to']) == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
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
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
