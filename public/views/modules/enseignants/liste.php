<?php
/**
 * Fichier : public/views/modules/enseignants/liste.php
 * Rôle    : Liste des enseignants avec recherche, filtres et actions
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('enseignants.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EnseignantController.php';
$enseignantController = new EnseignantController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $enseignantController->handleAjax();
}

// Récupérer les spécialités pour le filtre
$specialites = $enseignantController->getSpecialites();

// Statistiques
$totalEnseignants = (int) $db->fetchColumn("SELECT COUNT(*) FROM enseignants WHERE est_actif = 1");
$totalClassesAssignees = (int) $db->fetchColumn("SELECT COUNT(*) FROM enseignants WHERE classe_id IS NOT NULL AND est_actif = 1");
$totalSansClasse = (int) $db->fetchColumn("SELECT COUNT(*) FROM enseignants WHERE classe_id IS NULL AND est_actif = 1");

// Métadonnées
$pageTitle = 'Gestion des enseignants';
$pageIcon  = 'fa-chalkboard-user';
$pageDescription = 'Gérez les enseignants, leurs spécialités et leurs affectations';
$extraCSS = ['modules/enseignants.css'];
$extraJS  = ['modules/enseignants.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="enseignants-header">
    <div class="enseignants-header-left">
        <h4>
            <i class="fa-solid fa-chalkboard-user"></i> Gestion des enseignants
        </h4>
        <p>Gérez le corps professoral, leurs spécialités et leurs classes titulaires.</p>
    </div>
    <div class="enseignants-header-right">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <?php if (can('enseignants.creer')): ?>
            <button type="button" class="btn btn-primary" id="btnAddEnseignant">
                <i class="fa-solid fa-user-plus"></i> Nouvel enseignant
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
<div class="enseignants-filters">
    <div class="filter-group">
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchEnseignants" class="search-input" 
                   placeholder="Rechercher par nom, matricule, spécialité, email...">
        </div>
    </div>
    <div class="filter-group">
        <label for="filterSpecialite"><i class="fa-solid fa-graduation-cap"></i> Spécialité</label>
        <select id="filterSpecialite">
            <option value="">Toutes les spécialités</option>
            <?php foreach ($specialites as $spe): ?>
                <option value="<?= $spe ?>"><?= e($spe) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterStatut"><i class="fa-solid fa-toggle-on"></i> Statut</label>
        <select id="filterStatut">
            <option value="">Tous</option>
            <option value="1">Actifs</option>
            <option value="0">Inactifs</option>
        </select>
    </div>
    <button type="button" class="btn-reset-filters" id="btnResetFilters">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     STATISTIQUES RAPIDES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card teachers">
        <div class="stat-icon green"><i class="fa-solid fa-chalkboard-user"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalEnseignants, 0, ',', ' ') ?></div>
            <div class="stat-label">Enseignants actifs</div>
        </div>
    </div>
    <div class="stat-card classes">
        <div class="stat-icon orange"><i class="fa-solid fa-chalkboard"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalClassesAssignees, 0, ',', ' ') ?></div>
            <div class="stat-label">Classes assignées</div>
        </div>
    </div>
    <div class="stat-card attendance">
        <div class="stat-icon cyan"><i class="fa-solid fa-user-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalSansClasse, 0, ',', ' ') ?></div>
            <div class="stat-label">Sans classe</div>
        </div>
    </div>
</div>

<!-- ============================================================
     TABLEAU DES ENSEIGNANTS
     ============================================================ -->
<div class="enseignants-table-card">
    <div class="table-header-info">
        <span><strong id="totalEnseignants">—</strong> enseignant(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur le nom pour voir la fiche complète
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Enseignant</th>
                    <th>Matricule</th>
                    <th>Spécialité</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Classe</th>
                    <th>Embauche</th>
                    <th>Statut</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyEnseignants">
                <!-- Chargé dynamiquement par AJAX -->
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationEnseignants"></div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>