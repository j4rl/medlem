# Medlem - Feature Overview

## Core Features

### 1. User Authentication
- Registration with username, email, and password
- Secure login and session management
- Logout to clear sessions

### 2. User Profiles
- Full name, email, immutable username
- Profile pictures (JPG/PNG/GIF, 5MB, old picture cleanup)

### 3. User Settings
- Light/Dark theme with instant preview
- Primary color selection (8 presets)
- Language selection (sv/en) applied across the UI

### 4. Case Management

#### Case Creation
- Title and description (required)
- Priority: low, medium, high, urgent
- Optional assignment to a handler
- Automatic case number (CASE-YYYY-XXXX)

#### Case View & Edit
- Creator and assignee profiles
- Status workflow (new, in progress, resolved, closed)
- Update title, description, status, priority, and assignment

#### Case List
- User-scoped view: related / created / assigned tabs
- Status chips (new, in progress, resolved) and quick search
- Side cards showing the user's own cases and assigned cases with rubrik + handläggare

#### Case Comments
- Add/view comments with attribution and timestamps

### 5. Members Directory & Import
- CSV import with robust header normalization (Födelsedatum, Arbetsplats, etc.)
- Search by name; filter by arbetsplats, medlemsform, befattning, verksamhetsform
- Sortable columns across member fields
- Quick views for members turning 50 within 1, 3, or 6 months

### 6. Dashboard
- Totals for open/resolved/etc.
- Recent cases list with status and priority indicators

### 7. Internationalization (i18n)
- Swedish (default) and English
- Language switching from settings and login/register

### 8. Responsive Design
- Mobile-friendly layouts using flex/grid
- Touch-friendly targets and spacing

### 9. Security Features
- Password hashing via `password_hash` (bcrypt)
- Prepared statements for database access
- Escaped output (`htmlspecialchars`)
- Session-based authentication
- File upload validation (type/size)
- Member data encrypted at rest (AES-256-GCM) except IDs/medlemsnummer

### 10. Database Schema (high level)
- `tbl_users`: accounts, roles, settings
- `tbl_members`: encrypted member data
- `tbl_cases`: cases with assignment and status
- `tbl_comments`: case comments
- `tbl_member_imports`: import history

### 11. User Interface
- Modern card-based layout
- Light/dark themes with customizable primary color
- Consistent badges for status/priority

### 12. Additional Notes
- Installation helper via `config/setup.sql`
- Deployment checklist in `DEPLOYMENT.md`
