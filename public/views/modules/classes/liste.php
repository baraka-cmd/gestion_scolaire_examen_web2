<?php
/**
 * Fichier : public/views/modules/classes/liste.php
 * Rôle    : Liste des classes avec recherche, filtres et double vue
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('classes.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ClasseController.php';
$classeController = new ClasseController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $classeController->handleAjax();
}

// Récupérer les années scolaires pour le filtre
$anneesScolaires = $classeController->getAnneesScolaires();

// Récupérer l'année active
$anneeActive = $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1") ?? 1;

// Métadonnées
$pageTitle = 'Gestion des classes';
$pageIcon  = 'fa-chalkboard';
$pageDescription = 'Gérez les classes, les salles et les titulaires';
$extraCSS = ['modules/classes.css'];
$extraJS  = ['modules/classes.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="classes-header">
    <div class="classes-header-left">
        <h4>
            <i class="fa-solid fa-chalkboard"></i> Gestion des classes
        </h4>
        <p>Gérez les classes, leurs salles, capacités et enseignants titulaires.</p>
    </div>
    <div class="classes-header-right">
        <!-- Toggle vue -->
        <select id="viewMode" class="form-control" style="width:auto;min-width:130px;padding:8px 14px;">
            <option value="cards">Vue cartes</option>
            <option value="table">Vue tableau</option>
        </select>

        <?php if (can('classes.creer')): ?>
            <button type="button" class="btn btn-primary" id="btnAddClasse">
                <i class="fa-solid fa-plus"></i> Nouvelle classe
            </button>
        <?php endif; ?>

        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
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
<div class="classes-filters">
    <div class="filter-group">
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchClasses" class="search-input" placeholder="Rechercher une classe ou une salle...">
        </div>
    </div>
    <div class="filter-group">
        <label for="filterAnnee"><i class="fa-solid fa-calendar-alt"></i> Année</label>
        <select id="filterAnnee">
            <option value="">Toutes les années</option>
            <?php foreach ($anneesScolaires as $annee): ?>
                <option value="<?= $annee['id'] ?>" <?= $annee['id'] == $anneeActive ? 'selected' : '' ?>>
                    <?= e($annee['libelle']) ?> <?= $annee['est_active'] ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="button" class="btn-reset-filters" id="btnResetFilters">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     VUE CARTES
     ============================================================ -->
<div class="classes-grid" id="classesContainer">
    <!-- Chargé dynamiquement -->
    <div class="skeleton-classe-card"></div>
    <div class="skeleton-classe-card"></div>
    <div class="skeleton-classe-card"></div>
</div>

<!-- ============================================================
     VUE TABLEAU (cachée par défaut)
     ============================================================ -->
<div class="classes-table-card" id="tableClasses" style="display:none;">
    <div class="table-header-info" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color-light);">
        <span><strong id="totalClasses">—</strong> classe(s) trouvée(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur un nom pour voir les détails
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Niveau</th>
                    <th>Classe</th>
                    <th>Salle</th>
                    <th>Titulaire</th>
                    <th>Effectif</th>
                    <th>Statut</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyClasses">
                <!-- Chargé dynamiquement -->
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationClasses"></div>
    </div>
</div>

<!-- ============================================================
     PAGINATION (VUE CARTES)
     ============================================================ -->
<div class="pagination-wrapper" style="justify-content:center;">
    <div class="pagination" id="paginationClassesCards"></div>
</div>

<!-- ============================================================
     MODALE CONFIRMATION SUPPRESSION (incluse dans main.js)
     ============================================================ -->

<script>
// Synchroniser les paginations
const originalRenderPagination = window.renderPagination;
if (originalRenderPagination) {
    // La pagination est déjà gérée dans classes.js
}

// Ajuster l'affichage selon la vue sélectionnée
document.getElementById('viewMode').addEventListener('change', function() {
    const cardsView = document.getElementById('classesContainer');
    const tableView = document.getElementById('tableClasses');
    const paginationCards = document.getElementById('paginationClassesCards');

    if (this.value === 'table') {
        if (cardsView) cardsView.style.display = 'none';
        if (tableView) tableView.style.display = 'block';
        if (paginationCards) paginationCards.style.display = 'none';
    } else {
        if (cardsView) cardsView.style.display = 'grid';
        if (tableView) tableView.style.display = 'none';
        if (paginationCards) paginationCards.style.display = 'flex';
    }
});
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>