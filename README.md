# Medlem - ärendehanteringssystem

Medlem är ett PHP/MySQL-baserat ärendehanteringssystem med medlemsregister, byggt för en lärarfacklig miljö.

## Funktioner / Features

- **Användarhantering** – Registrering, inloggning, profiler, adminhantering, CSV-import av användare och valfri tvåfaktorsinloggning (TOTP)
- **Medlemsregister** – CSV-import med automatiska rubrikfixar (Födelsedatum/Arbetsplats), importhistorik, sök, filtrering, sortering och vy för medlemmar som fyller 50 inom 1/3/6 månader
- **Ärendehantering** – Skapa, visa, redigera och ta bort ärenden; stöd för flera handläggare, snabbvy över egna och tilldelade ärenden samt notiser sedan senaste inloggning
- **Rich text** – TinyMCE för ärendebeskrivningar med serversidad HTML-sanerare
- **Kommentarer** – Lägg till kommentarer på ärenden
- **Flerspråksstöd** – Svenska och Engelska (i18n)
- **Användarinställningar** – Ljust/Mörkt läge, färgteman, språkval, profilbilder
- **Temahantering** – Admin kan skapa, ändra och ta bort färgteman
- **Responsiv design** – Fungerar på alla enheter

## Teknisk stack

- **Backend**: PHP med MySQLi
- **Frontend**: HTML5, CSS3, vanilla JavaScript
- **Databas**: MySQL/MariaDB
- **Editor**: TinyMCE (lokalt bundlad)
- **Språk**: Svenska (standard) med stöd för Engelska

## Installation

### Förutsättningar

- PHP 7.4 eller senare
- MySQL 5.7 eller senare / MariaDB 10.2 eller senare
- Webbserver (Apache/Nginx)
- PHP-tillägg: `mysqli`, `openssl`, `dom`; `mbstring` och `iconv` rekommenderas för robust CSV-rubrikhantering

### Steg för steg

1. **Klona repositoryt**
   ```bash
   git clone https://github.com/j4rl/medlem.git
   cd medlem
   ```

2. **Skapa databas**
   ```bash
   mysql -u root -p < config/setup.sql
   ```
   eller importera filen `config/setup.sql` via phpMyAdmin.

3. **Konfigurera databas**
   Kopiera `config/database.example.php` till `config/database.php` och uppdatera dina databasuppgifter. Värdena kan också sättas via miljövariablerna `DB_HOST`, `DB_USER`, `DB_PASS` och `DB_NAME`.
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'medlem_db');
   ```

4. **Sätt rättigheter**
   ```bash
   chmod 755 assets/uploads/profiles
   ```

5. **Sätt krypteringsnyckel**
   Sätt `DATA_ENCRYPTION_KEY` till en unik 32-byte-nyckel (raw, hex eller base64) innan medlemsimport eller ärendehantering används. Rekommenderat är miljövariabel i produktion; lokalt kan `config/secrets.local.example.php` kopieras till `config/secrets.local.php`.

6. **Skapa första admin**
   Skapa ett vanligt konto via registreringen och höj det sedan till admin i databasen:
   ```sql
   UPDATE tbl_users SET userlevel = 1000 WHERE username = 'ditt_anvandarnamn';
   ```
   `config/setup.sql` skapar inte längre ett delat standardkonto.

7. **Kontrollera installationen vid behov**
   `install.php` är låst som standard. Sätt tillfälligt miljövariabeln `ALLOW_INSTALL=1` om installationskontrollen behöver öppnas, och stäng av den igen direkt efter kontrollen.

8. **Öppna i webbläsaren**
   Navigera till din installation, t.ex. `http://localhost/medlem`.

## Snabbstart

- Skapa ett nytt ärende via "Nytt ärende"; fyll rubrik, rich text-beskrivning, prio och en eller flera handläggare.
- Se relaterade, skapade och tilldelade ärenden i flikarna på ärendelistan. Navigeringen visar notiser för nya tilldelningar och uppdateringar sedan senaste inloggning.
- Importera medlemmar via Admin → Import; därefter sök/filter/sortera i Medlemmar-sidan och använd 50-årsvyerna.
- Hantera användare via Admin → Användare eller importera dem med CSV-rubrikerna `username;email;password;name;phone;lang;colorscheme;userlevel`.
- Hantera färgpaletter via Admin → Teman och låt användare välja tema i Inställningar.

## Projektstruktur

```
medlem/
├── assets/          # CSS, JS, images, uploads
├── config/          # Konfiguration och databas-schema
├── includes/        # Återanvändbara PHP-moduler (auth, cases, members, i18n)
├── lang/            # Översättningar (sv, en)
├── pages/           # Sidor (login, dashboard, cases, members m.fl.)
├── tinymce/         # Lokalt bundlad TinyMCE-editor
└── index.php        # Startsida
```

## Säkerhet

- Lösenord hashas med `password_hash()` (bcrypt)
- Prepared statements för SQL
- `htmlspecialchars()` för utdata
- Session-baserad autentisering med CSRF-token för POST-flöden
- Per-ärende-behörighet för direktlänkar och API
- Filuppladdningskontroller (typ och storlek)
- AES-256-GCM-kryptering för medlemsdata och ärendefält när `DATA_ENCRYPTION_KEY` är satt
- Enkel rate limiting för inloggningsförsök via lokala temporära filer

## Bidra

Bidrag är välkomna. Se `CONTRIBUTING.md` för kodstandard, testförväntningar och dokumentationskrav.

## Licens

MIT-licens.

## Support

För frågor eller problem, skapa ett issue i GitHub-repositoryt.
