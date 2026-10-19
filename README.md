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

Il seeder popola anche la tabella dei codici catastali (comuni italiani e stati esteri, da `database/seeders/data/cadastral_codes.json`) e crea il primo amministratore (`maio.cesco@gmail.com`) con una password provvisoria generata casualmente e stampata a console (oppure letta da `ADMIN_PASSWORD` se impostata nell'ambiente): **annotala subito**, non viene mostrata di nuovo. Al primo accesso il sistema obbliga il cambio password.

Data di nascita, luogo di nascita e codice fiscale del primo amministratore sono dati personali reali: non sono nel codice sorgente. Per popolarli imposta in `.env` (facoltativo, si possono anche completare dal pannello dopo il primo accesso):

```
ADMIN_BIRTH_DATE=gg-mm-aaaa
ADMIN_BIRTH_PLACE_BELFIORE=...    # codice catastale (belfiore) del comune/stato di nascita
ADMIN_FISCAL_CODE=...
```

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

La tabella `cadastral_codes` (comuni italiani, incluso lo storico dei cambi nome, e stati esteri) è popolata da `CadastralCodeSeeder` a partire da `database/seeders/data/cadastral_codes.json`, il file ufficiale fornito dal cliente. Il seeder è idempotente: se la tabella è già popolata non la tocca, per non invalidare i riferimenti dagli utenti già creati.

La validazione del codice fiscale (`FiscalCodeService`) verifica la coerenza con nome, cognome, data e luogo di nascita, incluso il carattere di controllo e i casi di omocodia. Il sesso non è un campo dell'anagrafica (non richiesto dalla specifica): viene dedotto provando entrambe le varianti del giorno di nascita codificate nel codice fiscale stesso.

## Tesseramenti e sedi

- **Tesseramenti**: un atleta può avere più tesseramenti (anche per la stessa disciplina con enti diversi, mai due identici); il tipo di società (Corriferrara, Corriferrara Bike, altra società) vincola le discipline ammesse; FIDAL è ammesso solo per l'atletica leggera ed è sempre agonistico (campo bloccato lato server, non solo nell'interfaccia). Le società esterne ("altra società") si scelgono o si creano al volo dalla stessa interfaccia.
- **Sedi Runner's School**: CRUD completo; l'eliminazione di una sede con atleti assegnati mostra una pagina di conferma con il conteggio e un link all'elenco filtrato per riassegnarli, invece di un semplice popup di conferma.
- **Filtri atleti**: l'elenco admin è filtrabile (e i filtri sono combinabili) per società, ente di tesseramento, numero di tessera, disciplina e sede.

## Certificati e copertura

`CertificateCoverageService` è l'unica fonte di verità per le regole di copertura (requisiti per disciplina agonistica, requisito generico per l'attività non agonistica, nozione di certificato "superato"). È usato da profilo atleta, dashboard amministratore e, dalla Fase 6, dai promemoria.

- **Upload**: l'atleta carica i propri certificati (scadenza futura, tipo, disciplina se agonistico, file immagine/PDF, dimensione max configurabile con `CERTIFICATE_MAX_SIZE_MB`, 10 MB di default); l'amministratore può caricare per conto di un atleta dalla sua scheda.
- **FIDAL**: un atleta con tesseramento FIDAL non può caricare (né un amministratore approvare) un certificato non agonistico; viceversa non si può aggiungere un tesseramento FIDAL senza un certificato agonistico già valido per l'atletica leggera — chiude il punto lasciato in sospeso in Fase 3.
- **Approvazione/rifiuto**: l'amministratore può correggere scadenza/tipo/disciplina in approvazione; il rifiuto richiede una motivazione obbligatoria. Entrambi inviano un'email (mailer di default, in attesa del sistema PEC/classico di Fase 5).
- **File**: solo su disco privato (`storage/app/private`), nomi non prevedibili, accesso tramite rotta protetta da Policy; ogni visualizzazione è registrata nella tabella `audit_logs`.
- **Dashboard amministratore**: certificati da approvare, atleti non in regola (con il motivo), certificati in scadenza nei prossimi 30 giorni — ogni elenco filtrabile ed esportabile in CSV.

## Deploy su hosting condiviso

Non essendoci un worker della coda sempre attivo, sia la coda sia i comandi schedulati (promemoria, controlli di conservazione) vengono elaborati dal cron tramite lo scheduler di Laravel. Configura sul cron del provider:

```cron
* * * * * cd /percorso/del/progetto && php artisan schedule:run >> /dev/null 2>&1
```

Lo scheduler si occupa di eseguire `queue:work --stop-when-empty` ogni minuto (vedi `routes/console.php`) e, dalla Fase 6 in poi, i comandi di promemoria e conservazione certificati.

Checklist deliverability email (SPF/DKIM/DMARC) e istruzioni di deploy dettagliate verranno aggiunte in Fase 7.
