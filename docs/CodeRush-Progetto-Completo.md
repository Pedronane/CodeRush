# CodeRush — Documentazione Tecnica Completa

> Piattaforma di coding competitivo a turni per la didattica. Gli studenti si passano il codice come un "telefono senza fili" e alla fine l'AI valuta il risultato.

---

## Indice

1. [[#Panoramica generale]]
2. [[#Stack tecnologico]]
3. [[#Struttura file del progetto]]
4. [[#Database]]
5. [[#Livello `includes/` — cuore condiviso]]
6. [[#API REST — `api/api.php`]]
7. [[#JavaScript globale — `js/script.js`]]
8. [[#Pagine del progetto]]
   - [[#login.php]]
   - [[#index.php — Home]]
   - [[#rush.php — Crea un nuovo Rush]]
   - [[#lobby.php — Sala d'attesa host]]
   - [[#waiting.php — Sala d'attesa studente]]
   - [[#game.php — Arena di gioco]]
   - [[#risultati.php — Risultati finali]]
   - [[#rush-detail.php — Analisi completa con diff]]
   - [[#partecipa.php — Inserisci codice partita]]
   - [[#classi.php — Gestione classi]]
   - [[#classe.php — Dettaglio singola classe]]
   - [[#consegne.php — Lista consegne]]
   - [[#nuova-domanda.php — Crea/modifica consegna]]
   - [[#registra.php — Crea utenti]]
   - [[#profilo.php — Profilo utente]]
   - [[#studente.php — Dettaglio studente (host)]]
   - [[#rushes.php — Lista rush di una classe]]
   - [[#linguaggi.php — Gestione linguaggi]]
   - [[#logout.php]]
9. [[#Flusso completo di una partita]]
10. [[#Algoritmo turni — createTurniForGame]]
11. [[#Sistema AI — evaluateCode]]
12. [[#Sicurezza]]
13. [[#Setup iniziale — setup.php]]

---

## Panoramica generale

CodeRush è un'applicazione web scolastica che simula una gara di programmazione a turni. Il meccanismo centrale:

- Un **host** (insegnante) crea una partita scegliendo una consegna, una classe e i tempi.
- Gli **studenti** entrano con un codice alfanumerico di 6 caratteri.
- La partita ha 3 fasi: **attesa** → **lettura** → **scrittura** (ripetuta N volte, dove N = numero studenti) → **finita**.
- In ogni round ogni studente lavora su uno "slot" (catena di codice) diverso dal suo precedente, seguendo una rotazione ciclica.
- Al termine, **Claude Haiku** valuta il codice finale di ogni catena e restituisce un voto (`corretto/parziale/sbagliato`) con feedback testuale.

---

## Stack tecnologico

| Livello | Tecnologia |
|---|---|
| Server | Apache (XAMPP/LAMPP) |
| Backend | PHP 8+ con PDO |
| Database | MySQL (charset utf8mb4) |
| Frontend | HTML5 + CSS3 custom (design system proprietario) |
| Editor codice | CodeMirror 5.65 (CDN, tema Dracula) |
| AI | Anthropic API — modello `claude-haiku-4-5-20251001` via cURL |
| Comunicazione real-time | Polling HTTP con `fetch()` ogni 2.5–5 secondi |

Non ci sono framework PHP (no Laravel, no Symfony). Tutto custom e minimale.

---

## Struttura file del progetto

```
CodeRush/
├── index.php              # Home (branch host/studente)
├── login.php              # Autenticazione
├── logout.php             # Distrugge sessione
├── setup.php              # Inizializza DB (eseguire 1 sola volta)
│
├── api/
│   └── api.php            # API JSON per il gioco in tempo reale
│
├── includes/
│   ├── config.php         # Costanti (DB, BASE_URL, AI_API_KEY)
│   ├── db.php             # Singleton PDO
│   ├── functions.php      # Tutta la business logic PHP
│   └── header.php         # Template HTML comune (navbar, bg)
│
├── pages/
│   ├── rush.php           # Crea nuova partita
│   ├── lobby.php          # Host attende gli studenti
│   ├── waiting.php        # Studente attende l'host
│   ├── game.php           # Arena di gioco (lettura + scrittura)
│   ├── risultati.php      # Risultati post-partita
│   ├── rush-detail.php    # Analisi con diff GitHub-style
│   ├── partecipa.php      # Form inserimento codice partita
│   ├── classi.php         # Elenco + creazione classi
│   ├── classe.php         # Dettaglio classe + studenti + rush
│   ├── consegne.php       # Elenco consegne con ricerca
│   ├── nuova-domanda.php  # Crea / modifica consegna
│   ├── registra.php       # Crea account studente o host
│   ├── profilo.php        # Profilo utente (cambio password)
│   ├── studente.php       # Modifica studente (solo host)
│   ├── rushes.php         # Lista rush per classe
│   ├── linguaggi.php      # Gestione linguaggi disponibili
│   └── test.php           # Pagina di test (sviluppo)
│
├── js/
│   └── script.js          # JS globale (transizioni, particelle, validazione)
│
├── css/
│   ├── style.css          # Design system globale
│   └── pages/             # Un CSS per ogni pagina
│
└── db/
    └── schema.sql         # DDL del database
```

---

## Database

### Schema completo

```sql
CREATE DATABASE IF NOT EXISTS coderush CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coderush;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login_id VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    ruolo ENUM('host', 'studente') NOT NULL
);

CREATE TABLE IF NOT EXISTS classi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anno TINYINT NOT NULL,
    sezione VARCHAR(2) NOT NULL,
    indirizzo VARCHAR(100) NOT NULL,
    UNIQUE KEY uk_classe (anno, sezione, indirizzo)
);

CREATE TABLE IF NOT EXISTS studente_classe (
    studente_id INT NOT NULL,
    classe_id INT NOT NULL,
    PRIMARY KEY (studente_id, classe_id),
    FOREIGN KEY (studente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classi(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS linguaggi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS domande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    testo TEXT NOT NULL,
    linguaggio_id INT NOT NULL,
    difficolta TINYINT(1) NULL,
    host_id INT NOT NULL,
    FOREIGN KEY (linguaggio_id) REFERENCES linguaggi(id),
    FOREIGN KEY (host_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS partite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host_id INT NOT NULL,
    classe_id INT NOT NULL,
    domanda_id INT NOT NULL,
    tempo_lettura INT NOT NULL,
    tempo_turno INT NOT NULL,
    stato ENUM('attesa', 'lettura', 'scrittura', 'finita') NOT NULL DEFAULT 'attesa',
    round_corrente INT NOT NULL DEFAULT 0,
    fase_inizio DATETIME NULL,
    codice_accesso VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(id),
    FOREIGN KEY (classe_id) REFERENCES classi(id),
    FOREIGN KEY (domanda_id) REFERENCES domande(id)
);

CREATE TABLE IF NOT EXISTS partecipazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partita_id INT NOT NULL,
    studente_id INT NOT NULL,
    slot_number INT NOT NULL,
    UNIQUE KEY uk_studente (partita_id, studente_id),
    UNIQUE KEY uk_slot (partita_id, slot_number),
    FOREIGN KEY (partita_id) REFERENCES partite(id) ON DELETE CASCADE,
    FOREIGN KEY (studente_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS turni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partita_id INT NOT NULL,
    studente_id INT NOT NULL,
    slot_id INT NOT NULL,
    numero_turno INT NOT NULL,
    codice TEXT NULL,
    submitted_at DATETIME NULL,
    UNIQUE KEY uk_turno (partita_id, studente_id, numero_turno),
    FOREIGN KEY (partita_id) REFERENCES partite(id) ON DELETE CASCADE,
    FOREIGN KEY (studente_id) REFERENCES users(id),
    FOREIGN KEY (slot_id) REFERENCES partecipazioni(id)
);

CREATE TABLE IF NOT EXISTS valutazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_id INT NOT NULL UNIQUE,
    voto ENUM('corretto', 'parziale', 'sbagliato') NOT NULL,
    feedback TEXT NOT NULL,
    FOREIGN KEY (slot_id) REFERENCES partecipazioni(id) ON DELETE CASCADE
);

INSERT IGNORE INTO linguaggi (nome) VALUES
('Python'), ('JavaScript'), ('Java'), ('C'), ('C++'), ('PHP'), ('SQL'), ('HTML/CSS');
```

### Relazioni chiave

- `users` → può essere host o studente (campo `ruolo`)
- `studente_classe` → N:M tra studenti e classi
- `partite` → una partita per ogni "Rush" avviato
- `partecipazioni` → ogni studente in una partita ha uno **slot** (posizione fissa, 0-based)
- `turni` → ogni riga rappresenta il lavoro di uno studente su uno slot in un determinato round
  - `slot_id` = la catena su cui lo studente sta lavorando in quel round
  - `studente_id` = chi sta scrivendo
  - `numero_turno` = il round (0, 1, 2, …, N-1)
- `valutazioni` → una per ogni slot (catena), scritta dall'AI alla fine

### Diagramma concettuale

```
users ──┬── (host) ──── partite ──── domande ──── linguaggi
        │                  │
        └── (studente) ─── partecipazioni (slot_number)
                                │
                               turni (numero_turno, codice)
                                │
                           valutazioni (voto, feedback)
```

---

## Livello `includes/` — cuore condiviso

### `config.php`

```php
define('BASE_URL', '/CodeRush');
define('DB_HOST', 'localhost');
define('DB_NAME', 'coderush');
define('DB_USER', 'root');
define('DB_PASS', '');
define('AI_API_KEY', '');  // chiave Anthropic
```

### `db.php` — Singleton PDO

```php
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    return $pdo;
}
```

Pattern singleton con `static $pdo`: la connessione viene creata una sola volta per richiesta HTTP.

### `functions.php` — Business logic

Tutte le funzioni dell'applicazione vivono qui. Nessun ORM, solo query PDO parametrizzate.

#### Autenticazione e ruoli

```php
function isLoggedIn() { return isset($_SESSION['user_id']); }
function isHost()     { return isLoggedIn() && $_SESSION['ruolo'] === 'host'; }
function isStudent()  { return isLoggedIn() && $_SESSION['ruolo'] === 'studente'; }

function sanitize($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

function loginUser($login_id, $password) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE login_id = ?');
    $stmt->execute([trim($login_id)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) return $user;
    return null;
}
```

#### Generazione codice accesso

```php
function generateAccessCode() {
    $db   = getDB();
    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $stmt = $db->prepare('SELECT id FROM partite WHERE codice_accesso = ?');
    $stmt->execute([$code]);
    // ricorsione se collisione (rarissima)
    if ($stmt->fetch()) return generateAccessCode();
    return $code;
}
```

Genera 6 caratteri esadecimali maiuscoli (es. `A3F8C2`). La ricorsione gestisce le rare collisioni.

#### Query principali

```php
// Partita con tutti i JOIN (domanda, linguaggio, classe, host)
function getPartitaById($id) {
    $stmt = $db->prepare(
        'SELECT p.*, d.nome AS domanda_nome, d.testo AS domanda_testo,
                l.nome AS linguaggio_nome, c.anno, c.sezione, c.indirizzo,
                u.nome AS host_nome, u.cognome AS host_cognome
         FROM partite p
         JOIN domande d ON d.id = p.domanda_id
         JOIN linguaggi l ON l.id = d.linguaggio_id
         JOIN classi c ON c.id = p.classe_id
         JOIN users u ON u.id = p.host_id
         WHERE p.id = ?'
    );
    // ...
}

// Il turno che uno studente deve completare nel round corrente
function getTurnoCorrente($partita_id, $studente_id, $round) { ... }

// Il codice prodotto dal turno precedente sullo stesso slot
function getPreviousCodice($slot_id, $round) { ... }

// Controlla se tutti hanno consegnato nel round corrente
function allSubmitted($partita_id, $round) {
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS cnt FROM turni
         WHERE partita_id = ? AND numero_turno = ? AND submitted_at IS NULL'
    );
    $stmt->execute([$partita_id, $round]);
    return (int)$stmt->fetch()['cnt'] === 0;
}
```

#### `getTempoRimanente`

```php
function getTempoRimanente($partita) {
    if (empty($partita['fase_inizio'])) return 0;
    $inizio  = strtotime($partita['fase_inizio']);
    $durata  = $partita['stato'] === 'lettura'
               ? (int)$partita['tempo_lettura']
               : (int)$partita['tempo_turno'];
    $rimasto = $durata - (time() - $inizio);
    return max(0, $rimasto);
}
```

Il timer è server-side: `fase_inizio` è un DATETIME nel DB. Il frontend fa polling e usa il valore restituito dall'API per sincronizzarsi.

#### `advanceGamePhase`

```php
function advanceGamePhase($partita) {
    $db = getDB();
    $partita_id = $partita['id'];

    if ($partita['stato'] === 'lettura') {
        // lettura → scrittura (round 0)
        $db->prepare('UPDATE partite SET stato="scrittura", fase_inizio=NOW() WHERE id=?')
           ->execute([$partita_id]);
        return 'scrittura';

    } elseif ($partita['stato'] === 'scrittura') {
        $n         = count(getPartecipazioniByPartita($partita_id));
        $nextRound = $partita['round_corrente'] + 1;

        if ($nextRound >= $n) {
            // tutti i round completati → finita
            $db->prepare('UPDATE partite SET stato="finita", fase_inizio=NULL WHERE id=?')
               ->execute([$partita_id]);
            triggerAIEvaluation($partita_id);
            return 'finita';
        } else {
            // avanza al round successivo
            $db->prepare('UPDATE partite SET round_corrente=?, fase_inizio=NOW() WHERE id=?')
               ->execute([$nextRound, $partita_id]);
            return 'scrittura';
        }
    }
    return $partita['stato'];
}
```

### `header.php` — Template HTML

Ogni pagina include questo file dopo aver settato `$pageTitle`. Genera:

- Il tag `<html>` fino alla navbar aperta
- **BackgroundFX**: griglia CSS + 3 blob animati (verde, blu, arancio)
- **Page Transition**: overlay con logo e barra di caricamento tra le pagine
- **Navbar responsive** con menu diverso per host e studente
  - Host: Home, Nuovo Rush, Consegne, Classi, Utenti
  - Studente: Home, Partecipa al Rush, Profilo
  - Entrambi: avatar con iniziali, badge ruolo, pulsante Esci

```php
<div class="nav-avatar">
    <div class="avatar-circle">
        <?= strtoupper(mb_substr($currentUser['nome'],0,1) . mb_substr($currentUser['cognome'],0,1)) ?>
    </div>
    <span class="avatar-name"><?= sanitize($currentUser['nome']) ?></span>
</div>
```

---

## API REST — `api/api.php`

Unico file che gestisce tutta la comunicazione real-time. Accetta GET e POST (JSON).

### Azioni disponibili

| Azione | Metodo | Chi | Descrizione |
|---|---|---|---|
| `lobby_state` | GET | host | Stato lobby + lista studenti connessi |
| `game_state` | GET | tutti | Stato corrente, round, tempo rimanente |
| `start_game` | POST | host | Avvia la partita (lettura → scrittura) |
| `submit_code` | POST | studente | Consegna il codice del turno corrente |
| `advance_phase` | POST | host | Forza avanzamento di fase |

### `game_state` — gestione scadenza timer lato server

```php
function handleGameState() {
    $partita = getPartitaById($id);

    // se il timer è scaduto, l'API stessa avanza la fase
    $tempoScaduto = $partita['stato'] !== 'attesa'
        && $partita['stato'] !== 'finita'
        && !empty($partita['fase_inizio'])
        && getTempoRimanente($partita) <= 0;

    if ($tempoScaduto) {
        advanceGamePhase($partita);
        $partita = getPartitaById($id);  // rilegge lo stato aggiornato
    }

    return [
        'success' => true,
        'stato'   => $partita['stato'],
        'round'   => $partita['round_corrente'],
        'tempo_rimanente' => getTempoRimanente($partita)
    ];
}
```

Questo è il meccanismo fondamentale: il timer non avanza sul server autonomamente. È la prima chiamata `game_state` dopo la scadenza che fa scattare `advanceGamePhase`. Tutti i client fanno polling ogni 5 secondi.

### `submit_code`

```php
function handleSubmitCode($input) {
    // ...
    $db->prepare('UPDATE turni SET codice=?, submitted_at=NOW() WHERE id=?')
       ->execute([$codice, $turno['id']]);

    // se tutti hanno consegnato, avanza subito senza aspettare il timer
    if (allSubmitted($partita_id, $partita['round_corrente'])) {
        $nuovoStato = advanceGamePhase($partita);
        if ($nuovoStato === 'finita') $gameEnded = true;
    }

    return ['success' => true, 'game_ended' => $gameEnded];
}
```

---

## JavaScript globale — `js/script.js`

Caricato su quasi tutte le pagine. Non usa jQuery né altri framework.

### `initPageTransition()`

Intercetta tutti i click su link interni, mostra un overlay con logo e barra, poi naviga dopo 320ms. Dà una sensazione di app nativa.

```js
document.body.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a) return;
    var href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript') || a.target === '_blank') return;
    if (a.href.startsWith(location.origin) || a.href.startsWith('/')) {
        e.preventDefault();
        overlay.classList.add('show');
        setTimeout(function () { window.location.href = a.href; }, 320);
    }
});
```

### `initCodeParticles()`

Cerca tutti gli elementi `[data-particles]` e li riempie di simboli di codice (`</>`, `{ }`, `//`, `()`, `=>`, ecc.) con animazione CSS `float`.

```js
var SYMBOLS = ['</>', '{ }', '//', '()', '=>', '[]', '&&', '||', 'fn', '0x'];

function spawnParticles(container, density) {
    for (var i = 0; i < density; i++) {
        var el = document.createElement('span');
        el.className = 'cp-sym';
        el.textContent = SYMBOLS[i % SYMBOLS.length];
        // posizione, dimensione e timing casuali
        el.style.cssText = 'left:' + left + '%;top:' + top + '%;font-size:' + size + 'px;' +
            'animation:float ' + duration + 's ease-in-out ' + delay + 's infinite;';
        container.appendChild(el);
    }
}
```

### `initPasswordStrength()`

Barra a 4 segmenti colorati che valuta la password in tempo reale. Controlla lunghezza ≥8, maiuscolo, numero, carattere speciale.

### `initFormValidation()`

Validazione lato client per tutti i `form[novalidate]`. Controlla campi `required`, `minLength`, `min/max` per numeri, `pattern` regex. Gestisce anche il confronto `new_password === confirm_password`. In caso di errore agita il form con `animation: shake`.

### `initRoleToggle()`

Sul login, i pulsanti "Studente / Host" cambiano il label del campo login_id (`Matricola` ↔ `Username`). Chiamata manualmente da `login.php` dopo il caricamento del DOM.

### `initTabKey()`

Negli editor `.code-editor`, intercetta Tab per inserire 4 spazi invece di spostare il focus.

---

## Pagine del progetto

---

### `login.php`

**Scopo**: autenticazione. Se già loggato → redirect a home.

**Flusso POST**:
1. Valida che `login_id` e `password` non siano vuoti
2. Chiama `loginUser()` che usa `password_verify()` (bcrypt)
3. Se successo: popola `$_SESSION[user_id, ruolo, nome, cognome]` e redirect
4. Se fallisce: mostra "Credenziali non valide"

**UI notevole**:
- Role toggle Studente/Host che cambia il placeholder del campo login
- Pulsante 👁 per mostrare/nascondere la password
- 28 particelle di codice su tutto lo schermo (`data-particles="28"`)
- Animazione `shake` sull'alert di errore

```php
if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['ruolo']   = $user['ruolo'];
    $_SESSION['nome']    = $user['nome'];
    $_SESSION['cognome'] = $user['cognome'];
    header('Location: /CodeRush/');
    exit();
}
```

---

### `index.php` — Home

**Accesso**: utenti loggati. Gli altri vengono reindirizzati al login.

**Branch host**: 
- Hero con 14 particelle
- 4 stat card (classi totali, consegne proprie, rush completati, studenti totali) calcolate live con query COUNT
- Quick actions (bottoni per le sezioni principali)

```php
$nClassi   = $db->query('SELECT COUNT(*) FROM classi')->fetchColumn();
$stmtD = $db->prepare('SELECT COUNT(*) FROM domande WHERE host_id = ?');
$stmtD->execute([$_SESSION['user_id']]);
$nConsegne = $stmtD->fetchColumn();
```

**Branch studente**:
- Hero con saluto personalizzato
- 3 step "Come funziona" (Ricevi codice → Leggi → Scrivi)
- CTA diretta a `partecipa.php`

**Sezione comune**: 4 feature card che spiegano il gioco (La consegna, I turni, Il passaggio, La valutazione).

---

### `rush.php` — Crea un nuovo Rush

**Accesso**: solo host.

**Dati del form**:
- `classe_id` — classe di destinazione
- `domanda_id` — consegna da assegnare
- `tempo_lettura` — slider da 10 a 600 secondi
- `tempo_turno` — slider da 30 a 1800 secondi

**Validazione server**:
```php
if ($classe_id <= 0)                             $errors[] = 'Seleziona una classe.';
if ($domanda_id <= 0)                            $errors[] = 'Seleziona una consegna.';
if ($tempo_lettura < 10 || $tempo_lettura > 600) $errors[] = 'Tempo lettura: da 10 a 600 secondi.';
if ($tempo_turno  < 30  || $tempo_turno  > 1800) $errors[] = 'Tempo per turno: da 30 a 1800 secondi.';
```

**Al submit**: crea la riga in `partite` con `stato='attesa'` e `codice_accesso` generato, poi redirect alla lobby.

**UI**: layout a 2 colonne — form a sinistra, pannello preview a destra. Il JS inietta il testo della consegna nel pannello preview quando si seleziona una consegna dal dropdown. I valori degli slider vengono formattati live (es. `120` → `2m`).

```js
var domande = <?= json_encode(array_map(function($d){
    return ['id'=>$d['id'],'nome'=>$d['nome'],'testo'=>$d['testo']];
}, $domande)) ?>;
// PHP serializza le consegne in JSON per il JS
```

---

### `lobby.php` — Sala d'attesa host

**Accesso**: solo host proprietario della partita. Se la partita non è più in `attesa` → redirect a `game.php`.

**Polling ogni 3 secondi** via `api.php?action=lobby_state`:
- Mostra la griglia degli studenti connessi con le loro iniziali
- Aggiorna il contatore con animazione `pop-in`
- Abilita il pulsante "Avvia il Rush" solo quando ci sono ≥ 2 studenti

```js
function pollLobby() {
    fetch('/CodeRush/api/api.php?action=lobby_state&id=' + PARTITA_ID)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.stato !== 'attesa') {
                window.location.href = '/CodeRush/pages/game.php?id=' + PARTITA_ID;
                return;
            }
            renderStudenti(data.studenti);
            // ...
        });
}
```

**`startGame()`**: POST a `api.php` con `{action: 'start_game', partita_id: ...}`. L'API:
1. Verifica che ci siano ≥ 2 studenti
2. Imposta `stato='lettura'` e `fase_inizio=NOW()`
3. Chiama `createTurniForGame()` che pre-genera tutti i turni

---

### `waiting.php` — Sala d'attesa studente

**Accesso**: solo studenti.

**Doppia modalità**:
1. Arrivo da `partecipa.php` con `?code=ABC123`: cerca la partita, iscrive lo studente (inserisce in `partecipazioni` con il prossimo slot libero)
2. Arrivo diretto con `?partita_id=X`: mostra solo la pagina di attesa

**Gestione stati**:
- Partita `finita` → errore "già terminata"
- Partita non in `attesa` ma lo studente è già iscritto → redirect diretto a `game.php`
- Partita non in `attesa` e studente non iscritto → errore "già iniziata"

```php
$stmtSlot = $db->prepare('SELECT MAX(slot_number) AS mx FROM partecipazioni WHERE partita_id=?');
$stmtSlot->execute([$partita_id]);
$maxSlot  = $stmtSlot->fetchColumn();
$nextSlot = ($maxSlot === null) ? 0 : (int)$maxSlot + 1;
$db->prepare('INSERT INTO partecipazioni (partita_id,studente_id,slot_number) VALUES (?,?,?)')
   ->execute([$partita_id, $_SESSION['user_id'], $nextSlot]);
```

**Polling ogni 2.5 secondi**: quando `stato !== 'attesa'` → redirect a `game.php`.

**UI**: spinner dual-ring + nome dell'host + info partita (codice, linguaggio, consegna).

---

### `game.php` — Arena di gioco

La pagina più complessa del progetto. Accessibile a host e studenti.

**Redirect automatici**:
- `stato = 'attesa'` → host va a `lobby.php`, studente a `waiting.php`
- `stato = 'finita'` → tutti a `risultati.php`

**Calcoli iniziali lato PHP**:
```php
if (isStudent()) {
    $partecipazione   = getPartecipazione($partita_id, $_SESSION['user_id']);
    $slotId           = $partecipazione['id'];
    $myTurno          = getTurnoCorrente($partita_id, $_SESSION['user_id'], $partita['round_corrente']);
    $codicePrecedente = getPreviousCodice($myTurno['slot_id'], $partita['round_corrente']);
}
```

#### Fase LETTURA

Mostra il testo della consegna su un card grande. L'host ha un pulsante "Avanza alla scrittura". Gli studenti vedono solo il timer e il testo.

#### Fase SCRITTURA — studente

- **Aside sinistro**: testo consegna + (se round > 0) codice del turno precedente su quello slot
- **Editor principale**: CodeMirror con tema Dracula, mode dinamica in base al linguaggio

```js
var modeMap = {
    'python':'python', 'javascript':'javascript',
    'java':'text/x-java', 'c':'text/x-csrc', 'c++':'text/x-c++src',
    'php':'application/x-httpd-php', 'sql':'text/x-sql',
    'html/css':'htmlmixed'
};
cmEditor = CodeMirror.fromTextArea(ta, {
    mode: modeMap[LINGUAGGIO] || 'text/plain',
    theme: 'dracula',
    lineNumbers: true,
    tabSize: 4,
    indentWithTabs: false,
    extraKeys: { Tab: function(cm) { cm.replaceSelection('    '); } }
});
```

Se il turno è già stato consegnato, l'editor è in `readOnly: true`.

#### Fase SCRITTURA — host

Monitor con lista studenti e stato (In corso / Consegnato). Pulsante "Forza turno successivo" per saltare il timer.

#### Timer

```js
function updateTimerDisplay() {
    var el = document.getElementById('timer-display');
    // verde > 30s, arancio ≤ 30s, rosso + shake ≤ 10s
    if (timerSeconds <= 10) {
        el.style.color = 'var(--brand-danger)';
        el.style.animation = 'shake .4s ease-in-out infinite';
        tb.classList.add('danger');
    } else if (timerSeconds <= 30) {
        el.style.color = 'var(--brand-orange)';
    } else {
        el.style.color = 'var(--brand-green)';
    }
    // barra progressiva
    if (bar && totalSeconds > 0)
        bar.style.width = (timerSeconds / totalSeconds * 100) + '%';
}
```

Quando il timer arriva a 0, lo studente esegue automaticamente `submitCode(null)` prima del polling.

#### `submitCode()`

```js
function submitCode(e) {
    var code = cmEditor ? cmEditor.getValue() : document.getElementById('codeEditor').value;
    fetch('/CodeRush/api/api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action:'submit_code', partita_id:PARTITA_ID, codice:code})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            cmEditor.setOption('readOnly', true);
            if (data.game_ended) setTimeout(function(){
                window.location.href = '/CodeRush/pages/risultati.php?id=' + PARTITA_ID;
            }, 1500);
        }
    });
}
```

#### Polling ogni 5 secondi

```js
function pollGameState() {
    fetch('/CodeRush/api/api.php?action=game_state&id=' + PARTITA_ID)
        .then(function(data) {
            if (data.stato === 'finita')
                window.location.href = '/CodeRush/pages/risultati.php?id=' + PARTITA_ID;
            else if (data.stato !== STATO_INIT || data.round !== ROUND_INIT)
                window.location.reload();  // cambio di fase o round
            else
                timerSeconds = data.tempo_rimanente;  // sincronizza il timer
        });
}
```

#### Banner flash all'ingresso

```js
(function showBanner() {
    var label = '<?= $partita['stato'] === 'lettura' ? 'FASE LETTURA — Concentrati!' : 'FASE SCRITTURA — Vai!' ?>';
    var banner = document.getElementById('game-banner');
    box.textContent = label;
    banner.style.display = 'grid';
    setTimeout(function() { banner.style.display = 'none'; }, 1800);
})();
```

---

### `risultati.php` — Risultati finali

**Accesso**: loggati. Lo studente deve essere iscritto alla partita.

Per ogni slot (catena):
- Nome del partecipante iniziale
- Badge voto AI (Corretto/Parziale/Sbagliato) colorato in verde/arancio/rosso
- Feedback testuale dell'AI
- Codice finale (l'ultimo turno di quella catena), con indicazione di chi l'ha scritto

```php
$stmtLast = $db->prepare(
    'SELECT t.*, u.nome, u.cognome FROM turni t JOIN users u ON u.id = t.studente_id
     WHERE t.slot_id = ? AND t.numero_turno = ?'
);
$stmtLast->execute([$part['id'], $n - 1]);
```

Le card hanno un'animazione `fade-up` con delay crescente (`$i * 0.08s`) per un effetto cascata.

L'host ha il link "Analisi completa →" che porta a `rush-detail.php`.

---

### `rush-detail.php` — Analisi completa con diff

**Accesso**: solo host.

La pagina più sofisticata. Mostra l'intera catena di turni per ogni slot, con diff GitHub-style tra turno precedente e turno corrente.

#### Algoritmo LCS per il diff (JavaScript, embedded)

Implementato interamente in JS nella `<head>` prima del resto del body.

```js
function lcsOps(a, b) {
    // Longest Common Subsequence — O(m*n)
    var m = a.length, n = b.length;
    var dp = [];
    for (var i = 0; i <= m; i++) dp[i] = new Array(n+1).fill(0);
    for (var i = 1; i <= m; i++)
        for (var j = 1; j <= n; j++)
            dp[i][j] = a[i-1] === b[j-1]
                ? dp[i-1][j-1] + 1
                : Math.max(dp[i-1][j], dp[i][j-1]);

    // backtracking per ottenere le operazioni
    var ops = [], i = m, j = n;
    while (i > 0 || j > 0) {
        if (i > 0 && j > 0 && a[i-1] === b[j-1]) {
            ops.unshift({type:'same', line:a[i-1]}); i--; j--;
        } else if (j > 0 && (i === 0 || dp[i][j-1] >= dp[i-1][j])) {
            ops.unshift({type:'add', line:b[j-1]}); j--;
        } else {
            ops.unshift({type:'remove', line:a[i-1]}); i--;
        }
    }
    return ops;
}
```

`mergeModify()` trasforma coppie `remove+add` consecutive in `modify` (riga modificata), come fa GitHub.

`githubDiff()` produce HTML inline con la tabella diff completa: numero riga sinistra, numero riga destra, simbolo (`+`/`-`/`~`), contenuto.

```js
var palette = {
    same:   {bg:'transparent',          gut:'#161b22', sign:'#484f58'},
    remove: {bg:'rgba(248,81,73,.16)',  gut:'rgba(248,81,73,.32)', sign:'#f85149'},
    add:    {bg:'rgba(63,185,80,.16)',  gut:'rgba(63,185,80,.32)', sign:'#3fb950'},
    modify: {bg:'rgba(210,153,34,.18)', gut:'rgba(210,153,34,.34)',sign:'#d29922'}
};
```

Il diff è in un `<details>` collassabile (`↕ Mostra diff rispetto al turno precedente`) per non appesantire la pagina. Al render, lo script inline inietta il diff nel `<div id="diff-X-Y">` corrispondente:

```php
<script>
(function() {
    var prev = <?= json_encode($turni[$i-1]['codice']) ?>;
    var curr = <?= json_encode($t['codice']) ?>;
    var el   = document.getElementById('diff-<?= $part['id'] ?>-<?= $i ?>');
    if (el) el.innerHTML = githubDiff(prev, curr);
})();
</script>
```

---

### `partecipa.php` — Inserisci codice partita

**Accesso**: solo studenti.

Form semplicissimo con un `<input>` da 6 caratteri che:
- Forza maiuscolo in tempo reale (`oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"`)
- Submit via GET a `waiting.php?code=XXXXXX`

---

### `classi.php` — Gestione classi

**Accesso**: solo host.

Layout a 2 colonne: tabella classi a sinistra, form di creazione/modifica a destra.

**Azioni POST**:
- `action=create`: valida `anno` (1-5), `sezione` (A-C), `indirizzo`, controlla duplicati con UNIQUE KEY
- `action=edit`: stessa validazione ma con UPDATE, richiede `?edit=ID` in GET

```php
$indirizzi = ['Informatica', 'Grafica', 'Meccanica', 'Telecomunicazioni'];
$sezioni   = range('A', 'C');
```

La tabella mostra il conteggio studenti per ogni classe con una subquery COUNT.

---

### `classe.php` — Dettaglio singola classe

**Accesso**: solo host.

**Azioni POST**:
- `aggiungi_studente`: inserisce in `studente_classe`
- `rimuovi_studente`: rimuove da `studente_classe`
- `sposta_studente`: UPDATE su `studente_classe` con nuova `classe_id`

Il form "sposta" usa un `<select>` inline con le altre classi disponibili, direttamente nella riga della tabella.

```php
$studentiDisponibili = array_filter($tuttiStudenti, fn($s) => !in_array($s['id'], $idInClasse));
$altreClassi         = array_filter(getAllClassi(), fn($c) => $c['id'] != $classe_id);
```

Mostra anche la cronologia Rush della classe con link a Risultati e Analisi.

---

### `consegne.php` — Lista consegne

**Accesso**: solo host.

Mostra solo le consegne dell'host loggato (`WHERE host_id = ?`). Filtri via GET:
- `search` — LIKE sul nome
- `linguaggio` — filtro per linguaggio_id

```php
$domande = getDomandeByHost($_SESSION['user_id'], $search, $linguaggio_id);
```

La funzione `getDomandeByHost` costruisce la query dinamicamente aggiungendo condizioni solo se i filtri sono attivi.

---

### `nuova-domanda.php` — Crea/modifica consegna

**Accesso**: solo host.

Se `?id=X` in GET → modalità edit (carica la consegna esistente, verifica che appartenga all'host corrente).

**Campi**:
- `nome` — titolo della consegna (max 200 caratteri)
- `testo` — descrizione del problema (textarea)
- `linguaggio_id` — select dai linguaggi disponibili
- `difficolta` — select opzionale: non specificata / facile / difficile (valori: NULL, 0, 1)

```php
$difficolta_raw = $_POST['difficolta'] ?? '';
$difficolta     = ($difficolta_raw === '' || $difficolta_raw === 'null') ? null : (int)$difficolta_raw;
```

---

### `registra.php` — Crea utenti

**Accesso**: solo host.

Tabs JavaScript (client-side) per scegliere il tipo: **Studente** (matricola) o **Host** (username).

**Validazioni**:
- nome/cognome: non vuoti
- login_id: regex `[a-zA-Z0-9_.\-]+`
- password: minimo 6 caratteri
- unicità login_id con SELECT prima dell'INSERT

La password viene hashata con `password_hash($password, PASSWORD_DEFAULT)` (bcrypt).

**Barra forza password**: 4 segmenti colorati generati da `initPasswordStrength()` in `script.js`.

---

### `profilo.php` — Profilo utente

**Accesso**: tutti i loggati.

**Azioni POST**:
- `update_info` (solo host): aggiorna nome e cognome, aggiorna anche `$_SESSION`
- `change_password` (tutti): verifica la vecchia password con `password_verify`, hash della nuova

Gli studenti vedono i propri dati in sola lettura con un warning "modificabili solo dal professore".

---

### `studente.php` — Dettaglio studente (host)

**Accesso**: solo host.

Permette all'host di modificare nome, cognome e password di uno studente specifico (non la matricola). I campi sono pre-popolati ma la logica aggiorna solo i campi effettivamente cambiati:

```php
$updates = []; $params = [];
if (!empty($nome) && $nome !== $studente['nome']) {
    $updates[] = 'nome=?'; $params[] = $nome;
}
// ...
if (!empty($updates)) {
    $params[] = $student_id;
    $db->prepare('UPDATE users SET ' . implode(',', $updates) . ' WHERE id=?')->execute($params);
}
```

Sidebar con elenco delle classi a cui lo studente appartiene (con link).

---

### `rushes.php` — Lista rush di una classe

**Accesso**: solo host.

Tabella di tutti i rush `stato='finita'` per una classe specifica. Link a Risultati e Analisi per ognuno. Semplice wrapper attorno a `getRushByClasse($classe_id)`.

---

### `linguaggi.php` — Gestione linguaggi

**Accesso**: solo host.

CRUD per la tabella `linguaggi`. Azioni:
- `create`: INSERT con controllo duplicati
- `edit`: UPDATE con controllo duplicati su altri record

Layout a 2 colonne: tabella a sinistra, form(s) a destra. Se `?edit=ID`, appare il form di modifica blu sopra il form di creazione.

---

### `logout.php`

```php
session_start();
session_destroy();
header('Location: /CodeRush/login.php');
exit();
```

---

## Flusso completo di una partita

```
[HOST crea Rush] → rush.php
        ↓
[partite INSERT, stato='attesa', codice generato]
        ↓
[HOST apre lobby] → lobby.php
        ↓
[STUDENTI entrano] → partecipa.php → waiting.php
    (INSERT in partecipazioni con slot_number incrementale)
        ↓
[HOST avvia] → api start_game
    → stato='lettura', fase_inizio=NOW()
    → createTurniForGame() crea N×N righe in turni
        ↓
[game.php] — FASE LETTURA (tutti leggono la consegna)
    → timer conta (tempo_lettura secondi)
    → HOST può forzare avanzamento
        ↓
[advanceGamePhase] → stato='scrittura', round_corrente=0
        ↓
[game.php] — FASE SCRITTURA round 0
    → ogni studente vede il codice precedente (null al round 0)
    → scrive nel proprio slot assegnato
    → submitCode() → UPDATE turni SET codice, submitted_at
    → se allSubmitted() → advanceGamePhase() anticipa il cambio
        ↓
[round 1, 2, ..., N-1] — ogni round gli studenti lavorano
    su slot diversi (rotazione ciclica)
        ↓
[round N-1 completato] → advanceGamePhase()
    → stato='finita'
    → triggerAIEvaluation() valuta ogni slot
        ↓
[risultati.php] → mostra voti e feedback
[rush-detail.php] → mostra diff turno per turno
```

---

## Algoritmo turni — `createTurniForGame`

Questo è il cuore della logica di gioco. Con N studenti ci sono N round e N slot (catene). Ogni studente lavora su uno slot diverso a ogni round, con rotazione ciclica.

```php
function createTurniForGame($partita_id, $participants) {
    $n = count($participants);

    // mappa slot_number → partecipazione.id
    $slotMap = [];
    foreach ($participants as $p) {
        $slotMap[$p['slot_number']] = $p['id'];
    }

    $stmt = $db->prepare(
        'INSERT INTO turni (partita_id, studente_id, slot_id, numero_turno) VALUES (?, ?, ?, ?)'
    );

    for ($round = 0; $round < $n; $round++) {
        foreach ($participants as $p) {
            $studentSlot = $p['slot_number'];
            // formula rotazione: al round R, lo studente dello slot S
            // lavora sullo slot (S - R) mod N
            $workSlot = (($studentSlot - $round) % $n + $n) % $n;
            $slotId   = $slotMap[$workSlot];
            $stmt->execute([$partita_id, $p['studente_id'], $slotId, $round]);
        }
    }
}
```

### Esempio con 3 studenti (slot 0, 1, 2)

| Round | Studente slot 0 lavora su | Studente slot 1 lavora su | Studente slot 2 lavora su |
|---|---|---|---|
| 0 | slot 0 | slot 1 | slot 2 |
| 1 | slot 2 | slot 0 | slot 1 |
| 2 | slot 1 | slot 2 | slot 0 |

Ogni studente tocca tutti gli slot esattamente una volta. Ogni slot viene toccato da tutti gli studenti esattamente una volta.

La formula `(S - R) mod N` (con correzione per negativi) garantisce la rotazione senza sovrapposizioni.

---

## Sistema AI — `evaluateCode`

```php
function evaluateCode($domanda, $codice, $nomeDomanda) {
    $apiKey = defined('AI_API_KEY') ? AI_API_KEY : '';

    // fallback se API key non configurata o codice vuoto
    if (empty($apiKey) || empty(trim($codice))) {
        return ['voto' => 'parziale', 'feedback' => 'Valutazione automatica non disponibile.'];
    }

    $prompt = "Sei un valutatore di codice scolastico. "
        . "Consegna: \"$nomeDomanda\"\n\n"
        . "Dettaglio: $domanda\n\n"
        . "Codice finale:\n$codice\n\n"
        . "Rispondi SOLO con JSON: {\"voto\": \"corretto|parziale|sbagliato\", \"feedback\": \"spiegazione breve\"}";

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 300,
        'messages'   => [['role' => 'user', 'content' => $prompt]]
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($data && isset($data['content'][0]['text'])) {
        $parsed = json_decode($data['content'][0]['text'], true);
        if ($parsed && isset($parsed['voto'])) return $parsed;
    }
    return ['voto' => 'parziale', 'feedback' => 'Errore chiamata API.'];
}
```

**Modello usato**: `claude-haiku-4-5-20251001` — scelto per la velocità e il basso costo.

**Prompt engineering**: il prompt chiede esplicitamente JSON puro, con valori enum fissi per `voto`. Questo riduce al minimo i fallimenti di parsing.

**Chiamata sincrona**: `triggerAIEvaluation` chiama `evaluateCode` per ogni slot in sequenza. Con timeout di 15 secondi per slot, per 30 studenti potrebbe richiedere fino a 7 minuti. In produzione andrebbe resa asincrona.

**Quando viene chiamata**: alla fine di `advanceGamePhase` quando lo stato diventa `'finita'`. È chiamata una sola volta e salva risultati in `valutazioni` con CHECK `IF NOT EXISTS` per evitare duplicati.

---

## Sicurezza

| Vettore | Mitigazione |
|---|---|
| SQL Injection | PDO con query parametrizzate ovunque |
| XSS | `sanitize()` = `htmlspecialchars()` su tutto l'output |
| Password | `password_hash()` + `password_verify()` (bcrypt) |
| Accesso non autorizzato | Check `isLoggedIn()`, `isHost()`, `isStudent()` in cima a ogni pagina |
| Accesso cross-partita | Verifica `host_id == $_SESSION['user_id']` per azioni host |
| Accesso cross-studente | Verifica `studente_id == $_SESSION['user_id']` per submit codice |
| CSRF | Non implementato (possibile miglioramento) |
| Codice accesso | Generato con `random_bytes(4)`, unicità garantita da UNIQUE KEY |

---

## Setup iniziale — `setup.php`

```
http://localhost/CodeRush/setup.php
```

Esegue `schema.sql` che crea il database, tutte le tabelle e inserisce i linguaggi di default (Python, JavaScript, Java, C, C++, PHP, SQL, HTML/CSS).

**Da eseguire una sola volta** all'installazione. Dopo aver fatto l'accesso iniziale creare un utente host da `registra.php` (non c'è un utente di default nel seeding).

### Requisiti di sistema

- PHP 8.0+ con estensioni: `pdo_mysql`, `curl`, `mbstring`
- MySQL 5.7+ o MariaDB 10.3+
- Apache con `mod_rewrite` (non strettamente necessario, usa path diretti)
- `AI_API_KEY` in `includes/config.php` per la valutazione AI (opzionale, c'è fallback)
