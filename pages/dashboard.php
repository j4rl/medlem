<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$userStats = getCaseStatistics($user['id']);
$orgStats = getCaseStatistics();
$submittedCases = array_slice(getAllCases($user['id'], null, 'created'), 0, 3);
$assignedCases = array_slice(getAllCases($user['id'], null, 'assigned'), 0, 3);

$statusKeys = ['no_action', 'in_progress', 'resolved', 'closed'];
$priorityKeys = ['low', 'medium', 'high', 'urgent'];
$statusColors = [
    'no_action' => '#38bdf8',
    'in_progress' => '#fbbf24',
    'resolved' => '#22c55e',
    'closed' => '#a855f7',
];
$priorityColors = [
    'low' => '#cbd5e1',
    'medium' => '#60a5fa',
    'high' => '#f97316',
    'urgent' => '#ef4444',
];

// Helper for radial meters
$meterPercent = function ($count, $total) {
    $total = (int)$total;
    $count = (int)$count;
    return $total > 0 ? round(($count / $total) * 100) : 0;
};

// Helper to build a conic gradient for pie charts
function buildPieStyle(array $counts, array $colors, array $keys): string {
    $total = 0;
    foreach ($keys as $k) {
        $total += (int)($counts[$k] ?? 0);
    }
    if ($total <= 0) {
        return "background: conic-gradient(#e5e7eb 0deg 360deg);";
    }
    $segments = [];
    $current = 0.0;
    foreach ($keys as $k) {
        $val = (int)($counts[$k] ?? 0);
        if ($val <= 0) {
            continue;
        }
        $next = $current + ($val / $total) * 360;
        $color = $colors[$k] ?? '#2563eb';
        $segments[] = sprintf('%s %.2fdeg %.2fdeg', $color, $current, $next);
        $current = $next;
    }
    if (empty($segments)) {
        return "background: conic-gradient(#e5e7eb 0deg 360deg);";
    }
    return 'background: conic-gradient(' . implode(',', $segments) . ');';
}

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
                <button type="button" class="btn btn-primary" onclick="window.location.href='case-create.php'"><?php echo __('new_case'); ?></button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='cases.php'"><?php echo __('all_cases'); ?></button>
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

        <?php
            $userStatusCounts = $userStats['status_counts'] ?? [];
            $userPriorityCounts = $userStats['priority_counts'] ?? [];
            $orgStatusCounts = $orgStats['status_counts'] ?? [];
            $orgPriorityCounts = $orgStats['priority_counts'] ?? [];

            $calcMax = function(array $data, array $keys): int {
                $max = 0;
                foreach ($keys as $k) {
                    $max = max($max, (int)($data[$k] ?? 0));
                }
                return $max ?: 1;
            };

            $userStatusMax = $calcMax($userStatusCounts, $statusKeys);
            $userPriorityMax = $calcMax($userPriorityCounts, $priorityKeys);
            $orgStatusMax = $calcMax($orgStatusCounts, $statusKeys);
            $orgPriorityMax = $calcMax($orgPriorityCounts, $priorityKeys);
        ?>

        <section class="card">
            <div class="section-header">
                <h2><?php echo __('statistics'); ?></h2>
                <span class="muted">Status + <?php echo __('priority'); ?> (pie)</span>
            </div>
            <div class="pie-grid">
                <div class="pie-block">
                    <h3><?php echo __('case_stats_mine'); ?> — <?php echo __('status'); ?></h3>
                    <div class="pie-wrap">
                        <div class="pie-chart" style="<?php echo buildPieStyle($userStatusCounts, $statusColors, $statusKeys); ?>"></div>
                        <div class="pie-center"><?php echo array_sum($userStatusCounts); ?></div>
                    </div>
                    <ul class="pie-legend">
                        <?php foreach ($statusKeys as $key): ?>
                            <li>
                                <span class="pie-dot" style="background: <?php echo $statusColors[$key]; ?>;"></span>
                                <?php echo __('status_' . $key); ?> (<?php echo (int)($userStatusCounts[$key] ?? 0); ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="pie-block">
                    <h3><?php echo __('case_stats_mine'); ?> — <?php echo __('priority'); ?></h3>
                    <div class="pie-wrap">
                        <div class="pie-chart" style="<?php echo buildPieStyle($userPriorityCounts, $priorityColors, $priorityKeys); ?>"></div>
                        <div class="pie-center"><?php echo array_sum($userPriorityCounts); ?></div>
                    </div>
                    <ul class="pie-legend">
                        <?php foreach ($priorityKeys as $key): ?>
                            <li>
                                <span class="pie-dot" style="background: <?php echo $priorityColors[$key]; ?>;"></span>
                                <?php echo __('priority_' . $key); ?> (<?php echo (int)($userPriorityCounts[$key] ?? 0); ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="pie-block">
                    <h3><?php echo __('case_stats_overall'); ?> — <?php echo __('status'); ?></h3>
                    <div class="pie-wrap">
                        <div class="pie-chart" style="<?php echo buildPieStyle($orgStatusCounts, $statusColors, $statusKeys); ?>"></div>
                        <div class="pie-center"><?php echo array_sum($orgStatusCounts); ?></div>
                    </div>
                    <ul class="pie-legend">
                        <?php foreach ($statusKeys as $key): ?>
                            <li>
                                <span class="pie-dot" style="background: <?php echo $statusColors[$key]; ?>;"></span>
                                <?php echo __('status_' . $key); ?> (<?php echo (int)($orgStatusCounts[$key] ?? 0); ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="pie-block">
                    <h3><?php echo __('case_stats_overall'); ?> — <?php echo __('priority'); ?></h3>
                    <div class="pie-wrap">
                        <div class="pie-chart" style="<?php echo buildPieStyle($orgPriorityCounts, $priorityColors, $priorityKeys); ?>"></div>
                        <div class="pie-center"><?php echo array_sum($orgPriorityCounts); ?></div>
                    </div>
                    <ul class="pie-legend">
                        <?php foreach ($priorityKeys as $key): ?>
                            <li>
                                <span class="pie-dot" style="background: <?php echo $priorityColors[$key]; ?>;"></span>
                                <?php echo __('priority_' . $key); ?> (<?php echo (int)($orgPriorityCounts[$key] ?? 0); ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
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
