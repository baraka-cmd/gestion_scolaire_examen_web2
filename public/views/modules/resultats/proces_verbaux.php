<?php
/**
 * Fichier : public/views/modules/resultats/proces_verbaux.php
 * Rôle    : Procès-verbaux par classe — Liste de tous les élèves avec résultats
 * Système : Bulletin congolais — PV de proclamation
 * Version : 2.0 — Correction décision + styles
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
$typeProclamation = $_GET['type_proclamation'] ?? 'Trimestre';

if ($anneeId <= 0) {
    $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
}

$trimestreNum = 0;
if ($trimestreId > 0) {
    $trimestreNum = (int) $db->fetchColumn("SELECT numero FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
}

$typesProclamation = [];
switch ($trimestreNum) {
    case 1:
        $typesProclamation = ['Période 1' => '1ère Période', 'Période 2' => '2ème Période', 'Examen' => 'Examen 1er Trimestre', 'Trimestre' => '1er Trimestre complet'];
        break;
    case 2:
        $typesProclamation = ['Période 3' => '3ème Période', 'Période 4' => '4ème Période', 'Examen' => 'Examen 2ème Trimestre', 'Trimestre' => '2ème Trimestre complet'];
        break;
    case 3:
        $typesProclamation = ['Période 5' => '5ème Période', 'Période 6' => '6ème Période', 'Examen' => 'Examen 3ème Trimestre', 'Trimestre' => '3ème Trimestre complet'];
        break;
}

if (empty($typeProclamation) && !empty($typesProclamation)) {
    $typeProclamation = array_key_first($typesProclamation);
}

$classe = null;
$trimestre = null;
$annee = null;
$ecole = $db->fetchOne("SELECT * FROM parametres_ecole LIMIT 1");
$resultats = [];
$stats = [];

if ($classeId > 0 && $trimestreId > 0 && !empty($typeProclamation)) {
    $classe = $db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]);
    $trimestre = $db->fetchOne("SELECT * FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
    $annee = $db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $anneeId]);

    $dataProclamation = $resultatController->getResultatsPeriode($classeId, $trimestreId, $typeProclamation);
    $resultats = $dataProclamation['resultats'] ?? [];

    if (!empty($resultats)) {
        $admis = count(array_filter($resultats, fn($r) => $r['is_admis']));
        $ajournes = count($resultats) - $admis;
        $moyennes = array_column($resultats, 'pourcentage');
        $stats = [
            'effectif'      => count($resultats),
            'admis'         => $admis,
            'ajournes'      => $ajournes,
            'taux_reussite' => count($resultats) > 0 ? round(($admis / count($resultats)) * 100, 1) : 0,
            'moyenne_classe' => count($moyennes) > 0 ? round(array_sum($moyennes) / count($moyennes), 1) : 0,
            'moyenne_haute' => count($moyennes) > 0 ? max($moyennes) : 0,
            'moyenne_basse' => count($moyennes) > 0 ? min($moyennes) : 0,
        ];
        $mentions = [];
        foreach ($resultats as $r) {
            $m = $r['mention'];
            if (!isset($mentions[$m])) $mentions[$m] = 0;
            $mentions[$m]++;
        }
        $stats['mentions'] = $mentions;
    }
}

$typeLabel = $typesProclamation[$typeProclamation] ?? $typeProclamation;

$pageTitle = 'Procès-verbaux';
$pageIcon  = 'fa-file-contract';
$pageDescription = 'Procès-verbaux de proclamation des résultats';
$extraCSS = ['modules/resultats.css'];
$extraJS  = ['modules/resultats.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/resultats">Résultats</a>
    <span class="separator">/</span>
    <span class="current">Procès-verbaux</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-file-contract"></i> Procès-verbaux</h4>
        <p>Procès-verbaux de proclamation des résultats par classe, trimestre et type.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/resultats" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-arrow-left"></i> Résultats</a>
        <?php if (!empty($resultats)): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer le PV</button>
        <?php endif; ?>
    </div>
</div>

<div id="flashMessage"></div>

<!-- SÉLECTEURS -->
<div class="resultats-selectors no-print">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header"><i class="fa-solid fa-filter"></i> Sélectionner la classe, le trimestre et le type</div>
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
                                <option value="<?= $a['id'] ?>" <?= $anneeId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['libelle']) ?> <?= $a['est_active'] ? '(Active)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- PROCÈS-VERBAL -->
<?php if ($classeId <= 0 || $trimestreId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-file-contract"></i>
        <p>Sélectionnez une classe, un trimestre et un type pour afficher le procès-verbal.</p>
    </div>

<?php elseif (empty($resultats)): ?>
    <div class="resultats-non-calcules">
        <div class="resultats-non-calcules-icon"><i class="fa-solid fa-info-circle"></i></div>
        <div>
            <h5>Aucun résultat trouvé</h5>
            <p>Les notes pour cette période n'ont pas encore été saisies.</p>
            <a href="/gestion_scolaire/resultats/consulter?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-primary btn-sm mt-3">
                <i class="fa-solid fa-calculator"></i> Calculer les résultats
            </a>
        </div>
    </div>

<?php else: ?>
    <div class="pv-document" id="pvDocument">
        <!-- En-tête du PV -->
        <div class="pv-header">
            <div class="pv-header-top">
                <div class="pv-logo"><i class="fa-solid fa-school"></i></div>
                <div class="pv-ecole-info">
                    <h2><?= e($ecole['nom_ecole'] ?? 'École Primaire') ?></h2>
                    <p><?= e($ecole['devise'] ?? 'Discipline - Travail - Excellence') ?></p>
                </div>
                <div class="pv-annee"><span class="pv-annee-badge"><?= e($annee['libelle'] ?? '') ?></span></div>
            </div>
            <h1 class="pv-titre">PROCÈS-VERBAL DE PROCLAMATION</h1>
            <div class="pv-infos">
                <div class="pv-info-item">
                    <span><strong>Classe :</strong> <?= e($classe['nom']) ?> (Niveau <?= $classe['niveau'] ?>)</span>
                    <span><strong>Salle :</strong> <?= e($classe['salle'] ?? '—') ?></span>
                </div>
                <div class="pv-info-item">
                    <span><strong>Trimestre :</strong> <?= e($trimestre['libelle']) ?></span>
                    <span><strong>Année scolaire :</strong> <?= e($annee['libelle'] ?? '') ?></span>
                </div>
                <div class="pv-info-item">
                    <span><strong>Type :</strong> <?= e($typeLabel) ?></span>
                    <span><strong>Effectif :</strong> <?= $stats['effectif'] ?> élève(s)</span>
                    <span><strong>Date :</strong> <?= formatDate(date('Y-m-d'), 'd/m/Y') ?></span>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="pv-stats">
            <div class="pv-stat-card">
                <div class="pv-stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
                <div class="pv-stat-content">
                    <div class="pv-stat-value"><?= $stats['admis'] ?></div>
                    <div class="pv-stat-label">Admis</div>
                </div>
            </div>
            <div class="pv-stat-card">
                <div class="pv-stat-icon red"><i class="fa-solid fa-times-circle"></i></div>
                <div class="pv-stat-content">
                    <div class="pv-stat-value"><?= $stats['ajournes'] ?></div>
                    <div class="pv-stat-label">Ajournés</div>
                </div>
            </div>
            <div class="pv-stat-card">
                <div class="pv-stat-icon blue"><i class="fa-solid fa-percent"></i></div>
                <div class="pv-stat-content">
                    <div class="pv-stat-value"><?= $stats['taux_reussite'] ?>%</div>
                    <div class="pv-stat-label">Taux de réussite</div>
                </div>
            </div>
            <div class="pv-stat-card">
                <div class="pv-stat-icon orange"><i class="fa-solid fa-chart-line"></i></div>
                <div class="pv-stat-content">
                    <div class="pv-stat-value"><?= $stats['moyenne_classe'] ?>%</div>
                    <div class="pv-stat-label">Moyenne de classe</div>
                    <span class="pv-stat-detail">↑ <?= $stats['moyenne_haute'] ?>% / ↓ <?= $stats['moyenne_basse'] ?>%</span>
                </div>
            </div>
        </div>

        <!-- Mentions -->
        <?php if (!empty($stats['mentions'])): ?>
            <div class="pv-mentions">
                <h5><i class="fa-solid fa-award"></i> Répartition des mentions</h5>
                <div class="pv-mentions-grid">
                    <?php foreach ($stats['mentions'] as $mention => $nb): ?>
                        <div class="pv-mention-item">
                            <span class="pv-mention-nom"><?= e($mention) ?></span>
                            <span class="pv-mention-nb"><?= $nb ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tableau des résultats -->
        <div class="pv-table-wrapper">
            <table class="pv-table">
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Matricule</th>
                        <th>Nom et Prénom</th>
                        <th>Sexe</th>
                        <th>Total Max</th>
                        <th>Total Obt.</th>
                        <th>%</th>
                        <th>Mention</th>
                        <th>Décision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultats as $resultat): ?>
                        <tr class="<?= $resultat['rang'] <= 3 ? 'top-rank-pv' : '' ?> <?= !$resultat['is_admis'] ? 'ajourne-row' : '' ?>">
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
                            <td><strong><?= e($resultat['prenom'] . ' ' . $resultat['nom']) ?></strong></td>
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
                                    <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Admis</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fa-solid fa-times-circle"></i> <?= e($resultat['decision']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="pv-signatures">
            <div class="pv-signature-block"><div class="pv-signature-line"></div><span>Le Secrétaire</span></div>
            <div class="pv-signature-block"><div class="pv-signature-line"></div><span>L'Enseignant Titulaire</span></div>
            <div class="pv-signature-block"><div class="pv-signature-line"></div><span>Le Directeur</span></div>
        </div>
    </div>

    <div class="pv-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer le procès-verbal</button>
        <a href="/gestion_scolaire/bulletins/generer?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-success"><i class="fa-solid fa-file-invoice"></i> Générer les bulletins</a>
        <a href="/gestion_scolaire/resultats/consulter?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-outline-primary"><i class="fa-solid fa-table-list"></i> Voir le tableau</a>
    </div>
<?php endif; ?>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>