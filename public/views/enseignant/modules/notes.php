<?php
/**
 * Fichier : public/views/modules/enseignants/mes_notes.php
 * Rôle    : Liste des évaluations filtrées pour la classe de l'enseignant
 * Système : École primaire congolaise — Espace Enseignant
 * Version : 1.0
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

// Récupérer la classe de l'enseignant connecté
$enseignantId = $_SESSION['utilisateur_id'] ?? 0;
$classeId = (int) $db->fetchColumn(
    "SELECT e.classe_id FROM enseignants e WHERE e.utilisateur_id = :uid AND e.est_actif = 1 LIMIT 1",
    ['uid' => $enseignantId]
);

if ($classeId <= 0) {
    setFlash('warning', 'Aucune classe ne vous est assignée. Contactez l\'administration.');
    redirect('/gestion_scolaire/dashboard');
}

// Récupérer les infos de la classe
$maClasse = $db->fetchOne(
    "SELECT c.id, c.nom, c.niveau, c.salle, a.libelle AS annee_libelle
     FROM classes c
     INNER JOIN annees_scolaires a ON c.annee_scolaire_id = a.id
     WHERE c.id = :id",
    ['id' => $classeId]
);

if (!$maClasse) {
    setFlash('error', 'Classe introuvable.');
    redirect('/gestion_scolaire/dashboard');
}

// Récupérer les classes pour les filtres (une seule : la sienne)
$classes = $noteController->getClassesActives();

// Récupérer les trimestres
$trimestres = $noteController->getTrimestresActifs();
$trimestreActif = $noteController->getTrimestreActif();

// Récupérer les groupes actifs
$groupes = $noteController->getGroupesActifs();

// Données initiales pour les cards (filtrées sur la classe de l'enseignant)
$whereEval = [];
$paramsEval = [];

$whereEval[] = "ev.classe_id = :cid";
$paramsEval['cid'] = $classeId;

if ($trimestreActif) {
    $whereEval[] = "ev.trimestre_id = :tid";
    $paramsEval['tid'] = $trimestreActif['id'];
}

$whereEvalClause = 'WHERE ' . implode(' AND ', $whereEval);

// Récupérer toutes les évaluations de cette classe
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
     ORDER BY t.numero, FIELD(ev.type_evaluation, 'Période 1', 'Période 2', 'Examen')",
    $paramsEval
);

// Statistiques
$totalEvaluations = count($evaluations);
$totalNotesSaisies = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM notes n INNER JOIN evaluations ev ON n.evaluation_id = ev.id WHERE ev.classe_id = :cid",
    ['cid' => $classeId]
);
$totalNotesAttendues = array_sum(array_column($evaluations, 'nb_notes_attendues'));
$progressionGlobale = $totalNotesAttendues > 0 ? round(($totalNotesSaisies / $totalNotesAttendues) * 100) : 0;

// Métadonnées
$pageTitle = 'Saisie des notes — ' . e($maClasse['nom']);
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Classe : ' . e($maClasse['nom']) . ' — Gérez les évaluations et saisissez les notes';
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
            <i class="fa-solid fa-pen-to-square"></i> Saisie des notes — <?= e($maClasse['nom']) ?>
        </h4>
        <p>
            <i class="fa-solid fa-chalkboard"></i> <?= e($maClasse['nom']) ?> (Niveau <?= $maClasse['niveau'] ?>) — 
            <i class="fa-solid fa-door-open"></i> Salle <?= e($maClasse['salle'] ?? '—') ?> — 
            <i class="fa-solid fa-calendar-alt"></i> <?= e($maClasse['annee_libelle']) ?>
        </p>
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
            <div class="stat-value"><?= $progressionGlobale ?>%</div>
            <div class="stat-label">Progression globale</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-layer-group"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= count($groupes) ?></div>
            <div class="stat-label">Groupes de matières</div>
        </div>
    </div>
</div>

<!-- ============================================================
     BARRE DE FILTRES (classe pré-sélectionnée et verrouillée)
     ============================================================ -->
<div class="evaluations-filters">
    <div class="filter-group">
        <label for="filterClasse"><i class="fa-solid fa-chalkboard"></i> Classe</label>
        <select id="filterClasse" disabled style="background:#f1f5f9;cursor:not-allowed;">
            <option value="<?= $maClasse['id'] ?>" selected><?= e($maClasse['nom']) ?> (Niv. <?= $maClasse['niveau'] ?>)</option>
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
            <p>Aucune évaluation trouvée pour votre classe</p>
            <?php if (can('notes.creer')): ?>
                <button type="button" class="btn btn-success btn-sm mt-3" id="btnGenererBis">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Générer les évaluations
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($evaluations as $evaluation): 
            $nbSaisies = (int) $evaluation['nb_notes_saisies'];
            $nbAttendues = (int) $evaluation['nb_notes_attendues'];
            $pct = $nbAttendues > 0 ? round(($nbSaisies / $nbAttendues) * 100) : 0;
            
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

                    <div class="evaluation-card-actions">
                        <a href="/gestion_scolaire/notes/saisie/<?= $evaluation['id'] ?>" class="btn-saisir">
                            <i class="fa-solid fa-pen-to-square"></i> Saisir les notes
                        </a>
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
            <li>Utilisez le bouton <strong>"Générer les évaluations"</strong> pour créer automatiquement les évaluations manquantes.</li>
            <li>Cliquez sur <strong>"Saisir les notes"</strong> pour accéder à la grille de saisie (toutes les sous-matières du groupe).</li>
            <li>La <strong>progression</strong> indique le pourcentage de notes déjà saisies par rapport au total attendu.</li>
            <li><strong>Total Trimestre = P1 + P2 + Examen</strong> (addition directe des points).</li>
            <li>Utilisez <strong>Ctrl+S</strong> dans la grille de saisie pour sauvegarder rapidement les notes.</li>
        </ul>
    </div>
</div>

<!-- ============================================================
     SCRIPT : Forcer le filtre sur la classe de l'enseignant
     ============================================================ -->
<script>
var enseignantClasseId = <?= $classeId ?>;

document.addEventListener('DOMContentLoaded', function() {
    var filterClasse = document.getElementById('filterClasse');
    if (filterClasse && enseignantClasseId > 0) {
        filterClasse.value = enseignantClasseId;
    }
});

// Surcharger les fonctions de filtre pour forcer la classe
var originalGetFiltres = window.getFiltres;
window.getFiltres = function() {
    var filtres = originalGetFiltres ? originalGetFiltres() : {};
    filtres.classe_id = enseignantClasseId;
    return filtres;
};

// Bouton générer adapté à la classe de l'enseignant
document.getElementById('btnGenerer')?.addEventListener('click', function() {
    var formData = new FormData();
    formData.append('action', 'genererEvaluations');
    formData.append('classe_id', enseignantClasseId);
    formData.append('trimestre_id', document.getElementById('filterTrimestre')?.value || '');

    this.disabled = true;
    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Génération...';

    fetch('/gestion_scolaire/notes', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(function() {
        showToast('Erreur de connexion.', 'error');
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les évaluations';
    });
});

document.getElementById('btnGenererBis')?.addEventListener('click', function() {
    document.getElementById('btnGenerer')?.click();
});
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>