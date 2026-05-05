# API

File: `api/api.php`

Tutte le richieste richiedono una sessione PHP valida (login). Risposta sempre in JSON.

**Regola:** un solo `echo json_encode($response)` alla fine del file.

---

## Struttura generale

```
GET  api/api.php?action=<nome>   — lettura stato
POST api/api.php                 — body JSON con { "action": "<nome>", ... }
```

---

## Endpoint

### `GET ?action=lobby_state&id=<partita_id>`

Stato della lobby: studenti connessi e stato partita.

**Accesso:** autenticati

**Risposta (successo):**
```json
{
  "success": true,
  "stato": "attesa",
  "studenti": [
    { "id": 5, "nome": "Mario", "cognome": "Rossi" }
  ],
  "count": 1
}
```

**Usato da:** `pages/lobby.php` (polling ogni 3 secondi)

---

### `GET ?action=game_state&id=<partita_id>`

Stato corrente della partita: fase, round, tempo rimanente.

**Accesso:** autenticati

**Comportamento speciale:** se il timer è scaduto (`tempo_rimanente <= 0`), avanza automaticamente la fase prima di rispondere.

**Risposta (successo):**
```json
{
  "success": true,
  "stato": "scrittura",
  "round": 1,
  "tempo_rimanente": 87
}
```

**`stato` possibili:** `attesa`, `lettura`, `scrittura`, `finita`

**Usato da:** `pages/game.php` (polling ogni 5 secondi), `pages/waiting.php` (polling ogni 2.5 secondi)

---

### `POST action=start_game`

Avvia la partita dalla lobby. Crea tutti i turni per tutti i round.

**Accesso:** solo host (deve essere il creatore della partita)

**Body:**
```json
{
  "action": "start_game",
  "partita_id": 42
}
```

**Risposta (successo):**
```json
{
  "success": true,
  "stato": "lettura"
}
```

**Errori possibili:**
- `"Non autenticato"` — sessione scaduta
- `"Solo host può avviare"` — utente non host
- `"Servono almeno 2 studenti"` — meno di 2 partecipanti
- `"Partita già avviata"` — stato non `attesa`

**Effetti collaterali:** `partite.stato = lettura`, `fase_inizio = NOW()`, crea N×N record in `turni`

---

### `POST action=submit_code`

Lo studente consegna il proprio codice per il turno corrente.

**Accesso:** solo studenti

**Body:**
```json
{
  "action": "submit_code",
  "partita_id": 42,
  "codice": "def fattoriale(n):\n    if n == 0:\n        return 1\n    return n * fattoriale(n-1)"
}
```

**Risposta (successo):**
```json
{
  "success": true,
  "game_ended": false
}
```

Se era l'ultimo round e tutti hanno consegnato:
```json
{
  "success": true,
  "game_ended": true
}
```

**Effetti collaterali:** aggiorna `turni.codice` e `turni.submitted_at`. Se tutti hanno consegnato → chiama `advanceGamePhase()` (che può terminare la partita e triggerare valutazione AI).

---

### `POST action=advance_phase`

L'host forza l'avanzamento alla fase/round successivo.

**Accesso:** solo host (deve essere il creatore della partita)

**Body:**
```json
{
  "action": "advance_phase",
  "partita_id": 42
}
```

**Risposta:**
```json
{
  "success": true,
  "stato": "scrittura"
}
```

O se era l'ultimo round:
```json
{
  "success": true,
  "stato": "finita"
}
```

**Transizioni:**
- `lettura` → `scrittura` (round 0)
- `scrittura` (round r < N-1) → `scrittura` (round r+1)
- `scrittura` (round N-1) → `finita` + trigger AI

---

## Gestione errori

Tutti gli endpoint restituiscono `success: false` con un campo `error` descrittivo in caso di problema:

```json
{
  "success": false,
  "error": "Servono almeno 2 studenti"
}
```

---

## Polling e sincronizzazione

Il sistema usa **polling** (fetch periodico) invece di WebSocket. Vantaggi: semplice, nessuna dipendenza. Svantaggi: latenza proporzionale all'intervallo.

| Client | Intervallo | Endpoint |
|---|---|---|
| Lobby host | 3 secondi | `lobby_state` |
| Waiting studente | 2.5 secondi | `game_state` |
| Game (tutti) | 5 secondi | `game_state` |

Il timer countdown è **lato client** ma viene riallineato ad ogni risposta polling dal campo `tempo_rimanente` del server.
