/**
 * Fichier : public/assets/js/modules/effectifs.js
 * Rôle    : Scripts du rapport des effectifs
 * Système : École primaire congolaise — Rapports
 * Version : 1.0
 */

'use strict';

// ============================================================
// VARIABLES GLOBALES
// ============================================================
var chartEffectifsClasse = null;
var chartRepartitionSexe = null;
var chartEvolution = null;
var chartPyramide = null;
var chartTranchesAges = null;
var chartInscriptions = null;
var rafraichissementAuto = null;

// Couleurs du thème
var couleurPrimaire = '#2c3e90';
var couleurSecondaire = '#8b5cf6';
var couleurGarcon = '#3b82f6';
var couleurFille = '#8b5cf6';
var couleurSucces = '#10b981';
var couleurWarning = '#f59e0b';
var couleurDanger = '#ef4444';

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    chargerToutesLesDonnees();
    demarrerRafraichissementAuto();
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
            mettreAJourKPIs(data.stats);
        }
    })
    .catch(function() {
        console.error('Erreur chargement statistiques');
    });
}

function mettreAJourKPIs(stats) {
    // Valeurs principales
    document.getElementById('kpiTotalEleves').textContent = stats.total_eleves.toLocaleString('fr-FR');
    document.getElementById('kpiGarcons').textContent = stats.total_garcons.toLocaleString('fr-FR');
    document.getElementById('kpiFilles').textContent = stats.total_filles.toLocaleString('fr-FR');
    document.getElementById('kpiPctGarcons').textContent = stats.pct_garcons + '%';
    document.getElementById('kpiPctFilles').textContent = stats.pct_filles + '%';
    document.getElementById('kpiRatio').textContent = stats.ratio_gf;
    document.getElementById('kpiNouveaux').textContent = stats.nouvelles_inscriptions;
    document.getElementById('kpiMoyenneClasse').textContent = stats.moyenne_par_classe + ' él./classe';

    // Évolution
    var evolutionEl = document.getElementById('kpiEvolution');
    var evolutionHtml = '';
    
    if (stats.evolution > 0) {
        evolutionHtml = '<i class="fa-solid fa-arrow-up"></i> +' + stats.evolution + '% vs ' + (stats.annee_precedente || 'N-1');
        evolutionEl.className = 'kpi-card-subtitle up';
    } else if (stats.evolution < 0) {
        evolutionHtml = '<i class="fa-solid fa-arrow-down"></i> ' + stats.evolution + '% vs ' + (stats.annee_precedente || 'N-1');
        evolutionEl.className = 'kpi-card-subtitle down';
    } else {
        evolutionHtml = '<i class="fa-solid fa-minus"></i> Stable vs ' + (stats.annee_precedente || 'année précédente');
        evolutionEl.className = 'kpi-card-subtitle neutral';
    }
    
    evolutionEl.innerHTML = evolutionHtml;
}

// ============================================================
// 2. EFFECTIFS PAR CLASSE (GRAPHIQUE + TABLEAU)
// ============================================================
function chargerEffectifsParClasse() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getEffectifsParClasse' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            afficherTableauEffectifs(data.effectifs);
            afficherGraphiqueEffectifs(data.effectifs);
        }
    })
    .catch(function() {
        console.error('Erreur chargement effectifs par classe');
    });
}

function afficherTableauEffectifs(effectifs) {
    var tbody = document.getElementById('tbodyEffectifs');
    if (!tbody) return;

    if (effectifs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state" style="padding:30px;"><i class="fa-solid fa-users-slash"></i><p>Aucune donnée disponible.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = effectifs.map(function(c) {
        var tauxClass = c.taux_remplissage >= 90 ? 'danger' : (c.taux_remplissage >= 70 ? 'warning' : 'success');
        return '<tr>' +
            '<td><strong>' + escapeHtml(c.classe_nom) + '</strong></td>' +
            '<td>Niv. ' + c.niveau + '</td>' +
            '<td><small>' + escapeHtml(c.titulaire) + '</small></td>' +
            '<td><span class="badge badge-primary">' + c.garcons + '</span></td>' +
            '<td><span class="badge badge-info">' + c.filles + '</span></td>' +
            '<td><strong>' + c.total_eleves + '</strong></td>' +
            '<td>' + c.capacite_max + '</td>' +
            '<td>' +
                '<div class="progress-cell">' +
                    '<div class="progress-bar-wrapper">' +
                        '<div class="progress-bar-fill ' + tauxClass + '" style="width:' + c.taux_remplissage + '%;"></div>' +
                    '</div>' +
                    '<span class="progress-label">' + c.taux_remplissage + '%</span>' +
                '</div>' +
            '</td>' +
        '</tr>';
    }).join('');
}

function afficherGraphiqueEffectifs(effectifs) {
    var ctx = document.getElementById('chartEffectifsClasse');
    if (!ctx) return;

    if (chartEffectifsClasse) chartEffectifsClasse.destroy();

    chartEffectifsClasse = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: effectifs.map(function(c) { return c.classe_nom; }),
            datasets: [
                {
                    label: 'Garçons',
                    data: effectifs.map(function(c) { return c.garcons; }),
                    backgroundColor: 'rgba(44, 62, 144, 0.85)',
                    borderColor: '#2c3e90',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    maxBarThickness: 55,
                },
                {
                    label: 'Filles',
                    data: effectifs.map(function(c) { return c.filles; }),
                    backgroundColor: 'rgba(139, 92, 246, 0.85)',
                    borderColor: '#8b5cf6',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    maxBarThickness: 55,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20, font: { family: 'Poppins', size: 12 } }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 14,
                    cornerRadius: 10,
                    titleFont: { family: 'Poppins', weight: '600' },
                    bodyFont: { family: 'Poppins' }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 11 } }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        stepSize: 1,
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return Math.floor(value) === value ? value : ''; }
                    }
                }
            }
        }
    });
}

// ============================================================
// 3. RÉPARTITION GARÇONS/FILLES (DONUT)
// ============================================================
function chargerRepartitionSexe() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getRepartitionSexe' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.global) {
            afficherGraphiqueRepartition(data.global);
        }
    })
    .catch(function() {
        console.error('Erreur chargement répartition par sexe');
    });
}

function afficherGraphiqueRepartition(global) {
    var ctx = document.getElementById('chartRepartitionSexe');
    if (!ctx) return;

    if (chartRepartitionSexe) chartRepartitionSexe.destroy();

    var total = global.garcons + global.filles;
    var pctGarcons = total > 0 ? Math.round((global.garcons / total) * 100) : 0;
    var pctFilles = total > 0 ? Math.round((global.filles / total) * 100) : 0;

    chartRepartitionSexe = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Garçons (' + pctGarcons + '%)', 'Filles (' + pctFilles + '%)'],
            datasets: [{
                data: [global.garcons, global.filles],
                backgroundColor: ['#2c3e90', '#8b5cf6'],
                borderWidth: 5,
                borderColor: '#ffffff',
                hoverBorderWidth: 7,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 22,
                        font: { family: 'Poppins', size: 12 },
                        generateLabels: function(chart) {
                            var data = chart.data;
                            return data.labels.map(function(label, i) {
                                return {
                                    text: label + ' (' + data.datasets[0].data[i] + ')',
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].backgroundColor[i],
                                    lineWidth: 1,
                                    hidden: false,
                                    index: i,
                                    pointStyle: 'circle',
                                    rotation: 0
                                };
                            });
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 14,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            return context.label + ' : ' + context.raw + ' élève(s)';
                        }
                    }
                }
            }
        }
    });
}

// ============================================================
// 4. ÉVOLUTION DES EFFECTIFS (COURBE)
// ============================================================
function chargerEvolutionEffectifs() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getEvolutionEffectifs' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            afficherGraphiqueEvolution(data.evolution);
        }
    })
    .catch(function() {
        console.error('Erreur chargement évolution');
    });
}

function afficherGraphiqueEvolution(evolution) {
    var ctx = document.getElementById('chartEvolution');
    if (!ctx) return;

    if (chartEvolution) chartEvolution.destroy();

    chartEvolution = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: evolution.map(function(e) { return e.annee; }),
            datasets: [
                {
                    label: 'Total',
                    data: evolution.map(function(e) { return e.total; }),
                    borderColor: '#2c3e90',
                    backgroundColor: 'rgba(44, 62, 144, 0.08)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointBackgroundColor: '#2c3e90',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 8,
                },
                {
                    label: 'Garçons',
                    data: evolution.map(function(e) { return e.garcons; }),
                    borderColor: '#3b82f6',
                    backgroundColor: 'transparent',
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 5,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'Filles',
                    data: evolution.map(function(e) { return e.filles; }),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'transparent',
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 5,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20, font: { family: 'Poppins', size: 12 } }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 14,
                    cornerRadius: 10,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        stepSize: 1,
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return Math.floor(value) === value ? value : ''; }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 11 } }
                }
            }
        }
    });
}

// ============================================================
// 5. PYRAMIDE DES ÂGES
// ============================================================
function chargerPyramideAges() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getPyramideAges' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('pyramideStats').textContent = 
                'Âge moyen : ' + data.age_moyen + ' ans | Min : ' + data.age_min + ' ans | Max : ' + data.age_max + ' ans';
            afficherGraphiquePyramide(data.pyramide);
        }
    })
    .catch(function() {
        console.error('Erreur chargement pyramide des âges');
    });
}

function afficherGraphiquePyramide(pyramide) {
    var ctx = document.getElementById('chartPyramideAges');
    if (!ctx) return;

    if (chartPyramide) chartPyramide.destroy();

    chartPyramide = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: pyramide.map(function(p) { return p.age + ' ans'; }),
            datasets: [
                {
                    label: 'Garçons',
                    data: pyramide.map(function(p) { return -p.garcons; }),
                    backgroundColor: 'rgba(44, 62, 144, 0.8)',
                    borderColor: '#2c3e90',
                    borderWidth: 1.5,
                    borderRadius: 6,
                },
                {
                    label: 'Filles',
                    data: pyramide.map(function(p) { return p.filles; }),
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: '#8b5cf6',
                    borderWidth: 1.5,
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 14,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ' : ' + Math.abs(context.raw) + ' élève(s)';
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: false,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return Math.abs(value); }
                    }
                },
                y: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 11 } }
                }
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
            afficherTopClasses(data.top_classes);
        }
    })
    .catch(function() {
        console.error('Erreur chargement top classes');
    });
}

function afficherTopClasses(topClasses) {
    var tbody = document.getElementById('tbodyTopClasses');
    if (!tbody) return;

    if (topClasses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state" style="padding:20px;"><p>Aucune donnée.</p></div></td></tr>';
        return;
    }

    var icones = ['🥇', '🥈', '🥉'];
    tbody.innerHTML = topClasses.map(function(c, i) {
        var rangIcon = icones[i] || ('#' + (i + 1));
        return '<tr>' +
            '<td>' + rangIcon + ' <strong>' + escapeHtml(c.classe_nom) + '</strong></td>' +
            '<td><strong>' + c.nb_eleves + '</strong></td>' +
            '<td>' + c.capacite_max + '</td>' +
            '<td><span class="badge badge-' + (c.taux_remplissage >= 90 ? 'danger' : 'warning') + '">' + c.taux_remplissage + '%</span></td>' +
            '<td>' + (c.places_disponibles > 0 ? '<span class="badge badge-success">' + c.places_disponibles + '</span>' : '<span class="badge badge-danger">Complet</span>') + '</td>' +
        '</tr>';
    }).join('');
}

// ============================================================
// 7. NOUVELLES INSCRIPTIONS (12 MOIS)
// ============================================================
function chargerNouvellesInscriptions() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getNouvellesInscriptions' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            afficherGraphiqueInscriptions(data.inscriptions);
        }
    })
    .catch(function() {
        console.error('Erreur chargement inscriptions');
    });
}

function afficherGraphiqueInscriptions(inscriptions) {
    var ctx = document.getElementById('chartNouvellesInscriptions');
    if (!ctx) return;

    if (chartInscriptions) chartInscriptions.destroy();

    chartInscriptions = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: inscriptions.map(function(i) { return i.label; }),
            datasets: [{
                label: 'Nouvelles inscriptions',
                data: inscriptions.map(function(i) { return i.nb; }),
                backgroundColor: 'rgba(16, 185, 129, 0.75)',
                borderColor: '#10b981',
                borderWidth: 1.5,
                borderRadius: 10,
                maxBarThickness: 50,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 14,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            return context.raw + ' inscription(s)';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        stepSize: 1,
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return Math.floor(value) === value ? value : ''; }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 10 }, maxRotation: 45 }
                }
            }
        }
    });
}

// ============================================================
// 8. RÉPARTITION PAR TRANCHE D'ÂGE
// ============================================================
function chargerRepartitionParAge() {
    fetch('/gestion_scolaire/rapports/effectifs', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getRepartitionParAge' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            afficherGraphiqueTranchesAges(data.repartition);
        }
    })
    .catch(function() {
        console.error('Erreur chargement répartition par âge');
    });
}

function afficherGraphiqueTranchesAges(repartition) {
    var ctx = document.getElementById('chartTranchesAges');
    if (!ctx) return;

    if (chartTranchesAges) chartTranchesAges.destroy();

    chartTranchesAges = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: repartition.map(function(r) { return r.tranche_age; }),
            datasets: [
                {
                    label: 'Garçons',
                    data: repartition.map(function(r) { return r.garcons; }),
                    backgroundColor: 'rgba(44, 62, 144, 0.8)',
                    borderColor: '#2c3e90',
                    borderWidth: 1,
                    borderRadius: 8,
                },
                {
                    label: 'Filles',
                    data: repartition.map(function(r) { return r.filles; }),
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: '#8b5cf6',
                    borderWidth: 1,
                    borderRadius: 8,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20, font: { family: 'Poppins', size: 12 } }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        stepSize: 1,
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return Math.floor(value) === value ? value : ''; }
                    }
                }
            }
        }
    });
}

// ============================================================
// RAFRAÎCHISSEMENT AUTOMATIQUE
// ============================================================
function demarrerRafraichissementAuto() {
    // Actualiser toutes les 5 minutes
    rafraichissementAuto = setInterval(function() {
        chargerToutesLesDonnees();
    }, 300000);
}

window.addEventListener('beforeunload', function() {
    if (rafraichissementAuto) {
        clearInterval(rafraichissementAuto);
        rafraichissementAuto = null;
    }
});

// ============================================================
// DESTRUCTION DES GRAPHIQUES (avant de quitter)
// ============================================================
window.addEventListener('beforeunload', function() {
    if (chartEffectifsClasse) chartEffectifsClasse.destroy();
    if (chartRepartitionSexe) chartRepartitionSexe.destroy();
    if (chartEvolution) chartEvolution.destroy();
    if (chartPyramide) chartPyramide.destroy();
    if (chartTranchesAges) chartTranchesAges.destroy();
    if (chartInscriptions) chartInscriptions.destroy();
});

// ============================================================
// UTILITAIRES
// ============================================================
function escapeHtml(texte) {
    if (!texte) return '';
    var div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}