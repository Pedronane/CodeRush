# Setup e installazione

## Requisiti

| Requisito | Versione minima |
|---|---|
| XAMPP | 8.x (Apache + MariaDB) |
| PHP | 8.0+ |
| MariaDB / MySQL | 10.4+ |
| Browser | Chrome, Firefox, Edge moderni |

Il progetto gira interamente in locale — nessun server esterno richiesto (eccetto l'API AI opzionale).

---

## Installazione

### 1. Posiziona il progetto

Clona o copia la repo nella cartella `htdocs` di XAMPP:

```
C:\xampp\htdocs\CodeRush\
```

L'URL base sarà: `http://localhost/CodeRush/`

### 2. Avvia XAMPP

Avvia **Apache** e **MySQL/MariaDB** dal pannello di controllo XAMPP.

### 3. Crea il database

Apri il browser e vai su:

```
http://localhost/CodeRush/setup.php
```

Compila il form con i dati del primo account **host** (il professore). Il setup:
- Crea il database `coderush`
- Crea tutte le tabelle
- Inserisce i linguaggi di programmazione predefiniti
- Crea il primo account host

> **Attenzione:** `setup.php` va eseguito **una sola volta**. Non ha protezioni per riesecuzioni multiple — se lo riesegui, potrebbe tentare di ricreare tabelle già esistenti (le `CREATE TABLE IF NOT EXISTS` evitano errori, ma l'account host verrebbe duplicato se usi lo stesso username).

### 4. Accedi

```
http://localhost/CodeRush/login.php
```

Usa le credenziali inserite nel setup (username + password host).

---

## Configurazione

Tutto si configura in `includes/config.php`:

```php
define('BASE_URL', '/CodeRush');      // URL base del progetto
define('DB_HOST', 'localhost');        // Host MariaDB
define('DB_NAME', 'coderush');         // Nome database
define('DB_USER', 'root');             // Utente DB (default XAMPP)
define('DB_PASS', '');                 // Password DB (default XAMPP: vuota)
define('AI_API_KEY', '');              // Chiave Anthropic (opzionale)
```

### Cambiare URL base

Se il progetto è in una sottocartella diversa da `/CodeRush/`, aggiorna `BASE_URL`. Esempio per root:

```php
define('BASE_URL', '');
```

### Database su porta non standard

Se MariaDB gira su porta diversa dalla 3306, modifica il DSN in `includes/db.php`:

```php
'mysql:host=localhost;port=3307;dbname=coderush;charset=utf8mb4'
```

---

## Lingua e linguaggi predefiniti

Lo schema SQL inserisce automaticamente questi linguaggi al setup:

```
Python, JavaScript, Java, C, C++, PHP, SQL, HTML/CSS
```

Puoi aggiungerne altri dalla pagina [Linguaggi](../pages/linguaggi.php) dopo il login.

---

## Reset completo

Per ricominciare da zero (cancella tutti i dati):

1. Apri **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Seleziona il database `coderush`
3. Clicca **Elimina** (drop database)
4. Riesegui `setup.php`

Oppure via SQL:

```sql
DROP DATABASE coderush;
```

---

## Problemi comuni

| Problema | Soluzione |
|---|---|
| Pagina bianca | Controlla che Apache sia avviato in XAMPP |
| Errore connessione DB | Verifica che MariaDB sia avviato e le credenziali in `config.php` siano corrette |
| `setup.php` errore "table exists" | Il DB esiste già — usa phpMyAdmin per controllare o droppare e rifare |
| Login non funziona | Assicurati di aver completato il setup; usa username (non email) |
| AI non valuta | `AI_API_KEY` vuota in `config.php` — il sistema usa fallback "parziale" |
