/**
 * Fichier : public/assets/js/modules/statistiques.js
 * Rôle    : Scripts du rapport statistique global
 * Système : École primaire congolaise — Rapports
 * Version : 1.0
 */

'use strict';

// ============================================================
// VARIABLES GLOBALES
// ============================================================
var chartEffectifsClasse = null;
var chartResultats = null;
var chartRecouvrement = null;
var chartPresencesMois = null;
var chartIncidents = null;
var rafraichissementAuto = null;

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
            mettreAJourKPIs(data.kpis);
        }
    })
    .catch(function() {
        console.error('Erreur chargement KPIs');
    });
}

function mettreAJourKPIs(k) {
    document.getElementById('kpiEleves').textContent = k.total_eleves.toLocaleString('fr-FR');
    document.getElementById('kpiEnseignants').textContent = k.total_enseignants;
    document.getElementById('kpiClasses').textContent = k.total_classes;
    document.getElementById('kpiPresence').textContent = k.taux_presence + '%';
    document.getElementById('kpiReussite').textContent = k.taux_reussite + '%';
    document.getElementById('kpiRecouvrement').textContent = k.taux_recouvrement + '%';
    document.getElementById('kpiRatio').textContent = k.ratio_eleves_enseignant + ' él./ens.';
    document.getElementById('kpiPresenceDetail').textContent = k.presences_jour + ' présents / ' + k.absences_jour + ' absents';
    document.getElementById('kpiMoyenne').textContent = 'Moy. ' + k.moyenne_generale + '/20';

    // Évolution
    var evolEl = document.getElementById('kpiEvolution');
    if (k.evolution_mensuelle > 0) {
        evolEl.innerHTML = '<i class="fa-solid fa-arrow-up"></i> +' + k.evolution_mensuelle + '% ce mois';
        evolEl.className = 'kpi-card-subtitle up';
    } else if (k.evolution_mensuelle < 0) {
        evolEl.innerHTML = '<i class="fa-solid fa-arrow-down"></i> ' + k.evolution_mensuelle + '% ce mois';
        evolEl.className = 'kpi-card-subtitle down';
    } else {
        evolEl.innerHTML = '<i class="fa-solid fa-minus"></i> Stable ce mois';
        evolEl.className = 'kpi-card-subtitle neutral';
    }

    // Top enseignant
    if (k.top_enseignant) {
        document.getElementById('topEnseignantCard').innerHTML =
            '<div class="top-enseignant-avatar">' + k.top_enseignant.nom.charAt(0).toUpperCase() + '</div>' +
            '<h5>' + escapeHtml(k.top_enseignant.nom) + '</h5>' +
            '<p><span class="badge badge-primary">' + escapeHtml(k.top_enseignant.classe_nom) + '</span></p>' +
            '<p class="top-enseignant-moyenne">Moyenne classe : <strong>' + k.top_enseignant.moyenne_classe + '/20</strong></p>';
    }
}

// ============================================================
// 2. EFFECTIFS PAR CLASSE
// ============================================================
function chargerEffectifsParClasse() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getEffectifsParClasse' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.effectifs && data.effectifs.length > 0) {
            afficherGraphiqueEffectifs(data.effectifs);
        }
    })
    .catch(function() {
        console.error('Erreur chargement effectifs');
    });
}

function afficherGraphiqueEffectifs(effectifs) {
    var ctx = document.getElementById('chartEffectifsClasse');
    if (!ctx) return;

    if (chartEffectifsClasse) chartEffectifsClasse.destroy();

    chartEffectifsClasse = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: effectifs.map(function(e) { return e.classe_nom; }),
            datasets: [
                {
                    label: 'Garçons',
                    data: effectifs.map(function(e) { return e.garcons; }),
                    backgroundColor: 'rgba(44, 62, 144, 0.85)',
                    borderColor: '#2c3e90',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    maxBarThickness: 55,
                },
                {
                    label: 'Filles',
                    data: effectifs.map(function(e) { return e.filles; }),
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
// 3. RÉSULTATS PAR TRIMESTRE
// ============================================================
function chargerResultatsParTrimestre() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getResultatsParTrimestre' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.resultats && data.resultats.length > 0) {
            afficherGraphiqueResultats(data.resultats);
        }
    })
    .catch(function() {
        console.error('Erreur chargement résultats');
    });
}

function afficherGraphiqueResultats(resultats) {
    var ctx = document.getElementById('chartResultatsTrimestre');
    if (!ctx) return;

    if (chartResultats) chartResultats.destroy();

    chartResultats = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: resultats.map(function(r) { return r.trimestre; }),
            datasets: [
                {
                    label: 'Admis',
                    data: resultats.map(function(r) { return r.admis; }),
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    borderRadius: 8,
                },
                {
                    label: 'Échoués',
                    data: resultats.map(function(r) { return r.echoues; }),
                    backgroundColor: 'rgba(239, 68, 68, 0.85)',
                    borderColor: '#ef4444',
                    borderWidth: 1.5,
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
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 14,
                    cornerRadius: 10,
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
// 4. RECOUVREMENT PAR CLASSE
// ============================================================
function chargerRecouvrementParClasse() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getRecouvrementParClasse' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.recouvrement && data.recouvrement.length > 0) {
            afficherGraphiqueRecouvrement(data.recouvrement);
        }
    })
    .catch(function() {
        console.error('Erreur chargement recouvrement');
    });
}

function afficherGraphiqueRecouvrement(recouvrement) {
    var ctx = document.getElementById('chartRecouvrement');
    if (!ctx) return;

    if (chartRecouvrement) chartRecouvrement.destroy();

    chartRecouvrement = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: recouvrement.map(function(r) { return r.classe_nom; }),
            datasets: [
                {
                    label: 'Payé',
                    data: recouvrement.map(function(r) { return r.montant_paye; }),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 8,
                },
                {
                    label: 'Impayé',
                    data: recouvrement.map(function(r) { return r.montant_impaye; }),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: '#ef4444',
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
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 14,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ' : $ ' + context.raw.toLocaleString('fr-FR');
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 10 } }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return '$ ' + value.toLocaleString('fr-FR'); }
                    }
                }
            }
        }
    });
}

// ============================================================
// 5. PRÉSENCES PAR MOIS
// ============================================================
function chargerPresencesParMois() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getPresencesParMois' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.presences && data.presences.length > 0) {
            afficherGraphiquePresences(data.presences);
        }
    })
    .catch(function() {
        console.error('Erreur chargement présences');
    });
}

function afficherGraphiquePresences(presences) {
    var ctx = document.getElementById('chartPresencesMois');
    if (!ctx) return;

    if (chartPresencesMois) chartPresencesMois.destroy();

    chartPresencesMois = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: presences.map(function(p) { return p.label; }),
            datasets: [{
                label: 'Taux de présence (%)',
                data: presences.map(function(p) { return p.taux; }),
                borderColor: '#2c3e90',
                backgroundColor: 'rgba(44, 62, 144, 0.08)',
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointRadius: 5,
                pointBackgroundColor: '#2c3e90',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointHoverRadius: 7,
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
                            return 'Taux de présence : ' + context.raw + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return value + '%'; }
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
// 6. INCIDENTS PAR MOIS
// ============================================================
function chargerIncidentsParMois() {
    fetch('/gestion_scolaire/rapports/statistiques', {
        method: 'POST',
        body: new URLSearchParams({ action: 'getIncidentsParMois' }),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.incidents && data.incidents.length > 0) {
            afficherGraphiqueIncidents(data.incidents);
        }
    })
    .catch(function() {
        console.error('Erreur chargement incidents');
    });
}

function afficherGraphiqueIncidents(incidents) {
    var ctx = document.getElementById('chartIncidents');
    if (!ctx) return;

    if (chartIncidents) chartIncidents.destroy();

    chartIncidents = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: incidents.map(function(i) { return i.label; }),
            datasets: [
                {
                    label: 'Légères',
                    data: incidents.map(function(i) { return i.legeres; }),
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    borderColor: '#f59e0b',
                    borderWidth: 1,
                    borderRadius: 8,
                },
                {
                    label: 'Moyennes',
                    data: incidents.map(function(i) { return i.moyennes; }),
                    backgroundColor: 'rgba(249, 115, 22, 0.8)',
                    borderColor: '#f97316',
                    borderWidth: 1,
                    borderRadius: 8,
                },
                {
                    label: 'Graves',
                    data: incidents.map(function(i) { return i.graves; }),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: '#ef4444',
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
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 10 }, maxRotation: 45 }
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
            if (!tbody) return;

            if (!data.top_classes || data.top_classes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state" style="padding:20px;"><p>Aucune donnée disponible.</p></div></td></tr>';
                return;
            }

            var icones = ['🥇', '🥈', '🥉'];
            tbody.innerHTML = data.top_classes.map(function(c, i) {
                var rang = icones[i] || ('#' + (i + 1));
                return '<tr>' +
                    '<td>' + rang + '</td>' +
                    '<td><strong>' + escapeHtml(c.classe_nom) + '</strong></td>' +
                    '<td>' + c.moyenne_classe + '/20</td>' +
                    '<td><span class="badge badge-success">' + c.taux_reussite + '%</span></td>' +
                '</tr>';
            }).join('');
        }
    })
    .catch(function() {
        console.error('Erreur chargement top classes');
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
            if (!tbody) return;

            if (!data.classes_risque || data.classes_risque.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state" style="padding:20px;"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i><p>Aucune classe à risque — Excellent !</p></div></td></tr>';
                return;
            }

            tbody.innerHTML = data.classes_risque.map(function(c) {
                return '<tr>' +
                    '<td><strong>' + escapeHtml(c.classe_nom) + '</strong></td>' +
                    '<td>' + c.nb_eleves + '</td>' +
                    '<td>' + c.moyenne_classe + '/20</td>' +
                    '<td><span class="badge badge-danger">' + c.taux_reussite + '%</span></td>' +
                    '<td><span class="badge badge-danger">' + c.nb_echecs + '</span></td>' +
                '</tr>';
            }).join('');
        }
    })
    .catch(function() {
        console.error('Erreur chargement classes à risque');
    });
}

// ============================================================
// RAFRAÎCHISSEMENT AUTOMATIQUE
// ============================================================
function demarrerRafraichissementAuto() {
    rafraichissementAuto = setInterval(function() {
        chargerToutesLesDonnees();
    }, 300000); // 5 minutes
}

window.addEventListener('beforeunload', function() {
    if (rafraichissementAuto) {
        clearInterval(rafraichissementAuto);
        rafraichissementAuto = null;
    }
});

// ============================================================
// NETTOYAGE GRAPHIQUES
// ============================================================
window.addEventListener('beforeunload', function() {
    if (chartEffectifsClasse) chartEffectifsClasse.destroy();
    if (chartResultats) chartResultats.destroy();
    if (chartRecouvrement) chartRecouvrement.destroy();
    if (chartPresencesMois) chartPresencesMois.destroy();
    if (chartIncidents) chartIncidents.destroy();
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