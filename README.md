# Medlem - ärendehanteringssystem

Enkelt ärendehanteringssystem för lärarfackförening (Case Management System for Teachers Union).

## Funktioner / Features

- **Användarhantering** – Registrering, inloggning, profiler, valfri tvåfaktorsinloggning (TOTP)
- **Medlemsregister** – CSV-import med automatiska rubrikfixar (Födelsedatum/Arbetsplats), sök, filtrering, sortering och vy för medlemmar som fyller 50 inom 1/3/6 månader
- **Ärendehantering** – Skapa, visa, redigera och ta bort ärenden; snabbvy över egna och tilldelade ärenden med rubrik och handläggare
- **Kommentarer** – Lägg till kommentarer på ärenden
- **Flerspråksstöd** – Svenska och Engelska (i18n)
- **Användarinställningar** – Ljust/Mörkt läge, färgteman, språkval, profilbilder
- **Responsiv design** – Fungerar på alla enheter

## Teknisk stack

- **Backend**: PHP med MySQLi
- **Frontend**: HTML5, CSS3, vanilla JavaScript
- **Databas**: MySQL/MariaDB
- **Språk**: Svenska (standard) med stöd för Engelska

## Installation

### Förutsättningar

- PHP 7.4 eller senare
- MySQL 5.7 eller senare / MariaDB 10.2 eller senare
- Webbserver (Apache/Nginx)

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
   Redigera `config/database.php` och uppdatera dina databasuppgifter:
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

5. **Öppna i webbläsaren**
   Navigera till din installation, t.ex. `http://localhost/medlem`.

### Standard inloggning

- **Användarnamn**: admin
- **Lösenord**: admin123

**OBS**: Ändra admin-lösenordet direkt efter första inloggningen!

## Snabbstart

- Skapa ett nytt ärende via "Nytt ärende"; fyll rubrik, beskrivning, prio och (valfritt) handläggare.
- Se dina ärenden och tilldelade ärenden i flikarna på ärendelistan.
- Importera medlemmar via Admin → Import; därefter sök/filter/sortera i Medlemmar-sidan och använd 50-årsvyerna.

## Projektstruktur

```
medlem/
├── assets/          # CSS, JS, images, uploads
├── config/          # Konfiguration och databas-schema
├── includes/        # Återanvändbara PHP-moduler (auth, cases, members, i18n)
├── lang/            # Översättningar (sv, en)
├── pages/           # Sidor (login, dashboard, cases, members m.fl.)
└── index.php        # Startsida
```

## Säkerhet

- Lösenord hashas med `password_hash()` (bcrypt)
- Prepared statements för SQL
- `htmlspecialchars()` för utdata
- Session-baserad autentisering
- Filuppladdningskontroller (typ och storlek)

## Bidra

Bidrag är välkomna! Skapa en pull request med dina ändringar.

## Licens

MIT-licens.

## Support

För frågor eller problem, skapa ett issue i GitHub-repositoryt.
