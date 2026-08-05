<?php
/**
 * Fichier : public/views/enseignant/modules/resultats.php
 * Rôle    : Consultation des résultats — Vue Enseignant
 * Système : Bulletin congolais — Résultats par période, examen ou trimestre
 * Version : 2.2 — Résolution de l'absence de la table cours
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

// Remonter de 4 niveaux pour atteindre la racine depuis public/views/enseignant/modules/
require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('resultats.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ResultatController.php';
$resultatController = new ResultatController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $resultatController->handleAjax();
}

// Récupération de l'ID utilisateur connecté
$utilisateurId = $_SESSION['utilisateur_id'] ?? 0;

// 1. Récupérer l'ID de l'enseignant associé à cet utilisateur
$enseignantId = (int) $db->fetchColumn(
    "SELECT id FROM enseignants WHERE utilisateur_id = :uid AND est_actif = 1 LIMIT 1",
    ['uid' => $utilisateurId]
);

if ($enseignantId <= 0) {
    $enseignantId = $utilisateurId;
}

// 2. Recherche robuste de la classe attribuée à l'enseignant
$classeId = 0;

// Option A : Recherche si la classe_id est stockée dans la table enseignants
try {
    $classeId = (int) $db->fetchColumn(
        "SELECT classe_id FROM enseignants WHERE id = :eid AND classe_id IS NOT NULL LIMIT 1",
        ['eid' => $enseignantId]
    );
} catch (\Throwable $e) {
    $classeId = 0;
}

// Option B : Recherche si enseignant_id est stocké directement dans la table classes
if ($classeId <= 0) {
    try {
        $classeId = (int) $db->fetchColumn(
            "SELECT id FROM classes WHERE enseignant_id = :eid LIMIT 1",
            ['eid' => $enseignantId]
        );
    } catch (\Throwable $e) {
        $classeId = 0;
    }
}

// Option C : Fallback — Prendre la première classe active du système si aucune liaison explicite n'est trouvée
if ($classeId <= 0) {
    $classeId = (int) $db->fetchColumn("SELECT id FROM classes ORDER BY id ASC LIMIT 1");
}

if ($classeId <= 0) {
    setFlash('warning', 'Aucune classe ne vous est attribuée actuellement. Contactez l\'administration.');
    redirect('/gestion_scolaire/dashboard');
}

// 3. Récupérer les informations de la classe
$maClasse = $db->fetchOne(
    "SELECT c.id, c.nom, c.niveau, c.salle, c.capacite_max,
            a.libelle AS annee_libelle
     FROM classes c
     INNER JOIN annees_scolaires a ON c.annee_scolaire_id = a.id
     WHERE c.id = :id",
    ['id' => $classeId]
);

if (!$maClasse) {
    setFlash('error', 'Classe introuvable.');
    redirect('/gestion_scolaire/dashboard');
}

// 4. Récupérer les listes pour les filtres
$trimestres = $resultatController->getTrimestresActifs();
$annees = $resultatController->getAnneesScolaires();

$trimestreId = (int) ($_GET['trimestre_id'] ?? 0);
$anneeId = (int) ($_GET['annee_id'] ?? 0);
$typeProclamation = $_GET['type_proclamation'] ?? '';

if ($anneeId <= 0) {
    $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
}

// Numéro du trimestre
$trimestreNum = 0;
if ($trimestreId > 0) {
    $trimestreNum = (int) $db->fetchColumn("SELECT numero FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
}

// Types de proclamation par trimestre
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

if ($trimestreId > 0 && !empty($typeProclamation)) {
    $classe = $db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]);
    $trimestre = $db->fetchOne("SELECT * FROM trimestres WHERE id = :id", ['id' => $trimestreId]);

    $dataProclamation = $resultatController->getResultatsPeriode($classeId, $trimestreId, $typeProclamation);
    $resultats = $dataProclamation['resultats'] ?? [];
}

$typeLabel = $typesProclamation[$typeProclamation] ?? $typeProclamation;

$pageTitle = 'Résultats — ' . e($maClasse['nom']);
$pageIcon  = 'fa-square-poll-vertical';
$pageDescription = 'Classe : ' . e($maClasse['nom']) . ' — Consultation des résultats par trimestre et par évaluation';
$extraCSS = ['modules/resultats.css'];
$extraJS  = ['modules/resultats.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Résultats — <?= e($maClasse['nom']) ?></span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-square-poll-vertical"></i> Résultats — <?= e($maClasse['nom']) ?></h4>
        <p>
            <i class="fa-solid fa-chalkboard"></i> <?= e($maClasse['nom']) ?> (Niveau <?= e($maClasse['niveau']) ?>) — 
            <i class="fa-solid fa-door-open"></i> Salle <?= e($maClasse['salle'] ?? '—') ?> — 
            <i class="fa-solid fa-calendar-alt"></i> <?= e($maClasse['annee_libelle']) ?>
        </p>
    </div>
    <div class="resultats-header-right">
        <?php if (can('resultats.modifier') && $trimestreId > 0): ?>
            <button type="button" class="btn btn-primary btn-sm" id="btnCalculerResultats">
                <i class="fa-solid fa-calculator"></i> Calculer les résultats
            </button>
        <?php endif; ?>
        <a href="/gestion_scolaire/resultats/proclamation?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list-ol"></i> Proclamation
        </a>
        <a href="/gestion_scolaire/resultats/proces-verbaux?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-file-contract"></i> Procès-verbal
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<div id="flashMessage"></div>

<!-- SÉLECTEURS ET FILTRES -->
<div class="resultats-selectors">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header"><i class="fa-solid fa-filter"></i> Filtres</div>
        <div class="resultats-selector-body">
            <form method="GET" action="" class="resultats-selector-form">
                
                <input type="hidden" name="classe_id" value="<?= $classeId ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe</label>
                        <select class="form-control" disabled style="background:#f1f5f9; cursor:not-allowed;">
                            <option value="<?= $maClasse['id'] ?>" selected><?= e($maClasse['nom']) ?> (Niv. <?= e($maClasse['niveau']) ?>)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-calendar-alt"></i> Trimestre</label>
                        <select name="trimestre_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Trimestre —</option>
                            <?php foreach ($trimestres as $trim): ?>
                                <option value="<?= $trim['id'] ?>" <?= $trimestreId === (int) $trim['id'] ? 'selected' : '' ?>>
                                    <?= e($trim['libelle']) ?> <?= !empty($trim['est_actif']) ? '(Actif)' : '' ?>
                                </option>
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

<!-- AFFICHAGE DES RÉSULTATS -->
<?php if ($trimestreId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px; background:#fff; border-radius:16px; border:1px solid var(--border-color);">
        <i class="fa-solid fa-square-poll-vertical"></i>
        <p>Sélectionnez un trimestre et un type d'évaluation pour afficher les résultats de votre classe.</p>
    </div>

<?php elseif (empty($resultats)): ?>
    <div class="resultats-non-calcules">
        <div class="resultats-non-calcules-icon"><i class="fa-solid fa-info-circle"></i></div>
        <div>
            <h5>Aucun résultat trouvé</h5>
            <p>Les notes pour cette période n'ont pas encore été calculées ou saisies.</p>
            <?php if (can('resultats.modifier')): ?>
                <button type="button" class="btn btn-primary btn-sm mt-3" id="btnCalculerResultatsBis">
                    <i class="fa-solid fa-calculator"></i> Calculer les résultats
                </button>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <?php 
    $admis = count(array_filter($resultats, function($r) {
        return isset($r['is_admis']) ? $r['is_admis'] : (($r['decision'] ?? '') === 'Admis');
    }));
    $ajournes = count($resultats) - $admis;
    $taux = count($resultats) > 0 ? round(($admis / count($resultats)) * 100, 1) : 0;
    $moyennes = array_column($resultats, 'pourcentage');
    $moyenneClasse = count($moyennes) > 0 ? round(array_sum($moyennes) / count($moyennes), 1) : 0;
    ?>

    <div class="resultats-info-bar">
        <div class="resultats-info-item"><i class="fa-solid fa-chalkboard"></i><span>Classe : <strong><?= e($classe['nom'] ?? $maClasse['nom']) ?></strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-calendar-alt"></i><span>Trimestre : <strong><?= e($trimestre['libelle'] ?? '') ?></strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-tag"></i><span>Type : <strong><?= e($typeLabel) ?></strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-users"></i><span>Effectif : <strong><?= count($resultats) ?> élève(s)</strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-check-circle"></i><span>Taux de réussite : <strong><?= $taux ?>%</strong></span></div>
        <div class="resultats-info-item"><i class="fa-solid fa-chart-line"></i><span>Moyenne générale : <strong><?= $moyenneClasse ?>%</strong></span></div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-ranking-star"></i> Résultats — <?= e($maClasse['nom']) ?> — <?= e($typeLabel) ?></h5>
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
                            <?php $rang = (int) ($resultat['rang'] ?? 0); ?>
                            <tr class="<?= $rang > 0 && $rang <= 3 ? 'top-rank' : '' ?>">
                                <td>
                                    <?php if ($rang === 1): ?>
                                        <span class="rank-badge gold"><i class="fa-solid fa-trophy"></i> 1er</span>
                                    <?php elseif ($rang === 2): ?>
                                        <span class="rank-badge silver"><i class="fa-solid fa-medal"></i> 2ème</span>
                                    <?php elseif ($rang === 3): ?>
                                        <span class="rank-badge bronze"><i class="fa-solid fa-award"></i> 3ème</span>
                                    <?php else: ?>
                                        <span class="rank-number"><?= $rang > 0 ? $rang . 'ème' : '-' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-primary"><?= e($resultat['matricule'] ?? 'N/A') ?></span></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-placeholder <?= ($resultat['sexe'] ?? 'M') === 'M' ? 'blue' : 'purple' ?>" style="width:34px;height:34px;font-size:0.7rem;">
                                            <?= mb_strtoupper(mb_substr($resultat['prenom'] ?? '', 0, 1) . mb_substr($resultat['nom'] ?? '', 0, 1)) ?>
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name"><?= e(($resultat['prenom'] ?? '') . ' ' . ($resultat['nom'] ?? '')) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($resultat['sexe'] ?? 'M') ?></td>
                                <td><strong><?= number_format($resultat['total_max'] ?? 0, 0) ?></strong></td>
                                <td><strong><?= number_format($resultat['total_obt'] ?? 0, 0) ?></strong></td>
                                <td>
                                    <?php $pct = (float) ($resultat['pourcentage'] ?? 0); ?>
                                    <span class="pourcentage-badge <?= $pct >= 70 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">
                                        <?= number_format($pct, 1) ?>%
                                    </span>
                                </td>
                                <td><span class="badge badge-info"><?= e($resultat['mention'] ?? 'N/A') ?></span></td>
                                <td>
                                    <?php if (($resultat['decision'] ?? '') === 'Admis'): ?>
                                        <span class="badge badge-success">
                                            <i class="fa-solid fa-check-circle"></i> Admis
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">
                                            <i class="fa-solid fa-times-circle"></i> <?= e($resultat['decision'] ?? 'Ajourné') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/gestion_scolaire/bulletins/consulter?eleve_id=<?= $resultat['eleve_id'] ?? 0 ?>&annee_id=<?= $anneeId ?>" class="btn btn-sm btn-outline-primary" title="Voir le bulletin">
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
            const classeId = <?= (int) $classeId ?>;
            const trimestreId = <?= (int) $trimestreId ?>;
            if (!classeId || !trimestreId) { 
                showToast('Veuillez sélectionner un trimestre.', 'warning'); 
                return; 
            }
            showConfirmDialog('Voulez-vous calculer les résultats pour cette classe et ce trimestre ?<br><small class="text-muted">Les résultats existants seront recalculés.</small>', function(confirmed) {
                if (confirmed) {
                    btnCalculer.disabled = true;
                    btnCalculer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calcul en cours...';
                    const formData = new FormData();
                    formData.append('action', 'calculerResultatsClasse');
                    formData.append('classe_id', classeId);
                    formData.append('trimestre_id', trimestreId);
                    fetch('/gestion_scolaire/resultats', { 
                        method: 'POST', 
                        body: formData, 
                        headers: { 'X-Requested-With': 'XMLHttpRequest' } 
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) { 
                            showToast(data.message, 'success'); 
                            setTimeout(() => location.reload(), 1000); 
                        } else { 
                            showToast(data.message, 'error'); 
                            btnCalculer.disabled = false; 
                            btnCalculer.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculer les résultats'; 
                        }
                    })
                    .catch(() => { 
                        showToast('Erreur de connexion lors du calcul.', 'error'); 
                        btnCalculer.disabled = false; 
                        btnCalculer.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculer les résultats'; 
                    });
                }
            }, { title: 'Calculer les résultats', confirmText: 'Calculer', confirmClass: 'btn-primary', icon: 'fa-calculator', iconColor: '#2c3e90' });
        });
    }
});
</script>
<?php endif; ?>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
