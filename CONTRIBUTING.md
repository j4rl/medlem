# Bidra till Medlem / Contributing to Medlem

Tack för att du vill bidra till Medlem! / Thank you for wanting to contribute to Medlem!

## Hur man bidrar / How to Contribute

1. **Forka repositoryt** / Fork the repository
2. **Skapa en branch** för din feature / Create a branch for your feature
   ```bash
   git checkout -b feature/min-nya-feature
   ```
3. **Gör dina ändringar** / Make your changes
4. **Testa dina ändringar** / Test your changes
5. **Commit dina ändringar** / Commit your changes
   ```bash
   git commit -m "Lägg till min nya feature"
   ```
6. **Push till din branch** / Push to your branch
   ```bash
   git push origin feature/min-nya-feature
   ```
7. **Skapa en Pull Request** / Create a Pull Request

## Kodstandard / Code Standards

- Använd PHP 7.4+ syntax / Use PHP 7.4+ syntax
- Följ PSR-12 kodningsstandard / Follow PSR-12 coding standard
- Kommentera komplex kod / Comment complex code
- Skriv beskrivande commit-meddelanden / Write descriptive commit messages
- Testa all ny funktionalitet / Test all new functionality
- Kör `php -l <fil>` på berörda PHP-filer / Run `php -l <file>` on touched PHP files
- Uppdatera dokumentation och översättningar när flöden ändras / Update docs and translations when flows change

## Säkerhet / Security

- Använd alltid prepared statements för databas-queries / Always use prepared statements for database queries
- Escapea all output med `htmlspecialchars()` / Escape all output with `htmlspecialchars()`
- Hasha lösenord med `password_hash()` / Hash passwords with `password_hash()`
- Validera och sanitera all user input / Validate and sanitize all user input

## Bug-rapporter / Bug Reports

När du rapporterar bugs, inkludera:
- En tydlig beskrivning av problemet / A clear description of the problem
- Steg för att reproducera / Steps to reproduce
- Förväntat vs faktiskt beteende / Expected vs actual behavior
- Skärmdumpar om relevant / Screenshots if relevant
- PHP- och MySQL-version / PHP and MySQL version

## Feature-förslag / Feature Suggestions

Vi välkomnar nya idéer! När du föreslår features:
- Beskriv tydligt vad featuren gör / Clearly describe what the feature does
- Förklara varför den är användbar / Explain why it's useful
- Föreslå en implementation om möjligt / Suggest an implementation if possible

## Frågor? / Questions?

Skapa ett issue på GitHub eller kontakta maintainers. / Create an issue on GitHub or contact the maintainers.
