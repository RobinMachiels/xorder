# Meewerken aan XOrder (klasproject)

Iedereen bouwt een eigen feature. Om merge-conflicten te vermijden raakt niemand gezamenlijke bestanden aan.

## Wat mag je aanpassen?

| Mag | Mag niet |
| --- | --- |
| `features/<voornaam>.php` | alles in `src/`, `views/`, `lang/`, `config/`, `database/` |
| `public/features/<voornaam>/` (css, js, afbeeldingen) | `public/index.php`, `public/assets/`, `.htaccess` |
| | `README.md`, `.github/`, bestanden van een andere cursist |

`<voornaam>` is in kleine letters, zonder spaties of accenten (bv. `jan`, `anne-sophie`).
Je pagina verschijnt vanzelf op `/features/<voornaam>` en in het menu **Features**. Je hoeft geen route of menu toe te voegen.

Een automatische controle (GitHub Actions) keurt je pull request af als je een ander bestand aanraakt.

## Zo werk je

```bash
# 1. Fork de repo op GitHub, clone daarna JOUW fork
git clone https://github.com/<jouw-account>/xorder.git
cd xorder

# 2. Eigen branch
git checkout -b feature/<voornaam>

# 3. Start lokaal (SQLite, geen installatie nodig)
php -S 127.0.0.1:8787 -t public

# 4. Kopieer het voorbeeld
cp features/voorbeeld.php features/<voornaam>.php
cp -r public/features/voorbeeld public/features/<voornaam>

# 5. Committen en pushen naar je fork
git add features/<voornaam>.php public/features/<voornaam>
git commit -m "feat(<voornaam>): korte beschrijving"
git push -u origin feature/<voornaam>
```

Open daarna op GitHub een pull request van `feature/<voornaam>` naar `main` van de originele repo.

Gebruik `git add` altijd met je eigen paden, nooit `git add .`: zo neem je niet per ongeluk een ander bestand mee.

## Afspraken in je code

- Tabellen beginnen met je naam: `jan_allergenen`.
- CSS-klassen beginnen met je naam: `.jan-kaart`.
- Gegevens altijd filteren op `$user->id`.
- Tekst die je toont altijd escapen met `$e(...)`.
- Geen inline JavaScript (`<script>...</script>` of `onclick`): de beveiliging (CSP) blokkeert dat. Zet je code in `public/features/<voornaam>/app.js`.
- Elk POST-formulier krijgt `<?= $csrfField() ?>`.

Bekijk `features/voorbeeld.php`: alles wat je ter beschikking hebt staat bovenaan uitgelegd.
