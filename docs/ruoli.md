# Ruoli utente

CodeRush ha due ruoli: **Host** (professore) e **Studente**.

---

## Host

Il professore/gestore del sistema. Accede con username + password.

### Cosa può fare

| Funzionalità | Pagina |
|---|---|
| Creare/modificare classi | `pages/classi.php` |
| Aggiungere/spostare studenti nelle classi | `pages/classe.php` |
| Creare account studenti e altri host | `pages/registra.php` |
| Modificare i dati di uno studente | `pages/studente.php` |
| Gestire i linguaggi di programmazione | `pages/linguaggi.php` |
| Creare/modificare consegne | `pages/consegne.php`, `pages/nuova-domanda.php` |
| Creare e avviare un Rush | `pages/rush.php`, `pages/lobby.php` |
| Controllare la partita in corso | `pages/game.php` (vista host) |
| Vedere i risultati e l'analisi dettagliata | `pages/risultati.php`, `pages/rush-detail.php` |
| Modificare il proprio nome/cognome | `pages/profilo.php` |
| Cambiare la propria password | `pages/profilo.php` |

### Cosa NON può fare

- Non può modificare la propria `login_id` (username)
- Non può vedere il codice scritto dagli studenti **durante** la partita (solo dopo)

---

## Studente

Creato dall'host. Accede con matricola + password.

### Cosa può fare

| Funzionalità | Pagina |
|---|---|
| Entrare in una partita con codice accesso | `index.php` → `pages/waiting.php` |
| Attendere l'avvio della partita | `pages/waiting.php` |
| Leggere la consegna nella fase di lettura | `pages/game.php` |
| Scrivere codice nel proprio turno | `pages/game.php` |
| Vedere i risultati finali | `pages/risultati.php` |
| Cambiare la propria password | `pages/profilo.php` |

### Cosa NON può fare

- Non può creare account, classi, domande
- Non può vedere i menu host (classi, consegne, rush)
- Non può modificare il proprio nome/cognome (solo l'host può)
- Non può avviare o controllare la partita
- Non può vedere l'analisi dettagliata (`rush-detail.php`)

---

## Login

Entrambi i ruoli usano la stessa pagina di login (`login.php`).

- **Studenti:** il campo "Matricola / Username" contiene la loro matricola
- **Host:** il campo contiene il loro username

Il sistema distingue automaticamente il ruolo dalla tabella `users`.

---

## Creazione account

**Non c'è una pagina di registrazione pubblica.**

| Tipo account | Chi lo crea |
|---|---|
| Host | Un altro host esistente (via `pages/registra.php`) |
| Studente | Un host (via `pages/registra.php`) |
| Primo host | `setup.php` (eseguito una volta all'installazione) |

### Campi richiesti per creare uno studente

- Matricola (usata come login_id — deve essere univoca)
- Nome
- Cognome
- Password (minimo 6 caratteri)

### Campi richiesti per creare un host

- Username (deve essere univoco)
- Nome
- Cognome
- Password (minimo 6 caratteri)
