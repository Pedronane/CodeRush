# Database

Database: `coderush` — MariaDB/MySQL — charset `utf8mb4`

---

## Schema ER (concettuale)

```
users ──────────────────────────────────────────────┐
  │ (host)                                           │
  │ crea                                             │
  ▼                                                  │
domande ──── linguaggi                               │
  │                                                  │
  │ usata in                                         │
  ▼                                                  │
partite ◄──── classi ◄──── studente_classe ◄──── users (studente)
  │                                                      │
  │ ha                                                   │
  ▼                                                      │
partecipazioni ◄────────────────────────────────────────┘
  │
  │ ha
  ▼
turni ──► valutazioni
```

---

## Tabelle

### `users`

Tutti gli utenti del sistema (sia host che studenti).

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK AUTO | |
| `login_id` | VARCHAR(100) UNIQUE | Matricola (studenti) o username (host) — usato per il login |
| `nome` | VARCHAR(100) | |
| `cognome` | VARCHAR(100) | |
| `password` | VARCHAR(255) | Bcrypt (`password_hash`) |
| `ruolo` | ENUM('host','studente') | |

---

### `classi`

Le classi scolastiche.

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK AUTO | |
| `anno` | TINYINT | 1–5 |
| `sezione` | VARCHAR(2) | Es. "A", "B" |
| `indirizzo` | VARCHAR(100) | Es. "Informatica" |

Vincolo UNIQUE su `(anno, sezione, indirizzo)` — non ci possono essere due "4A Informatica".

---

### `studente_classe`

Tabella ponte N:N tra studenti e classi. Uno studente può essere in più classi; una classe ha più studenti.

| Campo | Tipo | Note |
|---|---|---|
| `studente_id` | INT FK → users.id | ON DELETE CASCADE |
| `classe_id` | INT FK → classi.id | ON DELETE CASCADE |

PK composita `(studente_id, classe_id)`.

---

### `linguaggi`

Linguaggi di programmazione disponibili per le consegne.

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK AUTO | |
| `nome` | VARCHAR(100) UNIQUE | Es. "Python", "Java" |

---

### `domande`

Le consegne/sfide di programmazione create dagli host.

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK AUTO | |
| `nome` | VARCHAR(200) | Titolo breve |
| `testo` | TEXT | Descrizione dettagliata del problema |
| `linguaggio_id` | INT FK → linguaggi.id | |
| `difficolta` | TINYINT(1) NULL | `NULL` = non specificata, `0` = facile, `1` = difficile |
| `host_id` | INT FK → users.id | Chi ha creato la domanda |

---

### `partite`

Una sessione di gioco (Rush).

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK AUTO | |
| `host_id` | INT FK → users.id | Host che ha creato la partita |
| `classe_id` | INT FK → classi.id | Classe coinvolta |
| `domanda_id` | INT FK → domande.id | Consegna usata |
| `tempo_lettura` | INT | Secondi per la fase di lettura iniziale |
| `tempo_turno` | INT | Secondi per ogni turno di scrittura |
| `stato` | ENUM | `attesa` → `lettura` → `scrittura` → `finita` |
| `round_corrente` | INT | Turno corrente (0-indexed, da 0 a N-1) |
| `fase_inizio` | DATETIME NULL | Timestamp inizio fase corrente (per calcolare tempo rimanente) |
| `codice_accesso` | VARCHAR(10) UNIQUE | Codice a 6 caratteri per entrare (es. "AB12CD") |
| `created_at` | TIMESTAMP | |

#### Transizioni di stato

```
attesa ──[host avvia]──► lettura ──[timer scade]──► scrittura ──[tutti consegnano O host forza]──► scrittura (round+1)
                                                                                                          │
                                                                                              [ultimo round]
                                                                                                          │
                                                                                                          ▼
                                                                                                        finita
```

---

### `partecipazioni`

Traccia quali studenti sono entrati in quale partita e il loro slot number.

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK AUTO | Usato come `slot_id` nei turni |
| `partita_id` | INT FK → partite.id | ON DELETE CASCADE |
| `studente_id` | INT FK → users.id | |
| `slot_number` | INT | Posizione 0-indexed dello studente nella rotazione |

UNIQUE su `(partita_id, studente_id)` e `(partita_id, slot_number)`.

---

### `turni`

Un turno = un singolo studente che lavora su una catena di codice in un round specifico.

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK AUTO | |
| `partita_id` | INT FK → partite.id | ON DELETE CASCADE |
| `studente_id` | INT FK → users.id | Chi scrive in questo turno |
| `slot_id` | INT FK → partecipazioni.id | Quale catena di codice sta lavorando |
| `numero_turno` | INT | Round (0-indexed) |
| `codice` | TEXT NULL | Il codice scritto (NULL finché non consegnato) |
| `submitted_at` | DATETIME NULL | Quando lo studente ha consegnato |

UNIQUE su `(partita_id, studente_id, numero_turno)`.

Tutti i turni vengono creati all'avvio della partita (vedi [flusso di gioco](flusso-gioco.md)).

---

### `valutazioni`

Il giudizio AI sul codice finale di ogni catena.

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK AUTO | |
| `slot_id` | INT UNIQUE FK → partecipazioni.id | Una valutazione per catena |
| `voto` | ENUM('corretto','parziale','sbagliato') | |
| `feedback` | TEXT | Spiegazione testuale dall'AI |

---

## Rotazione del codice

La formula per determinare su quale catena lavora lo studente `i` (slot_number) nel round `r`:

```
slot_da_lavorare = (slot_number_studente - round + N) % N
```

Dove `N` = numero totale di studenti nella partita.

**Esempio con 3 studenti (A=0, B=1, C=2):**

| Round | Studente A (slot 0) | Studente B (slot 1) | Studente C (slot 2) |
|---|---|---|---|
| 0 | catena 0 (propria) | catena 1 (propria) | catena 2 (propria) |
| 1 | catena 2 (di C) | catena 0 (di A) | catena 1 (di B) |
| 2 | catena 1 (di B) | catena 2 (di C) | catena 0 (di A) |

Dopo N round, ogni studente ha lavorato su ogni catena esattamente una volta.
