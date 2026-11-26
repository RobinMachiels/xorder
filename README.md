# XOrder by AssurIO

Lichte, modulaire OOP PHP + MySQL webapplicatie voor traiteurs, slagers, bakkers en speciaalzaken om feestbestellingen zonder gedoe en in alle zekerheid te beheren, inclusief 1-klik lokale Excel-backup (`.xlsx`).

Live: https://assurio.be/xorder/

## 1. Lokaal starten (SQLite, zonder extra installatie)

```bash
php -S 127.0.0.1:8787 -t public
```

- Landingspagina + registratie (NL/FR): `http://127.0.0.1:8787/`
- App dashboard (na registratie/login): `http://127.0.0.1:8787/app`
- Features van de klas: `http://127.0.0.1:8787/features`

Zonder `config/config.php` gebruikt de app `config/config.example.php` (SQLite in `storage/xorder.sqlite`, debug aan). Tabellen worden automatisch aangemaakt bij het eerste verzoek.

## 2. Features van cursisten

Elke cursist maakt `features/<voornaam>.php` met eigen assets in `public/features/<voornaam>/`.
De pagina verschijnt vanzelf op `/features/<voornaam>` en in het menu. Lees [CONTRIBUTING.md](CONTRIBUTING.md).
Op productie staan features uit (`features.enabled = false`) en worden ze niet mee gedeployed.

## 3. Productie

Zie [DEPLOY.md](DEPLOY.md). Kort: `bash bin/deploy-assurio.sh --dry-run`, daarna zonder `--dry-run`.

## 4. Seizoensknop op assurio.be

Zie [widget/README.md](widget/README.md). De bestanden staan in `public/widget/`.

## 5. Extra's

- **Wachtwoord vergeten:** `/forgot` en `/reset/<token>` (token 1 uur geldig, eenmalig, enkel gehasht opgeslagen). Links in mails komen uit `app.url`, nooit uit de Host-header.
- **Brute-force bescherming:** max 8 loginpogingen per e-mail en IP per 15 minuten, max 5 resetaanvragen per IP per uur.
- **Juridische pagina's:** `/privacy` en `/cookies` (NL/FR).
- **Statistieken seizoensknop:** `php bin/widget-stats.php`.
- **Engels later activeren:** kopieer `lang/en.php.example` naar `lang/en.php`, vertaal, en voeg `'en'` toe aan `supported_locales`.
