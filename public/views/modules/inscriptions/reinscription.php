<?php
/**
 * Fichier : public/views/modules/inscriptions/reinscription.php
 * Rôle    : Réinscription massive des élèves par classe
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('inscriptions.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'InscriptionController.php';
$inscriptionController = new InscriptionController();

// Récupérer les années scolaires
$annees = $inscriptionController->getAnneesScolaires();

// Récupérer les classes de toutes les années pour le sélecteur source
$classesToutes = $db->fetchAll(
    "SELECT c.id, c.nom, c.niveau, a.libelle AS annee_libelle, a.id AS annee_id, a.est_active
     FROM classes c
     INNER JOIN annees_scolaires a ON c.annee_scolaire_id = a.id
     ORDER BY a.date_debut DESC, c.niveau"
);

// Classes de l'année active pour la destination
$classesDestination = $db->fetchAll(
    "SELECT c.id, c.nom, c.niveau, c.capacite_max,
            (SELECT COUNT(*) FROM inscriptions WHERE classe_id = c.id AND statut = 'Actif') AS nb_inscrits
     FROM classes c
     WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
     ORDER BY c.niveau"
);

$pageTitle = 'Réinscription massive';
$pageIcon  = 'fa-rotate';
$pageDescription = 'Réinscrire les élèves pour la nouvelle année scolaire';
$extraCSS = ['modules/inscriptions.css', 'modules/reinscription.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/inscriptions">Inscriptions</a>
    <span class="separator">/</span>
    <span class="current">Réinscription massive</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-rotate text-primary me-2"></i>Réinscription massive
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Réinscrivez les élèves d'une classe dans une nouvelle classe pour l'année suivante.
        </p>
    </div>
    <a href="/gestion_scolaire/inscriptions" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Retour à la liste
    </a>
</div>

<!-- ============================================================
     CARTE DE RÉINSCRIPTION
     ============================================================ -->
<div class="reinscription-card">
    <div class="reinscription-header">
        <div class="reinscription-icon">
            <i class="fa-solid fa-rotate"></i>
        </div>
        <div>
            <h5>Réinscription des élèves</h5>
            <p>Sélectionnez la classe source et la classe de destination</p>
        </div>
    </div>
    <div class="reinscription-body">

        <!-- Sélecteurs Source → Destination -->
        <div class="reinscription-selectors">
            <div class="form-group">
                <label for="classe_source_id" class="form-label">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Classe source
                </label>
                <select id="classe_source_id" class="form-control" onchange="loadElevesReinscription()">
                    <option value="">— Sélectionnez une classe —</option>
                    <?php foreach ($classesToutes as $classe): ?>
                        <option value="<?= $classe['id'] ?>" 
                                data-annee="<?= $classe['annee_id'] ?>" 
                                data-niveau="<?= $classe['niveau'] ?>"
                                data-nom="<?= e($classe['nom']) ?>">
                            <?= e($classe['nom']) ?> — <?= e($classe['annee_libelle']) ?>
                            <?= $classe['est_active'] ? '(Active)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="reinscription-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>
            <div class="form-group">
                <label for="classe_destination_id" class="form-label">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Classe de destination
                </label>
                <select id="classe_destination_id" class="form-control">
                    <option value="">— Sélectionnez une classe —</option>
                    <?php foreach ($classesDestination as $classe): ?>
                        <option value="<?= $classe['id'] ?>" data-niveau="<?= $classe['niveau'] ?>">
                            <?= e($classe['nom']) ?> — Niv. <?= $classe['niveau'] ?> 
                            (<?= $classe['nb_inscrits'] ?>/<?= $classe['capacite_max'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Résumé -->
        <div class="reinscription-summary" id="reinscriptionSummary" style="display:none;">
            <div class="reinscription-summary-item total">
                <i class="fa-solid fa-users"></i>
                <span>Total : <strong id="summaryTotal">0</strong></span>
            </div>
            <div class="reinscription-summary-item passe">
                <i class="fa-solid fa-check-circle"></i>
                <span>Passent : <strong id="summaryPasse">0</strong></span>
            </div>
            <div class="reinscription-summary-item redouble">
                <i class="fa-solid fa-rotate"></i>
                <span>Redoublent : <strong id="summaryRedouble">0</strong></span>
            </div>
            <div class="reinscription-summary-item exclu">
                <i class="fa-solid fa-ban"></i>
                <span>Exclus : <strong id="summaryExclu">0</strong></span>
            </div>
        </div>

        <!-- Liste des élèves (chargée dynamiquement) -->
        <div id="elevesContainer">
            <div class="empty-state" style="padding:40px;">
                <i class="fa-solid fa-rotate"></i>
                <p>Sélectionnez une classe source pour afficher les élèves.</p>
            </div>
        </div>

        <!-- Actions -->
        <div id="reinscriptionActions" style="display:none;margin-top:20px;justify-content:flex-end;gap:10px;">
            <label class="select-all-wrapper" style="margin-right:auto;">
                <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes(this)">
                <span class="select-all-custom"><i class="fa-solid fa-check"></i></span>
                <span class="select-all-label">Tout sélectionner</span>
            </label>
            <button type="button" class="btn btn-outline-primary" onclick="setAllPasse()">
                <i class="fa-solid fa-check-double"></i> Tous : Passe
            </button>
            <button type="button" class="btn btn-primary btn-lg" id="btnReinscrire" onclick="executerReinscription()">
                <i class="fa-solid fa-rotate"></i> Réinscrire les élèves sélectionnés
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     GUIDE
     ============================================================ -->
<div class="guide-card">
    <div class="guide-card-header">
        <div class="guide-card-icon blue">
            <i class="fa-solid fa-circle-info"></i>
        </div>
        <h6>Fonctionnement de la réinscription</h6>
    </div>
    <div class="guide-card-body">
        <ul class="guide-list">
            <li>Sélectionnez la <strong>classe source</strong> (année précédente ou actuelle).</li>
            <li>La <strong>classe de destination</strong> est automatiquement suggérée (niveau supérieur).</li>
            <li>Par défaut, <strong>tous les élèves passent</strong> en classe supérieure.</li>
            <li>Seuls les élèves avec <strong>moyenne &lt; 10/20</strong> sont proposés en <strong>Redouble</strong>.</li>
            <li>Utilisez le bouton <strong>"Tous : Passe"</strong> pour réinitialiser toutes les décisions.</li>
            <li>Les élèves marqués <strong>"Exclu"</strong> ne seront pas réinscrits.</li>
            <li>L'<strong>historique</strong> des inscriptions précédentes est conservé.</li>
        </ul>
    </div>
</div>

<script>
// ============================================================
// CHARGEMENT DES ÉLÈVES POUR RÉINSCRIPTION
// ============================================================
function loadElevesReinscription() {
    const classeSourceId = document.getElementById('classe_source_id').value;
    if (!classeSourceId) {
        document.getElementById('elevesContainer').innerHTML = '<div class="empty-state" style="padding:40px;"><i class="fa-solid fa-rotate"></i><p>Sélectionnez une classe source pour afficher les élèves.</p></div>';
        document.getElementById('reinscriptionSummary').style.display = 'none';
        document.getElementById('reinscriptionActions').style.display = 'none';
        return;
    }

    const selectedOption = document.getElementById('classe_source_id').selectedOptions[0];
    const anneeId = selectedOption.dataset.annee || '';
    const niveauSource = parseInt(selectedOption.dataset.niveau || 0);

    // Auto-sélectionner la classe de destination (niveau supérieur)
    if (niveauSource > 0 && niveauSource < 6) {
        const destSelect = document.getElementById('classe_destination_id');
        for (let opt of destSelect.options) {
            if (parseInt(opt.dataset.niveau) === niveauSource + 1) {
                destSelect.value = opt.value;
                break;
            }
        }
    }

    const formData = new FormData();
    formData.append('action', 'getElevesPourReinscription');
    formData.append('classe_source_id', classeSourceId);
    formData.append('annee_source_id', anneeId);

    document.getElementById('elevesContainer').innerHTML = '<div style="text-align:center;padding:30px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;color:var(--color-primary);"></i><p style="margin-top:10px;color:var(--color-gray-500);">Chargement des élèves...</p></div>';

    fetch('/gestion_scolaire/inscriptions', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderElevesReinscription(data);
            document.getElementById('reinscriptionSummary').style.display = 'flex';
            document.getElementById('reinscriptionActions').style.display = 'flex';
        } else {
            document.getElementById('elevesContainer').innerHTML = '<div class="empty-state" style="padding:40px;"><i class="fa-solid fa-exclamation-circle"></i><p>' + data.message + '</p></div>';
        }
    })
    .catch(err => {
        console.error('Erreur:', err);
        document.getElementById('elevesContainer').innerHTML = '<div class="empty-state" style="padding:40px;"><i class="fa-solid fa-exclamation-circle"></i><p>Erreur de chargement. Vérifiez la console.</p></div>';
    });
}

function renderElevesReinscription(data) {
    const container = document.getElementById('elevesContainer');
    
    if (!data.eleves || data.eleves.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding:40px;"><i class="fa-solid fa-users"></i><p>Aucun élève trouvé dans cette classe.</p></div>';
        document.getElementById('reinscriptionSummary').style.display = 'none';
        document.getElementById('reinscriptionActions').style.display = 'none';
        return;
    }

    let html = '';
    data.eleves.forEach(e => {
        const moyenne = parseFloat(e.moyenne_annuelle || 0);
        // Par défaut : Passe (sauf si moyenne < 10)
        const decisionDefaut = (moyenne > 0 && moyenne < 10) ? 'Redouble' : 'Passe';
        const moyenneClass = moyenne >= 10 ? 'reussite' : (moyenne > 0 ? 'echec' : '');
        const rowClass = decisionDefaut === 'Passe' ? 'passe' : 'redouble';

        html += `
            <div class="eleve-reinscription-row ${rowClass}" data-inscription-id="${e.inscription_id || ''}">
                <input type="checkbox" class="eleve-checkbox eleve-reinscription-check" value="${e.id}" checked onchange="updateSummary()">
                <div class="eleve-reinscription-info">
                    <div class="eleve-reinscription-name">${escapeHtml(e.prenom)} ${escapeHtml(e.nom)}</div>
                    <div class="eleve-reinscription-matricule">${escapeHtml(e.matricule)} — ${e.sexe === 'M' ? 'Garçon' : 'Fille'}</div>
                </div>
                <div class="eleve-reinscription-moyenne ${moyenneClass}">
                    ${moyenne > 0 
                        ? '<i class="fa-solid fa-' + (moyenne >= 10 ? 'arrow-up' : 'arrow-down') + '"></i> ' + moyenne.toFixed(1) + '/20'
                        : '<span style="color:var(--color-gray-400);">—</span>'}
                </div>
                <div class="eleve-reinscription-decision">
                    <select class="decision-select" onchange="updateRowStyle(this); updateSummary();">
                        <option value="Passe" ${decisionDefaut === 'Passe' ? 'selected' : ''}>✅ Passe</option>
                        <option value="Redouble" ${decisionDefaut === 'Redouble' ? 'selected' : ''}>🔄 Redouble</option>
                        <option value="Exclu">❌ Exclu</option>
                    </select>
                </div>
                <div class="eleve-reinscription-destination">
                    <i class="fa-solid fa-arrow-right"></i> 
                    <span class="destination-text">Classe supérieure</span>
                </div>
            </div>`;
    });

    container.innerHTML = html;
    updateSummary();
}

// ============================================================
// FONCTIONS UTILITAIRES
// ============================================================

function updateRowStyle(select) {
    const row = select.closest('.eleve-reinscription-row');
    if (row) {
        row.classList.remove('passe', 'redouble', 'exclu');
        const val = select.value;
        if (val === 'Passe') row.classList.add('passe');
        else if (val === 'Redouble') row.classList.add('redouble');
        else if (val === 'Exclu') row.classList.add('exclu');
    }
}

function updateSummary() {
    let total = 0, passe = 0, redouble = 0, exclu = 0;
    
    document.querySelectorAll('.eleve-checkbox:checked').forEach(cb => {
        total++;
        const row = cb.closest('.eleve-reinscription-row');
        const select = row?.querySelector('.decision-select');
        if (select) {
            const val = select.value;
            if (val === 'Passe') passe++;
            else if (val === 'Redouble') redouble++;
            else if (val === 'Exclu') exclu++;
        }
    });

    document.getElementById('summaryTotal').textContent = total;
    document.getElementById('summaryPasse').textContent = passe;
    document.getElementById('summaryRedouble').textContent = redouble;
    document.getElementById('summaryExclu').textContent = exclu;
}

function toggleAllCheckboxes(checkbox) {
    document.querySelectorAll('.eleve-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSummary();
}

function setAllPasse() {
    document.querySelectorAll('.decision-select').forEach(select => {
        select.value = 'Passe';
        updateRowStyle(select);
    });
    document.querySelectorAll('.eleve-checkbox').forEach(cb => {
        cb.checked = true;
    });
    updateSummary();
    showToast('Tous les élèves sont définis sur "Passe".', 'success');
}

// ============================================================
// EXÉCUTION DE LA RÉINSCRIPTION
// ============================================================
async function executerReinscription() {
    const destinationId = document.getElementById('classe_destination_id').value;
    if (!destinationId) {
        showToast('Veuillez sélectionner une classe de destination.', 'warning');
        return;
    }

    const selected = [];
    document.querySelectorAll('.eleve-checkbox:checked').forEach(cb => {
        const row = cb.closest('.eleve-reinscription-row');
        selected.push({
            eleve_id: cb.value,
            nouvelle_classe_id: destinationId,
            decision: row.querySelector('.decision-select')?.value || 'Passe',
            ancienne_inscription_id: row.dataset.inscriptionId || ''
        });
    });

    if (selected.length === 0) {
        showToast('Veuillez sélectionner au moins un élève.', 'warning');
        return;
    }

    if (!confirm(`Voulez-vous réinscrire ${selected.length} élève(s) ?`)) return;

    const btn = document.getElementById('btnReinscrire');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Réinscription en cours...';

    const formData = new FormData();
    formData.append('action', 'reinscrireMassivement');
    formData.append('eleves', JSON.stringify(selected));

    try {
        const res = await fetch('/gestion_scolaire/inscriptions', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (err) {
        alert('Erreur de connexion.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Réinscrire les élèves sélectionnés';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type) {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>