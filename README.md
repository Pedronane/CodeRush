# CodeRush

> Il **telefono senza fili** della programmazione — un gioco multiplayer scolastico dove il codice passa da studente a studente.

---

## Cos'è CodeRush?

CodeRush trasforma il classico gioco del telefono senza fili in un'attività digitale legata alla programmazione. Ogni partecipante scrive codice per un tempo limitato, poi lo passa al compagno successivo, che deve leggerlo, capirlo e continuarlo. Alla fine, un'AI analizza ogni soluzione finale.

### Il flusso in 30 secondi

```
Host crea partita → Studenti entrano col codice → Host avvia
→ [Fase lettura] Tutti leggono la consegna
→ [Turno 1] Ognuno scrive il proprio codice
→ [Turno 2] Codice passato al compagno successivo
→ [...N turni, uno per studente]
→ AI valuta ogni codice finale → Risultati mostrati a tutti
```

---

## Stack tecnologico

| Tecnologia | Utilizzo |
|---|---|
| **PHP** | Backend, logica di gioco, API |
| **MariaDB** | Database locale (XAMPP) |
| **HTML/CSS** | Interfaccia dark-theme |
| **JavaScript** | Timer, polling, editor codice |
| **Anthropic API** | Valutazione AI dei codici finali |

> Nessun framework PHP. CSS custom. Progetto scolastico — ambiente locale.

---

## Quickstart

### Prerequisiti
- XAMPP (Apache + MariaDB attivi)
- PHP 8.0+
- Repo nella cartella `htdocs/CodeRush/`

### 1. Setup database e primo account

```
http://localhost/CodeRush/setup.php
```

Inserisci nome, cognome, username e password del primo **host** (professore). Il setup crea automaticamente il database `coderush` con tutte le tabelle.

### 2. Configura (opzionale: AI)

In `includes/config.php`, imposta la chiave API Anthropic per abilitare la valutazione automatica del codice:

```php
define('AI_API_KEY', 'sk-ant-...');
```

### 3. Accedi

```
http://localhost/CodeRush/login.php
```

---

## Documentazione

| Pagina | Contenuto |
|---|---|
| [Setup e installazione](docs/setup.md) | Requisiti, configurazione, primo avvio |
| [Database](docs/database.md) | Schema completo, relazioni tra tabelle |
| [Ruoli utente](docs/ruoli.md) | Host vs Studente — permessi e funzionalità |
| [Pagine del sito](docs/pagine.md) | Riferimento per ogni pagina PHP |
| [Flusso di gioco](docs/flusso-gioco.md) | Meccanica del Rush passo per passo |
| [API](docs/api.md) | Endpoint JSON per comunicazione real-time |
| [Valutazione AI](docs/ai-valutazione.md) | Integrazione Anthropic, configurazione |
| [Note di sviluppo](docs/sviluppo.md) | Convenzioni, regole del codice, struttura |

---

## Struttura del progetto

```
CodeRush/
├── index.php               # Home post-login
├── login.php               # Login host + studente
├── logout.php
├── setup.php               # Setup DB iniziale (eseguire una volta)
├── api/
│   └── api.php             # API JSON (polling, submit, controllo gioco)
├── css/
│   └── style.css           # Dark theme
├── db/
│   └── schema.sql          # Schema MariaDB
├── docs/                   # Documentazione
├── img/
│   └── logo.png
├── includes/
│   ├── config.php          # Costanti DB e AI_API_KEY
│   ├── db.php              # Connessione PDO singleton
│   ├── functions.php       # Tutte le funzioni helper
│   └── header.php          # Navbar HTML
├── js/
│   └── script.js           # Tabs, validazione form, editor
└── pages/
    ├── classi.php          # Lista classi
    ├── classe.php          # Vista singola classe + studenti
    ├── consegne.php        # Lista domande con ricerca/filtro
    ├── nuova-domanda.php   # Crea/modifica domanda
    ├── linguaggi.php       # Gestione linguaggi di programmazione
    ├── registra.php        # Crea studenti/host (solo host)
    ├── profilo.php         # Modifica profilo
    ├── studente.php        # Modifica dati studente (solo host)
    ├── rush.php            # Crea nuova partita
    ├── lobby.php           # Lobby host (attende studenti)
    ├── waiting.php         # Attesa studente
    ├── game.php            # Partita in corso
    ├── risultati.php       # Schermata finale
    ├── rushes.php          # Storia rush per classe
    └── rush-detail.php     # Analisi dettagliata con diff
```

---

## Regole di sviluppo

- Ogni funzione PHP: **singolo `return`**, niente `exit()`/`die()` all'interno — usare `if/else`
- `api/api.php`: **un solo `echo`** per file
- Validazione **doppia**: server (PHP) + client (JavaScript)
- Nessun commento nel codice salvo per invarianti non ovvie

---

*Progetto scolastico — PHP + MariaDB + HTML/CSS/JS — Nessun framework PHP*
