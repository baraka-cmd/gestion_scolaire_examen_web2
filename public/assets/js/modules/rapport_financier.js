/**
 * Fichier : public/assets/js/modules/rapport_financier.js
 * Rôle    : Scripts du rapport financier — Graphiques et interactions
 * Version : 1.0
 */

'use strict';

document.addEventListener('DOMContentLoaded', function() {

    // ============================================================
    // 1. INITIALISER LES GRAPHIQUES CHART.JS
    // ============================================================
    initGraphiqueEvolution();
    initGraphiqueClasses();
    initGraphiqueModesPaiement();
    initGraphiqueFrais();

    // ============================================================
    // 2. FILTRES DU RAPPORT
    // ============================================================
    initFiltresRapport();

    // ============================================================
    // 3. EXPORT PDF
    // ============================================================
    initExportPDF();

    // ============================================================
    // 4. ACTUALISATION AUTO (toutes les 5 minutes)
    // ============================================================
    setInterval(rafraichirDonnees, 300000);
});

// ============================================================
// GRAPHIQUE 1 : ÉVOLUTION MENSUELLE DES ENCAISSEMENTS
// ============================================================
function initGraphiqueEvolution() {
    const canvas = document.getElementById('chartEvolutionMensuelle');
    if (!canvas) return;

    const dataEvolution = JSON.parse(canvas.dataset.evolution || '[]');
    
    if (dataEvolution.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.font = '14px Poppins, sans-serif';
        ctx.fillStyle = '#94a3b8';
        ctx.textAlign = 'center';
        ctx.fillText('Aucune donnée disponible', canvas.width / 2, canvas.height / 2);
        return;
    }

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: dataEvolution.map(function(item) { return item.label; }),
            datasets: [
                {
                    label: 'Encaissement ($)',
                    data: dataEvolution.map(function(item) { return item.montant; }),
                    backgroundColor: 'rgba(44, 62, 144, 0.75)',
                    borderColor: '#2c3e90',
                    borderWidth: 1.5,
                    borderRadius: 10,
                    maxBarThickness: 55,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { family: 'Poppins', size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { family: 'Poppins', weight: '600' },
                    bodyFont: { family: 'Poppins' },
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
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 10 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) {
                            return '$ ' + value.toLocaleString('fr-FR');
                        }
                    }
                }
            }
        }
    });
}

// ============================================================
// GRAPHIQUE 2 : ENCAISSEMENT PAR CLASSE (EMPILÉ)
// ============================================================
function initGraphiqueClasses() {
    const canvas = document.getElementById('chartEncaissementClasse');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const encaisse = JSON.parse(canvas.dataset.encaisse || '[]');
    const impayes = JSON.parse(canvas.dataset.impayes || '[]');

    if (labels.length === 0) return;

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Encaissé',
                    data: encaisse,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    borderRadius: 8,
                },
                {
                    label: 'Impayés',
                    data: impayes,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
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
                    labels: { usePointStyle: true, padding: 20, font: { family: 'Poppins', size: 11 } }
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
// GRAPHIQUE 3 : RÉPARTITION PAR MODE DE PAIEMENT (DONUT)
// ============================================================
function initGraphiqueModesPaiement() {
    const canvas = document.getElementById('chartModesPaiement');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const data = JSON.parse(canvas.dataset.data || '[]');
    const counts = JSON.parse(canvas.dataset.counts || '[]');

    if (labels.length === 0) return;

    const colors = ['#2c3e90', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'];

    new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 4,
                borderColor: '#ffffff',
                hoverBorderWidth: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 18,
                        font: { family: 'Poppins', size: 11 },
                        generateLabels: function(chart) {
                            var data = chart.data;
                            return data.labels.map(function(label, i) {
                                return {
                                    text: label + ' (' + counts[i] + ' paiements)',
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
                            var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                            var pct = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                            return context.label + ' : $ ' + context.raw.toLocaleString('fr-FR') + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
}

// ============================================================
// GRAPHIQUE 4 : ENCAISSEMENT PAR TYPE DE FRAIS (HORIZONTAL)
// ============================================================
function initGraphiqueFrais() {
    const canvas = document.getElementById('chartEncaissementFrais');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const encaisse = JSON.parse(canvas.dataset.encaisse || '[]');
    const impayes = JSON.parse(canvas.dataset.impayes || '[]');

    if (labels.length === 0) return;

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Encaissé',
                    data: encaisse,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    borderRadius: 8,
                },
                {
                    label: 'Impayés',
                    data: impayes,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 1.5,
                    borderRadius: 8,
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20, font: { family: 'Poppins', size: 11 } }
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
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return '$ ' + value.toLocaleString('fr-FR'); }
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
// FILTRES INTERACTIFS
// ============================================================
function initFiltresRapport() {
    var filterTrimestre = document.getElementById('filterTrimestre');
    var filterClasse = document.getElementById('filterClasseRapport');
    var filterMois = document.getElementById('filterMois');
    var btnAppliquer = document.getElementById('btnAppliquerFiltres');
    var btnReset = document.getElementById('btnResetFiltres');

    if (btnAppliquer) {
        btnAppliquer.addEventListener('click', function() {
            appliquerFiltresRapport();
        });
    }

    if (btnReset) {
        btnReset.addEventListener('click', function() {
            if (filterTrimestre) filterTrimestre.value = '';
            if (filterClasse) filterClasse.value = '';
            if (filterMois) filterMois.value = '';
            appliquerFiltresRapport();
        });
    }
}

function appliquerFiltresRapport() {
    var formData = new FormData();
    formData.append('action', 'getStatsPaiements');
    
    var trimestre = document.getElementById('filterTrimestre')?.value || '';
    var classe = document.getElementById('filterClasseRapport')?.value || '';
    var mois = document.getElementById('filterMois')?.value || '';
    
    if (trimestre) formData.append('trimestre_id', trimestre);
    if (classe) formData.append('classe_id', classe);
    if (mois) formData.append('mois', mois);

    var btnAppliquer = document.getElementById('btnAppliquerFiltres');
    if (btnAppliquer) {
        btnAppliquer.disabled = true;
        btnAppliquer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Filtrage...';
    }

    fetch('/gestion_scolaire/paiements', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            mettreAJourStats(data);
        }
    })
    .catch(function(error) {
        console.error('Erreur filtrage:', error);
        showToast('Erreur lors du filtrage.', 'error');
    })
    .finally(function() {
        if (btnAppliquer) {
            btnAppliquer.disabled = false;
            btnAppliquer.innerHTML = '<i class="fa-solid fa-filter"></i> Appliquer';
        }
    });
}

function mettreAJourStats(data) {
    // Mettre à jour les KPI
    var elements = {
        'kpiEncaissement': '$ ' + (data.total_encaissement || 0).toLocaleString('fr-FR'),
        'kpiAttendu': '$ ' + (data.total_attendu || 0).toLocaleString('fr-FR'),
        'kpiTaux': (data.taux_recouvrement || 0) + '%',
        'kpiImpayes': '$ ' + (data.total_impayes || 0).toLocaleString('fr-FR'),
    };

    Object.keys(elements).forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = elements[id];
    });
}

// ============================================================
// EXPORT PDF / IMPRESSION
// ============================================================
function initExportPDF() {
    var btnExport = document.getElementById('btnExportPDF');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            window.print();
        });
    }

    var btnImprimer = document.getElementById('btnImprimerRapport');
    if (btnImprimer) {
        btnImprimer.addEventListener('click', function() {
            window.print();
        });
    }
}

// ============================================================
// RAFRAÎCHISSEMENT AUTO
// ============================================================
function rafraichirDonnees() {
    var formData = new FormData();
    formData.append('action', 'getStatsPaiements');

    fetch('/gestion_scolaire/paiements', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            mettreAJourStats(data);
            showToast('Données actualisées.', 'info', 2000);
        }
    })
    .catch(function(error) {
        console.error('Erreur actualisation:', error);
    });
}

// ============================================================
// UTILITAIRES
// ============================================================
function formaterMontant(montant) {
    return '$ ' + parseFloat(montant || 0).toLocaleString('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

function formaterPourcentage(valeur, total) {
    if (!total || total === 0) return '0%';
    return Math.round((valeur / total) * 100) + '%';
}

function animerValeur(elementId, valeurFinale, duree) {
    duree = duree || 800;
    var element = document.getElementById(elementId);
    if (!element) return;

    var debut = 0;
    var debutTimestamp = null;

    function animation(timestamp) {
        if (!debutTimestamp) debutTimestamp = timestamp;
        var progression = Math.min((timestamp - debutTimestamp) / duree, 1);
        var valeurActuelle = Math.floor(progression * valeurFinale);
        element.textContent = '$ ' + valeurActuelle.toLocaleString('fr-FR');
        if (progression < 1) {
            requestAnimationFrame(animation);
        }
    }

    requestAnimationFrame(animation);
}