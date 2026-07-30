<?php
/**
 * Fichier : public/views/dashboard/index.php
 * Rôle    : Tableau de bord principal — Admin
 */

// Activer les erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

// Charger la configuration
require_once __DIR__ . '/../../../src/config/app.php';

// Vérifier l'authentification
requireLogin();

// Charger le contrôleur dashboard
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'DashboardController.php';

// Traitement des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $dashboard = new DashboardController();
    $dashboard->handleAjax();
}

// Récupérer les statistiques pour l'affichage initial
$dashboard = new DashboardController();

// Titre et métadonnées
$pageTitle = 'Tableau de bord';
$pageIcon  = 'fa-gauge-high';
$pageDescription = 'Tableau de bord administratif — Vue d\'ensemble du système';

// CSS et JS additionnels
$extraCSS = [
    'dashboard/dashboard.css',
];
$extraJS = [
    'dashboard/dashboard.js',
];

// Données pour les cartes stats (valeurs par défaut)
$statsData = [
    'totalEleves'      => 0,
    'totalEnseignants' => 0,
    'totalClasses'     => 0,
    'tauxPresence'     => 0,
    'paiementsMois'    => 0,
    'messagesNonLus'   => 0,
    'elevesCeMois'     => 0,
    'elevesMoisDernier'=> 0,
];

try {
    // Récupérer les stats depuis la base
    $anneeActive = $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1") 
                   ?? $db->fetchColumn("SELECT id FROM annees_scolaires WHERE est_active = 1 LIMIT 1") 
                   ?? 1;

    $statsData['totalEleves'] = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :annee AND statut = 'Actif'",
        ['annee' => $anneeActive]
    );

    $statsData['totalEnseignants'] = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM enseignants WHERE est_actif = 1"
    );

    $statsData['totalClasses'] = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM classes WHERE annee_scolaire_id = :annee",
        ['annee' => $anneeActive]
    );

    $absencesJour = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM absences WHERE date_absence = CURDATE() AND type_absence = 'Absence'"
    );
    $presencesJour = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM absences WHERE date_absence = CURDATE() AND type_absence = 'Présent'"
    );
    $totalPresence = $absencesJour + $presencesJour;
    $statsData['tauxPresence'] = $totalPresence > 0 ? round(($presencesJour / $totalPresence) * 100, 1) : 0;

    $statsData['paiementsMois'] = (float) $db->fetchColumn(
        "SELECT COALESCE(SUM(montant_paye), 0) FROM paiements WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())"
    );

    $statsData['messagesNonLus'] = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM messages WHERE destinataire_id = :uid AND est_lu = 0 AND est_supprime_destinataire = 0",
        ['uid' => $_SESSION['utilisateur_id']]
    );

    $statsData['elevesCeMois'] = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM eleves WHERE MONTH(date_inscription) = MONTH(CURDATE()) AND YEAR(date_inscription) = YEAR(CURDATE())"
    );
    $statsData['elevesMoisDernier'] = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM eleves WHERE MONTH(date_inscription) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(date_inscription) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
    );

} catch (Exception $e) {
    error_log('Erreur chargement stats dashboard: ' . $e->getMessage());
}

// Évolution des élèves
$evolutionEleves = 0;
if ($statsData['elevesMoisDernier'] > 0) {
    $evolutionEleves = round((($statsData['elevesCeMois'] - $statsData['elevesMoisDernier']) / $statsData['elevesMoisDernier']) * 100, 1);
}

// Charger le header
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     SECTION 1 : CARTES STATISTIQUES
     ============================================================ -->
<div class="stats-grid">
    <!-- Carte : Total élèves -->
    <div class="stat-card students" onclick="window.location.href='/gestion_scolaire/eleves'">
        <div class="stat-icon blue">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($statsData['totalEleves'], 0, ',', ' ') ?></div>
            <div class="stat-label">Élèves inscrits</div>
            <?php if ($evolutionEleves !== 0): ?>
                <span class="stat-change <?= $evolutionEleves > 0 ? 'up' : 'down' ?>">
                    <i class="fa-solid fa-arrow-<?= $evolutionEleves > 0 ? 'up' : 'down' ?>"></i>
                    <?= abs($evolutionEleves) ?>% ce mois
                </span>
            <?php else: ?>
                <span class="stat-change neutral">
                    <i class="fa-solid fa-minus"></i> Stable
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Carte : Total enseignants -->
    <div class="stat-card teachers" onclick="window.location.href='/gestion_scolaire/enseignants'">
        <div class="stat-icon green">
            <i class="fa-solid fa-chalkboard-user"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($statsData['totalEnseignants'], 0, ',', ' ') ?></div>
            <div class="stat-label">Enseignants actifs</div>
            <span class="stat-change neutral">
                <i class="fa-solid fa-users"></i> Corps professoral
            </span>
        </div>
    </div>

    <!-- Carte : Total classes -->
    <div class="stat-card classes" onclick="window.location.href='/gestion_scolaire/classes'">
        <div class="stat-icon orange">
            <i class="fa-solid fa-chalkboard"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($statsData['totalClasses'], 0, ',', ' ') ?></div>
            <div class="stat-label">Classes actives</div>
            <span class="stat-change neutral">
                <i class="fa-solid fa-school"></i> Année en cours
            </span>
        </div>
    </div>

    <!-- Carte : Taux de présence -->
    <div class="stat-card attendance">
        <div class="stat-icon cyan">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $statsData['tauxPresence'] ?>%</div>
            <div class="stat-label">Taux de présence</div>
            <span class="stat-change <?= $statsData['tauxPresence'] >= 80 ? 'up' : 'down' ?>">
                <i class="fa-solid fa-<?= $statsData['tauxPresence'] >= 80 ? 'arrow-up' : 'arrow-down' ?>"></i>
                Aujourd'hui
            </span>
        </div>
    </div>

    <!-- Carte : Paiements du mois -->
    <div class="stat-card payments">
        <div class="stat-icon purple">
            <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($statsData['paiementsMois'], 0, ',', ' ') ?></div>
            <div class="stat-label">Paiements ce mois</div>
            <span class="stat-change up">
                <i class="fa-solid fa-calendar"></i> <?= moisEnFrancais((int) date('m')) ?>
            </span>
        </div>
    </div>

    <!-- Carte : Messages non lus -->
    <div class="stat-card messages" onclick="window.location.href='/gestion_scolaire/messages'">
        <div class="stat-icon red">
            <i class="fa-solid fa-envelope"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($statsData['messagesNonLus'], 0, ',', ' ') ?></div>
            <div class="stat-label">Messages non lus</div>
            <?php if ($statsData['messagesNonLus'] > 0): ?>
                <span class="stat-change down">
                    <i class="fa-solid fa-exclamation-circle"></i> À lire
                </span>
            <?php else: ?>
                <span class="stat-change up">
                    <i class="fa-solid fa-check-circle"></i> Tout lu
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     SECTION 2 : GRAPHIQUES — LIGNE 1
     ============================================================ -->
<div class="charts-grid">
    <!-- Graphique 1 : Élèves par classe -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon blue">
                    <i class="fa-solid fa-chart-bar"></i>
                </div>
                <div>
                    <div class="card-header-title">Élèves par classe</div>
                    <div class="card-header-subtitle">Répartition des effectifs</div>
                </div>
            </div>
            <div class="card-header-actions">
                <button class="btn-card-action" onclick="window.location.href='/gestion_scolaire/eleves'">
                    <i class="fa-solid fa-eye"></i> Voir tout
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="skeleton-chart-eleves" class="skeleton skeleton-chart" style="height:300px;border-radius:8px;"></div>
            <div class="chart-container" style="display:none;">
                <canvas id="chartElevesClasse"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique 2 : Paiements mensuels -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon green">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <div class="card-header-title">Paiements mensuels</div>
                    <div class="card-header-subtitle">Évolution sur l'année <?= date('Y') ?></div>
                </div>
            </div>
            <div class="card-header-actions">
                <button class="btn-card-action" onclick="window.location.href='/gestion_scolaire/paiements'">
                    <i class="fa-solid fa-eye"></i> Détails
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="skeleton-chart-paiements" class="skeleton skeleton-chart" style="height:300px;border-radius:8px;"></div>
            <div class="chart-container" style="display:none;">
                <canvas id="chartPaiements"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SECTION 3 : GRAPHIQUES — LIGNE 2
     ============================================================ -->
<div class="charts-grid">
    <!-- Graphique 3 : Taux de présence par classe -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon cyan">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <div class="card-header-title">Taux de présence par classe</div>
                    <div class="card-header-subtitle">Présence du jour</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="skeleton-chart-presences" class="skeleton skeleton-chart" style="height:300px;border-radius:8px;"></div>
            <div class="chart-container" style="display:none;">
                <canvas id="chartPresences"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique 4 : Répartition filles/garçons -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon purple">
                    <i class="fa-solid fa-venus-mars"></i>
                </div>
                <div>
                    <div class="card-header-title">Répartition par sexe</div>
                    <div class="card-header-subtitle">Tous les élèves actifs</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="skeleton-chart-sexe" class="skeleton skeleton-chart" style="height:300px;border-radius:8px;"></div>
            <div class="chart-container" style="display:none;">
                <canvas id="chartSexe"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SECTION 4 : GRAPHIQUE RÉSULTATS + ACTIVITÉS
     ============================================================ -->
<div class="charts-grid">
    <!-- Graphique 5 : Résultats par trimestre -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon orange">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                </div>
                <div>
                    <div class="card-header-title">Résultats par trimestre</div>
                    <div class="card-header-subtitle">Réussite, ajournement, échec</div>
                </div>
            </div>
            <div class="card-header-actions">
                <button class="btn-card-action" onclick="window.location.href='/gestion_scolaire/resultats'">
                    <i class="fa-solid fa-eye"></i> Voir résultats
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="skeleton-chart-resultats" class="skeleton skeleton-chart" style="height:300px;border-radius:8px;"></div>
            <div class="chart-container" style="display:none;">
                <canvas id="chartResultats"></canvas>
            </div>
        </div>
    </div>

    <!-- Activités récentes -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon red">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <div class="card-header-title">Activités récentes</div>
                    <div class="card-header-subtitle">Dernières actions du système</div>
                </div>
            </div>
            <div class="card-header-actions">
                <button class="btn-card-action" onclick="window.location.href='/gestion_scolaire/logs'">
                    <i class="fa-solid fa-list"></i> Tout voir
                </button>
            </div>
        </div>
        <div class="card-body no-padding">
            <div id="skeleton-table-activites" class="skeleton skeleton-table" style="height:300px;border-radius:8px;margin:16px;"></div>
            <div class="table-responsive" style="display:none;">
                <table class="table" id="tableActivites">
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SECTION 5 : TABLEAUX
     ============================================================ -->
<div class="bottom-grid">
    <!-- Derniers élèves inscrits -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon blue">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <div class="card-header-title">Derniers élèves inscrits</div>
                    <div class="card-header-subtitle">5 dernières inscriptions</div>
                </div>
            </div>
            <div class="card-header-actions">
                <button class="btn-card-action" onclick="window.location.href='/gestion_scolaire/eleves'">
                    <i class="fa-solid fa-list"></i> Tous les élèves
                </button>
            </div>
        </div>
        <div class="card-body no-padding">
            <div id="skeleton-table-eleves" class="skeleton skeleton-table" style="height:250px;border-radius:8px;margin:16px;"></div>
            <div class="table-responsive" style="display:none;">
                <table class="table" id="tableDerniersEleves">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Élève</th>
                            <th>Sexe</th>
                            <th>Date inscription</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Prochains anniversaires -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon orange">
                    <i class="fa-solid fa-cake-candles"></i>
                </div>
                <div>
                    <div class="card-header-title">Prochains anniversaires</div>
                    <div class="card-header-subtitle">Dans les 7 prochains jours</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div id="skeleton-table-anniversaires" class="skeleton skeleton-table" style="height:250px;border-radius:8px;margin:16px;"></div>
            <div class="table-responsive" style="display:none;">
                <table class="table" id="tableAnniversaires">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Date de naissance</th>
                            <th>Âge</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Charger le footer
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>