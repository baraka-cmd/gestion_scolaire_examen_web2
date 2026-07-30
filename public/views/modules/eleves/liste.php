<?php
/**
 * Fichier : public/views/modules/eleves/liste.php
 * Rôle    : Liste des élèves avec recherche avancée, filtres et actions
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('eleves.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EleveController.php';
$eleveController = new EleveController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $eleveController->handleAjax();
}

// Récupérer les classes pour le filtre
$classes = $eleveController->getClasses();

// Métadonnées
$pageTitle = 'Gestion des élèves';
$pageIcon  = 'fa-graduation-cap';
$pageDescription = 'Gérez les élèves, leurs inscriptions et leurs informations';
$extraCSS = ['modules/eleves.css'];
$extraJS  = ['modules/eleves.js'];


// Statistiques pour le résumé
$totalActifs = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) AND statut = 'Actif'"
);
$totalGarcons = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions i 
     INNER JOIN eleves e ON i.eleve_id = e.id 
     WHERE i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) 
     AND i.statut = 'Actif' AND e.sexe = 'M'"
);
$totalFilles = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions i 
     INNER JOIN eleves e ON i.eleve_id = e.id 
     WHERE i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) 
     AND i.statut = 'Actif' AND e.sexe = 'F'"
);
$nouvellesInscriptions = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE MONTH(date_inscription) = MONTH(CURDATE()) AND YEAR(date_inscription) = YEAR(CURDATE())"
);

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="eleves-header">
    <div class="eleves-header-left">
        <h4>
            <i class="fa-solid fa-graduation-cap"></i> Gestion des élèves
        </h4>
        <p>Gérez les élèves, leurs inscriptions, leurs parents et leurs informations scolaires.</p>
    </div>
    <div class="eleves-header-right">
        <?php if (can('eleves.importer')): ?>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnImport">
                <i class="fa-solid fa-file-import"></i> Importer CSV
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnExport">
            <i class="fa-solid fa-file-export"></i> Exporter
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <?php if (can('eleves.creer')): ?>
            <button type="button" class="btn btn-primary" id="btnAddEleve">
                <i class="fa-solid fa-user-plus"></i> Nouvel élève
            </button>
        <?php endif; ?>
    </div>
</div>


<!-- ============================================================
     RÉSUMÉ DES EFFECTIFS (AVANT LE TABLEAU)
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card students">
        <div class="stat-icon blue"><i class="fa-solid fa-graduation-cap"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalActifs, 0, ',', ' ') ?></div>
            <div class="stat-label">Total élèves</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);"><i class="fa-solid fa-mars"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalGarcons, 0, ',', ' ') ?></div>
            <div class="stat-label">Garçons</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#ec4899,#f472b6);"><i class="fa-solid fa-venus"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalFilles, 0, ',', ' ') ?></div>
            <div class="stat-label">Filles</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fa-solid fa-user-plus"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $nouvellesInscriptions ?></div>
            <div class="stat-label">Nouveaux ce mois</div>
        </div>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     BARRE DE FILTRES
     ============================================================ -->
<div class="eleves-filters">
    <div class="filter-group">
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchEleves" class="search-input" 
                   placeholder="Rechercher par nom, prénom ou matricule...">
        </div>
    </div>
    <div class="filter-group">
        <label for="filterClasse"><i class="fa-solid fa-chalkboard"></i> Classe</label>
        <select id="filterClasse">
            <option value="">Toutes les classes</option>
            <?php foreach ($classes as $classe): ?>
                <option value="<?= $classe['id'] ?>"><?= e($classe['nom']) ?> (Niv. <?= $classe['niveau'] ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterSexe"><i class="fa-solid fa-venus-mars"></i> Sexe</label>
        <select id="filterSexe">
            <option value="">Tous</option>
            <option value="M">Garçons</option>
            <option value="F">Filles</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterStatut"><i class="fa-solid fa-toggle-on"></i> Statut</label>
        <select id="filterStatut">
            <option value="">Actifs uniquement</option>
            <option value="1">Actifs</option>
            <option value="0">Inactifs</option>
        </select>
    </div>
    <button type="button" class="btn-reset-filters" id="btnResetFilters">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>


<!-- ============================================================
     TABLEAU DES ÉLÈVES
     ============================================================ -->
<div class="eleves-table-card">
    <div class="table-header-info">
        <span><strong id="totalEleves">—</strong> élève(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur le nom pour voir la fiche complète
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Élève</th>
                    <th>Matricule</th>
                    <th>Sexe</th>
                    <th>Né(e) le</th>
                    <th>Âge</th>
                    <th>Classe</th>
                    <th>Téléphone</th>
                    <th>Statut</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyEleves">
                <!-- Chargé dynamiquement par AJAX -->
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationEleves"></div>
    </div>
</div>


<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>