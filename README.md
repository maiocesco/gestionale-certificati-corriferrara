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

`CertificateCoverageService` è l'unica fonte di verità per le regole di copertura (requisiti per disciplina agonistica, requisito generico per l'attività non agonistica, nozione di certificato "superato"). È usato da profilo atleta, dashboard amministratore e dai promemoria (vedi "Promemoria e avvisi di scadenza").

- **Upload**: l'atleta carica i propri certificati (scadenza futura, tipo, disciplina se agonistico, file immagine/PDF, dimensione max configurabile con `CERTIFICATE_MAX_SIZE_MB`, 10 MB di default); l'amministratore può caricare per conto di un atleta dalla sua scheda.
- **FIDAL**: un atleta con tesseramento FIDAL non può caricare (né un amministratore approvare) un certificato non agonistico; viceversa non si può aggiungere un tesseramento FIDAL senza un certificato agonistico già valido per l'atletica leggera — chiude il punto lasciato in sospeso in Fase 3.
- **Approvazione/rifiuto**: l'amministratore può correggere scadenza/tipo/disciplina in approvazione; il rifiuto richiede una motivazione obbligatoria. Entrambi inviano un'email dall'account "classico", con testo configurabile da pannello (vedi "Configurazione email").
- **File**: solo su disco privato (`storage/app/private`), nomi non prevedibili, accesso tramite rotta protetta da Policy; ogni visualizzazione è registrata nella tabella `audit_logs`.
- **Dashboard amministratore**: certificati da approvare, atleti non in regola (con il motivo), certificati in scadenza nei prossimi 30 giorni — ogni elenco filtrabile ed esportabile in CSV.

## Configurazione email

Due account di invio, configurabili dal pannello amministrativo (**Account email**) con fallback su `.env` finché non sono impostati:

- **PEC della società** → promemoria di scadenza, diffida, sospensione parziale.
- **Email "classica"** → benvenuto, password provvisorie, recupero password, esito verifica certificati, riepilogo giornaliero admin.

Le credenziali SMTP sono salvate cifrate (cast `encrypted`). Il pulsante "Invia email di prova" di ciascun account invia subito (non in coda) un messaggio di verifica, per dare un esito immediato in caso di configurazione errata.

I testi delle 10 email previste dalla specifica più la firma comune sono modificabili dal pannello (**Testi email**), con elenco dei segnaposto disponibili per ciascuna, anteprima con dati di esempio, invio di prova a se stessi e ripristino al testo di default. Il corpo si scrive in Markdown semplice; HTML e testo semplice sono generati automaticamente, con escaping dei valori sostituiti.

## Promemoria e avvisi di scadenza

Un comando schedulato giornaliero (`certificates:send-reminders`, 08:00 `Europe/Rome`) controlla ogni certificato approvato e accoda, **per certificato**, al più un avviso per esecuzione — quello ancora dovuto più urgente tra: promemoria a 30/10/3 giorni dalla scadenza, oppure il giorno stesso della scadenza una **diffida** (nessuna copertura residua) o una **sospensione parziale** (restano altre attività coperte, elencate nel testo). Se lo scheduler resta fermo per qualche giorno, al rientro invia solo l'avviso ancora rilevante, mai tutti quelli nel frattempo superati.

Ogni invio passa dalla coda (`App\Jobs\SendCertificateNotification`, fino a 3 tentativi con backoff) e viene tracciato in `certificate_notifications` (in coda / inviato / fallito, con il messaggio di errore), visibile agli amministratori nella pagina **Log invii**, filtrabile per stato — così un avviso non è mai inviato due volte e un errore di configurazione SMTP resta visibile. Un certificato con una copertura già superata da uno più recente (stesso ambito, scadenza più lontana) non riceve più avvisi.

Ogni amministratore può attivare dal proprio profilo un **riepilogo giornaliero** via email (preferenza personale, disattivata di default): conteggio dei certificati da approvare e di quelli in scadenza nei prossimi 30 giorni, inviato dal comando schedulato `admins:send-daily-digest` (08:05) solo se quel giorno c'è davvero qualcosa da segnalare.

## Conservazione dei certificati

`RetentionService` è l'unica fonte di verità per la politica di conservazione. Il periodo (in anni, dalla **data di scadenza** del certificato) dipende dall'ente di tesseramento dell'atleta — se ne ha più di uno vale il più lungo tra quelli applicabili, senza tesseramenti si applica "Nessun tesseramento" — ed è configurabile dal pannello (**Conservazione**, default FIDAL 3 anni / CSI 5 / UISP 10 / nessun tesseramento 10, come da specifica). I certificati validi o in attesa di verifica non sono mai eliminabili, qualunque sia la loro età.

- Il comando mensile `certificates:retention-notify` (il giorno 1 di ogni mese) individua i certificati appena entrati nel periodo oltre la conservazione e avvisa via email tutti gli amministratori attivi — una volta sola per certificato, anche se restano nell'elenco finché non vengono eliminati.
- La pagina **Da eliminare** mostra sempre l'elenco aggiornato (interrogato dal vivo, non da uno stato salvato) con un link di conferma per ciascun certificato.
- L'eliminazione, dopo conferma, cancella il file dal disco e riduce il certificato al record minimo richiesto dalla specifica (atleta, tipo, scadenza, data ed esecutore dell'eliminazione): oggetto, nome file originale, MIME, dimensione e motivazione di un eventuale rifiuto vengono rimossi. L'azione è registrata nell'audit log.

## Deploy su hosting condiviso

Non essendoci un worker della coda sempre attivo, sia la coda sia i comandi schedulati vengono elaborati dal cron tramite lo scheduler di Laravel. Configura sul cron del provider un'unica riga:

```cron
* * * * * cd /percorso/del/progetto && php artisan schedule:run >> /dev/null 2>&1
```

Lo scheduler (vedi `routes/console.php`) si occupa di tutto il resto: `queue:work --stop-when-empty` ogni minuto, `certificates:send-reminders` e `admins:send-daily-digest` ogni mattina, `certificates:retention-notify` il primo di ogni mese.

### Passi di deploy

1. `composer install --no-dev --optimize-autoloader` e `npm install && npm run build`.
2. Copia `.env.example` in `.env`, imposta `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, le credenziali del database e (facoltativo) `ADMIN_PASSWORD`/i dati anagrafici dell'admin — vedi sopra.
3. `php artisan key:generate --force`, poi `php artisan migrate --seed --force`.
4. Configura la riga di cron sopra.
5. Accedi come primo amministratore (password stampata a console dal seeder) e, dal pannello, imposta gli **account email** (PEC e classica) — fino a quel momento gli invii usano il mailer di fallback di `.env`.
6. Verifica entrambi gli account con il pulsante **"Invia email di prova"** prima di andare in produzione.

### Checklist deliverability email (SPF / DKIM / DMARC)

Ogni dominio usato come mittente (sia quello della PEC sia quello dell'email "classica") deve avere questi record DNS, altrimenti i messaggi finiscono facilmente in spam o vengono rifiutati:

- **SPF** (TXT sul dominio): elenca i server autorizzati a inviare per conto del dominio, es. `v=spf1 include:_spf.nomeprovider.it ~all` — il valore esatto lo fornisce il provider SMTP/PEC. Verifica con `dig TXT nomedominio.it` o un tool come [mxtoolbox.com/spf.aspx](https://mxtoolbox.com/spf.aspx).
- **DKIM** (TXT su `<selettore>._domainkey.nomedominio.it`): firma crittografica dei messaggi; selettore e chiave pubblica li genera il provider SMTP/PEC (quasi tutti firmano automaticamente lato server — è il caso comune). Verifica con `dig TXT <selettore>._domainkey.nomedominio.it` o [mxtoolbox.com/dkim.aspx](https://mxtoolbox.com/dkim.aspx). Se il provider **non** firma lato server, la specifica richiede una firma DKIM applicativa: Symfony Mailer la supporta nativamente tramite `Symfony\Component\Mailer\Bridge\...` con un signer DKIM — non ancora implementata in questo progetto perché entrambi gli account configurati finora firmano lato server; va aggiunta se si sceglie un provider che non lo fa.
- **DMARC** (TXT su `_dmarc.nomedominio.it`): politica su cosa fare dei messaggi che falliscono SPF/DKIM, es. `v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@nomedominio.it` per iniziare in modalità osservativa prima di passare a `p=reject`. Verifica con `dig TXT _dmarc.nomedominio.it` o [mxtoolbox.com/dmarc.aspx](https://mxtoolbox.com/dmarc.aspx).

Oltre ai record DNS, l'applicazione rispetta già gli altri punti della specifica: ogni email è multipart (HTML + testo semplice), con oggetto chiaro e nessun allegato; il mittente di ciascun account email deve appartenere allo stesso dominio verificato (niente "From" falsificati) — è responsabilità di chi configura l'account dal pannello usare un indirizzo coerente col dominio con SPF/DKIM/DMARC impostati.
