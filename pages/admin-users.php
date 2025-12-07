<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/user.php';
requireLogin();
requireAdmin();

$current = getCurrentUser();
$conn = getDBConnection();
$result = $conn->query("SELECT id, username, email, name AS full_name, role, userlevel, phone FROM tbl_users ORDER BY id ASC");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
closeDBConnection($conn);

$msg = '';
$err = '';
$view = $_GET['view'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$editUser = null;
if ($view === 'edit' && $editId) {
    foreach ($users as $u) {
        if ((int)$u['id'] === $editId) {
            $editUser = $u;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $userlevel = (int)($_POST['userlevel'] ?? 10);
        if ($username === '' || $email === '' || $password === '' || $name === '') {
            $err = __('error_required');
        } else {
            $res = createUserAdmin($username, $email, $password, $name, $role, 'sv', $userlevel);
            if ($res['success']) {
                header('Location: admin-users.php?created=1');
                exit();
            } else {
                $err = __($res['error'] ?? 'error_general');
            }
        }
    } elseif ($action === 'reset') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        if ($password === '') {
            $err = __('error_required');
        } else {
            if (resetUserPassword($userId, $password)) {
                $msg = __('success_update');
            } else {
                $err = __('error_general');
            }
        }
    } elseif ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === ($current['id'] ?? 0)) {
            $err = __('error_general');
        } else {
            if (deleteUserById($userId, $current['id'])) {
                header('Location: admin-users.php?deleted=1');
                exit();
            } else {
                $err = __('error_general');
            }
        }
    } elseif ($action === 'update') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $userlevel = (int)($_POST['userlevel'] ?? 10);
        $phone = trim($_POST['phone'] ?? '');
        if ($email === '' || $name === '') {
            $err = __('error_required');
        } else {
            $res = updateUserAdmin($userId, $email, $name, $role, $userlevel, $phone);
            if ($res['success']) {
                $msg = __('success_update');
                $view = 'edit';
                $editId = $userId;
            } else {
                $err = __($res['error'] ?? 'error_general');
            }
        }
    } elseif ($action === 'disable_2fa') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if (disableTwoFactor($userId)) {
            $msg = __('success_update');
        } else {
            $err = __('error_general');
        }
    } elseif ($action === 'one_time_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $temp = bin2hex(random_bytes(4)) . '!Aa';
        if (resetUserPassword($userId, $temp)) {
            $msg = "Engångslösenord: {$temp} (giltigt tills användaren loggar in och byter).";
        } else {
            $err = __('error_general');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="card mt-3">
            <div class="section-header">
                <div>
                    <p class="eyebrow"><?php echo __('admin'); ?></p>
                    <h1><?php echo __('users'); ?></h1>
                    <p class="muted">Hantera användare, skapa nya, återställ lösenord och ta bort.</p>
                </div>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-sm" href="admin-users.php?view=create"><?php echo __('create_case'); ?></a>
                    <a class="btn btn-secondary btn-sm" href="admin-import.php#users-import"><?php echo __('import_csv_action'); ?></a>
                </div>
            </div>

            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success"><?php echo __('success_create'); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success"><?php echo __('success_delete'); ?></div>
            <?php endif; ?>
            <?php if ($msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <?php if ($err): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div>
            <?php endif; ?>

            <?php if ($view === 'create'): ?>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="create">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="full_name"><?php echo __('full_name'); ?></label>
                            <input type="text" id="full_name" name="full_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password"><?php echo __('password'); ?></label>
                            <input type="password" id="password" name="password" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="role">Role</label>
                            <select name="role" id="role" class="form-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="userlevel">Userlevel</label>
                            <input type="number" id="userlevel" name="userlevel" class="form-input" value="10">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo __('create_case'); ?></button>
                    <a href="admin-users.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($view === 'edit' && $editUser): ?>
            <div class="card mt-3">
                <div class="section-header">
                    <h2>Redigera användare #<?php echo (int)$editUser['id']; ?></h2>
                    <a href="admin-users.php" class="btn btn-secondary btn-sm"><?php echo __('back'); ?></a>
                </div>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?php echo (int)$editUser['id']; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><?php echo __('email'); ?></label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($editUser['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo __('full_name'); ?></label>
                            <input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($editUser['full_name']); ?>" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="user" <?php echo strtolower($editUser['role']) === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo strtolower($editUser['role']) === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Userlevel</label>
                            <input type="number" name="userlevel" class="form-input" value="<?php echo (int)$editUser['userlevel']; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('phone'); ?></label>
                        <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
                        <a href="admin-users.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                    </div>
                </form>

                <div class="flex gap-2" style="margin-top: 1rem; flex-wrap: wrap;">
                    <form method="POST" style="display:inline-flex; gap: 0.5rem; align-items:center;">
                        <input type="hidden" name="action" value="one_time_password">
                        <input type="hidden" name="user_id" value="<?php echo (int)$editUser['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">Reset password (engång)</button>
                    </form>
                    <form method="POST" style="display:inline-flex; gap: 0.5rem; align-items:center;">
                        <input type="hidden" name="action" value="disable_2fa">
                        <input type="hidden" name="user_id" value="<?php echo (int)$editUser['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Reset 2FA</button>
                    </form>
                    <?php if (($current['id'] ?? 0) !== (int)$editUser['id']): ?>
                        <form method="POST" style="display:inline-flex; gap: 0.5rem; align-items:center;" onsubmit="return confirm('<?php echo __('confirm_delete_case'); ?>');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?php echo (int)$editUser['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm"><?php echo __('delete'); ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mt-3">
            <div class="section-header">
                <h2><?php echo __('users'); ?></h2>
                <span class="muted"><?php echo count($users); ?> st</span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th><?php echo __('full_name'); ?></th>
                            <th>Role</th>
                            <th>Userlevel</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo (int)$u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['role']); ?></td>
                                <td><?php echo (int)$u['userlevel']; ?></td>
                                <td>
                                    <a href="admin-users.php?view=edit&id=<?php echo (int)$u['id']; ?>" class="btn btn-secondary btn-sm"><?php echo __('edit'); ?></a>
                                    <?php if (($current['id'] ?? 0) !== (int)$u['id']): ?>
                                        <form method="POST" style="display:inline-block; margin-left: 4px;" onsubmit="return confirm('<?php echo __('confirm_delete_case'); ?>');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"><?php echo __('delete'); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
