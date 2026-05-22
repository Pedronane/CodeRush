<?php
require_once __DIR__ . '/db.php';

/* ── AUTH & SESSIONE ───────────────────────────────────────── */

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isHost() {
    return isLoggedIn() && $_SESSION['ruolo'] === 'host';
}

function isStudent() {
    return isLoggedIn() && $_SESSION['ruolo'] === 'studente';
}

function sanitize($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

/* ── UTENTI ────────────────────────────────────────────────── */

function loginUser($login_id, $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE login_id = ?');
    $stmt->execute([trim($login_id)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $result = $user;
    } else {
        $result = null;
    }
    return $result;
}

function getUserById($id) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/* ── PARTITE & RUSH ────────────────────────────────────────── */

function generateAccessCode() {
    $db = getDB();
    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $stmt = $db->prepare('SELECT id FROM partite WHERE codice_accesso = ?');
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        // Collisione: rigenera finché il codice non è univoco
        $result = generateAccessCode();
    } else {
        $result = $code;
    }
    return $result;
}

function getAllLinguaggi() {
    $db = getDB();
    $stmt = $db->query('SELECT * FROM linguaggi ORDER BY nome');
    return $stmt->fetchAll();
}

function getAllClassi() {
    $db = getDB();
    $stmt = $db->query('SELECT * FROM classi ORDER BY anno, sezione, indirizzo');
    return $stmt->fetchAll();
}

function getClasseById($id) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM classi WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getStudentiByClasse($classe_id) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT u.* FROM users u
         JOIN studente_classe sc ON sc.studente_id = u.id
         WHERE sc.classe_id = ?
         ORDER BY u.cognome, u.nome'
    );
    $stmt->execute([$classe_id]);
    return $stmt->fetchAll();
}

function getDomandeByHost($host_id, $search = '', $linguaggio_id = 0) {
    $db = getDB();
    $sql = 'SELECT d.*, l.nome AS linguaggio_nome FROM domande d
            JOIN linguaggi l ON l.id = d.linguaggio_id
            WHERE d.host_id = ?';
    $params = [$host_id];
    if ($search !== '') {
        $sql .= ' AND d.nome LIKE ?';
        $params[] = '%' . $search . '%';
    }
    if ($linguaggio_id > 0) {
        $sql .= ' AND d.linguaggio_id = ?';
        $params[] = $linguaggio_id;
    }
    $sql .= ' ORDER BY d.nome';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getDomandaById($id) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT d.*, l.nome AS linguaggio_nome FROM domande d
         JOIN linguaggi l ON l.id = d.linguaggio_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPartitaById($id) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT p.*, d.nome AS domanda_nome, d.testo AS domanda_testo,
                l.nome AS linguaggio_nome, c.anno, c.sezione, c.indirizzo,
                u.nome AS host_nome, u.cognome AS host_cognome
         FROM partite p
         JOIN domande d ON d.id = p.domanda_id
         JOIN linguaggi l ON l.id = d.linguaggio_id
         JOIN classi c ON c.id = p.classe_id
         JOIN users u ON u.id = p.host_id
         WHERE p.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPartitaByCode($code) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM partite WHERE codice_accesso = ?');
    $stmt->execute([strtoupper(trim($code))]);
    return $stmt->fetch();
}

function getPartecipazioniByPartita($partita_id) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT p.*, u.nome, u.cognome, u.login_id
         FROM partecipazioni p
         JOIN users u ON u.id = p.studente_id
         WHERE p.partita_id = ?
         ORDER BY p.slot_number'
    );
    $stmt->execute([$partita_id]);
    return $stmt->fetchAll();
}

function getPartecipazione($partita_id, $studente_id) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM partecipazioni WHERE partita_id = ? AND studente_id = ?'
    );
    $stmt->execute([$partita_id, $studente_id]);
    return $stmt->fetch();
}

function getTurnoCorrente($partita_id, $studente_id, $round) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT t.*, p.slot_number FROM turni t
         JOIN partecipazioni p ON p.id = t.slot_id
         WHERE t.partita_id = ? AND t.studente_id = ? AND t.numero_turno = ?'
    );
    $stmt->execute([$partita_id, $studente_id, $round]);
    return $stmt->fetch();
}

// Codice prodotto sullo stesso slot nel round precedente (ciò che lo studente eredita)
function getPreviousCodice($slot_id, $round) {
    if ($round === 0) {
        // Primo round: nessun codice ereditato, si parte da zero
        $result = null;
    } else {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT codice FROM turni WHERE slot_id = ? AND numero_turno = ?'
        );
        $stmt->execute([$slot_id, $round - 1]);
        $row = $stmt->fetch();
        if ($row) {
            $result = $row['codice'];
        } else {
            $result = null;
        }
    }
    return $result;
}

function allSubmitted($partita_id, $round) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS cnt FROM turni
         WHERE partita_id = ? AND numero_turno = ? AND submitted_at IS NULL'
    );
    $stmt->execute([$partita_id, $round]);
    $row = $stmt->fetch();
    return (int)$row['cnt'] === 0;
}

function getPartiteByStudente($studente_id) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT p.*, par.id AS slot_id, d.nome AS domanda_nome, l.nome AS linguaggio_nome,
                c.anno, c.sezione, c.indirizzo,
                u.nome AS host_nome, u.cognome AS host_cognome,
                v.voto
         FROM partite p
         JOIN partecipazioni par ON par.partita_id = p.id AND par.studente_id = ?
         JOIN domande d ON d.id = p.domanda_id
         JOIN linguaggi l ON l.id = d.linguaggio_id
         JOIN classi c ON c.id = p.classe_id
         JOIN users u ON u.id = p.host_id
         LEFT JOIN valutazioni v ON v.slot_id = par.id
         WHERE p.stato = "finita"
         ORDER BY p.created_at DESC'
    );
    $stmt->execute([$studente_id]);
    return $stmt->fetchAll();
}

function getRushesStorico($host_id) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT p.id, p.created_at, p.classe_id,
                c.anno, c.sezione, c.indirizzo,
                p.domanda_id, d.nome AS domanda_nome,
                l.id AS linguaggio_id, l.nome AS linguaggio_nome,
                GROUP_CONCAT(par.studente_id ORDER BY par.studente_id) AS studente_ids,
                COUNT(par.id) AS n_partecipanti
         FROM partite p
         JOIN domande d ON d.id = p.domanda_id
         JOIN linguaggi l ON l.id = d.linguaggio_id
         JOIN classi c ON c.id = p.classe_id
         LEFT JOIN partecipazioni par ON par.partita_id = p.id
         WHERE p.host_id = ? AND p.stato = "finita"
         GROUP BY p.id
         ORDER BY p.created_at DESC'
    );
    $stmt->execute([$host_id]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['studente_ids'] = $r['studente_ids']
            ? array_map('intval', explode(',', $r['studente_ids']))
            : [];
    }
    return $rows;
}

function getRushByClasse($classe_id) {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT p.*, d.nome AS domanda_nome, u.nome AS host_nome, u.cognome AS host_cognome
         FROM partite p
         JOIN domande d ON d.id = p.domanda_id
         JOIN users u ON u.id = p.host_id
         WHERE p.classe_id = ? AND p.stato = "finita"
         ORDER BY p.created_at DESC'
    );
    $stmt->execute([$classe_id]);
    return $stmt->fetchAll();
}

/* ── LOGICA DI GIOCO ───────────────────────────────────────── */

// Secondi mancanti alla fine della fase corrente (0 se scaduta)
function getTempoRimanente($partita) {
    if (empty($partita['fase_inizio'])) {
        $result = 0;
    } else {
        $inizio = strtotime($partita['fase_inizio']);
        $now = time();
        if ($partita['stato'] === 'lettura') {
            $durata = (int)$partita['tempo_lettura'];
        } else {
            $durata = (int)$partita['tempo_turno'];
        }
        $rimanente = $durata - ($now - $inizio);
        if ($rimanente < 0) {
            $result = 0;
        } else {
            $result = $rimanente;
        }
    }
    return $result;
}

// Pre-genera tutti i turni della partita: meccanica "telefono senza fili".
// Ogni round ciascuno studente lavora sullo slot di un compagno diverso,
// così dopo n round ogni codice è passato per tutte le mani.
function createTurniForGame($partita_id, $participants) {
    $db = getDB();
    $n = count($participants);
    $slotMap = [];
    foreach ($participants as $p) {
        $slotMap[$p['slot_number']] = $p['id'];
    }
    $stmt = $db->prepare(
        'INSERT INTO turni (partita_id, studente_id, slot_id, numero_turno) VALUES (?, ?, ?, ?)'
    );
    for ($round = 0; $round < $n; $round++) {
        foreach ($participants as $p) {
            $studentSlot = $p['slot_number'];
            // Rotazione a ritroso; il doppio modulo evita risultati negativi di %
            $workSlot = (($studentSlot - $round) % $n + $n) % $n;
            $slotId = $slotMap[$workSlot];
            $stmt->execute([$partita_id, $p['studente_id'], $slotId, $round]);
        }
    }
    return true;
}

// Macchina a stati della partita: lettura → scrittura → (round successivi) → finita
function advanceGamePhase($partita) {
    $db = getDB();
    $partita_id = $partita['id'];
    if ($partita['stato'] === 'lettura') {
        $db->prepare(
            'UPDATE partite SET stato = "scrittura", fase_inizio = NOW() WHERE id = ?'
        )->execute([$partita_id]);
        $result = 'scrittura';
    } elseif ($partita['stato'] === 'scrittura') {
        $participants = getPartecipazioniByPartita($partita_id);
        $n = count($participants);
        $nextRound = $partita['round_corrente'] + 1;
        // Esauriti i round (uno per studente) la partita finisce e parte la valutazione
        if ($nextRound >= $n) {
            $db->prepare(
                'UPDATE partite SET stato = "finita", fase_inizio = NULL WHERE id = ?'
            )->execute([$partita_id]);
            triggerAIEvaluation($partita_id);
            $result = 'finita';
        } else {
            $db->prepare(
                'UPDATE partite SET round_corrente = ?, fase_inizio = NOW() WHERE id = ?'
            )->execute([$nextRound, $partita_id]);
            $result = 'scrittura';
        }
    } else {
        $result = $partita['stato'];
    }
    return $result;
}

// Valuta con l'AI il codice finale di ogni slot a partita conclusa
function triggerAIEvaluation($partita_id) {
    $db = getDB();
    $partita = getPartitaById($partita_id);
    $domanda = getDomandaById($partita['domanda_id']);
    $participants = getPartecipazioniByPartita($partita_id);
    $n = count($participants);
    foreach ($participants as $p) {
        $stmt = $db->prepare(
            'SELECT codice FROM turni WHERE slot_id = ? AND numero_turno = ?'
        );
        $stmt->execute([$p['id'], $n - 1]);
        $lastTurn = $stmt->fetch();
        if ($lastTurn && $lastTurn['codice']) {
            $eval = evaluateCode(
                $domanda['testo'],
                $lastTurn['codice'],
                $domanda['nome']
            );
            // Evita doppie valutazioni se la funzione viene richiamata
            $checkStmt = $db->prepare('SELECT id FROM valutazioni WHERE slot_id = ?');
            $checkStmt->execute([$p['id']]);
            if (!$checkStmt->fetch()) {
                $db->prepare(
                    'INSERT INTO valutazioni (slot_id, voto, feedback) VALUES (?, ?, ?)'
                )->execute([$p['id'], $eval['voto'], $eval['feedback']]);
            }
        }
    }
    return true;
}

// Chiede a Claude un voto sul codice; ritorna sempre un esito (fallback "parziale")
function evaluateCode($domanda, $codice, $nomeDomanda) {
    $apiKey = defined('AI_API_KEY') ? AI_API_KEY : '';
    if (empty($apiKey) || empty(trim($codice))) {
        // Senza chiave o senza codice non si può valutare: esito neutro
        $result = ['voto' => 'parziale', 'feedback' => 'Valutazione automatica non disponibile.'];
    } else {
        $prompt = "Sei un valutatore di codice scolastico. Consegna: \"$nomeDomanda\"\n\nDettaglio: $domanda\n\nCodice finale:\n$codice\n\nRispondi SOLO con JSON: {\"voto\": \"corretto|parziale|sbagliato\", \"feedback\": \"spiegazione breve\"}";
        $payload = json_encode([
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 300,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 15
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        if ($data && isset($data['content'][0]['text'])) {
            $parsed = json_decode($data['content'][0]['text'], true);
            if ($parsed && isset($parsed['voto'])) {
                $result = $parsed;
            } else {
                $result = ['voto' => 'parziale', 'feedback' => 'Errore nel parsing AI.'];
            }
        } else {
            $result = ['voto' => 'parziale', 'feedback' => 'Errore chiamata API.'];
        }
    }
    return $result;
}
