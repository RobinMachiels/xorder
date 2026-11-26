# Combell deploy checklist (XOrder by AssurIO)

Dit pakket wordt NIET automatisch geupload. Volg de stappen zelf wanneer je klaar bent.

## 0. Pakket bouwen (lokaal)

```bash
bash bin/build-deploy.sh
```

Resultaat: `dist/xorder-combell-<datum>.zip`. Bevat geen `config/config.php`, geen lokale SQLite en geen `.git`.

## 1. Voorbereiding in het Combell controlepaneel

- [ ] Subdomein of domein aanmaken (bv. `xorder.assurio.be`) met **PHP 8.1 of hoger** (8.2 of 8.3 aanbevolen).
- [ ] HTTPS (Let's Encrypt) activeren voor dat domein.
- [ ] MySQL-database + gebruiker aanmaken. Noteer host, databasenaam, gebruikersnaam, wachtwoord.
- [ ] Een mailadres aanmaken voor de afzender van reset-mails (bv. `noreply@assurio.be`).

## 2. Uploaden

- [ ] Zip uitpakken in de webroot van het (sub)domein, via SFTP of de Combell bestandsbeheerder.
- [ ] Controleer dat `.htaccess` (verborgen bestand) mee is geupload, zowel in de root als in `public/`.
- [ ] Kan je de document root naar `public/` laten wijzen, doe dat dan (veiligst). Zo niet: de root `.htaccess` stuurt alles door naar `public/`.
- [ ] `storage/` moet schrijfbaar zijn voor PHP (rechten 775), enkel nodig als je SQLite gebruikt.

## 3. Configuratie

- [ ] `config/config.example.php` kopieren naar `config/config.php`.
- [ ] Invullen: `db.driver = 'mysql'`, `host`, `database`, `username`, `password`.
- [ ] `app.env = 'production'` en `app.debug = false` (anders toont de reset-pagina de resetlink rechtstreeks).
- [ ] `app.mail_from` op het aangemaakte mailadres zetten.
- [ ] `app.base_url` leeg laten bij (sub)domein-root, of `'/xorder'` bij een submap.

## 4. Database

Tabellen worden automatisch aangemaakt bij het eerste verzoek. Wil je het liever handmatig:

- [ ] `database/schema.mysql.sql` importeren via phpMyAdmin.

## 5. Rooktest na uploaden

- [ ] `/` laadt in NL en `/?lang=fr` in FR.
- [ ] Registreren met testaccount, voorbeelddata verschijnt, dashboard laadt.
- [ ] Bestelling aanmaken, keukenlijst klopt.
- [ ] Excel-backup downloaden en openen in Excel (4 tabbladen).
- [ ] "Wachtwoord vergeten": mail komt aan, link werkt, nieuw wachtwoord werkt.
- [ ] `/privacy` en `/cookies` laden.
- [ ] `/widget/assurio-xorder-widget.js` en `.css` zijn bereikbaar.
- [ ] Testaccount verwijderen of wissen.

## 6. Sticky knop op assurio.be (pas als alles hierboven klopt)

- [ ] `widget/wordpress-snippet.php` plakken in een mu-plugin op assurio.be (back-up maken van het bestand dat je aanpast).
- [ ] `$baseWidgetUrl` en `$targetLandingUrl` in het snippet controleren op het echte domein.
- [ ] Datums controleren: `2026-10-15` tot `2027-01-05`.
- [ ] Na enkele dagen stats bekijken: `php bin/widget-stats.php` (op de server of lokaal met kopie van de database).

## 7. Na het seizoen

- [ ] Snippet uitzetten of laten verlopen op `endDate`. De productpagina blijft online.
