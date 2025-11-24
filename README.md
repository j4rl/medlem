# Medlem - Ärendehanteringssystem

Enkelt ärendehanteringssystem för lärarfackförening (Case Management System for Teachers Union)

## Funktioner / Features

- **Användarhantering** - Registrering, inloggning, profiler
- **Ärendehantering** - Skapa, visa, redigera och ta bort ärenden
- **Kommentarer** - Lägg till kommentarer på ärenden
- **Flerspråksstöd** - Svenska och Engelska (i18n)
- **Användarinställningar**:
  - Ljust/Mörkt läge
  - Anpassningsbara färgteman
  - Språkval
  - Profilbilder
- **Responsiv design** - Fungerar på alla enheter

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
   
   Eller importera filen `config/setup.sql` via phpMyAdmin

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
   
   Navigera till din installation, t.ex. `http://localhost/medlem`

### Standard inloggning

- **Användarnamn**: admin
- **Lösenord**: admin123

⚠️ **OBS**: Ändra admin-lösenordet direkt efter första inloggningen!

## Projektstruktur

```
medlem/
├── assets/
│   ├── css/          # Stilmallar
│   ├── js/           # JavaScript-filer
│   ├── images/       # Bilder och ikoner
│   └── uploads/      # Uppladdade filer
├── config/
│   ├── database.php  # Databaskonfiguration
│   └── setup.sql     # Databas-schema
├── includes/
│   ├── auth.php      # Autentisering
│   ├── cases.php     # Ärendehantering
│   ├── user.php      # Användarhantering
│   ├── i18n.php      # Internationalisering
│   ├── header.php    # Header-template
│   └── footer.php    # Footer-template
├── lang/
│   ├── sv.php        # Svenska översättningar
│   └── en.php        # Engelska översättningar
├── pages/
│   ├── login.php           # Inloggningssida
│   ├── register.php        # Registreringssida
│   ├── dashboard.php       # Instrumentpanel
│   ├── cases.php           # Ärendelista
│   ├── case-create.php     # Skapa ärende
│   ├── case-view.php       # Visa ärende
│   ├── case-edit.php       # Redigera ärende
│   ├── profile.php         # Användarprofil
│   └── settings.php        # Inställningar
└── index.php         # Startsida
```

## Användning

### Skapa ett nytt ärende

1. Logga in i systemet
2. Klicka på "Nytt ärende" från instrumentpanelen eller ärendelistan
3. Fyll i titel, beskrivning, prioritet
4. Tilldela ärendet till en användare (valfritt)
5. Klicka "Skapa ärende"

### Ändra tema och färger

1. Gå till Inställningar från användarmenyn
2. Välj ljust eller mörkt läge
3. Välj önskad primärfärg
4. Välj språk
5. Klicka "Spara"

### Uppdatera profilbild

1. Gå till "Min profil" från användarmenyn
2. Klicka på "Välj fil" under profilbilden
3. Välj en bild (JPG, PNG, GIF, max 5MB)
4. Klicka "Byt bild"

## Säkerhet

- Lösenord hashas med PHP's `password_hash()` (bcrypt)
- SQL-injektionsskydd via prepared statements
- XSS-skydd via `htmlspecialchars()`
- Session-baserad autentisering
- Filuppladdningsskydd (filtyp och storlek)

## Bidra

Bidrag är välkomna! Vänligen skapa en pull request med dina ändringar.

## Licens

Detta projekt är open source och tillgängligt under MIT-licensen.

## Support

För frågor eller problem, skapa ett issue i GitHub-repositoryt.
