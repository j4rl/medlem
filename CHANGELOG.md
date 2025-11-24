# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2024-11-24

### Added
- Initial release of Medlem case management system
- User authentication (login, registration, logout)
- User profiles with customizable settings
  - Profile picture upload
  - Theme mode (light/dark)
  - Customizable primary colors
  - Language selection (Swedish/English)
- Case management system
  - Create, read, update, delete cases
  - Case status management (new, in progress, resolved, closed)
  - Priority levels (low, medium, high, urgent)
  - Case assignment to users
  - Case comments
- Dashboard with statistics
- Internationalization (i18n) support
  - Swedish (default)
  - English
- Responsive design for mobile and desktop
- Security features
  - Password hashing (bcrypt)
  - SQL injection protection (prepared statements)
  - XSS protection (htmlspecialchars)
  - Session-based authentication
  - File upload validation
- Database schema with MySQL/MariaDB support
- Installation helper script

### Technical Details
- PHP 7.4+ with MySQLi
- HTML5, CSS3, vanilla JavaScript
- No external dependencies
- Clean, maintainable code structure

### Security
- All passwords are hashed using PHP's password_hash()
- SQL queries use prepared statements
- User input is validated and sanitized
- File uploads are restricted by type and size
- Session management for authentication
