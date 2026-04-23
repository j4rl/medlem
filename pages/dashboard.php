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

usort($recentUpdates, fn($a, $b) => (strtotime($b['updated_at'] ?? '') ?: 0) <=> (strtotime($a['updated_at'] ?? '') ?: 0));
usort($highPriority, fn($a, $b) => (strtotime($b['updated_at'] ?? '') ?: 0) <=> (strtotime($a['updated_at'] ?? '') ?: 0));
usort($openAssigned, fn($a, $b) => (strtotime($b['updated_at'] ?? '') ?: 0) <=> (strtotime($a['updated_at'] ?? '') ?: 0));

function dashboardCaseList(array $cases, int $limit = 5): void
{
    if (empty($cases)) {
        echo '<p class="muted text-center">' . __('no_cases') . '</p>';
        return;
    }

    echo '<div class="case-list">';
    foreach (array_slice($cases, 0, $limit) as $case) {
        echo '<a class="case-row" href="case-edit.php?id=' . (int)$case['id'] . '">';
        echo '<div>';
        echo '<p class="case-title">' . htmlspecialchars($case['title']) . '</p>';
        echo '<p class="muted">' . htmlspecialchars($case['case_number']) . ' &bull; ' . date('Y-m-d H:i', strtotime($case['updated_at'] ?? $case['created_at'])) . '</p>';
        echo '</div>';
        echo '<div class="row-right" style="flex-wrap: wrap; justify-content: flex-end;">';
        echo renderCaseIndicators($case);
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

        <div class="grid grid-2 mt-3">
            <section class="card list-card">
                <div class="section-header">
                    <h2><?php echo __('new_assignments'); ?></h2>
                    <a class="link" href="cases.php?scope=assigned"><?php echo __('view_all'); ?></a>
                </div>
                <?php dashboardCaseList($newAssignments); ?>
            </section>

            <section class="card list-card">
                <div class="section-header">
                    <h2><?php echo __('high_priority_cases'); ?></h2>
                    <a class="link" href="cases.php?sort=priority&dir=desc"><?php echo __('view_all'); ?></a>
                </div>
                <?php dashboardCaseList($highPriority); ?>
            </section>

            <section class="card list-card">
                <div class="section-header">
                    <h2><?php echo __('recent_updates'); ?></h2>
                    <a class="link" href="cases.php?sort=updated_at&dir=desc"><?php echo __('view_all'); ?></a>
                </div>
                <?php dashboardCaseList($recentUpdates); ?>
            </section>

            <section class="card list-card">
                <div class="section-header">
                    <h2><?php echo __('open_assigned_cases'); ?></h2>
                    <a class="link" href="cases.php?scope=assigned&status=in_progress"><?php echo __('view_all'); ?></a>
                </div>
                <?php dashboardCaseList($openAssigned); ?>
            </section>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
