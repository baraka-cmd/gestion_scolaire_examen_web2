<?php
/**
 * Fichier : public/views/modules/paiements/liste.php
 * Rôle    : Liste des paiements avec filtres, statistiques et actions
 * Système : École primaire congolaise — Gestion financière
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('paiements.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'PaiementController.php';
$paiementController = new PaiementController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $paiementController->handleAjax();
}

$classes = $paiementController->getClassesActives();

// Stats globales


$totalEncaissement = (float) $db->fetchColumn("SELECT COALESCE(SUM(montant_paye), 0) FROM paiements");
$nbPaiements = (int) $db->fetchColumn("SELECT COUNT(*) FROM paiements");
$paiementsMois = (float) $db->fetchColumn("SELECT COALESCE(SUM(montant_paye), 0) FROM paiements WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())");
$nbPaiementsMois = (int) $db->fetchColumn("SELECT COUNT(*) FROM paiements WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())");
$nbElevesEnRegle = (int) $db->fetchColumn(
    "SELECT COUNT(DISTINCT i.eleve_id) FROM inscriptions i
     WHERE i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
     AND i.statut = 'Actif'
     AND i.eleve_id IN (SELECT DISTINCT eleve_id FROM paiements WHERE statut = 'Payé')"
);
$nbElevesTotal = (int) $db->fetchColumn("SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) AND statut = 'Actif'");

$pageTitle = 'Gestion des paiements';
$pageIcon  = 'fa-money-bill-wave';
$pageDescription = 'Liste des paiements, encaissements et suivi financier';
$extraCSS = ['modules/paiements.css'];
$extraJS  = ['modules/paiements.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Paiements</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-money-bill-wave"></i> Gestion des paiements</h4>
        <p>Liste complète des paiements, encaissements et suivi financier.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/paiements/enregistrer" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Nouveau paiement
        </a>
        <a href="/gestion_scolaire/paiements/impayes" class="btn btn-outline-warning btn-sm">
            <i class="fa-solid fa-exclamation-circle"></i> Impayés
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="retards-stats mb-4">
    <div class="retard-stat-card">
        <div class="retard-stat-icon green">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value">$ <?= number_format($totalEncaissement, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Total encaissé</div>
            <span class="stat-change up"><?= $nbPaiements ?> paiement(s)</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon blue">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value">$ <?= number_format($paiementsMois, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Encaissé ce mois</div>
            <span class="stat-change neutral"><?= $nbPaiementsMois ?> transaction(s)</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon orange">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $nbElevesEnRegle ?> / <?= $nbElevesTotal ?></div>
            <div class="retard-stat-label">Élèves en règle</div>
            <span class="stat-change <?= $nbElevesTotal > 0 && ($nbElevesEnRegle / $nbElevesTotal) >= 0.8 ? 'up' : 'down' ?>">
                <?= $nbElevesTotal > 0 ? round(($nbElevesEnRegle / $nbElevesTotal) * 100) : 0 ?>% de recouvrement
            </span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon red">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $nbElevesTotal - $nbElevesEnRegle ?></div>
            <div class="retard-stat-label">Élèves en défaut</div>
            <span class="stat-change down">À recouvrer</span>
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
                <option value="<?= $cl['id'] ?>"><?= e($cl['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label><i class="fa-solid fa-toggle-on"></i> Statut</label>
        <select id="filterStatut" onchange="appliquerFiltres()">
            <option value="">Tous les statuts</option>
            <option value="Payé">Payés</option>
            <option value="Partiel">Partiels</option>
            <option value="Impayé">Impayés</option>
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
        <label><i class="fa-solid fa-search"></i> Recherche</label>
        <input type="text" id="filterRecherche" placeholder="Nom, prénom, matricule..." onkeyup="appliquerFiltres()">
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="reinitialiserFiltres()" style="align-self:flex-end;">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     TABLEAU
     ============================================================ -->
<div class="retards-table-card">
    <div class="table-header-info" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color-light);">
        <span><strong id="totalPaiements">—</strong> paiement(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur <i class="fa-solid fa-receipt"></i> pour voir le reçu
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
                    <th>Frais</th>
                    <th>Montant total</th>
                    <th>Montant payé</th>
                    <th>Reste</th>
                    <th>Statut</th>
                    <th>Mode</th>
                    <th>Enregistré par</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyPaiements">
                <tr><td colspan="12"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationPaiements"></div>
    </div>
</div>

<script>
function getFiltres() {
    return {
        classe_id: document.getElementById('filterClasse')?.value || '',
        statut: document.getElementById('filterStatut')?.value || '',
        date_debut: document.getElementById('filterDateDebut')?.value || '',
        date_fin: document.getElementById('filterDateFin')?.value || '',
        recherche: document.getElementById('filterRecherche')?.value || ''
    };
}

function appliquerFiltres() { chargerPaiements(1); }

function reinitialiserFiltres() {
    ['filterClasse','filterStatut','filterDateDebut','filterDateFin','filterRecherche'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    chargerPaiements(1);
}

function chargerPaiements(page) {
    const formData = new FormData();
    formData.append('action', 'getListePaiements');
    formData.append('page', page);

    const filtres = getFiltres();
    Object.keys(filtres).forEach(key => {
        if (filtres[key]) formData.append(key, filtres[key]);
    });

    fetch('/gestion_scolaire/paiements', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) afficherPaiements(data);
    });
}

function afficherPaiements(data) {
    const tbody = document.getElementById('tbodyPaiements');
    document.getElementById('totalPaiements').textContent = data.total;

    if (!data.paiements || data.paiements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12"><div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Aucun paiement trouvé.</p></div></td></tr>';
        document.getElementById('paginationPaiements').innerHTML = '';
        document.getElementById('paginationInfo').textContent = '';
        return;
    }

    tbody.innerHTML = data.paiements.map(p => {
        const reste = parseFloat(p.montant_total) - parseFloat(p.montant_paye);
        const statutClass = p.statut === 'Payé' ? 'badge-success' : (p.statut === 'Partiel' ? 'badge-warning' : 'badge-danger');
        const resteClass = reste > 0 ? 'text-danger' : 'text-success';

        return `
        <tr>
            <td><strong>${escapeHtml(p.date_format)}</strong></td>
            <td><span class="badge badge-primary">${escapeHtml(p.matricule)}</span></td>
            <td><strong>${escapeHtml(p.prenom + ' ' + p.nom)}</strong></td>
            <td>${escapeHtml(p.classe_nom)}</td>
            <td>${escapeHtml(p.frais_libelle)}</td>
            <td><strong>$ ${parseFloat(p.montant_total).toLocaleString('fr-FR')}</strong></td>
            <td><strong>$ ${parseFloat(p.montant_paye).toLocaleString('fr-FR')}</strong></td>
            <td class="${resteClass}"><strong>$ ${reste.toLocaleString('fr-FR')}</strong></td>
            <td><span class="badge ${statutClass}">${p.statut}</span></td>
            <td>${escapeHtml(p.mode_paiement)}</td>
            <td><small>${escapeHtml(p.enregistre_par_nom || '—')}</small></td>
            <td>
                <div class="d-flex gap-xs">
                    <a href="/gestion_scolaire/paiements/recu?paiement_id=${p.id}" class="btn btn-sm btn-outline-primary" title="Voir le reçu" target="_blank">
                        <i class="fa-solid fa-receipt"></i>
                    </a>
                    <a href="/gestion_scolaire/eleves/fiche/${p.eleve_id}" class="btn btn-sm btn-outline-info" title="Fiche élève">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                </div>
            </td>
        </tr>`;
    }).join('');

    let paginationHtml = '';
    if (data.pages > 1) {
        for (let i = 1; i <= data.pages; i++) {
            paginationHtml += `<a href="#" onclick="chargerPaiements(${i}); return false;" class="${i === data.page ? 'active' : ''}">${i}</a>`;
        }
    }
    document.getElementById('paginationPaiements').innerHTML = paginationHtml;
    document.getElementById('paginationInfo').textContent = `Page ${data.page} / ${data.pages} — ${data.total} résultat(s)`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => chargerPaiements(1));
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>