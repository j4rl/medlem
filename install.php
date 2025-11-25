<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Medlem</title>
    <?php require_once __DIR__ . '/config/config.php'; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 600px;">
            <div class="auth-header">
                <h1 class="auth-title">Medlem Installation</h1>
                <p>Setup Guide / Installationsguide</p>
            </div>
            
            <?php
            $dbConfigured = file_exists(__DIR__ . '/config/database.php');
            $canConnect = false;
            $dbExists = false;
            
            if ($dbConfigured) {
                try {
                    require_once __DIR__ . '/config/database.php';
                    $conn = @getDBConnection();
                    if ($conn) {
                        $canConnect = true;
                        // Check if tables exist
                        $result = $conn->query("SHOW TABLES LIKE 'tbl_users'");
                        $dbExists = $result && $result->num_rows > 0;
                        closeDBConnection($conn);
                    }
                } catch (Exception $e) {
                    // Database not configured properly
                }
            }
            ?>
            
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Installation Steps</h2>
                
                <div style="margin-bottom: 1rem;">
                    <strong>1. Database Configuration</strong>
                    <?php if ($dbConfigured): ?>
                        <div class="alert alert-success" style="margin-top: 0.5rem;">
                            ✓ config/database.php exists
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error" style="margin-top: 0.5rem;">
                            ✗ Copy config/database.example.php to config/database.php and update credentials
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>2. Database Connection</strong>
                    <?php if ($canConnect): ?>
                        <div class="alert alert-success" style="margin-top: 0.5rem;">
                            ✓ Successfully connected to database
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error" style="margin-top: 0.5rem;">
                            ✗ Cannot connect to database. Check your credentials.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>3. Database Tables</strong>
                    <?php if ($dbExists): ?>
                        <div class="alert alert-success" style="margin-top: 0.5rem;">
                            ✓ Database tables are set up
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error" style="margin-top: 0.5rem;">
                            ✗ Run the SQL script: mysql -u username -p < config/setup.sql
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>4. File Permissions</strong>
                    <?php 
                    $uploadsWritable = is_writable(__DIR__ . '/assets/uploads/profiles');
                    if ($uploadsWritable): ?>
                        <div class="alert alert-success" style="margin-top: 0.5rem;">
                            ✓ Upload directory is writable
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error" style="margin-top: 0.5rem;">
                            ✗ Run: chmod 755 assets/uploads/profiles
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($dbConfigured && $canConnect && $dbExists && $uploadsWritable): ?>
                    <div class="alert alert-success" style="margin-top: 1.5rem;">
                        <strong>✓ Installation Complete!</strong><br>
                        <a href="/pages/login.php" style="color: #065f46; text-decoration: underline;">
                            Go to Login Page
                        </a>
                    </div>
                    
                    <div style="margin-top: 1rem; padding: 1rem; background-color: var(--bg-secondary); border-radius: 0.375rem;">
                        <strong>Default Login:</strong><br>
                        Username: <code>admin</code><br>
                        Password: <code>admin123</code><br>
                        <small style="color: var(--text-secondary);">⚠️ Change this password immediately!</small>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-top: 1.5rem;">
                        Please complete all steps above before using the application.
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 1.5rem; text-align: center; color: var(--text-secondary);">
                For detailed instructions, see 
                <a href="https://github.com/j4rl/medlem" target="_blank" style="color: var(--primary-color);">
                    README.md
                </a>
            </div>
        </div>
    </div>
</body>
</html>
