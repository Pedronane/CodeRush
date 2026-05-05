<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$partita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partita = $partita_id > 0 ? getPartitaById($partita_id) : null;

if (!$partita || $partita['stato'] !== 'finita') {
    header('Location: /CodeRush/pages/classi.php');
    exit();
}

$db = getDB();
$partecipazioni = getPartecipazioniByPartita($partita_id);
$n = count($partecipazioni);

$pageTitle = 'Analisi Rush — ' . $partita['domanda_nome'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="breadcrumb">
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">/</span>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>"><?= sanitize($partita['anno'] . $partita['sezione'] . ' ' . $partita['indirizzo']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <span>Rush <?= date('d/m/Y', strtotime($partita['created_at'])) ?></span>
    </div>

    <div class="page-header">
        <div>
            <h1>Analisi: <?= sanitize($partita['domanda_nome']) ?></h1>
            <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">
                <?= sanitize($partita['linguaggio_nome']) ?>
                &nbsp;·&nbsp; <?= $n ?> partecipanti
                &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($partita['created_at'])) ?>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Consegna originale</div>
        <div style="white-space: pre-wrap; line-height: 1.7;"><?= sanitize($partita['domanda_testo']) ?></div>
    </div>

    <?php foreach ($partecipazioni as $part):
        $stmtVal = $db->prepare('SELECT * FROM valutazioni WHERE slot_id = ?');
        $stmtVal->execute([$part['id']]);
        $val = $stmtVal->fetch();

        $stmtTurni = $db->prepare(
            'SELECT t.*, u.nome, u.cognome FROM turni t
             JOIN users u ON u.id = t.studente_id
             WHERE t.slot_id = ?
             ORDER BY t.numero_turno ASC'
        );
        $stmtTurni->execute([$part['id']]);
        $turni = $stmtTurni->fetchAll();
    ?>
    <div class="chain-card">
        <div class="chain-header">
            <div>
                <strong>Catena avviata da:</strong> <?= sanitize($part['cognome'] . ' ' . $part['nome']) ?>
                <span style="color: var(--text-muted); margin-left: 8px; font-size: 13px;">(slot <?= $part['slot_number'] + 1 ?>)</span>
            </div>
            <?php if ($val): ?>
            <span class="badge badge-<?= $val['voto'] ?>"><?= ucfirst($val['voto']) ?></span>
            <?php else: ?>
            <span class="badge badge-attesa">Nessuna valutazione</span>
            <?php endif; ?>
        </div>

        <?php if ($val): ?>
        <div style="padding: 14px 20px; background: rgba(88,166,255,0.05); border-bottom: 1px solid var(--border);">
            <strong style="color: var(--accent);">Feedback AI:</strong>
            <span style="margin-left: 8px;"><?= sanitize($val['feedback']) ?></span>
        </div>
        <?php endif; ?>

        <?php foreach ($turni as $i => $t): ?>
        <div class="chain-turn">
            <div class="chain-turn-meta">
                <span class="turn-number"><?= $i + 1 ?></span>
                <strong><?= sanitize($t['nome'] . ' ' . $t['cognome']) ?></strong>
                <?php if ($t['submitted_at']): ?>
                <span style="color: var(--text-muted);"><?= date('H:i:s', strtotime($t['submitted_at'])) ?></span>
                <?php endif; ?>
                <?php if ($i === $n - 1): ?>
                <span class="badge badge-corretto" style="margin-left: auto;">Finale</span>
                <?php endif; ?>
            </div>
            <?php if ($t['codice']): ?>
            <div class="code-block"><?= sanitize($t['codice']) ?></div>
            <?php else: ?>
            <div class="code-block code-block-empty">Nessun codice consegnato</div>
            <?php endif; ?>
            <?php if ($i > 0 && $turni[$i-1]['codice'] && $t['codice']): ?>
            <details style="margin-top: 8px;">
                <summary style="cursor: pointer; font-size: 12px; color: var(--text-muted);">Mostra diff rispetto al turno precedente</summary>
                <div id="diff-<?= $part['id'] ?>-<?= $i ?>" style="margin-top: 8px;"></div>
            </details>
            <script>
            (function() {
                const prev = <?= json_encode($turni[$i-1]['codice']) ?>;
                const curr = <?= json_encode($t['codice']) ?>;
                const el = document.getElementById('diff-<?= $part['id'] ?>-<?= $i ?>');
                if (el) el.innerHTML = '<pre class="code-block" style="background: var(--surface2);">' + simpleDiff(prev, curr) + '</pre>';
            })();
            </script>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div style="margin-top: 24px; display: flex; gap: 12px;">
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>" class="btn btn-secondary">Torna alla classe</a>
    </div>
</main>
</body>
</html>
<script>
function simpleDiff(prev, curr) {
    const prevLines = prev.split('\n');
    const currLines = curr.split('\n');
    let html = '';
    const maxLen = Math.max(prevLines.length, currLines.length);
    for (let i = 0; i < maxLen; i++) {
        const p = prevLines[i] !== undefined ? prevLines[i] : null;
        const c = currLines[i] !== undefined ? currLines[i] : null;
        if (p === null) {
            html += '<span style="color: var(--success); display: block;">+ ' + escHtml(c) + '</span>';
        } else if (c === null) {
            html += '<span style="color: var(--danger); display: block;">- ' + escHtml(p) + '</span>';
        } else if (p !== c) {
            html += '<span style="color: var(--danger); display: block;">- ' + escHtml(p) + '</span>';
            html += '<span style="color: var(--success); display: block;">+ ' + escHtml(c) + '</span>';
        } else {
            html += '<span style="display: block; color: var(--text-muted);">  ' + escHtml(p) + '</span>';
        }
    }
    return html;
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
