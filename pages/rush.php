<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classe_id = (int)($_POST['classe_id'] ?? 0);
    $domanda_id = (int)($_POST['domanda_id'] ?? 0);
    $tempo_lettura = (int)($_POST['tempo_lettura'] ?? 0);
    $tempo_turno = (int)($_POST['tempo_turno'] ?? 0);

    if ($classe_id <= 0) {
        $errors[] = 'Seleziona una classe.';
    }
    if ($domanda_id <= 0) {
        $errors[] = 'Seleziona una consegna.';
    }
    if ($tempo_lettura < 10 || $tempo_lettura > 600) {
        $errors[] = 'Tempo lettura: da 10 a 600 secondi.';
    }
    if ($tempo_turno < 30 || $tempo_turno > 1800) {
        $errors[] = 'Tempo per turno: da 30 a 1800 secondi.';
    }

    if (empty($errors)) {
        $classe = getClasseById($classe_id);
        $domanda = getDomandaById($domanda_id);
        if (!$classe || !$domanda) {
            $errors[] = 'Classe o consegna non valida.';
        } else {
            $codice = generateAccessCode();
            $db->prepare(
                'INSERT INTO partite (host_id, classe_id, domanda_id, tempo_lettura, tempo_turno, codice_accesso)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$_SESSION['user_id'], $classe_id, $domanda_id, $tempo_lettura, $tempo_turno, $codice]);
            $partita_id = $db->lastInsertId();
            header('Location: /CodeRush/pages/lobby.php?id=' . $partita_id);
            exit();
        }
    }
}

$classi = getAllClassi();
$domande = getDomandeByHost($_SESSION['user_id']);

$pageTitle = 'Nuovo Rush';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="page-header">
        <h1>Crea un nuovo Rush</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>

    <?php if (empty($classi)): ?>
        <div class="alert alert-warning">
            Nessuna classe disponibile. <a href="/CodeRush/pages/classi.php">Crea una classe</a> prima di avviare un Rush.
        </div>
    <?php elseif (empty($domande)): ?>
        <div class="alert alert-warning">
            Nessuna consegna disponibile. <a href="/CodeRush/pages/nuova-domanda.php">Crea una consegna</a> prima di avviare un Rush.
        </div>
    <?php else: ?>
    <div class="card">
        <form method="POST" novalidate id="formRush">
            <div class="form-group">
                <label class="form-label">Classe</label>
                <select name="classe_id" class="form-control" required>
                    <option value="0">— Seleziona classe —</option>
                    <?php foreach ($classi as $cl): ?>
                        <option value="<?= $cl['id'] ?>">
                            <?= sanitize($cl['anno'] . $cl['sezione'] . ' ' . $cl['indirizzo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Consegna</label>
                <select name="domanda_id" class="form-control" required id="selectDomanda">
                    <option value="0">— Seleziona consegna —</option>
                    <?php foreach ($domande as $d): ?>
                        <option value="<?= $d['id'] ?>">
                            <?= sanitize($d['nome']) ?> — <?= sanitize($d['linguaggio_nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="previewDomanda" style="display: none;" class="alert alert-info" style="margin-bottom: 20px;"></div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tempo di lettura (secondi)</label>
                    <input type="number" name="tempo_lettura" class="form-control" value="60" min="10" max="600" required>
                    <div class="form-text">Tempo per leggere la consegna prima di scrivere codice (10-600 sec).</div>
                    <div class="error-text" id="err-lettura"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Tempo per turno (secondi)</label>
                    <input type="number" name="tempo_turno" class="form-control" value="120" min="30" max="1800" required>
                    <div class="form-text">Tempo di scrittura per ogni turno (30-1800 sec).</div>
                    <div class="error-text" id="err-turno"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="min-width: 160px;">Crea partita</button>
        </form>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
<script>
const domande = <?= json_encode(array_map(fn($d) => ['id' => $d['id'], 'nome' => $d['nome'], 'testo' => $d['testo']], $domande)) ?>;
document.getElementById('selectDomanda')?.addEventListener('change', function() {
    const preview = document.getElementById('previewDomanda');
    const d = domande.find(x => x.id == this.value);
    if (d) {
        preview.textContent = d.testo;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});
</script>
<script src="/CodeRush/js/script.js"></script>
