<?php
/**
 * Fichier : public/views/modules/absences/justifier.php
 * Rôle    : Interface de justification des absences par le parent/secrétaire
 * Système : École primaire congolaise
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('absences.justifier');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AbsenceController.php';
$absenceController = new AbsenceController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $absenceController->handleAjax();
}

// Paramètres
$eleveId = (int) ($_GET['eleve_id'] ?? 0);
$dateAbsence = $_GET['date'] ?? '';
$classeId = (int) ($_GET['classe_id'] ?? 0);

// Si parent connecté, récupérer ses enfants
$enfants = [];
$isParent = hasRole('Parent');
if ($isParent) {
    $parentId = (int) $db->fetchColumn(
        "SELECT id FROM parents_tuteurs WHERE utilisateur_id = :uid LIMIT 1",
        ['uid' => $_SESSION['utilisateur_id']]
    );
    if ($parentId > 0) {
        $enfants = $db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom, c.id AS classe_id
             FROM eleves e
             INNER JOIN eleve_parent ep ON e.id = ep.eleve_id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             WHERE ep.parent_id = :pid AND e.est_actif = 1
             ORDER BY e.nom",
            ['pid' => $parentId]
        );
    }
}

// Récupérer les classes pour le filtre (admin/secrétaire)
$classes = [];
if (!$isParent) {
    $classes = $absenceController->getClassesActives();
}

// Récupérer les élèves d'une classe
$eleves = [];
if ($classeId > 0 && !$isParent) {
    $eleves = $db->fetchAll(
        "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe
         FROM inscriptions i
         INNER JOIN eleves e ON i.eleve_id = e.id
         WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1
         ORDER BY e.nom",
        ['cid' => $classeId]
    );
}

// Récupérer les absences non justifiées
$absencesNonJustifiees = [];
if ($eleveId > 0) {
    $absencesNonJustifiees = $db->fetchAll(
        "SELECT a.*, DATE_FORMAT(a.date_absence, '%d/%m/%Y') AS date_format,
                e.matricule, e.nom, e.prenom,
                c.nom AS classe_nom
         FROM absences a
         INNER JOIN eleves e ON a.eleve_id = e.id
         INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
         INNER JOIN classes c ON i.classe_id = c.id
         WHERE a.eleve_id = :eid AND a.type_absence = 'Absence'
         ORDER BY a.date_absence DESC",
        ['eid' => $eleveId]
    );
} elseif (count($enfants) === 1 && $isParent) {
    // Auto-sélectionner l'unique enfant
    $eleveId = $enfants[0]['id'];
    $absencesNonJustifiees = $db->fetchAll(
        "SELECT a.*, DATE_FORMAT(a.date_absence, '%d/%m/%Y') AS date_format
         FROM absences a
         WHERE a.eleve_id = :eid AND a.type_absence = 'Absence'
         ORDER BY a.date_absence DESC",
        ['eid' => $eleveId]
    );
}

// Récupérer toutes les absences justifiées (historique)
$absencesJustifiees = [];
if ($eleveId > 0) {
    $absencesJustifiees = $db->fetchAll(
        "SELECT a.*, DATE_FORMAT(a.date_absence, '%d/%m/%Y') AS date_format,
                DATE_FORMAT(a.date_justification, '%d/%m/%Y') AS date_justification_format
         FROM absences a
         WHERE a.eleve_id = :eid AND a.type_absence = 'Absence' AND a.est_justifiee = 1
         ORDER BY a.date_absence DESC",
        ['eid' => $eleveId]
    );
}

$pageTitle = 'Justifier les absences';
$pageIcon  = 'fa-file-medical';
$pageDescription = 'Justifiez les absences des élèves';
$extraCSS = ['modules/absences.css'];
$extraJS  = ['modules/absences.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/absences/liste">Absences</a>
    <span class="separator">/</span>
    <span class="current">Justifier</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-file-medical"></i> Justifier les absences</h4>
        <p>Justifiez les absences non justifiées des élèves.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/absences/liste" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Liste des absences
        </a>
        <a href="/gestion_scolaire/absences/marquer" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-calendar-check"></i> Marquer les présences
        </a>
    </div>
</div>

<div id="flashMessage"></div>

<!-- SÉLECTEURS -->
<div class="resultats-selectors no-print">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header"><i class="fa-solid fa-filter"></i> Sélectionner un élève</div>
        <div class="resultats-selector-body">
            <form method="GET" action="" class="resultats-selector-form">
                <div class="form-row">
                    <?php if (!$isParent): ?>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe</label>
                        <select name="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>><?= e($cl['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user-graduate"></i> Élève</label>
                        <select name="eleve_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Élève —</option>
                            <?php foreach ($eleves as $el): ?>
                                <option value="<?= $el['id'] ?>" <?= $eleveId === (int) $el['id'] ? 'selected' : '' ?>><?= e($el['prenom'] . ' ' . $el['nom']) ?> — <?= e($el['matricule']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-children"></i> Mon enfant</label>
                        <select name="eleve_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez —</option>
                            <?php foreach ($enfants as $enf): ?>
                                <option value="<?= $enf['id'] ?>" <?= $eleveId === (int) $enf['id'] ? 'selected' : '' ?>><?= e($enf['prenom'] . ' ' . $enf['nom']) ?> — <?= e($enf['classe_nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($eleveId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-file-medical"></i>
        <p>Sélectionnez un élève pour voir et justifier ses absences.</p>
    </div>

<?php else: ?>
    <!-- STATS DE L'ÉLÈVE -->
    <?php 
    $totalAbsences = count($absencesNonJustifiees) + count($absencesJustifiees);
    $nonJustifiees = count($absencesNonJustifiees);
    $justifiees = count($absencesJustifiees);
    ?>
    <div class="retards-stats mb-4">
        <div class="retard-stat-card">
            <div class="retard-stat-icon red"><i class="fa-solid fa-calendar-xmark"></i></div>
            <div class="retard-stat-content">
                <div class="retard-stat-value"><?= $totalAbsences ?></div>
                <div class="retard-stat-label">Total absences</div>
            </div>
        </div>
        <div class="retard-stat-card">
            <div class="retard-stat-icon orange"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="retard-stat-content">
                <div class="retard-stat-value"><?= $nonJustifiees ?></div>
                <div class="retard-stat-label">Non justifiées</div>
            </div>
        </div>
        <div class="retard-stat-card">
            <div class="retard-stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
            <div class="retard-stat-content">
                <div class="retard-stat-value"><?= $justifiees ?></div>
                <div class="retard-stat-label">Justifiées</div>
            </div>
        </div>
        <div class="retard-stat-card">
            <div class="retard-stat-icon blue"><i class="fa-solid fa-percent"></i></div>
            <div class="retard-stat-content">
                <div class="retard-stat-value"><?= $totalAbsences > 0 ? round(($justifiees / $totalAbsences) * 100) : 0 ?>%</div>
                <div class="retard-stat-label">Taux de justification</div>
            </div>
        </div>
    </div>

    <!-- ABSENCES NON JUSTIFIÉES -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fa-solid fa-circle-exclamation" style="color:#f59e0b;"></i> Absences non justifiées</h5>
            <span class="badge badge-warning"><?= $nonJustifiees ?> à justifier</span>
        </div>
        <div class="card-body no-padding">
            <?php if (empty($absencesNonJustifiees)): ?>
                <div class="empty-state" style="padding:30px;">
                    <i class="fa-solid fa-check-circle" style="color:#10b981;"></i>
                    <p>Toutes les absences sont justifiées.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th>Motif actuel</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($absencesNonJustifiees as $abs): ?>
                                <tr>
                                    <td><strong><?= e($abs['date_format']) ?></strong></td>
                                    <td><strong><?= e($abs['prenom'] . ' ' . $abs['nom']) ?></strong><br><small class="text-muted"><?= e($abs['matricule']) ?></small></td>
                                    <td><?= e($abs['classe_nom'] ?? '—') ?></td>
                                    <td><?= e($abs['motif'] ?: 'Aucun motif') ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary btn-justifier-absence"
                                                onclick="ouvrirJustification(<?= $abs['id'] ?>, '<?= e($abs['date_format']) ?>')">
                                            <i class="fa-solid fa-file-medical"></i> Justifier
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ABSENCES JUSTIFIÉES -->
    <?php if (!empty($absencesJustifiees)): ?>
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-check-circle" style="color:#10b981;"></i> Absences justifiées</h5>
            <span class="badge badge-success"><?= $justifiees ?> justifiée(s)</span>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Motif</th>
                            <th>Date justification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absencesJustifiees as $abs): ?>
                            <tr>
                                <td><strong><?= e($abs['date_format']) ?></strong></td>
                                <td><?= e($abs['motif'] ?: '—') ?></td>
                                <td><?= e($abs['date_justification_format'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- MODALE JUSTIFICATION -->
<div class="modal-overlay" id="modalJustification">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h5><i class="fa-solid fa-file-medical"></i> Justifier une absence</h5>
            <button type="button" class="modal-close" onclick="closeModalJustification()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formJustification" autocomplete="off">
                <input type="hidden" id="just_absence_id" name="absence_id" value="">

                <div id="justDateInfo" style="padding:10px 14px;background:#fef3c7;border-radius:8px;margin-bottom:16px;font-size:0.82rem;color:#92400e;">
                    <i class="fa-solid fa-calendar"></i> Absence du : <strong id="justDateAbsence"></strong>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-tag"></i> Motif de justification <span class="required">*</span></label>
                    <select id="just_motif" name="motif" class="form-control" required>
                        <option value="">— Sélectionnez un motif —</option>
                        <optgroup label="Raisons médicales">
                            <option value="Maladie">Maladie</option>
                            <option value="Consultation médicale">Consultation médicale</option>
                            <option value="Hospitalisation">Hospitalisation</option>
                        </optgroup>
                        <optgroup label="Raisons familiales">
                            <option value="Décès familial">Décès familial</option>
                            <option value="Mariage familial">Mariage familial</option>
                            <option value="Obligation familiale">Obligation familiale</option>
                        </optgroup>
                        <optgroup label="Autres raisons">
                            <option value="Transport">Problème de transport</option>
                            <option value="Intempéries">Intempéries / Pluie</option>
                            <option value="Insécurité">Insécurité</option>
                            <option value="Autre">Autre raison</option>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-align-left"></i> Commentaire</label>
                    <textarea id="just_commentaire" name="commentaire" class="form-control" rows="3" placeholder="Détails supplémentaires sur la justification..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-paperclip"></i> Document justificatif <span style="font-weight:400;color:var(--color-gray-400);">(optionnel)</span></label>
                    <input type="file" id="just_document" name="justificatif_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="form-help"><i class="fa-solid fa-info-circle"></i> Formats acceptés : PDF, JPG, PNG — Max 5 Mo</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" onclick="closeModalJustification()"><i class="fa-solid fa-xmark"></i> Annuler</button>
            <button type="button" class="btn btn-primary" id="btnSaveJustification"><i class="fa-solid fa-save"></i> Enregistrer la justification</button>
        </div>
    </div>
</div>

<script>
function ouvrirJustification(absenceId, dateAbsence) {
    document.getElementById('just_absence_id').value = absenceId;
    document.getElementById('justDateAbsence').textContent = dateAbsence;
    document.getElementById('modalJustification').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModalJustification() {
    document.getElementById('modalJustification').classList.remove('show');
    document.body.style.overflow = '';
    document.getElementById('formJustification').reset();
}

document.getElementById('modalJustification').addEventListener('click', function(e) {
    if (e.target === this) closeModalJustification();
});

document.getElementById('btnSaveJustification').addEventListener('click', async function() {
    const absenceId = document.getElementById('just_absence_id').value;
    const motif = document.getElementById('just_motif').value;
    const commentaire = document.getElementById('just_commentaire').value;

    if (!motif) {
        showToast('Veuillez sélectionner un motif de justification.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'justifierAbsenceDirecte');
    formData.append('absence_id', absenceId);
    formData.append('motif', motif);
    formData.append('commentaire', commentaire);

    const fichier = document.getElementById('just_document').files[0];
    if (fichier) {
        formData.append('justificatif_document', fichier);
    }

    this.disabled = true;
    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';

    try {
        const res = await fetch('/gestion_scolaire/absences', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeModalJustification();
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('Erreur de connexion.', 'error');
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer la justification';
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModalJustification();
});
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>