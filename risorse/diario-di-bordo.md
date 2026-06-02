# Diario di Bordo — CodeRush

Gruppo: Pietro Marchesi (coordinatore) · Francesco Cucchi (backend) · Andreo Toledo (frontend)


## Settimana 1

### 31/03/2026

Dopo avere controllato assieme al cliente la gestione e l'utilizzo di ClickUp, il gruppo ha inoltre individuato una nuova possibile espansione del gioco in accordo con il cliente. A seguito di uno showcase di un sito web realizzato da Marchesi e Cucchi si è discussa la possibilità di integrare una sala giochi con come crediti dei crediti guadagnati durante l'utilizzo di CodeRush. Definite le responsabilità: Marchesi coordina e fa da ponte tra le parti, Cucchi sul backend, Toledo sul frontend. Aggiornata la board ClickUp con le nuove card.

### 03/04/2026

Riunione di avvio tecnico. Deciso lo stack: xampp, nessuna libreria perche renderebbe troppo difficile lo sviluppo. Toledo propone un tema scuro pensato per chi scrive codice. Stesa la prima bozza del flusso di gioco ispirato a Kahoot: host crea partita, codice a 6 cifre, fase lettura, turni di scrittura con rotazione.


## Settimana 2

### 07/04/2026

Cucchi e Pietro progettano lo schema del database.

### 10/04/2026

Implementato `setup.php` per la creazione automatica delle 8 tabelle grazie all’aiuto di Claude. Configurata la connessione al DB in `includes/`. Inserimento dati di prova: una classe con studenti e una consegna di esempio. Verificato il login con `password\_hash` (bcrypt).


## Settimana 3

### 14/04/2026

Cucchi lavora sul cuore del backend: l'algoritmo di rotazione del codice. Decisione di generare tutti gli N×N turni in anticipo all'avvio della partita. Abbiamo optato per `slot\_da\_lavorare = (slot\_studente - round + N) % N`, nel mentr Toledo e Pietro anno continuato con la creazione delle prime pagine.

### 17/04/2026

Testata la rotazione con 3 e 5 studenti tramite tabelle di verifica. Risolto un bug sull'indice di slot quando un giocatore entra e poi esce.


## Settimana 4

### 21/04/2026

Implementato il sistema di polling al posto dei WebSocket per restare semplici e affidabili: il client interroga `api/api.php` ogni pochi secondi e riceve lo stato in JSON. Gestita la macchina a stati della partita e l'avanzamento automatico dei turni quando tutti consegnano.

### 24/04/2026

Aggiunte le protezioni di robustezza: l'host può forzare il turno successivo, chi non ha consegnato avanza col codice che ha in quel momento, e blocco late-join per chi tenta di entrare a partita iniziata. Cucchi chiude la parte logica del motore di gioco.


## Settimana 5

### 28/04/2026

Toledo parte sul frontend vero e proprio. Realizzata la lobby: lato host il codice di accesso in grande e la griglia studenti che si popola in tempo reale (aggiornamento ogni 3s); lato studente la schermata di attesa. CSS completamente custom, nessun Bootstrap, grazie a Claude Code.

### 01/05/2026

Sviluppata la schermata di gioco: consegna sempre visibile, timer countdown e editor di codice. Editor bloccato in fase di lettura, sbloccato in scrittura. Dal secondo turno l'editor si pre-popola col codice ricevuto dal compagno.


## Settimana 6

### 05/05/2026

Risolta la sfida tecnica del timer: countdown JavaScript locale per fluidità, risincronizzato dal polling col tempo del server per restare onesto. Aggiunta la doppia validazione dei form: JavaScript per risposta immediata, PHP lato server perché del client non ci si fida mai.

### 08/05/2026

Realizzata la schermata risultati: ogni catena col codice finale e badge colorato. Allineamento grafico del tema scuro su tutte le pagine. Sessione di test integrato frontend-backend: prima partita completa giocata internamente dal gruppo.


## Settimana 7

### 12/05/2026

Integrazione della valutazione AI. A partita finita, per ogni catena il codice finale viene inviato all'API di Anthropic (modello Claude Haiku, scelto per velocità e costo). Risposta strutturata in JSON: voto (corretto/parziale/sbagliato) e feedback testuale.

### 15/05/2026

Implementati i fallback dell'AI: chiave API mancante, codice vuoto o timeout di rete non rompono il sistema — assegnano "parziale" con messaggio e proseguono. Badge colorati collegati ai voti AI: verde/giallo/rosso. Verifica end-to-end con bug volontario per mostrare la propagazione dell'errore.


## Settimana 8

### 22/05/2026

Rifinitura finale: cleanup del codice, rimozione file di test, unificazione delle pagine risultati e analisi. Aggiornata la documentazione (`docs/`, `relazione.md`) e la board ClickUp. Preparato lo `script di presentazione` (`presentazione.md`).


