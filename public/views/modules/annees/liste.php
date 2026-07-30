<?php
/**
 * Fichier : public/views/modules/annees/liste.php
 * Rôle    : Liste et gestion des années scolaires
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('parametres.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AnneeController.php';
$anneeController = new AnneeController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $anneeController->handleAjax();
}

// Récupérer toutes les années
$annees = $anneeController->getAllAnnees();

// Récupérer l'année active
$anneeActive = $anneeController->getAnneeActive();

// Métadonnées
$pageTitle = 'Gestion des années scolaires';
$pageIcon  = 'fa-calendar-alt';
$pageDescription = 'Gérez les années scolaires, activez et clôturez les périodes académiques';
$extraCSS = ['modules/annees.css'];
$extraJS  = ['modules/annees.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="annees-header">
    <div class="annees-header-left">
        <h4>
            <i class="fa-solid fa-calendar-alt"></i> Gestion des années scolaires
        </h4>
        <p>Gérez les périodes académiques, activez l'année en cours et clôturez les années terminées.</p>
    </div>
    <div class="annees-header-right">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <?php if (can('parametres.modifier')): ?>
            <a href="/gestion_scolaire/annees-scolaires/ajouter" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Nouvelle année
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     ALERTE ANNÉE ACTIVE
     ============================================================ -->
<?php if ($anneeActive): ?>
<div class="annee-active-alert">
    <i class="fa-solid fa-circle-check"></i>
    <div>
        <strong>Année scolaire active : <?= e($anneeActive['libelle']) ?></strong>
        <br>
        <span>
            Du <?= formatDate($anneeActive['date_debut'], 'd/m/Y') ?> 
            au <?= formatDate($anneeActive['date_fin'], 'd/m/Y') ?>
        </span>
    </div>
</div>
<?php else: ?>
<div class="annee-active-alert" style="background:linear-gradient(135deg,#fef3c7,#fffbeb);border-color:#fde68a;">
    <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;"></i>
    <div>
        <strong style="color:#92400e;">Aucune année scolaire active</strong>
        <br>
        <span style="color:#78350f;">Veuillez activer une année scolaire pour commencer.</span>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     LISTE DES ANNÉES
     ============================================================ -->
<?php if (empty($annees)): ?>
    <div class="empty-state" style="padding:40px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-calendar-alt"></i>
        <p>Aucune année scolaire trouvée</p>
        <?php if (can('parametres.modifier')): ?>
            <a href="/gestion_scolaire/annees-scolaires/ajouter" class="btn btn-primary btn-sm mt-3">
                <i class="fa-solid fa-plus"></i> Créer une année
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($annees as $annee): ?>
        <?php
        $isActive = $annee['est_active'] == 1;
        $isCloturee = $annee['est_cloturee'] == 1;
        $cardClass = $isActive ? 'active' : ($isCloturee ? 'cloturee' : '');
        $iconClass = $isActive ? 'active' : ($isCloturee ? 'cloturee' : 'inactive');
        $iconFA = $isActive ? 'fa-check-circle' : ($isCloturee ? 'fa-lock' : 'fa-clock');
        $statusBadge = $isActive ? 'active' : ($isCloturee ? 'cloturee' : 'inactive');
        $statusText = $isActive ? 'Année active' : ($isCloturee ? 'Clôturée' : 'Inactive');
        $statusIcon = $isActive ? 'fa-circle-check' : ($isCloturee ? 'fa-lock' : 'fa-circle');
        
        // Récupérer les trimestres
        $trimestres = $db->fetchAll(
            "SELECT * FROM trimestres WHERE annee_scolaire_id = :aid ORDER BY numero",
            ['aid' => $annee['id']]
        );
        ?>

        <div class="annee-card <?= $cardClass ?>">
            <div class="annee-card-header">
                <div class="annee-card-title">
                    <div class="annee-card-icon <?= $iconClass ?>">
                        <i class="fa-solid <?= $iconFA ?>"></i>
                    </div>
                    <div>
                        <div class="annee-card-name"><?= e($annee['libelle']) ?></div>
                        <div class="annee-card-dates">
                            <i class="fa-solid fa-calendar"></i>
                            <?= formatDate($annee['date_debut'], 'd/m/Y') ?>
                            <i class="fa-solid fa-arrow-right" style="font-size:0.6rem;"></i>
                            <?= formatDate($annee['date_fin'], 'd/m/Y') ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span class="annee-status-badge <?= $statusBadge ?>">
                        <i class="fa-solid <?= $statusIcon ?>"></i> <?= $statusText ?>
                    </span>

                    <?php if (can('parametres.modifier')): ?>
                    <div class="annee-card-actions">
                        <?php if (!$isActive && !$isCloturee): ?>
                            <button class="btn btn-sm btn-success" onclick="activerAnnee(<?= $annee['id'] ?>, '<?= e($annee['libelle']) ?>')" title="Activer cette année">
                                <i class="fa-solid fa-check-circle"></i> Activer
                            </button>
                        <?php endif; ?>

                        <?php if (!$isCloturee): ?>
                            <a href="/gestion_scolaire/annees-scolaires/modifier/<?= $annee['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($isActive && !$isCloturee): ?>
                            <button class="btn btn-sm btn-outline-warning" onclick="cloturerAnnee(<?= $annee['id'] ?>, '<?= e($annee['libelle']) ?>')" title="Clôturer cette année">
                                <i class="fa-solid fa-lock"></i> Clôturer
                            </button>
                        <?php endif; ?>

                        <?php if ($annee['nb_inscriptions'] == 0 && $annee['nb_classes'] == 0): ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="supprimerAnnee(<?= $annee['id'] ?>, '<?= e($annee['libelle']) ?>')" title="Supprimer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="annee-card-body">
                <!-- Trimestres -->
                <?php if (!empty($trimestres)): ?>
                    <div class="trimestres-grid">
                        <?php foreach ($trimestres as $trimestre): ?>
                            <div class="trimestre-item <?= $trimestre['est_actif'] ? 'actif' : '' ?>">
                                <div class="trimestre-numero">Trimestre <?= $trimestre['numero'] ?></div>
                                <div class="trimestre-libelle"><?= e($trimestre['libelle']) ?></div>
                                <div class="trimestre-dates">
                                    <?= formatDate($trimestre['date_debut'], 'd/m/Y') ?> → <?= formatDate($trimestre['date_fin'], 'd/m/Y') ?>
                                </div>
                                <?php if ($trimestre['est_actif']): ?>
                                    <span class="trimestre-actif-badge">Actif</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="font-size:0.82rem;color:var(--color-gray-500);margin-bottom:16px;">
                        <i class="fa-solid fa-info-circle"></i> Aucun trimestre configuré.
                    </p>
                <?php endif; ?>

                <!-- Stats -->
                <div class="annee-stats">
                    <div class="annee-stat">
                        <i class="fa-solid fa-chalkboard"></i>
                        <span><strong><?= $annee['nb_classes'] ?? 0 ?></strong> classe(s)</span>
                    </div>
                    <div class="annee-stat">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span><strong><?= $annee['nb_inscriptions'] ?? 0 ?></strong> élève(s) inscrit(s)</span>
                    </div>
                    <div class="annee-stat">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span><strong><?= count($trimestres) ?></strong> trimestre(s)</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>