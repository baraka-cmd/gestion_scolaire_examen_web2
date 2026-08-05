<?php
/**
 * Fichier : public/views/modules/parametres/ecole.php
 * Rôle    : Configuration des paramètres de l'école
 * Système : École primaire congolaise — Administration
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('parametres.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ParametreController.php';
$parametreController = new ParametreController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $parametreController->handleAjax();
}

// Récupérer les paramètres actuels
$params = $db->fetchOne("SELECT * FROM parametres_ecole WHERE id = 1");
if (!$params) {
    $params = [
        'nom_ecole'              => ECOLE_NOM,
        'devise'                 => ECOLE_DEVIS,
        'adresse'                => '',
        'telephone'              => '',
        'email'                  => '',
        'site_web'               => '',
        'logo'                   => '',
        'pays'                   => 'RÉPUBLIQUE DÉMOCRATIQUE DU CONGO',
        'ministere'              => "MINISTÈRE DE L'ENSEIGNEMENT PRIMAIRE, SECONDAIRE ET PROFESSIONNEL",
        'ville'                  => '',
        'commune_territoire'     => '',
        'code_etablissement'     => '',
        'annee_scolaire_active_id' => null,
        'trimestre_actif_id'     => null,
        'bareme_note_max'        => 20,
        'moyenne_passage'        => 10,
        'pourcentage_interro'    => 30,
        'pourcentage_devoir'     => 30,
        'pourcentage_examen'     => 40,
        'format_matricule'       => 'EL-{ANNEE}-{ID}',
    ];
}

// Récupérer les années et trimestres pour les sélecteurs
$annees = $db->fetchAll("SELECT * FROM annees_scolaires ORDER BY date_debut DESC");
$trimestres = $db->fetchAll(
    "SELECT * FROM trimestres WHERE annee_scolaire_id = :aid ORDER BY numero",
    ['aid' => $params['annee_scolaire_active_id'] ?? 0]
);

$pageTitle = 'Paramètres de l\'école';
$pageIcon  = 'fa-gear';
$pageDescription = 'Configurez les informations et les paramètres de l\'établissement';
$extraCSS = ['modules/parametres.css'];
$extraJS  = ['modules/parametres.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Paramètres</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-gear"></i> Paramètres de l'école</h4>
        <p>Configurez les informations et les paramètres généraux de l'établissement.</p>
    </div>
    <div class="resultats-header-right">
        <button type="button" class="btn btn-primary btn-lg" id="btnSauvegarderTout">
            <i class="fa-solid fa-save"></i> Sauvegarder tous les paramètres
        </button>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     SECTION 1 : IDENTITÉ DE L'ÉCOLE
     ============================================================ -->
<div class="parametres-card mb-4">
    <div class="parametres-card-header">
        <div class="parametres-card-icon blue">
            <i class="fa-solid fa-school"></i>
        </div>
        <div>
            <h5>Identité de l'école</h5>
            <p>Ces informations apparaissent sur les bulletins, reçus et documents officiels</p>
        </div>
    </div>
    <div class="parametres-card-body">
        <form id="formParametres" autocomplete="off">

            <!-- Logo -->
            <div class="form-section">
                <div class="form-section-header">
                    <div class="form-section-icon"><i class="fa-solid fa-image"></i></div>
                    <div>
                        <h6 class="form-section-title">Logo de l'école</h6>
                        <p class="form-section-subtitle">Apparaît sur les bulletins et documents officiels</p>
                    </div>
                </div>
                <div class="logo-upload-container">
                    <div class="logo-preview" id="logoPreview">
                        <?php if (!empty($params['logo']) && file_exists(UPLOADS_PATH . $params['logo'])): ?>
                            <img src="<?= ASSETS_URL . $params['logo'] ?>" alt="Logo" class="logo-image">
                        <?php else: ?>
                            <i class="fa-solid fa-school logo-placeholder"></i>
                        <?php endif; ?>
                    </div>
                    <div class="logo-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnChangerLogo">
                            <i class="fa-solid fa-upload"></i> Changer le logo
                        </button>
                        <input type="file" id="logoInput" accept="image/jpeg,image/png,image/gif" style="display:none;">
                        <small class="form-help">Format JPG, PNG ou GIF — Max 2 Mo</small>
                    </div>
                </div>
            </div>

            <hr class="form-divider">

            <div class="form-row">
                <div class="form-group">
                    <label for="nom_ecole" class="form-label">
                        <i class="fa-solid fa-building"></i> Nom de l'école <span class="required">*</span>
                    </label>
                    <input type="text" id="nom_ecole" name="nom_ecole" class="form-control" 
                           value="<?= e($params['nom_ecole']) ?>" required maxlength="150">
                </div>
                <div class="form-group">
                    <label for="devise" class="form-label">
                        <i class="fa-solid fa-quote-right"></i> Devise
                    </label>
                    <input type="text" id="devise" name="devise" class="form-control" 
                           value="<?= e($params['devise']) ?>" maxlength="255" placeholder="Ex: Discipline - Travail - Excellence">
                </div>
            </div>

            <div class="form-group">
                <label for="adresse" class="form-label">
                    <i class="fa-solid fa-location-dot"></i> Adresse complète
                </label>
                <input type="text" id="adresse" name="adresse" class="form-control" 
                       value="<?= e($params['adresse']) ?>" maxlength="255" placeholder="Ex: 123 Avenue de l'École, Quartier X">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telephone" class="form-label">
                        <i class="fa-solid fa-phone"></i> Téléphone
                    </label>
                    <input type="text" id="telephone" name="telephone" class="form-control" 
                           value="<?= e($params['telephone']) ?>" maxlength="20" placeholder="+243XXXXXXXXX">
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fa-solid fa-envelope"></i> Email
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= e($params['email']) ?>" maxlength="100" placeholder="ecole@exemple.com">
                </div>
                <div class="form-group">
                    <label for="site_web" class="form-label">
                        <i class="fa-solid fa-globe"></i> Site web
                    </label>
                    <input type="text" id="site_web" name="site_web" class="form-control" 
                           value="<?= e($params['site_web']) ?>" maxlength="150" placeholder="www.ecole-exemple.edu">
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 2 : BULLETIN OFFICIEL RDC
         ============================================================ -->
    <div class="parametres-card mb-4">
        <div class="parametres-card-header">
            <div class="parametres-card-icon green">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div>
                <h5>Bulletin officiel — En-tête RDC</h5>
                <p>Ces informations apparaissent dans l'en-tête des bulletins et proclamations</p>
            </div>
        </div>
        <div class="parametres-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="pays" class="form-label">
                        <i class="fa-solid fa-flag"></i> Pays
                    </label>
                    <input type="text" id="pays" name="pays" class="form-control" 
                           value="<?= e($params['pays']) ?>" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="ministere" class="form-label">
                        <i class="fa-solid fa-landmark"></i> Ministère
                    </label>
                    <input type="text" id="ministere" name="ministere" class="form-control" 
                           value="<?= e($params['ministere']) ?>" maxlength="255">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="ville" class="form-label">
                        <i class="fa-solid fa-city"></i> Ville
                    </label>
                    <input type="text" id="ville" name="ville" class="form-control" 
                           value="<?= e($params['ville']) ?>" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="commune_territoire" class="form-label">
                        <i class="fa-solid fa-map-pin"></i> Commune / Territoire
                    </label>
                    <input type="text" id="commune_territoire" name="commune_territoire" class="form-control" 
                           value="<?= e($params['commune_territoire']) ?>" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="code_etablissement" class="form-label">
                        <i class="fa-solid fa-hashtag"></i> Code établissement
                    </label>
                    <input type="text" id="code_etablissement" name="code_etablissement" class="form-control" 
                           value="<?= e($params['code_etablissement']) ?>" maxlength="50">
                </div>
            </div>
        </div>
    </div>

    
    
    <!-- ============================================================
         SECTION 4 : ANNÉE SCOLAIRE & TRIMESTRE ACTIFS
         ============================================================ -->
    <div class="parametres-card mb-4">
        <div class="parametres-card-header">
            <div class="parametres-card-icon cyan">
                <i class="fa-solid fa-calendar-alt"></i>
            </div>
            <div>
                <h5>Année scolaire & Trimestre actifs</h5>
                <p>Sélectionnez l'année scolaire et le trimestre en cours</p>
            </div>
        </div>
        <div class="parametres-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="annee_scolaire_active_id" class="form-label">
                        <i class="fa-solid fa-calendar"></i> Année scolaire active <span class="required">*</span>
                    </label>
                    <select id="annee_scolaire_active_id" name="annee_scolaire_active_id" class="form-control" onchange="chargerTrimestres()">
                        <option value="">— Sélectionnez —</option>
                        <?php foreach ($annees as $annee): ?>
                            <option value="<?= $annee['id'] ?>" <?= ($params['annee_scolaire_active_id'] ?? 0) == $annee['id'] ? 'selected' : '' ?>>
                                <?= e($annee['libelle']) ?> <?= $annee['est_active'] ? '(Actif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="trimestre_actif_id" class="form-label">
                        <i class="fa-solid fa-calendar-check"></i> Trimestre actif <span class="required">*</span>
                    </label>
                    <select id="trimestre_actif_id" name="trimestre_actif_id" class="form-control">
                        <option value="">— Sélectionnez —</option>
                        <?php foreach ($trimestres as $trimestre): ?>
                            <option value="<?= $trimestre['id'] ?>" <?= ($params['trimestre_actif_id'] ?? 0) == $trimestre['id'] ? 'selected' : '' ?>>
                                <?= e($trimestre['libelle']) ?> <?= $trimestre['est_actif'] ? '(Actif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 5 : FORMAT MATRICULE
         ============================================================ -->
    <div class="parametres-card mb-4">
        <div class="parametres-card-header">
            <div class="parametres-card-icon purple">
                <i class="fa-solid fa-barcode"></i>
            </div>
            <div>
                <h5>Format du matricule élève</h5>
                <p>Définissez le pattern de génération des matricules</p>
            </div>
        </div>
        <div class="parametres-card-body">
            <div class="form-group">
                <label for="format_matricule" class="form-label">
                    <i class="fa-solid fa-qrcode"></i> Format
                </label>
                <input type="text" id="format_matricule" name="format_matricule" class="form-control" 
                       value="<?= e($params['format_matricule']) ?>" maxlength="50">
                <small class="form-help">
                    <i class="fa-solid fa-info-circle"></i> 
                    Variables disponibles : <code>{ANNEE}</code> = année sur 2 chiffres, <code>{ID}</code> = numéro séquentiel.
                    Exemple : <strong>EL-{ANNEE}-{ID}</strong> donne <strong>EL-26-00001</strong>
                </small>
            </div>
        </div>
    </div>

    <!-- ============================================================
         BOUTON SAUVEGARDE
         ============================================================ -->
    <div class="d-flex justify-content-end gap-sm mb-4">
        <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()">
            <i class="fa-solid fa-rotate"></i> Réinitialiser
        </button>
        <button type="button" class="btn btn-primary btn-lg" id="btnSauvegarder">
            <i class="fa-solid fa-save"></i> Sauvegarder tous les paramètres
        </button>
    </div>
</div>

<script>
// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initPonderation();
    initLogoUpload();
    initSauvegarde();
});

// ============================================================
// PONDÉRATION — VÉRIFICATION EN TEMPS RÉEL
// ============================================================
function initPonderation() {
    var inputs = ['pourcentage_interro', 'pourcentage_devoir', 'pourcentage_examen'];
    inputs.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', verifierPonderation);
        }
    });
}

function verifierPonderation() {
    var interro = parseInt(document.getElementById('pourcentage_interro').value) || 0;
    var devoir  = parseInt(document.getElementById('pourcentage_devoir').value) || 0;
    var examen  = parseInt(document.getElementById('pourcentage_examen').value) || 0;
    var total   = interro + devoir + examen;

    document.getElementById('ponderationSomme').textContent = 'Total : ' + total + '%';

    var ok = document.getElementById('ponderationOk');
    var erreur = document.getElementById('ponderationErreur');
    var totalDiv = document.getElementById('ponderationTotal');

    if (total === 100) {
        ok.style.display = 'inline';
        erreur.style.display = 'none';
        totalDiv.style.background = '#d1fae5';
        totalDiv.style.color = '#065f46';
    } else {
        ok.style.display = 'none';
        erreur.style.display = 'inline';
        totalDiv.style.background = '#fee2e2';
        totalDiv.style.color = '#991b1b';
    }
}

// ============================================================
// UPLOAD DU LOGO
// ============================================================
function initLogoUpload() {
    var btnChanger = document.getElementById('btnChangerLogo');
    var inputLogo = document.getElementById('logoInput');

    if (btnChanger && inputLogo) {
        btnChanger.addEventListener('click', function() {
            inputLogo.click();
        });

        inputLogo.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var fichier = this.files[0];
                var extension = fichier.name.split('.').pop().toLowerCase();

                if (['jpg', 'jpeg', 'png', 'gif'].indexOf(extension) === -1) {
                    showToast('Format non autorisé. Utilisez JPG, PNG ou GIF.', 'error');
                    return;
                }

                if (fichier.size > 2 * 1024 * 1024) {
                    showToast('Le logo ne doit pas dépasser 2 Mo.', 'error');
                    return;
                }

                // Afficher l'aperçu
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('logoPreview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Logo" class="logo-image">';
                };
                reader.readAsDataURL(fichier);

                // Uploader
                var formData = new FormData();
                formData.append('action', 'uploadLogo');
                formData.append('logo', fichier);

                fetch('/gestion_scolaire/parametres', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        showToast('Logo mis à jour avec succès.', 'success');
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(function() {
                    showToast('Erreur lors de l\'upload du logo.', 'error');
                });
            }
        });
    }
}

// ============================================================
// CHARGER LES TRIMESTRES SELON L'ANNÉE SÉLECTIONNÉE
// ============================================================
function chargerTrimestres() {
    var anneeId = document.getElementById('annee_scolaire_active_id').value;
    var selectTrimestre = document.getElementById('trimestre_actif_id');

    if (!anneeId || !selectTrimestre) return;

    selectTrimestre.innerHTML = '<option value="">Chargement...</option>';
    selectTrimestre.disabled = true;

    var formData = new FormData();
    formData.append('action', 'getTrimestres');
    formData.append('annee_id', anneeId);

    fetch('/gestion_scolaire/parametres', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        selectTrimestre.innerHTML = '<option value="">— Sélectionnez —</option>';
        if (data.success && data.trimestres.length > 0) {
            data.trimestres.forEach(function(t) {
                var option = document.createElement('option');
                option.value = t.id;
                option.textContent = t.libelle + (t.est_actif ? ' (Actif)' : '');
                if (t.est_actif) option.selected = true;
                selectTrimestre.appendChild(option);
            });
        }
    })
    .catch(function() {
        selectTrimestre.innerHTML = '<option value="">Erreur de chargement</option>';
    })
    .finally(function() {
        selectTrimestre.disabled = false;
    });
}

// ============================================================
// SAUVEGARDE DES PARAMÈTRES
// ============================================================
function initSauvegarde() {
    var boutons = ['btnSauvegarder', 'btnSauvegarderTout'];
    boutons.forEach(function(id) {
        var btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', sauvegarderParametres);
        }
    });
}

function sauvegarderParametres() {
    // Vérifier la pondération
    var interro = parseInt(document.getElementById('pourcentage_interro').value) || 0;
    var devoir  = parseInt(document.getElementById('pourcentage_devoir').value) || 0;
    var examen  = parseInt(document.getElementById('pourcentage_examen').value) || 0;
    var total   = interro + devoir + examen;

    if (total !== 100) {
        showToast('La somme des pourcentages doit être égale à 100% (actuellement ' + total + '%).', 'error');
        return;
    }

    // Vérifier les champs obligatoires
    var nomEcole = document.getElementById('nom_ecole').value.trim();
    var anneeId = document.getElementById('annee_scolaire_active_id').value;
    var trimestreId = document.getElementById('trimestre_actif_id').value;

    if (!nomEcole) { showToast('Le nom de l\'école est obligatoire.', 'warning'); return; }
    if (!anneeId) { showToast('Veuillez sélectionner une année scolaire active.', 'warning'); return; }
    if (!trimestreId) { showToast('Veuillez sélectionner un trimestre actif.', 'warning'); return; }

    var formData = new FormData(document.getElementById('formParametres'));
    formData.append('action', 'sauvegarder');

    var btn = document.activeElement;
    if (btn && btn.tagName === 'BUTTON') {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';
    }

    fetch('/gestion_scolaire/parametres', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message, 'success');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarder tous les paramètres';
            }
        } else {
            showToast(data.message, 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarder tous les paramètres';
            }
        }
    })
    .catch(function() {
        showToast('Erreur de connexion.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarder tous les paramètres';
        }
    });
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>