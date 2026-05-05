<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = '';
$input = [];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
} else {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
    $action = $input['action'] ?? '';
}

$response = handleAction($action, $input);
echo json_encode($response);

function handleAction($action, $input) {
    if (!isLoggedIn()) {
        $result = ['error' => 'Non autenticato', 'success' => false];
    } else {
        if ($action === 'lobby_state') {
            $result = handleLobbyState();
        } elseif ($action === 'game_state') {
            $result = handleGameState();
        } elseif ($action === 'start_game') {
            $result = handleStartGame($input);
        } elseif ($action === 'submit_code') {
            $result = handleSubmitCode($input);
        } elseif ($action === 'advance_phase') {
            $result = handleAdvancePhase($input);
        } else {
            $result = ['error' => 'Azione non valida', 'success' => false];
        }
    }
    return $result;
}

function handleLobbyState() {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        $result = ['error' => 'ID mancante', 'success' => false];
    } else {
        $partita = getPartitaById($id);
        if (!$partita) {
            $result = ['error' => 'Partita non trovata', 'success' => false];
        } else {
            $partecipazioni = getPartecipazioniByPartita($id);
            $studenti = array_map(fn($p) => [
                'id' => $p['studente_id'],
                'nome' => $p['nome'],
                'cognome' => $p['cognome']
            ], $partecipazioni);
            $result = [
                'success' => true,
                'stato' => $partita['stato'],
                'studenti' => $studenti,
                'count' => count($studenti)
            ];
        }
    }
    return $result;
}

function handleGameState() {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        $result = ['error' => 'ID mancante', 'success' => false];
    } else {
        $partita = getPartitaById($id);
        if (!$partita) {
            $result = ['error' => 'Partita non trovata', 'success' => false];
        } else {
            $tempoScaduto = $partita['stato'] !== 'attesa'
                && $partita['stato'] !== 'finita'
                && !empty($partita['fase_inizio'])
                && getTempoRimanente($partita) <= 0;

            if ($tempoScaduto) {
                $nuovoStato = advanceGamePhase($partita);
                $partita = getPartitaById($id);
            }

            $result = [
                'success' => true,
                'stato' => $partita['stato'],
                'round' => $partita['round_corrente'],
                'tempo_rimanente' => getTempoRimanente($partita)
            ];
        }
    }
    return $result;
}

function handleStartGame($input) {
    $partita_id = (int)($input['partita_id'] ?? 0);
    if (!isHost()) {
        $result = ['error' => 'Solo host può avviare', 'success' => false];
    } elseif ($partita_id <= 0) {
        $result = ['error' => 'ID mancante', 'success' => false];
    } else {
        $partita = getPartitaById($partita_id);
        if (!$partita || $partita['host_id'] != $_SESSION['user_id']) {
            $result = ['error' => 'Partita non trovata o non autorizzato', 'success' => false];
        } elseif ($partita['stato'] !== 'attesa') {
            $result = ['error' => 'Partita già avviata', 'success' => false];
        } else {
            $participants = getPartecipazioniByPartita($partita_id);
            if (count($participants) < 2) {
                $result = ['error' => 'Servono almeno 2 studenti', 'success' => false];
            } else {
                $db = getDB();
                $db->prepare(
                    'UPDATE partite SET stato = "lettura", fase_inizio = NOW() WHERE id = ?'
                )->execute([$partita_id]);
                createTurniForGame($partita_id, $participants);
                $result = ['success' => true, 'stato' => 'lettura'];
            }
        }
    }
    return $result;
}

function handleSubmitCode($input) {
    $partita_id = (int)($input['partita_id'] ?? 0);
    $codice = $input['codice'] ?? '';
    if (!isStudent()) {
        $result = ['error' => 'Solo studenti possono consegnare codice', 'success' => false];
    } elseif ($partita_id <= 0) {
        $result = ['error' => 'ID mancante', 'success' => false];
    } else {
        $partita = getPartitaById($partita_id);
        if (!$partita || $partita['stato'] !== 'scrittura') {
            $result = ['error' => 'Partita non in fase di scrittura', 'success' => false];
        } else {
            $turno = getTurnoCorrente($partita_id, $_SESSION['user_id'], $partita['round_corrente']);
            if (!$turno) {
                $result = ['error' => 'Turno non trovato', 'success' => false];
            } elseif ($turno['submitted_at']) {
                $result = ['success' => true, 'already_submitted' => true];
            } else {
                $db = getDB();
                $db->prepare(
                    'UPDATE turni SET codice = ?, submitted_at = NOW() WHERE id = ?'
                )->execute([$codice, $turno['id']]);

                $gameEnded = false;
                if (allSubmitted($partita_id, $partita['round_corrente'])) {
                    $nuovoStato = advanceGamePhase($partita);
                    if ($nuovoStato === 'finita') {
                        $gameEnded = true;
                    }
                }
                $result = ['success' => true, 'game_ended' => $gameEnded];
            }
        }
    }
    return $result;
}

function handleAdvancePhase($input) {
    $partita_id = (int)($input['partita_id'] ?? 0);
    if (!isHost()) {
        $result = ['error' => 'Solo host', 'success' => false];
    } elseif ($partita_id <= 0) {
        $result = ['error' => 'ID mancante', 'success' => false];
    } else {
        $partita = getPartitaById($partita_id);
        if (!$partita || $partita['host_id'] != $_SESSION['user_id']) {
            $result = ['error' => 'Non autorizzato', 'success' => false];
        } else {
            $nuovoStato = advanceGamePhase($partita);
            $result = ['success' => true, 'stato' => $nuovoStato];
        }
    }
    return $result;
}
