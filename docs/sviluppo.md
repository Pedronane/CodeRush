# Note di sviluppo

Convenzioni, regole obbligatorie e struttura interna del codice.

---

## Regole obbligatorie (dal professore)

### 1. Singolo return nelle funzioni

Ogni funzione PHP deve avere **un solo `return`** alla fine, senza `exit()`, `die()`, o return multipli. Usare `if/else` per gestire i casi.

```php
// CORRETTO
function loginUser($login_id, $password) {
    $user = fetchUser($login_id);
    if ($user && password_verify($password, $user['password'])) {
        $result = $user;
    } else {
        $result = null;
    }
    return $result;
}

// SBAGLIATO
function loginUser($login_id, $password) {
    $user = fetchUser($login_id);
    if (!$user) return null;          // ← return multiplo
    if (!password_verify(...)) die(); // ← die() vietato
    return $user;
}
```

> Nota: `exit()` è permesso nel flusso principale delle pagine (non dentro funzioni), es. dopo `header('Location: ...')`.

### 2. Un solo `echo` in api/api.php

Il file API può avere **un solo `echo`**, alla fine:

```php
$response = handleAction($action, $input);
echo json_encode($response); // ← unico echo
```

### 3. Validazione doppia

Ogni form deve avere validazione sia **lato client** (JavaScript in `script.js`) che **lato server** (PHP). Il server non deve mai fidarsi del client.

---

## Struttura dei file

### `includes/functions.php`

Contiene tutte le funzioni helper del progetto. Le funzioni sono raggruppate per area:

- Auth: `isLoggedIn()`, `isHost()`, `isStudent()`, `loginUser()`, `getUserById()`
- Utility: `sanitize()`, `generateAccessCode()`
- Lettura DB: `getAllLinguaggi()`, `getDomandeByHost()`, `getClasseById()`, ecc.
- Gioco: `createTurniForGame()`, `advanceGamePhase()`, `getTempoRimanente()`, `allSubmitted()`
- AI: `triggerAIEvaluation()`, `evaluateCode()`

### `includes/config.php`

Costanti globali. Da non committare con dati sensibili (chiave API produzione).

### `includes/header.php`

Include `config.php`. Deve essere incluso **dopo** `functions.php` perché usa `isLoggedIn()`, `isHost()`, `sanitize()`, `getUserById()`.

Ogni pagina segue questo pattern:

```php
session_start();
require_once __DIR__ . '/includes/functions.php'; // (o ../ per pages/)
// auth check + logica POST
$pageTitle = 'Nome pagina';
require_once __DIR__ . '/includes/header.php';
// HTML della pagina
?>
</main>
</body>
</html>
```

---

## Database

- **PDO** con `ERRMODE_EXCEPTION` — gli errori SQL lanciano eccezioni
- Connessione singleton in `getDB()` — un solo oggetto PDO per richiesta
- Sempre **prepared statements** per le query con parametri utente
- Output: sempre `sanitize()` (= `htmlspecialchars`) prima di echoare in HTML

---

## Sicurezza

| Attacco | Protezione |
|---|---|
| SQL Injection | Prepared statements PDO ovunque |
| XSS | `sanitize()` = `htmlspecialchars` su tutto l'output |
| CSRF | Accettabile per contesto scolastico — non implementato |
| Session hijacking | `session_start()` + controllo `$_SESSION['user_id']` su ogni pagina |
| Enumerazione utenti | Login restituisce messaggio generico "Credenziali non valide" |
| Accesso diretto alle pagine | Check `isHost()`/`isStudent()`/`isLoggedIn()` + redirect in testa a ogni pagina |

---

## Aggiornamento della documentazione

Questa documentazione è in `docs/`. Va aggiornata quando si aggiungono o modificano funzionalità significative.

**File da aggiornare per tipo di modifica:**

| Modifica | File docs da aggiornare |
|---|---|
| Nuova pagina PHP | `docs/pagine.md` |
| Modifica schema DB | `docs/database.md` |
| Nuovo endpoint API | `docs/api.md` |
| Cambio regole gioco | `docs/flusso-gioco.md` |
| Cambio config/setup | `docs/setup.md` |
| Cambio AI | `docs/ai-valutazione.md` |
| Nuovi ruoli/permessi | `docs/ruoli.md` |

---

## CSS — Design system

Il CSS usa variabili CSS custom (in `:root`). **Non usare colori hardcoded** — usare sempre le variabili:

| Variabile | Valore | Uso |
|---|---|---|
| `--bg` | `#0d1117` | Background pagina |
| `--surface` | `#161b22` | Card, navbar |
| `--surface2` | `#21262d` | Hover, elementi secondari |
| `--border` | `#30363d` | Bordi |
| `--text` | `#c9d1d9` | Testo principale |
| `--text-muted` | `#8b949e` | Testo secondario, label |
| `--accent` | `#58a6ff` | Blu (link, primary, timer) |
| `--success` | `#3fb950` | Verde (ok, corretto) |
| `--warning` | `#d29922` | Giallo (attenzione, parziale) |
| `--danger` | `#f85149` | Rosso (errore, sbagliato) |

---

## Componenti riutilizzabili (CSS)

Classi disponibili in `style.css`:

- `.card` — contenitore con bordo e sfondo surface
- `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-sm` — pulsanti
- `.form-control`, `.form-group`, `.form-label`, `.form-row` — form
- `.alert`, `.alert-success`, `.alert-danger`, `.alert-warning`, `.alert-info` — messaggi
- `.badge`, `.badge-corretto`, `.badge-parziale`, `.badge-sbagliato` — badge voti
- `.code-editor` — textarea monospace per editor codice
- `.code-block` — blocco read-only per mostrare codice
- `.timer-display` — timer grande, cambia colore con `.warning` e `.danger`
- `.lobby-grid`, `.lobby-student` — griglia stile Kahoot
- `.game-code` — codice accesso in grande
- `.phase-banner` — banner fase con varianti `.phase-lettura`, `.phase-scrittura`, ecc.
- `.chain-card`, `.chain-turn` — per l'analisi catene in rush-detail
- `.waiting-screen`, `.waiting-spinner` — schermata attesa studente
- `.tabs`, `.tab-btn`, `.tab-pane` — sistema tab (gestito da `script.js`)
