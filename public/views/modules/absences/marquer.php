<?php
/**
 * Fichier : public/views/modules/absences/marquer.php
 * Rôle    : Interface de marquage quotidien des présences/absences
 * Système : École primaire congolaise — Pointage quotidien par classe
 * Version : 1.1 — Correction sauvegarde
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('absences.marquer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AbsenceController.php';
$absenceController = new AbsenceController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $absenceController->handleAjax();
}

// Récupérer les classes
$classes = $absenceController->getClassesActives();

// Paramètres
$classeId = (int) ($_GET['classe_id'] ?? 0);
$dateAbsence = $_GET['date'] ?? date('Y-m-d');

// Si enseignant, récupérer automatiquement sa classe
if (hasRole('Enseignant') && $classeId <= 0) {
    $classeId = $absenceController->getClasseEnseignant();
}

// Récupérer les infos de la classe
$classe = null;
$eleves = [];
$absencesExistantes = [];

if ($classeId > 0) {
    $classe = $db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]);

    // Récupérer les élèves
    $eleves = $db->fetchAll(
        "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe, e.photo,
                DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format
         FROM inscriptions i
         INNER JOIN eleves e ON i.eleve_id = e.id
         WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1
         ORDER BY e.nom, e.prenom",
        ['cid' => $classeId]
    );

    // Récupérer les absences déjà marquées pour cette date
    $absencesExistantes = $db->fetchAll(
        "SELECT a.eleve_id, a.type_absence, a.est_justifiee, a.motif
         FROM absences a
         WHERE a.eleve_id IN (SELECT eleve_id FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif')
         AND a.date_absence = :date",
        ['cid' => $classeId, 'date' => $dateAbsence]
    );

    // Organiser par eleve_id
    $absencesMap = [];
    foreach ($absencesExistantes as $abs) {
        $absencesMap[$abs['eleve_id']] = $abs;
    }
}

// Statistiques du jour
$totalEleves = count($eleves);
$dejaMarques = count($absencesExistantes);
$presences = count(array_filter($absencesExistantes, fn($a) => $a['type_absence'] === 'Présent'));
$absences = count(array_filter($absencesExistantes, fn($a) => $a['type_absence'] === 'Absence'));

$pageTitle = 'Marquer les présences';
$pageIcon  = 'fa-calendar-check';
$pageDescription = $classe ? 'Pointage du ' . formatDate($dateAbsence, 'l j F Y') . ' — ' . e($classe['nom']) : 'Marquez les présences et absences quotidiennes';
$extraCSS = ['modules/absences.css'];
$extraJS  = ['modules/absences.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/absences/liste">Absences</a>
    <span class="separator">/</span>
    <span class="current">Marquer</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-calendar-check"></i> Marquer les présences</h4>
        <p>Pointage quotidien des élèves — <?= $classe ? e($classe['nom']) : 'Sélectionnez une classe' ?></p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/absences/liste" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Liste des absences
        </a>
        <a href="/gestion_scolaire/absences/justifier" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-file-medical"></i> Justifier
        </a>
    </div>
</div>

<div id="flashMessage"></div>

<!-- SÉLECTEURS -->
<div class="resultats-selectors no-print">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header"><i class="fa-solid fa-filter"></i> Sélectionner la classe et la date</div>
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
                        <label class="form-label"><i class="fa-solid fa-calendar"></i> Date</label>
                        <input type="date" name="date" class="form-control" value="<?= $dateAbsence ?>" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($classeId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-calendar-check"></i>
        <p>Sélectionnez une classe pour marquer les présences du jour.</p>
    </div>

<?php elseif (empty($eleves)): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-users-slash"></i>
        <p>Aucun élève inscrit dans cette classe.</p>
    </div>

<?php else: ?>
    <!-- BARRE D'INFO -->
    <div class="absence-info-bar">
        <div class="absence-info-item">
            <i class="fa-solid fa-calendar"></i>
            <span><strong><?= formatDate($dateAbsence, 'l j F Y') ?></strong></span>
        </div>
        <div class="absence-info-item">
            <i class="fa-solid fa-users"></i>
            <span><strong><?= $totalEleves ?></strong> élève(s)</span>
        </div>
        <div class="absence-info-item present">
            <i class="fa-solid fa-check-circle"></i>
            <span><strong><?= $presences ?></strong> présent(s)</span>
        </div>
        <div class="absence-info-item absent">
            <i class="fa-solid fa-times-circle"></i>
            <span><strong><?= $absences ?></strong> absent(s)</span>
        </div>
        <div class="absence-info-item">
            <i class="fa-solid fa-clock"></i>
            <span><strong><?= $totalEleves - $dejaMarques ?></strong> non marqué(s)</span>
        </div>
    </div>

    <!-- BARRE D'OUTILS -->
    <div class="absence-toolbar">
        <div class="absence-toolbar-left">
            <button type="button" class="btn btn-outline-success btn-sm" id="btnTousPresents">
                <i class="fa-solid fa-check-double"></i> Tous présents
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="btnTousAbsents">
                <i class="fa-solid fa-xmark"></i> Tous absents
            </button>
        </div>
        <div class="absence-toolbar-right">
            <span class="absence-legend">
                <span class="legend-dot present"></span> Présent
            </span>
            <span class="absence-legend">
                <span class="legend-dot absent"></span> Absent
            </span>
            <span class="absence-legend">
                <span class="legend-dot justifie"></span> Justifié
            </span>
            <span class="absence-legend">
                <span class="legend-dot non-marque"></span> Non marqué
            </span>
        </div>
    </div>

    <!-- LISTE DES ÉLÈVES -->
    <div class="absence-list-card">
        <div class="absence-eleves-grid" id="absenceElevesGrid">
            <?php foreach ($eleves as $eleve): 
                $absence = $absencesMap[$eleve['id']] ?? null;
                $estPresent = $absence && $absence['type_absence'] === 'Présent';
                $estAbsent = $absence && $absence['type_absence'] === 'Absence';
                $estJustifie = $absence && $absence['est_justifiee'];
                $estMarque = $absence !== null;
            ?>
                <div class="absence-eleve-card <?= $estMarque ? ($estPresent ? 'present' : 'absent') : 'non-marque' ?> <?= $estJustifie ? 'justifie' : '' ?>"
                     data-eleve-id="<?= $eleve['id'] ?>">
                    
                    <div class="absence-eleve-avatar <?= $eleve['sexe'] === 'M' ? 'male' : 'female' ?>">
                        <?= mb_strtoupper(mb_substr($eleve['prenom'], 0, 1) . mb_substr($eleve['nom'], 0, 1)) ?>
                    </div>

                    <div class="absence-eleve-info">
                        <span class="absence-eleve-nom"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></span>
                        <span class="absence-eleve-matricule"><?= e($eleve['matricule']) ?></span>
                    </div>

                    <div class="absence-eleve-statut">
                        <?php if ($estMarque): ?>
                            <span class="badge <?= $estPresent ? 'badge-success' : 'badge-danger' ?>">
                                <?= $estPresent ? 'Présent' : 'Absent' ?>
                            </span>
                            <?php if ($estJustifie): ?>
                                <span class="badge badge-info" style="margin-top:4px;">Justifié</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-secondary">Non marqué</span>
                        <?php endif; ?>
                    </div>

                    <div class="absence-eleve-actions">
                        <button type="button" class="btn-marquer present <?= $estMarque && $estPresent ? 'active' : '' ?>"
                                onclick="marquerEleve(<?= $eleve['id'] ?>, 'Présent', this)" title="Marquer présent">
                            <i class="fa-solid fa-check"></i>
                        </button>
                        <button type="button" class="btn-marquer absent <?= $estMarque && $estAbsent ? 'active' : '' ?>"
                                onclick="marquerEleve(<?= $eleve['id'] ?>, 'Absence', this)" title="Marquer absent">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <?php if ($estAbsent): ?>
                        <button type="button" class="btn-marquer justifier <?= $estJustifie ? 'active' : '' ?>"
                                onclick="justifierAbsence(<?= $eleve['id'] ?>)" title="Justifier l'absence">
                            <i class="fa-solid fa-file-medical"></i>
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($estAbsent): ?>
                    <div class="absence-motif-rapide">
                        <input type="text" class="form-control form-control-sm" 
                               placeholder="Motif (optionnel)" 
                               value="<?= e($absence['motif'] ?? '') ?>"
                               data-eleve-id="<?= $eleve['id'] ?>"
                               onchange="sauvegarderMotif(<?= $eleve['id'] ?>, this.value)">
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

        </div>
    <!-- FIN de absence-eleves-grid -->

    <!-- BOUTON SAUVEGARDE GLOBALE -->
    <div class="absence-actions-footer">
        <button type="button" class="btn btn-primary btn-lg" id="btnSauvegarderPointage">
            <i class="fa-solid fa-save"></i> Sauvegarder le pointage
        </button>
    </div>
</div>
<!-- FIN de absence-list-card -->

<!-- MODALE JUSTIFICATION -->
<div class="modal-overlay" id="modalJustifier">

</div>
    <!-- MODALE JUSTIFICATION -->
    <div class="modal-overlay" id="modalJustifier">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header">
                <h5><i class="fa-solid fa-file-medical"></i> Justifier une absence</h5>
                <button type="button" class="modal-close" onclick="closeModalJustifier()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formJustifier" autocomplete="off">
                    <input type="hidden" id="justifier_eleve_id" name="eleve_id" value="">
                    <input type="hidden" name="date_absence" value="<?= $dateAbsence ?>">

                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-tag"></i> Motif de justification</label>
                        <select id="justifier_motif" name="motif" class="form-control" required>
                            <option value="">— Sélectionnez un motif —</option>
                            <option value="Maladie">Maladie</option>
                            <option value="Décès familial">Décès familial</option>
                            <option value="Événement familial">Événement familial (mariage, etc.)</option>
                            <option value="Raison médicale">Raison médicale</option>
                            <option value="Transport">Problème de transport</option>
                            <option value="Intempéries">Intempéries</option>
                            <option value="Autre">Autre raison</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-align-left"></i> Commentaire</label>
                        <textarea id="justifier_commentaire" name="commentaire" class="form-control" rows="2" placeholder="Détails supplémentaires..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" onclick="closeModalJustifier()"><i class="fa-solid fa-xmark"></i> Annuler</button>
                <button type="button" class="btn btn-primary" id="btnSaveJustification"><i class="fa-solid fa-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
var dateAbsence = '<?= $dateAbsence ?>';
var classeId = <?= $classeId ?>;

function marquerEleve(eleveId, type, btn) {
    const card = btn.closest('.absence-eleve-card');
    if (!card) return;

    card.querySelectorAll('.btn-marquer').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    card.classList.remove('present', 'absent', 'non-marque');
    card.classList.add(type === 'Présent' ? 'present' : 'absent');

    const badgeContainer = card.querySelector('.absence-eleve-statut');
    if (badgeContainer) {
        badgeContainer.innerHTML = type === 'Présent' 
            ? '<span class="badge badge-success">Présent</span>' 
            : '<span class="badge badge-danger">Absent</span>';
    }

    if (type === 'Présent') {
        const motifDiv = card.querySelector('.absence-motif-rapide');
        if (motifDiv) motifDiv.style.display = 'none';
        const btnJustifier = card.querySelector('.btn-marquer.justifier');
        if (btnJustifier) btnJustifier.style.display = 'none';
    } else {
        let motifDiv = card.querySelector('.absence-motif-rapide');
        if (!motifDiv) {
            motifDiv = document.createElement('div');
            motifDiv.className = 'absence-motif-rapide';
            motifDiv.innerHTML = '<input type="text" class="form-control form-control-sm" placeholder="Motif (optionnel)" data-eleve-id="' + eleveId + '" onchange="sauvegarderMotif(' + eleveId + ', this.value)">';
            card.appendChild(motifDiv);
        } else {
            motifDiv.style.display = 'block';
        }

        let btnJustifier = card.querySelector('.btn-marquer.justifier');
        if (!btnJustifier) {
            btnJustifier = document.createElement('button');
            btnJustifier.type = 'button';
            btnJustifier.className = 'btn-marquer justifier';
            btnJustifier.title = 'Justifier l\'absence';
            btnJustifier.innerHTML = '<i class="fa-solid fa-file-medical"></i>';
            btnJustifier.onclick = function() { justifierAbsence(eleveId); };
            card.querySelector('.absence-eleve-actions').appendChild(btnJustifier);
        } else {
            btnJustifier.style.display = 'inline-flex';
        }
    }

    sauvegarderAbsence(eleveId, type);
    updateCompteurs();
}

async function sauvegarderAbsence(eleveId, type) {
    const formData = new FormData();
    formData.append('action', 'marquerAbsence');
    formData.append('eleve_id', eleveId);
    formData.append('classe_id', classeId);
    formData.append('date_absence', dateAbsence);
    formData.append('type_absence', type);

    try {
        const res = await fetch('/gestion_scolaire/absences/marquer', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (!data.success) {
            showToast(data.message, 'error');
        }
    } catch (e) {
        console.error('Erreur sauvegarde:', e);
    }
}

function sauvegarderMotif(eleveId, motif) {
    const formData = new FormData();
    formData.append('action', 'sauvegarderMotif');
    formData.append('eleve_id', eleveId);
    formData.append('date_absence', dateAbsence);
    formData.append('motif', motif);

    fetch('/gestion_scolaire/absences/marquer', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
}

function justifierAbsence(eleveId) {
    document.getElementById('justifier_eleve_id').value = eleveId;
    document.getElementById('modalJustifier').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModalJustifier() {
    document.getElementById('modalJustifier').classList.remove('show');
    document.body.style.overflow = '';
    document.getElementById('formJustifier').reset();
}

document.getElementById('modalJustifier').addEventListener('click', function(e) {
    if (e.target === this) closeModalJustifier();
});

document.getElementById('btnSaveJustification').addEventListener('click', async function() {
    const motif = document.getElementById('justifier_motif').value;
    if (!motif) { showToast('Veuillez sélectionner un motif.', 'warning'); return; }

    const formData = new FormData(document.getElementById('formJustifier'));
    formData.append('action', 'justifierAbsence');

    this.disabled = true;
    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';

    try {
        const res = await fetch('/gestion_scolaire/absences/marquer', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeModalJustifier();
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('Erreur de connexion.', 'error');
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer';
    }
});

document.getElementById('btnSauvegarderPointage').addEventListener('click', function() {
    const cards = document.querySelectorAll('.absence-eleve-card');
    let saved = 0;
    
    cards.forEach(card => {
        const eleveId = card.dataset.eleveId;
        if (card.classList.contains('present')) {
            sauvegarderAbsence(eleveId, 'Présent');
            saved++;
        } else if (card.classList.contains('absent')) {
            sauvegarderAbsence(eleveId, 'Absence');
            saved++;
        }
    });
    
    showToast(saved + ' élève(s) sauvegardé(s).', 'success');
});

document.getElementById('btnTousPresents').addEventListener('click', function() {
    document.querySelectorAll('.absence-eleve-card').forEach(card => {
        const btn = card.querySelector('.btn-marquer.present');
        if (btn) marquerEleve(card.dataset.eleveId, 'Présent', btn);
    });
    showToast('Tous les élèves sont marqués présents.', 'success');
});

document.getElementById('btnTousAbsents').addEventListener('click', function() {
    document.querySelectorAll('.absence-eleve-card').forEach(card => {
        const btn = card.querySelector('.btn-marquer.absent');
        if (btn) marquerEleve(card.dataset.eleveId, 'Absence', btn);
    });
    showToast('Tous les élèves sont marqués absents.', 'warning');
});

function updateCompteurs() {
    const cards = document.querySelectorAll('.absence-eleve-card');
    let presents = 0, absents = 0, nonMarques = 0;
    cards.forEach(card => {
        if (card.classList.contains('present')) presents++;
        else if (card.classList.contains('absent')) absents++;
        else nonMarques++;
    });
    document.querySelectorAll('.absence-info-item').forEach(item => {
        if (item.classList.contains('present')) {
            item.innerHTML = '<i class="fa-solid fa-check-circle"></i><span><strong>' + presents + '</strong> présent(s)</span>';
        } else if (item.classList.contains('absent')) {
            item.innerHTML = '<i class="fa-solid fa-times-circle"></i><span><strong>' + absents + '</strong> absent(s)</span>';
        } else if (item.textContent.includes('non marqué')) {
            item.innerHTML = '<i class="fa-solid fa-clock"></i><span><strong>' + nonMarques + '</strong> non marqué(s)</span>';
        }
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModalJustifier();
});
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';