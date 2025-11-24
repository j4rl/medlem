<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$stats = getCaseStatistics($user['id']);
$recentCases = getAllCases($user['id']);
$recentCases = array_slice($recentCases, 0, 5);

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <h1><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        
        <div class="grid grid-4 mt-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label"><?php echo __('total_cases'); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['open']; ?></div>
                <div class="stat-label"><?php echo __('open_cases'); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                <div class="stat-label"><?php echo __('resolved_cases'); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['closed']; ?></div>
                <div class="stat-label"><?php echo __('status_closed'); ?></div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="flex-between mb-2">
                <h2 class="card-header"><?php echo __('my_cases'); ?></h2>
                <a href="case-create.php" class="btn btn-primary btn-sm"><?php echo __('new_case'); ?></a>
            </div>
            
            <?php if (count($recentCases) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo __('case_number'); ?></th>
                        <th><?php echo __('title'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('priority'); ?></th>
                        <th><?php echo __('created_at'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentCases as $case): ?>
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
                        <td><?php echo date('Y-m-d H:i', strtotime($case['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="text-right mt-2">
                <a href="cases.php" class="btn btn-secondary btn-sm"><?php echo __('all_cases'); ?></a>
            </div>
            <?php else: ?>
            <p class="text-center"><?php echo __('no_cases'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
