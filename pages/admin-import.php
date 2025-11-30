<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/members.php';
require_once __DIR__ . '/../includes/i18n.php';

requireAdmin();

$user = getCurrentUser();
$error = '';
$summary = null;
$canImport = encryptionIsConfigured();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canImport) {
        $error = __('encryption_not_configured');
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = __('error_upload_failed');
    } elseif (strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $error = __('error_invalid_csv');
    } else {
        $result = importMembersFromCsv($_FILES['csv_file']['tmp_name'], $user['id'], $_FILES['csv_file']['name']);
        if (!empty($result['success'])) {
            $summary = $result;
        } else {
            $errorKey = $result['error'] ?? 'error_general';
            $error = __($errorKey) !== $errorKey ? __($errorKey) : __('error_general');
            if (!empty($result['missing'])) {
                $error .= ': ' . implode(', ', $result['missing']);
            }
        }
    }
}

$history = getMemberImportHistory(10);

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="card mt-3">
            <div class="section-header">
                <div>
            <p class="eyebrow"><?php echo __('admin'); ?></p>
            <h1><?php echo __('member_import'); ?></h1>
            <p class="muted"><?php echo __('member_import_hint'); ?></p>
        </div>
    </div>

            <?php if (!$canImport): ?>
                <div class="alert alert-error">
                    <?php echo __('encryption_not_configured'); ?>
                    <br>
                    <small><?php echo __('encryption_not_configured_hint'); ?></small>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($summary): ?>
                <div class="alert alert-success">
                    <?php echo sprintf(__('import_result_line'), $summary['total'], $summary['inserted'], $summary['updated'], $summary['skipped']); ?>
                    <?php if (isset($summary['inactivated'])): ?>
                        <br><?php echo sprintf(__('import_inactivated_line'), (int)$summary['inactivated']); ?>
                    <?php endif; ?>
                    <?php if (!empty($summary['errors'])): ?>
                        <br><?php echo __('import_result_errors'); ?>
                        <ul style="margin-top: 0.5rem;">
                            <?php foreach (array_slice($summary['errors'], 0, 5) as $msg): ?>
                                <li><?php echo htmlspecialchars($msg); ?></li>
                            <?php endforeach; ?>
                            <?php if (count($summary['errors']) > 5): ?>
                                <li><?php echo sprintf(__('import_result_more_errors'), count($summary['errors']) - 5); ?></li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label" for="csv_file"><?php echo __('import_csv_label'); ?></label>
                    <input type="file" id="csv_file" name="csv_file" class="form-input" accept=".csv" required>
                    <p class="muted" style="margin-top: 0.5rem;">
                        <?php echo __('import_csv_help'); ?>
                    </p>
                </div>

                <button type="submit" class="btn btn-primary" <?php echo $canImport ? '' : 'disabled'; ?>>
                    <?php echo __('import_csv_action'); ?>
                </button>
            </form>
        </div>

        <div class="card mt-3">
            <div class="section-header">
                <h2><?php echo __('import_history'); ?></h2>
                <span class="muted"><?php echo __('import_history_sub'); ?></span>
            </div>
            <?php if (empty($history)): ?>
                <p class="muted"><?php echo __('no_data'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="importTable">
                        <thead>
                            <tr>
                                <th><?php echo __('filename'); ?></th>
                                <th><?php echo __('imported_by'); ?></th>
                                <th><?php echo __('imported_at'); ?></th>
                                <th><?php echo __('total_rows'); ?></th>
                                <th><?php echo __('inserted_rows'); ?></th>
                                <th><?php echo __('updated_rows'); ?></th>
                                <th><?php echo __('skipped_rows'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['filename']); ?></td>
                                    <td><?php echo htmlspecialchars($item['imported_by_name'] ?? __('unknown_user')); ?></td>
                                    <td data-date="<?php echo htmlspecialchars($item['imported_at']); ?>">
                                        <?php echo htmlspecialchars($item['imported_at']); ?>
                                    </td>
                                    <td><?php echo (int)$item['total_rows']; ?></td>
                                    <td><?php echo (int)$item['inserted_rows']; ?></td>
                                    <td><?php echo (int)$item['updated_rows']; ?></td>
                                    <td><?php echo (int)$item['skipped_rows']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
