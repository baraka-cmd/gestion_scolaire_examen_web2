<?php
/**
 * Fichier : public/views/modules/bulletins/generer.php
 * Rôle    : Génération des bulletins pour une classe entière
 * Système : Bulletin officiel congolais (EPST/RDC)
 * Version : 3.0 — Grille complète Max/OBT
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('bulletins.generer');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'BulletinController.php';
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ResultatController.php';
$bulletinController = new BulletinController();
$resultatController = new ResultatController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $bulletinController->handleAjax();
}

$classes = $bulletinController->getClassesActives();
$trimestres = $bulletinController->getTrimestresActifs();
$annees = $bulletinController->getAnneesScolaires();

$classeId = (int) ($_GET['classe_id'] ?? $_POST['classe_id'] ?? 0);
$trimestreId = (int) ($_GET['trimestre_id'] ?? $_POST['trimestre_id'] ?? 0);
$anneeId = (int) ($_GET['annee_id'] ?? $_POST['annee_id'] ?? 0);

if ($anneeId <= 0) $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

$classe = null;
if ($classeId > 0) $classe = $db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]);

$bulletinsExistants = [];
if ($classeId > 0 && $trimestreId > 0) {
    $bulletinsExistants = $db->fetchAll(
        "SELECT b.*, e.matricule, e.nom, e.prenom, e.sexe,
                rt.moyenne_generale, rt.mention, rt.decision, rt.rang, rt.effectif_classe,
                DATE_FORMAT(b.date_edition, '%d/%m/%Y %H:%i') AS date_edition_format
         FROM bulletins b
         INNER JOIN eleves e ON b.eleve_id = e.id
         LEFT JOIN resultats_trimestriels rt ON b.eleve_id = rt.eleve_id AND b.trimestre_id = rt.trimestre_id
         WHERE b.trimestre_id = :tid
         AND b.eleve_id IN (
             SELECT eleve_id FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif' AND annee_scolaire_id = :aid
         )
         ORDER BY e.nom, e.prenom",
        ['tid' => $trimestreId, 'cid' => $classeId, 'aid' => $anneeId]
    );
}

$resultatsCalcules = false;
if ($classeId > 0 && $trimestreId > 0) {
    $nbResultats = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM resultats_trimestriels WHERE classe_id = :cid AND trimestre_id = :tid",
        ['cid' => $classeId, 'tid' => $trimestreId]
    );
    $resultatsCalcules = $nbResultats > 0;
}

$pageTitle = 'Générer les bulletins';
$pageIcon  = 'fa-file-invoice';
$pageDescription = 'Générez et imprimez les bulletins d\'une classe';
$extraCSS = ['modules/bulletins.css'];
$extraJS  = ['modules/bulletins.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/bulletins">Bulletins</a>
    <span class="separator">/</span>
    <span class="current">Générer</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-file-invoice"></i> Générer les bulletins</h4>
        <p>Générez les bulletins de tous les élèves d'une classe pour un trimestre.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/bulletins" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        <?php if (!empty($bulletinsExistants)): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer tous les bulletins</button>
        <?php endif; ?>
    </div>
</div>

<div id="flashMessage"></div>

<!-- SÉLECTEURS -->
<div class="resultats-selectors">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header"><i class="fa-solid fa-filter"></i> Sélectionner la classe et le trimestre</div>
        <div class="resultats-selector-body">
            <form method="GET" action="" class="resultats-selector-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe</label>
                        <select name="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez une classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>><?= e($cl['nom']) ?> (Niv. <?= $cl['niveau'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-calendar-alt"></i> Trimestre</label>
                        <select name="trimestre_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez un trimestre —</option>
                            <?php foreach ($trimestres as $trim): ?>
                                <option value="<?= $trim['id'] ?>" <?= $trimestreId === (int) $trim['id'] ? 'selected' : '' ?>><?= e($trim['libelle']) ?> <?= $trim['est_actif'] ? '(Actif)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-calendar"></i> Année</label>
                        <select name="annee_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $anneeId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['libelle']) ?> <?= $a['est_active'] ? '(Active)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($classeId > 0 && $trimestreId > 0): ?>

    <?php if (!$resultatsCalcules): ?>
        <div class="resultats-non-calcules">
            <div class="resultats-non-calcules-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <h5>Résultats non calculés</h5>
                <p>Les résultats pour cette classe et ce trimestre n'ont pas encore été calculés.</p>
                <p style="font-size:0.8rem;color:#92400e;">Vous devez d'abord calculer les résultats avant de générer les bulletins.</p>
                <a href="/gestion_scolaire/resultats/consulter?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-primary btn-sm mt-3"><i class="fa-solid fa-calculator"></i> Aller calculer les résultats</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($resultatsCalcules && empty($bulletinsExistants)): ?>
        <div class="resultats-non-calcules" style="background:linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);border-color:#bfdbfe;">
            <div class="resultats-non-calcules-icon" style="background:linear-gradient(135deg, #3b82f6, #60a5fa);"><i class="fa-solid fa-file-invoice"></i></div>
            <div>
                <h5 style="color:#1e40af;">Bulletins non générés</h5>
                <p style="color:#1e40af;">Les résultats sont calculés mais les bulletins n'ont pas encore été générés.</p>
                <p style="font-size:0.8rem;color:#3b82f6;"><i class="fa-solid fa-info-circle"></i> Classe : <strong><?= e($classe['nom']) ?></strong> — <?= $db->fetchColumn("SELECT libelle FROM trimestres WHERE id = :id", ['id' => $trimestreId]) ?></p>
                <button type="button" class="btn btn-success btn-sm mt-3" id="btnGenererBulletins"><i class="fa-solid fa-wand-magic-sparkles"></i> Générer les bulletins maintenant</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($bulletinsExistants)): ?>
        <div class="resultats-info-bar" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="resultats-info-item"><i class="fa-solid fa-check-circle"></i><span><strong><?= count($bulletinsExistants) ?></strong> bulletin(s) généré(s)</span></div>
            <div class="resultats-info-item"><i class="fa-solid fa-chalkboard"></i><span>Classe : <strong><?= e($classe['nom']) ?></strong></span></div>
            <div class="resultats-info-item"><i class="fa-solid fa-calendar-alt"></i><span>Trimestre : <strong><?= $db->fetchColumn("SELECT libelle FROM trimestres WHERE id = :id", ['id' => $trimestreId]) ?></strong></span></div>
            <div class="resultats-info-item"><i class="fa-solid fa-clock"></i><span>Généré le : <strong><?= $bulletinsExistants[0]['date_edition_format'] ?></strong></span></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="fa-solid fa-list-check"></i> Bulletins générés — <?= e($classe['nom']) ?></h5>
                <div class="d-flex gap-sm">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer tout</button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnRegenererBulletins"><i class="fa-solid fa-rotate"></i> Régénérer</button>
                </div>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th><th>Matricule</th><th>Élève</th><th>Sexe</th>
                                <th>Moyenne</th><th>Mention</th><th>Décision</th><th>Rang</th>
                                <th>Date génération</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bulletinsExistants as $index => $bul): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><span class="badge badge-primary"><?= e($bul['matricule']) ?></span></td>
                                    <td><strong><?= e($bul['prenom'] . ' ' . $bul['nom']) ?></strong></td>
                                    <td><?= $bul['sexe'] === 'M' ? 'M' : 'F' ?></td>
                                    <td><span class="pourcentage-badge <?= ($bul['moyenne_generale'] ?? 0) >= 70 ? 'success' : (($bul['moyenne_generale'] ?? 0) >= 50 ? 'warning' : 'danger') ?>"><?= number_format($bul['moyenne_generale'] ?? 0, 1) ?>%</span></td>
                                    <td><span class="badge badge-info"><?= e($bul['mention'] ?? '—') ?></span></td>
                                    <td><span class="badge <?= ($bul['decision'] ?? '') === 'Admis' ? 'badge-success' : 'badge-danger' ?>"><?= e($bul['decision'] ?? '—') ?></span></td>
                                    <td><?= !empty($bul['rang']) ? $bul['rang'] . 'ème / ' . $bul['effectif_classe'] : '—' ?></td>
                                    <td style="font-size:0.75rem;"><?= $bul['date_edition_format'] ?></td>
                                    <td>
                                        <div class="d-flex gap-xs">
                                            <a href="/gestion_scolaire/bulletins/consulter?eleve_id=<?= $bul['eleve_id'] ?>&annee_id=<?= $anneeId ?>" class="btn btn-sm btn-outline-primary" title="Voir le bulletin" target="_blank"><i class="fa-solid fa-eye"></i></a>
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="imprimerBulletin(<?= $bul['eleve_id'] ?>)" title="Imprimer"><i class="fa-solid fa-print"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php 
        $admisCount = count(array_filter($bulletinsExistants, fn($b) => ($b['decision'] ?? '') === 'Admis'));
        $ajournesCount = count($bulletinsExistants) - $admisCount;
        $moyennes = array_column($bulletinsExistants, 'moyenne_generale');
        $moyenneClasse = count($moyennes) > 0 ? round(array_sum($moyennes) / count($moyennes), 1) : 0;
        $moyenneHaute = count($moyennes) > 0 ? max($moyennes) : 0;
        $moyenneBasse = count($moyennes) > 0 ? min($moyennes) : 0;
        ?>
        <div class="stats-grid mt-4">
            <div class="stat-card"><div class="stat-icon blue"><i class="fa-solid fa-users"></i></div><div class="stat-content"><div class="stat-value"><?= count($bulletinsExistants) ?></div><div class="stat-label">Bulletins générés</div></div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div><div class="stat-content"><div class="stat-value"><?= $admisCount ?></div><div class="stat-label">Admis</div></div></div>
            <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-times-circle"></i></div><div class="stat-content"><div class="stat-value"><?= $ajournesCount ?></div><div class="stat-label">Ajournés</div></div></div>
            <div class="stat-card"><div class="stat-icon orange"><i class="fa-solid fa-chart-bar"></i></div><div class="stat-content"><div class="stat-value"><?= $moyenneClasse ?>%</div><div class="stat-label">Moyenne de la classe</div><span class="stat-change neutral">↑ <?= $moyenneHaute ?>% / ↓ <?= $moyenneBasse ?>%</span></div></div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);"><i class="fa-solid fa-file-invoice"></i><p>Sélectionnez une classe et un trimestre pour générer les bulletins.</p></div>
<?php endif; ?>

<div class="info-card-custom mt-4">
    <div class="info-card-custom-header"><div class="info-card-custom-icon blue"><i class="fa-solid fa-circle-info"></i></div><h6>Procédure de génération des bulletins</h6></div>
    <div class="info-card-custom-body">
        <ul class="info-list">
            <li><strong>Étape 1 :</strong> Saisissez toutes les notes (P1 à P6 + Examens) dans le module <strong>Notes</strong>.</li>
            <li><strong>Étape 2 :</strong> Allez dans <strong>Résultats</strong> et cliquez sur <strong>"Calculer les résultats"</strong>.</li>
            <li><strong>Étape 3 :</strong> Revenez ici et cliquez sur <strong>"Générer les bulletins"</strong>.</li>
            <li><strong>Étape 4 :</strong> Consultez, imprimez ou exportez les bulletins générés.</li>
            <li>Les bulletins sont <strong>individuels</strong> : chaque élève a son propre bulletin.</li>
            <li>Vous pouvez <strong>régénérer</strong> les bulletins à tout moment si des notes sont modifiées.</li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnGenerer = document.getElementById('btnGenererBulletins');
    const btnRegenerer = document.getElementById('btnRegenererBulletins');

    if (btnGenerer) {
        btnGenerer.addEventListener('click', function() {
            const classeId = <?= $classeId ?>;
            const trimestreId = <?= $trimestreId ?>;
            const anneeId = <?= $anneeId ?>;
            if (!classeId || !trimestreId) { showToast('Veuillez sélectionner une classe et un trimestre.', 'warning'); return; }
            showConfirmDialog('Voulez-vous générer les bulletins pour cette classe et ce trimestre ?<br><small class="text-muted">Les bulletins existants seront remplacés.</small>', function(confirmed) {
                if (confirmed) {
                    btnGenerer.disabled = true;
                    btnGenerer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Génération en cours...';
                    const formData = new FormData();
                    formData.append('action', 'genererBulletinsClasse');
                    formData.append('classe_id', classeId);
                    formData.append('trimestre_id', trimestreId);
                    formData.append('annee_id', anneeId);
                    fetch('/gestion_scolaire/bulletins', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
                        else { showToast(data.message, 'error'); btnGenerer.disabled = false; btnGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les bulletins maintenant'; }
                    })
                    .catch(() => { showToast('Erreur de connexion.', 'error'); btnGenerer.disabled = false; btnGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les bulletins maintenant'; });
                }
            }, { title: 'Générer les bulletins', confirmText: 'Générer', confirmClass: 'btn-success', icon: 'fa-file-invoice', iconColor: '#10b981' });
        });
    }

    if (btnRegenerer) {
        btnRegenerer.addEventListener('click', function() {
            showConfirmDialog('Voulez-vous régénérer tous les bulletins ?<br><small class="text-muted">Tous les bulletins existants seront remplacés.</small>', function(confirmed) {
                if (confirmed && btnGenerer) btnGenerer.click();
            }, { title: 'Régénérer les bulletins', confirmText: 'Régénérer', confirmClass: 'btn-warning', icon: 'fa-rotate', iconColor: '#f59e0b' });
        });
    }
});

function imprimerBulletin(eleveId) {
    window.open('/gestion_scolaire/bulletins/consulter?eleve_id=' + eleveId + '&annee_id=<?= $anneeId ?>', '_blank');
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>