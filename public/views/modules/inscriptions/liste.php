<?php
/**
 * Fichier : public/views/modules/inscriptions/liste.php
 * Rôle    : Liste des inscriptions avec filtres et actions
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('inscriptions.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'InscriptionController.php';
$inscriptionController = new InscriptionController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $inscriptionController->handleAjax();
}

// Récupérer les données pour les filtres
$classes = $inscriptionController->getClassesActives();
$annees = $inscriptionController->getAnneesScolaires();

// Statistiques
$totalActifs = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) AND statut = 'Actif'"
);
$totalRedoublants = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) AND statut = 'Redoublant'"
);
$totalTransferes = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) AND statut = 'Transféré'"
);

// Métadonnées
$pageTitle = 'Gestion des inscriptions';
$pageIcon  = 'fa-file-pen';
$pageDescription = 'Gérez les inscriptions, les changements de classe et les réinscriptions';
$extraCSS = ['modules/inscriptions.css'];
$extraJS  = ['modules/inscriptions.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="inscriptions-header">
    <div class="inscriptions-header-left">
        <h4>
            <i class="fa-solid fa-file-pen"></i> Gestion des inscriptions
        </h4>
        <p>Gérez les inscriptions, les changements de classe et les réinscriptions.</p>
    </div>
    <div class="inscriptions-header-right">
        <?php if (can('inscriptions.creer')): ?>
            <a href="/gestion_scolaire/inscriptions/nouvelle" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-user-plus"></i> Nouvelle inscription
            </a>
            <a href="/gestion_scolaire/inscriptions/reinscription" class="btn btn-success btn-sm">
                <i class="fa-solid fa-rotate"></i> Réinscription massive
            </a>
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
     STATISTIQUES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-user-check"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalActifs, 0, ',', ' ') ?></div>
            <div class="stat-label">Inscriptions actives</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-rotate"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalRedoublants, 0, ',', ' ') ?></div>
            <div class="stat-label">Redoublants</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalTransferes, 0, ',', ' ') ?></div>
            <div class="stat-label">Transferts</div>
        </div>
    </div>
</div>

<!-- ============================================================
     BARRE DE FILTRES
     ============================================================ -->
<div class="inscriptions-filters">
    <div class="filter-group">
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInscriptions" class="search-input" 
                   placeholder="Rechercher par nom, prénom ou matricule...">
        </div>
    </div>
    <div class="filter-group">
        <label for="filterClasse"><i class="fa-solid fa-chalkboard"></i> Classe</label>
        <select id="filterClasse">
            <option value="">Toutes les classes</option>
            <?php foreach ($classes as $classe): ?>
                <option value="<?= $classe['id'] ?>"><?= e($classe['nom']) ?> (<?= $classe['nb_inscrits'] ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterAnnee"><i class="fa-solid fa-calendar-alt"></i> Année</label>
        <select id="filterAnnee">
            <option value="">Année active</option>
            <?php foreach ($annees as $annee): ?>
                <option value="<?= $annee['id'] ?>" <?= $annee['est_active'] ? 'selected' : '' ?>>
                    <?= e($annee['libelle']) ?> <?= $annee['est_active'] ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterStatut"><i class="fa-solid fa-toggle-on"></i> Statut</label>
        <select id="filterStatut">
            <option value="">Tous</option>
            <option value="Actif">Actifs</option>
            <option value="Redoublant">Redoublants</option>
            <option value="Transféré">Transférés</option>
            <option value="Exclu">Exclus</option>
            <option value="Diplômé">Diplômés</option>
            <option value="Abandon">Abandons</option>
        </select>
    </div>
    <button type="button" class="btn-reset-filters" id="btnResetFilters">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     TABLEAU DES INSCRIPTIONS
     ============================================================ -->
<div class="inscriptions-table-card">
    <div class="table-header-info">
        <span><strong id="totalInscriptions">—</strong> inscription(s) trouvée(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur <i class="fa-solid fa-clock-rotate-left"></i> pour voir l'historique
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>Année</th>
                    <th>Date inscription</th>
                    <th>Statut</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyInscriptions">
                <!-- Chargé dynamiquement -->
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationInscriptions"></div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>