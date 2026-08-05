<?php
/**
 * Fichier : public/views/modules/documents/upload.php
 * Rôle    : Formulaire d'upload de documents (simple + multiple)
 * Système : École primaire congolaise — Gestion documentaire
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('documents.creer');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'DocumentController.php';
$documentController = new DocumentController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $documentController->handleAjax();
}

// Statistiques
$stats = $documentController->getStatsDocuments();
$documentsRecents = $documentController->getDocumentsRecents(5);

// Catégories disponibles
$categories = [
    'Administratif' => '📁 Administratif — Règlements, PV, listes officielles',
    'Pédagogique'   => '📚 Pédagogique — Cours, exercices, évaluations',
    'Financier'     => '💰 Financier — Reçus, factures, rapports financiers',
    'Communication' => '📢 Communication — Courriers, circulaires, notes aux parents',
    'Autre'         => '📎 Autre — Documents divers'
];

$pageTitle = 'Uploader des documents';
$pageIcon  = 'fa-cloud-arrow-up';
$pageDescription = 'Ajoutez des documents à la bibliothèque de l\'école';
$extraCSS = ['modules/documents.css'];
$extraJS  = ['modules/documents.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/documents">Documents</a>
    <span class="separator">/</span>
    <span class="current">Uploader</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-cloud-arrow-up"></i> Uploader des documents</h4>
        <p>Ajoutez un ou plusieurs fichiers à la bibliothèque documentaire de l'école.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/documents" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Retour à la liste
        </a>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     FORMULAIRE D'UPLOAD
     ============================================================ -->
<div class="upload-form-grid">
    <div class="upload-form-card">
        <div class="upload-form-header">
            <div class="upload-form-icon">
                <i class="fa-solid fa-cloud-arrow-up"></i>
            </div>
            <div>
                <h5>Ajouter des fichiers</h5>
                <p>Sélectionnez les fichiers à uploader et remplissez les informations</p>
            </div>
        </div>
        <div class="upload-form-body">
            <form id="formUpload" enctype="multipart/form-data" autocomplete="off">

                <!-- ============================================================
                     ZONE DE DÉPÔT (DRAG & DROP)
                     ============================================================ -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-file"></i></div>
                        <div>
                            <h6 class="form-section-title">Fichiers à uploader</h6>
                            <p class="form-section-subtitle">Glissez-déposez vos fichiers ou cliquez pour les sélectionner</p>
                        </div>
                    </div>

                    <!-- Zone de drop -->
                    <div class="upload-dropzone" id="uploadDropzone">
                        <input type="file" id="fichiers" name="fichiers[]" class="upload-input-hidden" 
                               multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.gif,.ppt,.pptx">
                        <div class="upload-dropzone-content">
                            <div class="upload-dropzone-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <h6>Glissez-déposez vos fichiers ici</h6>
                            <p>ou <span class="upload-browse-link">parcourez votre ordinateur</span></p>
                            <small class="upload-formats">
                                Formats acceptés : PDF, Word, Excel, CSV, TXT, JPG, PNG, GIF, PPT — Max 10 Mo par fichier
                            </small>
                        </div>
                    </div>

                    <!-- Liste des fichiers sélectionnés -->
                    <div class="upload-files-list" id="uploadFilesList" style="display:none;">
                        <div class="upload-files-list-header">
                            <span><strong id="uploadFilesCount">0</strong> fichier(s) sélectionné(s)</span>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="viderFichiers()">
                                <i class="fa-solid fa-trash"></i> Tout supprimer
                            </button>
                        </div>
                        <div class="upload-files-items" id="uploadFilesItems"></div>
                    </div>
                </div>

                <hr class="form-divider">

                <!-- ============================================================
                     INFORMATIONS DU DOCUMENT
                     ============================================================ -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-circle-info"></i></div>
                        <div>
                            <h6 class="form-section-title">Informations</h6>
                            <p class="form-section-subtitle">Ces informations seront appliquées à tous les fichiers uploadés</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="categorie" class="form-label">
                                <i class="fa-solid fa-tag"></i> Catégorie <span class="required">*</span>
                            </label>
                            <select id="categorie" name="categorie" class="form-control" required>
                                <option value="">— Sélectionnez une catégorie —</option>
                                <?php foreach ($categories as $valeur => $libelle): ?>
                                    <option value="<?= $valeur ?>"><?= $libelle ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="est_public" class="form-label">
                                <i class="fa-solid fa-eye"></i> Visibilité
                            </label>
                            <select id="est_public" name="est_public" class="form-control">
                                <option value="1">🌍 Public — Visible par tous les utilisateurs</option>
                                <option value="0">🔒 Restreint — Visible uniquement par Admin et Directeur</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">
                            <i class="fa-solid fa-align-left"></i> Description
                        </label>
                        <textarea id="description" name="description" class="form-control" rows="2" 
                                  placeholder="Description du document (optionnelle)..."></textarea>
                        <small class="form-help">
                            <i class="fa-solid fa-info-circle"></i> Une description aide les autres utilisateurs à comprendre le contenu du document.
                        </small>
                    </div>
                </div>

                <!-- ============================================================
                     PROGRESSION DE L'UPLOAD
                     ============================================================ -->
                <div class="upload-progress-container" id="uploadProgressContainer" style="display:none;">
                    <div class="upload-progress-header">
                        <span><i class="fa-solid fa-spinner fa-spin"></i> Upload en cours...</span>
                        <span id="uploadProgressText">0%</span>
                    </div>
                    <div class="upload-progress-bar">
                        <div class="upload-progress-fill" id="uploadProgressFill"></div>
                    </div>
                </div>

                <!-- ============================================================
                     RÉSULTAT DE L'UPLOAD
                     ============================================================ -->
                <div class="upload-result-container" id="uploadResultContainer" style="display:none;"></div>
            </form>
        </div>
        <div class="upload-form-footer">
            <a href="/gestion_scolaire/documents" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="button" class="btn btn-primary btn-lg" id="btnUpload" disabled>
                <i class="fa-solid fa-cloud-arrow-up"></i> Uploader les fichiers
            </button>
        </div>
    </div>

    <!-- ============================================================
         GUIDES ET INFORMATIONS
         ============================================================ -->
    <div>
        <!-- Derniers documents uploadés -->
        <div class="info-card-custom">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon blue"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h6>Derniers documents uploadés</h6>
            </div>
            <div class="info-card-custom-body">
                <?php if (empty($documentsRecents)): ?>
                    <p style="font-size:0.82rem;color:var(--color-gray-500);">Aucun document pour le moment.</p>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($documentsRecents as $doc): 
                            $icone = $documentController->getIconeFichier($doc['type_fichier']);
                        ?>
                            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                                <i class="fa-solid <?= $icone['icon'] ?>" style="color:<?= $icone['color'] ?>;font-size:1.1rem;"></i>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:0.8rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($doc['nom_original']) ?></div>
                                    <small class="text-muted"><?= e($doc['date_format']) ?> — <?= e($doc['upload_par_nom']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Guide -->
        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon orange"><i class="fa-solid fa-circle-info"></i></div>
                <h6>Guide d'upload</h6>
            </div>
            <div class="info-card-custom-body">
                <ul class="info-list">
                    <li><strong>Formats acceptés :</strong> PDF, Word (.doc, .docx), Excel (.xls, .xlsx), CSV, TXT, JPG, PNG, GIF, PowerPoint (.ppt, .pptx).</li>
                    <li><strong>Taille maximale :</strong> 10 Mo par fichier.</li>
                    <li><strong>Upload multiple :</strong> Vous pouvez sélectionner plusieurs fichiers à la fois.</li>
                    <li><strong>Catégorie :</strong> Tous les fichiers uploadés ensemble auront la même catégorie et description.</li>
                    <li><strong>Visibilité :</strong> Les documents publics sont visibles par tous les utilisateurs. Les documents restreints sont réservés à l'administration.</li>
                    <li><strong>Sécurité :</strong> Les types de fichiers sont vérifiés pour éviter tout contenu malveillant.</li>
                </ul>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon purple"><i class="fa-solid fa-chart-simple"></i></div>
                <h6>Bibliothèque</h6>
            </div>
            <div class="info-card-custom-body">
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.82rem;color:#64748b;">Total documents</span>
                        <strong><?= $stats['total'] ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.82rem;color:#64748b;">Espace utilisé</span>
                        <strong><?= $stats['taille_totale'] ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.82rem;color:#64748b;">Documents publics</span>
                        <strong><?= $stats['nb_publics'] ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.82rem;color:#64748b;">Ajoutés ce mois</span>
                        <strong><?= $stats['nb_ce_mois'] ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
// ============================================================
// VARIABLES
// ============================================================
var fichiersSelectionnes = [];
var uploadEnCours = false;

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initDragAndDrop();
    initFileInput();
    initUploadButton();
});

// ============================================================
// DRAG & DROP
// ============================================================
function initDragAndDrop() {
    var dropzone = document.getElementById('uploadDropzone');
    if (!dropzone) return;

    // Empêcher le comportement par défaut du navigateur
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    // Survol
    ['dragenter', 'dragover'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function() {
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function() {
            dropzone.classList.remove('dragover');
        });
    });

    // Dépôt
    dropzone.addEventListener('drop', function(e) {
        var fichiers = e.dataTransfer.files;
        if (fichiers.length > 0) {
            ajouterFichiers(fichiers);
        }
    });

    // Clic pour ouvrir le sélecteur
    dropzone.addEventListener('click', function() {
        document.getElementById('fichiers').click();
    });
}

// ============================================================
// INPUT FILE
// ============================================================
function initFileInput() {
    var input = document.getElementById('fichiers');
    if (!input) return;

    input.addEventListener('change', function() {
        if (this.files.length > 0) {
            ajouterFichiers(this.files);
        }
    });
}

// ============================================================
// AJOUTER DES FICHIERS À LA LISTE
// ============================================================
function ajouterFichiers(fichiers) {
    var maxSize = 10 * 1024 * 1024; // 10 Mo
    var allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'ppt', 'pptx'];
    var ajoutes = 0;
    var ignores = 0;

    for (var i = 0; i < fichiers.length; i++) {
        var fichier = fichiers[i];
        var extension = fichier.name.split('.').pop().toLowerCase();

        // Vérifier l'extension
        if (allowedExtensions.indexOf(extension) === -1) {
            showToast('Format .' + extension + ' non autorisé : ' + fichier.name, 'warning');
            ignores++;
            continue;
        }

        // Vérifier la taille
        if (fichier.size > maxSize) {
            showToast('Fichier trop volumineux (max 10 Mo) : ' + fichier.name, 'warning');
            ignores++;
            continue;
        }

        // Vérifier les doublons
        var doublon = fichiersSelectionnes.some(function(f) {
            return f.name === fichier.name && f.size === fichier.size;
        });

        if (doublon) {
            ignores++;
            continue;
        }

        fichiersSelectionnes.push(fichier);
        ajoutes++;
    }

    afficherFichiersSelectionnes();
    
    if (ajoutes > 0) {
        showToast(ajoutes + ' fichier(s) ajouté(s)' + (ignores > 0 ? ', ' + ignores + ' ignoré(s)' : '') + '.', 'success');
    }
}

// ============================================================
// AFFICHER LES FICHIERS SÉLECTIONNÉS
// ============================================================
function afficherFichiersSelectionnes() {
    var container = document.getElementById('uploadFilesList');
    var itemsContainer = document.getElementById('uploadFilesItems');
    var countSpan = document.getElementById('uploadFilesCount');
    var btnUpload = document.getElementById('btnUpload');

    if (fichiersSelectionnes.length === 0) {
        container.style.display = 'none';
        btnUpload.disabled = true;
        return;
    }

    container.style.display = 'block';
    countSpan.textContent = fichiersSelectionnes.length;
    btnUpload.disabled = false;

    var tailleTotale = fichiersSelectionnes.reduce(function(total, f) { return total + f.size; }, 0);
    var tailleFormatee = tailleTotale > 1048576 
        ? (tailleTotale / 1048576).toFixed(1) + ' Mo' 
        : (tailleTotale / 1024).toFixed(1) + ' Ko';

    var iconesFichiers = {
        'pdf':  { icon: 'fa-file-pdf',  color: '#ef4444' },
        'doc':  { icon: 'fa-file-word',  color: '#3b82f6' },
        'docx': { icon: 'fa-file-word',  color: '#3b82f6' },
        'xls':  { icon: 'fa-file-excel', color: '#10b981' },
        'xlsx': { icon: 'fa-file-excel', color: '#10b981' },
        'csv':  { icon: 'fa-file-csv',   color: '#10b981' },
        'ppt':  { icon: 'fa-file-powerpoint', color: '#f59e0b' },
        'pptx': { icon: 'fa-file-powerpoint', color: '#f59e0b' },
        'jpg':  { icon: 'fa-file-image', color: '#8b5cf6' },
        'jpeg': { icon: 'fa-file-image', color: '#8b5cf6' },
        'png':  { icon: 'fa-file-image', color: '#8b5cf6' },
        'gif':  { icon: 'fa-file-image', color: '#8b5cf6' },
        'txt':  { icon: 'fa-file-lines', color: '#64748b' }
    };

    itemsContainer.innerHTML = fichiersSelectionnes.map(function(fichier, index) {
        var extension = fichier.name.split('.').pop().toLowerCase();
        var icone = iconesFichiers[extension] || { icon: 'fa-file', color: '#64748b' };
        var taille = fichier.size > 1048576 
            ? (fichier.size / 1048576).toFixed(1) + ' Mo' 
            : (fichier.size / 1024).toFixed(1) + ' Ko';

        return `
        <div class="upload-file-item">
            <i class="fa-solid ${icone.icon}" style="color:${icone.color};font-size:1.2rem;"></i>
            <div class="upload-file-info">
                <span class="upload-file-name">${escapeHtml(fichier.name)}</span>
                <span class="upload-file-size">${taille}</span>
            </div>
            <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" onclick="retirerFichier(${index})" title="Retirer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>`;
    }).join('');

    // Ajouter le total
    var totalDiv = document.createElement('div');
    totalDiv.className = 'upload-files-total';
    totalDiv.innerHTML = '<span>Poids total : <strong>' + tailleFormatee + '</strong></span>';
    
    var oldTotal = itemsContainer.querySelector('.upload-files-total');
    if (oldTotal) oldTotal.remove();
    itemsContainer.appendChild(totalDiv);
}

// ============================================================
// RETIRER UN FICHIER
// ============================================================
function retirerFichier(index) {
    fichiersSelectionnes.splice(index, 1);
    afficherFichiersSelectionnes();
    
    if (fichiersSelectionnes.length === 0) {
        document.getElementById('uploadFilesList').style.display = 'none';
        document.getElementById('btnUpload').disabled = true;
    }
}

// ============================================================
// VIDER TOUS LES FICHIERS
// ============================================================
function viderFichiers() {
    fichiersSelectionnes = [];
    afficherFichiersSelectionnes();
    document.getElementById('uploadFilesList').style.display = 'none';
    document.getElementById('btnUpload').disabled = true;
    document.getElementById('fichiers').value = '';
}

// ============================================================
// BOUTON UPLOAD
// ============================================================
function initUploadButton() {
    var btn = document.getElementById('btnUpload');
    if (!btn) return;

    btn.addEventListener('click', function() {
        if (uploadEnCours) return;
        if (fichiersSelectionnes.length === 0) {
            showToast('Veuillez sélectionner au moins un fichier.', 'warning');
            return;
        }

        var categorie = document.getElementById('categorie').value;
        if (!categorie) {
            showToast('Veuillez sélectionner une catégorie.', 'warning');
            return;
        }

        uploaderFichiers();
    });
}

// ============================================================
// UPLOADER LES FICHIERS
// ============================================================
function uploaderFichiers() {
    uploadEnCours = true;

    var formData = new FormData();
    formData.append('action', 'upload');
    formData.append('categorie', document.getElementById('categorie').value);
    formData.append('description', document.getElementById('description').value);
    formData.append('est_public', document.getElementById('est_public').value);

    // Ajouter tous les fichiers
    fichiersSelectionnes.forEach(function(fichier) {
        formData.append('fichiers[]', fichier);
    });

    // Afficher la progression
    var progressContainer = document.getElementById('uploadProgressContainer');
    var progressFill = document.getElementById('uploadProgressFill');
    var progressText = document.getElementById('uploadProgressText');
    var resultContainer = document.getElementById('uploadResultContainer');
    var btn = document.getElementById('btnUpload');

    progressContainer.style.display = 'block';
    resultContainer.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Upload en cours...';

    // Simuler une progression (XMLHttpRequest pour avoir le vrai progress)
    var xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            var pct = Math.round((e.loaded / e.total) * 100);
            progressFill.style.width = pct + '%';
            progressText.textContent = pct + '%';
        }
    });

    xhr.addEventListener('load', function() {
        uploadEnCours = false;
        progressContainer.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Uploader les fichiers';

        try {
            var data = JSON.parse(xhr.responseText);
            afficherResultatUpload(data);
        } catch (e) {
            showToast('Erreur lors du traitement de la réponse.', 'error');
        }
    });

    xhr.addEventListener('error', function() {
        uploadEnCours = false;
        progressContainer.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Uploader les fichiers';
        showToast('Erreur de connexion lors de l\'upload.', 'error');
    });

    xhr.open('POST', '/gestion_scolaire/documents');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(formData);
}

// ============================================================
// AFFICHER LE RÉSULTAT DE L'UPLOAD
// ============================================================
function afficherResultatUpload(data) {
    var resultContainer = document.getElementById('uploadResultContainer');
    resultContainer.style.display = 'block';

    if (data.success) {
        var documentsHtml = '';
        if (data.documents && data.documents.length > 0) {
            documentsHtml = data.documents.map(function(doc) {
                return '<div style="display:flex;align-items:center;gap:8px;padding:4px 0;">' +
                    '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i>' +
                    '<span>' + escapeHtml(doc.nom_original) + ' (' + escapeHtml(doc.taille) + ')</span>' +
                '</div>';
            }).join('');
        }

        resultContainer.innerHTML = `
            <div class="upload-result-success">
                <i class="fa-solid fa-circle-check"></i>
                <h5>Upload réussi !</h5>
                <p>${data.message}</p>
                ${documentsHtml}
                ${data.erreurs && data.erreurs.length > 0 ? '<div class="upload-result-erreurs" style="margin-top:8px;">' + data.erreurs.map(function(e) { return '<p style="color:#ef4444;font-size:0.78rem;"><i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(e) + '</p>'; }).join('') + '</div>' : ''}
            </div>`;

        // Vider la sélection
        fichiersSelectionnes = [];
        document.getElementById('uploadFilesList').style.display = 'none';
        document.getElementById('fichiers').value = '';
        document.getElementById('description').value = '';

        showToast(data.message, 'success');

        // Bouton pour retourner à la liste
        var btnUpload = document.getElementById('btnUpload');
        btnUpload.style.display = 'none';
        
        var footer = document.querySelector('.upload-form-footer');
        if (footer) {
            var btnRetour = document.createElement('a');
            btnRetour.href = '/gestion_scolaire/documents';
            btnRetour.className = 'btn btn-primary btn-lg';
            btnRetour.innerHTML = '<i class="fa-solid fa-list"></i> Voir tous les documents';
            footer.appendChild(btnRetour);
        }

    } else {
        resultContainer.innerHTML = `
            <div class="upload-result-error">
                <i class="fa-solid fa-circle-xmark"></i>
                <h5>Échec de l'upload</h5>
                <p>${data.message}</p>
                ${data.erreurs && data.erreurs.length > 0 ? '<div style="margin-top:8px;">' + data.erreurs.map(function(e) { return '<p style="color:#ef4444;font-size:0.78rem;"><i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(e) + '</p>'; }).join('') + '</div>' : ''}
            </div>`;
    }
}

// ============================================================
// UTILITAIRES
// ============================================================
function escapeHtml(texte) {
    if (!texte) return '';
    var div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>