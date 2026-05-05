<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$db = getDB();
$errors = [];
$success = '';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editClasse = null;

if ($editId > 0) {
    $editClasse = getClasseById($editId);
    if (!$editClasse) {
        $editId = 0;
    }
}

$indirizzi = ['Informatica', 'Meccanica', 'Elettronica', 'Chimica', 'Costruzioni', 'Logistica', 'Turismo', 'Agraria', 'Grafica', 'Moda'];
$sezioni = range('A', 'K');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $anno = (int)($_POST['anno'] ?? 0);
    $sezione = strtoupper(trim($_POST['sezione'] ?? ''));
    $indirizzo = trim($_POST['indirizzo'] ?? '');

    if ($anno < 1 || $anno > 5) {
        $errors[] = 'Anno deve essere tra 1 e 5.';
    }
    if (empty($sezione) || !in_array($sezione, array_map('strtoupper', $sezioni))) {
        $errors[] = 'Sezione non valida.';
    }
    if (empty($indirizzo)) {
        $errors[] = 'Indirizzo obbligatorio.';
    }

    if (empty($errors)) {
        if ($action === 'edit' && $editId > 0) {
            $chk = $db->prepare('SELECT id FROM classi WHERE anno = ? AND sezione = ? AND indirizzo = ? AND id != ?');
            $chk->execute([$anno, $sezione, $indirizzo, $editId]);
            if ($chk->fetch()) {
                $errors[] = 'Questa combinazione anno/sezione/indirizzo esiste già.';
            } else {
                $db->prepare('UPDATE classi SET anno = ?, sezione = ?, indirizzo = ? WHERE id = ?')
                   ->execute([$anno, $sezione, $indirizzo, $editId]);
                $success = 'Classe aggiornata.';
                $editId = 0;
                $editClasse = null;
            }
        } else {
            $chk = $db->prepare('SELECT id FROM classi WHERE anno = ? AND sezione = ? AND indirizzo = ?');
            $chk->execute([$anno, $sezione, $indirizzo]);
            if ($chk->fetch()) {
                $errors[] = 'Questa classe esiste già.';
            } else {
                $db->prepare('INSERT INTO classi (anno, sezione, indirizzo) VALUES (?, ?, ?)')
                   ->execute([$anno, $sezione, $indirizzo]);
                $success = 'Classe creata: ' . $anno . $sezione . ' ' . sanitize($indirizzo) . '.';
            }
        }
    }
}

$classi = getAllClassi();

$pageTitle = 'Classi';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="page-header">
        <h1>Classi</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        <div class="card" style="padding: 0;">
            <div class="card-title" style="padding: 16px 20px; margin: 0;">Classi registrate (<?= count($classi) ?>)</div>
            <?php if (empty($classi)): ?>
                <div class="empty-state"><p>Nessuna classe. Creane una.</p></div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Indirizzo</th>
                        <th>Studenti</th>
                        <th style="width: 150px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classi as $cl): ?>
                    <?php
                        $stmtCount = $db->prepare('SELECT COUNT(*) FROM studente_classe WHERE classe_id = ?');
                        $stmtCount->execute([$cl['id']]);
                        $nStudenti = $stmtCount->fetchColumn();
                    ?>
                    <tr>
                        <td><strong><?= $cl['anno'] . $cl['sezione'] ?></strong></td>
                        <td><?= sanitize($cl['indirizzo']) ?></td>
                        <td><?= $nStudenti ?></td>
                        <td>
                            <a href="/CodeRush/pages/classe.php?id=<?= $cl['id'] ?>" class="btn btn-sm btn-primary">Apri</a>
                            <a href="?edit=<?= $cl['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($editClasse): ?>
            <div class="card">
                <div class="card-title">Modifica classe</div>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="edit">
                    <div class="form-group">
                        <label class="form-label">Anno</label>
                        <select name="anno" class="form-control" required>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= $editClasse['anno'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sezione</label>
                        <select name="sezione" class="form-control" required>
                            <?php foreach ($sezioni as $s): ?>
                                <option value="<?= $s ?>" <?= $editClasse['sezione'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Indirizzo</label>
                        <select name="indirizzo" class="form-control" required>
                            <?php foreach ($indirizzi as $ind): ?>
                                <option value="<?= $ind ?>" <?= $editClasse['indirizzo'] === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary">Salva</button>
                        <a href="/CodeRush/pages/classi.php" class="btn btn-secondary">Annulla</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-title">Crea nuova classe</div>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label class="form-label">Anno</label>
                        <select name="anno" class="form-control" required>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sezione</label>
                        <select name="sezione" class="form-control" required>
                            <?php foreach ($sezioni as $s): ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Indirizzo</label>
                        <select name="indirizzo" class="form-control" required>
                            <?php foreach ($indirizzi as $ind): ?>
                                <option value="<?= $ind ?>"><?= $ind ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Crea classe</button>
                </form>
            </div>
        </div>
    </div>
</main>
</body>
</html>
<script src="/CodeRush/js/script.js"></script>
