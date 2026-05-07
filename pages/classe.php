<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$db = getDB();
$classe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$classe    = $classe_id > 0 ? getClasseById($classe_id) : null;
if (!$classe) { header('Location: /CodeRush/pages/classi.php'); exit(); }

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'aggiungi_studente') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id <= 0) {
            $errors[] = 'Seleziona uno studente.';
        } else {
            $chk = $db->prepare('SELECT 1 FROM studente_classe WHERE studente_id=? AND classe_id=?');
            $chk->execute([$student_id,$classe_id]);
            if ($chk->fetch()) {
                $errors[] = 'Studente già in questa classe.';
            } else {
                $db->prepare('INSERT INTO studente_classe (studente_id,classe_id) VALUES (?,?)')->execute([$student_id,$classe_id]);
                $success = 'Studente aggiunto.';
            }
        }
    } elseif ($action === 'rimuovi_studente') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id > 0) {
            $db->prepare('DELETE FROM studente_classe WHERE studente_id=? AND classe_id=?')->execute([$student_id,$classe_id]);
            $success = 'Studente rimosso dalla classe.';
        }
    } elseif ($action === 'sposta_studente') {
        $student_id  = (int)($_POST['student_id']  ?? 0);
        $nuova_classe = (int)($_POST['nuova_classe'] ?? 0);
        if ($student_id <= 0 || $nuova_classe <= 0) {
            $errors[] = 'Dati mancanti per lo spostamento.';
        } elseif ($nuova_classe === $classe_id) {
            $errors[] = 'Lo studente è già in questa classe.';
        } else {
            $db->prepare('UPDATE studente_classe SET classe_id=? WHERE studente_id=? AND classe_id=?')->execute([$nuova_classe,$student_id,$classe_id]);
            $success = 'Studente spostato.';
        }
    }
}

$studenti          = getStudentiByClasse($classe_id);
$tuttiStudenti     = $db->query('SELECT * FROM users WHERE ruolo="studente" ORDER BY cognome,nome')->fetchAll();
$stmtInClasse      = $db->prepare('SELECT studente_id FROM studente_classe WHERE classe_id=?');
$stmtInClasse->execute([$classe_id]);
$idInClasse        = array_column($stmtInClasse->fetchAll(), 'studente_id');
$studentiDisponibili = array_filter($tuttiStudenti, fn($s) => !in_array($s['id'], $idInClasse));
$altreClassi       = array_filter(getAllClassi(), fn($c) => $c['id'] != $classe_id);
$rushes            = getRushByClasse($classe_id);

$pageTitle = $classe['anno'].$classe['sezione'].' '.$classe['indirizzo'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">

    <div class="breadcrumb" style="animation:fade-up .35s ease-out both;">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">›</span>
        <span><?= sanitize($classe['anno'].$classe['sezione'].' '.$classe['indirizzo']) ?></span>
    </div>

    <div class="page-header" style="animation:fade-up .4s ease-out both;">
        <div>
            <h1><?= sanitize($classe['anno'].$classe['sezione'].' '.$classe['indirizzo']) ?></h1>
            <p class="page-subtitle"><?= count($studenti) ?> studenti iscritti</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="/CodeRush/pages/rush.php" class="btn-primary-lg">▶ Nuovo Rush</a>
            <a href="/CodeRush/pages/classi.php?edit=<?= $classe_id ?>" class="btn-ghost">Modifica</a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="animation:fade-up .3s ease-out both;"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success" style="animation:fade-up .3s ease-out both;"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Students table -->
    <div class="card card-no-pad" style="animation:fade-up .45s ease-out .05s both;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);">Studenti</span>
            <span class="badge badge-host"><?= count($studenti) ?></span>
        </div>
        <?php if (empty($studenti)): ?>
        <div class="empty-state">
            <p>Nessuno studente in questa classe.</p>
            <a href="/CodeRush/pages/registra.php" class="btn-primary-lg">Crea studente</a>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Studente</th>
                    <th>Matricola</th>
                    <th style="width:280px;">Azioni</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($studenti as $s): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--brand-green),var(--brand-blue));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;">
                            <?= strtoupper(mb_substr($s['cognome'],0,1).mb_substr($s['nome'],0,1)) ?>
                        </div>
                        <a href="/CodeRush/pages/studente.php?id=<?= $s['id'] ?>" style="font-weight:700;">
                            <?= sanitize($s['cognome'].' '.$s['nome']) ?>
                        </a>
                    </div>
                </td>
                <td><code style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--muted-foreground);"><?= sanitize($s['login_id']) ?></code></td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                        <?php if (!empty($altreClassi)): ?>
                        <form method="POST" style="display:inline-flex;gap:4px;align-items:center;">
                            <input type="hidden" name="action"     value="sposta_studente">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <select name="nuova_classe" class="input-arena" style="padding:5px 10px;font-size:12px;width:auto;">
                                <?php foreach ($altreClassi as $ac): ?>
                                <option value="<?= $ac['id'] ?>"><?= sanitize($ac['anno'].$ac['sezione'].' '.$ac['indirizzo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline">Sposta</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Rimuovere lo studente dalla classe?')">
                            <input type="hidden" name="action"     value="rimuovi_studente">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Rimuovi</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Add student -->
    <?php if (!empty($studentiDisponibili)): ?>
    <div class="card" style="animation:fade-up .45s ease-out .1s both;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);margin-bottom:14px;">Aggiungi studente esistente</p>
        <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;">
            <input type="hidden" name="action" value="aggiungi_studente">
            <select name="student_id" class="input-arena" style="flex:1;max-width:380px;" required>
                <option value="0">— Seleziona studente —</option>
                <?php foreach ($studentiDisponibili as $s): ?>
                <option value="<?= $s['id'] ?>"><?= sanitize($s['cognome'].' '.$s['nome'].' ('.$s['login_id'].')') ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary-lg">Aggiungi</button>
        </form>
        <p style="font-size:12px;color:var(--muted-foreground);margin-top:10px;">
            Per creare un nuovo studente: <a href="/CodeRush/pages/registra.php">Registra utente →</a>
        </p>
    </div>
    <?php else: ?>
    <div class="card" style="animation:fade-up .45s ease-out .1s both;border-style:dashed;">
        <p style="color:var(--muted-foreground);font-size:13px;">Tutti gli studenti registrati sono già in questa classe.</p>
        <p style="margin-top:8px;"><a href="/CodeRush/pages/registra.php">Crea un nuovo studente →</a></p>
    </div>
    <?php endif; ?>

    <!-- Rush history -->
    <?php if (!empty($rushes)): ?>
    <div class="card card-no-pad" style="animation:fade-up .45s ease-out .15s both;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);">Rush completati</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Consegna</th>
                    <th>Host</th>
                    <th style="width:100px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rushes as $r): ?>
            <tr>
                <td style="color:var(--muted-foreground);font-size:12px;"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                <td style="font-weight:600;"><?= sanitize($r['domanda_nome']) ?></td>
                <td style="color:var(--muted-foreground);"><?= sanitize($r['host_nome'].' '.$r['host_cognome']) ?></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="/CodeRush/pages/risultati.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline">Risultati</a>
                        <a href="/CodeRush/pages/rush-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Analisi</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
