/**
 * Fichier : public/assets/js/modules/logs.js
 * Rôle    : Scripts de la page des journaux d'activité
 * Système : École primaire congolaise — Administration
 * Version : 1.0
 */

'use strict';

// ============================================================
// VARIABLES GLOBALES
// ============================================================
var searchTimeout;
var chartTopActions = null;
var chartActiviteJour = null;
var rafraichissementAuto = null;

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    chargerLogs(1);
    chargerStats();
    chargerTopActions();
    chargerTopUtilisateurs();
    chargerActiviteParJour();
    demarrerRafraichissementAuto();
});

// ============================================================
// 1. CHARGEMENT DES LOGS
// ============================================================
function chargerLogs(page) {
    var tbody = document.getElementById('tbodyLogs');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state" style="padding:30px;"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement des logs...</p></div></td></tr>';

    var formData = new FormData();
    formData.append('action', 'getListe');
    formData.append('page', page || 1);
    formData.append('utilisateur_id', document.getElementById('filterUtilisateur')?.value || '');
    formData.append('action_filtre', document.getElementById('filterAction')?.value || '');
    formData.append('table_concernee', document.getElementById('filterTable')?.value || '');
    formData.append('date_debut', document.getElementById('filterDateDebut')?.value || '');
    formData.append('date_fin', document.getElementById('filterDateFin')?.value || '');
    formData.append('recherche', document.getElementById('searchLogs')?.value || '');

    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            afficherLogs(data);
        } else {
            showToast(data.message || 'Erreur de chargement.', 'error');
        }
    })
    .catch(function() {
        tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state" style="padding:30px;"><i class="fa-solid fa-circle-exclamation"></i><p>Erreur de connexion au serveur.</p></div></td></tr>';
    });
}

// ============================================================
// 2. AFFICHAGE DES LOGS
// ============================================================
function afficherLogs(data) {
    var tbody = document.getElementById('tbodyLogs');
    var totalSpan = document.getElementById('totalLogsTable');
    var paginationDiv = document.getElementById('paginationLogs');
    var paginationInfo = document.getElementById('paginationInfo');

    if (!tbody) return;
    if (totalSpan) totalSpan.textContent = data.total;

    if (!data.logs || data.logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state" style="padding:40px;"><i class="fa-solid fa-clock-rotate-left"></i><p>Aucun log trouvé avec ces filtres.</p></div></td></tr>';
        if (paginationDiv) paginationDiv.innerHTML = '';
        if (paginationInfo) paginationInfo.textContent = '';
        return;
    }

    tbody.innerHTML = data.logs.map(function(l) {
        var actionLower = (l.action || '').toLowerCase();
        var actionBadge = '';

        if (actionLower.indexOf('connexion') !== -1) {
            actionBadge = '<span class="badge badge-info"><i class="fa-solid fa-right-to-bracket"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('création') !== -1 || actionLower.indexOf('creation') !== -1 || actionLower.indexOf('upload') !== -1) {
            actionBadge = '<span class="badge badge-success"><i class="fa-solid fa-plus-circle"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('modification') !== -1 || actionLower.indexOf('saisie') !== -1) {
            actionBadge = '<span class="badge badge-warning"><i class="fa-solid fa-pen-to-square"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('suppression') !== -1 || actionLower.indexOf('échec') !== -1 || actionLower.indexOf('echec') !== -1) {
            actionBadge = '<span class="badge badge-danger"><i class="fa-solid fa-trash"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('accès') !== -1 || actionLower.indexOf('refusé') !== -1 || actionLower.indexOf('deny') !== -1) {
            actionBadge = '<span class="badge badge-dark"><i class="fa-solid fa-ban"></i> ' + escapeHtml(l.action) + '</span>';
        } else if (actionLower.indexOf('déconnexion') !== -1 || actionLower.indexOf('deconnexion') !== -1) {
            actionBadge = '<span class="badge badge-secondary"><i class="fa-solid fa-right-from-bracket"></i> ' + escapeHtml(l.action) + '</span>';
        } else {
            actionBadge = '<span class="badge badge-secondary">' + escapeHtml(l.action) + '</span>';
        }

        return '<tr>' +
            '<td><i class="fa-solid fa-circle" style="font-size:0.5rem;color:#94a3b8;"></i></td>' +
            '<td><strong>' + escapeHtml(l.date_format) + '</strong><br><small class="text-muted">' + escapeHtml(l.heure_format) + '</small></td>' +
            '<td><small>' + escapeHtml(l.utilisateur_nom || 'Système') + '</small></td>' +
            '<td>' + actionBadge + '</td>' +
            '<td><small>' + escapeHtml(l.table_concernee || '—') + '</small></td>' +
            '<td><small>' + escapeHtml(l.description || '—') + '</small></td>' +
            '<td><small class="text-muted">' + escapeHtml(l.adresse_ip || '—') + '</small></td>' +
        '</tr>';
    }).join('');

    // Pagination
    if (data.pages > 1) {
        var html = '';
        for (var i = 1; i <= data.pages; i++) {
            html += '<a href="#" onclick="chargerLogs(' + i + '); return false;" class="' + (i === data.page ? 'active' : '') + '">' + i + '</a>';
        }
        if (paginationDiv) paginationDiv.innerHTML = html;
        if (paginationInfo) paginationInfo.textContent = 'Page ' + data.page + ' / ' + data.pages + ' — ' + data.total + ' log(s)';
    } else {
        if (paginationDiv) paginationDiv.innerHTML = '';
        if (paginationInfo) paginationInfo.textContent = '';
    }
}

// ============================================================
// 3. STATISTIQUES
// ============================================================
function chargerStats() {
    var formData = new FormData();
    formData.append('action', 'getStatistiques');

    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            mettreAJourStats(data.stats);
        }
    })
    .catch(function() {
        console.error('Erreur chargement statistiques logs');
    });
}

function mettreAJourStats(stats) {
    var statAujourdhui = document.getElementById('statAujourdhui');
    var statSemaine = document.getElementById('statSemaine');
    var statUtilisateurs = document.getElementById('statUtilisateurs');
    var statDerniereActivite = document.getElementById('statDerniereActivite');

    if (statAujourdhui) statAujourdhui.textContent = stats.logs_aujourdhui.toLocaleString('fr-FR');
    if (statSemaine) statSemaine.textContent = stats.logs_semaine.toLocaleString('fr-FR');
    if (statUtilisateurs) statUtilisateurs.textContent = stats.nb_utilisateurs;

    if (statDerniereActivite && stats.derniere_activite) {
        statDerniereActivite.innerHTML = 
            '<small>' + escapeHtml(stats.derniere_activite.utilisateur_nom || 'Système') + '</small><br>' +
            '<small class="text-muted">' + escapeHtml(stats.derniere_activite.date_format) + '</small>';
    }
}

// ============================================================
// 4. TOP 10 ACTIONS (GRAPHIQUE)
// ============================================================
function chargerTopActions() {
    var formData = new FormData();
    formData.append('action', 'getTopActions');

    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    )
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.top_actions && data.top_actions.length > 0) {
            afficherGraphiqueTopActions(data.top_actions);
        }
    })
    .catch(function() {
        console.error('Erreur chargement top actions');
    });
}

function afficherGraphiqueTopActions(topActions) {
    var ctx = document.getElementById('chartTopActions');
    if (!ctx) return;

    if (chartTopActions) chartTopActions.destroy();

    chartTopActions = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: topActions.map(function(a) { return a.action; }),
            datasets: [{
                label: 'Nombre d\'actions',
                data: topActions.map(function(a) { return a.nb; }),
                backgroundColor: 'rgba(44, 62, 144, 0.75)',
                borderColor: '#2c3e90',
                borderWidth: 1.5,
                borderRadius: 8,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.raw + ' action(s)';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        stepSize: 1,
                        font: { family: 'Poppins', size: 11 },
                        callback: function(value) { return Math.floor(value) === value ? value : ''; }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 11 } }
                }
            }
        }
    });
}

// ============================================================
// 5. TOP 5 UTILISATEURS (TABLEAU)
// ============================================================
function chargerTopUtilisateurs() {
    var formData = new FormData();
    formData.append('action', 'getTopUtilisateurs');

    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            afficherTopUtilisateurs(data.top_utilisateurs);
        }
    })
    .catch(function() {
        console.error('Erreur chargement top utilisateurs');
    });
}

function afficherTopUtilisateurs(topUtilisateurs) {
    var tbody = document.getElementById('tbodyTopUtilisateurs');
    if (!tbody) return;

    if (!topUtilisateurs || topUtilisateurs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state" style="padding:20px;"><p>Aucune donnée.</p></div></td></tr>';
        return;
    }

    var icones = [
        '<i class="fa-solid fa-medal" style="color:#f59e0b;"></i>',
        '<i class="fa-solid fa-medal" style="color:#94a3b8;"></i>',
        '<i class="fa-solid fa-medal" style="color:#cd853f;"></i>'
    ];

    tbody.innerHTML = topUtilisateurs.map(function(u, i) {
        return '<tr>' +
            '<td>' + (icones[i] || ('#' + (i + 1))) + '</td>' +
            '<td><strong>' + escapeHtml(u.utilisateur_nom) + '</strong><br><small>@' + escapeHtml(u.login) + '</small></td>' +
            '<td><span class="badge badge-primary">' + u.nb + '</span></td>' +
            '<td><small>' + escapeHtml(u.derniere_action_format) + '</small></td>' +
        '</tr>';
    }).join('');
}

// ============================================================
// 6. ACTIVITÉ PAR JOUR (GRAPHIQUE)
// ============================================================
function chargerActiviteParJour() {
    var formData = new FormData();
    formData.append('action', 'getActiviteParJour');

    fetch('/gestion_scolaire/logs', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.activite && data.activite.length > 0) {
            afficherGraphiqueActiviteJour(data.activite);
        }
    })
    .catch(function() {
        console.error('Erreur chargement activité par jour');
    });
}

function afficherGraphiqueActiviteJour(activite) {
    var ctx = document.getElementById('chartActiviteJour');
    if (!ctx) return;

    if (chartActiviteJour) chartActiviteJour.destroy();

    chartActiviteJour = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: activite.map(function(a) { return a.jour + ' ' + a.label; }),
            datasets: [{
                label: 'Actions',
                data: activite.map(function(a) { return a.nb; }),
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
                            return context.raw + ' action(s)';
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
                    ticks: { font: { family: 'Poppins', size: 10 } }
                }
            }
        }
    });
}

// ============================================================
// 7. FILTRES
// ============================================================
function appliquerFiltres() {
    chargerLogs(1);
}

function reinitialiserFiltres() {
    var filtres = ['filterUtilisateur', 'filterAction', 'filterTable', 'filterDateDebut', 'filterDateFin', 'searchLogs'];
    filtres.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    chargerLogs(1);
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        chargerLogs(1);
    }, 400);
}

// ============================================================
// 8. VIDER LES LOGS
// ============================================================
function viderLogs() {
    showConfirmDialog(
        'Voulez-vous nettoyer les anciens logs ?<br><small>Les 100 logs les plus récents seront conservés pour la sécurité.</small>',
        function(confirmed) {
            if (confirmed) {
                var formData = new FormData();
                formData.append('action', 'viderLogs');

                var btnVider = document.getElementById('btnViderLogs');
                if (btnVider) {
                    btnVider.disabled = true;
                    btnVider.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Nettoyage...';
                }

                fetch('/gestion_scolaire/logs', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        showToast(data.message, 'success');
                        chargerLogs(1);
                        chargerStats();
                    } else {
                        showToast(data.message || 'Erreur lors du nettoyage.', 'error');
                    }
                })
                .catch(function() {
                    showToast('Erreur de connexion.', 'error');
                })
                .finally(function() {
                    if (btnVider) {
                        btnVider.disabled = false;
                        btnVider.innerHTML = '<i class="fa-solid fa-eraser"></i> Nettoyer les logs';
                    }
                });
            }
        },
        {
            title: 'Nettoyer les logs',
            confirmText: 'Nettoyer',
            confirmClass: 'btn-danger',
            icon: 'fa-eraser',
            iconColor: '#ef4444'
        }
    );
}

// ============================================================
// 9. RAFRAÎCHISSEMENT AUTOMATIQUE
// ============================================================
function demarrerRafraichissementAuto() {
    rafraichissementAuto = setInterval(function() {
        chargerStats();
    }, 60000); // Actualiser les stats toutes les 60 secondes
}

window.addEventListener('beforeunload', function() {
    if (rafraichissementAuto) {
        clearInterval(rafraichissementAuto);
        rafraichissementAuto = null;
    }
    if (chartTopActions) chartTopActions.destroy();
    if (chartActiviteJour) chartActiviteJour.destroy();
});

// ============================================================
// 10. UTILITAIRES
// ============================================================
function escapeHtml(texte) {
    if (!texte) return '';
    var div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}