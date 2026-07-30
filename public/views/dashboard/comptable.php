<?php
/**
 * Fichier : public/views/dashboard/comptable.php
 * Rôle    : Tableau de bord — Comptable
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../src/config/app.php';
requireLogin();

if (!hasRole(['Comptable', 'Admin', 'Directeur'])) {
    redirect('/gestion_scolaire/dashboard');
}

// ============================================================
// STATISTIQUES FINANCIÈRES
// ============================================================

$anneeActive = $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1") ?? 1;

$totalAttendu = (float) $db->fetchColumn(
    "SELECT COALESCE(SUM(f.montant), 0)
     FROM frais_scolaires f
     WHERE f.annee_scolaire_id = :annee1 AND f.est_obligatoire = 1",
    ['annee1' => $anneeActive]
) * (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :annee2 AND statut = 'Actif'",
    ['annee2' => $anneeActive]
);

$totalEncaissement = (float) $db->fetchColumn("SELECT COALESCE(SUM(montant_paye), 0) FROM paiements");

$totalImpayes = (float) $db->fetchColumn(
    "SELECT COALESCE(SUM(montant_total - montant_paye), 0) FROM paiements WHERE statut != 'Payé'"
);

$paiementsMois = (float) $db->fetchColumn(
    "SELECT COALESCE(SUM(montant_paye), 0) FROM paiements 
     WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())"
);

$elevesEnRegle = (int) $db->fetchColumn(
    "SELECT COUNT(DISTINCT eleve_id) FROM paiements 
     WHERE eleve_id NOT IN (
         SELECT DISTINCT eleve_id FROM paiements WHERE statut != 'Payé'
     )"
);

$elevesImpayes = (int) $db->fetchColumn(
    "SELECT COUNT(DISTINCT eleve_id) FROM paiements WHERE statut != 'Payé'"
);

$tauxRecouvrement = $totalAttendu > 0 ? round(($totalEncaissement / $totalAttendu) * 100, 1) : 0;

// ============================================================
// DONNÉES GRAPHIQUES
// ============================================================

$paiementsParMois = $db->fetchAll(
    "SELECT MONTH(date_paiement) as mois, COALESCE(SUM(montant_paye), 0) as total
     FROM paiements
     WHERE YEAR(date_paiement) = YEAR(CURDATE())
     GROUP BY MONTH(date_paiement)
     ORDER BY mois"
);

$paiementsParClasse = $db->fetchAll(
    "SELECT c.nom, COALESCE(SUM(p.montant_paye), 0) as total
     FROM classes c
     INNER JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
     LEFT JOIN paiements p ON i.eleve_id = p.eleve_id
     WHERE c.annee_scolaire_id = :annee3
     GROUP BY c.id, c.nom
     ORDER BY c.niveau",
    ['annee3' => $anneeActive]
);

$paiementsParMode = $db->fetchAll(
    "SELECT mode_paiement, COUNT(*) as nombre, COALESCE(SUM(montant_paye), 0) as total
     FROM paiements
     WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())
     GROUP BY mode_paiement
     ORDER BY total DESC"
);

// ============================================================
// TABLEAUX
// ============================================================

$derniersPaiements = $db->fetchAll(
    "SELECT p.id, p.montant_paye, p.date_paiement, p.mode_paiement, p.statut,
            e.nom, e.prenom, e.matricule, f.libelle as frais_libelle
     FROM paiements p
     INNER JOIN eleves e ON p.eleve_id = e.id
     INNER JOIN frais_scolaires f ON p.frais_scolaire_id = f.id
     ORDER BY p.date_paiement DESC
     LIMIT 10"
);

$topImpayes = $db->fetchAll(
    "SELECT e.id, e.nom, e.prenom, e.matricule, c.nom as classe_nom,
            COALESCE(SUM(p.montant_total - p.montant_paye), 0) as total_du
     FROM paiements p
     INNER JOIN eleves e ON p.eleve_id = e.id
     INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     INNER JOIN classes c ON i.classe_id = c.id
     WHERE p.statut != 'Payé'
     GROUP BY e.id, e.nom, e.prenom, e.matricule, c.nom
     ORDER BY total_du DESC
     LIMIT 10"
);

// ============================================================
// MÉTADONNÉES
// ============================================================

$pageTitle = 'Tableau de bord — Comptable';
$pageIcon  = 'fa-gauge-high';
$pageDescription = 'Gestion financière';
$extraCSS = ['dashboard/dashboard.css'];
$extraJS  = ['dashboard/dashboard.js'];

// Préparer les données JavaScript pour les graphiques
$moisLabels = json_encode(['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']);
$paiementsMensuelsData = array_fill(0, 12, 0);
foreach ($paiementsParMois as $p) {
    $paiementsMensuelsData[$p['mois'] - 1] = (float) $p['total'];
}
$paiementsMensuelsJSON = json_encode(array_values($paiementsMensuelsData));
$labelsClasseJSON = json_encode(array_column($paiementsParClasse, 'nom'));
$dataClasseJSON = json_encode(array_map('floatval', array_column($paiementsParClasse, 'total')));
$labelsModeJSON = json_encode(array_column($paiementsParMode, 'mode_paiement'));
$dataModeJSON = json_encode(array_map('floatval', array_column($paiementsParMode, 'total')));

// Script inline pour les graphiques (exécuté APRÈS Chart.js dans footer.php)
$inlineJS = "
document.addEventListener('DOMContentLoaded', function() {
    var ctx1 = document.getElementById('chartPaiementsMensuels');
    if (ctx1) {
        var gradient1 = ctx1.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
        gradient1.addColorStop(1, 'rgba(16, 185, 129, 0)');
        new Chart(ctx1.getContext('2d'), {
            type: 'line',
            data: {
                labels: {$moisLabels},
                datasets: [{
                    label: 'Paiements (\$)',
                    data: {$paiementsMensuelsJSON},
                    borderColor: '#10b981',
                    backgroundColor: gradient1,
                    borderWidth: 3,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(v) { return '\$ ' + v.toLocaleString(); } }
                    }
                }
            }
        });
    }

    var ctx2 = document.getElementById('chartPaiementsClasse');
    if (ctx2) {
        new Chart(ctx2.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {$labelsClasseJSON},
                datasets: [{
                    label: 'Total encaissé (\$)',
                    data: {$dataClasseJSON},
                    backgroundColor: ['#2c3e90', '#3498db', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                    borderRadius: 8,
                    maxBarThickness: 50,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(v) { return '\$ ' + v.toLocaleString(); } }
                    }
                }
            }
        });
    }

    var ctx3 = document.getElementById('chartModePaiement');
    if (ctx3) {
        new Chart(ctx3.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {$labelsModeJSON},
                datasets: [{
                    data: {$dataModeJSON},
                    backgroundColor: ['#2c3e90', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
                }
            }
        });
    }
});
";

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- En-tête -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-calculator text-primary me-2"></i>Tableau de bord financier
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            <i class="fa-solid fa-calendar me-1"></i> Année scolaire <?= date('Y') ?>-<?= date('Y')+1 ?>
            <span class="mx-2">|</span>
            <i class="fa-solid fa-money-bill-wave me-1"></i> Taux de recouvrement : <strong><?= $tauxRecouvrement ?>%</strong>
        </p>
    </div>
    <div>
        <a href="/gestion_scolaire/paiements/enregistrer" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Nouveau paiement
        </a>
        <a href="/gestion_scolaire/paiements/factures" class="btn btn-outline-primary btn-sm ms-2">
            <i class="fa-solid fa-receipt"></i> Factures
        </a>
    </div>
</div>

<!-- Cartes statistiques -->
<div class="stats-grid">
    <div class="stat-card payments">
        <div class="stat-icon purple">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($totalEncaissement, 0, ',', ' ') ?></div>
            <div class="stat-label">Total encaissé</div>
            <span class="stat-change up">
                <i class="fa-solid fa-chart-line"></i> Sur $ <?= number_format($totalAttendu, 0, ',', ' ') ?> attendus
            </span>
        </div>
    </div>

    <div class="stat-card messages">
        <div class="stat-icon red">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($totalImpayes, 0, ',', ' ') ?></div>
            <div class="stat-label">Total impayés</div>
            <span class="stat-change down">
                <i class="fa-solid fa-users"></i> <?= $elevesImpayes ?> élève(s) concerné(s)
            </span>
        </div>
    </div>

    <div class="stat-card attendance">
        <div class="stat-icon cyan">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($paiementsMois, 0, ',', ' ') ?></div>
            <div class="stat-label">Encaissé ce mois</div>
            <span class="stat-change up">
                <i class="fa-solid fa-calendar"></i> <?= moisEnFrancais((int) date('m')) ?> <?= date('Y') ?>
            </span>
        </div>
    </div>

    <div class="stat-card teachers">
        <div class="stat-icon green">
            <i class="fa-solid fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $elevesEnRegle ?></div>
            <div class="stat-label">Élèves en règle</div>
            <span class="stat-change <?= $tauxRecouvrement >= 80 ? 'up' : 'down' ?>">
                <i class="fa-solid fa-<?= $tauxRecouvrement >= 80 ? 'arrow-up' : 'arrow-down' ?>"></i>
                <?= $tauxRecouvrement ?>% de recouvrement
            </span>
        </div>
    </div>
</div>

<!-- Ligne 1 : Graphiques -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon green">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <div class="card-header-title">Évolution des paiements</div>
                    <div class="card-header-subtitle">Par mois — <?= date('Y') ?></div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container large">
                <canvas id="chartPaiementsMensuels"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon blue">
                    <i class="fa-solid fa-chart-bar"></i>
                </div>
                <div>
                    <div class="card-header-title">Paiements par classe</div>
                    <div class="card-header-subtitle">Total encaissé</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container large">
                <canvas id="chartPaiementsClasse"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Ligne 2 : Modes de paiement + Top impayés -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon purple">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <div class="card-header-title">Modes de paiement</div>
                    <div class="card-header-subtitle">Ce mois</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chartModePaiement"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon red">
                    <i class="fa-solid fa-ranking-star"></i>
                </div>
                <div>
                    <div class="card-header-title">Top 10 des impayés</div>
                    <div class="card-header-subtitle">Élèves avec le plus de dettes</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Montant dû</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topImpayes)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-check-circle"></i>
                                        <p>Tous les paiements sont à jour</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topImpayes as $index => $impaye): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <span style="font-size:1.2rem;">
                                                <i class="fa-solid fa-<?= $index === 0 ? 'trophy' : ($index === 1 ? 'medal' : 'award') ?>"></i>
                                            </span>
                                        <?php else: ?>
                                            <strong><?= $index + 1 ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= e($impaye['prenom'] . ' ' . $impaye['nom']) ?></strong>
                                        <br><small class="text-muted"><?= e($impaye['matricule']) ?></small>
                                    </td>
                                    <td><?= e($impaye['classe_nom']) ?></td>
                                    <td>
                                        <span class="badge badge-danger">$ <?= number_format($impaye['total_du'], 0, ',', ' ') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Derniers paiements -->
<div class="card">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon green">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <div class="card-header-title">Derniers paiements enregistrés</div>
                <div class="card-header-subtitle">10 dernières transactions</div>
            </div>
        </div>
        <div class="card-header-actions">
            <a href="/gestion_scolaire/paiements" class="btn-card-action">
                <i class="fa-solid fa-list"></i> Voir tout
            </a>
        </div>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Élève</th>
                        <th>Frais</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Date</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($derniersPaiements)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    <p>Aucun paiement enregistré</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($derniersPaiements as $p): ?>
                            <tr>
                                <td><span class="badge badge-secondary">#<?= $p['id'] ?></span></td>
                                <td>
                                    <strong><?= e($p['prenom'] . ' ' . $p['nom']) ?></strong>
                                    <br><small class="text-muted"><?= e($p['matricule']) ?></small>
                                </td>
                                <td><?= e($p['frais_libelle']) ?></td>
                                <td><strong>$ <?= number_format($p['montant_paye'], 2, ',', ' ') ?></strong></td>
                                <td><?= e($p['mode_paiement']) ?></td>
                                <td><?= formatDate($p['date_paiement'], 'd/m/Y') ?></td>
                                <td><?= badgeStatut($p['statut']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>