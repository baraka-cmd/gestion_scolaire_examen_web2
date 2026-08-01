<?php
/**
 * Fichier : public/views/modules/retards/enregistrer.php
 * Rôle    : Interface d'enregistrement des retards
 * Système : École primaire congolaise — Gestion des retards
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('retards.enregistrer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'RetardController.php';
$retardController = new RetardController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $retardController->handleAjax();
}

// Récupérer les classes
$classes = $retardController->getClassesActives();

// Paramètres
$classeId = (int) ($_GET['classe_id'] ?? 0);
$dateRetard = $_GET['date'] ?? date('Y-m-d');

// Si enseignant, récupérer automatiquement sa classe
if (hasRole('Enseignant') && $classeId <= 0) {
    $classeId = $retardController->getClasseEnseignant();
}

// Récupérer les infos de la classe et les élèves
$classe = null;
$eleves = [];
$retardsExistants = [];

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

    // Récupérer les retards déjà enregistrés pour cette date
    $retardsExistants = $db->fetchAll(
        "SELECT r.eleve_id, r.heure_arrivee, r.duree_retard, r.motif, r.est_justifie,
                TIME_FORMAT(r.heure_arrivee, '%H:%i') AS heure_arrivee_format
         FROM retards r
         WHERE r.eleve_id IN (SELECT eleve_id FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif')
         AND r.date_retard = :date",
        ['cid' => $classeId, 'date' => $dateRetard]
    );

    $retardsMap = [];
    foreach ($retardsExistants as $r) {
        $retardsMap[$r['eleve_id']] = $r;
    }
}

// Statistiques du jour
$totalEleves = count($eleves);
$totalRetards = count($retardsExistants);
$retardsJustifies = count(array_filter($retardsExistants, fn($r) => $r['est_justifie'] == 1));

$pageTitle = 'Enregistrer les retards';
$pageIcon  = 'fa-clock';
$pageDescription = $classe ? 'Retards du ' . formatDate($dateRetard, 'l j F Y') . ' — ' . e($classe['nom']) : 'Enregistrez les retards des élèves';
$extraCSS = ['modules/absences.css', 'modules/retards.css'];
$extraJS  = ['modules/absences.js', 'modules/retards.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/retards">Retards</a>
    <span class="separator">/</span>
    <span class="current">Enregistrer</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-clock"></i> Enregistrer les retards</h4>
        <p>Enregistrez les retards des élèves — <?= $classe ? e($classe['nom']) : 'Sélectionnez une classe' ?></p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/retards" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Liste des retards
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
                        <input type="date" name="date" class="form-control" value="<?= $dateRetard ?>" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($classeId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-clock"></i>
        <p>Sélectionnez une classe pour enregistrer les retards.</p>
    </div>

<?php elseif (empty($eleves)): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-users-slash"></i>
        <p>Aucun élève inscrit dans cette classe.</p>
    </div>

<?php else: ?>
    <!-- BARRE D'INFO -->
    <div class="retard-info-bar">
        <div class="retard-info-item">
            <i class="fa-solid fa-calendar"></i>
            <span><strong><?= formatDate($dateRetard, 'l j F Y') ?></strong></span>
        </div>
        <div class="retard-info-item">
            <i class="fa-solid fa-users"></i>
            <span><strong><?= $totalEleves ?></strong> élève(s)</span>
        </div>
        <div class="retard-info-item">
            <i class="fa-solid fa-clock"></i>
            <span><strong><?= $totalRetards ?></strong> retard(s) enregistré(s)</span>
        </div>
        <div class="retard-info-item">
            <i class="fa-solid fa-check-circle"></i>
            <span><strong><?= $retardsJustifies ?></strong> justifié(s)</span>
        </div>
    </div>

    <!-- LISTE DES ÉLÈVES -->
    <div class="retard-list-card">
        <div class="retard-list-header">
            <h5><i class="fa-solid fa-users"></i> Élèves de la classe</h5>
            <span class="text-muted" style="font-size:0.78rem;">Cliquez sur <i class="fa-solid fa-clock"></i> pour enregistrer un retard</span>
        </div>

        <div class="table-responsive">
            <table class="table" id="tableRetards">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Élève</th>
                        <th>Sexe</th>
                        <th>Statut</th>
                        <th>Heure arrivée</th>
                        <th>Durée retard</th>
                        <th>Motif</th>
                        <th>Justifié</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eleves as $eleve): 
                        $retard = $retardsMap[$eleve['id']] ?? null;
                        $aUnRetard = $retard !== null;
                    ?>
                        <tr class="retard-row <?= $aUnRetard ? 'has-retard' : '' ?> <?= $aUnRetard && $retard['est_justifie'] ? 'justifie' : '' ?>"
                            data-eleve-id="<?= $eleve['id'] ?>">
                            <td><span class="badge badge-primary"><?= e($eleve['matricule']) ?></span></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-placeholder <?= $eleve['sexe'] === 'M' ? 'blue' : 'purple' ?>" style="width:34px;height:34px;font-size:0.7rem;">
                                        <?= mb_strtoupper(mb_substr($eleve['prenom'], 0, 1) . mb_substr($eleve['nom'], 0, 1)) ?>
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= $eleve['sexe'] === 'M' ? 'M' : 'F' ?></td>
                            <td>
                                <?php if ($aUnRetard): ?>
                                    <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> En retard</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> À l'heure</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($aUnRetard): ?>
                                    <strong><?= e($retard['heure_arrivee_format']) ?></strong>
                                <?php else: ?>
                                    <span style="color:var(--color-gray-400);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($aUnRetard): ?>
                                    <span class="badge <?= $retard['duree_retard'] > 30 ? 'badge-danger' : 'badge-warning' ?>">
                                        <?= $retard['duree_retard'] ?> min
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--color-gray-400);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($aUnRetard && !empty($retard['motif'])): ?>
                                    <?= e($retard['motif']) ?>
                                <?php else: ?>
                                    <span style="color:var(--color-gray-400);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($aUnRetard): ?>
                                    <?php if ($retard['est_justifie']): ?>
                                        <span class="badge badge-info"><i class="fa-solid fa-check"></i> Oui</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Non</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--color-gray-400);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-xs">
                                    <?php if (!$aUnRetard): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-enregistrer-retard"
                                                onclick="ouvrirModalRetard(<?= $eleve['id'] ?>, '<?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?>')"
                                                title="Enregistrer un retard">
                                            <i class="fa-solid fa-clock"></i> Retard
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-modifier-retard"
                                                onclick="ouvrirModalRetard(<?= $eleve['id'] ?>, '<?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?>', '<?= $retard['heure_arrivee'] ?>', '<?= e($retard['motif'] ?? '') ?>')"
                                                title="Modifier le retard">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php if (!$retard['est_justifie']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info btn-justifier-retard"
                                                    onclick="ouvrirModalJustifier(<?= $eleve['id'] ?>)"
                                                    title="Justifier ce retard">
                                                <i class="fa-solid fa-file-medical"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-retard"
                                                onclick="supprimerRetard(<?= $eleve['id'] ?>)"
                                                title="Supprimer ce retard">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- ============================================================
     MODALE : ENREGISTRER/MODIFIER UN RETARD
     ============================================================ -->
<div class="modal-overlay" id="modalRetard">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h5><i class="fa-solid fa-clock"></i> <span id="modalRetardTitre">Enregistrer un retard</span></h5>
            <button type="button" class="modal-close" onclick="closeModalRetard()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formRetard" autocomplete="off">
                <input type="hidden" id="retard_eleve_id" name="eleve_id" value="">
                <input type="hidden" name="date_retard" value="<?= $dateRetard ?>">
                <input type="hidden" name="heure_prevue" value="07:30">

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-user-graduate"></i> Élève</label>
                    <input type="text" id="retard_eleve_nom" class="form-control" readonly style="background:#f1f5f9;">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-clock"></i> Heure d'arrivée <span class="required">*</span></label>
                        <input type="time" id="retard_heure_arrivee" name="heure_arrivee" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-door-open"></i> Heure prévue</label>
                        <input type="time" id="retard_heure_prevue" name="heure_prevue" class="form-control" value="07:30">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-align-left"></i> Motif du retard</label>
                    <select id="retard_motif_select" class="form-control" onchange="remplirMotifRetard(this.value)">
                        <option value="">— Sélectionnez un motif —</option>
                        <option value="Réveil tardif">Réveil tardif</option>
                        <option value="Transport">Problème de transport</option>
                        <option value="Intempéries">Intempéries / Pluie</option>
                        <option value="Maladie">Raison de santé</option>
                        <option value="Obligation familiale">Obligation familiale</option>
                        <option value="Autre">Autre raison</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-pen"></i> Commentaire</label>
                    <textarea id="retard_motif" name="motif" class="form-control" rows="2" placeholder="Détails supplémentaires sur le retard..."></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="est_justifie" id="retard_est_justifie" value="1">
                        <span class="checkbox-custom"><i class="fa-solid fa-check"></i></span>
                        <span class="checkbox-label">Marquer comme justifié</span>
                    </label>
                </div>

                <!-- Alerte nombre de retards -->
                <div id="retardAlerte" style="display:none;margin-top:12px;"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" onclick="closeModalRetard()"><i class="fa-solid fa-xmark"></i> Annuler</button>
            <button type="button" class="btn btn-primary" id="btnSaveRetard"><i class="fa-solid fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODALE : JUSTIFIER UN RETARD
     ============================================================ -->
<div class="modal-overlay" id="modalJustifierRetard">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h5><i class="fa-solid fa-file-medical"></i> Justifier un retard</h5>
            <button type="button" class="modal-close" onclick="closeModalJustifierRetard()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formJustifierRetard" autocomplete="off">
                <input type="hidden" id="justifier_retard_eleve_id" name="eleve_id" value="">

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-tag"></i> Motif de justification</label>
                    <select name="motif" class="form-control" required>
                        <option value="">— Sélectionnez un motif —</option>
                        <option value="Maladie">Maladie</option>
                        <option value="Transport">Problème de transport</option>
                        <option value="Intempéries">Intempéries</option>
                        <option value="Obligation familiale">Obligation familiale</option>
                        <option value="Autre">Autre raison</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" onclick="closeModalJustifierRetard()"><i class="fa-solid fa-xmark"></i> Annuler</button>
            <button type="button" class="btn btn-primary" id="btnSaveJustificationRetard"><i class="fa-solid fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>

<script>
var dateRetard = '<?= $dateRetard ?>';
var classeId = <?= $classeId ?>;

// Ouvrir le modal pour enregistrer un retard
function ouvrirModalRetard(eleveId, eleveNom, heureArrivee, motif) {
    document.getElementById('retard_eleve_id').value = eleveId;
    document.getElementById('retard_eleve_nom').value = eleveNom;
    document.getElementById('modalRetardTitre').textContent = heureArrivee ? 'Modifier le retard' : 'Enregistrer un retard';

    if (heureArrivee) {
        document.getElementById('retard_heure_arrivee').value = heureArrivee.substring(0,5);
    } else {
        document.getElementById('retard_heure_arrivee').value = '';
    }
    document.getElementById('retard_motif').value = motif || '';
    document.getElementById('retard_motif_select').value = '';
    document.getElementById('retard_est_justifie').checked = false;
    document.getElementById('retardAlerte').style.display = 'none';

    document.getElementById('modalRetard').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModalRetard() {
    document.getElementById('modalRetard').classList.remove('show');
    document.body.style.overflow = '';
}

// Ouvrir le modal pour justifier
function ouvrirModalJustifier(eleveId) {
    document.getElementById('justifier_retard_eleve_id').value = eleveId;
    document.getElementById('modalJustifierRetard').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModalJustifierRetard() {
    document.getElementById('modalJustifierRetard').classList.remove('show');
    document.body.style.overflow = '';
}

// Remplir le motif depuis le select
function remplirMotifRetard(valeur) {
    if (valeur && valeur !== 'Autre') {
        document.getElementById('retard_motif').value = valeur;
    }
}

// Sauvegarder le retard
document.getElementById('btnSaveRetard').addEventListener('click', async function() {
    const eleveId = document.getElementById('retard_eleve_id').value;
    const heureArrivee = document.getElementById('retard_heure_arrivee').value;

    if (!eleveId || !heureArrivee) {
        showToast('Veuillez remplir tous les champs obligatoires.', 'warning');
        return;
    }

    const formData = new FormData(document.getElementById('formRetard'));
    formData.append('action', 'enregistrerRetard');

    this.disabled = true;
    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';

    try {
        const res = await fetch('/gestion_scolaire/retards', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.success) {
            let msg = data.message;
            if (data.alerte) msg += ' ' + data.alerte;
            showToast(msg, 'success');
            closeModalRetard();
            setTimeout(() => location.reload(), 800);
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

// Sauvegarder la justification
document.getElementById('btnSaveJustificationRetard').addEventListener('click', async function() {
    const eleveId = document.getElementById('justifier_retard_eleve_id').value;
    const motif = document.querySelector('#formJustifierRetard select').value;

    if (!motif) {
        showToast('Veuillez sélectionner un motif.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'justifierRetard');
    formData.append('eleve_id', eleveId);
    formData.append('date_retard', dateRetard);
    formData.append('motif', motif);

    this.disabled = true;
    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';

    try {
        const res = await fetch('/gestion_scolaire/retards', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeModalJustifierRetard();
            setTimeout(() => location.reload(), 800);
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

// Supprimer un retard
async function supprimerRetard(eleveId) {
    showConfirmDialog(
        'Voulez-vous supprimer ce retard ?',
        async function(confirmed) {
            if (confirmed) {
                const formData = new FormData();
                formData.append('action', 'supprimerRetard');
                formData.append('eleve_id', eleveId);
                formData.append('date_retard', dateRetard);

                try {
                    const res = await fetch('/gestion_scolaire/retards', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 600);
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (e) {
                    showToast('Erreur de connexion.', 'error');
                }
            }
        },
        { title: 'Supprimer le retard', confirmText: 'Supprimer', confirmClass: 'btn-danger', icon: 'fa-trash', iconColor: '#ef4444' }
    );
}

// Fermer les modales au clic extérieur
['modalRetard', 'modalJustifierRetard'].forEach(id => {
    const modal = document.getElementById(id);
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                if (id === 'modalRetard') closeModalRetard();
                else closeModalJustifierRetard();
            }
        });
    }
});

// Raccourci Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModalRetard();
        closeModalJustifierRetard();
    }
});
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';