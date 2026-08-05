<?php
/**
 * Fichier : public/views/comptable/modules/enregistrer.php
 * Rôle    : Formulaire d'enregistrement d'un paiement — Vue Comptable
 * Système : École primaire congolaise — Gestion financière
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('paiements.enregistrer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'PaiementController.php';
$paiementController = new PaiementController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $paiementController->handleAjax();
}

// Récupérer les données pour les formulaires
$classes = $paiementController->getClassesActives();
$fraisActifs = $paiementController->getFraisActifs();

// Paramètres
$eleveId = (int) ($_GET['eleve_id'] ?? 0);
$fraisId = (int) ($_GET['frais_id'] ?? 0);

// Si un élève est présélectionné, récupérer ses infos
$elevePreselectionne = null;
if ($eleveId > 0) {
    $elevePreselectionne = $db->fetchOne(
        "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
                c.nom AS classe_nom
         FROM eleves e
         INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
         INNER JOIN classes c ON i.classe_id = c.id
         WHERE e.id = :eid AND e.est_actif = 1",
        ['eid' => $eleveId]
    );
}

$pageTitle = 'Enregistrer un paiement';
$pageIcon  = 'fa-money-bill-wave';
$pageDescription = 'Enregistrez un nouveau paiement pour un élève';
$extraCSS = ['modules/paiements.css'];
$extraJS  = ['modules/paiements.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/comptable/paiements">Paiements</a>
    <span class="separator">/</span>
    <span class="current">Enregistrer</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-money-bill-wave"></i> Enregistrer un paiement</h4>
        <p>Enregistrez un nouveau paiement pour un élève.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/comptable/paiements" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Liste des paiements
        </a>
        <a href="/gestion_scolaire/comptable/impayes" class="btn btn-outline-warning btn-sm">
            <i class="fa-solid fa-exclamation-circle"></i> Voir les impayés
        </a>
    </div>
</div>

<div id="flashMessage"></div>

<div class="paiement-form-grid">
    <!-- ============================================================
         FORMULAIRE
         ============================================================ -->
    <div class="paiement-form-card">
        <div class="paiement-form-header">
            <div class="paiement-form-icon">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <div>
                <h5>Nouveau paiement</h5>
                <p>Remplissez les informations pour enregistrer un paiement</p>
            </div>
        </div>
        <div class="paiement-form-body">
            <form id="formPaiement" autocomplete="off">
                <input type="hidden" id="eleve_id" name="eleve_id" value="<?= $eleveId ?>">

                <!-- Étape 1 : Rechercher l'élève -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-search"></i></div>
                        <div>
                            <h6 class="form-section-title">Élève</h6>
                            <p class="form-section-subtitle">Recherchez l'élève concerné par ce paiement</p>
                        </div>
                    </div>

                    <?php if ($elevePreselectionne): ?>
                        <div class="eleve-trouve-card">
                            <div class="eleve-trouve-avatar <?= $elevePreselectionne['sexe'] === 'M' ? 'male' : 'female' ?>">
                                <?= mb_strtoupper(mb_substr($elevePreselectionne['prenom'], 0, 1) . mb_substr($elevePreselectionne['nom'], 0, 1)) ?>
                            </div>
                            <div class="eleve-trouve-info">
                                <strong><?= e($elevePreselectionne['prenom'] . ' ' . $elevePreselectionne['nom']) ?></strong>
                                <span><?= e($elevePreselectionne['matricule']) ?> — <?= e($elevePreselectionne['classe_nom']) ?></span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="changerEleve()">
                                <i class="fa-solid fa-rotate"></i> Changer
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="recherche-eleve-container">
                            <div class="search-wrapper">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="rechercheEleve" class="form-control" 
                                       placeholder="Rechercher par nom, prénom ou matricule..."
                                       autocomplete="off">
                            </div>
                            <div id="resultatsRecherche" class="resultats-recherche"></div>
                            <small class="form-help">
                                <i class="fa-solid fa-info-circle"></i> Tapez au moins 2 caractères pour lancer la recherche
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="form-divider">

                <!-- Étape 2 : Type de frais -->
                <div class="form-section" id="sectionFrais" style="<?= $elevePreselectionne ? '' : 'display:none;' ?>">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-tags"></i></div>
                        <div>
                            <h6 class="form-section-title">Type de frais</h6>
                            <p class="form-section-subtitle">Sélectionnez le type de frais à payer</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="frais_scolaire_id" class="form-label">
                            <i class="fa-solid fa-tag"></i> Frais <span class="required">*</span>
                        </label>
                        <select id="frais_scolaire_id" name="frais_scolaire_id" class="form-control" onchange="mettreAJourMontant()">
                            <option value="">— Sélectionnez un type de frais —</option>
                            <?php foreach ($fraisActifs as $frais): ?>
                                <option value="<?= $frais['id'] ?>" 
                                        data-montant="<?= $frais['montant'] ?>"
                                        <?= $fraisId === (int) $frais['id'] ? 'selected' : '' ?>>
                                    <?= e($frais['libelle']) ?> — <strong>$ <?= number_format($frais['montant'], 2, ',', ' ') ?></strong>
                                    <?= $frais['est_obligatoire'] ? '(Obligatoire)' : '(Optionnel)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="paiement-montant-attendu" id="montantAttendu" style="display:none;">
                        <div class="montant-attendu-label">Montant attendu</div>
                        <div class="montant-attendu-valeur" id="montantAttenduValeur">$ 0,00</div>
                    </div>
                </div>

                <!-- Étape 3 : Montant et mode -->
                <div class="form-section" id="sectionMontant" style="display:none;">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-calculator"></i></div>
                        <div>
                            <h6 class="form-section-title">Paiement</h6>
                            <p class="form-section-subtitle">Saisissez le montant payé et le mode de paiement</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="montant_paye" class="form-label">
                                <i class="fa-solid fa-money-bill"></i> Montant payé <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">$</span>
                                <input type="number" id="montant_paye" name="montant_paye" class="form-control" 
                                       placeholder="0,00" min="1" step="0.01" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="mode_paiement" class="form-label">
                                <i class="fa-solid fa-credit-card"></i> Mode de paiement <span class="required">*</span>
                            </label>
                            <select id="mode_paiement" name="mode_paiement" class="form-control" required>
                                <option value="Espèces">Espèces</option>
                                <option value="Mobile Money">Mobile Money</option>
                                <option value="Chèque">Chèque</option>
                                <option value="Virement bancaire">Virement bancaire</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reference_paiement" class="form-label">
                            <i class="fa-solid fa-hashtag"></i> Référence / N° transaction
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon"><i class="fa-solid fa-barcode"></i></span>
                            <input type="text" id="reference_paiement" name="reference_paiement" class="form-control" 
                                   placeholder="Ex: N° chèque, référence mobile money...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="commentaire" class="form-label">
                            <i class="fa-solid fa-comment"></i> Commentaire
                        </label>
                        <textarea id="commentaire" name="commentaire" class="form-control" rows="2" 
                                  placeholder="Note ou observation sur ce paiement..."></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="paiement-form-footer">
            <a href="/gestion_scolaire/comptable/paiements" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="button" class="btn btn-primary btn-lg" id="btnEnregistrerPaiement" style="display:none;">
                <i class="fa-solid fa-save"></i> Enregistrer le paiement
            </button>
        </div>
    </div>

    <!-- ============================================================
         RÉCAPITULATIF
         ============================================================ -->
    <div>
        <div class="info-card-custom" id="historiqueEleve" style="display:none;">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon blue"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h6>Historique des paiements</h6>
            </div>
            <div class="info-card-custom-body" id="historiqueEleveContent">
                <p style="font-size:0.82rem;color:var(--color-gray-500);">Sélectionnez un élève pour voir son historique.</p>
            </div>
        </div>

        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon orange"><i class="fa-solid fa-circle-info"></i></div>
                <h6>Guide</h6>
            </div>
            <div class="info-card-custom-body">
                <ul class="info-list">
                    <li>Recherchez l'<strong>élève</strong> par son nom, prénom ou matricule.</li>
                    <li>Sélectionnez le <strong>type de frais</strong> à payer.</li>
                    <li>Le <strong>montant attendu</strong> s'affiche automatiquement.</li>
                    <li>Saisissez le <strong>montant payé</strong> (peut être inférieur au montant attendu pour un paiement partiel).</li>
                    <li>Un <strong>reçu</strong> sera généré automatiquement après l'enregistrement.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- MODALE REÇU -->
<div class="modal-overlay" id="modalRecu">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h5><i class="fa-solid fa-receipt"></i> Paiement enregistré</h5>
            <button type="button" class="modal-close" onclick="closeModalRecu()">&times;</button>
        </div>
        <div class="modal-body" id="modalRecuContent"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" onclick="closeModalRecu()">
                <i class="fa-solid fa-xmark"></i> Fermer
            </button>
            <a href="#" class="btn btn-primary" id="btnVoirRecu" target="_blank">
                <i class="fa-solid fa-print"></i> Voir / Imprimer le reçu
            </a>
        </div>
    </div>
</div>

<script>
var eleveSelectionne = <?= $eleveId > 0 ? $eleveId : 'null' ?>;
let searchTimeout;

document.getElementById('rechercheEleve')?.addEventListener('keyup', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    if (query.length < 2) {
        document.getElementById('resultatsRecherche').innerHTML = '';
        document.getElementById('resultatsRecherche').classList.remove('show');
        return;
    }
    searchTimeout = setTimeout(() => {
        const formData = new FormData();
        formData.append('action', 'rechercherEleve');
        formData.append('recherche', query);
        fetch('/gestion_scolaire/paiements', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('resultatsRecherche');
            if (data.success && data.eleves.length > 0) {
                container.innerHTML = data.eleves.map(e => `
                    <div class="resultat-eleve-item" onclick="selectionnerEleve(${e.id}, '${escapeHtml(e.prenom + ' ' + e.nom)}', '${escapeHtml(e.matricule)}', '${escapeHtml(e.classe_nom)}')">
                        <div class="resultat-eleve-avatar ${e.sexe === 'M' ? 'male' : 'female'}">${(e.prenom.charAt(0) + e.nom.charAt(0)).toUpperCase()}</div>
                        <div class="resultat-eleve-info"><strong>${escapeHtml(e.prenom + ' ' + e.nom)}</strong><span>${escapeHtml(e.matricule)} — ${escapeHtml(e.classe_nom)}</span></div>
                    </div>`).join('');
                container.classList.add('show');
            } else {
                container.innerHTML = '<p style="padding:12px;color:var(--color-gray-500);">Aucun élève trouvé.</p>';
                container.classList.add('show');
            }
        });
    }, 300);
});

document.addEventListener('click', function(e) {
    const container = document.getElementById('resultatsRecherche');
    const input = document.getElementById('rechercheEleve');
    if (container && input && !container.contains(e.target) && e.target !== input) container.classList.remove('show');
});

function selectionnerEleve(id, nom, matricule, classe) {
    document.getElementById('eleve_id').value = id;
    document.getElementById('rechercheEleve').value = nom + ' (' + matricule + ')';
    document.getElementById('resultatsRecherche').classList.remove('show');
    document.getElementById('sectionFrais').style.display = 'block';
    document.getElementById('historiqueEleve').style.display = 'block';
    chargerHistoriqueEleve(id, nom);
    document.getElementById('frais_scolaire_id').value = '';
    document.getElementById('montantAttendu').style.display = 'none';
    document.getElementById('sectionMontant').style.display = 'none';
    document.getElementById('btnEnregistrerPaiement').style.display = 'none';
}

function changerEleve() {
    ['eleve_id','rechercheEleve'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    ['sectionFrais','sectionMontant','historiqueEleve','btnEnregistrerPaiement'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
    location.href = '/gestion_scolaire/comptable/enregistrer';
}

function mettreAJourMontant() {
    const select = document.getElementById('frais_scolaire_id');
    const option = select.selectedOptions[0];
    if (option && option.dataset.montant) {
        const montant = parseFloat(option.dataset.montant);
        document.getElementById('montantAttenduValeur').textContent = '$ ' + montant.toLocaleString('fr-FR', {minimumFractionDigits: 2});
        document.getElementById('montantAttendu').style.display = 'block';
        document.getElementById('sectionMontant').style.display = 'block';
        document.getElementById('btnEnregistrerPaiement').style.display = 'inline-flex';
        document.getElementById('montant_paye').value = montant;
        document.getElementById('montant_paye').setAttribute('max', montant);
    }
}

function chargerHistoriqueEleve(eleveId, nom) {
    const container = document.getElementById('historiqueEleveContent');
    const formData = new FormData();
    formData.append('action', 'getListePaiements');
    formData.append('recherche', nom);
    formData.append('page', 1);
    fetch('/gestion_scolaire/paiements', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.paiements.length > 0) {
            container.innerHTML = data.paiements.map(p => `
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <div><span style="font-size:0.82rem;">${escapeHtml(p.frais_libelle)}</span><br><small class="text-muted">${p.date_format} — ${escapeHtml(p.mode_paiement)}</small></div>
                    <div><span class="badge ${p.statut === 'Payé' ? 'badge-success' : (p.statut === 'Partiel' ? 'badge-warning' : 'badge-danger')}">${p.statut}</span><strong style="margin-left:8px;">$ ${parseFloat(p.montant_paye).toLocaleString()}</strong></div>
                </div>`).join('');
        } else {
            container.innerHTML = '<p style="font-size:0.82rem;color:var(--color-gray-500);">Aucun paiement trouvé pour cet élève.</p>';
        }
    });
}

function closeModalRecu() {
    document.getElementById('modalRecu').classList.remove('show');
    document.body.style.overflow = '';
}
document.getElementById('modalRecu').addEventListener('click', function(e) { if (e.target === this) closeModalRecu(); });

function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text || ''; return div.innerHTML; }

<?php if ($elevePreselectionne): ?>
document.getElementById('sectionFrais').style.display = 'block';
document.getElementById('historiqueEleve').style.display = 'block';
chargerHistoriqueEleve(<?= $eleveId ?>, '<?= e($elevePreselectionne['prenom'] . ' ' . $elevePreselectionne['nom']) ?>');
<?php endif; ?>
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>