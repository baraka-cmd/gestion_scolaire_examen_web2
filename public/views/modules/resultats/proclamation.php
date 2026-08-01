<?php
/**
 * Fichier : public/views/modules/resultats/proclamation.php
 * Rôle    : Liste de proclamation — Classement par ordre de mérite avec statistiques
 * Système : Bulletin congolais — Proclamation officielle RDC
 * Version : 2.0 — Proclamation par période, examen ou trimestre avec filtre intelligent
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('resultats.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ResultatController.php';
$resultatController = new ResultatController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $resultatController->handleAjax();
}

// Récupérer les données pour les filtres
$classes = $resultatController->getClassesActives();
$trimestres = $resultatController->getTrimestresActifs();
$annees = $resultatController->getAnneesScolaires();

// Paramètres
$classeId = (int) ($_GET['classe_id'] ?? 0);
$trimestreId = (int) ($_GET['trimestre_id'] ?? 0);
$anneeId = (int) ($_GET['annee_id'] ?? 0);
$typeProclamation = $_GET['type_proclamation'] ?? '';

if ($anneeId <= 0) {
    $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
}

// Déterminer le numéro du trimestre sélectionné
$trimestreNum = 0;
if ($trimestreId > 0) {
    $trimestreNum = (int) $db->fetchColumn("SELECT numero FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
}

// Types de proclamation disponibles selon le trimestre
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

// Si pas de type sélectionné, prendre le premier disponible
if (empty($typeProclamation) && !empty($typesProclamation)) {
    $typeProclamation = array_key_first($typesProclamation);
}

// Récupérer les infos
$classe = null;
$trimestre = null;
$annee = null;
$ecole = $db->fetchOne("SELECT * FROM parametres_ecole LIMIT 1");
$resultats = [];
$stats = [];
$titulaire = null;

if ($classeId > 0 && $trimestreId > 0 && !empty($typeProclamation)) {
    $classe = $db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]);
    $trimestre = $db->fetchOne("SELECT * FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
    $annee = $db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $anneeId]);

    // Récupérer le titulaire
    $titulaire = $db->fetchOne(
        "SELECT CONCAT(e.prenom, ' ', e.nom) AS nom_complet
         FROM enseignants e
         WHERE e.classe_id = :cid AND e.est_actif = 1
         LIMIT 1",
        ['cid' => $classeId]
    );

    // Récupérer les résultats selon le type de proclamation
    $dataProclamation = $resultatController->getResultatsPeriode($classeId, $trimestreId, $typeProclamation);
    
    $resultats = $dataProclamation['resultats'] ?? [];
    $stats = [
        'total'           => $dataProclamation['effectif'] ?? 0,
        'garcons'         => $dataProclamation['total_garcons'] ?? 0,
        'filles'          => $dataProclamation['total_filles'] ?? 0,
        'admis'           => $dataProclamation['total_admis'] ?? 0,
        'garcons_admis'   => $dataProclamation['garcons_admis'] ?? 0,
        'filles_admises'  => $dataProclamation['filles_admises'] ?? 0,
        'ajournes'        => $dataProclamation['total_echecs'] ?? 0,
        'garcons_echecs'  => $dataProclamation['garcons_echecs'] ?? 0,
        'filles_echecs'   => $dataProclamation['filles_echecs'] ?? 0,
        'taux_reussite'   => $dataProclamation['taux_reussite'] ?? 0,
        'moyenne_classe'  => $dataProclamation['moyenne_classe'] ?? 0,
        'moyenne_haute'   => !empty($resultats) ? max(array_column($resultats, 'pourcentage')) : 0,
        'moyenne_basse'   => !empty($resultats) ? min(array_column($resultats, 'pourcentage')) : 0,
        'mention_dominante' => '—'
    ];

    // Mentions
    if (!empty($resultats)) {
        $mentions = [];
        foreach ($resultats as $r) {
            $m = $r['mention'];
            if (!isset($mentions[$m])) $mentions[$m] = 0;
            $mentions[$m]++;
        }
        arsort($mentions);
        $stats['mention_dominante'] = !empty($mentions) ? array_key_first($mentions) : '—';
    }
}

// Libellé du type de proclamation
$typeLabel = $typesProclamation[$typeProclamation] ?? $typeProclamation;

$pageTitle = 'Liste de proclamation';
$pageIcon  = 'fa-list-ol';
$pageDescription = 'Liste de proclamation — ' . $typeLabel;
$extraCSS = ['modules/resultats.css'];
$extraJS  = ['modules/resultats.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/resultats">Résultats</a>
    <span class="separator">/</span>
    <span class="current">Proclamation</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4>
            <i class="fa-solid fa-list-ol"></i> Liste de proclamation
        </h4>
        <p>Liste de proclamation officielle — Classement par ordre de mérite.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/resultats" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Résultats
        </a>
        <?php if (!empty($resultats)): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Imprimer la proclamation
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     SÉLECTEURS
     ============================================================ -->
<div class="resultats-selectors no-print">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header">
            <i class="fa-solid fa-filter"></i> Sélectionner la classe, le trimestre et le type
        </div>
        <div class="resultats-selector-body">
            <form method="GET" action="" class="resultats-selector-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="classe_id" class="form-label">
                            <i class="fa-solid fa-chalkboard"></i> Classe
                        </label>
                        <select id="classe_id" name="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez une classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>>
                                    <?= e($cl['nom']) ?> (Niv. <?= $cl['niveau'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="trimestre_id" class="form-label">
                            <i class="fa-solid fa-calendar-alt"></i> Trimestre
                        </label>
                        <select id="trimestre_id" name="trimestre_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez un trimestre —</option>
                            <?php foreach ($trimestres as $trim): ?>
                                <option value="<?= $trim['id'] ?>" <?= $trimestreId === (int) $trim['id'] ? 'selected' : '' ?>>
                                    <?= e($trim['libelle']) ?> <?= $trim['est_actif'] ? '(Actif)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type_proclamation" class="form-label">
                            <i class="fa-solid fa-tag"></i> Type
                        </label>
                        <select id="type_proclamation" name="type_proclamation" class="form-control" onchange="this.form.submit()" <?= empty($typesProclamation) ? 'disabled' : '' ?>>
                            <?php if (empty($typesProclamation)): ?>
                                <option value="">— Sélectionnez d'abord un trimestre —</option>
                            <?php else: ?>
                                <?php foreach ($typesProclamation as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $typeProclamation === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="annee_id" class="form-label">
                            <i class="fa-solid fa-calendar"></i> Année
                        </label>
                        <select id="annee_id" name="annee_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $anneeId === (int) $a['id'] ? 'selected' : '' ?>>
                                    <?= e($a['libelle']) ?> <?= $a['est_active'] ? '(Active)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     LISTE DE PROCLAMATION
     ============================================================ -->
<?php if ($classeId <= 0 || $trimestreId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-list-ol"></i>
        <p>Sélectionnez une classe, un trimestre et un type de proclamation.</p>
    </div>

<?php elseif (empty($resultats)): ?>
    <div class="resultats-non-calcules">
        <div class="resultats-non-calcules-icon">
            <i class="fa-solid fa-info-circle"></i>
        </div>
        <div>
            <h5>Aucun résultat trouvé</h5>
            <p>Les notes pour cette période n'ont pas encore été saisies ou les résultats n'ont pas été calculés.</p>
            <a href="/gestion_scolaire/resultats/consulter?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-primary btn-sm mt-3">
                <i class="fa-solid fa-calculator"></i> Calculer les résultats
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- Document de proclamation -->
    <div class="proclamation-document" id="proclamationDocument">

        <!-- ========== EN-TÊTE OFFICIEL RDC ========== -->
        <div class="proclamation-header">
            <div class="proclamation-pays">
                <?= e($ecole['pays'] ?? 'RÉPUBLIQUE DÉMOCRATIQUE DU CONGO') ?>
            </div>
            <div class="proclamation-ministere">
                <?= e($ecole['ministere'] ?? "MINISTÈRE DE L'ENSEIGNEMENT PRIMAIRE, SECONDAIRE ET PROFESSIONNEL") ?>
            </div>

            <div class="proclamation-ecole">
                <div><strong>ÉCOLE :</strong> <?= e($ecole['nom_ecole'] ?? '___________________________') ?></div>
                <div><strong>VILLE :</strong> <?= e($ecole['ville'] ?? '___________________________') ?></div>
                <div><strong>COMMUNE/TER :</strong> <?= e($ecole['commune_territoire'] ?? '___________________________') ?></div>
                <div><strong>CODE :</strong> <?= e($ecole['code_etablissement'] ?? '___________________________') ?></div>
            </div>

            <h1 class="proclamation-titre">LISTE DE PROCLAMATION — <?= e(mb_strtoupper($typeLabel)) ?></h1>
            <div class="proclamation-annee">ANNÉE SCOLAIRE <?= e($annee['libelle'] ?? '______________________') ?></div>

            <div class="proclamation-infos">
                <div class="proclamation-info-ligne">
                    <span><strong>Classe :</strong> <?= e($classe['nom']) ?> (Niveau <?= $classe['niveau'] ?>)</span>
                    <span><strong>Trimestre :</strong> <?= e($trimestre['libelle']) ?></span>
                </div>
                <div class="proclamation-info-ligne">
                    <span><strong>Titulaire :</strong> <?= e($titulaire['nom_complet'] ?? 'Non assigné') ?></span>
                    <span><strong>Effectif :</strong> <?= $stats['total'] ?> élève(s)</span>
                </div>
            </div>
        </div>

        <!-- ========== STATISTIQUES ========== -->
        <div class="proclamation-stats">
            <div class="proclamation-stat-box">
                <div class="proclamation-stat-titre">PARTICIPANTS</div>
                <div class="proclamation-stat-valeur"><?= $stats['total'] ?></div>
                <div class="proclamation-stat-detail">
                    <span class="proclamation-genre garcon"><i class="fa-solid fa-mars"></i> G : <?= $stats['garcons'] ?></span>
                    <span class="proclamation-genre fille"><i class="fa-solid fa-venus"></i> F : <?= $stats['filles'] ?></span>
                </div>
            </div>

            <div class="proclamation-stat-box reussite">
                <div class="proclamation-stat-titre">RÉUSSITE</div>
                <div class="proclamation-stat-valeur"><?= $stats['admis'] ?></div>
                <div class="proclamation-stat-detail">
                    <span class="proclamation-genre garcon"><i class="fa-solid fa-mars"></i> G : <?= $stats['garcons_admis'] ?></span>
                    <span class="proclamation-genre fille"><i class="fa-solid fa-venus"></i> F : <?= $stats['filles_admises'] ?></span>
                </div>
            </div>

            <div class="proclamation-stat-box echec">
                <div class="proclamation-stat-titre">ÉCHEC</div>
                <div class="proclamation-stat-valeur"><?= $stats['ajournes'] ?></div>
                <div class="proclamation-stat-detail">
                    <span class="proclamation-genre garcon"><i class="fa-solid fa-mars"></i> G : <?= $stats['garcons_echecs'] ?></span>
                    <span class="proclamation-genre fille"><i class="fa-solid fa-venus"></i> F : <?= $stats['filles_echecs'] ?></span>
                </div>
            </div>

            <div class="proclamation-stat-box taux">
                <div class="proclamation-stat-titre">TAUX DE RÉUSSITE</div>
                <div class="proclamation-stat-valeur"><?= $stats['taux_reussite'] ?>%</div>
                <div class="proclamation-stat-detail">
                    <span>Moy. classe : <?= $stats['moyenne_classe'] ?>%</span>
                </div>
            </div>
        </div>

        <!-- ========== TABLEAU DE PROCLAMATION ========== -->
        <div class="proclamation-table-wrapper">
            <table class="proclamation-table">
                <thead>
                    <tr>
                        <th class="col-numero">N°</th>
                        <th class="col-nom">NOM ET POST-NOM</th>
                        <th class="col-sexe">S</th>
                        <th class="col-pourcentage">%</th>
                        <th class="col-observation">OBSERVATION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $ligneAjoureeAffichee = false;
                    foreach ($resultats as $index => $resultat): 
                        $isAdmis = $resultat['is_admis'];
                        $pourcentage = $resultat['pourcentage'];
                        
                        if (!$isAdmis && !$ligneAjoureeAffichee) {
                            $ligneAjoureeAffichee = true;
                            echo '<tr class="ligne-separatrice"><td colspan="5"></td></tr>';
                        }
                    ?>
                        <tr class="proclamation-row <?= $isAdmis ? 'admis' : 'ajourne' ?> <?= $index < 3 ? 'top-three' : '' ?>">
                            <td class="col-numero">
                                <?php if ($index === 0): ?>
                                    <span class="rank-badge gold"><i class="fa-solid fa-trophy"></i> 1er</span>
                                <?php elseif ($index === 1): ?>
                                    <span class="rank-badge silver"><i class="fa-solid fa-medal"></i> 2ème</span>
                                <?php elseif ($index === 2): ?>
                                    <span class="rank-badge bronze"><i class="fa-solid fa-award"></i> 3ème</span>
                                <?php else: ?>
                                    <?= str_pad($resultat['rang'], 2, '0', STR_PAD_LEFT) ?>
                                <?php endif; ?>
                            </td>
                            <td class="col-nom">
                                <strong><?= e(mb_strtoupper($resultat['nom'] . ' ' . $resultat['prenom'])) ?></strong>
                            </td>
                            <td class="col-sexe">
                                <?php if ($resultat['sexe'] === 'M'): ?>
                                    <span class="sexe-badge masculin">M</span>
                                <?php else: ?>
                                    <span class="sexe-badge feminin">F</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-pourcentage">
                                <span class="pourcentage-valeur <?= $pourcentage >= 70 ? 'excellent' : ($pourcentage >= 50 ? 'moyen' : 'faible') ?>">
                                    <?= number_format($pourcentage, 1) ?>
                                </span>
                            </td>
                            <td class="col-observation">
                                <?= e($resultat['mention']) ?> (<?= number_format($pourcentage, 1) ?>%)
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ========== SIGNATURES ========== -->
        <div class="proclamation-signatures">
            <div class="proclamation-date">
                Fait à _______________, le _______________
            </div>
            <div class="proclamation-signature-blocks">
                <div class="proclamation-signature-block">
                    <div class="proclamation-signature-line"></div>
                    <span>L'Enseignant Titulaire</span>
                    <?php if ($titulaire): ?>
                        <span class="proclamation-signature-nom"><?= e($titulaire['nom_complet']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="proclamation-signature-block">
                    <div class="proclamation-signature-line"></div>
                    <span>Le Directeur</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== BOUTONS D'ACTION ========== -->
    <div class="proclamation-actions no-print">
        <button type="button" class="btn btn-primary btn-lg" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer la proclamation
        </button>
        <a href="/gestion_scolaire/resultats/proces-verbaux?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-file-contract"></i> Voir le procès-verbal
        </a>
        <a href="/gestion_scolaire/resultats/deliberations?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-gavel"></i> Délibérations
        </a>
    </div>
<?php endif; ?>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';