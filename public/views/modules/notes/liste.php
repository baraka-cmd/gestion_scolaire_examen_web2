<?php
/**
 * Fichier : public/views/modules/notes/liste.php
 * Rôle    : Liste des évaluations avec filtres, progression et actions
 * Système : Bulletin congolais — Groupes → Sous-matières → Notes
 * Version : 2.0 — Refonte (groupe_id, actions saisir/modifier/supprimer)
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('notes.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'NoteController.php';
$noteController = new NoteController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $noteController->handleAjax();
}

// Récupérer les classes, trimestres et groupes pour les filtres
$classes = $noteController->getClassesActives();
$trimestres = $noteController->getTrimestresActifs();
$trimestreActif = $noteController->getTrimestreActif();

// Données initiales pour les cards (chargement PHP direct)
$whereEval = [];
$paramsEval = [];

if ($trimestreActif) {
    $whereEval[] = "ev.trimestre_id = :tid";
    $paramsEval['tid'] = $trimestreActif['id'];
}

$whereEvalClause = !empty($whereEval) ? 'WHERE ' . implode(' AND ', $whereEval) : '';

// Récupérer toutes les évaluations
$evaluations = $db->fetchAll(
    "SELECT ev.*, 
            t.libelle AS trimestre_libelle, 
            t.numero AS trimestre_numero,
            t.est_actif AS trimestre_actif,
            c.nom AS classe_nom, 
            c.niveau AS classe_niveau,
            m.libelle AS groupe_libelle,
            m.code AS groupe_code,
            (SELECT COUNT(*) FROM notes n WHERE n.evaluation_id = ev.id) AS nb_notes_saisies,
            (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = ev.classe_id AND i.statut = 'Actif') 
            * (SELECT COUNT(*) FROM matieres sm WHERE sm.parent_id = ev.groupe_id AND sm.est_groupe = 0 AND sm.est_actif = 1) AS nb_notes_attendues
     FROM evaluations ev
     INNER JOIN trimestres t ON ev.trimestre_id = t.id
     INNER JOIN classes c ON ev.classe_id = c.id
     INNER JOIN matieres m ON ev.groupe_id = m.id
     {$whereEvalClause}
     ORDER BY t.numero, c.niveau, FIELD(ev.type_evaluation, 'Période 1', 'Période 2', 'Examen')",
    $paramsEval
);

// Statistiques
$totalEvaluations = count($evaluations);
$totalNotesSaisies = (int) $db->fetchColumn("SELECT COUNT(*) FROM notes");
$totalNotesAttendues = array_sum(array_column($evaluations, 'nb_notes_attendues'));

// Métadonnées
$pageTitle = 'Gestion des notes';
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Gérez les évaluations et la saisie des notes';
$extraCSS = ['modules/notes.css'];
$extraJS  = ['modules/notes.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="evaluations-header">
    <div class="evaluations-header-left">
        <h4>
            <i class="fa-solid fa-pen-to-square"></i> Gestion des notes
        </h4>
        <p>Gérez les évaluations et saisissez les notes des élèves.</p>
    </div>
    <div class="evaluations-header-right">
        <?php if (can('notes.creer')): ?>
            <a href="/gestion_scolaire/notes/ajouter" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Nouvelle évaluation
            </a>
            <button type="button" class="btn btn-success btn-sm" id="btnGenerer">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Générer les évaluations
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-clipboard-list"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalEvaluations, 0, ',', ' ') ?></div>
            <div class="stat-label">Évaluations créées</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-pen-to-square"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalNotesSaisies, 0, ',', ' ') ?></div>
            <div class="stat-label">Notes saisies</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-chart-bar"></i></div>
        <div class="stat-content">
            <?php $pourcentage = $totalNotesAttendues > 0 ? round(($totalNotesSaisies / $totalNotesAttendues) * 100) : 0; ?>
            <div class="stat-value"><?= $pourcentage ?>%</div>
            <div class="stat-label">Progression globale</div>
        </div>
    </div>
</div>

<!-- ============================================================
     BARRE DE FILTRES
     ============================================================ -->
<div class="evaluations-filters">
    <div class="filter-group">
        <label for="filterClasse"><i class="fa-solid fa-chalkboard"></i> Classe</label>
        <select id="filterClasse">
            <option value="">Toutes les classes</option>
            <?php foreach ($classes as $classe): ?>
                <option value="<?= $classe['id'] ?>"><?= e($classe['nom']) ?> (Niv. <?= $classe['niveau'] ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterTrimestre"><i class="fa-solid fa-calendar-alt"></i> Trimestre</label>
        <select id="filterTrimestre">
            <option value="">Tous les trimestres</option>
            <?php foreach ($trimestres as $trimestre): ?>
                <option value="<?= $trimestre['id'] ?>" <?= ($trimestreActif && $trimestre['id'] == $trimestreActif['id']) ? 'selected' : '' ?>>
                    <?= e($trimestre['libelle']) ?> <?= $trimestre['est_actif'] ? '(Actif)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- ============================================================
     PROGRESSION DU TRIMESTRE
     ============================================================ -->
<div id="progressionContainer"></div>

<!-- ============================================================
     GRILLE DES ÉVALUATIONS
     ============================================================ -->
<div class="evaluations-grid" id="evaluationsGrid">
    <?php if (empty($evaluations)): ?>
        <div class="empty-state" style="grid-column:1/-1;">
            <i class="fa-solid fa-clipboard-list"></i>
            <p>Aucune évaluation trouvée</p>
            <?php if (can('notes.creer')): ?>
                <a href="/gestion_scolaire/notes/ajouter" class="btn btn-primary btn-sm mt-3">
                    <i class="fa-solid fa-plus"></i> Créer une évaluation
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($evaluations as $evaluation): 
            $nbSaisies = (int) $evaluation['nb_notes_saisies'];
            $nbAttendues = (int) $evaluation['nb_notes_attendues'];
            $pct = $nbAttendues > 0 ? round(($nbSaisies / $nbAttendues) * 100) : 0;
            
            // Déterminer les classes CSS selon le type
            $typeClass = '';
            $typeBadgeClass = '';
            $dotClass = '';
            $iconBadge = '';
            
            switch ($evaluation['type_evaluation']) {
                case 'Période 1':
                    $typeClass = 'periode1';
                    $typeBadgeClass = 'periode1';
                    $dotClass = 'periode1';
                    $iconBadge = 'fa-1';
                    break;
                case 'Période 2':
                    $typeClass = 'periode2';
                    $typeBadgeClass = 'periode2';
                    $dotClass = 'periode2';
                    $iconBadge = 'fa-2';
                    break;
                case 'Examen':
                    $typeClass = 'examen';
                    $typeBadgeClass = 'examen';
                    $dotClass = 'examen';
                    $iconBadge = 'fa-file-pen';
                    break;
            }
            
            // Progression
            $progressClass = $pct >= 80 ? 'complete' : ($pct > 0 ? 'in-progress' : 'empty');
        ?>
            <div class="evaluation-card" data-id="<?= $evaluation['id'] ?>">
                <div class="evaluation-card-header">
                    <div class="evaluation-card-type">
                        <span class="evaluation-type-dot <?= $dotClass ?>"></span>
                        <span class="evaluation-type-text"><?= e($evaluation['type_evaluation']) ?></span>
                    </div>
                    <span class="evaluation-card-badge <?= $typeBadgeClass ?>">
                        <i class="fa-solid <?= $iconBadge ?>"></i> <?= e($evaluation['groupe_libelle']) ?>
                    </span>
                </div>
                <div class="evaluation-card-body">
                    <div class="evaluation-card-info">
                        <div class="evaluation-card-info-row">
                            <i class="fa-solid fa-chalkboard"></i>
                            <span>Classe : <strong><?= e($evaluation['classe_nom']) ?></strong> (Niv. <?= $evaluation['classe_niveau'] ?>)</span>
                        </div>
                        <div class="evaluation-card-info-row">
                            <i class="fa-solid fa-calendar-alt"></i>
                            <span>Trimestre : <strong><?= e($evaluation['trimestre_libelle']) ?></strong></span>
                        </div>
                        <div class="evaluation-card-info-row">
                            <i class="fa-regular fa-calendar"></i>
                            <span>Date : <strong><?= formatDate($evaluation['date_evaluation'], 'd/m/Y') ?></strong></span>
                        </div>
                    </div>

                    <!-- Barre de progression -->
                    <div class="evaluation-progress">
                        <div class="evaluation-progress-header">
                            <span class="evaluation-progress-label">Notes saisies</span>
                            <span class="evaluation-progress-value <?= $progressClass ?>">
                                <?= $nbSaisies ?> / <?= $nbAttendues ?> (<?= $pct ?>%)
                            </span>
                        </div>
                        <div class="evaluation-progress-bar">
                            <div class="evaluation-progress-fill <?= $progressClass ?>" style="width: <?= $pct ?>%;"></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="evaluation-card-actions">
                        <a href="/gestion_scolaire/notes/saisie/<?= $evaluation['id'] ?>" class="btn-saisir">
                            <i class="fa-solid fa-pen-to-square"></i> Saisir les notes
                        </a>
                        <?php if (can('notes.modifier')): ?>
                            <a href="/gestion_scolaire/notes/ajouter/<?= $evaluation['id'] ?>" class="btn-modifier" title="Modifier la date">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (can('notes.supprimer')): ?>
                            <button type="button" class="btn-supprimer" 
                                    onclick="supprimerEvaluation(<?= $evaluation['id'] ?>, '<?= e($evaluation['type_evaluation']) ?>', '<?= e($evaluation['groupe_libelle']) ?>', '<?= e($evaluation['classe_nom']) ?>')" 
                                    title="Supprimer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ============================================================
     GUIDE
     ============================================================ -->
<div class="info-card-custom mt-4">
    <div class="info-card-custom-header">
        <div class="info-card-custom-icon blue">
            <i class="fa-solid fa-circle-info"></i>
        </div>
        <h6>Fonctionnement des évaluations</h6>
    </div>
    <div class="info-card-custom-body">
        <ul class="info-list">
            <li>Chaque trimestre comporte <strong>3 évaluations par groupe</strong> : Période 1, Période 2, Examen.</li>
            <li>Utilisez le bouton <strong>"Générer les évaluations"</strong> pour créer automatiquement les évaluations manquantes pour une classe et un trimestre.</li>
            <li>Cliquez sur <strong>"Saisir les notes"</strong> pour accéder à la grille de saisie (toutes les sous-matières du groupe).</li>
            <li>Utilisez les icônes <i class="fa-solid fa-pen-to-square"></i> et <i class="fa-solid fa-trash"></i> pour modifier la date ou supprimer une évaluation.</li>
            <li>La <strong>progression</strong> indique le pourcentage de notes déjà saisies par rapport au total attendu.</li>
            <li><strong>Total Trimestre = P1 + P2 + Examen</strong> (addition directe des points, sans pourcentage de pondération).</li>
            <li>Utilisez <strong>Ctrl+S</strong> dans la grille de saisie pour sauvegarder rapidement les notes.</li>
        </ul>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';