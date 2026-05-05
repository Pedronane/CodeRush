# Valutazione AI

CodeRush usa l'API Anthropic per valutare automaticamente il codice finale prodotto da ogni catena al termine di un Rush.

---

## Configurazione

In `includes/config.php`:

```php
define('AI_API_KEY', 'sk-ant-api03-...');
```

Se `AI_API_KEY` è vuota o non definita, il sistema usa un **fallback**:
- `voto = parziale`
- `feedback = "Valutazione automatica non disponibile."`

---

## Modello usato

```
claude-haiku-4-5-20251001
```

Haiku è scelto per velocità e costo ridotto — la valutazione avviene su N codici alla fine di ogni partita.

---

## Come funziona

### Trigger

La valutazione viene avviata automaticamente quando la partita termina (funzione `triggerAIEvaluation` in `includes/functions.php`).

### Per ogni catena di codice

1. Recupera il codice dell'ultimo turno (round N-1) per quello slot
2. Costruisce il prompt con: nome consegna + testo consegna + codice finale
3. Chiama l'API Anthropic (REST, `curl`)
4. Parsa la risposta JSON
5. Salva in tabella `valutazioni`

### Prompt usato

```
Sei un valutatore di codice scolastico. Consegna: "<nome>"

Dettaglio: <testo_consegna>

Codice finale:
<codice_studente>

Rispondi SOLO con JSON: {"voto": "corretto|parziale|sbagliato", "feedback": "spiegazione breve"}
```

### Risposta attesa dall'AI

```json
{
  "voto": "corretto",
  "feedback": "La funzione calcola correttamente il fattoriale usando la ricorsione."
}
```

---

## Valori del voto

| Voto | Significato |
|---|---|
| `corretto` | Il codice risolve il problema richiesto |
| `parziale` | La logica è in parte valida ma presenta errori o mancanze |
| `sbagliato` | Non rispetta la consegna o contiene errori gravi |

---

## Gestione errori API

La funzione `evaluateCode()` gestisce i casi:

| Caso | Comportamento |
|---|---|
| `AI_API_KEY` vuota | Fallback: `parziale` + messaggio "non disponibile" |
| Codice vuoto | Fallback immediato |
| Risposta API non valida | `parziale` + "Errore nel parsing AI" |
| Timeout o rete | `parziale` + "Errore chiamata API" |

Il timeout è impostato a **15 secondi** per richiesta curl.

---

## Dove vengono mostrati i risultati

| Pagina | Cosa mostra |
|---|---|
| `pages/risultati.php` | Voto badge + feedback per ogni catena |
| `pages/rush-detail.php` | Stesso + integrato nell'analisi completa per catena |

### Rappresentazione visiva dei voti

```
● corretto   → badge verde
● parziale   → badge giallo
● sbagliato  → badge rosso
```

---

## Limitazioni note

- La valutazione è asincrona rispetto alla schermata risultati: se l'API Anthropic è lenta, il badge può mostrare "Nessuna valutazione" per qualche secondo dopo la fine della partita.
- Il sistema non valuta le singole modifiche turno-per-turno (solo il codice finale). L'analisi turno-per-turno su `rush-detail.php` è visuale (diff testuale), non AI.
- Un codice `NULL` (studente che non ha consegnato) viene saltato senza valutazione.
