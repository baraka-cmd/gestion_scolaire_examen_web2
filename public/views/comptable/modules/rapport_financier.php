<?php
/**
 * Fichier : public/views/comptable/modules/rapport_financier.php
 * Rôle    : Rapport financier complet — Vue Comptable
 * Système : École primaire congolaise — Rapports financiers
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('rapports.consulter');

global $db;

// ============================================================
// STATISTIQUES GLOBALES
// ============================================================
$anneeActive = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

// Totaux généraux
$totalEncaissement = (float) $db->fetchColumn("SELECT COALESCE(SUM(montant_paye), 0) FROM paiements");
$totalAttenduGlobal = (float) $db->fetchColumn(
    "SELECT COALESCE(SUM(f.montant), 0) FROM frais_scolaires f WHERE f.annee_scolaire_id = :aid AND f.est_obligatoire = 1 AND f.est_actif = 1",
    ['aid' => $anneeActive]
);
$nbElevesTotal = (int) $db->fetchColumn("SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :aid AND statut = 'Actif'", ['aid' => $anneeActive]);
$totalAttendu = $totalAttenduGlobal * $nbElevesTotal;
$tauxRecouvrement = $totalAttendu > 0 ? round(($totalEncaissement / $totalAttendu) * 100, 1) : 0;

// Paiements par statut
$nbPayes = (int) $db->fetchColumn("SELECT COUNT(DISTINCT eleve_id) FROM paiements WHERE statut = 'Payé'");
$nbPartiels = (int) $db->fetchColumn("SELECT COUNT(DISTINCT eleve_id) FROM paiements WHERE statut = 'Partiel'");
$nbImpayes = $nbElevesTotal - $nbPayes;

// Paiements du mois en cours
$encaissementMois = (float) $db->fetchColumn("SELECT COALESCE(SUM(montant_paye), 0) FROM paiements WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())");
$nbPaiementsMois = (int) $db->fetchColumn("SELECT COUNT(*) FROM paiements WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())");

// ============================================================
// ÉVOLUTION MENSUELLE (12 derniers mois)
// ============================================================
$evolutionMensuelle = [];
for ($i = 11; $i >= 0; $i--) {
    $mois = date('Y-m', strtotime("-{$i} months"));
    $montant = (float) $db->fetchColumn(
        "SELECT COALESCE(SUM(montant_paye), 0) FROM paiements WHERE DATE_FORMAT(date_paiement, '%Y-%m') = :mois",
        ['mois' => $mois]
    );
    $nb = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM paiements WHERE DATE_FORMAT(date_paiement, '%Y-%m') = :mois",
        ['mois' => $mois]
    );
    $evolutionMensuelle[] = [
        'mois' => $mois,
        'label' => moisEnFrancais((int) substr($mois, 5, 2)) . ' ' . substr($mois, 0, 4),
        'montant' => $montant,
        'nb' => $nb
    ];
}

// ============================================================
// ENCAISSEMENT PAR CLASSE
// ============================================================
$encaissementParClasse = $db->fetchAll(
    "SELECT c.nom AS classe_nom, c.niveau,
            COALESCE(SUM(p.montant_paye), 0) AS total_encaisse,
            COUNT(DISTINCT p.id) AS nb_paiements,
            COUNT(DISTINCT p.eleve_id) AS nb_eleves_payeurs,
            (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id AND i.statut = 'Actif') AS nb_eleves,
            (SELECT COALESCE(SUM(p2.montant_total - p2.montant_paye), 0) FROM paiements p2 
             INNER JOIN inscriptions i2 ON p2.eleve_id = i2.eleve_id 
             WHERE i2.classe_id = c.id AND i2.statut = 'Actif' AND p2.statut != 'Payé') AS total_impayes
     FROM classes c
     LEFT JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
     LEFT JOIN paiements p ON i.eleve_id = p.eleve_id
     WHERE c.annee_scolaire_id = :aid
     GROUP BY c.id, c.nom, c.niveau
     ORDER BY c.niveau",
    ['aid' => $anneeActive]
);

// ============================================================
// ENCAISSEMENT PAR TYPE DE FRAIS
// ============================================================
$encaissementParFrais = $db->fetchAll(
    "SELECT f.libelle, f.montant AS montant_unitaire, f.est_obligatoire,
            COUNT(DISTINCT p.id) AS nb_paiements,
            COUNT(DISTINCT p.eleve_id) AS nb_eleves,
            COALESCE(SUM(p.montant_paye), 0) AS total_encaisse,
            COALESCE(SUM(p.montant_total), 0) AS total_attendu,
            COALESCE(SUM(p.montant_total - p.montant_paye), 0) AS total_impayes
     FROM frais_scolaires f
     LEFT JOIN paiements p ON f.id = p.frais_scolaire_id
     WHERE f.annee_scolaire_id = :aid AND f.est_actif = 1
     GROUP BY f.id, f.libelle, f.montant, f.est_obligatoire
     ORDER BY f.est_obligatoire DESC, total_encaisse DESC",
    ['aid' => $anneeActive]
);

// ============================================================
// TOP 10 ÉLÈVES AVEC LE PLUS D'IMPAYÉS
// ============================================================
$topImpayes = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, c.nom AS classe_nom,
            COALESCE(SUM(p.montant_total), 0) AS total_du,
            COALESCE(SUM(p.montant_paye), 0) AS total_paye,
            COALESCE(SUM(p.montant_total - p.montant_paye), 0) AS reste_a_payer
     FROM inscriptions i
     INNER JOIN eleves e ON i.eleve_id = e.id
     INNER JOIN classes c ON i.classe_id = c.id
     LEFT JOIN paiements p ON e.id = p.eleve_id
     WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.est_actif = 1
     GROUP BY e.id, e.matricule, e.nom, e.prenom, c.nom
     HAVING reste_a_payer > 0
     ORDER BY reste_a_payer DESC
     LIMIT 10",
    ['aid' => $anneeActive]
);

// ============================================================
// RÉPARTITION PAR MODE DE PAIEMENT
// ============================================================
$repartitionModes = $db->fetchAll(
    "SELECT mode_paiement, COUNT(*) AS nb, COALESCE(SUM(montant_paye), 0) AS total
     FROM paiements WHERE mode_paiement IS NOT NULL
     GROUP BY mode_paiement ORDER BY total DESC"
);

// ============================================================
// COMPARAISON ANNÉE PRÉCÉDENTE
// ============================================================
$anneePrecedente = $db->fetchOne("SELECT id, libelle FROM annees_scolaires WHERE date_debut < (SELECT date_debut FROM annees_scolaires WHERE id = :aid) ORDER BY date_debut DESC LIMIT 1", ['aid' => $anneeActive]);
$encaissementAnneePrecedente = 0;
if ($anneePrecedente) {
    $encaissementAnneePrecedente = (float) $db->fetchColumn(
        "SELECT COALESCE(SUM(p.montant_paye), 0) FROM paiements p
         INNER JOIN inscriptions i ON p.eleve_id = i.eleve_id
         WHERE i.annee_scolaire_id = :aid",
        ['aid' => $anneePrecedente['id']]
    );
}
$evolutionAnnuelle = $encaissementAnneePrecedente > 0 ? round((($totalEncaissement - $encaissementAnneePrecedente) / $encaissementAnneePrecedente) * 100, 1) : 0;

$pageTitle = 'Rapport financier';
$pageIcon  = 'fa-chart-pie';
$pageDescription = 'Rapport financier complet — Analyse des encaissements, impayés et évolution';
$extraCSS = ['modules/paiements.css', 'modules/rapport_financier.css'];
$extraJS  = ['common/chart.umd.min.js', 'modules/rapport_financier.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Rapport financier</span>
</nav>

<!-- EN-TÊTE -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-chart-pie"></i> Rapport financier</h4>
        <p>Analyse complète des finances — Année scolaire en cours</p>
    </div>
    <div class="resultats-header-right">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer le rapport
        </button>
        <a href="/gestion_scolaire/comptable/paiements" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Paiements
        </a>
    </div>
</div>

<!-- ============================================================
     STATISTIQUES GLOBALES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($totalEncaissement, 0, ',', ' ') ?></div>
            <div class="stat-label">Total encaissé</div>
            <?php if ($anneePrecedente): ?>
                <span class="stat-change <?= $evolutionAnnuelle >= 0 ? 'up' : 'down' ?>">
                    <i class="fa-solid fa-<?= $evolutionAnnuelle >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                    <?= abs($evolutionAnnuelle) ?>% vs <?= e($anneePrecedente['libelle']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-calculator"></i></div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($totalAttendu, 0, ',', ' ') ?></div>
            <div class="stat-label">Total attendu</div>
            <span class="stat-change neutral"><?= $nbElevesTotal ?> élèves × frais obligatoires</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-chart-line"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $tauxRecouvrement ?>%</div>
            <div class="stat-label">Taux de recouvrement</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($totalAttendu - $totalEncaissement, 0, ',', ' ') ?></div>
            <div class="stat-label">Impayés</div>
            <span class="stat-change down"><?= $nbElevesTotal - $nbPayes ?> élèves</span>
        </div>
    </div>
</div>

<!-- ============================================================
     GRAPHIQUE 1 : ÉVOLUTION MENSUELLE
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon blue"><i class="fa-solid fa-chart-line"></i></div>
            <div>
                <div class="card-header-title">Évolution des encaissements (12 derniers mois)</div>
                <div class="card-header-subtitle">Montant encaissé par mois</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="chart-container" style="height:350px;">
            <canvas id="chartEvolutionMensuelle"></canvas>
        </div>
    </div>
</div>

<!-- ============================================================
     GRAPHIQUE 2 : ENCAISSEMENT PAR CLASSE
     ============================================================ -->
<div class="bottom-grid mb-4">
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-chalkboard"></i> Encaissement par classe</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:300px;">
                <canvas id="chartEncaissementClasse"></canvas>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-credit-card"></i> Répartition par mode de paiement</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:300px;">
                <canvas id="chartModesPaiement"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TABLEAU : ENCAISSEMENT PAR CLASSE
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fa-solid fa-table"></i> Détail par classe</h5>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Classe</th><th>Élèves</th><th>Élèves payeurs</th><th>Encaissé</th><th>Impayés</th><th>Taux</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($encaissementParClasse as $classe): 
                        $tauxClasse = $classe['nb_eleves'] > 0 ? round(($classe['nb_eleves_payeurs'] / $classe['nb_eleves']) * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?= e($classe['classe_nom']) ?></strong></td>
                            <td><?= $classe['nb_eleves'] ?></td>
                            <td><?= $classe['nb_eleves_payeurs'] ?></td>
                            <td><strong style="color:#065f46;">$ <?= number_format($classe['total_encaisse'], 0, ',', ' ') ?></strong></td>
                            <td><strong style="color:#ef4444;">$ <?= number_format($classe['total_impayes'], 0, ',', ' ') ?></strong></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;height:8px;background:#e2e8f0;border-radius:10px;overflow:hidden;min-width:60px;">
                                        <div style="width:<?= $tauxClasse ?>%;height:100%;background:<?= $tauxClasse >= 80 ? '#10b981' : ($tauxClasse >= 50 ? '#f59e0b' : '#ef4444') ?>;border-radius:10px;"></div>
                                    </div>
                                    <span style="font-size:0.75rem;font-weight:600;"><?= $tauxClasse ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TABLEAU : ENCAISSEMENT PAR TYPE DE FRAIS
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fa-solid fa-tags"></i> Détail par type de frais</h5>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Frais</th><th>Type</th><th>Montant unitaire</th><th>Paiements</th><th>Encaissé</th><th>Impayés</th><th>Progression</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($encaissementParFrais as $frais): 
                        $pctFrais = $frais['total_attendu'] > 0 ? round(($frais['total_encaisse'] / $frais['total_attendu']) * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?= e($frais['libelle']) ?></strong></td>
                            <td><?= $frais['est_obligatoire'] ? '<span class="badge badge-warning">Obligatoire</span>' : '<span class="badge badge-secondary">Optionnel</span>' ?></td>
                            <td>$ <?= number_format($frais['montant_unitaire'], 2, ',', ' ') ?></td>
                            <td><span class="badge badge-primary"><?= $frais['nb_paiements'] ?></span></td>
                            <td><strong style="color:#065f46;">$ <?= number_format($frais['total_encaisse'], 0, ',', ' ') ?></strong></td>
                            <td><strong style="color:#ef4444;">$ <?= number_format($frais['total_impayes'], 0, ',', ' ') ?></strong></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;height:8px;background:#e2e8f0;border-radius:10px;overflow:hidden;min-width:60px;">
                                        <div style="width:<?= $pctFrais ?>%;height:100%;background:#10b981;border-radius:10px;"></div>
                                    </div>
                                    <span style="font-size:0.75rem;font-weight:600;"><?= $pctFrais ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TOP 10 IMPAYÉS
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon red"><i class="fa-solid fa-ranking-star"></i></div>
            <div>
                <div class="card-header-title">Top 10 — Élèves avec le plus d'impayés</div>
                <div class="card-header-subtitle">Classement par montant restant à payer</div>
            </div>
        </div>
    </div>
    <div class="card-body no-padding">
        <?php if (empty($topImpayes)): ?>
            <div class="empty-state" style="padding:40px;"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i><p>Aucun impayé — Tous les élèves sont en règle !</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Élève</th><th>Matricule</th><th>Classe</th><th>Total dû</th><th>Payé</th><th>Reste</th></tr></thead>
                    <tbody>
                        <?php foreach ($topImpayes as $index => $eleve): ?>
                            <tr>
                                <td><span class="badge badge-danger">#<?= $index + 1 ?></span></td>
                                <td><strong><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></strong></td>
                                <td><?= e($eleve['matricule']) ?></td>
                                <td><?= e($eleve['classe_nom']) ?></td>
                                <td>$ <?= number_format($eleve['total_du'], 0, ',', ' ') ?></td>
                                <td>$ <?= number_format($eleve['total_paye'], 0, ',', ' ') ?></td>
                                <td><strong style="color:#ef4444;">$ <?= number_format($eleve['reste_a_payer'], 0, ',', ' ') ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     SCRIPTS CHART.JS
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique 1 : Évolution mensuelle
    var ctx1 = document.getElementById('chartEvolutionMensuelle');
    if (ctx1) {
        new Chart(ctx1.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($evolutionMensuelle, 'label')) ?>,
                datasets: [{
                    label: 'Encaissement ($)',
                    data: <?= json_encode(array_column($evolutionMensuelle, 'montant')) ?>,
                    backgroundColor: 'rgba(44, 62, 144, 0.7)',
                    borderColor: '#2c3e90',
                    borderWidth: 1,
                    borderRadius: 8,
                    maxBarThickness: 50,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: { callbacks: { label: function(ctx) { return '$ ' + ctx.raw.toLocaleString('fr-FR'); } } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(v) { return '$ ' + v.toLocaleString('fr-FR'); } } }
                }
            }
        });
    }

    // Graphique 2 : Encaissement par classe
    var ctx2 = document.getElementById('chartEncaissementClasse');
    if (ctx2) {
        new Chart(ctx2.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($encaissementParClasse, 'classe_nom')) ?>,
                datasets: [
                    { label: 'Encaissé', data: <?= json_encode(array_column($encaissementParClasse, 'total_encaisse')) ?>, backgroundColor: 'rgba(16, 185, 129, 0.7)', borderColor: '#10b981', borderWidth: 1, borderRadius: 6 },
                    { label: 'Impayés', data: <?= json_encode(array_column($encaissementParClasse, 'total_impayes')) ?>, backgroundColor: 'rgba(239, 68, 68, 0.7)', borderColor: '#ef4444', borderWidth: 1, borderRadius: 6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { x: { stacked: true }, y: { stacked: true, ticks: { callback: function(v) { return '$ ' + v.toLocaleString('fr-FR'); } } } }
            }
        });
    }

    // Graphique 3 : Modes de paiement
    var ctx3 = document.getElementById('chartModesPaiement');
    if (ctx3) {
        var colors = ['#2c3e90', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
        new Chart(ctx3.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($repartitionModes, 'mode_paiement')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($repartitionModes, 'total')) ?>,
                    backgroundColor: colors,
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } },
                    tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': $ ' + ctx.raw.toLocaleString('fr-FR') + ' (' + <?= json_encode(array_column($repartitionModes, 'nb')) ?>[ctx.dataIndex] + ' paiements)'; } } }
                }
            }
        });
    }
});
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>