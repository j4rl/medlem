# Medlem Project Summary

## Project Snapshot

- **Domain**: Case management + member register for teachers’ union
- **Languages**: PHP, HTML, CSS, JS
- **i18n**: Swedish (default), English
- **Key data**: Member records, sensitive case fields, cases, comments, user settings
- **Runtime**: PHP 7.4+, MySQL/MariaDB, MySQLi, OpenSSL AES-256-GCM

## Highlights

1) **User Platform**
- Authentication, profiles, avatars, personal settings (theme, color palette, language)
- Optional TOTP 2FA and admin-side 2FA reset
- Admin user management with create/edit/delete/password reset plus CSV import

2) **Case Flow**
- Create/edit cases with priority, rich text body, member snapshot, and one or more handlers
- Simplified list scoped to the user: related/created/assigned tabs, search and status chips
- Quick cards showing own cases and assigned cases with rubrik + handläggare
- Header badges and list annotations for new assignments and recent updates since previous login
- Comments with attribution and timestamps

3) **Member Directory**
- CSV import with robust header normalization (fixes Födelsedatum/Arbetsplats garbling)
- Import history and automatic `Inaktiv` marking for members absent from the latest import
- Search, filter (arbetsplats/medlemsform/befattning/verksamhetsform), and sort on all columns
- Special views for members turning 50 within 1, 3, or 6 months

4) **Security & Data**
- Passwords via `password_hash` (bcrypt)
- Prepared statements, escaped output, CSRF tokens, session-based auth
- Member fields and sensitive case fields encrypted at rest (AES-256-GCM) except IDs/medlemsnummer
- Per-case access checks for pages and API actions
- Server-side rich text sanitization for TinyMCE content
- Optional TOTP two-factor authentication for logins

5) **UX**
- Responsive layout, light/dark themes, selectable/admin-managed color palettes
- Language toggle (sv/en)
- Local TinyMCE editor for case text

## Deployment Notes

- Requires PHP 7.4+, MySQL/MariaDB, web server (Apache/Nginx)
- Required PHP extensions: `mysqli`, `openssl`, `dom`; `mbstring`/`iconv` recommended
- Import schema: `config/setup.sql`
- Configure DB credentials in `config/database.php` or via `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
- Set `DATA_ENCRYPTION_KEY` (32-byte raw/hex/base64) for member and case data encryption
- Local installed/dev secrets can live in `config/secrets.local.php`
- Ensure `assets/uploads/profiles` is writable
- `install.php` is disabled unless `ALLOW_INSTALL=1`
- First admin is created manually; admin access requires `userlevel` 1000+

## Recent Additions

- Admin user management, user CSV import, and admin theme manager
- Rich text editing for cases with TinyMCE and server-side HTML sanitization
- Multiple handlers per case and activity indicators since last login
- Member import now maps Födelsedatum and Arbetsplats correctly even with mangled CSV encodings
- New members page with search/filter/sort + 50th birthday quick views
- Cases list revamped for simpler tracking of own and assigned cases
