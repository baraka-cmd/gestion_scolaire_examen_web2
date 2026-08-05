<?php
/**
 * Fichier : public/views/modules/logs/liste.php
 * Rôle    : Consultation des journaux d'activité
 * Système : École primaire congolaise — Administration
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('logs.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'LogController.php';
$logController = new LogController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $logController->handleAjax();
}

// Données pour les filtres
$utilisateurs = $logController->getUtilisateursActifs();
$actions = $logController->getActionsDistinctes();
$tables = $logController->getTablesDistinctes();

// Stats rapides pour affichage initial
$totalLogs = (int) $db->fetchColumn("SELECT COUNT(*) FROM logs_activites");
$logsAujourdhui = (int) $db->fetchColumn("SELECT COUNT(*) FROM logs_activites WHERE DATE(date_action) = CURDATE()");
$logsSemaine = (int) $db->fetchColumn("SELECT COUNT(*) FROM logs_activites WHERE YEARWEEK(date_action, 1) = YEARWEEK(CURDATE(), 1)");

$pageTitle = 'Journaux d\'activité';
$pageIcon  = 'fa-clock-rotate-left';
$pageDescription = 'Consultez l\'historique complet des actions effectuées dans le système';
$extraCSS = ['modules/logs.css'];
$extraJS  = ['modules/logs.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Journaux d'activité</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-clock-rotate-left"></i> Journaux d'activité</h4>
        <p>Consultez l'historique complet de toutes les actions effectuées dans le système.</p>
    </div>
    <div class="resultats-header-right">
        <?php if (can('logs.supprimer')): ?>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="viderLogs()" id="btnViderLogs">
                <i class="fa-solid fa-eraser"></i> Nettoyer les logs
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnActualiser" onclick="chargerLogs(1); chargerStats();">
            <i class="fa-solid fa-rotate"></i> Actualiser
        </button>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="stats-grid mb-4" id="statsContainer">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-list-ol"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalLogs, 0, ',', ' ') ?></div>
            <div class="stat-label">Total logs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="stat-content">
            <div class="stat-value" id="statAujourdhui"><?= number_format($logsAujourdhui, 0, ',', ' ') ?></div>
            <div class="stat-label">Aujourd'hui</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-calendar-week"></i></div>
        <div class="stat-content">
            <div class="stat-value" id="statSemaine"><?= number_format($logsSemaine, 0, ',', ' ') ?></div>
            <div class="stat-label">Cette semaine</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value" id="statUtilisateurs">—</div>
            <div class="stat-label">Utilisateurs actifs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value" style="font-size:0.85rem;" id="statDerniereActivite">—</div>
            <div class="stat-label">Dernière activité</div>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTRES
     ============================================================ -->
<div class="logs-filters">
    <div class="filter-group">
        <label for="filterUtilisateur"><i class="fa-solid fa-user"></i> Utilisateur</label>
        <select id="filterUtilisateur" class="form-control" onchange="appliquerFiltres()">
            <option value="">Tous les utilisateurs</option>
            <?php foreach ($utilisateurs as $u): ?>
                <option value="<?= $u['id'] ?>"><?= e($u['nom_complet']) ?> (<?= e($u['login']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterAction"><i class="fa-solid fa-bolt"></i> Action</label>
        <select id="filterAction" class="form-control" onchange="appliquerFiltres()">
            <option value="">Toutes les actions</option>
            <?php foreach ($actions as $act): ?>
                <option value="<?= e($act) ?>"><?= e($act) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterTable"><i class="fa-solid fa-table"></i> Table</label>
        <select id="filterTable" class="form-control" onchange="appliquerFiltres()">
            <option value="">Toutes les tables</option>
            <?php foreach ($tables as $tab): ?>
                <option value="<?= e($tab) ?>"><?= e($tab) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterDateDebut"><i class="fa-solid fa-calendar"></i> Du</label>
        <input type="date" id="filterDateDebut" class="form-control" onchange="appliquerFiltres()">
    </div>
    <div class="filter-group">
        <label for="filterDateFin"><i class="fa-solid fa-calendar"></i> Au</label>
        <input type="date" id="filterDateFin" class="form-control" onchange="appliquerFiltres()">
    </div>
    <div class="filter-group" style="flex:1;max-width:300px;">
        <label for="searchLogs"><i class="fa-solid fa-search"></i> Recherche</label>
        <input type="text" id="searchLogs" class="form-control" placeholder="Description, action, utilisateur..." onkeyup="debounceSearch()">
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="reinitialiserFiltres()" style="align-self:flex-end;">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     TABLEAU DES LOGS
     ============================================================ -->
<div class="logs-table-card">
    <div class="table-header-info">
        <span><strong id="totalLogsTable">—</strong> log(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Les logs sont affichés par ordre chronologique inverse
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Description</th>
                    <th>Adresse IP</th>
                </tr>
            </thead>
            <tbody id="tbodyLogs">
                <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationLogs"></div>
    </div>
</div>

<!-- ============================================================
     GRAPHIQUES
     ============================================================ -->
<div class="charts-grid-2 mb-4 mt-4">
    <!-- Top 10 actions -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-ranking-star"></i> Top 10 — Actions les plus fréquentes</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartTopActions"></canvas>
            </div>
        </div>
    </div>

    <!-- Top 5 utilisateurs -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-user-gear"></i> Top 5 — Utilisateurs les plus actifs</h5>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table table-finance">
                    <thead><tr><th>#</th><th>Utilisateur</th><th>Actions</th><th>Dernière activité</th></tr></thead>
                    <tbody id="tbodyTopUtilisateurs">
                        <tr><td colspan="4"><div class="empty-state" style="padding:20px;"><p>Chargement...</p></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ACTIVITÉ DES 7 DERNIERS JOURS
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fa-solid fa-chart-line"></i> Activité des 7 derniers jours</h5>
    </div>
    <div class="card-body">
        <div class="chart-wrapper-md">
            <canvas id="chartActiviteJour"></canvas>
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
var chartTopActions = null;
var chartActiviteJour = null;

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    chargerLogs(1);
    chargerStats();
    chargerTopActions();
    chargerTopUtilisateurs();
    chargerActiviteParJour();
});

// ============================================================
// CHARGEMENT DES LOGS
// ============================================================
function chargerLogs(page) {
    var tbody = document.getElementById('tbodyLogs');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state" style="padding:30px;"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>';

    var formData = new FormData();
    formData.append('action', 'getListe');
    formData.append('page', page || 1);
    formData.append('utilisateur_id', document.getElementById('filterUtilisateur')?.value || '');
    formData.append('action_filtre', document.getElementById('filterAction')?.value || '');
    formData.append('table_concernee', document.getElementById('filterTable')?.value || '');
    formData.append('date_debut', document.getElementById('filterDateDebut')?.value || '');
    formData.append('date_fin', document.getElementById('filterDateFin')?.value || '');
    formData.append('recherche', document.getElementById('searchLogs')?.value || '');

    fetch('/gestion_scolaire/logs', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) afficherLogs(data);
    });
}

function afficherLogs(data) {
    var tbody = document.getElementById('tbodyLogs');
    var totalSpan = document.getElementById('totalLogsTable');
    var paginationDiv = document.getElementById('paginationLogs');
    var paginationInfo = document.getElementById('paginationInfo');

    if (!tbody) return;
    if (totalSpan) totalSpan.textContent = data.total;

    if (!data.logs || data.logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state" style="padding:40px;"><i class="fa-solid fa-clock-rotate-left"></i><p>Aucun log trouvé avec ces filtres.</p></div></td></tr>';
        if (paginationDiv) paginationDiv.innerHTML = '';
        if (paginationInfo) paginationInfo.textContent = '';
        return;
    }

    tbody.innerHTML = data.logs.map(function(l) {
        var actionBadge = '';
        var actionLower = (l.action || '').toLowerCase();
        
        if (actionLower.indexOf('connexion') !== -1 || actionLower.indexOf('déconnexion') !== -1) {
            actionBadge = '<span class="badge badge-info"><i class="fa-solid fa-right-to-bracket"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('création') !== -1 || actionLower.indexOf('upload') !== -1) {
            actionBadge = '<span class="badge badge-success"><i class="fa-solid fa-plus-circle"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('modification') !== -1 || actionLower.indexOf('saisie') !== -1) {
            actionBadge = '<span class="badge badge-warning"><i class="fa-solid fa-pen-to-square"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('suppression') !== -1 || actionLower.indexOf('échec') !== -1) {
            actionBadge = '<span class="badge badge-danger"><i class="fa-solid fa-trash"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('accès refusé') !== -1) {
            actionBadge = '<span class="badge badge-dark"><i class="fa-solid fa-ban"></i> ' + escapeHtml(l.action) + '</span>';
        } else {
            actionBadge = '<span class="badge badge-secondary">' + escapeHtml(l.action) + '</span>';
        }

        return '<tr>' +
            '<td><i class="fa-solid fa-circle" style="font-size:0.5rem;color:#94a3b8;"></i></td>' +
            '<td><strong>' + escapeHtml(l.date_format) + '</strong><br><small class="text-muted">' + escapeHtml(l.heure_format) + '</small></td>' +
            '<td><small>' + escapeHtml(l.utilisateur_nom || 'Système') + '</small></td>' +
            '<td>' + actionBadge + '</td>' +
            '<td><small>' + escapeHtml(l.table_concernee || '—') + '</small></td>' +
            '<td><small>' + escapeHtml(l.description || '—') + '</small></td>' +
            '<td><small class="text-muted">' + escapeHtml(l.adresse_ip || '—') + '</small></td>' +
        '</tr>';
    }).join('');

    if (data.pages > 1) {
        var html = '';
        for (var i = 1; i <= data.pages; i++) {
            html += '<a href="#" onclick="chargerLogs(' + i + '); return false;" class="' + (i === data.page ? 'active' : '') + '">' + i + '</a>';
        }
        if (paginationDiv) paginationDiv.innerHTML = html;
        if (paginationInfo) paginationInfo.textContent = 'Page ' + data.page + ' / ' + data.pages + ' — ' + data.total + ' log(s)';
    } else {
        if (paginationDiv) paginationDiv.innerHTML = '';
        if (paginationInfo) paginationInfo.textContent = '';
    }
}

// ============================================================
// STATISTIQUES
// ============================================================
function chargerStats() {
    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getStatistiques' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('statAujourdhui').textContent = data.stats.logs_aujourdhui.toLocaleString('fr-FR');
            document.getElementById('statSemaine').textContent = data.stats.logs_semaine.toLocaleString('fr-FR');
            document.getElementById('statUtilisateurs').textContent = data.stats.nb_utilisateurs;
            if (data.stats.derniere_activite) {
                document.getElementById('statDerniereActivite').innerHTML = 
                    escapeHtml(data.stats.derniere_activite.utilisateur_nom || 'Système') + '<br><small>' + escapeHtml(data.stats.derniere_activite.date_format) + '</small>';
            }
        }
    });
}

// ============================================================
// TOP ACTIONS
// ============================================================
function chargerTopActions() {
    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getTopActions' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.top_actions.length > 0) {
            var ctx = document.getElementById('chartTopActions');
            if (ctx) {
                if (chartTopActions) chartTopActions.destroy();
                chartTopActions = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.top_actions.map(function(a) { return a.action; }),
                        datasets: [{
                            label: 'Nombre d\'actions',
                            data: data.top_actions.map(function(a) { return a.nb; }),
                            backgroundColor: 'rgba(44, 62, 144, 0.7)',
                            borderColor: '#2c3e90',
                            borderWidth: 1,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// TOP UTILISATEURS
// ============================================================
function chargerTopUtilisateurs() {
    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getTopUtilisateurs' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var tbody = document.getElementById('tbodyTopUtilisateurs');
            if (data.top_utilisateurs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state"><p>Aucune donnée.</p></div></td></tr>';
            } else {
                var icones = ['<i class="fa-solid fa-medal" style="color:#f59e0b;"></i>', '<i class="fa-solid fa-medal" style="color:#94a3b8;"></i>', '<i class="fa-solid fa-medal" style="color:#cd853f;"></i>'];
                tbody.innerHTML = data.top_utilisateurs.map(function(u, i) {
                    return '<tr><td>' + (icones[i] || ('#' + (i+1))) + '</td><td><strong>' + escapeHtml(u.utilisateur_nom) + '</strong><br><small>@' + escapeHtml(u.login) + '</small></td><td><span class="badge badge-primary">' + u.nb + '</span></td><td><small>' + escapeHtml(u.derniere_action_format) + '</small></td></tr>';
                }).join('');
            }
        }
    });
}

// ============================================================
// ACTIVITÉ PAR JOUR
// ============================================================
function chargerActiviteParJour() {
    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getActiviteParJour' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.activite.length > 0) {
            var ctx = document.getElementById('chartActiviteJour');
            if (ctx) {
                if (chartActiviteJour) chartActiviteJour.destroy();
                chartActiviteJour = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.activite.map(function(a) { return a.jour + ' ' + a.label; }),
                        datasets: [{
                            label: 'Actions',
                            data: data.activite.map(function(a) { return a.nb; }),
                            borderColor: '#2c3e90',
                            backgroundColor: 'rgba(44, 62, 144, 0.08)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2.5,
                            pointRadius: 5,
                            pointBackgroundColor: '#2c3e90',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// FILTRES
// ============================================================
function appliquerFiltres() { chargerLogs(1); }

function reinitialiserFiltres() {
    document.getElementById('filterUtilisateur').value = '';
    document.getElementById('filterAction').value = '';
    document.getElementById('filterTable').value = '';
    document.getElementById('filterDateDebut').value = '';
    document.getElementById('filterDateFin').value = '';
    document.getElementById('searchLogs').value = '';
    chargerLogs(1);
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() { chargerLogs(1); }, 400);
}

// ============================================================
// VIDER LES LOGS
// ============================================================
function viderLogs() {
    showConfirmDialog(
        'Voulez-vous nettoyer les anciens logs ?<br><small>Les 100 logs les plus récents seront conservés.</small>',
        function(confirmed) {
            if (confirmed) {
                var formData = new FormData();
                formData.append('action', 'viderLogs');
                fetch('/gestion_scolaire/logs', {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) { showToast(data.message, 'success'); chargerLogs(1); chargerStats(); }
                    else { showToast(data.message, 'error'); }
                });
            }
        },
        { title: 'Nettoyer les logs', confirmText: 'Nettoyer', confirmClass: 'btn-danger', icon: 'fa-eraser', iconColor: '#ef4444' }
    );
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>