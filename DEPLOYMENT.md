# Deployment Checklist

Use this checklist when deploying the Medlem system to production.

## Pre-Deployment

### Server Requirements
- [ ] PHP 7.4 or higher installed
- [ ] MySQL 5.7+ or MariaDB 10.2+ installed
- [ ] Apache or Nginx web server configured
- [ ] mod_rewrite enabled (Apache) or equivalent (Nginx)
- [ ] SSL certificate installed (recommended)

### File Preparation
- [ ] Clone or download the repository
- [ ] Set correct file permissions
  ```bash
  chmod 755 assets/uploads/profiles
  chmod 644 config/database.php
  ```
- [ ] Copy `config/database.example.php` to `config/database.php`
- [ ] Update database credentials in `config/database.php`

### Database Setup
- [ ] Create MySQL database
  ```bash
  mysql -u root -p -e "CREATE DATABASE medlem_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  ```
- [ ] Import database schema
  ```bash
  mysql -u username -p medlem_db < config/setup.sql
  ```
- [ ] Verify tables were created
  ```bash
  mysql -u username -p -e "SHOW TABLES FROM medlem_db;"
  ```

## Configuration

### Database Configuration
- [ ] Update `DB_HOST` in `config/database.php`
- [ ] Update `DB_USER` in `config/database.php`
- [ ] Update `DB_PASS` in `config/database.php`
- [ ] Update `DB_NAME` in `config/database.php`
- [ ] Test database connection (visit `/install.php`)

### Encryption Configuration
- [ ] Set `DATA_ENCRYPTION_KEY` (32-byte raw/hex/base64) for member data encryption
- [ ] Confirm OpenSSL AES-256-GCM is available on the server
- [ ] Verify member import works after key is set

### Security Configuration
- [ ] Change default admin password immediately after first login
- [ ] Review `.htaccess` security headers
- [ ] Ensure `config/` directory is not web-accessible
- [ ] Verify file upload restrictions work
- [ ] Test SQL injection protection
- [ ] Verify XSS protection

### Web Server Configuration

#### Apache
- [ ] Ensure `.htaccess` files are processed
- [ ] Enable `mod_rewrite`
- [ ] Enable `mod_headers`
- [ ] Set `AllowOverride All` for the directory

#### Nginx (if using)
- [ ] Configure PHP-FPM
- [ ] Add security headers to server config
- [ ] Configure file upload limits
- [ ] Set up URL rewriting rules

### PHP Configuration
- [ ] Set `upload_max_filesize = 5M`
- [ ] Set `post_max_size = 5M`
- [ ] Set `max_execution_time = 30`
- [ ] Enable `mysqli` extension
- [ ] Set `session.cookie_httponly = 1`
- [ ] Set `session.cookie_secure = 1` (if using HTTPS)

## Post-Deployment

### Testing
- [ ] Visit installation page (`/install.php`)
- [ ] Verify all checks pass
- [ ] Test user registration
- [ ] Test user login with default credentials
- [ ] Change admin password
- [ ] Test case creation
- [ ] Test case editing
- [ ] Test case deletion
- [ ] Test commenting on cases
- [ ] Test profile picture upload
- [ ] Test theme switching (light/dark)
- [ ] Test color scheme changes
- [ ] Test language switching (Swedish/English)
- [ ] Test on mobile device
- [ ] Test on tablet device
- [ ] Test on desktop browser

### Security Verification
- [ ] Try SQL injection attacks (should be blocked)
- [ ] Try XSS attacks (should be escaped)
- [ ] Verify sessions expire properly
- [ ] Test file upload restrictions
- [ ] Verify direct access to `/config/` is denied
- [ ] Verify direct access to `/includes/` PHP files is denied
- [ ] Check for exposed sensitive information

### Performance
- [ ] Test page load times
- [ ] Verify database queries are optimized
- [ ] Check for N+1 query problems
- [ ] Monitor memory usage
- [ ] Test with multiple concurrent users

### Monitoring
- [ ] Set up error logging
- [ ] Configure PHP error reporting for production
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 0);
  ini_set('log_errors', 1);
  ```
- [ ] Set up server monitoring
- [ ] Configure database backup schedule
- [ ] Set up file backup schedule

## Maintenance

### Regular Tasks
- [ ] Backup database daily
- [ ] Backup uploaded files weekly
- [ ] Review error logs weekly
- [ ] Update PHP and MySQL as needed
- [ ] Review and update dependencies (if any added)

### User Management
- [ ] Remove default admin account if not needed
- [ ] Audit user accounts regularly
- [ ] Remove inactive accounts
- [ ] Review case assignment patterns

### Security Updates
- [ ] Subscribe to PHP security updates
- [ ] Subscribe to MySQL security updates
- [ ] Monitor for web application security issues
- [ ] Keep server software updated

## Troubleshooting

### Common Issues

**Cannot connect to database**
- Verify database credentials
- Check if MySQL service is running
- Verify user has proper permissions

**File uploads not working**
- Check directory permissions (755 for `assets/uploads/profiles`)
- Verify PHP `upload_max_filesize` setting
- Check Apache/Nginx upload limits

**Pages not loading correctly**
- Verify `.htaccess` is being processed
- Check Apache error logs
- Ensure PHP is configured correctly

**Sessions not persisting**
- Check PHP session configuration
- Verify session directory is writable
- Check for session conflicts

**Theme/colors not saving**
- Check browser console for JavaScript errors
- Verify database connection
- Check user_settings table exists

## Rollback Plan

If issues occur after deployment:

1. [ ] Keep backup of previous version
2. [ ] Document database schema changes
3. [ ] Have rollback SQL scripts ready
4. [ ] Test rollback procedure
5. [ ] Inform users of maintenance window

## Final Checklist

- [ ] All tests passed
- [ ] Security checks completed
- [ ] Performance is acceptable
- [ ] Monitoring is in place
- [ ] Backups configured
- [ ] Documentation updated
- [ ] Users notified of go-live
- [ ] Support plan in place
- [ ] Remove or secure `/install.php` after setup

## Post-Go-Live

- [ ] Monitor error logs for first 24 hours
- [ ] Check performance metrics
- [ ] Gather user feedback
- [ ] Address any issues immediately
- [ ] Document lessons learned

---

**Important**: After successful deployment, remove or move `install.php` to a secure location to prevent unauthorized access to system information.
