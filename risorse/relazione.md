# Relazione

**Relazione di progetto – CodeRush** 
**Introduzione** 
Il progetto **CodeRush** nasce dall’idea di trasformare il classico gioco del _telefono senza fili_ in un’attività digitale legata alla programmazione. 
L’obiettivo è realizzare un applicativo web in cui più partecipanti collaborano, in modo alternato, alla realizzazione di un programma partendo da una stessa consegna iniziale. 
A differenza di un normale esercizio di coding individuale, in CodeRush ogni giocatore non lavora solo sul proprio codice, ma anche su quello scritto dagli altri partecipanti. In questo modo il progetto unisce aspetti di logica, collaborazione e analisi del codice. 
L’applicazione sarà sviluppata utilizzando **PHP, SQL(MariaDB), HTML, CSS e JavaScript.** 

**Idea generale del progetto** 
CodeRush è un gioco informatico multiplayer gestito da un **host**, cioè il creatore della sessione. 
L’host potrà aprire una partita, scegliere il numero massimo di partecipanti e preparare una o più domande o sfide di programmazione. 
I **player** potranno entrare nella sessione tramite un codice, con un sistema simile a quello di piattaforme come Kahoot. Una volta entrati tutti i partecipanti, l’host potrà avviare il gioco. 
Ogni sessione si svolgerà in questo modo: un giocatore legge la consegna, inizia a scrivere una possibile soluzione e, allo scadere del tempo, il suo codice viene passato a un altro partecipante. Quest’ultimo dovrà analizzarlo, comprenderlo e continuarlo o correggerlo. Il processo andrà avanti per più turni, finché tutti avranno lavorato anche sul codice degli altri. 
Alla fine della partita sarà mostrata una schermata conclusiva con il risultato finale di ogni codice e con un riepilogo delle modifiche effettuate dai partecipanti. 

**Funzionamento dell’applicazione** 
Il funzionamento di CodeRush può essere suddiviso in varie fasi. 
**1\. Creazione della sessione** 
L’host accede all’applicazione e crea una nuova sessione di gioco. 
Durante questa fase imposta: 
*   il nome o codice della sessione;  
*   il numero massimo di partecipanti;  
*   i tempi di gioco;  
*   una o più domande/sfide di programmazione.  
**2\. Accesso dei partecipanti** 
I player si collegano alla piattaforma e inseriscono il codice della sessione per entrare. 
L’host può vedere chi è entrato e decidere quando iniziare la partita. 
**3\. Presentazione della sfida** 
Quando la sessione parte, tutti i partecipanti visualizzano la stessa domanda o richiesta di programmazione. 
Esempi di sfide potrebbero essere: 
*   scrivere una funzione;  
*   completare un algoritmo;  
*   correggere un errore logico;  
*   sviluppare una piccola soluzione a un problema dato.  
**4\. Primo tempo: lettura e ragionamento** 
I player hanno a disposizione un primo intervallo di tempo, deciso dall’host, per leggere attentamente la consegna e pensare alla soluzione. 
**5\. Secondo tempo: scrittura iniziale** 
Successivamente si apre una fase in cui ciascun partecipante scrive il proprio codice o una parte della soluzione. 
**6\. Passaggio del codice** 
Scaduto il tempo, ogni codice viene assegnato a un altro player. 
Ogni partecipante riceve quindi il lavoro iniziato da un altro utente. 
**7\. Analisi e continuazione** 
Il nuovo player deve: 
*   leggere il codice ricevuto;  
*   capirne il funzionamento;  
*   individuare eventuali errori;  
*   completarlo o migliorarlo.  
Questa fase si ripete più volte, in modo che ogni partecipante lavori su elaborati diversi. 
**8\. Conclusione e analisi finale** 
Al termine del numero previsto di passaggi, il sistema mostra una schermata finale. 
L’host potrà visualizzare: 
*   il codice finale di ogni elaborato;  
*   quali utenti lo hanno modificato;  
*   le modifiche effettuate turno dopo turno;  
*   una valutazione generale del risultato.  

**Ruoli principali** 
All’interno dell’applicazione sono presenti due ruoli principali. 
**Host** 
L’host è il gestore della sessione. 
Le sue funzioni principali sono: 
*   creare la partita;  
*   impostare i parametri;  
*   inserire le domande;  
*   avviare e controllare i turni;  
*   visualizzare l’analisi finale.  
**Player** 
Il player è il partecipante alla sfida. 
Le sue azioni principali sono: 
*   entrare nella sessione tramite codice;  
*   leggere la consegna;  
*   scrivere una parte di codice;  
*   analizzare il codice ricevuto;  
*   modificarlo o completarlo nei turni successivi.  

**Caratteristiche principali del sistema** 
CodeRush dovrà includere alcune funzionalità fondamentali. 
La prima è la **gestione delle sessioni**, che permette di creare partite separate con accesso controllato. 
La seconda è la **gestione dei timer**, elemento centrale del gioco, perché ogni fase deve avere una durata precisa. 
Un’altra caratteristica importante è il **passaggio automatico del codice**, che rappresenta il cuore del progetto. 
Infine, sarà essenziale la presenza di una **schermata finale di riepilogo**, utile sia per il gioco sia per eventuali scopi didattici. 
Un ulteriore elemento innovativo sarà l’integrazione di un sistema di **valutazione automatica**, basato su intelligenza artificiale o su API esterne, capace di analizzare la correttezza delle soluzioni finali. 

**Analisi della valutazione automatica** 
Uno degli aspetti più interessanti del progetto è l’idea di integrare un sistema intelligente per l’analisi del codice. 
Questo sistema dovrà esaminare il risultato finale prodotto dai partecipanti e restituire un giudizio, per esempio: 
*   **corretto**, se la soluzione risolve il problema richiesto;  
*   **parzialmente corretto**, se la logica è in parte valida ma presenta errori o mancanze;  
*   **sbagliato**, se la soluzione non rispetta la consegna o contiene errori gravi.  
Oltre alla valutazione generale, il sistema potrebbe analizzare anche le singole modifiche effettuate dai vari player, evidenziandole con colori diversi: 
*   **verde** per le parti corrette;  
*   **giallo** per le parti parzialmente corrette;  
*   **rosso** per le parti errate.  
Questa funzione renderebbe CodeRush non solo un gioco, ma anche uno strumento utile per capire come evolve il codice e per riconoscere gli interventi positivi o negativi di ciascun partecipante. 

**Tecnologie utilizzate** 
L’applicazione sarà sviluppata con tecnologie web di base: 
**HTML** 
Verrà utilizzato per creare la struttura delle pagine, come schermate di accesso, area host, area player, editor di codice e pagina finale. 
**CSS** 
Anche se non richiesto esplicitamente, sarà utile per rendere l’interfaccia più chiara, ordinata e intuitiva. 
**JavaScript** 
Servirà per gestire l’interattività lato client, ad esempio: 
*   aggiornamento dei timer;  
*   passaggio tra le schermate;  
*   controllo di alcuni eventi in tempo reale;  
*   gestione dinamica dei contenuti mostrati all’utente.  
**PHP** 
Sarà il linguaggio principale lato server e avrà il compito di: 
*   creare e gestire le sessioni;  
*   salvare i dati dei partecipanti;  
*   memorizzare domande e codici;  
*   controllare i passaggi del gioco;  
*   collegarsi eventualmente a un database;  
*   comunicare con eventuali API esterne per la valutazione.  

**Possibile struttura dei dati** 
Per il corretto funzionamento del progetto sarà probabilmente necessario memorizzare diverse informazioni. 
Tra i dati principali ci saranno: 
*   sessioni create;  
*   utenti partecipanti;  
*   domande inserite dall’host;  
*   codici scritti nei vari turni;  
*   cronologia delle modifiche;  
*   risultati finali e valutazioni.  
Per questo motivo potrebbe essere utile collegare l’applicazione a un database, in modo da conservare in modo ordinato tutti i dati relativi alle partite. 

**Interfaccia dell’applicazione** 
L’interfaccia dovrà essere semplice e immediata, in modo da permettere l’utilizzo anche a utenti non esperti. 
Le principali schermate previste sono: 
*   pagina iniziale con scelta tra host e player;  
*   pagina di creazione o accesso alla sessione;  
*   schermata di attesa prima dell’avvio;  
*   schermata della domanda;  
*   editor per scrivere o modificare il codice;  
*   schermata finale con analisi e risultati.  
Dal punto di vista grafico sarà importante evidenziare bene: 
*   il tempo rimanente;  
*   la domanda da risolvere;  
*   il codice ricevuto;  
*   le modifiche effettuate;  
*   i risultati finali.  

**Aspetti innovativi del progetto** 
L’elemento originale di CodeRush è la trasformazione del concetto di _telefono senza fili_ in un ambiente di programmazione. 
Normalmente, negli esercizi scolastici, ogni studente scrive la propria soluzione in modo indipendente. In questo progetto, invece, il codice diventa un prodotto condiviso e in continua evoluzione. 
Questo approccio introduce aspetti molto interessanti: 
*   obbliga a leggere codice scritto da altri;  
*   mette in evidenza quanto sia importante scrivere in modo chiaro;  
*   sviluppa capacità di adattamento;  
*   stimola il ragionamento veloce;  
*   rende evidente come piccoli errori possano influenzare il risultato finale.  
Inoltre, l’aggiunta della valutazione automatica permette di unire gioco, apprendimento e analisi. 

**Pianificazione temporale del progetto** 
Il progetto viene suddiviso su **8 settimane**, distribuendo il lavoro in modo equilibrato tra i 3 componenti del gruppo. Tutti partecipano attivamente allo sviluppo del codice, occupandosi di moduli differenti ma integrati tra loro. 
**Settimana 1** 
*   analisi dei requisiti  
*   definizione delle funzionalità  
*   progettazione iniziale del database e delle schermate  
**Settimana 2** 
*   progettazione tecnica dettagliata  
*   avvio sviluppo delle pagine principali  
*   inizio sviluppo gestione sessioni e accesso dei player  
**Settimana 3** 
*   completamento pagine base  
*   sviluppo editor di scrittura codice  
*   implementazione iniziale di timer e fasi di gioco  
**Settimana 4** 
*   sviluppo del salvataggio del codice  
*   gestione dei turni  
*   collegamento tra frontend e backend  
**Settimana 5** 
*   implementazione della rotazione automatica del codice  
*   gestione dello storico delle modifiche  
*   inizio dashboard finale per host  
**Settimana 6** 
*   completamento dashboard host  
*   integrazione completa dei moduli  
*   prove di sessione complete  
**Settimana 7** 
*   testing generale  
*   correzione bug  
*   rifinitura dell’interfaccia  
*   possibile inizio modulo AI/API  
**Settimana 8** 
*   ultime correzioni  
*   completamento documentazione  
*   preparazione consegna finale 
**Divisione semplice dei 3 membri** 
Per renderlo chiaro nella relazione, puoi scrivere così: 
*   **Componente 1:** backend PHP, database, salvataggio dati, gestione sessioni  
*   **Componente 2:** frontend HTML/CSS/JavaScript, pagine e interfaccia  
*   **Componente 3:** logica di gioco, timer, turni, integrazione tra frontend e backend  
Comunque, **tutti collaborano al codice** durante tutto il progetto. 

**Conclusione** 
CodeRush è un progetto originale che unisce programmazione, gioco e collaborazione. 
L’idea di far passare il codice da un partecipante all’altro permette di trasformare un semplice esercizio in un’attività dinamica, dove non conta soltanto scrivere una soluzione, ma anche saper leggere, capire e migliorare quella degli altri. 
Dal punto di vista didattico, il progetto è interessante perché sviluppa competenze molto importanti nella programmazione reale, come la comprensione del codice altrui, la correzione degli errori e il lavoro condiviso. 
Dal punto di vista tecnico, l’applicazione può essere realizzata tramite PHP, HTML e JavaScript, costruendo una piattaforma web interattiva e accessibile. 
In conclusione, CodeRush rappresenta un’idea innovativa e concreta, capace di rendere l’apprendimento della programmazione più coinvolgente, moderno e collaborativo.