# 🎤 Script Presentazione — CodeRush (30 min)

**Relatori:** PM (coordinatore) · Cucchi (backend) · Toledo (frontend)
**Setup pre-presentazione:** XAMPP avviato, `setup.php` già eseguito, almeno 1 classe con 3 studenti + 1 consegna pronta, `AI_API_KEY` configurata. Tre dispositivi/finestre pronti (uno per relatore = i tre "giocatori" della demo).

---

## ⏱️ Timeline

| Tempo | Sezione | Chi |
|---|---|---|
| 0:00–3:00 | Apertura + il problema | PM |
| 3:00–6:00 | L'idea: il telefono senza fili del codice | PM |
| 6:00–13:00 | Backend: motore di gioco, DB, rotazione | Cucchi |
| 13:00–18:00 | Frontend: interfaccia, timer, real-time | Toledo |
| 18:00–27:00 | **DEMO LIVE** (tutti giocano) | Tutti |
| 27:00–30:00 | Valutazione AI + chiusura + Q&A | PM |

---

## 🟦 0:00–3:00 — Apertura (PM)

> "Buongiorno. Siamo [nomi] e vi presentiamo **CodeRush**.
>
> Partiamo da una domanda: quando imparate a programmare, su quale codice lavorate? **Solo il vostro.** Scrivete la vostra soluzione, la consegnate, fine. Ma nel lavoro reale un programmatore passa la maggior parte del tempo a leggere e modificare codice scritto da **altri**.
>
> CodeRush nasce per allenare proprio questo. Abbiamo preso un gioco che conoscete tutti — il **telefono senza fili** — e l'abbiamo trasformato in un'attività di programmazione multiplayer.
>
> Nel telefono senza fili una frase passa di orecchio in orecchio e si deforma. In CodeRush è il **codice** a passare di mano in mano: ognuno lo legge, lo capisce e lo continua. Alla fine vediamo cosa è diventato e un'**intelligenza artificiale** giudica il risultato.
>
> Tra poco lo proverete dal vivo. Ma prima vi spieghiamo come funziona e cosa c'è sotto."

**[Slide: logo CodeRush + tagline "Il telefono senza fili della programmazione"]**

---

## 🟦 3:00–6:00 — L'idea (PM)

> "Il flusso è semplice, simile a Kahoot.
>
> 1. Un **host** — il professore — crea una partita scegliendo una classe, una consegna di programmazione e i tempi di gioco.
> 2. Il sistema genera un **codice a 6 cifre**. Gli studenti entrano con quel codice, esattamente come su Kahoot.
> 3. Si parte: c'è una **fase di lettura** in cui tutti studiano la consegna senza poter scrivere.
> 4. Poi i **turni di scrittura**: ognuno scrive il proprio codice. Allo scadere del tempo, il codice **ruota** — passa al compagno successivo.
> 5. Ogni studente riceve il codice di un altro, lo continua o lo corregge. Si ripete per tanti turni quanti sono gli studenti.
> 6. Alla fine, ogni 'catena' di codice è passata per le mani di tutti. Un'**AI** valuta ogni risultato finale: corretto, parziale o sbagliato, con un feedback.
>
> Il bello didattico: vedi come un piccolo errore all'inizio si propaga, e quanto è importante scrivere codice **chiaro** perché il prossimo lo capisca.
>
> Ora Cucchi vi mostra come abbiamo costruito tutto questo."

---

## 🟩 6:00–13:00 — Backend (Cucchi)

> "Grazie. Io mi sono occupato del **backend**: la logica di gioco, il database e le API.
>
> **Lo stack è volutamente essenziale:** PHP puro senza framework, database MariaDB, e nessuna libreria pesante. Volevamo capire i meccanismi, non nasconderli dietro un framework."

**[Mostra schema DB — docs/database.md]**

> "Il database ha **8 tabelle**. Le tre fondamentali:
> - `partite`: la sessione di gioco, con il suo stato — `attesa`, `lettura`, `scrittura`, `finita` — è una **macchina a stati**.
> - `partecipazioni`: chi è entrato e con quale numero di slot nella rotazione.
> - `turni`: il cuore di tutto. Un turno = uno studente che lavora su una catena di codice in un round preciso."

**[Punto tecnico forte — la rotazione]**

> "La parte di cui vado più orgoglioso è l'**algoritmo di rotazione del codice**. Quando l'host avvia la partita, non creiamo i turni man mano: li generiamo **tutti in anticipo**. Con N studenti creiamo N×N turni.
>
> La formula che decide su quale catena lavora ogni studente è questa:
> ```
> slot_da_lavorare = (slot_studente - round + N) % N
> ```
> È un'aritmetica modulare. Garantisce due cose: in ogni round ogni studente lavora su una catena **diversa**, e dopo N round ognuno ha toccato **ogni** catena esattamente una volta. Nessuno lavora due volte sullo stesso codice, e nessuna catena viene saltata."

**[Mostra tabellina esempio con 3 studenti]**

| Round | Studente A | Studente B | Studente C |
|---|---|---|---|
| 0 | catena A | catena B | catena C |
| 1 | catena C | catena A | catena B |
| 2 | catena B | catena C | catena A |

> "Il secondo problema era: **come fa il browser a sapere che la partita è iniziata o che il turno è cambiato?** Non abbiamo usato WebSocket per restare semplici. Usiamo il **polling**: il client chiede al server `api/api.php` ogni pochi secondi 'qual è lo stato?'. Il server risponde con un JSON. Quando lo stato cambia, il client reagisce.
>
> Il tempo è gestito **lato server**: ogni fase ha un `fase_inizio`, e il tempo rimanente è sempre `fase_inizio + durata - adesso`. Così anche se un browser è lento o si desincronizza, il polling lo riallinea. La verità è sempre nel database, mai nel client.
>
> Un dettaglio di robustezza: l'host può **forzare** il turno successivo, e chi non ha consegnato viene avanzato col codice che ha in quel momento. E c'è la protezione **late-join**: se entri dopo l'avvio, il sistema ti blocca.
>
> Tutto questo è lo scheletro. Ora Toledo vi mostra come diventa qualcosa con cui si gioca davvero."

---

## 🟨 13:00–18:00 — Frontend (Toledo)

> "Io ho curato il **frontend**: tutto quello che l'utente vede e tocca.
>
> La scelta grafica è un **tema scuro**, pensato per chi scrive codice — meno affaticamento, più focus. CSS completamente custom, nessun Bootstrap: ogni componente è scritto a mano."

**[Mostra le schermate principali, oppure aprile dal vivo]**

> "Le schermate chiave sono tre:
>
> **1. La lobby.** Lato host mostra il codice di accesso in grande e una griglia di studenti che si popola in tempo reale man mano che entrano — si aggiorna da sola ogni 3 secondi. Lato studente, una schermata di attesa 'aspetta il professore'.
>
> **2. Il gioco.** Qui c'è il pezzo più delicato. Sullo schermo abbiamo: la **consegna** sempre visibile, il **timer** che conta alla rovescia, e l'**editor di codice**. Nella fase di lettura l'editor è bloccato. Quando inizia la scrittura si sblocca. Dal secondo turno in poi, l'editor si **pre-popola** col codice ricevuto dal compagno — così non parti da zero, parti da dove ha lasciato lui.
>
> **3. I risultati.** Ogni catena con il suo codice finale e il **badge colorato** della valutazione AI: verde corretto, giallo parziale, rosso sbagliato.
>
> La sfida tecnica del frontend era il **timer**. È una countdown JavaScript locale per fluidità, ma — come diceva Cucchi — il polling ogni pochi secondi la **risincronizza** col server. Così l'animazione è liscia ma il tempo resta onesto.
>
> Abbiamo anche **doppia validazione**: i controlli sui form li facciamo sia in JavaScript, per dare risposta immediata all'utente, sia in PHP lato server, perché del client non ci si fida mai.
>
> E ora basta parole — proviamolo."

---

## 🟥 18:00–27:00 — DEMO LIVE (tutti)

> **PM:** "Adesso giochiamo davvero. Noi tre saremo i giocatori, così vedete l'intero ciclo. Se volete, dopo potete entrare anche voi con un vostro dispositivo."

**Coreografia (chi fa cosa):**

**① Cucchi è l'HOST** — proietta il suo schermo:
- Va su `rush.php`, crea la partita: sceglie classe, consegna (es. *"Scrivi una funzione che calcola il fattoriale"*), tempo lettura 30s, tempo turno 60s.
- Mostra il **codice a 6 cifre** generato.

**② Toledo e PM (e il pubblico) sono STUDENTI:**
- Entrano da `index.php` col codice.
- Cucchi mostra la **lobby che si popola** in diretta — "vedete? compaiono da soli, è il polling".

> **Cucchi:** "Avvio." → preme START.

**③ Fase lettura (30s):**
> **Toledo:** "Notate: l'editor è grigio, bloccato. Stiamo solo leggendo. Il timer scorre."

**④ Round 0 — scrittura:**
- Ognuno scrive un pezzo di soluzione. PM volutamente scrive qualcosa di **incompleto o con un piccolo bug** ("così vedremo cosa succede").
- Consegnano.

> **Cucchi:** "Appena tutti consegnano, il sistema avanza da solo. Posso anche forzarlo io."

**⑤ Round 1 — la rotazione:**
> **Toledo:** "Guardate il mio editor: non è vuoto. È pre-popolato col codice che ha scritto [PM] un attimo fa. Io ora devo capirlo e continuarlo."

- Tutti continuano il codice ricevuto. Qui si vede il "telefono senza fili" in azione: si commenta dal vivo com'è cambiato il codice.

**⑥ Round 2 (ultimo, con 3 studenti):**
- Ultimo passaggio, tutti consegnano.

> **Cucchi:** "Ultimo round consegnato → stato `finita` → parte la valutazione AI."

**⑦ Risultati:**
- Si apre `risultati.php`. Si mostrano le tre catene coi badge colorati e il feedback dell'AI.

> **PM:** "Vedete? La catena dove avevo lasciato il bug ha preso [parziale/sbagliato], e l'AI spiega il perché. Questo è il valore didattico: il codice è un prodotto condiviso, e si vede esattamente dove le cose sono andate bene o male."

*(Se il pubblico ha partecipato, mostrare le loro catene rende il momento.)*

---

## 🟦 27:00–30:00 — AI + Chiusura (PM)

> "Un'ultima parola sulla **valutazione AI**, che è l'elemento più innovativo.
>
> Quando la partita finisce, per ogni catena il sistema prende il codice finale, lo manda all'**API di Anthropic** — usiamo il modello **Claude Haiku**, scelto perché è veloce ed economico, e qui dobbiamo valutare tanti codici insieme — e chiediamo un giudizio strutturato in JSON: voto e feedback.
>
> Abbiamo previsto i **fallback**: se manca la chiave API, se il codice è vuoto, se la rete va in timeout, il sistema non si rompe — assegna 'parziale' con un messaggio e va avanti. Robustezza prima di tutto.
>
> **Per chiudere.** CodeRush unisce tre cose: **gioco**, perché c'è il timer, la competizione, la sorpresa; **collaborazione**, perché il codice è di tutti; e **apprendimento**, perché ti costringe a leggere e capire il lavoro altrui — la competenza più sottovalutata nella programmazione reale.
>
> Tecnicamente l'abbiamo costruito da zero: PHP, MariaDB, JavaScript, senza framework, per padroneggiare ogni pezzo. Cucchi sul motore e i dati, Toledo sull'interfaccia e l'esperienza, e io ho coordinato il gruppo e fatto da ponte tra le due parti integrandole.
>
> Grazie — siamo a disposizione per le domande."

---

## 🛟 Appendice — Domande probabili (prep Q&A)

| Domanda | Risposta breve |
|---|---|
| Perché niente framework? | Scelta didattica: capire i meccanismi (routing, query, stato) senza astrazioni. |
| Perché polling e non WebSocket? | Semplicità e affidabilità su ambiente locale; il polling basta per i tempi di gioco. |
| Come gestite il tempo se un PC è lento? | Il tempo è autorevole sul server (`fase_inizio + durata`); il polling riallinea il client. |
| Cosa succede se uno non consegna? | L'host può forzare; lo studente avanza col codice che ha, o NULL. |
| L'AI può sbagliare? | Sì, è un supporto didattico, non un voto ufficiale; c'è sempre il feedback testuale da leggere. |
| Sicurezza password? | Bcrypt con `password_hash`, mai in chiaro. |
| E se entra qualcuno a partita iniziata? | Bloccato: protezione late-join. |

---

## ✅ Checklist pratica pre-presentazione

- **Prova la demo la sera prima** end-to-end almeno una volta: è la parte che può rompersi (rete/AI). Tieni pronta una partita già finita come backup se l'AI è lenta.
- Tempi demo: se sforate, Cucchi può **forzare i turni** invece di aspettare i timer.
- Ognuno conosca a memoria solo la **propria** sezione + sappia rispondere a 1-2 domande delle altre.
- XAMPP (Apache + MariaDB) attivi · `AI_API_KEY` valida · classe + consegna + 3 studenti pronti.
