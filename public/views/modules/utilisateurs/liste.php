<?php
/**
 * Fichier : public/views/modules/utilisateurs/liste.php
 * Rôle    : Liste des utilisateurs avec recherche, filtres et actions
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';

requireLogin();
requirePermission('utilisateurs.consulter');

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'UtilisateurController.php';
$userController = new UtilisateurController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $userController->handleAjax();
}

// Récupérer les rôles pour le filtre
$roles = $userController->getRoles();

// Métadonnées
$pageTitle = 'Gestion des utilisateurs';
$pageIcon  = 'fa-users-gear';
$pageDescription = 'Gérez les comptes utilisateurs du système';
$extraCSS = ['modules/utilisateurs.css'];
$extraJS  = ['modules/utilisateurs.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="module-header">
    <div class="module-header-left">
        <h4>
            <i class="fa-solid fa-users-gear"></i> Gestion des utilisateurs
        </h4>
        <p>Gérez les comptes utilisateurs, leurs rôles et leurs accès au système.</p>
    </div>
    <div class="module-header-right">
        <?php if (can('utilisateurs.creer')): ?>
            <a href="/gestion_scolaire/utilisateurs/ajouter" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i> Nouvel utilisateur
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportPDF()">
            <i class="fa-solid fa-file-pdf"></i> PDF
        </button>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     BARRE DE FILTRES
     ============================================================ -->
<div class="filters-bar">
    <div class="filter-group">
        <div class="search-wrapper" style="position:relative;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchUsers" class="search-input" placeholder="Rechercher par nom, email, login...">
        </div>
    </div>
    <div class="filter-group">
        <label for="filterRole"><i class="fa-solid fa-user-tag"></i> Rôle</label>
        <select id="filterRole">
            <option value="">Tous les rôles</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role['id'] ?>"><?= e($role['nom']) ?></option>
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
     TABLEAU DES UTILISATEURS
     ============================================================ -->
<div class="user-table-card">
    <div class="table-header-info">
        <span><strong id="totalUsers">—</strong> utilisateur(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur un nom pour voir la fiche détaillée
        </span>
    </div>
    <div class="table-responsive">
        <table class="table" id="tableUsers">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Login</th>
                    <th>Dernière connexion</th>
                    <th>Statut</th>
                    <th style="width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyUsers">
                <!-- Chargé dynamiquement -->
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationUsers"></div>
    </div>
</div>



<script>
function exportPDF() {
    window.print();
}
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>