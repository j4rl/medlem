<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$lastLoginAt = getLastLoginAt() ?? ($user['last_login'] ?? null);
$userStats = getCaseStatistics($user['id']);
$relatedCases = annotateCaseRecency(getAllCases($user['id'], null, 'related'), (int)$user['id'], $lastLoginAt);
$assignedCases = getAllCases($user['id'], null, 'assigned');

$newAssignments = array_values(array_filter($relatedCases, fn($case) => !empty($case['is_new_assignment'])));
$recentUpdates = array_values(array_filter($relatedCases, fn($case) => !empty($case['is_recent_update'])));
$highPriority = array_values(array_filter($relatedCases, fn($case) => in_array($case['priority'] ?? '', ['high', 'urgent'], true) && !in_array($case['status'] ?? '', ['resolved', 'closed'], true)));
$openAssigned = array_values(array_filter($assignedCases, fn($case) => !in_array($case['status'] ?? '', ['resolved', 'closed'], true)));
$workQueue = array_values(array_filter($relatedCases, fn($case) => !in_array($case['status'] ?? '', ['resolved', 'closed'], true)));

usort($recentUpdates, fn($a, $b) => (strtotime($b['updated_at'] ?? '') ?: 0) <=> (strtotime($a['updated_at'] ?? '') ?: 0));
usort($highPriority, fn($a, $b) => (strtotime($b['updated_at'] ?? '') ?: 0) <=> (strtotime($a['updated_at'] ?? '') ?: 0));
usort($openAssigned, fn($a, $b) => (strtotime($b['updated_at'] ?? '') ?: 0) <=> (strtotime($a['updated_at'] ?? '') ?: 0));
usort($workQueue, function ($a, $b) use ($user) {
    $score = function ($case) use ($user) {
        $priorityRank = ['urgent' => 400, 'high' => 300, 'medium' => 100, 'low' => 50];
        $score = $priorityRank[$case['priority'] ?? ''] ?? 0;
        if (!empty($case['is_new_assignment'])) $score += 1000;
        if (!empty($case['is_recent_update'])) $score += 200;
        if (in_array((int)($user['id'] ?? 0), array_map('intval', $case['handler_ids'] ?? []), true)) $score += 150;
        return $score;
    };

    $cmp = $score($a) <=> $score($b);
    if ($cmp !== 0) {
        return -$cmp;
    }

    return (strtotime($b['updated_at'] ?? '') ?: 0) <=> (strtotime($a['updated_at'] ?? '') ?: 0);
});

function dashboardCaseReason(array $case, int $userId): string
{
    if (!empty($case['is_new_assignment'])) {
        return __('reason_new_assignment');
    }
    if (in_array($case['priority'] ?? '', ['urgent', 'high'], true)) {
        return __('reason_high_priority');
    }
    if (!empty($case['is_recent_update'])) {
        return __('reason_recent_update');
    }
    if (in_array($userId, array_map('intval', $case['handler_ids'] ?? []), true)) {
        return __('reason_open_assigned');
    }
    return __('open_cases');
}

function dashboardWorkQueue(array $cases, int $userId, int $limit = 10): void
{
    if (empty($cases)) {
        echo '<p class="muted text-center">' . __('no_cases') . '</p>';
        return;
    }

    echo '<div class="case-list case-list--work-queue">';
    foreach (array_slice($cases, 0, $limit) as $case) {
        echo '<a class="case-row case-row--work" href="case-edit.php?id=' . (int)$case['id'] . '">';
        echo '<div>';
        echo '<div class="case-work-reason">' . htmlspecialchars(dashboardCaseReason($case, $userId)) . '</div>';
        echo '<p class="case-title">' . htmlspecialchars($case['title']) . '</p>';
        echo '<p class="muted">' . htmlspecialchars($case['case_number']) . ' &bull; ' . date('Y-m-d H:i', strtotime($case['updated_at'] ?? $case['created_at'])) . '</p>';
        echo '</div>';
        echo '<div class="row-right" style="flex-wrap: wrap; justify-content: flex-end;">';
        echo renderCaseIndicatorLabels($case);
        echo '</div>';
        echo '</a>';
    }
    echo '</div>';
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <section class="dashboard-hero">
            <div>
                <p class="eyebrow"><?php echo __('dashboard'); ?></p>
                <h1><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                <p class="muted"><?php echo __('dashboard_focus_hint'); ?></p>
            </div>
            <div class="hero-actions">
                <a class="btn btn-primary" href="case-create.php"><?php echo __('new_case'); ?></a>
                <a class="btn btn-secondary" href="cases.php"><?php echo __('case_workbench'); ?></a>
            </div>
        </section>

        <section class="summary-grid mt-3">
            <div class="summary-tile">
                <div class="summary-tile__value"><?php echo (int)($userStats['open'] ?? 0); ?></div>
                <div class="summary-tile__label"><?php echo __('open_cases'); ?></div>
            </div>
            <div class="summary-tile">
                <div class="summary-tile__value"><?php echo count($newAssignments); ?></div>
                <div class="summary-tile__label"><?php echo __('new_assignments'); ?></div>
            </div>
            <div class="summary-tile">
                <div class="summary-tile__value"><?php echo count($highPriority); ?></div>
                <div class="summary-tile__label"><?php echo __('high_priority_cases'); ?></div>
            </div>
            <div class="summary-tile">
                <div class="summary-tile__value"><?php echo count($openAssigned); ?></div>
                <div class="summary-tile__label"><?php echo __('open_assigned_cases'); ?></div>
            </div>
        </section>

        <section class="card list-card mt-3 workflow-panel">
            <div class="section-header">
                <div>
                    <h2><?php echo __('work_queue'); ?></h2>
                    <p class="muted"><?php echo __('work_queue_hint'); ?></p>
                </div>
                <a class="link" href="cases.php?scope=related&status=in_progress"><?php echo __('view_all'); ?></a>
            </div>
            <?php dashboardWorkQueue($workQueue, (int)$user['id']); ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
