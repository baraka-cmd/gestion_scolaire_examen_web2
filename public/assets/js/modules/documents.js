/**
 * Fichier : public/assets/js/modules/documents.js
 * Rôle    : Scripts de la gestion documentaire
 * Système : École primaire congolaise — Documents
 * Version : 1.0
 */

'use strict';

// ============================================================
// VARIABLES GLOBALES
// ============================================================
var fichiersSelectionnes = [];
var uploadEnCours = false;
var searchTimeout;

// Icônes par type de fichier
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

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Page liste
    if (document.getElementById('tbodyDocuments')) {
        chargerDocuments(1);
    }

    // Page upload
    if (document.getElementById('uploadDropzone')) {
        initDragAndDrop();
        initFileInput();
        initUploadButton();
        initRaccourcisClavier();
    }
});

// ============================================================
// 1. LISTE DES DOCUMENTS — CHARGEMENT
// ============================================================
function chargerDocuments(page) {
    var tbody = document.getElementById('tbodyDocuments');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="10"><div class="empty-state" style="padding:30px;"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>';

    var formData = new FormData();
    formData.append('action', 'getListe');
    formData.append('page', page || 1);

    var categorie = document.getElementById('filterCategorie')?.value || '';
    var visibilite = document.getElementById('filterVisibilite')?.value || '';
    var recherche = document.getElementById('searchDocuments')?.value || '';

    if (categorie) formData.append('categorie', categorie);
    if (visibilite !== '') formData.append('visibilite', visibilite);
    if (recherche) formData.append('recherche', recherche);

    fetch('/gestion_scolaire/documents', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            afficherDocuments(data);
        } else {
            showToast(data.message || 'Erreur de chargement.', 'error');
        }
    })
    .catch(function() {
        tbody.innerHTML = '<tr><td colspan="10"><div class="empty-state" style="padding:30px;"><i class="fa-solid fa-circle-exclamation"></i><p>Erreur de connexion.</p></div></td></tr>';
    });
}

// ============================================================
// 2. LISTE DES DOCUMENTS — AFFICHAGE
// ============================================================
function afficherDocuments(data) {
    var tbody = document.getElementById('tbodyDocuments');
    var totalSpan = document.getElementById('totalDocuments');
    var paginationDiv = document.getElementById('paginationDocuments');
    var paginationInfo = document.getElementById('paginationInfo');
    var isAdmin = document.querySelector('[data-admin]') !== null || true;

    if (totalSpan) totalSpan.textContent = data.total;

    if (!data.documents || data.documents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10"><div class="empty-state" style="padding:40px;"><i class="fa-solid fa-folder-open"></i><p>Aucun document trouvé.</p></div></td></tr>';
        if (paginationDiv) paginationDiv.innerHTML = '';
        if (paginationInfo) paginationInfo.textContent = '';
        return;
    }

    tbody.innerHTML = data.documents.map(function(doc) {
        var icone = iconesFichiers[doc.type_fichier] || { icon: 'fa-file', color: '#64748b' };
        var visibiliteBadge = doc.est_public == 1
            ? '<span class="badge badge-success"><i class="fa-solid fa-earth-americas"></i> Public</span>'
            : '<span class="badge badge-warning"><i class="fa-solid fa-lock"></i> Restreint</span>';

        return `
        <tr>
            <td><i class="fa-solid ${icone.icon}" style="font-size:1.3rem;color:${icone.color};"></i></td>
            <td>
                <a href="/gestion_scolaire/documents/telecharger/${doc.id}" class="document-nom" title="Télécharger ${escapeHtml(doc.nom_original)}">
                    <strong>${escapeHtml(doc.nom_original)}</strong>
                </a>
                ${doc.description ? '<br><small class="text-muted">' + escapeHtml(doc.description).substring(0, 100) + '</small>' : ''}
            </td>
            <td><span class="badge badge-info">${escapeHtml(doc.categorie)}</span></td>
            <td><span class="badge badge-secondary">.${escapeHtml(doc.type_fichier)}</span></td>
            <td>${escapeHtml(doc.taille_formatee)}</td>
            <td>${visibiliteBadge}</td>
            <td><small>${escapeHtml(doc.upload_par_nom || '—')}</small></td>
            <td><small>${escapeHtml(doc.date_format)}</small></td>
            <td><span class="badge badge-primary">${doc.nb_telechargements || 0}</span></td>
            <td>
                <div class="d-flex gap-xs">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="ouvrirApercu(${doc.id})" title="Infos">
                        <i class="fa-solid fa-info-circle"></i>
                    </button>
                    <a href="/gestion_scolaire/documents/telecharger/${doc.id}" class="btn btn-sm btn-outline-success" title="Télécharger">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    ${isAdmin ? `
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleVisibilite(${doc.id})" title="${doc.est_public == 1 ? 'Restreindre' : 'Rendre public'}">
                        <i class="fa-solid fa-${doc.est_public == 1 ? 'lock' : 'earth-americas'}"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="supprimerDocument(${doc.id}, '${escapeHtml(doc.nom_original)}')" title="Supprimer">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>`;
    }).join('');

    // Pagination
    if (data.pages > 1) {
        var html = '';
        for (var i = 1; i <= data.pages; i++) {
            html += '<a href="#" onclick="chargerDocuments(' + i + '); return false;" class="' + (i === data.page ? 'active' : '') + '">' + i + '</a>';
        }
        if (paginationDiv) paginationDiv.innerHTML = html;
        if (paginationInfo) paginationInfo.textContent = 'Page ' + data.page + ' / ' + data.pages + ' — ' + data.total + ' document(s)';
    } else {
        if (paginationDiv) paginationDiv.innerHTML = '';
        if (paginationInfo) paginationInfo.textContent = '';
    }
}

// ============================================================
// 3. APERÇU DU DOCUMENT
// ============================================================
function ouvrirApercu(docId) {
    var formData = new FormData();
    formData.append('action', 'getDocument');
    formData.append('document_id', docId);

    fetch('/gestion_scolaire/documents', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var doc = data.document;
            var icone = iconesFichiers[doc.type_fichier] || { icon: 'fa-file', color: '#64748b' };

            var apercuNom = document.getElementById('apercuNom');
            var apercuIcon = document.getElementById('apercuIcon');
            var apercuNomOriginal = document.getElementById('apercuNomOriginal');
            var apercuMeta = document.getElementById('apercuMeta');
            var apercuDescription = document.getElementById('apercuDescription');
            var btnTelecharger = document.getElementById('btnTelechargerApercu');
            var modal = document.getElementById('modalApercu');

            if (apercuNom) apercuNom.textContent = 'Détails du document';
            if (apercuIcon) apercuIcon.innerHTML = '<i class="fa-solid ' + icone.icon + '" style="font-size:2rem;color:' + icone.color + ';"></i>';
            if (apercuNomOriginal) apercuNomOriginal.textContent = doc.nom_original;
            if (apercuMeta) apercuMeta.innerHTML =
                '<span class="badge badge-info">' + escapeHtml(doc.categorie) + '</span> ' +
                '<span class="badge badge-secondary">.' + escapeHtml(doc.type_fichier) + '</span> ' +
                '<span class="ms-2">' + escapeHtml(doc.taille_formatee) + '</span> ' +
                '<span class="ms-2"><i class="fa-regular fa-clock"></i> ' + escapeHtml(doc.date_format) + '</span> ' +
                '<span class="ms-2"><i class="fa-solid fa-download"></i> ' + (doc.nb_telechargements || 0) + ' téléchargement(s)</span>';
            if (apercuDescription) apercuDescription.textContent = doc.description || 'Aucune description.';
            if (btnTelecharger) btnTelecharger.href = '/gestion_scolaire/documents/telecharger/' + doc.id;

            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
    });
}

function closeModalApercu() {
    var modal = document.getElementById('modalApercu');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

var modalApercu = document.getElementById('modalApercu');
if (modalApercu) {
    modalApercu.addEventListener('click', function(e) {
        if (e.target === modalApercu) closeModalApercu();
    });
}

// ============================================================
// 4. ACTIONS SUR DOCUMENTS
// ============================================================
function toggleVisibilite(docId) {
    var formData = new FormData();
    formData.append('action', 'toggleVisibilite');
    formData.append('document_id', docId);

    fetch('/gestion_scolaire/documents', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message, 'success');
            chargerDocuments(1);
        } else {
            showToast(data.message, 'error');
        }
    });
}

function supprimerDocument(docId, nom) {
    showConfirmDialog(
        'Voulez-vous supprimer le document <strong>' + escapeHtml(nom) + '</strong> ?<br><small>Cette action est irréversible.</small>',
        function(confirmed) {
            if (confirmed) {
                var formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('document_id', docId);

                fetch('/gestion_scolaire/documents', {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        showToast(data.message, 'success');
                        chargerDocuments(1);
                    } else {
                        showToast(data.message, 'error');
                    }
                });
            }
        },
        { title: 'Supprimer le document', confirmText: 'Supprimer', confirmClass: 'btn-danger', icon: 'fa-trash', iconColor: '#ef4444' }
    );
}

// ============================================================
// 5. FILTRES ET RECHERCHE
// ============================================================
function appliquerFiltres() {
    chargerDocuments(1);
}

function reinitialiserFiltres() {
    var filtreCategorie = document.getElementById('filterCategorie');
    var filtreVisibilite = document.getElementById('filterVisibilite');
    var champRecherche = document.getElementById('searchDocuments');

    if (filtreCategorie) filtreCategorie.value = '';
    if (filtreVisibilite) filtreVisibilite.value = '';
    if (champRecherche) champRecherche.value = '';

    chargerDocuments(1);
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        chargerDocuments(1);
    }, 400);
}

// ============================================================
// 6. DRAG & DROP
// ============================================================
function initDragAndDrop() {
    var dropzone = document.getElementById('uploadDropzone');
    if (!dropzone) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

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

    dropzone.addEventListener('drop', function(e) {
        var fichiers = e.dataTransfer.files;
        if (fichiers.length > 0) {
            ajouterFichiers(fichiers);
        }
    });

    dropzone.addEventListener('click', function() {
        var input = document.getElementById('fichiers');
        if (input) input.click();
    });
}

// ============================================================
// 7. INPUT FILE
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
// 8. GESTION DES FICHIERS SÉLECTIONNÉS
// ============================================================
function ajouterFichiers(fichiers) {
    var maxSize = 10 * 1024 * 1024;
    var allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'ppt', 'pptx'];
    var ajoutes = 0;
    var ignores = 0;

    for (var i = 0; i < fichiers.length; i++) {
        var fichier = fichiers[i];
        var extension = fichier.name.split('.').pop().toLowerCase();

        if (allowedExtensions.indexOf(extension) === -1) {
            showToast('Format .' + extension + ' non autorisé : ' + fichier.name, 'warning');
            ignores++;
            continue;
        }

        if (fichier.size > maxSize) {
            showToast('Fichier trop volumineux (max 10 Mo) : ' + fichier.name, 'warning');
            ignores++;
            continue;
        }

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

function afficherFichiersSelectionnes() {
    var container = document.getElementById('uploadFilesList');
    var itemsContainer = document.getElementById('uploadFilesItems');
    var countSpan = document.getElementById('uploadFilesCount');
    var btnUpload = document.getElementById('btnUpload');

    if (!container || !itemsContainer || !countSpan || !btnUpload) return;

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

    var totalDiv = document.createElement('div');
    totalDiv.className = 'upload-files-total';
    totalDiv.innerHTML = '<span>Poids total : <strong>' + tailleFormatee + '</strong></span>';

    var oldTotal = itemsContainer.querySelector('.upload-files-total');
    if (oldTotal) oldTotal.remove();
    itemsContainer.appendChild(totalDiv);
}

function retirerFichier(index) {
    fichiersSelectionnes.splice(index, 1);
    afficherFichiersSelectionnes();

    if (fichiersSelectionnes.length === 0) {
        var container = document.getElementById('uploadFilesList');
        var btnUpload = document.getElementById('btnUpload');
        if (container) container.style.display = 'none';
        if (btnUpload) btnUpload.disabled = true;
    }
}

function viderFichiers() {
    fichiersSelectionnes = [];
    afficherFichiersSelectionnes();
    var container = document.getElementById('uploadFilesList');
    var btnUpload = document.getElementById('btnUpload');
    var input = document.getElementById('fichiers');
    if (container) container.style.display = 'none';
    if (btnUpload) btnUpload.disabled = true;
    if (input) input.value = '';
}

// ============================================================
// 9. UPLOAD
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

        var categorie = document.getElementById('categorie');
        if (categorie && !categorie.value) {
            showToast('Veuillez sélectionner une catégorie.', 'warning');
            return;
        }

        uploaderFichiers();
    });
}

function uploaderFichiers() {
    uploadEnCours = true;

    var formData = new FormData();
    formData.append('action', 'upload');
    formData.append('categorie', document.getElementById('categorie')?.value || 'Autre');
    formData.append('description', document.getElementById('description')?.value || '');
    formData.append('est_public', document.getElementById('est_public')?.value || '1');

    fichiersSelectionnes.forEach(function(fichier) {
        formData.append('fichiers[]', fichier);
    });

    var progressContainer = document.getElementById('uploadProgressContainer');
    var progressFill = document.getElementById('uploadProgressFill');
    var progressText = document.getElementById('uploadProgressText');
    var resultContainer = document.getElementById('uploadResultContainer');
    var btn = document.getElementById('btnUpload');

    if (progressContainer) progressContainer.style.display = 'block';
    if (resultContainer) resultContainer.style.display = 'none';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Upload en cours...';
    }

    var xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable && progressFill && progressText) {
            var pct = Math.round((e.loaded / e.total) * 100);
            progressFill.style.width = pct + '%';
            progressText.textContent = pct + '%';
        }
    });

    xhr.addEventListener('load', function() {
        uploadEnCours = false;
        if (progressContainer) progressContainer.style.display = 'none';
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Uploader les fichiers';
        }

        try {
            var data = JSON.parse(xhr.responseText);
            afficherResultatUpload(data);
        } catch (e) {
            showToast('Erreur lors du traitement de la réponse.', 'error');
        }
    });

    xhr.addEventListener('error', function() {
        uploadEnCours = false;
        if (progressContainer) progressContainer.style.display = 'none';
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Uploader les fichiers';
        }
        showToast('Erreur de connexion lors de l\'upload.', 'error');
    });

    xhr.open('POST', '/gestion_scolaire/documents');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(formData);
}

function afficherResultatUpload(data) {
    var resultContainer = document.getElementById('uploadResultContainer');
    if (!resultContainer) return;

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

        var erreursHtml = '';
        if (data.erreurs && data.erreurs.length > 0) {
            erreursHtml = '<div class="upload-result-erreurs" style="margin-top:8px;">' +
                data.erreurs.map(function(e) { return '<p style="color:#ef4444;font-size:0.78rem;"><i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(e) + '</p>'; }).join('') +
                '</div>';
        }

        resultContainer.innerHTML = `
            <div class="upload-result-success">
                <i class="fa-solid fa-circle-check"></i>
                <h5>Upload réussi !</h5>
                <p>${data.message}</p>
                ${documentsHtml}
                ${erreursHtml}
            </div>`;

        fichiersSelectionnes = [];
        var container = document.getElementById('uploadFilesList');
        var input = document.getElementById('fichiers');
        var description = document.getElementById('description');
        if (container) container.style.display = 'none';
        if (input) input.value = '';
        if (description) description.value = '';

        showToast(data.message, 'success');

        var btn = document.getElementById('btnUpload');
        if (btn) btn.style.display = 'none';

        var footer = document.querySelector('.upload-form-footer');
        if (footer && !footer.querySelector('.btn-retour-liste')) {
            var btnRetour = document.createElement('a');
            btnRetour.href = '/gestion_scolaire/documents';
            btnRetour.className = 'btn btn-primary btn-lg btn-retour-liste';
            btnRetour.innerHTML = '<i class="fa-solid fa-list"></i> Voir tous les documents';
            footer.appendChild(btnRetour);
        }

    } else {
        var erreursHtml = '';
        if (data.erreurs && data.erreurs.length > 0) {
            erreursHtml = '<div style="margin-top:8px;">' +
                data.erreurs.map(function(e) { return '<p style="color:#ef4444;font-size:0.78rem;"><i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(e) + '</p>'; }).join('') +
                '</div>';
        }

        resultContainer.innerHTML = `
            <div class="upload-result-error">
                <i class="fa-solid fa-circle-xmark"></i>
                <h5>Échec de l'upload</h5>
                <p>${data.message}</p>
                ${erreursHtml}
            </div>`;
    }
}

// ============================================================
// 10. RACCOURCIS CLAVIER
// ============================================================
function initRaccourcisClavier() {
    document.addEventListener('keydown', function(e) {
        // Échap : fermer les modales
        if (e.key === 'Escape') {
            closeModalApercu();
        }
    });
}

// ============================================================
// 11. UTILITAIRES
// ============================================================
function escapeHtml(texte) {
    if (!texte) return '';
    var div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}