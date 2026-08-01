<?php
/**
 * Fichier : public/views/modules/absences/liste.php
 * Rôle    : Liste intelligente des absences avec filtres avancés et statistiques
 * Système : École primaire congolaise
 * Version : 2.0 — Filtres dynamiques + Stats en temps réel
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('absences.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AbsenceController.php';
$absenceController = new AbsenceController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $absenceController->handleAjax();
}

$classes = $absenceController->getClassesActives();
$trimestres = $db->fetchAll("SELECT * FROM trimestres WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) ORDER BY numero");

$classeId = (int) ($_GET['classe_id'] ?? 0);
$trimestreId = (int) ($_GET['trimestre_id'] ?? 0);

// Si enseignant, auto-sélectionner sa classe
if (hasRole('Enseignant') && $classeId <= 0) {
    $classeId = $absenceController->getClasseEnseignant();
}

// Stats globales du jour
$statsJour = $absenceController->getStatsDashboard();

$pageTitle = 'Liste des absences';
$pageIcon  = 'fa-calendar-xmark';
$pageDescription = 'Historique et statistiques des absences';
$extraCSS = ['modules/absences.css'];
$extraJS  = ['modules/absences.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Absences</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-calendar-xmark"></i> Gestion des absences</h4>
        <p>Consultez l'historique complet des absences avec statistiques.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/absences/marquer" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-calendar-check"></i> Marquer les présences
        </a>
        <a href="/gestion_scolaire/absences/justifier" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-file-medical"></i> Justifier
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- STATS RAPIDES DU JOUR -->
<div class="retards-stats mb-4">
    <div class="retard-stat-card">
        <div class="retard-stat-icon green"><i class="fa-solid fa-user-check"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $statsJour['presences_jour'] ?></div>
            <div class="retard-stat-label">Présents aujourd'hui</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon red"><i class="fa-solid fa-user-xmark"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $statsJour['absences_jour'] ?></div>
            <div class="retard-stat-label">Absents aujourd'hui</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon blue"><i class="fa-solid fa-percent"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $statsJour['taux_presence'] ?>%</div>
            <div class="retard-stat-label">Taux de présence</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon orange"><i class="fa-solid fa-chart-line"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $statsJour['total_marques'] ?></div>
            <div class="retard-stat-label">Total pointages</div>
        </div>
    </div>
</div>

<!-- FILTRES INTELLIGENTS -->
<div class="absences-filters no-print">
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
        <label><i class="fa-solid fa-calendar-alt"></i> Trimestre</label>
        <select id="filterTrimestre" onchange="appliquerFiltres()">
            <option value="">Tous les trimestres</option>
            <?php foreach ($trimestres as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $trimestreId === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['libelle']) ?></option>
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
        <label><i class="fa-solid fa-filter"></i> Statut</label>
        <select id="filterType" onchange="appliquerFiltres()">
            <option value="">Tous</option>
            <option value="Absence">Absents</option>
            <option value="Présent">Présents</option>
        </select>
    </div>
    <div class="filter-group">
        <label><i class="fa-solid fa-check-circle"></i> Justification</label>
        <select id="filterJustifie" onchange="appliquerFiltres()">
            <option value="">Tous</option>
            <option value="1">Justifiées</option>
            <option value="0">Non justifiées</option>
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

<!-- TABLEAU -->
<div class="absences-table-card">
    <div class="table-header-info" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color-light);">
        <span><strong id="totalAbsences">—</strong> absence(s) trouvée(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur une ligne pour plus de détails
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
                    <th>Statut</th>
                    <th>Justifiée</th>
                    <th>Motif</th>
                    <th>Saisie par</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyAbsences">
                <tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationAbsences"></div>
    </div>
</div>

<script>
// Filtres
function getFiltres() {
    return {
        classe_id: document.getElementById('filterClasse')?.value || '',
        trimestre_id: document.getElementById('filterTrimestre')?.value || '',
        date_debut: document.getElementById('filterDateDebut')?.value || '',
        date_fin: document.getElementById('filterDateFin')?.value || '',
        type: document.getElementById('filterType')?.value || '',
        justifiee: document.getElementById('filterJustifie')?.value || '',
        recherche: document.getElementById('filterRecherche')?.value || ''
    };
}

function appliquerFiltres() {
    chargerAbsences(1);
}

function reinitialiserFiltres() {
    document.getElementById('filterClasse').value = '';
    document.getElementById('filterTrimestre').value = '';
    document.getElementById('filterDateDebut').value = '';
    document.getElementById('filterDateFin').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterJustifie').value = '';
    document.getElementById('filterRecherche').value = '';
    chargerAbsences(1);
}

function chargerAbsences(page) {
    const formData = new FormData();
    formData.append('action', 'getListeAbsences');
    formData.append('page', page);
    
    const filtres = getFiltres();
    Object.keys(filtres).forEach(key => {
        if (filtres[key]) formData.append(key, filtres[key]);
    });

    fetch('/gestion_scolaire/absences', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) afficherAbsences(data);
    })
    .catch(() => {
        document.getElementById('tbodyAbsences').innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-exclamation-circle"></i><p>Erreur de chargement.</p></div></td></tr>';
    });
}

function afficherAbsences(data) {
    const tbody = document.getElementById('tbodyAbsences');
    document.getElementById('totalAbsences').textContent = data.total;

    if (!data.absences || data.absences.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-check-circle"></i><p>Aucune absence trouvée avec ces filtres.</p></div></td></tr>';
        document.getElementById('paginationAbsences').innerHTML = '';
        document.getElementById('paginationInfo').textContent = '';
        return;
    }

    tbody.innerHTML = data.absences.map(a => `
        <tr>
            <td><strong>${escapeHtml(a.date_format)}</strong></td>
            <td><span class="badge badge-primary">${escapeHtml(a.matricule)}</span></td>
            <td><strong>${escapeHtml(a.prenom + ' ' + a.nom)}</strong></td>
            <td>${escapeHtml(a.classe_nom)}</td>
            <td>${a.type_absence === 'Absence' 
                ? '<span class="badge badge-danger"><i class="fa-solid fa-times-circle"></i> Absent</span>' 
                : '<span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Présent</span>'}</td>
            <td>${a.est_justifiee == 1 
                ? '<span class="badge badge-info"><i class="fa-solid fa-check"></i> Oui</span>' 
                : '<span class="badge badge-secondary">Non</span>'}</td>
            <td>${escapeHtml(a.motif || '—')}</td>
            <td><small>${escapeHtml(a.saisie_par_nom || '—')}</small></td>
            <td>
                <a href="/gestion_scolaire/eleves/fiche/${a.eleve_id}" class="btn btn-sm btn-outline-primary" title="Fiche élève">
                    <i class="fa-solid fa-eye"></i>
                </a>
                ${a.type_absence === 'Absence' && a.est_justifiee == 0 ? 
                `<a href="/gestion_scolaire/absences/justifier?eleve_id=${a.eleve_id}&date=${a.date_absence}" class="btn btn-sm btn-outline-info ms-1" title="Justifier">
                    <i class="fa-solid fa-file-medical"></i>
                </a>` : ''}
            </td>
        </tr>
    `).join('');

    // Pagination
    let paginationHtml = '';
    if (data.pages > 1) {
        for (let i = 1; i <= data.pages; i++) {
            paginationHtml += `<a href="#" onclick="chargerAbsences(${i}); return false;" class="${i === data.page ? 'active' : ''}">${i}</a>`;
        }
    }
    document.getElementById('paginationAbsences').innerHTML = paginationHtml;
    document.getElementById('paginationInfo').textContent = `Page ${data.page} / ${data.pages}`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Chargement initial
document.addEventListener('DOMContentLoaded', () => chargerAbsences(1));
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>