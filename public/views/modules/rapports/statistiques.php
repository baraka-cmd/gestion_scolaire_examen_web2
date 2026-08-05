<?php
/**
 * Fichier : public/views/modules/rapports/statistiques.php
 * Rôle    : Rapport statistique global — Vue synthétique école
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

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'StatistiqueController.php';
$statistiqueController = new StatistiqueController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $statistiqueController->handleAjax();
}

$pageTitle = 'Statistiques générales';
$pageIcon  = 'fa-chart-pie';
$pageDescription = 'Vue d\'ensemble des indicateurs clés de l\'école';
$extraCSS = ['modules/statistiques.css'];
$extraJS  = ['common/chart.umd.min.js', 'modules/statistiques.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Statistiques générales</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-chart-pie"></i> Statistiques générales</h4>
        <p>Vue d'ensemble des indicateurs clés de l'établissement — Année scolaire en cours</p>
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
     KPIs — 6 INDICATEURS CLÉS
     ============================================================ -->
<div class="kpi-grid mb-4">
    <div class="kpi-card blue">
        <div class="kpi-card-header">
            <div class="kpi-card-icon blue-bg"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-card-title">Total élèves</div>
        </div>
        <div class="kpi-card-value" id="kpiEleves">—</div>
        <div class="kpi-card-subtitle neutral" id="kpiEvolution">Chargement...</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-card-header">
            <div class="kpi-card-icon green-bg"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div class="kpi-card-title">Enseignants</div>
        </div>
        <div class="kpi-card-value" id="kpiEnseignants">—</div>
        <div class="kpi-card-subtitle neutral" id="kpiRatio">Chargement...</div>
    </div>
    <div class="kpi-card purple">
        <div class="kpi-card-header">
            <div class="kpi-card-icon purple-bg"><i class="fa-solid fa-chalkboard"></i></div>
            <div class="kpi-card-title">Classes</div>
        </div>
        <div class="kpi-card-value" id="kpiClasses">—</div>
        <div class="kpi-card-subtitle neutral">Année en cours</div>
    </div>
    <div class="kpi-card cyan">
        <div class="kpi-card-header">
            <div class="kpi-card-icon cyan-bg"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="kpi-card-title">Taux présence</div>
        </div>
        <div class="kpi-card-value" id="kpiPresence">—</div>
        <div class="kpi-card-subtitle neutral" id="kpiPresenceDetail">Aujourd'hui</div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-card-header">
            <div class="kpi-card-icon orange-bg"><i class="fa-solid fa-trophy"></i></div>
            <div class="kpi-card-title">Taux réussite</div>
        </div>
        <div class="kpi-card-value" id="kpiReussite">—</div>
        <div class="kpi-card-subtitle neutral" id="kpiMoyenne">Trimestre actif</div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-card-header">
            <div class="kpi-card-icon red-bg"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="kpi-card-title">Recouvrement</div>
        </div>
        <div class="kpi-card-value" id="kpiRecouvrement">—</div>
        <div class="kpi-card-subtitle neutral">Finances</div>
    </div>
</div>

<!-- ============================================================
     TOP ENSEIGNANT + ALERTES
     ============================================================ -->
<div class="charts-grid-2 mb-4">
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-medal"></i> Meilleur enseignant du trimestre</h5>
        </div>
        <div class="card-body">
            <div class="top-enseignant-card" id="topEnseignantCard">
                <div class="top-enseignant-placeholder">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Chargement...</p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-ranking-star"></i> Top 5 — Meilleures classes</h5>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table table-finance">
                    <thead><tr><th>#</th><th>Classe</th><th>Moyenne</th><th>Réussite</th></tr></thead>
                    <tbody id="tbodyTopClasses"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRAPHIQUES PRINCIPAUX
     ============================================================ -->
<div class="charts-grid-2 mb-4">
    <!-- Effectifs par classe -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-chart-bar"></i> Effectifs par classe</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartEffectifsClasse"></canvas>
            </div>
        </div>
    </div>

    <!-- Résultats par trimestre -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-chart-line"></i> Résultats par trimestre</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartResultatsTrimestre"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="charts-grid-2 mb-4">
    <!-- Recouvrement par classe -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-money-bill-wave"></i> Recouvrement par classe</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartRecouvrement"></canvas>
            </div>
        </div>
    </div>

    <!-- Présences par mois -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-calendar-check"></i> Taux de présence par mois</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartPresencesMois"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     INCIDENTS + CLASSES À RISQUE
     ============================================================ -->
<div class="charts-grid-2 mb-4">
    <!-- Incidents par mois -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-gavel"></i> Incidents disciplinaires par mois</h5>
        </div>
        <div class="card-body">
            <div class="chart-wrapper-md">
                <canvas id="chartIncidents"></canvas>
            </div>
        </div>
    </div>

    <!-- Classes à risque -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div class="card-header-title">Classes à risque</div>
                    <div class="card-header-subtitle">Taux de réussite &lt; 50%</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table table-finance">
                    <thead><tr><th>Classe</th><th>Élèves</th><th>Moyenne</th><th>Réussite</th><th>Échecs</th></tr></thead>
                    <tbody id="tbodyClassesRisque">
                        <tr><td colspan="5"><div class="empty-state" style="padding:20px;"><p>Aucune classe à risque</p></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    chargerToutesLesDonnees();
});

function chargerToutesLesDonnees() {
    chargerKPIs();
    chargerEffectifsParClasse();
    chargerResultatsParTrimestre();
    chargerRecouvrementParClasse();
    chargerPresencesParMois();
    chargerIncidentsParMois();
    chargerTopClasses();
    chargerClassesARisque();
}

// ============================================================
// 1. KPIs
// ============================================================
function chargerKPIs() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getKPIs' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var k = data.kpis;
            document.getElementById('kpiEleves').textContent = k.total_eleves.toLocaleString('fr-FR');
            document.getElementById('kpiEnseignants').textContent = k.total_enseignants;
            document.getElementById('kpiClasses').textContent = k.total_classes;
            document.getElementById('kpiPresence').textContent = k.taux_presence + '%';
            document.getElementById('kpiReussite').textContent = k.taux_reussite + '%';
            document.getElementById('kpiRecouvrement').textContent = k.taux_recouvrement + '%';
            document.getElementById('kpiRatio').textContent = k.ratio_eleves_enseignant + ' él./ens.';
            document.getElementById('kpiPresenceDetail').textContent = k.presences_jour + ' présents / ' + k.absences_jour + ' absents';
            document.getElementById('kpiMoyenne').textContent = 'Moy. ' + k.moyenne_generale + '/20';

            var evolEl = document.getElementById('kpiEvolution');
            if (k.evolution_mensuelle > 0) {
                evolEl.innerHTML = '<i class="fa-solid fa-arrow-up"></i> +' + k.evolution_mensuelle + '% ce mois';
                evolEl.className = 'kpi-card-subtitle up';
            } else if (k.evolution_mensuelle < 0) {
                evolEl.innerHTML = '<i class="fa-solid fa-arrow-down"></i> ' + k.evolution_mensuelle + '% ce mois';
                evolEl.className = 'kpi-card-subtitle down';
            } else {
                evolEl.innerHTML = 'Stable ce mois';
                evolEl.className = 'kpi-card-subtitle neutral';
            }

            if (k.top_enseignant) {
                document.getElementById('topEnseignantCard').innerHTML = 
                    '<div class="top-enseignant-avatar">' + k.top_enseignant.nom.charAt(0).toUpperCase() + '</div>' +
                    '<h5>' + escapeHtml(k.top_enseignant.nom) + '</h5>' +
                    '<p><span class="badge badge-primary">' + escapeHtml(k.top_enseignant.classe_nom) + '</span></p>' +
                    '<p class="top-enseignant-moyenne">Moyenne classe : <strong>' + k.top_enseignant.moyenne_classe + '/20</strong></p>';
            }
        }
    });
}

// ============================================================
// 2. EFFECTIFS PAR CLASSE
// ============================================================
var chartEffectifsClasse = null;
function chargerEffectifsParClasse() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getEffectifsParClasse' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.effectifs.length > 0) {
            var ctx = document.getElementById('chartEffectifsClasse');
            if (ctx) {
                if (chartEffectifsClasse) chartEffectifsClasse.destroy();
                chartEffectifsClasse = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.effectifs.map(function(e) { return e.classe_nom; }),
                        datasets: [
                            { label: 'Garçons', data: data.effectifs.map(function(e) { return e.garcons; }), backgroundColor: 'rgba(44,62,144,0.8)', borderColor: '#2c3e90', borderWidth: 1, borderRadius: 6 },
                            { label: 'Filles',  data: data.effectifs.map(function(e) { return e.filles; }),  backgroundColor: 'rgba(139,92,246,0.8)', borderColor: '#8b5cf6', borderWidth: 1, borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
                        scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 3. RÉSULTATS PAR TRIMESTRE
// ============================================================
var chartResultats = null;
function chargerResultatsParTrimestre() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getResultatsParTrimestre' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.resultats.length > 0) {
            var ctx = document.getElementById('chartResultatsTrimestre');
            if (ctx) {
                if (chartResultats) chartResultats.destroy();
                chartResultats = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.resultats.map(function(r) { return r.trimestre; }),
                        datasets: [
                            { label: 'Admis', data: data.resultats.map(function(r) { return r.admis; }), backgroundColor: 'rgba(16,185,129,0.8)', borderColor: '#10b981', borderWidth: 1, borderRadius: 6 },
                            { label: 'Échoués', data: data.resultats.map(function(r) { return r.echoues; }), backgroundColor: 'rgba(239,68,68,0.8)', borderColor: '#ef4444', borderWidth: 1, borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
                        scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 4. RECOUVREMENT PAR CLASSE
// ============================================================
var chartRecouvrement = null;
function chargerRecouvrementParClasse() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getRecouvrementParClasse' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.recouvrement.length > 0) {
            var ctx = document.getElementById('chartRecouvrement');
            if (ctx) {
                if (chartRecouvrement) chartRecouvrement.destroy();
                chartRecouvrement = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.recouvrement.map(function(r) { return r.classe_nom; }),
                        datasets: [
                            { label: 'Payé', data: data.recouvrement.map(function(r) { return r.montant_paye; }), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 6 },
                            { label: 'Impayé', data: data.recouvrement.map(function(r) { return r.montant_impaye; }), backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } }, tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ' : $ ' + ctx.raw.toLocaleString('fr-FR'); } } } },
                        scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { callback: function(v) { return '$ ' + v.toLocaleString('fr-FR'); } } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 5. PRÉSENCES PAR MOIS
// ============================================================
var chartPresencesMois = null;
function chargerPresencesParMois() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getPresencesParMois' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.presences.length > 0) {
            var ctx = document.getElementById('chartPresencesMois');
            if (ctx) {
                if (chartPresencesMois) chartPresencesMois.destroy();
                chartPresencesMois = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.presences.map(function(p) { return p.label; }),
                        datasets: [
                            { label: 'Taux de présence (%)', data: data.presences.map(function(p) { return p.taux; }), borderColor: '#2c3e90', backgroundColor: 'rgba(44,62,144,0.08)', tension: 0.4, fill: true, borderWidth: 3, pointRadius: 5, pointBackgroundColor: '#2c3e90' }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { min: 0, max: 100, ticks: { callback: function(v) { return v + '%'; } } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 6. INCIDENTS PAR MOIS
// ============================================================
var chartIncidents = null;
function chargerIncidentsParMois() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getIncidentsParMois' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.incidents.length > 0) {
            var ctx = document.getElementById('chartIncidents');
            if (ctx) {
                if (chartIncidents) chartIncidents.destroy();
                chartIncidents = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.incidents.map(function(i) { return i.label; }),
                        datasets: [
                            { label: 'Légères', data: data.incidents.map(function(i) { return i.legeres; }), backgroundColor: 'rgba(245,158,11,0.7)', borderRadius: 6 },
                            { label: 'Moyennes', data: data.incidents.map(function(i) { return i.moyennes; }), backgroundColor: 'rgba(249,115,22,0.7)', borderRadius: 6 },
                            { label: 'Graves', data: data.incidents.map(function(i) { return i.graves; }), backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
                        scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        }
    });
}

// ============================================================
// 7. TOP CLASSES
// ============================================================
function chargerTopClasses() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getTopClasses' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var tbody = document.getElementById('tbodyTopClasses');
            if (data.top_classes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state" style="padding:20px;"><p>Aucune donnée.</p></div></td></tr>';
            } else {
                var icones = ['🥇','🥈','🥉'];
                tbody.innerHTML = data.top_classes.map(function(c, i) {
                    return '<tr><td>' + (icones[i] || ('#'+(i+1))) + '</td><td><strong>' + escapeHtml(c.classe_nom) + '</strong></td><td>' + c.moyenne_classe + '/20</td><td><span class="badge badge-success">' + c.taux_reussite + '%</span></td></tr>';
                }).join('');
            }
        }
    });
}

// ============================================================
// 8. CLASSES À RISQUE
// ============================================================
function chargerClassesARisque() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getClassesARisque' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var tbody = document.getElementById('tbodyClassesRisque');
            if (data.classes_risque.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state" style="padding:20px;"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i><p>Aucune classe à risque — Excellent !</p></div></td></tr>';
            } else {
                tbody.innerHTML = data.classes_risque.map(function(c) {
                    return '<tr><td><strong>' + escapeHtml(c.classe_nom) + '</strong></td><td>' + c.nb_eleves + '</td><td>' + c.moyenne_classe + '/20</td><td><span class="badge badge-danger">' + c.taux_reussite + '%</span></td><td><span class="badge badge-danger">' + c.nb_echecs + '</span></td></tr>';
                }).join('');
            }
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>