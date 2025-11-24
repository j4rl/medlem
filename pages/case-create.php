<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$users = getAllUsers();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $assignedTo = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    
    if (empty($title) || empty($description)) {
        $error = __('error_required');
    } else {
        $result = createCase($title, $description, $priority, $user['id'], $assignedTo);
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
        <h1><?php echo __('create_case'); ?></h1>
        
        <div class="card mt-3">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="title"><?php echo __('title'); ?> *</label>
                    <input type="text" id="title" name="title" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="description"><?php echo __('description'); ?> *</label>
                    <textarea id="description" name="description" class="form-textarea" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
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
                
                <div class="form-group">
                    <label class="form-label" for="assigned_to"><?php echo __('assigned_to'); ?></label>
                    <select id="assigned_to" name="assigned_to" class="form-select">
                        <option value="">-</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($_POST['assigned_to'] ?? '') == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary"><?php echo __('create_case'); ?></button>
                    <a href="cases.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
