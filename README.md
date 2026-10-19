# Gestionale Certificati Medici – A.S.D. Corriferrara

Gestionale web per i certificati medici sportivi degli atleti dell'A.S.D. Corriferrara. La specifica completa è in [`docs/SPECIFICHE.md`](docs/SPECIFICHE.md).

Stack: Laravel 12, MySQL, Breeze (Blade) senza registrazione pubblica, Tailwind CSS, coda con driver `database` elaborata dallo scheduler (adatto a hosting condiviso senza worker permanente), test con Pest.

## Setup ambiente locale

Requisiti: PHP ≥ 8.2, Composer, MySQL, Node.js (per gli asset).

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Configura in `.env` le credenziali del database MySQL locale (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). In sviluppo su macOS senza un MySQL già disponibile è stato usato Homebrew:

```bash
brew install mysql
brew services start mysql
mysql -u root -e "CREATE DATABASE corriferrara_gestionale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Poi:

```bash
php artisan migrate --seed
```

Il seeder crea il primo amministratore (`maio.cesco@gmail.com`) con una password provvisoria generata casualmente e stampata a console (oppure letta da `ADMIN_PASSWORD` se impostata nell'ambiente): **annotala subito**, non viene mostrata di nuovo. Al primo accesso il sistema obbliga il cambio password.

### Test

I test usano un database MySQL dedicato, configurato in `.env.testing` (non versionato, come `.env`):

```bash
mysql -u root -e "CREATE DATABASE corriferrara_gestionale_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cp .env.testing.example .env.testing
php artisan key:generate --env=testing
# imposta DB_USERNAME/DB_PASSWORD in .env.testing
php artisan test
```

Nota: quando `.env.testing` esiste, Laravel lo usa **al posto di** `.env` (non in aggiunta) per l'ambiente di test — per questo contiene una propria `APP_KEY`.

### Avvio in locale

```bash
php artisan serve
npm run dev        # per gli asset in sviluppo, con hot reload
php artisan schedule:work     # scheduler (promemoria, elaborazione coda) in locale
```

## Dati catastali per la validazione del codice fiscale

La Fase 2 richiede una tabella dei codici catastali (comuni italiani e stati esteri) per validare il codice fiscale degli atleti. Il file ufficiale verrà importato con un seeder dedicato quando disponibile.

## Deploy su hosting condiviso

Non essendoci un worker della coda sempre attivo, sia la coda sia i comandi schedulati (promemoria, controlli di conservazione) vengono elaborati dal cron tramite lo scheduler di Laravel. Configura sul cron del provider:

```cron
* * * * * cd /percorso/del/progetto && php artisan schedule:run >> /dev/null 2>&1
```

Lo scheduler si occupa di eseguire `queue:work --stop-when-empty` ogni minuto (vedi `routes/console.php`) e, dalla Fase 6 in poi, i comandi di promemoria e conservazione certificati.

Checklist deliverability email (SPF/DKIM/DMARC) e istruzioni di deploy dettagliate verranno aggiunte in Fase 7.
