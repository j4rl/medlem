# Medlem Project Summary

## Project Snapshot

- **Domain**: Case management + member register for teachers’ union
- **Languages**: PHP, HTML, CSS, JS
- **i18n**: Swedish (default), English
- **Key data**: Member records (encrypted at rest except IDs), cases, comments

## Highlights

1) **User Platform**
- Authentication, profiles, avatars, personal settings (theme, primary color, language)

2) **Case Flow**
- Create/edit cases with priority and assignment
- Simplified list scoped to the user: related/created/assigned tabs, search and status chips
- Quick cards showing own cases and assigned cases with rubrik + handläggare
- Comments with attribution and timestamps

3) **Member Directory**
- CSV import with robust header normalization (fixes Födelsedatum/Arbetsplats garbling)
- Search, filter (arbetsplats/medlemsform/befattning/verksamhetsform), and sort on all columns
- Special views for members turning 50 within 1, 3, or 6 months

4) **Security & Data**
- Passwords via `password_hash` (bcrypt)
- Prepared statements, escaped output, session-based auth
- Member fields encrypted at rest (AES-256-GCM) except IDs/medlemsnummer
- Optional TOTP two-factor authentication for logins

5) **UX**
- Responsive layout, light/dark themes, configurable primary color
- Language toggle (sv/en)

## Deployment Notes

- Requires PHP 7.4+, MySQL/MariaDB, web server (Apache/Nginx)
- Import schema: `config/setup.sql`
- Configure DB credentials in `config/database.php`
- Set `DATA_ENCRYPTION_KEY` (32-byte raw/hex/base64) for member data encryption
- Ensure `assets/uploads/profiles` is writable

## Recent Additions

- Member import now maps Födelsedatum and Arbetsplats correctly even with mangled CSV encodings
- New members page with search/filter/sort + 50th birthday quick views
- Cases list revamped for simpler tracking of own and assigned cases
