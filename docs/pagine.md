# Pagine del sito

Riferimento completo per ogni pagina PHP. Tutte le pagine richiedono autenticazione eccetto `login.php` e `setup.php`.

---

## Pagine comuni

### `login.php`
**Accesso:** pubblico

Form di login con username/matricola e password. Redirige a `index.php` dopo login riuscito. Usa `loginUser()` che verifica la password con `password_verify()`.

Validazione client-side: campi obbligatori. Validazione server: credenziali errate → messaggio generico (non rivela se l'username esiste).

---

### `logout.php`
**Accesso:** autenticati

Distrugge la sessione PHP e redirige a `login.php`.

---

### `index.php` — Home
**Accesso:** autenticati

**Per l'host:** mostra statistiche (classi, consegne, rush completati, studenti totali) + pulsanti accesso rapido (Nuovo Rush, consegne, classi, registra utenti).

**Per lo studente:** mostra le descrizioni del gioco + form per inserire il codice accesso e entrare in una partita.

---

### `pages/profilo.php`
**Accesso:** autenticati

**Per l'host:** form modifica nome + cognome (la login_id non è modificabile).

**Per entrambi:** form cambio password (richiede la password attuale per conferma).

---

## Pagine host — Gestione

### `pages/registra.php`
**Accesso:** solo host

Due tab: "Nuovo studente" e "Nuovo host". Crea account nel sistema. Non c'è distinzione nell'URL — entrambi usano lo stesso form con campo nascosto `tipo`.

**Validazione:** login_id univoco (check DB), password minimo 6 caratteri, formato login_id (solo `[a-zA-Z0-9_.-]`).

---

### `pages/studente.php?id=X`
**Accesso:** solo host

Modifica i dati di uno studente specifico (nome, cognome, password). Lasciare un campo vuoto = non modificarlo. La matricola (login_id) non è modificabile.

---

### `pages/linguaggi.php`
**Accesso:** solo host

Lista di tutti i linguaggi di programmazione disponibili. Form per aggiungerne di nuovi. Pulsante "Edit" accanto a ciascuno per modificarne il nome.

**Vincolo:** nomi univoci (controllo DB lato server + feedback errore).

---

### `pages/consegne.php`
**Accesso:** solo host

Lista delle consegne dell'host loggato con:
- Barra di ricerca per nome (GET `?search=`)
- Filtro per linguaggio (GET `?linguaggio=ID`)
- Pulsante "Nuova" → `nuova-domanda.php`
- Pulsante "Edit" per ogni riga → `nuova-domanda.php?id=X`

---

### `pages/nuova-domanda.php`
**Accesso:** solo host

Crea o modifica una consegna. Se `?id=X` in URL: modalità modifica (carica i dati esistenti). Campi: nome, testo, linguaggio (dropdown), difficoltà (facoltativa: facile/difficile/non specificata).

Link a `linguaggi.php` per aggiungere un nuovo linguaggio senza uscire dal flusso.

---

### `pages/classi.php`
**Accesso:** solo host

Lista di tutte le classi con numero studenti. Form per creare nuova classe (anno 1-5, sezione A-K, indirizzo da lista predefinita). Pulsante "Edit" per modificare una classe esistente.

---

### `pages/classe.php?id=X`
**Accesso:** solo host

Vista dettagliata di una classe:
- Tabella studenti con azioni: sposta in altra classe, rimuovi dalla classe
- Form per aggiungere uno studente già registrato alla classe
- Link a `registra.php` per creare un nuovo studente
- Tabella rush completati con link a `rush-detail.php`

---

## Pagine gioco — Host

### `pages/rush.php`
**Accesso:** solo host

Form per creare una nuova partita. Seleziona: classe, consegna, tempo lettura (10-600 sec), tempo per turno (30-1800 sec). Il sistema genera automaticamente un codice accesso univoco a 6 caratteri.

Al submit → redirige a `lobby.php?id=X`.

---

### `pages/lobby.php?id=X`
**Accesso:** solo host (deve essere il creatore della partita)

Mostra il codice accesso in grande. Griglia degli studenti connessi (stile Kahoot) aggiornata ogni 3 secondi via polling API. Pulsante START abilitato quando ci sono almeno 2 studenti.

Al click START → chiama API `start_game` → redirige a `game.php?id=X`.

---

### `pages/rushes.php?classe_id=X`
**Accesso:** solo host

Lista di tutti i Rush completati per una classe specifica. Ogni riga ha data, nome consegna, host, pulsante "Dettagli".

---

### `pages/rush-detail.php?id=X`
**Accesso:** solo host

Analisi completa di un Rush terminato:
- Consegna originale
- Per ogni catena di codice: tutti i turni in ordine con autore e timestamp
- Diff visuale tra turno precedente e quello corrente (espandibile)
- Valutazione AI finale con voto e feedback

---

## Pagine gioco — Studente

### `pages/waiting.php?code=XXXXXX`
**Accesso:** solo studenti

Lo studente inserisce il codice accesso (o ci arriva direttamente da `index.php`). Il sistema crea il record `partecipazioni` e assegna uno slot_number. Mostra schermata "in attesa del professore" con polling ogni 2.5 secondi. Quando la partita parte → redirige automaticamente a `game.php`.

**Protezione late-join:** se la partita è già iniziata, viene mostrato un errore invece di permettere l'ingresso.

---

## Pagina gioco condivisa

### `pages/game.php?id=X`
**Accesso:** autenticati (host e studenti della partita)

Vista diversa per ruolo:

**Vista host:**
- Banner fase corrente
- Timer countdown (sincronizzato col server)
- Consegna
- Tabella stato studenti (chi ha consegnato, chi sta ancora scrivendo)
- Pulsante "Avanza alla scrittura" (in fase lettura) o "Forza turno successivo" (in fase scrittura)

**Vista studente (fase lettura):**
- Banner "Fase di lettura"
- Timer countdown
- Testo della consegna + linguaggio

**Vista studente (fase scrittura):**
- Timer countdown
- Consegna originale
- Se round > 0: codice ricevuto dal turno precedente (read-only)
- Editor codice (textarea monospace, Tab inserisce 4 spazi)
- Pulsante "Consegna codice"

Polling ogni 5 secondi per aggiornare lo stato. Se il timer scade → il polling triggera automaticamente l'avanzamento di fase.

---

### `pages/risultati.php?id=X`
**Accesso:** autenticati (partecipanti)

Schermata finale mostrata dopo la fine della partita:
- Banner "Rush completato"
- Per ogni catena: codice finale, autore, voto AI, feedback AI
- Link all'analisi dettagliata (solo host)
