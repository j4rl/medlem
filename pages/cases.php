<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$statusFilter = $_GET['status'] ?? null;
$cases = getAllCases(null, $statusFilter);

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex-between mb-3">
            <h1><?php echo __('all_cases'); ?></h1>
            <a href="case-create.php" class="btn btn-primary"><?php echo __('new_case'); ?></a>
        </div>
        
        <div class="card">
            <div class="flex-between mb-3">
                <div class="form-group" style="margin: 0; flex: 1; max-width: 300px;">
                    <input type="text" id="searchInput" class="form-input" placeholder="<?php echo __('search'); ?>...">
                </div>
                
                <div class="flex gap-2">
                    <a href="cases.php" class="btn btn-sm <?php echo !$statusFilter ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('all_cases'); ?>
                    </a>
                    <a href="cases.php?status=new" class="btn btn-sm <?php echo $statusFilter === 'new' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('status_new'); ?>
                    </a>
                    <a href="cases.php?status=in_progress" class="btn btn-sm <?php echo $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('status_in_progress'); ?>
                    </a>
                    <a href="cases.php?status=resolved" class="btn btn-sm <?php echo $statusFilter === 'resolved' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('status_resolved'); ?>
                    </a>
                </div>
            </div>
            
            <?php if (count($cases) > 0): ?>
            <table class="table" id="casesTable">
                <thead>
                    <tr>
                        <th><?php echo __('case_number'); ?></th>
                        <th><?php echo __('title'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('priority'); ?></th>
                        <th><?php echo __('created_by'); ?></th>
                        <th><?php echo __('assigned_to'); ?></th>
                        <th><?php echo __('created_at'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                    <tr onclick="window.location.href='case-view.php?id=<?php echo $case['id']; ?>'" style="cursor: pointer;">
                        <td><?php echo htmlspecialchars($case['case_number']); ?></td>
                        <td><?php echo htmlspecialchars($case['title']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $case['status']; ?>">
                                <?php echo __('status_' . $case['status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $case['priority']; ?>">
                                <?php echo __('priority_' . $case['priority']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($case['creator_name']); ?></td>
                        <td><?php echo $case['assignee_name'] ? htmlspecialchars($case['assignee_name']) : '-'; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($case['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-center"><?php echo __('no_cases'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    filterTable('searchInput', 'casesTable');
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
