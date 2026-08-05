<?php
/**
 * Fichier : public/views/modules/rapports/effectifs.php
 * Rôle    : Rapport des effectifs — Statistiques et graphiques
 * Système : École primaire congolaise — Rapports
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

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EffectifController.php';
$effectifController = new EffectifController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $effectifController->handleAjax();
}

// Stats initiales pour affichage direct
$statsInitiales = $effectifController->getStatsInitiales();
$annees = $effectifController->getAnneesScolaires();

$pageTitle = 'Rapport des effectifs';
$pageIcon  = 'fa-chart-bar';
$pageDescription = 'Analyse détaillée des effectifs scolaires';
$extraCSS = ['modules/effectifs.css'];
$extraJS  = ['common/chart.umd.min.js', 'modules/effectifs.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Rapport des effectifs</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-chart-bar"></i> Rapport des effectifs</h4>
        <p>Analyse complète des effectifs — Année scolaire en cours</p>
    </div>
    <div class="resultats-header-right">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer le rapport
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnActualiser" onclick="chargerToutesLesDonnees()">
            <i class="fa-solid fa-rotate"></i> Actualiser
        </button>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     KPIs — INDICATEURS CLÉS
     ============================================================ -->
<div class="kpi-grid mb-4" id="kpiContainer">
    <div class="kpi-card blue">
        <div class="kpi-card-header">
            <div class="kpi-card-icon blue-bg"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-card-title">Total élèves</div>
        </div>
        <div class="kpi-card-value" id="kpiTotalEleves"><?= number_format($statsInitiales['total_eleves'], 0, ',', ' ') ?></div>
        <div class="kpi-card-subtitle neutral" id="kpiEvolution">Chargement...</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-card-header">
            <div class="kpi-card-icon green-bg"><i class="fa-solid fa-mars"></i></div>
            <div class="kpi-card-title">Garçons</div>
        </div>
        <div class="kpi-card-value" id="kpiGarcons"><?= number_format($statsInitiales['total_garcons'], 0, ',', ' ') ?></div>
        <div class="kpi-card-subtitle neutral" id="kpiPctGarcons">Chargement...</div>
    </div>
    <div class="kpi-card purple">
        <div class="kpi-card-header">
            <div class="kpi-card-icon purple-bg"><i class="fa-solid fa-venus"></i></div>
            <div class="kpi-card-title">Filles</div>
        </div>
        <div class="kpi-card-value" id="kpiFilles"><?= number_format($statsInitiales['total_filles'], 0, ',', ' ') ?></div>
        <div class="kpi-card-subtitle neutral" id="kpiPctFilles">Chargement...</div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-card-header">
            <div class="kpi-card-icon orange-bg"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="kpi-card-title">Ratio G/F</div>
        </div>
        <div class="kpi-card-value" id="kpiRatio">—</div>
        <div class="kpi-card-subtitle neutral">Garçons pour 1 fille</div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-card-header">
            <div class="kpi-card-icon red-bg"><i class="fa-solid fa-user-plus"></i></div>
            <div class="kpi-card-title">Nouveaux ce mois</div>
        </div>
        <div class="kpi-card-value" id="kpiNouveaux">—</div>
        <div class="kpi-card-subtitle neutral">Inscriptions</div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-card-header">
            <div class="kpi-card-icon cyan-bg"><i class="fa-solid fa-chalkboard"></i></div>
            <div class="kpi-card-title">Classes</div>
        </div>
        <div class="kpi-card-value"><?= $statsInitiales['nb_classes'] ?></div>
        <div class="kpi-card-subtitle neutral" id="kpiMoyenneClasse">Chargement...</div>
    </div>
</div>

<!-- ============================================================
     GRAPHIQUE 1 : EFFECTIFS PAR CLASSE (BARRES EMPILÉES)
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon blue"><i class="fa-solid fa-chart-bar"></i></div>
            <div>
                <div class="card-header-title">Effectifs par classe</div>
                <div class="card-header-subtitle">Répartition Garçons / Filles</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="chart-wrapper-lg">
            <canvas id="chartEffectifsClasse"></canvas>
        </div>
    </div>
</div>

<!-- ============================================================
     GRAPHIQUES LIGNE 2 : RÉPARTITION G/F + ÉVOLUTION
     ============================================================ -->
<div class="charts-grid-2 mb-4">
    <!-- Répartition Garçons/Filles -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-chart-pie"></i> Répartition Garçons / Filles</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartRepartitionSexe"></canvas>
            </div>
        </div>
    </div>

    <!-- Évolution des effectifs (5 ans) -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-chart-line"></i> Évolution des effectifs</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartEvolution"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TABLEAU DÉTAILLÉ PAR CLASSE
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon green"><i class="fa-solid fa-table-list"></i></div>
            <div>
                <div class="card-header-title">Détail par classe</div>
                <div class="card-header-subtitle">Effectifs, titulaire, taux de remplissage</div>
            </div>
        </div>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="table table-finance">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th>Titulaire</th>
                        <th>Garçons</th>
                        <th>Filles</th>
                        <th>Total</th>
                        <th>Capacité</th>
                        <th>Taux rempl.</th>
                    </tr>
                </thead>
                <tbody id="tbodyEffectifs">
                    <tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     PYRAMIDE DES ÂGES
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon orange"><i class="fa-solid fa-chart-simple"></i></div>
            <div>
                <div class="card-header-title">Pyramide des âges</div>
                <div class="card-header-subtitle" id="pyramideStats">Chargement...</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="chart-wrapper-lg">
            <canvas id="chartPyramideAges"></canvas>
        </div>
    </div>
</div>

<!-- ============================================================
     TOP 5 + RÉPARTITION PAR ÂGE
     ============================================================ -->
<div class="charts-grid-2 mb-4">
    <!-- Top 5 classes -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-ranking-star"></i> Top 5 — Classes les plus remplies</h5>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table table-finance">
                    <thead><tr><th>Classe</th><th>Élèves</th><th>Capacité</th><th>Taux</th><th>Places</th></tr></thead>
                    <tbody id="tbodyTopClasses"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Répartition par tranche d'âge -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-calendar"></i> Répartition par tranche d'âge</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartTranchesAges"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     NOUVELLES INSCRIPTIONS (12 MOIS)
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fa-solid fa-user-plus"></i> Nouvelles inscriptions (12 derniers mois)</h5>
    </div>
    <div class="card-body">
        <div class="chart-wrapper-lg">
            <canvas id="chartNouvellesInscriptions"></canvas>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    chargerToutesLesDonnees();
});

// ============================================================
// CHARGEMENT DE TOUTES LES DONNÉES
// ============================================================
function chargerToutesLesDonnees() {
    chargerStatistiques();
    chargerEffectifsParClasse();
    chargerRepartitionSexe();
    chargerEvolutionEffectifs();
    chargerPyramideAges();
    chargerTopClasses();
    chargerNouvellesInscriptions();
    chargerRepartitionParAge();
}

// ============================================================
// 1. STATISTIQUES GLOBALES (KPIs)
// ============================================================
function chargerStatistiques() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getStatistiques' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var stats = data.stats;
            document.getElementById('kpiTotalEleves').textContent = stats.total_eleves.toLocaleString('fr-FR');
            document.getElementById('kpiGarcons').textContent = stats.total_garcons.toLocaleString('fr-FR');
            document.getElementById('kpiFilles').textContent = stats.total_filles.toLocaleString('fr-FR');
            document.getElementById('kpiPctGarcons').textContent = stats.pct_garcons + '%';
            document.getElementById('kpiPctFilles').textContent = stats.pct_filles + '%';
            document.getElementById('kpiRatio').textContent = stats.ratio_gf;
            document.getElementById('kpiNouveaux').textContent = stats.nouvelles_inscriptions;
            document.getElementById('kpiMoyenneClasse').textContent = stats.moyenne_par_classe + ' él./classe';
            
            var evolutionHtml = '';
            if (stats.evolution > 0) {
                evolutionHtml = '<i class="fa-solid fa-arrow-up"></i> +' + stats.evolution + '% vs ' + stats.annee_precedente;
                document.getElementById('kpiEvolution').className = 'kpi-card-subtitle up';
            } else if (stats.evolution < 0) {
                evolutionHtml = '<i class="fa-solid fa-arrow-down"></i> ' + stats.evolution + '% vs ' + stats.annee_precedente;
                document.getElementById('kpiEvolution').className = 'kpi-card-subtitle down';
            } else {
                evolutionHtml = 'Stable vs ' + (stats.annee_precedente || 'année précédente');
                document.getElementById('kpiEvolution').className = 'kpi-card-subtitle neutral';
            }
            document.getElementById('kpiEvolution').innerHTML = evolutionHtml;
        }
    });
}

// ============================================================
// 2. EFFECTIFS PAR CLASSE (GRAPHIQUE + TABLEAU)
// ============================================================
var chartEffectifsClasse = null;

function chargerEffectifsParClasse() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getEffectifsParClasse' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            // Tableau
            var tbody = document.getElementById('tbodyEffectifs');
            if (data.effectifs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-users-slash"></i><p>Aucune donnée.</p></div></td></tr>';
            } else {
                tbody.innerHTML = data.effectifs.map(function(c) {
                    var tauxClass = c.taux_remplissage >= 90 ? 'danger' : (c.taux_remplissage >= 70 ? 'warning' : 'success');
                    return '<tr>' +
                        '<td><strong>' + escapeHtml(c.classe_nom) + '</strong></td>' +
                        '<td>Niv. ' + c.niveau + '</td>' +
                        '<td>' + escapeHtml(c.titulaire) + '</td>' +
                        '<td><span class="badge badge-primary">' + c.garcons + '</span></td>' +
                        '<td><span class="badge badge-info">' + c.filles + '</span></td>' +
                        '<td><strong>' + c.total_eleves + '</strong></td>' +
                        '<td>' + c.capacite_max + '</td>' +
                        '<td><div class="progress-cell"><div class="progress-bar-wrapper"><div class="progress-bar-fill ' + tauxClass + '" style="width:' + c.taux_remplissage + '%;"></div></div><span class="progress-label">' + c.taux_remplissage + '%</span></div></td>' +
                    '</tr>';
                }).join('');
            }

            // Graphique
            var ctx = document.getElementById('chartEffectifsClasse');
            if (ctx) {
                if (chartEffectifsClasse) chartEffectifsClasse.destroy();
                chartEffectifsClasse = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.effectifs.map(function(c) { return c.classe_nom; }),
                        datasets: [
                            { label: 'Garçons', data: data.effectifs.map(function(c) { return c.garcons; }), backgroundColor: 'rgba(44, 62, 144, 0.8)', borderColor: '#2c3e90', borderWidth: 1, borderRadius: 6 },
                            { label: 'Filles',  data: data.effectifs.map(function(c) { return c.filles; }),  backgroundColor: 'rgba(139, 92, 246, 0.8)', borderColor: '#8b5cf6', borderWidth: 1, borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
                        scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 3. RÉPARTITION GARÇONS/FILLES (DONUT)
// ============================================================
var chartRepartitionSexe = null;

function chargerRepartitionSexe() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getRepartitionSexe' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.global) {
            var ctx = document.getElementById('chartRepartitionSexe');
            if (ctx) {
                if (chartRepartitionSexe) chartRepartitionSexe.destroy();
                chartRepartitionSexe = new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Garçons', 'Filles'],
                        datasets: [{ data: [data.global.garcons, data.global.filles], backgroundColor: ['#2c3e90', '#8b5cf6'], borderWidth: 4, borderColor: '#fff' }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '65%',
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 12 } } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 4. ÉVOLUTION DES EFFECTIFS (COURBE)
// ============================================================
var chartEvolution = null;

function chargerEvolutionEffectifs() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getEvolutionEffectifs' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var ctx = document.getElementById('chartEvolution');
            if (ctx) {
                if (chartEvolution) chartEvolution.destroy();
                chartEvolution = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.evolution.map(function(e) { return e.annee; }),
                        datasets: [
                            { label: 'Total', data: data.evolution.map(function(e) { return e.total; }), borderColor: '#2c3e90', backgroundColor: 'rgba(44,62,144,0.1)', tension: 0.4, fill: true, borderWidth: 2.5, pointRadius: 5, pointBackgroundColor: '#2c3e90' },
                            { label: 'Garçons', data: data.evolution.map(function(e) { return e.garcons; }), borderColor: '#3b82f6', backgroundColor: 'transparent', tension: 0.4, borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#3b82f6' },
                            { label: 'Filles', data: data.evolution.map(function(e) { return e.filles; }), borderColor: '#8b5cf6', backgroundColor: 'transparent', tension: 0.4, borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#8b5cf6' }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 5. PYRAMIDE DES ÂGES
// ============================================================
var chartPyramide = null;

function chargerPyramideAges() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getPyramideAges' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('pyramideStats').textContent = 'Âge moyen : ' + data.age_moyen + ' ans | Min : ' + data.age_min + ' ans | Max : ' + data.age_max + ' ans';
            
            var ctx = document.getElementById('chartPyramideAges');
            if (ctx) {
                if (chartPyramide) chartPyramide.destroy();
                chartPyramide = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.pyramide.map(function(p) { return p.age + ' ans'; }),
                        datasets: [
                            { label: 'Garçons', data: data.pyramide.map(function(p) { return -p.garcons; }), backgroundColor: 'rgba(44, 62, 144, 0.8)', borderColor: '#2c3e90', borderWidth: 1, borderRadius: 4 },
                            { label: 'Filles',  data: data.pyramide.map(function(p) { return p.filles; }),  backgroundColor: 'rgba(139, 92, 246, 0.8)', borderColor: '#8b5cf6', borderWidth: 1, borderRadius: 4 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                        plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + Math.abs(ctx.raw); } } } },
                        scales: { x: { stacked: false, ticks: { callback: function(v) { return Math.abs(v); } } }, y: { stacked: true } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 6. TOP 5 CLASSES
// ============================================================
function chargerTopClasses() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getTopClasses' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var tbody = document.getElementById('tbodyTopClasses');
            if (data.top_classes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><p>Aucune donnée.</p></div></td></tr>';
            } else {
                tbody.innerHTML = data.top_classes.map(function(c, i) {
                    var rangIcon = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : '#' + (i+1)));
                    return '<tr><td>' + rangIcon + ' ' + escapeHtml(c.classe_nom) + '</td><td><strong>' + c.nb_eleves + '</strong></td><td>' + c.capacite_max + '</td><td><span class="badge badge-' + (c.taux_remplissage >= 90 ? 'danger' : 'warning') + '">' + c.taux_remplissage + '%</span></td><td>' + (c.places_disponibles > 0 ? c.places_disponibles : '<span class="badge badge-danger">Complet</span>') + '</td></tr>';
                }).join('');
            }
        }
    });
}

// ============================================================
// 7. NOUVELLES INSCRIPTIONS (12 MOIS)
// ============================================================
var chartInscriptions = null;

function chargerNouvellesInscriptions() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getNouvellesInscriptions' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var ctx = document.getElementById('chartNouvellesInscriptions');
            if (ctx) {
                if (chartInscriptions) chartInscriptions.destroy();
                chartInscriptions = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.inscriptions.map(function(i) { return i.label; }),
                        datasets: [{ label: 'Inscriptions', data: data.inscriptions.map(function(i) { return i.nb; }), backgroundColor: 'rgba(16, 185, 129, 0.7)', borderColor: '#10b981', borderWidth: 1, borderRadius: 8, maxBarThickness: 50 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 8. RÉPARTITION PAR TRANCHE D'ÂGE
// ============================================================
var chartTranchesAges = null;

function chargerRepartitionParAge() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getRepartitionParAge' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var ctx = document.getElementById('chartTranchesAges');
            if (ctx) {
                if (chartTranchesAges) chartTranchesAges.destroy();
                chartTranchesAges = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.repartition.map(function(r) { return r.tranche_age; }),
                        datasets: [
                            { label: 'Garçons', data: data.repartition.map(function(r) { return r.garcons; }), backgroundColor: 'rgba(44,62,144,0.7)', borderRadius: 6 },
                            { label: 'Filles',  data: data.repartition.map(function(r) { return r.filles; }),  backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// UTILITAIRES
// ============================================================
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>