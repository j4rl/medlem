<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cases.php';
requireLogin();

$user = getCurrentUser();
$lastLoginAt = getLastLoginAt() ?? ($user['last_login'] ?? null);
$statusFilter = $_GET['status'] ?? null;
$scope = $_GET['scope'] ?? 'related'; // related | created | assigned
$cases = getAllCases($user['id'], $statusFilter, $scope);
$cases = annotateCaseRecency($cases, (int)$user['id'], $lastLoginAt);
$myCases = getAllCases($user['id'], null, 'created');
$myAssignments = getAllCases($user['id'], null, 'assigned');

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex-between mb-3">
            <div>
                <p class="eyebrow"><?php echo __('cases'); ?></p>
                <h1><?php echo __('all_cases'); ?></h1>
                <p class="muted"><?php echo __('my_cases'); ?> + <?php echo __('assigned_cases'); ?>, filter by status and search.</p>
            </div>
            <div class="flex gap-2">
                <a href="case-create.php" class="btn btn-primary"><?php echo __('new_case'); ?></a>
            </div>
        </div>

        <div class="card">
            <div class="flex-between mb-3" style="gap: 12px; flex-wrap: wrap;">
                <div class="form-group" style="margin: 0; flex: 1; min-width: 220px;">
                    <input type="text" id="searchInput" class="form-input" placeholder="<?php echo __('search'); ?>...">
                </div>

                <div class="flex gap-2" style="flex-wrap: wrap;">
                    <a href="cases.php" class="btn btn-sm <?php echo !$statusFilter ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('all_cases'); ?>
                    </a>
                    <a href="cases.php?status=no_action" class="btn btn-sm <?php echo $statusFilter === 'no_action' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('status_no_action'); ?>
                    </a>
                    <a href="cases.php?status=in_progress" class="btn btn-sm <?php echo $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('status_in_progress'); ?>
                    </a>
                    <a href="cases.php?status=resolved" class="btn btn-sm <?php echo $statusFilter === 'resolved' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('status_resolved'); ?>
                    </a>
                </div>

                <div class="flex gap-2" style="flex-wrap: wrap;">
                    <a href="cases.php?scope=related" class="btn btn-sm <?php echo $scope === 'related' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('assigned_cases'); ?>
                    </a>
                    <a href="cases.php?scope=created" class="btn btn-sm <?php echo $scope === 'created' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('my_cases'); ?>
                    </a>
                    <a href="cases.php?scope=assigned" class="btn btn-sm <?php echo $scope === 'assigned' ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo __('assigned_to'); ?>
                    </a>
                </div>
            </div>
            <?php if ($lastLoginAt): ?>
                <div class="case-legend">
                    <div class="case-legend__flags">
                        <span class="case-flag case-flag--new"><?php echo __('flag_new_assignment'); ?></span>
                        <span class="case-flag case-flag--updated"><?php echo __('flag_recent_update'); ?></span>
                    </div>
                    <span class="muted"><?php echo sprintf(__('since_last_login'), date('Y-m-d H:i', strtotime($lastLoginAt))); ?></span>
                </div>
            <?php endif; ?>

            <?php if (count($cases) > 0): ?>
            <table class="table" id="casesTable">
                <thead>
                    <tr>
                        <th><?php echo __('case_number'); ?></th>
                        <th><?php echo __('title'); ?></th>
                        <th><?php echo __('created_by'); ?></th>
                        <th><?php echo __('assigned_to'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('priority'); ?></th>
                        <th><?php echo __('created_at'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                    <?php
                        $rowClasses = [];
                        if (!empty($case['is_new_assignment'])) {
                            $rowClasses[] = 'case-row-new';
                        }
                        if (!empty($case['is_recent_update'])) {
                            $rowClasses[] = 'case-row-updated';
                        }
                        $rowClassAttr = $rowClasses ? ' class="' . implode(' ', $rowClasses) . '"' : '';
                    ?>
                    <tr<?php echo $rowClassAttr; ?> onclick="window.location.href='case-edit.php?id=<?php echo $case['id']; ?>'" style="cursor: pointer;">
                        <td>
                            <div class="case-id-cell">
                                <div><?php echo htmlspecialchars($case['case_number']); ?></div>
                                <?php if (!empty($case['is_new_assignment']) || !empty($case['is_recent_update'])): ?>
                                    <div class="case-flags">
                                        <?php if (!empty($case['is_new_assignment'])): ?>
                                            <span class="case-flag case-flag--new"><?php echo __('flag_new_assignment'); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($case['is_recent_update'])): ?>
                                            <span class="case-flag case-flag--updated"><?php echo __('flag_recent_update'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($case['title']); ?></td>
                        <td><?php echo htmlspecialchars($case['creator_name']); ?></td>
                        <td>
                            <?php
                            $handlerNames = $case['handler_names'] ?? [];
                            $handlerLabel = $handlerNames ? implode(', ', $handlerNames) : ($case['assignee_name'] ?? '');
                            echo $handlerLabel !== '' ? htmlspecialchars($handlerLabel) : '-';
                            ?>
                        </td>
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
            <?php else: ?>
            <p class="text-center"><?php echo __('no_cases'); ?></p>
            <?php endif; ?>
        </div>

        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-top: 16px;">
            <div class="card">
                <h3><?php echo __('my_cases'); ?></h3>
                <ul class="list-unstyled">
                    <?php if (count($myCases) === 0): ?>
                        <li class="muted"><?php echo __('no_cases'); ?></li>
                    <?php endif; ?>
                    <?php foreach ($myCases as $case): ?>
                        <li class="flex-between" style="padding: 6px 0; border-bottom: 1px solid #eee;">
                            <div>
                                <div><?php echo htmlspecialchars($case['title']); ?></div>
                                <small class="muted"><?php echo htmlspecialchars($case['case_number']); ?></small>
                                <div class="flex gap-1" style="margin-top: 4px; flex-wrap: wrap;">
                                    <span class="badge badge-<?php echo $case['status']; ?>"><?php echo __('status_' . $case['status']); ?></span>
                                    <span class="badge badge-<?php echo $case['priority']; ?>"><?php echo __('priority_' . $case['priority']); ?></span>
                                </div>
                            </div>
                            <a class="btn btn-link btn-sm" href="case-edit.php?id=<?php echo $case['id']; ?>"><?php echo __('details'); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card">
                <h3><?php echo __('assigned_cases'); ?></h3>
                <ul class="list-unstyled">
                    <?php if (count($myAssignments) === 0): ?>
                        <li class="muted"><?php echo __('no_cases'); ?></li>
                    <?php endif; ?>
                    <?php foreach ($myAssignments as $case): ?>
                        <li class="flex-between" style="padding: 6px 0; border-bottom: 1px solid #eee;">
                            <div>
                                <div><?php echo htmlspecialchars($case['title']); ?></div>
                                <?php
                                $handlerNames = $case['handler_names'] ?? [];
                                $handlerLabel = $handlerNames ? implode(', ', $handlerNames) : ($case['assignee_name'] ?? '');
                                ?>
                                <small class="muted"><?php echo $handlerLabel !== '' ? htmlspecialchars($handlerLabel) : '-'; ?></small>
                                <div class="flex gap-1" style="margin-top: 4px; flex-wrap: wrap;">
                                    <span class="badge badge-<?php echo $case['status']; ?>"><?php echo __('status_' . $case['status']); ?></span>
                                    <span class="badge badge-<?php echo $case['priority']; ?>"><?php echo __('priority_' . $case['priority']); ?></span>
                                </div>
                            </div>
                            <a class="btn btn-link btn-sm" href="case-edit.php?id=<?php echo $case['id']; ?>"><?php echo __('details'); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</main>

<script>
    filterTable('searchInput', 'casesTable');
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
