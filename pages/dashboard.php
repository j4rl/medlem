<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$userStats = getCaseStatistics($user['id']);
$orgStats = getCaseStatistics();
$submittedCases = array_slice(getAllCases($user['id'], null, 'created'), 0, 6);
$assignedCases = array_slice(getAllCases($user['id'], null, 'assigned'), 0, 6);

// Helper for radial meters
$meterPercent = function ($count, $total) {
    $total = (int)$total;
    $count = (int)$count;
    return $total > 0 ? round(($count / $total) * 100) : 0;
};

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <section class="dashboard-hero">
            <div>
                <p class="eyebrow"><?php echo __('dashboard'); ?></p>
                <h1><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                <p class="muted"><?php echo __('statistics'); ?> &amp; <?php echo __('cases'); ?> at a glance.</p>
            </div>
            <div class="hero-actions">
                <a href="case-create.php" class="btn btn-primary"><?php echo __('new_case'); ?></a>
                <a href="cases.php" class="btn btn-secondary"><?php echo __('all_cases'); ?></a>
            </div>
        </section>

        <section class="card meter-section">
            <div class="section-header">
                <h2><?php echo __('case_stats_mine'); ?></h2>
                <span class="muted"><?php echo __('my_cases'); ?></span>
            </div>
            <div class="meter-grid">
                <?php
                    $userTotal = (int)($userStats['total'] ?? 0);
                    $userOpen = (int)($userStats['open'] ?? 0);
                    $userResolved = (int)($userStats['resolved'] ?? 0);
                    $userClosed = (int)($userStats['closed'] ?? 0);
                ?>
                <div class="meter" aria-label="<?php echo __('open_cases'); ?>">
                    <div class="meter-ring meter-open" style="--meter-percent: <?php echo $meterPercent($userOpen, $userTotal); ?>;">
                        <div class="meter-center">
                            <div class="meter-value"><?php echo $meterPercent($userOpen, $userTotal); ?>%</div>
                        </div>
                    </div>
                    <p class="meter-label"><?php echo __('open_cases'); ?></p>
                </div>
                <div class="meter" aria-label="<?php echo __('resolved_cases'); ?>">
                    <div class="meter-ring meter-resolved" style="--meter-percent: <?php echo $meterPercent($userResolved, $userTotal); ?>;">
                        <div class="meter-center">
                            <div class="meter-value"><?php echo $meterPercent($userResolved, $userTotal); ?>%</div>
                        </div>
                    </div>
                    <p class="meter-label"><?php echo __('resolved_cases'); ?></p>
                </div>
                <div class="meter" aria-label="<?php echo __('status_closed'); ?>">
                    <div class="meter-ring meter-closed" style="--meter-percent: <?php echo $meterPercent($userClosed, $userTotal); ?>;">
                        <div class="meter-center">
                            <div class="meter-value"><?php echo $meterPercent($userClosed, $userTotal); ?>%</div>
                        </div>
                    </div>
                    <p class="meter-label"><?php echo __('status_closed'); ?></p>
                </div>
                <div class="meter meter-total" aria-label="<?php echo __('total_cases'); ?>">
                    <div class="meter-ring meter-total" style="--meter-percent: 100;">
                        <div class="meter-center">
                            <div class="meter-value"><?php echo $userTotal; ?></div>
                        </div>
                    </div>
                    <p class="meter-label"><?php echo __('my_cases'); ?></p>
                </div>
            </div>
        </section>

        <section class="card meter-section">
            <div class="section-header">
                <h2><?php echo __('case_stats_overall'); ?></h2>
                <span class="muted"><?php echo __('statistics'); ?></span>
            </div>
            <div class="meter-grid">
                <?php
                    $orgTotal = (int)($orgStats['total'] ?? 0);
                    $orgOpen = (int)($orgStats['open'] ?? 0);
                    $orgResolved = (int)($orgStats['resolved'] ?? 0);
                    $orgClosed = (int)($orgStats['closed'] ?? 0);
                ?>
                <div class="meter" aria-label="<?php echo __('open_cases'); ?>">
                    <div class="meter-ring meter-open alt" style="--meter-percent: <?php echo $meterPercent($orgOpen, $orgTotal); ?>;">
                        <div class="meter-center">
                            <div class="meter-value"><?php echo $meterPercent($orgOpen, $orgTotal); ?>%</div>
                        </div>
                    </div>
                    <p class="meter-label"><?php echo __('open_cases'); ?></p>
                </div>
                <div class="meter" aria-label="<?php echo __('resolved_cases'); ?>">
                    <div class="meter-ring meter-resolved alt" style="--meter-percent: <?php echo $meterPercent($orgResolved, $orgTotal); ?>;">
                        <div class="meter-center">
                            <div class="meter-value"><?php echo $meterPercent($orgResolved, $orgTotal); ?>%</div>
                        </div>
                    </div>
                    <p class="meter-label"><?php echo __('resolved_cases'); ?></p>
                </div>
                <div class="meter" aria-label="<?php echo __('status_closed'); ?>">
                    <div class="meter-ring meter-closed alt" style="--meter-percent: <?php echo $meterPercent($orgClosed, $orgTotal); ?>;">
                        <div class="meter-center">
                            <div class="meter-value"><?php echo $meterPercent($orgClosed, $orgTotal); ?>%</div>
                        </div>
                    </div>
                    <p class="meter-label"><?php echo __('status_closed'); ?></p>
                </div>
                <div class="meter meter-total" aria-label="<?php echo __('total_cases'); ?>">
                    <div class="meter-ring meter-total alt" style="--meter-percent: 100;">
                        <div class="meter-center">
                            <div class="meter-value"><?php echo $orgTotal; ?></div>
                        </div>
                    </div>
                    <p class="meter-label"><?php echo __('all_cases'); ?></p>
                </div>
            </div>
        </section>

        <div class="grid grid-2 mt-3">
            <div class="card list-card">
                <div class="section-header">
                    <h2><?php echo __('submitted_cases'); ?></h2>
                    <a class="link" href="cases.php"><?php echo __('all_cases'); ?></a>
                </div>
                <?php if (count($submittedCases) > 0): ?>
                    <div class="case-list">
                        <?php foreach ($submittedCases as $case): ?>
                            <div class="case-row" onclick="window.location.href='case-edit.php?id=<?php echo $case['id']; ?>'">
                                <div>
                                    <p class="case-title"><?php echo htmlspecialchars($case['title']); ?></p>
                                    <p class="muted"><?php echo htmlspecialchars($case['case_number']); ?> • <?php echo date('Y-m-d H:i', strtotime($case['created_at'])); ?></p>
                                </div>
                                <div class="row-right">
                                    <span class="badge badge-<?php echo $case['status']; ?>"><?php echo __('status_' . $case['status']); ?></span>
                                    <span class="badge badge-<?php echo $case['priority']; ?>"><?php echo __('priority_' . $case['priority']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted text-center"><?php echo __('no_cases'); ?></p>
                <?php endif; ?>
            </div>

            <div class="card list-card">
                <div class="section-header">
                    <h2><?php echo __('assigned_cases'); ?></h2>
                    <a class="link" href="cases.php"><?php echo __('all_cases'); ?></a>
                </div>
                <?php if (count($assignedCases) > 0): ?>
                    <div class="case-list">
                        <?php foreach ($assignedCases as $case): ?>
                            <div class="case-row" onclick="window.location.href='case-edit.php?id=<?php echo $case['id']; ?>'">
                                <div>
                                    <p class="case-title"><?php echo htmlspecialchars($case['title']); ?></p>
                                    <p class="muted"><?php echo htmlspecialchars($case['case_number']); ?> • <?php echo htmlspecialchars($case['creator_name']); ?></p>
                                </div>
                                <div class="row-right">
                                    <span class="badge badge-<?php echo $case['status']; ?>"><?php echo __('status_' . $case['status']); ?></span>
                                    <span class="badge badge-<?php echo $case['priority']; ?>"><?php echo __('priority_' . $case['priority']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted text-center"><?php echo __('no_cases'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
