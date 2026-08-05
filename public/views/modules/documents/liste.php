<?php
/**
 * Fichier : public/views/modules/documents/liste.php
 * Rôle    : Liste des documents avec filtres, upload et actions
 * Système : École primaire congolaise — Gestion documentaire
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('documents.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'DocumentController.php';
$documentController = new DocumentController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $documentController->handleAjax();
}

// Statistiques
$stats = $documentController->getStatsDocuments();
$isAdmin = hasRole(['Admin', 'Directeur']);

// Catégories disponibles
$categories = [
    ['valeur' => '', 'libelle' => 'Toutes les catégories'],
    ['valeur' => 'Administratif', 'libelle' => '📁 Administratif'],
    ['valeur' => 'Pédagogique', 'libelle' => '📚 Pédagogique'],
    ['valeur' => 'Financier', 'libelle' => '💰 Financier'],
    ['valeur' => 'Communication', 'libelle' => '📢 Communication'],
    ['valeur' => 'Autre', 'libelle' => '📎 Autre'],
];

$pageTitle = 'Gestion des documents';
$pageIcon  = 'fa-folder-open';
$pageDescription = 'Stockez, organisez et partagez les documents de l\'école';
$extraCSS = ['modules/documents.css'];
$extraJS  = ['modules/documents.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Documents</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-folder-open"></i> Gestion des documents</h4>
        <p>Stockez, organisez et partagez tous les documents administratifs et pédagogiques.</p>
    </div>
    <div class="resultats-header-right">
        <?php if (can('documents.creer')): ?>
            <a href="/gestion_scolaire/documents/upload" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-cloud-arrow-up"></i> Uploader des fichiers
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-file"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Documents</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-hard-drive"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['taille_totale'] ?></div>
            <div class="stat-label">Espace utilisé</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-eye"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['nb_publics'] ?></div>
            <div class="stat-label">Documents publics</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-calendar-plus"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['nb_ce_mois'] ?></div>
            <div class="stat-label">Ajoutés ce mois</div>
        </div>
    </div>
</div>

<!-- ============================================================
     BARRE DE FILTRES
     ============================================================ -->
<div class="documents-filters">
    <div class="filter-group">
        <label for="filterCategorie"><i class="fa-solid fa-tag"></i> Catégorie</label>
        <select id="filterCategorie" class="form-control" onchange="appliquerFiltres()">
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['valeur'] ?>"><?= $cat['libelle'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($isAdmin): ?>
    <div class="filter-group">
        <label for="filterVisibilite"><i class="fa-solid fa-eye"></i> Visibilité</label>
        <select id="filterVisibilite" class="form-control" onchange="appliquerFiltres()">
            <option value="">Tous</option>
            <option value="1">Publics</option>
            <option value="0">Restreints</option>
        </select>
    </div>
    <?php endif; ?>
    <div class="filter-group" style="flex:1;max-width:350px;">
        <label for="searchDocuments"><i class="fa-solid fa-search"></i> Recherche</label>
        <input type="text" id="searchDocuments" class="form-control" 
               placeholder="Nom, description, type..." onkeyup="debounceSearch()">
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="reinitialiserFiltres()" style="align-self:flex-end;">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     LISTE DES DOCUMENTS
     ============================================================ -->
<div class="documents-table-card">
    <div class="table-header-info">
        <span><strong id="totalDocuments">—</strong> document(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur le nom pour télécharger
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>Document</th>
                    <th>Catégorie</th>
                    <th>Type</th>
                    <th>Taille</th>
                    <th>Visibilité</th>
                    <th>Uploadé par</th>
                    <th>Date</th>
                    <th>Tél.</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyDocuments">
                <tr><td colspan="10"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationDocuments"></div>
    </div>
</div>

<!-- ============================================================
     MODALE : APERÇU DU DOCUMENT
     ============================================================ -->
<div class="modal-overlay" id="modalApercu">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header">
            <h5><i class="fa-solid fa-file"></i> <span id="apercuNom"></span></h5>
            <button type="button" class="modal-close" onclick="closeModalApercu()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="document-apercu-info">
                <div class="document-apercu-icon" id="apercuIcon"></div>
                <div>
                    <h6 id="apercuNomOriginal"></h6>
                    <p id="apercuMeta"></p>
                </div>
            </div>
            <div id="apercuDescription" style="margin-top:16px;font-size:0.85rem;color:#64748b;"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" onclick="closeModalApercu()">
                <i class="fa-solid fa-xmark"></i> Fermer
            </button>
            <a href="#" class="btn btn-primary" id="btnTelechargerApercu">
                <i class="fa-solid fa-download"></i> Télécharger
            </a>
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
var searchTimeout;
var isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
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
    chargerDocuments(1);
});

// ============================================================
// CHARGEMENT DES DOCUMENTS
// ============================================================
function chargerDocuments(page) {
    var formData = new FormData();
    formData.append('action', 'getListe');
    formData.append('page', page);
    
    var categorie = document.getElementById('filterCategorie')?.value || '';
    var visibilite = document.getElementById('filterVisibilite')?.value || '';
    var recherche = document.getElementById('searchDocuments')?.value || '';
    
    if (categorie) formData.append('categorie', categorie);
    if (visibilite !== '') formData.append('visibilite', visibilite);
    if (recherche) formData.append('recherche', recherche);

    fetch('/gestion_scolaire/documents', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) afficherDocuments(data);
    });
}

// ============================================================
// AFFICHAGE DES DOCUMENTS
// ============================================================
function afficherDocuments(data) {
    var tbody = document.getElementById('tbodyDocuments');
    document.getElementById('totalDocuments').textContent = data.total;

    if (!data.documents || data.documents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10"><div class="empty-state" style="padding:40px;"><i class="fa-solid fa-folder-open"></i><p>Aucun document trouvé.</p>' +
            <?php if (can('documents.creer')): ?>
            '<a href="/gestion_scolaire/documents/upload" class="btn btn-primary btn-sm mt-3"><i class="fa-solid fa-cloud-arrow-up"></i> Uploader un document</a>' +
            <?php endif; ?>
            '</div></td></tr>';
        document.getElementById('paginationDocuments').innerHTML = '';
        document.getElementById('paginationInfo').textContent = '';
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
        document.getElementById('paginationDocuments').innerHTML = html;
        document.getElementById('paginationInfo').textContent = 'Page ' + data.page + ' / ' + data.pages + ' — ' + data.total + ' document(s)';
    } else {
        document.getElementById('paginationDocuments').innerHTML = '';
        document.getElementById('paginationInfo').textContent = '';
    }
}

// ============================================================
// APERÇU DU DOCUMENT
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
            
            document.getElementById('apercuNom').textContent = 'Détails du document';
            document.getElementById('apercuIcon').innerHTML = '<i class="fa-solid ' + icone.icon + '" style="font-size:2rem;color:' + icone.color + ';"></i>';
            document.getElementById('apercuNomOriginal').textContent = doc.nom_original;
            document.getElementById('apercuMeta').innerHTML = 
                '<span class="badge badge-info">' + escapeHtml(doc.categorie) + '</span> ' +
                '<span class="badge badge-secondary">.' + escapeHtml(doc.type_fichier) + '</span> ' +
                '<span class="ms-2">' + escapeHtml(doc.taille_formatee) + '</span> ' +
                '<span class="ms-2"><i class="fa-regular fa-clock"></i> ' + escapeHtml(doc.date_format) + '</span> ' +
                '<span class="ms-2"><i class="fa-solid fa-download"></i> ' + (doc.nb_telechargements || 0) + ' téléchargement(s)</span>';
            document.getElementById('apercuDescription').textContent = doc.description || 'Aucune description.';
            document.getElementById('btnTelechargerApercu').href = '/gestion_scolaire/documents/telecharger/' + doc.id;
            
            document.getElementById('modalApercu').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    });
}

function closeModalApercu() {
    document.getElementById('modalApercu').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('modalApercu').addEventListener('click', function(e) {
    if (e.target === this) closeModalApercu();
});

// ============================================================
// ACTIONS
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
// FILTRES
// ============================================================
function appliquerFiltres() { chargerDocuments(1); }

function reinitialiserFiltres() {
    document.getElementById('filterCategorie').value = '';
    if (document.getElementById('filterVisibilite')) document.getElementById('filterVisibilite').value = '';
    document.getElementById('searchDocuments').value = '';
    chargerDocuments(1);
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() { chargerDocuments(1); }, 400);
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>