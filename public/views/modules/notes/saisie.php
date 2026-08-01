<?php
/**
 * Fichier : public/views/modules/evaluations/saisie.php
 * Rôle    : Saisie des notes pour une évaluation (grille Élèves × Sous-matières)
 * Système : Bulletin congolais — Groupes → Sous-matières → Notes
 * Version : 2.0 — Refonte (groupe_id, page unique de saisie)
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('notes.saisir');

global $db;

// Récupérer l'ID de l'évaluation depuis l'URL
$evaluationId = (int) ($_GET['id'] ?? $_GET['evaluation_id'] ?? 0);
if ($evaluationId <= 0) {
    setFlash('error', 'ID évaluation invalide.');
    redirect('/gestion_scolaire/notes');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'NoteController.php';
$noteController = new NoteController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $noteController->handleAjax();
}

// Récupérer les infos de l'évaluation
$evaluation = $db->fetchOne(
    "SELECT ev.*, 
            t.libelle AS trimestre_libelle, 
            t.numero AS trimestre_numero,
            c.nom AS classe_nom, 
            c.niveau AS classe_niveau, 
            c.id AS classe_id,
            m.libelle AS groupe_libelle, 
            m.id AS groupe_id
     FROM evaluations ev
     INNER JOIN trimestres t ON ev.trimestre_id = t.id
     INNER JOIN classes c ON ev.classe_id = c.id
     INNER JOIN matieres m ON ev.groupe_id = m.id
     WHERE ev.id = :id",
    ['id' => $evaluationId]
);

if (!$evaluation) {
    setFlash('error', 'Évaluation introuvable.');
    redirect('/gestion_scolaire/notes');
}

// Récupérer les sous-matières du groupe avec leurs maxima pour cette classe
$sousMatieres = $noteController->getSousMatieresByGroupe($evaluation['groupe_id'], $evaluation['classe_id']);

// Récupérer les élèves de la classe
$eleves = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe
     FROM inscriptions i
     INNER JOIN eleves e ON i.eleve_id = e.id
     WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1
     ORDER BY e.nom, e.prenom",
    ['cid' => $evaluation['classe_id']]
);

// Récupérer les notes existantes
$notesExistantes = $db->fetchAll(
    "SELECT n.id, n.eleve_id, n.sous_matiere_id, n.valeur, n.absence
     FROM notes n
     WHERE n.evaluation_id = :eid",
    ['eid' => $evaluationId]
);

// Organiser les notes en grille [eleve_id][sous_matiere_id]
$notesGrid = [];
foreach ($notesExistantes as $note) {
    $notesGrid[$note['eleve_id']][$note['sous_matiere_id']] = [
        'id'      => $note['id'],
        'valeur'  => $note['valeur'] !== null ? (float) $note['valeur'] : null,
        'absence' => (bool) $note['absence']
    ];
}

// Statistiques de saisie
$nbNotesSaisies = count($notesExistantes);
$nbTotalNotes = count($eleves) * count($sousMatieres);
$pourcentageSaisie = $nbTotalNotes > 0 ? round(($nbNotesSaisies / $nbTotalNotes) * 100) : 0;

// Déterminer la couleur du type d'évaluation
$typeColor = match($evaluation['type_evaluation']) {
    'Période 1' => ['bg' => '#dbeafe', 'text' => '#1e40af', 'icon' => 'fa-1'],
    'Période 2' => ['bg' => '#fef3c7', 'text' => '#92400e', 'icon' => 'fa-2'],
    'Examen'    => ['bg' => '#fee2e2', 'text' => '#991b1b', 'icon' => 'fa-file-pen'],
    default     => ['bg' => '#f1f5f9', 'text' => '#475569', 'icon' => 'fa-circle']
};

$pageTitle = 'Saisie des notes — ' . $evaluation['classe_nom'];
$pageIcon  = 'fa-pen-to-square';
$pageDescription = $evaluation['type_evaluation'] . ' — ' . $evaluation['groupe_libelle'] . ' — ' . $evaluation['trimestre_libelle'];
$extraCSS = ['modules/evaluations.css', 'modules/saisie_notes.css'];
$extraJS  = ['modules/saisie_notes.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/notes">Évaluations</a>
    <span class="separator">/</span>
    <span class="current">Saisie — <?= e($evaluation['classe_nom']) ?></span>
</nav>

<!-- ============================================================
     EN-TÊTE DE L'ÉVALUATION
     ============================================================ -->
<div class="saisie-header-card">
    <div class="saisie-header-left">
        <div class="saisie-header-icon" style="background:<?= $typeColor['bg'] ?>;">
            <i class="fa-solid <?= $typeColor['icon'] ?>" style="color:<?= $typeColor['text'] ?>;"></i>
        </div>
        <div class="saisie-header-info">
            <h4 class="saisie-header-title">
                <?= e($evaluation['type_evaluation']) ?> — <?= e($evaluation['groupe_libelle']) ?>
            </h4>
            <div class="saisie-header-meta">
                <span><i class="fa-solid fa-chalkboard"></i> <?= e($evaluation['classe_nom']) ?> (Niv. <?= $evaluation['classe_niveau'] ?>)</span>
                <span><i class="fa-solid fa-calendar-alt"></i> <?= e($evaluation['trimestre_libelle']) ?></span>
                <span><i class="fa-regular fa-calendar"></i> <?= formatDate($evaluation['date_evaluation'], 'd/m/Y') ?></span>
                <span><i class="fa-solid fa-list-ul"></i> <?= count($sousMatieres) ?> sous-matière(s)</span>
                <span><i class="fa-solid fa-users"></i> <?= count($eleves) ?> élève(s)</span>
            </div>
        </div>
    </div>
    <div class="saisie-header-right">
        <div class="saisie-progression-badge">
            <div class="saisie-progression-circle" style="--pct:<?= $pourcentageSaisie ?>;">
                <svg viewBox="0 0 36 36">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none" stroke="#e2e8f0" stroke-width="3"/>
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none" stroke="<?= $pourcentageSaisie >= 80 ? '#10b981' : ($pourcentageSaisie >= 40 ? '#f59e0b' : '#ef4444') ?>" 
                          stroke-width="3" stroke-dasharray="<?= $pourcentageSaisie ?>, 100"/>
                </svg>
                <span><?= $pourcentageSaisie ?>%</span>
            </div>
            <span style="font-size:0.72rem;color:var(--color-gray-500);"><?= $nbNotesSaisies ?>/<?= $nbTotalNotes ?> notes</span>
        </div>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     BARRE D'OUTILS
     ============================================================ -->
<div class="saisie-toolbar">
    <div class="saisie-toolbar-left">
        <div class="saisie-legend">
            <span class="legend-dot saisie"></span> Note saisie
        </div>
        <div class="saisie-legend">
            <span class="legend-dot absent"></span> Absent
        </div>
        <div class="saisie-legend">
            <span class="legend-dot vide"></span> Non saisi
        </div>
        <div class="saisie-legend">
            <span class="legend-dot modifie"></span> Modifié non sauvegardé
        </div>
    </div>
    <div class="saisie-toolbar-right">
        <div class="saisie-shortcut">
            <kbd>Tab</kbd> Cellule suivante
        </div>
        <div class="saisie-shortcut">
            <kbd>Ctrl</kbd> + <kbd>S</kbd> Sauvegarder
        </div>
        <div class="saisie-shortcut">
            <kbd>Double-clic</kbd> Absent
        </div>
        <div class="saisie-shortcut">
            <kbd>Ctrl</kbd> + <kbd>Z</kbd> Annuler cellule
        </div>
    </div>
</div>

<!-- ============================================================
     GRILLE DE SAISIE
     ============================================================ -->
<div class="notes-grid-card" id="notesGridCard">
    <div class="notes-grid-header">
        <div class="notes-grid-icon">
            <i class="fa-solid fa-table-cells"></i>
        </div>
        <div>
            <h5><?= e($evaluation['groupe_libelle']) ?> — <?= e($evaluation['type_evaluation']) ?></h5>
            <p><?= count($eleves) ?> élèves × <?= count($sousMatieres) ?> sous-matières</p>
        </div>
        <div class="notes-grid-actions">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnToutVider" title="Vider toutes les notes">
                <i class="fa-solid fa-eraser"></i> Vider
            </button>
            <button type="button" class="btn btn-primary" id="btnSaveNotes" data-evaluation-id="<?= $evaluationId ?>">
                <i class="fa-solid fa-save"></i> Enregistrer
            </button>
        </div>
    </div>
    <div class="notes-grid-body">
        <?php if (empty($eleves)): ?>
            <div class="empty-state" style="padding:40px;">
                <i class="fa-solid fa-users-slash"></i>
                <p>Aucun élève inscrit dans cette classe.</p>
            </div>
        <?php elseif (empty($sousMatieres)): ?>
            <div class="empty-state" style="padding:40px;">
                <i class="fa-solid fa-book-open"></i>
                <p>Aucune sous-matière trouvée pour ce groupe.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="notes-grid-table" id="notesGridTable">
                    <thead>
                        <tr>
                            <th class="eleve-col">Élève</th>
                            <?php foreach ($sousMatieres as $sm): ?>
                                <th class="matiere-col">
                                    <div class="matiere-header">
                                        <span class="matiere-nom"><?= e($sm['code'] ?? $sm['libelle']) ?></span>
                                        <span class="matiere-maxima">/<?= $sm['maxima'] ?></span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="notesTableBody">
                        <?php foreach ($eleves as $eleve): ?>
                            <tr data-eleve-id="<?= $eleve['id'] ?>">
                                <td class="eleve-col">
                                    <div class="eleve-cell">
                                        <div class="eleve-avatar <?= $eleve['sexe'] === 'M' ? 'male' : 'female' ?>">
                                            <?= mb_strtoupper(mb_substr($eleve['prenom'], 0, 1) . mb_substr($eleve['nom'], 0, 1)) ?>
                                        </div>
                                        <div class="eleve-info">
                                            <div class="eleve-nom"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></div>
                                            <div class="eleve-matricule"><?= e($eleve['matricule']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <?php foreach ($sousMatieres as $sm): 
                                    $note = $notesGrid[$eleve['id']][$sm['id']] ?? null;
                                    $valeur = $note ? $note['valeur'] : '';
                                    $absence = $note && $note['absence'];
                                    $inputClass = $absence ? 'absent' : ($valeur !== '' && $valeur !== null ? 'saisie' : '');
                                ?>
                                    <td class="note-cell">
                                        <input type="text" 
                                               class="note-input <?= $inputClass ?>"
                                               data-eleve-id="<?= $eleve['id'] ?>"
                                               data-sous-matiere-id="<?= $sm['id'] ?>"
                                               data-maxima="<?= $sm['maxima'] ?>"
                                               data-original-value="<?= $absence ? 'ABS' : e($valeur) ?>"
                                               value="<?= $absence ? 'ABS' : e($valeur) ?>"
                                               style="<?= $absence ? 'color:#ef4444;' : '' ?>"
                                               placeholder="—"
                                               autocomplete="off"
                                               inputmode="decimal">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     RÉSUMÉ PAR SOUS-MATIÈRE
     ============================================================ -->
<div class="saisie-summary mt-4">
    <div class="saisie-summary-title">
        <i class="fa-solid fa-chart-simple"></i> Résumé de saisie par sous-matière
    </div>
    <div class="saisie-summary-grid" id="saisieSummary">
        <?php foreach ($sousMatieres as $sm): 
            $saisies = 0;
            $absents = 0;
            foreach ($eleves as $eleve) {
                $note = $notesGrid[$eleve['id']][$sm['id']] ?? null;
                if ($note && $note['absence']) $absents++;
                elseif ($note && $note['valeur'] !== null) $saisies++;
            }
            $total = count($eleves);
            $pct = $total > 0 ? round(($saisies / $total) * 100) : 0;
        ?>
            <div class="summary-item">
                <div class="summary-item-header">
                    <span class="summary-item-name"><?= e($sm['code'] ?? $sm['libelle']) ?></span>
                    <span class="summary-item-count"><?= $saisies ?>/<?= $total ?></span>
                </div>
                <div class="summary-item-bar">
                    <div class="summary-item-fill" style="width:<?= $pct ?>%;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============================================================
     GUIDE DE SAISIE
     ============================================================ -->
<div class="info-card-custom mt-4">
    <div class="info-card-custom-header">
        <div class="info-card-custom-icon blue">
            <i class="fa-solid fa-circle-info"></i>
        </div>
        <h6>Guide de saisie rapide</h6>
    </div>
    <div class="info-card-custom-body">
        <ul class="info-list">
            <li><strong>Navigation</strong> : Utilisez <strong>Tab</strong> pour passer à la cellule suivante, <strong>Shift+Tab</strong> pour revenir en arrière.</li>
            <li><strong>Sauvegarde</strong> : Appuyez sur <strong>Ctrl+S</strong> ou cliquez sur le bouton <strong>"Enregistrer"</strong>.</li>
            <li><strong>Absence</strong> : <strong>Double-cliquez</strong> sur une cellule pour marquer l'élève comme absent (affiche "ABS" en rouge).</li>
            <li><strong>Validation</strong> : La note ne peut pas dépasser le <strong>maxima</strong> de la sous-matière. La cellule devient rouge en cas de dépassement.</li>
            <li><strong>Indicateur visuel</strong> : Une <strong>bordure orange</strong> indique une cellule modifiée mais non sauvegardée.</li>
            <li><strong>Vider</strong> : Le bouton <strong>"Vider"</strong> efface toutes les notes non sauvegardées.</li>
            <li><strong>Ctrl+Z</strong> : Annule la dernière modification sur la cellule active.</li>
        </ul>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';