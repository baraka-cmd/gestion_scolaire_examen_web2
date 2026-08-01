<?php
/**
 * Fichier : public/views/modules/retards/liste.php
 * Rôle    : Liste des retards avec statistiques avancées
 * Système : École primaire congolaise
 * Version : 2.0 — Stats + Filtres + Top 10
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('retards.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'RetardController.php';
$retardController = new RetardController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $retardController->handleAjax();
}

$classes = $retardController->getClassesActives();
$classeId = (int) ($_GET['classe_id'] ?? 0);

if (hasRole('Enseignant') && $classeId <= 0) {
    $classeId = $retardController->getClasseEnseignant();
}

// Stats globales
$statsGlobales = $retardController->getStatsDashboard();
$totalRetards = (int) $db->fetchColumn("SELECT COUNT(*) FROM retards");
$totalJustifies = (int) $db->fetchColumn("SELECT COUNT(*) FROM retards WHERE est_justifie = 1");
$dureeMoyenne = round((float) $db->fetchColumn("SELECT AVG(duree_retard) FROM retards"), 1);
$retardsCeMois = (int) $db->fetchColumn("SELECT COUNT(*) FROM retards WHERE MONTH(date_retard) = MONTH(CURDATE()) AND YEAR(date_retard) = YEAR(CURDATE())");

$pageTitle = 'Gestion des retards';
$pageIcon  = 'fa-clock';
$pageDescription = 'Historique complet et statistiques des retards';
$extraCSS = ['modules/absences.css', 'modules/retards.css'];
$extraJS  = ['modules/retards.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Retards</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-clock"></i> Gestion des retards</h4>
        <p>Consultez l'historique complet des retards avec statistiques détaillées.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/retards/enregistrer" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-clock"></i> Enregistrer un retard
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- ============================================================
     STATISTIQUES GLOBALES
     ============================================================ -->
<div class="retards-stats mb-4">
    <div class="retard-stat-card">
        <div class="retard-stat-icon orange"><i class="fa-solid fa-clock"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= number_format($totalRetards, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Total retards</div>
            <span class="stat-change neutral"><?= $retardsCeMois ?> ce mois</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= number_format($totalJustifies, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Retards justifiés</div>
            <span class="stat-change up"><?= $totalRetards > 0 ? round(($totalJustifies / $totalRetards) * 100) : 0 ?>% de justification</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= number_format($totalRetards - $totalJustifies, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Non justifiés</div>
            <span class="stat-change down">À surveiller</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon blue"><i class="fa-solid fa-stopwatch"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $dureeMoyenne ?> min</div>
            <div class="retard-stat-label">Durée moyenne</div>
            <span class="stat-change neutral">Par retard</span>
        </div>
    </div>
</div>

<!-- ============================================================
     TOP 10 DES ÉLÈVES LES PLUS EN RETARD
     ============================================================ -->
<div class="card mb-4" id="topRetardsCard">
    <div class="card-header">
        <h5><i class="fa-solid fa-ranking-star" style="color:#f59e0b;"></i> Top 10 — Élèves les plus en retard</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="chargerTopRetards()">
            <i class="fa-solid fa-rotate"></i> Actualiser
        </button>
    </div>
    <div class="card-body no-padding">
        <div id="topRetardsContainer">
            <div style="padding:20px;text-align:center;">
                <i class="fa-solid fa-spinner fa-spin"></i> Chargement...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTRES
     ============================================================ -->
<div class="retards-filters no-print">
    <div class="filter-group">
        <label><i class="fa-solid fa-chalkboard"></i> Classe</label>
        <select id="filterClasse" onchange="appliquerFiltres()">
            <option value="">Toutes les classes</option>
            <?php foreach ($classes as $cl): ?>
                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>><?= e($cl['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label><i class="fa-solid fa-calendar"></i> Du</label>
        <input type="date" id="filterDateDebut" onchange="appliquerFiltres()">
    </div>
    <div class="filter-group">
        <label><i class="fa-solid fa-calendar"></i> Au</label>
        <input type="date" id="filterDateFin" onchange="appliquerFiltres()">
    </div>
    <div class="filter-group">
        <label><i class="fa-solid fa-check-circle"></i> Justifié</label>
        <select id="filterJustifie" onchange="appliquerFiltres()">
            <option value="">Tous</option>
            <option value="1">Justifiés</option>
            <option value="0">Non justifiés</option>
        </select>
    </div>
    <div class="filter-group">
        <label><i class="fa-solid fa-search"></i> Recherche</label>
        <input type="text" id="filterRecherche" placeholder="Nom, prénom, matricule..." onkeyup="appliquerFiltres()">
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="reinitialiserFiltres()" style="align-self:flex-end;">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     TABLEAU DES RETARDS
     ============================================================ -->
<div class="retards-table-card">
    <div class="table-header-info" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color-light);">
        <span><strong id="totalRetards">—</strong> retard(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Les retards de +30 min sont en rouge
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Matricule</th>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>H. prévue</th>
                    <th>H. arrivée</th>
                    <th>Durée</th>
                    <th>Justifié</th>
                    <th>Motif</th>
                    <th>Enregistré par</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyRetards">
                <tr><td colspan="11"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationRetards"></div>
    </div>
</div>

<script>
// ============================================================
// CHARGEMENT DU TOP 10
// ============================================================
function chargerTopRetards() {
    const formData = new FormData();
    formData.append('action', 'getStatsRetards');
    
    const classeId = document.getElementById('filterClasse')?.value || '';
    if (classeId) formData.append('classe_id', classeId);

    fetch('/gestion_scolaire/retards', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) afficherTopRetards(data);
    });
}

function afficherTopRetards(data) {
    const container = document.getElementById('topRetardsContainer');
    
    if (!data.top_retards || data.top_retards.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding:30px;"><i class="fa-solid fa-check-circle"></i><p>Aucun retard enregistré.</p></div>';
        return;
    }

    let html = '<div class="top-retards-list" style="padding:16px;">';
    data.top_retards.forEach((r, index) => {
        const alerteClass = r.nb_retards >= 10 ? 'danger' : (r.nb_retards >= 5 ? 'warning' : '');
        const alerteIcon = r.nb_retards >= 10 ? '🔴' : (r.nb_retards >= 5 ? '🟠' : '');
        
        html += `
            <div class="top-retard-item ${alerteClass}">
                <div class="top-retard-eleve">
                    <span class="top-retard-rang">#${index + 1}</span>
                    <div>
                        <span class="top-retard-nom">${escapeHtml(r.prenom + ' ' + r.nom)}</span>
                        <span class="top-retard-classe"> — ${escapeHtml(r.classe_nom)}</span>
                    </div>
                </div>
                <div class="top-retard-stats">
                    <span class="top-retard-nb">${alerteIcon} ${r.nb_retards} retard(s)</span>
                    <span class="top-retard-duree">Total : ${r.total_duree} min | Moy : ${r.moyenne_duree} min/retard</span>
                    <span style="font-size:0.7rem;color:${r.nb_justifies > 0 ? '#10b981' : '#ef4444'};">${r.nb_justifies} justifié(s)</span>
                </div>
            </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

// ============================================================
// FILTRES ET LISTE
// ============================================================
function getFiltres() {
    return {
        classe_id: document.getElementById('filterClasse')?.value || '',
        date_debut: document.getElementById('filterDateDebut')?.value || '',
        date_fin: document.getElementById('filterDateFin')?.value || '',
        justifie: document.getElementById('filterJustifie')?.value || '',
        recherche: document.getElementById('filterRecherche')?.value || ''
    };
}

function appliquerFiltres() { 
    chargerRetards(1); 
    chargerTopRetards();
}

function reinitialiserFiltres() {
    ['filterClasse','filterDateDebut','filterDateFin','filterJustifie','filterRecherche'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    chargerRetards(1);
    chargerTopRetards();
}

function chargerRetards(page) {
    const formData = new FormData();
    formData.append('action', 'getListeRetards');
    formData.append('page', page);
    
    const filtres = getFiltres();
    Object.keys(filtres).forEach(key => {
        if (filtres[key]) formData.append(key, filtres[key]);
    });

    fetch('/gestion_scolaire/retards', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) afficherRetards(data);
    });
}

function afficherRetards(data) {
    const tbody = document.getElementById('tbodyRetards');
    document.getElementById('totalRetards').textContent = data.total;

    if (!data.retards || data.retards.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11"><div class="empty-state"><i class="fa-solid fa-check-circle"></i><p>Aucun retard trouvé avec ces filtres.</p></div></td></tr>';
        document.getElementById('paginationRetards').innerHTML = '';
        document.getElementById('paginationInfo').textContent = '';
        return;
    }

    tbody.innerHTML = data.retards.map(r => `
        <tr class="${r.duree_retard > 30 ? 'retard-grave' : ''} ${r.est_justifie == 1 ? 'retard-justifie' : ''}">
            <td><strong>${escapeHtml(r.date_format)}</strong></td>
            <td><span class="badge badge-primary">${escapeHtml(r.matricule)}</span></td>
            <td><strong>${escapeHtml(r.prenom + ' ' + r.nom)}</strong></td>
            <td>${escapeHtml(r.classe_nom)}</td>
            <td>${escapeHtml(r.heure_prevue_format)}</td>
            <td><strong style="color:#f59e0b;">${escapeHtml(r.heure_arrivee_format)}</strong></td>
            <td>
                <span class="badge ${r.duree_retard > 30 ? 'badge-danger' : 'badge-warning'}">
                    <i class="fa-solid fa-clock"></i> ${r.duree_retard} min
                </span>
            </td>
            <td>${r.est_justifie == 1 
                ? '<span class="badge badge-info"><i class="fa-solid fa-check"></i> Oui</span>' 
                : '<span class="badge badge-secondary">Non</span>'}</td>
            <td><small>${escapeHtml(r.motif || '—')}</small></td>
            <td><small>${escapeHtml(r.saisie_par_nom || '—')}</small></td>
            <td>
                <div class="d-flex gap-xs">
                    <a href="/gestion_scolaire/eleves/fiche/${r.eleve_id}" class="btn btn-sm btn-outline-primary" title="Fiche élève">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                </div>
            </td>
        </tr>
    `).join('');

    let paginationHtml = '';
    if (data.pages > 1) {
        for (let i = 1; i <= data.pages; i++) {
            paginationHtml += `<a href="#" onclick="chargerRetards(${i}); return false;" class="${i === data.page ? 'active' : ''}">${i}</a>`;
        }
    }
    document.getElementById('paginationRetards').innerHTML = paginationHtml;
    document.getElementById('paginationInfo').textContent = `Page ${data.page} / ${data.pages} — ${data.total} résultat(s)`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    chargerRetards(1);
    chargerTopRetards();
});
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>