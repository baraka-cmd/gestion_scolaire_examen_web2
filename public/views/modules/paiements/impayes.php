<?php
/**
 * Fichier : public/views/modules/paiements/impayes.php
 * Rôle    : Suivi des impayés — Élèves avec soldes restants
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
$totalImpayes = (float) $db->fetchColumn(
    "SELECT COALESCE(SUM(p.montant_total - p.montant_paye), 0) FROM paiements p WHERE p.statut != 'Payé'"
);
$nbElevesImpayes = (int) $db->fetchColumn(
    "SELECT COUNT(DISTINCT p.eleve_id) FROM paiements p WHERE p.statut != 'Payé'"
);
$nbElevesTotal = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) AND statut = 'Actif'"
);
$nbElevesEnRegle = $nbElevesTotal - $nbElevesImpayes;
$tauxRecouvrement = $nbElevesTotal > 0 ? round(($nbElevesEnRegle / $nbElevesTotal) * 100, 1) : 0;
$totalEncaissement = (float) $db->fetchColumn("SELECT COALESCE(SUM(montant_paye), 0) FROM paiements");
$totalAttendu = (float) $db->fetchColumn(
    "SELECT COALESCE(SUM(f.montant), 0) FROM frais_scolaires f
     WHERE f.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
     AND f.est_obligatoire = 1 AND f.est_actif = 1"
) * $nbElevesTotal;
$tauxRecouvrementMontant = $totalAttendu > 0 ? round(($totalEncaissement / $totalAttendu) * 100, 1) : 0;

$pageTitle = 'Suivi des impayés';
$pageIcon  = 'fa-exclamation-circle';
$pageDescription = 'Liste des élèves avec soldes restants à payer';
$extraCSS = ['modules/paiements.css'];
$extraJS  = ['modules/paiements.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/paiements">Paiements</a>
    <span class="separator">/</span>
    <span class="current">Impayés</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-exclamation-circle"></i> Suivi des impayés</h4>
        <p>Liste des élèves qui n'ont pas encore soldé leurs frais scolaires.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/paiements/enregistrer" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Nouveau paiement
        </a>
        <a href="/gestion_scolaire/paiements" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Tous les paiements
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
        <div class="retard-stat-icon red">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value">$ <?= number_format($totalImpayes, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Total impayés</div>
            <span class="stat-change down"><?= $nbElevesImpayes ?> élève(s) concerné(s)</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon orange">
            <i class="fa-solid fa-user-xmark"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $nbElevesImpayes ?></div>
            <div class="retard-stat-label">Élèves en défaut</div>
            <span class="stat-change down">Sur <?= $nbElevesTotal ?> inscrits</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon green">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $nbElevesEnRegle ?></div>
            <div class="retard-stat-label">Élèves en règle</div>
            <span class="stat-change up"><?= $tauxRecouvrement ?>% de l'effectif</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon blue">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $tauxRecouvrementMontant ?>%</div>
            <div class="retard-stat-label">Taux de recouvrement</div>
            <span class="stat-change <?= $tauxRecouvrementMontant >= 80 ? 'up' : ($tauxRecouvrementMontant >= 50 ? 'neutral' : 'down') ?>">
                $ <?= number_format($totalEncaissement, 0, ',', ' ') ?> / $ <?= number_format($totalAttendu, 0, ',', ' ') ?>
            </span>
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
        <label><i class="fa-solid fa-search"></i> Recherche</label>
        <input type="text" id="filterRecherche" placeholder="Nom, prénom, matricule..." onkeyup="appliquerFiltres()">
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="reinitialiserFiltres()" style="align-self:flex-end;">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     TABLEAU DES IMPAYÉS
     ============================================================ -->
<div class="retards-table-card">
    <div class="table-header-info" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color-light);">
        <span><strong id="totalImpayes">—</strong> élève(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur <i class="fa-solid fa-money-bill"></i> pour enregistrer un paiement
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>Total dû</th>
                    <th>Total payé</th>
                    <th>Reste à payer</th>
                    <th>Progression</th>
                    <th>Détails</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyImpayes">
                <tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function getFiltres() {
    return {
        classe_id: document.getElementById('filterClasse')?.value || '',
        recherche: document.getElementById('filterRecherche')?.value || ''
    };
}

function appliquerFiltres() { chargerImpayes(); }

function reinitialiserFiltres() {
    document.getElementById('filterClasse').value = '';
    document.getElementById('filterRecherche').value = '';
    chargerImpayes();
}

function chargerImpayes() {
    const formData = new FormData();
    formData.append('action', 'getImpayes');

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
        if (data.success) afficherImpayes(data);
    });
}

function afficherImpayes(data) {
    const tbody = document.getElementById('tbodyImpayes');
    document.getElementById('totalImpayes').textContent = data.total;

    if (!data.impayes || data.impayes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i><p>Tous les élèves sont en règle. Aucun impayé.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = data.impayes.map((el, index) => {
        const totalDu = parseFloat(el.total_du) || 0;
        const totalPaye = parseFloat(el.total_paye) || 0;
        const resteAPayer = parseFloat(el.reste_a_payer) || 0;
        const pct = totalDu > 0 ? Math.round((totalPaye / totalDu) * 100) : 0;
        const pctClass = pct >= 80 ? 'complete' : (pct >= 40 ? 'in-progress' : 'empty');
        const badgeClass = resteAPayer <= 0 ? 'badge-success' : (totalPaye > 0 ? 'badge-warning' : 'badge-danger');

        return `
        <tr class="${index < 3 ? 'top-rank' : ''}">
            <td><span class="badge badge-primary">${escapeHtml(el.matricule)}</span></td>
            <td><strong>${escapeHtml(el.prenom + ' ' + el.nom)}</strong></td>
            <td>${escapeHtml(el.classe_nom)}</td>
            <td><strong>$ ${totalDu.toLocaleString('fr-FR')}</strong></td>
            <td>$ ${totalPaye.toLocaleString('fr-FR')}</td>
            <td>
                <span class="${resteAPayer > 0 ? 'text-danger' : 'text-success'}" style="font-weight:700;font-size:0.9rem;">
                    $ ${resteAPayer.toLocaleString('fr-FR')}
                </span>
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex:1;height:8px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
                        <div style="width:${pct}%;height:100%;background:${pct >= 80 ? '#10b981' : (pct >= 40 ? '#f59e0b' : '#ef4444')};border-radius:10px;"></div>
                    </div>
                    <span style="font-size:0.72rem;font-weight:600;">${pct}%</span>
                </div>
            </td>
            <td>
                <span style="font-size:0.7rem;color:var(--color-gray-500);">
                    ${escapeHtml(el.details || 'Aucun paiement')}
                </span>
            </td>
            <td>
                <a href="/gestion_scolaire/paiements/enregistrer?eleve_id=${el.id}" class="btn btn-sm btn-primary" title="Enregistrer un paiement">
                    <i class="fa-solid fa-money-bill"></i> Payer
                </a>
                <a href="/gestion_scolaire/eleves/fiche/${el.id}" class="btn btn-sm btn-outline-primary ms-1" title="Fiche élève">
                    <i class="fa-solid fa-eye"></i>
                </a>
            </td>
        </tr>`;
    }).join('');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => chargerImpayes());
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>