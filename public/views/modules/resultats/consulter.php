<?php
/**
 * Fichier : public/views/modules/resultats/consulter.php
 * Rôle    : Consultation des résultats par élève ou par classe
 * Système : Bulletin congolais — Résultats par période, examen ou trimestre
 * Version : 2.0 — Filtre par type + correction décision
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('resultats.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ResultatController.php';
$resultatController = new ResultatController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $resultatController->handleAjax();
}

$classes = $resultatController->getClassesActives();
$trimestres = $resultatController->getTrimestresActifs();
$annees = $resultatController->getAnneesScolaires();

$classeId = (int) ($_GET['classe_id'] ?? 0);
$trimestreId = (int) ($_GET['trimestre_id'] ?? 0);
$anneeId = (int) ($_GET['annee_id'] ?? 0);
$typeProclamation = $_GET['type_proclamation'] ?? '';

if ($anneeId <= 0) {
    $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
}

// Déterminer le numéro du trimestre
$trimestreNum = 0;
if ($trimestreId > 0) {
    $trimestreNum = (int) $db->fetchColumn("SELECT numero FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
}

// Types disponibles selon le trimestre
$typesProclamation = [];
switch ($trimestreNum) {
    case 1:
        $typesProclamation = [
            'Période 1' => '1ère Période',
            'Période 2' => '2ème Période',
            'Examen'    => 'Examen 1er Trimestre',
            'Trimestre' => '1er Trimestre complet'
        ];
        break;
    case 2:
        $typesProclamation = [
            'Période 3' => '3ème Période',
            'Période 4' => '4ème Période',
            'Examen'    => 'Examen 2ème Trimestre',
            'Trimestre' => '2ème Trimestre complet'
        ];
        break;
    case 3:
        $typesProclamation = [
            'Période 5' => '5ème Période',
            'Période 6' => '6ème Période',
            'Examen'    => 'Examen 3ème Trimestre',
            'Trimestre' => '3ème Trimestre complet'
        ];
        break;
}

if (empty($typeProclamation) && !empty($typesProclamation)) {
    $typeProclamation = array_key_first($typesProclamation);
}

$classe = null;
$trimestre = null;
$resultats = [];
$dataProclamation = null;
$eleves = [];

if ($classeId > 0 && $trimestreId > 0 && !empty($typeProclamation)) {
    $classe = $db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]);
    $trimestre = $db->fetchOne("SELECT * FROM trimestres WHERE id = :id", ['id' => $trimestreId]);

    $dataProclamation = $resultatController->getResultatsPeriode($classeId, $trimestreId, $typeProclamation);
    $resultats = $dataProclamation['resultats'] ?? [];
}

$typeLabel = $typesProclamation[$typeProclamation] ?? $typeProclamation;

$pageTitle = 'Consultation des résultats';
$pageIcon  = 'fa-square-poll-vertical';
$pageDescription = 'Consultez les résultats par classe, trimestre et type';
$extraCSS = ['modules/resultats.css'];
$extraJS  = ['modules/resultats.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Résultats</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-square-poll-vertical"></i> Consultation des résultats</h4>
        <p>Consultez les résultats des élèves par classe, trimestre et type d'évaluation.</p>
    </div>
    <div class="resultats-header-right">
        <?php if (can('resultats.modifier') && $classeId > 0 && $trimestreId > 0): ?>
            <button type="button" class="btn btn-primary btn-sm" id="btnCalculerResultats">
                <i class="fa-solid fa-calculator"></i> Calculer les résultats
            </button>
        <?php endif; ?>
        <a href="/gestion_scolaire/resultats/proclamation?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list-ol"></i> Proclamation
        </a>
        <a href="/gestion_scolaire/resultats/proces-verbaux" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-file-contract"></i> Procès-verbaux
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<div id="flashMessage"></div>

<!-- SÉLECTEURS -->
<div class="resultats-selectors">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header"><i class="fa-solid fa-filter"></i> Filtres</div>
        <div class="resultats-selector-body">
            <form method="GET" action="" class="resultats-selector-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe</label>
                        <select name="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>><?= e($cl['nom']) ?> (Niv. <?= $cl['niveau'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-calendar-alt"></i> Trimestre</label>
                        <select name="trimestre_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Trimestre —</option>
                            <?php foreach ($trimestres as $trim): ?>
                                <option value="<?= $trim['id'] ?>" <?= $trimestreId === (int) $trim['id'] ? 'selected' : '' ?>><?= e($trim['libelle']) ?> <?= $trim['est_actif'] ? '(Actif)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-tag"></i> Type</label>
                        <select name="type_proclamation" class="form-control" onchange="this.form.submit()" <?= empty($typesProclamation) ? 'disabled' : '' ?>>
                            <?php if (empty($typesProclamation)): ?>
                                <option value="">— Sélectionnez un trimestre —</option>
                            <?php else: ?>
                                <?php foreach ($typesProclamation as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $typeProclamation === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-calendar"></i> Année</label>
                        <select name="annee_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $anneeId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- RÉSULTATS -->
<?php if ($classeId <= 0 || $trimestreId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-square-poll-vertical"></i>
        <p>Sélectionnez une classe, un trimestre et un type pour afficher les résultats.</p>
    </div>

<?php elseif (empty($resultats)): ?>
    <div class="resultats-non-calcules">
        <div class="resultats-non-calcules-icon"><i class="fa-solid fa-info-circle"></i></div>
        <div>
            <h5>Aucun résultat trouvé</h5>
            <p>Les notes pour cette période n'ont pas encore été saisies.</p>
            <?php if (can('resultats.modifier')): ?>
                <button type="button" class="btn btn-primary btn-sm mt-3" id="btnCalculerResultatsBis">
                    <i class="fa-solid fa-calculator"></i> Calculer les résultats
                </button>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <?php 
    $admis = count(array_filter($resultats, fn($r) => $r['is_admis']));
    $ajournes = count($resultats) - $admis;
    $taux = count($resultats) > 0 ? round(($admis / count($resultats)) * 100, 1) : 0;
    $moyennes = array_column($resultats, 'pourcentage');
    $moyenneClasse = count($moyennes) > 0 ? round(array_sum($moyennes) / count($moyennes), 1) : 0;
    ?>

    <div class="resultats-info-bar">
        <div class="resultats-info-item"><i class="fa-solid fa-chalkboard"></i><span>Classe : <strong><?= e($classe['nom']) ?></strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-calendar-alt"></i><span>Trimestre : <strong><?= e($trimestre['libelle']) ?></strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-tag"></i><span>Type : <strong><?= e($typeLabel) ?></strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-users"></i><span>Effectif : <strong><?= count($resultats) ?> élève(s)</strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-check-circle"></i><span>Taux de réussite : <strong><?= $taux ?>%</strong></span></div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-ranking-star"></i> Résultats — <?= e($classe['nom']) ?> — <?= e($typeLabel) ?></h5>
            <div class="d-flex gap-sm">
                <span class="badge badge-success"><?= $admis ?> Admis</span>
                <span class="badge badge-danger"><?= $ajournes ?> Ajourné(s)</span>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Matricule</th>
                            <th>Élève</th>
                            <th>Sexe</th>
                            <th>Total Max</th>
                            <th>Total Obt.</th>
                            <th>%</th>
                            <th>Mention</th>
                            <th>Décision</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultats as $resultat): ?>
                            <tr class="<?= $resultat['rang'] <= 3 ? 'top-rank' : '' ?>">
                                <td>
                                    <?php if ($resultat['rang'] === 1): ?>
                                        <span class="rank-badge gold"><i class="fa-solid fa-trophy"></i> 1er</span>
                                    <?php elseif ($resultat['rang'] === 2): ?>
                                        <span class="rank-badge silver"><i class="fa-solid fa-medal"></i> 2ème</span>
                                    <?php elseif ($resultat['rang'] === 3): ?>
                                        <span class="rank-badge bronze"><i class="fa-solid fa-award"></i> 3ème</span>
                                    <?php else: ?>
                                        <span class="rank-number"><?= $resultat['rang'] ?>ème</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-primary"><?= e($resultat['matricule']) ?></span></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-placeholder <?= $resultat['sexe'] === 'M' ? 'blue' : 'purple' ?>" style="width:34px;height:34px;font-size:0.7rem;">
                                            <?= mb_strtoupper(mb_substr($resultat['prenom'], 0, 1) . mb_substr($resultat['nom'], 0, 1)) ?>
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name"><?= e($resultat['prenom'] . ' ' . $resultat['nom']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $resultat['sexe'] === 'M' ? 'M' : 'F' ?></td>
                                <td><strong><?= number_format($resultat['total_max'], 0) ?></strong></td>
                                <td><strong><?= number_format($resultat['total_obt'], 0) ?></strong></td>
                                <td>
                                    <span class="pourcentage-badge <?= $resultat['pourcentage'] >= 70 ? 'success' : ($resultat['pourcentage'] >= 50 ? 'warning' : 'danger') ?>">
                                        <?= number_format($resultat['pourcentage'], 1) ?>%
                                    </span>
                                </td>
                                <td><span class="badge badge-info"><?= e($resultat['mention']) ?></span></td>
                                <td>
                                    <?php if ($resultat['decision'] === 'Admis'): ?>
                                        <span class="badge badge-success">
                                            <i class="fa-solid fa-check-circle"></i> Admis
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">
                                            <i class="fa-solid fa-times-circle"></i> <?= e($resultat['decision']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/gestion_scolaire/bulletins/consulter?eleve_id=<?= $resultat['eleve_id'] ?>&annee_id=<?= $anneeId ?>" class="btn btn-sm btn-outline-primary" title="Voir le bulletin">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (can('resultats.modifier')): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnCalculer = document.getElementById('btnCalculerResultats') || document.getElementById('btnCalculerResultatsBis');
    if (btnCalculer) {
        btnCalculer.addEventListener('click', function() {
            const classeId = <?= $classeId ?>;
            const trimestreId = <?= $trimestreId ?>;
            if (!classeId || !trimestreId) { showToast('Veuillez sélectionner une classe et un trimestre.', 'warning'); return; }
            showConfirmDialog('Voulez-vous calculer les résultats pour cette classe et ce trimestre ?<br><small class="text-muted">Les résultats existants seront remplacés.</small>', function(confirmed) {
                if (confirmed) {
                    btnCalculer.disabled = true;
                    btnCalculer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calcul en cours...';
                    const formData = new FormData();
                    formData.append('action', 'calculerResultatsClasse');
                    formData.append('classe_id', classeId);
                    formData.append('trimestre_id', trimestreId);
                    fetch('/gestion_scolaire/resultats', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
                        else { showToast(data.message, 'error'); btnCalculer.disabled = false; btnCalculer.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculer les résultats'; }
                    })
                    .catch(() => { showToast('Erreur de connexion.', 'error'); btnCalculer.disabled = false; btnCalculer.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculer les résultats'; });
                }
            }, { title: 'Calculer les résultats', confirmText: 'Calculer', confirmClass: 'btn-primary', icon: 'fa-calculator', iconColor: '#2c3e90' });
        });
    }
});
</script>
<?php endif; ?>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>