<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user.php';
requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($fullName) || empty($email)) {
        $error = __('error_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('error_email_invalid');
    } else {
        $result = updateUserProfile($user['id'], $fullName, $email);
        if ($result['success']) {
            $success = __('success_update');
            $user = getCurrentUser();
        } else {
            $error = __($result['error']);
        }
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = $_FILES['profile_picture']['type'];
        $filesize = $_FILES['profile_picture']['size'];
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = __('error_invalid_file_type');
        } elseif ($filesize > 5 * 1024 * 1024) {
            $error = __('error_file_too_large');
        } else {
            $newFilename = 'profile_' . $user['id'] . '_' . time() . '.' . $ext;
            $uploadPath = __DIR__ . '/../assets/uploads/profiles/' . $newFilename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                // Delete old profile picture if not default
                if ($user['profile_picture'] !== 'default.png') {
                    $oldPath = __DIR__ . '/../assets/uploads/profiles/' . $user['profile_picture'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                if (updateProfilePicture($user['id'], $newFilename)) {
                    $success = __('success_update');
                    $user = getCurrentUser();
                } else {
                    $error = __('error_general');
                }
            } else {
                $error = __('error_general');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <h1><?php echo __('my_profile'); ?></h1>
        
        <div class="grid grid-2 mt-3">
            <div>
                <div class="card">
                    <h2 class="card-header"><?php echo __('profile_picture'); ?></h2>
                    
                    <div class="profile-picture-container">
                        <img src="<?php echo BASE_URL; ?>/assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                             alt="<?php echo htmlspecialchars($user['full_name']); ?>" 
                             class="profile-picture-large" id="profilePreview"
                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default.png'">
                    </div>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="file" name="profile_picture" class="form-input" accept="image/*"
                                   onchange="previewImage(this, 'profilePreview')">
                        </div>
                        <button type="submit" name="update_picture" class="btn btn-primary btn-sm">
                            <?php echo __('change_picture'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <div>
                <div class="card">
                    <h2 class="card-header"><?php echo __('edit_profile'); ?></h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label" for="full_name"><?php echo __('full_name'); ?></label>
                            <input type="text" id="full_name" name="full_name" class="form-input" required 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email"><?php echo __('email'); ?></label>
                            <input type="email" id="email" name="email" class="form-input" required 
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('username'); ?></label>
                            <input type="text" class="form-input" readonly 
                                   value="<?php echo htmlspecialchars($user['username']); ?>">
                            <small style="color: var(--muted);"><?php echo __('username_immutable'); ?></small>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <?php echo __('save'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
