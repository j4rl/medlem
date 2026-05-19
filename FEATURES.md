# Medlem - Feature Overview

## Core Features

### 1. User Authentication
- Registration with username, email, and password
- Secure login and session management
- Logout to clear sessions
- Optional TOTP-based two-factor authentication (6-digit codes)
- Login rate limiting backed by local temporary files

### 2. User Profiles
- Full name, email, immutable username
- Profile pictures (JPG/PNG/GIF, 5MB, old picture cleanup)

### 3. User Settings & Themes
- Light/Dark theme with instant preview
- User-selectable color themes from `tbl_colors`
- Language selection (sv/en) applied across the UI
- Admin theme manager for creating, editing, and deleting palettes

### 4. Case Management

#### Case Creation
- Title and description (required)
- Priority: low, medium, high, urgent
- Rich text description through local TinyMCE
- Optional assignment to one or more handlers
- Automatic case number in `YY-MM-####` format

#### Case View & Edit
- Creator and assignee profiles
- Status workflow (new, in progress, resolved, closed)
- Update title, sanitized rich text description, status, priority, member snapshot, and handler assignment

#### Case List
- User-scoped view: related / created / assigned tabs
- Status chips (new, in progress, resolved) and quick search
- Side cards showing the user's own cases and assigned cases with rubrik + handläggare
- Navigation badges for new assignments and recent updates since the user's previous login

#### Case Comments
- Add/view comments with attribution and timestamps

### 5. Members Directory & Import
- CSV import with robust header normalization (Födelsedatum, Arbetsplats, etc.)
- Import audit history for member imports
- Missing members in the latest import are marked as `Inaktiv`
- Search by name; filter by arbetsplats, medlemsform, befattning, verksamhetsform
- Sortable columns across member fields
- Quick views for members turning 50 within 1, 3, or 6 months

### 6. Admin Tools
- User administration: create, edit, delete, reset password, reset 2FA
- User CSV import with `username;email;password;name;phone;lang;colorscheme;userlevel`
- Admin access controlled by `userlevel` (`1000+` is admin)
- Theme administration for all color variables used by the UI

### 7. Dashboard
- Totals for open/resolved/etc.
- Work queue for open assigned cases, recent updates, and high-priority items

### 8. Internationalization (i18n)
- Swedish (default) and English
- Language switching from settings and login/register

### 9. Responsive Design
- Mobile-friendly layouts using flex/grid
- Touch-friendly targets and spacing

### 10. Security Features
- Password hashing via `password_hash` (bcrypt)
- Prepared statements for database access
- Escaped output (`htmlspecialchars`)
- Session-based authentication
- CSRF tokens for POST and API-changing flows
- Per-case authorization for page and API access
- File upload validation (type/size)
- Member data and sensitive case fields encrypted at rest (AES-256-GCM) except IDs/medlemsnummer
- Server-side rich text sanitization for allowed HTML
- Optional TOTP 2FA for logins

### 11. Database Schema (high level)
- `tbl_users`: accounts, userlevels, settings
- `user_settings`: appearance and language preferences
- `tbl_colors`: selectable/admin-managed color themes
- `tbl_members`: encrypted member data
- `tbl_cases`: cases with assignment and status
- `case_handlers`: many-to-many case assignments
- `case_comments`: case comments
- `tbl_member_imports`: import history

### 12. User Interface
- Modern card-based layout
- Light/dark themes with customizable primary color
- Consistent badges for status/priority
- Header notification badges for case activity

### 13. Additional Notes
- Installation helper via `config/setup.sql`
- `install.php` is disabled unless `ALLOW_INSTALL=1`
- DB credentials can be supplied via environment variables
- Local secrets can be placed in `config/secrets.local.php` for installed/dev instances
- Deployment checklist in `DEPLOYMENT.md`
