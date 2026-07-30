<?php
/**
 * Fichier : public/views/modules/parents/liste.php
 * Rôle    : Liste des parents/tuteurs avec recherche, filtres et actions
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('parents.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ParentController.php';
$parentController = new ParentController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $parentController->handleAjax();
}

// Récupérer les liens pour le filtre
$liens = $parentController->getLiens();

// Statistiques pour le résumé
$totalParents = (int) $db->fetchColumn("SELECT COUNT(*) FROM parents_tuteurs");
$totalPeres   = (int) $db->fetchColumn("SELECT COUNT(*) FROM parents_tuteurs WHERE lien = 'Père'");
$totalMeres   = (int) $db->fetchColumn("SELECT COUNT(*) FROM parents_tuteurs WHERE lien = 'Mère'");
$totalTuteurs = (int) $db->fetchColumn("SELECT COUNT(*) FROM parents_tuteurs WHERE lien IN ('Tuteur', 'Tutrice')");
$totalUrgence = (int) $db->fetchColumn("SELECT COUNT(*) FROM parents_tuteurs WHERE est_contact_urgence = 1");

// Métadonnées
$pageTitle = 'Gestion des parents';
$pageIcon  = 'fa-people-roof';
$pageDescription = 'Gérez les parents, tuteurs et leurs liens avec les élèves';
$extraCSS = ['modules/parents.css'];
$extraJS  = ['modules/parents.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="parents-header">
    <div class="parents-header-left">
        <h4>
            <i class="fa-solid fa-people-roof"></i> Gestion des parents
        </h4>
        <p>Gérez les parents, tuteurs et leurs liens avec les élèves.</p>
    </div>
    <div class="parents-header-right">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <?php if (can('parents.creer')): ?>
            <button type="button" class="btn btn-primary" id="btnAddParent">
                <i class="fa-solid fa-user-plus"></i> Nouveau parent
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     BARRE DE FILTRES
     ============================================================ -->
<div class="parents-filters">
    <div class="filter-group">
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchParents" class="search-input" 
                   placeholder="Rechercher par nom, téléphone ou email...">
        </div>
    </div>
    <div class="filter-group">
        <label for="filterLien"><i class="fa-solid fa-link"></i> Lien</label>
        <select id="filterLien">
            <option value="">Tous les liens</option>
            <?php foreach ($liens as $lien): ?>
                <option value="<?= $lien ?>"><?= e($lien) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="button" class="btn-reset-filters" id="btnResetFilters">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     TABLEAU DES PARENTS
     ============================================================ -->
<div class="parents-table-card">
    <div class="table-header-info">
        <span><strong id="totalParents">—</strong> parent(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur le nom pour voir la fiche complète
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Parent</th>
                    <th>Lien</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Enfants</th>
                    <th>Profession</th>
                    <th>Contact</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyParents">
                <!-- Chargé dynamiquement par AJAX -->
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationParents"></div>
    </div>
</div>

<!-- ============================================================
     RÉSUMÉ RAPIDE
     ============================================================ -->
<div class="card mt-4">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon orange">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
            <div>
                <div class="card-header-title">Résumé des parents</div>
                <div class="card-header-subtitle">Statistiques globales</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="stats-summary-grid">
            <div class="stat-summary-card">
                <div class="stat-summary-icon blue"><i class="fa-solid fa-users"></i></div>
                <div class="stat-summary-value blue"><?= number_format($totalParents, 0, ',', ' ') ?></div>
                <div class="stat-summary-label">Total parents</div>
            </div>
            <div class="stat-summary-card">
                <div class="stat-summary-icon blue"><i class="fa-solid fa-mars"></i></div>
                <div class="stat-summary-value blue"><?= number_format($totalPeres, 0, ',', ' ') ?></div>
                <div class="stat-summary-label"><i class="fa-solid fa-mars"></i> Pères</div>
            </div>
            <div class="stat-summary-card">
                <div class="stat-summary-icon pink"><i class="fa-solid fa-venus"></i></div>
                <div class="stat-summary-value pink"><?= number_format($totalMeres, 0, ',', ' ') ?></div>
                <div class="stat-summary-label"><i class="fa-solid fa-venus"></i> Mères</div>
            </div>
            <div class="stat-summary-card">
                <div class="stat-summary-icon orange"><i class="fa-solid fa-user-tie"></i></div>
                <div class="stat-summary-value orange"><?= number_format($totalTuteurs, 0, ',', ' ') ?></div>
                <div class="stat-summary-label"><i class="fa-solid fa-user-tie"></i> Tuteurs</div>
            </div>
            <div class="stat-summary-card">
                <div class="stat-summary-icon green"><i class="fa-solid fa-phone-volume"></i></div>
                <div class="stat-summary-value green"><?= number_format($totalUrgence, 0, ',', ' ') ?></div>
                <div class="stat-summary-label"><i class="fa-solid fa-phone"></i> Contacts urgence</div>
            </div>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>