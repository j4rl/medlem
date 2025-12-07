<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/members.php';
require_once __DIR__ . '/../includes/i18n.php';

requireLogin();

$allMembers = fetchMembers();

$filterFields = ['arbetsplats', 'medlemsform', 'befattning', 'verksamhetsform'];
$options = [
    'search' => trim($_GET['search'] ?? ''),
    'arbetsplats' => trim($_GET['arbetsplats'] ?? ''),
    'medlemsform' => trim($_GET['medlemsform'] ?? ''),
    'befattning' => trim($_GET['befattning'] ?? ''),
    'verksamhetsform' => trim($_GET['verksamhetsform'] ?? ''),
    'turns50_months' => isset($_GET['turns50']) && $_GET['turns50'] !== '' ? (int)$_GET['turns50'] : null,
    'sort_by' => $_GET['sort'] ?? 'namn',
    'sort_dir' => $_GET['dir'] ?? 'asc',
];

$hasFilters = $options['search'] !== '' || $options['arbetsplats'] !== '' || $options['medlemsform'] !== '' || $options['befattning'] !== '' || $options['verksamhetsform'] !== '' || $options['turns50_months'] !== null;
$members = $hasFilters ? filterAndSortMembers($allMembers, $options) : [];

$uniqueValues = [];
foreach ($filterFields as $field) {
    $uniqueValues[$field] = [];
}
foreach ($allMembers as $member) {
    foreach ($filterFields as $field) {
        $value = trim((string)($member[$field] ?? ''));
        if ($value !== '') {
            $uniqueValues[$field][$value] = true;
        }
    }
}
foreach ($uniqueValues as $field => $values) {
    $uniqueValues[$field] = array_keys($values);
    natcasesort($uniqueValues[$field]);
    $uniqueValues[$field] = array_values($uniqueValues[$field]);
}

function buildQuery(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        }
    }
    $query = http_build_query($params);
    return $query === '' ? '' : '?' . $query;
}

function formatDateDisplay(?string $value): string
{
    $dt = parseBirthdate($value);
    return $dt ? $dt->format('Y-m-d') : '-';
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container container-wide">
        <div class="flex-between mb-3">
            <div>
                <p class="eyebrow"><?php echo __('member_directory'); ?></p>
                <h1><?php echo __('members'); ?></h1>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn btn-sm btn-chip <?php echo $options['turns50_months'] === 1 ? 'btn-accent' : 'btn-ghost'; ?>" onclick="window.location.href='<?php echo buildQuery(['turns50' => 1]); ?>'">
                    <?php echo __('within_1_month'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-chip <?php echo $options['turns50_months'] === 3 ? 'btn-accent' : 'btn-ghost'; ?>" onclick="window.location.href='<?php echo buildQuery(['turns50' => 3]); ?>'">
                    <?php echo __('within_3_months'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-chip <?php echo $options['turns50_months'] === 6 ? 'btn-accent' : 'btn-ghost'; ?>" onclick="window.location.href='<?php echo buildQuery(['turns50' => 6]); ?>'">
                    <?php echo __('within_6_months'); ?>
                </button>
            </div>
        </div>

        <div class="card">
            <form method="get" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label"><?php echo __('search'); ?></label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($options['search']); ?>" class="form-input" placeholder="<?php echo __('member_search_placeholder'); ?>">
                </div>
                <?php foreach ($filterFields as $field): ?>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label"><?php echo ucfirst($field); ?></label>
                        <select name="<?php echo $field; ?>" class="form-input">
                            <option value=""><?php echo __('filter'); ?></option>
                            <?php foreach ($uniqueValues[$field] as $value): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $options[$field] === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div class="form-group" style="margin:0;">
                    <label class="form-label"><?php echo __('sort_by'); ?></label>
                    <select name="sort" class="form-input">
                        <?php
                        $sortOptions = [
                            'medlnr' => 'Medlemsnummer',
                            'namn' => 'Namn',
                            'fodelsedatum' => 'Födelsedatum',
                            'forening' => 'Förening',
                            'medlemsform' => 'Medlemsform',
                            'befattning' => 'Befattning',
                            'verksamhetsform' => 'Verksamhetsform',
                            'arbetsplats' => 'Arbetsplats',
                        ];
                        foreach ($sortOptions as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $options['sort_by'] === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">&nbsp;</label>
                    <div class="flex gap-2" style="flex-wrap: nowrap; align-items: stretch;">
                        <input type="hidden" name="dir" value="<?php echo $options['sort_dir']; ?>">
                        <button type="submit" class="btn btn-primary" style="flex:1; min-width: 120px;"><?php echo __('filter'); ?></button>
                        <button type="button" class="btn btn-secondary" style="flex:1; min-width: 120px;" onclick="window.location.href='<?php echo basename(__FILE__); ?>'"><?php echo __('clear_filters'); ?></button>
                    </div>
                </div>
            </form>

            <?php if (!$hasFilters): ?>
                <p class="text-center muted" style="margin: 1rem 0;"><?php echo __('search'); ?> <?php echo __('members'); ?>...</p>
            <?php elseif (count($members) > 0): ?>
                <div class="table-responsive">
                    <table class="table members-table">
                        <thead>
                            <tr>
                                <?php
                                $columns = [
                                    'medlnr' => 'Medlemsnummer',
                                    'namn' => 'Namn',
                                    'fodelsedatum' => 'Födelsedatum',
                                    'forening' => 'Förening',
                                    'medlemsform' => 'Medlemsform',
                                    'befattning' => 'Befattning',
                                    'verksamhetsform' => 'Verksamhetsform',
                                    'arbetsplats' => 'Arbetsplats',
                                    'turns50' => __('turns_50_on'),
                                ];
                                foreach ($columns as $key => $label):
                                    if ($key === 'turns50') {
                                        echo '<th>' . htmlspecialchars($label) . '</th>';
                                        continue;
                                    }
                                    $nextDir = ($options['sort_by'] === $key && $options['sort_dir'] === 'asc') ? 'desc' : 'asc';
                                    $query = buildQuery(['sort' => $key, 'dir' => $nextDir]);
                                    $active = $options['sort_by'] === $key;
                                ?>
                                    <th>
                                        <a href="<?php echo $query; ?>" class="<?php echo $active ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars($label); ?>
                                            <?php if ($active): ?>
                                                <?php echo $options['sort_dir'] === 'asc' ? '↑' : '↓'; ?>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <?php $turns50 = getFiftiethBirthday($member['fodelsedatum'] ?? null); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['medlnr'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($member['namn'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(formatDateDisplay($member['fodelsedatum'] ?? null)); ?></td>
                                    <td><?php echo htmlspecialchars($member['forening'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($member['medlemsform'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($member['befattning'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($member['verksamhetsform'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($member['arbetsplats'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($turns50): ?>
                                            <?php echo $turns50->format('Y-m-d'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center muted" style="margin: 1rem 0;"><?php echo __('no_members_found'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
