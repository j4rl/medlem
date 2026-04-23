<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$lastLoginAt = getLastLoginAt() ?? ($user['last_login'] ?? null);

$statusOptions = getStatusOptions();
$scopeOptions = [
    'related' => __('my_related_cases'),
    'created' => __('my_cases'),
    'assigned' => __('assigned_cases'),
];
$sortOptions = [
    'updated_at' => __('updated_at'),
    'created_at' => __('created_at'),
    'priority' => __('priority'),
    'status' => __('status'),
    'title' => __('title'),
    'case_number' => __('case_number'),
];

$statusFilter = $_GET['status'] ?? '';
$statusFilter = array_key_exists($statusFilter, $statusOptions) ? $statusFilter : '';
$scope = $_GET['scope'] ?? 'related';
$scope = array_key_exists($scope, $scopeOptions) ? $scope : 'related';
$search = trim((string)($_GET['q'] ?? ''));
$sortBy = $_GET['sort'] ?? 'updated_at';
$sortBy = array_key_exists($sortBy, $sortOptions) ? $sortBy : 'updated_at';
$sortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$cases = getAllCases($user['id'], $statusFilter ?: null, $scope);
$cases = annotateCaseRecency($cases, (int)$user['id'], $lastLoginAt);

if ($search !== '') {
    $needle = function_exists('mb_strtolower') ? mb_strtolower($search) : strtolower($search);
    $cases = array_values(array_filter($cases, function ($case) use ($needle) {
        $handlerNames = $case['handler_names'] ?? [];
        $haystack = implode(' ', [
            $case['case_number'] ?? '',
            $case['title'] ?? '',
            $case['creator_name'] ?? '',
            implode(' ', $handlerNames),
            $case['assignee_name'] ?? '',
            $case['status'] ?? '',
            $case['priority'] ?? '',
        ]);
        $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack) : strtolower($haystack);
        return strpos($haystack, $needle) !== false;
    }));
}

$priorityRank = ['urgent' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
$statusRank = ['no_action' => 4, 'in_progress' => 3, 'resolved' => 2, 'closed' => 1];
usort($cases, function ($a, $b) use ($sortBy, $sortDir, $priorityRank, $statusRank) {
    if ($sortBy === 'priority') {
        $cmp = ($priorityRank[$a['priority'] ?? ''] ?? 0) <=> ($priorityRank[$b['priority'] ?? ''] ?? 0);
    } elseif ($sortBy === 'status') {
        $cmp = ($statusRank[$a['status'] ?? ''] ?? 0) <=> ($statusRank[$b['status'] ?? ''] ?? 0);
    } elseif (in_array($sortBy, ['created_at', 'updated_at'], true)) {
        $cmp = (strtotime($a[$sortBy] ?? '') ?: 0) <=> (strtotime($b[$sortBy] ?? '') ?: 0);
    } else {
        $cmp = strcasecmp((string)($a[$sortBy] ?? ''), (string)($b[$sortBy] ?? ''));
    }
    return $sortDir === 'asc' ? $cmp : -$cmp;
});

$totalCases = count($cases);
$totalPages = max(1, (int)ceil($totalCases / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$visibleCases = array_slice($cases, $offset, $perPage);
$from = $totalCases > 0 ? $offset + 1 : 0;
$to = min($offset + $perPage, $totalCases);

function caseQuery(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null || ($key === 'page' && (int)$value <= 1)) {
            unset($params[$key]);
        }
    }
    $query = http_build_query($params);
    return $query === '' ? 'cases.php' : 'cases.php?' . $query;
}

function caseHandlerLabel(array $case): string
{
    $handlerNames = $case['handler_names'] ?? [];
    $handlerLabel = $handlerNames ? implode(', ', $handlerNames) : ($case['assignee_name'] ?? '');
    return $handlerLabel !== '' ? $handlerLabel : '-';
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex-between mb-3" style="align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
            <div>
                <p class="eyebrow"><?php echo __('cases'); ?></p>
                <h1><?php echo __('case_workbench'); ?></h1>
                <p class="muted"><?php echo __('case_workbench_hint'); ?></p>
            </div>
            <a href="case-create.php" class="btn btn-primary"><?php echo __('new_case'); ?></a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success"><?php echo __('case_deleted'); ?></div>
        <?php endif; ?>

        <div class="card case-table-card">
            <form method="GET" action="cases.php" class="case-filter-grid">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="q"><?php echo __('search'); ?></label>
                    <input type="search" id="q" name="q" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo __('search'); ?>...">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="scope"><?php echo __('filter'); ?></label>
                    <select id="scope" name="scope" class="form-select">
                        <?php foreach ($scopeOptions as $code => $label): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $scope === $code ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="status"><?php echo __('status'); ?></label>
                    <select id="status" name="status" class="form-select">
                        <option value=""><?php echo __('all_cases'); ?></option>
                        <?php foreach ($statusOptions as $code => $label): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $statusFilter === $code ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="sort"><?php echo __('sort_by'); ?></label>
                    <select id="sort" name="sort" class="form-select">
                        <?php foreach ($sortOptions as $code => $label): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $sortBy === $code ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDir); ?>">
                </div>
                <div class="case-filter-actions">
                    <button type="submit" class="btn btn-primary"><?php echo __('filter'); ?></button>
                    <a href="cases.php" class="btn btn-secondary"><?php echo __('clear_filters'); ?></a>
                </div>
            </form>

            <?php if ($lastLoginAt): ?>
                <div class="case-legend">
                    <div class="case-legend__flags">
                        <span class="case-flag case-flag--new"><?php echo __('flag_new_assignment'); ?></span>
                        <span class="case-flag case-flag--updated"><?php echo __('flag_recent_update'); ?></span>
                    </div>
                    <span class="muted"><?php echo sprintf(__('since_last_login'), date('Y-m-d H:i', strtotime($lastLoginAt))); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($totalCases > 0): ?>
                <p class="muted" style="margin: 1rem 0 0.5rem;">
                    <?php echo sprintf(__('showing_results'), $from, $to, $totalCases); ?>
                </p>

                <div class="table-responsive case-table-desktop">
                    <table class="table" id="casesTable">
                        <thead>
                            <tr>
                                <?php foreach (['case_number', 'title', 'created_by', 'assigned_to', 'status', 'priority', 'updated_at'] as $column): ?>
                                    <th>
                                        <?php if (array_key_exists($column, $sortOptions)): ?>
                                            <?php $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc'; ?>
                                            <a href="<?php echo caseQuery(['sort' => $column, 'dir' => $nextDir, 'page' => 1]); ?>">
                                                <?php echo htmlspecialchars($sortOptions[$column]); ?>
                                                <?php if ($sortBy === $column): ?><?php echo $sortDir === 'asc' ? '↑' : '↓'; ?><?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo __($column); ?>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visibleCases as $case): ?>
                                <?php
                                $rowClasses = [];
                                if (!empty($case['is_new_assignment'])) $rowClasses[] = 'case-row-new';
                                if (!empty($case['is_recent_update'])) $rowClasses[] = 'case-row-updated';
                                $rowClassAttr = $rowClasses ? ' class="' . implode(' ', $rowClasses) . '"' : '';
                                ?>
                                <tr<?php echo $rowClassAttr; ?> onclick="window.location.href='case-edit.php?id=<?php echo (int)$case['id']; ?>'" style="cursor: pointer;">
                                    <td>
                                        <div class="case-id-cell">
                                            <div><?php echo htmlspecialchars($case['case_number']); ?></div>
                                            <?php if (!empty($case['is_new_assignment']) || !empty($case['is_recent_update'])): ?>
                                                <div class="case-flags">
                                                    <?php if (!empty($case['is_new_assignment'])): ?><span class="case-flag case-flag--new"><?php echo __('flag_new_assignment'); ?></span><?php endif; ?>
                                                    <?php if (!empty($case['is_recent_update'])): ?><span class="case-flag case-flag--updated"><?php echo __('flag_recent_update'); ?></span><?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($case['title']); ?></td>
                                    <td><?php echo htmlspecialchars($case['creator_name']); ?></td>
                                    <td><?php echo htmlspecialchars(caseHandlerLabel($case)); ?></td>
                                    <td><?php echo renderCaseIndicator('status', $case['status']); ?></td>
                                    <td><?php echo renderCaseIndicator('priority', $case['priority']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($case['updated_at'] ?? $case['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="case-mobile-list">
                    <?php foreach ($visibleCases as $case): ?>
                        <a class="case-row" href="case-edit.php?id=<?php echo (int)$case['id']; ?>">
                            <div>
                                <p class="case-title"><?php echo htmlspecialchars($case['title']); ?></p>
                                <p class="muted"><?php echo htmlspecialchars($case['case_number']); ?> &bull; <?php echo htmlspecialchars(caseHandlerLabel($case)); ?></p>
                            </div>
                            <div class="row-right" style="flex-wrap: wrap; justify-content: flex-end;">
                                <?php echo renderCaseIndicators($case); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="pagination">
                    <span class="muted"><?php echo __('page'); ?> <?php echo $page; ?> / <?php echo $totalPages; ?></span>
                    <div class="pagination__actions">
                        <a class="btn btn-secondary btn-sm" href="<?php echo caseQuery(['page' => max(1, $page - 1)]); ?>" <?php echo $page <= 1 ? 'aria-disabled="true"' : ''; ?>><?php echo __('previous'); ?></a>
                        <a class="btn btn-secondary btn-sm" href="<?php echo caseQuery(['page' => min($totalPages, $page + 1)]); ?>" <?php echo $page >= $totalPages ? 'aria-disabled="true"' : ''; ?>><?php echo __('next'); ?></a>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-center muted" style="margin: 1.5rem 0;"><?php echo __('no_matching_cases'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
