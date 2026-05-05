# Flusso di gioco

Descrizione passo per passo di come funziona un Rush completo.

---

## Panoramica

```
[PREPARAZIONE]          [LOBBY]              [GIOCO]                [FINE]
                                         ┌── lettura ──┐
Host crea Rush ──► Host aspetta ──► Host avvia ──► scrittura ──► ... ──► finita ──► risultati
                   studenti entrano                (N round)
```

---

## Fase 1: Creazione della partita

**Chi:** Host
**Dove:** `pages/rush.php`

L'host configura:
1. **Classe** — gli studenti di questa classe potranno entrare
2. **Consegna** — il problema di programmazione da risolvere
3. **Tempo lettura** — secondi per leggere la consegna (senza poter scrivere codice)
4. **Tempo per turno** — secondi a disposizione in ogni round di scrittura

Il sistema genera un **codice accesso** univoco a 6 caratteri (es. `AB12CD`) e crea il record nella tabella `partite` con stato `attesa`.

---

## Fase 2: Lobby e accesso studenti

**Chi:** Host (lobby) + Studenti (waiting)
**Dove:** `pages/lobby.php` (host), `pages/waiting.php` (studenti)

### Lato host
- Vede il codice accesso in grande
- La griglia degli studenti connessi si aggiorna ogni 3 secondi (polling API)
- Il pulsante START rimane disabilitato finché non ci sono almeno 2 studenti
- Può avviare la partita quando vuole

### Lato studente
- Va su `index.php`, inserisce il codice accesso
- Viene reindirizzato a `waiting.php?code=XXXXXX`
- Il sistema crea il record in `partecipazioni` con uno `slot_number` progressivo
- Vede la schermata "In attesa del professore"
- Polling ogni 2.5 secondi — quando la partita parte, viene reindirizzato automaticamente a `game.php`

> **Protezione late-join:** uno studente che prova ad entrare dopo l'avvio della partita vede l'errore "La partita è già iniziata".

---

## Fase 3: Avvio partita (START)

**Chi:** Host
**Dove:** `pages/lobby.php` → API `start_game`

Quando l'host clicca START:

1. `partite.stato` → `lettura`
2. `partite.fase_inizio` = `NOW()`
3. Vengono creati **tutti i turni** per tutti i round in anticipo (funzione `createTurniForGame`)

### Algoritmo di creazione turni

Con N studenti, vengono creati N×N turni. Per ogni round `r` (da 0 a N-1) e ogni studente con `slot_number = s`:

```
slot_da_lavorare = (s - r + N) % N
```

Questo garantisce che in ogni round ogni studente lavori su una catena diversa, e che alla fine ogni studente abbia lavorato su ogni catena.

---

## Fase 4: Lettura

**Chi:** Tutti
**Dove:** `pages/game.php`

- Tutti vedono la consegna e il linguaggio
- L'editor di codice **non è disponibile**
- Il timer mostra il tempo rimanente (calcolato da `fase_inizio + tempo_lettura - NOW()`)
- Quando il timer scade → polling triggera `advance_phase` → stato cambia a `scrittura`

---

## Fase 5: Turni di scrittura (round 0 → N-1)

**Chi:** Tutti
**Dove:** `pages/game.php`

### Round 0 (primo turno)

Ogni studente vede la consegna e un **editor vuoto**. Scrive la propria soluzione iniziale.

### Round 1+ (turni successivi)

Ogni studente vede:
1. La consegna originale (sempre visibile)
2. Il **codice ricevuto** — l'ultimo testo scritto sulla catena che sta ora lavorando (dal turno precedente, da un altro studente)
3. L'editor di codice pre-popolato con quel codice, modificabile

### Consegna del codice

Lo studente clicca "Consegna codice". Il sistema:
1. Salva `turni.codice` e `turni.submitted_at = NOW()`
2. Controlla se **tutti** gli studenti hanno consegnato per questo round
3. Se sì → avanza automaticamente al round successivo (o termina la partita se era l'ultimo)

### Fine anticipata del turno

L'host può premere "Forza turno successivo" che chiama l'API `advance_phase`. Gli studenti che non hanno ancora consegnato vengono avanzati con il codice che hanno al momento (o `NULL` se non hanno scritto nulla).

> **Nota:** il timer lato client è una countdown locale. Il tempo reale è calcolato dal server (`fase_inizio + tempo_turno`). Se il client e server si desincronizzano, il polling ogni 5 secondi riallinea il timer.

---

## Fase 6: Fine partita

Quando tutti i round sono completati (o l'host forza l'ultimo avanzamento):

1. `partite.stato` → `finita`
2. `partite.fase_inizio` → `NULL`
3. Viene chiamata `triggerAIEvaluation()` per ogni catena di codice
4. Tutti i client in polling vedono `stato = finita` e vengono reindirizzati a `risultati.php`

---

## Fase 7: Valutazione AI

**Quando:** automaticamente al termine della partita

Per ogni catena (slot), il sistema:
1. Recupera l'ultimo codice scritto (round N-1)
2. Chiama l'API Anthropic con: consegna + codice finale
3. L'AI restituisce un JSON `{"voto": "corretto|parziale|sbagliato", "feedback": "..."}`
4. Il risultato viene salvato in `valutazioni`

Se `AI_API_KEY` è vuota, il fallback è `voto = parziale, feedback = "Valutazione automatica non disponibile"`.

---

## Fase 8: Risultati

**Chi:** tutti i partecipanti
**Dove:** `pages/risultati.php` (vista semplice), `pages/rush-detail.php` (analisi host)

### Studenti
Vedono il codice finale di ogni catena e il relativo voto AI.

### Host
Vede tutto il sopra, più:
- L'intera progressione di ogni catena turno per turno
- Il diff visuale tra un turno e il successivo
- I timestamp di consegna
- Link all'analisi completa `rush-detail.php`

---

## Diagramma stati partita

```
                    ┌─────────────────────────────────────────────┐
                    │                                             │
          HOST: START                              timer scade o tutti consegnano
                    │                              (ultimo round)│
attesa ─────────────► lettura ──timer scade──► scrittura ────────► finita
                                              (round 0)
                                                  │
                                          timer scade o tutti consegnano
                                          (round < N-1)
                                                  │
                                                  ▼
                                          scrittura (round+1)
```
