# XOrder by AssurIO

Lichte, modulaire OOP PHP + MySQL webapplicatie voor traiteurs, slagers, bakkers en speciaalzaken om feestbestellingen zonder gedoe en in alle zekerheid te beheren, inclusief 1-klik lokale Excel-backup (`.xlsx`) en tijdelijke "Kerstdorp" sticky widget voor `assurio.be`.

## 1. Lokaal starten (met SQLite, zonder extra installatie)

Vanuit de hoofdmap van dit project:

```bash
php -S 127.0.0.1:8787 -t public
```

Open daarna in je browser:

- **Landingspagina + registratie (NL/FR):** `http://127.0.0.1:8787/`
- **AssurIO sticky knop schermpreview:** `http://127.0.0.1:8787/widget-preview`
- **App dashboard (na registratie/login):** `http://127.0.0.1:8787/app`

De lokale `config/config.php` gebruikt standaard SQLite (`storage/xorder.sqlite`). Tabellen worden automatisch aangemaakt bij het eerste verzoek.

## 2. Installatie op Combell hosting (PHP + MySQL)

1. Upload het volledige project naar jouw hostingmap (of wijs het domein rechtstreeks naar de map `public/`). De meegeleverde `.htaccess` in de hoofdmap stuurt verzoeken ook veilig door naar `public/` en blokkeert rechtstreekse toegang tot `config/`, `database/`, `lang/`, `src/`, `storage/` en `views/`.
2. Kopieer `config/config.example.php` naar `config/config.php`.
3. Zet in `config/config.php` de database-driver op `'mysql'` en vul jouw Combell MySQL-gegevens in (`host`, `database`, `username`, `password`).
4. Optioneel: importeer `database/schema.mysql.sql` via phpMyAdmin (dit gebeurt ook automatisch bij de eerste paginalading indien de tabellen nog niet bestaan).

## 3. AssurIO "Kerstdorp" sticky knop integreren

In de map `widget/` vind je de volledige drop-in integratie:

- `widget/assurio-xorder-widget.css`
- `widget/assurio-xorder-widget.js`
- `widget/wordpress-snippet.php` (klaar om te plakken in een mu-plugin of `functions.php` op `assurio.be`)

De knop verschijnt automatisch enkel tussen `startDate` (`2026-10-15`) en `endDate` (`2027-01-05`), detecteert NL of FR uit `<html lang>`, en onthoudt wanneer een bezoeker de knop wegklikt (`×`) in `localStorage`.

## 4. Engels (EN) later activeren

1. Kopieer `lang/en.php.example` naar `lang/en.php` en vertaal alle sleutels uit `lang/nl.php`.
2. Voeg `'en'` toe aan `'supported_locales' => ['nl', 'fr', 'en']` in `config/config.php`.
