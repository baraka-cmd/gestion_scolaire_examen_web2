/* ============================================================
   Fichier : public/assets/js/dashboard/dashboard.js
   Rôle    : Graphiques et interactions du tableau de bord
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const chartElevesClasse    = document.getElementById('chartElevesClasse');
    const chartPaiements       = document.getElementById('chartPaiements');
    const chartPresences       = document.getElementById('chartPresences');
    const chartSexe            = document.getElementById('chartSexe');
    const chartResultats       = document.getElementById('chartResultats');
    const tableDerniersEleves  = document.getElementById('tableDerniersEleves');
    const tableAnniversaires   = document.getElementById('tableAnniversaires');
    const tableActivites       = document.getElementById('tableActivites');
    const skeletonStats        = document.querySelectorAll('.skeleton-stat');
    const skeletonCharts       = document.querySelectorAll('.skeleton-chart');
    const skeletonTables       = document.querySelectorAll('.skeleton-table');

    // Si on n'est pas sur le dashboard, sortir
    if (!chartElevesClasse && !chartPaiements) return;

    // ============================================================
    // 2. COULEURS DES GRAPHIQUES
    // ============================================================
    const chartColors = {
        blue: '#2c3e90',
        blueLight: 'rgba(44, 62, 144, 0.15)',
        green: '#10b981',
        greenLight: 'rgba(16, 185, 129, 0.15)',
        orange: '#f59e0b',
        orangeLight: 'rgba(245, 158, 11, 0.15)',
        red: '#ef4444',
        redLight: 'rgba(239, 68, 68, 0.15)',
        purple: '#8b5cf6',
        purpleLight: 'rgba(139, 92, 246, 0.15)',
        cyan: '#06b6d4',
        cyanLight: 'rgba(6, 182, 212, 0.15)',
        pink: '#ec4899',
        pinkLight: 'rgba(236, 72, 153, 0.15)',
    };

    const palette6 = ['#2c3e90', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
    const palette6Light = [
        'rgba(44, 62, 144, 0.15)',
        'rgba(16, 185, 129, 0.15)',
        'rgba(245, 158, 11, 0.15)',
        'rgba(239, 68, 68, 0.15)',
        'rgba(139, 92, 246, 0.15)',
        'rgba(6, 182, 212, 0.15)'
    ];

    const palette12 = [
        '#2c3e90', '#3498db', '#10b981', '#f59e0b',
        '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899',
        '#84cc16', '#f97316', '#6366f1', '#14b8a6'
    ];

    // ============================================================
    // 3. OPTIONS GLOBALES CHART.JS
    // ============================================================
    const globalFont = 'Poppins, sans-serif';

    Chart.defaults.font.family = globalFont;
    Chart.defaults.font.size = 11;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(30, 41, 59, 0.9)';
    Chart.defaults.plugins.tooltip.titleFont = { family: globalFont, size: 12, weight: '600' };
    Chart.defaults.plugins.tooltip.bodyFont = { family: globalFont, size: 11 };
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.displayColors = false;

    // ============================================================
    // 4. GRAPHIQUE 1 — ÉLÈVES PAR CLASSE (BARRES HORIZONTALES)
    // ============================================================
    if (chartElevesClasse) {
        fetchDashboardData('getElevesParClasse', (data) => {
            const ctx = chartElevesClasse.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Nombre d\'élèves',
                        data: data.values,
                        backgroundColor: palette6,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 45,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: {
                                stepSize: 1,
                                font: { family: globalFont, size: 10 },
                                color: '#94a3b8',
                            },
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                font: { family: globalFont, size: 11, weight: '500' },
                                color: '#334155',
                            },
                        },
                    },
                },
            });
            hideSkeleton('skeleton-chart-eleves');
        });
    }

    // ============================================================
    // 5. GRAPHIQUE 2 — PAIEMENTS MENSUELS (LIGNE)
    // ============================================================
    if (chartPaiements) {
        fetchDashboardData('getPaiementsMensuels', (data) => {
            const ctx = chartPaiements.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Paiements',
                        data: data.values,
                        borderColor: chartColors.green,
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: chartColors.green,
                        pointBorderColor: '#ffffff',
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
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: {
                                font: { family: globalFont, size: 10 },
                                color: '#94a3b8',
                                callback: (value) => '$ ' + value.toLocaleString(),
                            },
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { family: globalFont, size: 10 },
                                color: '#94a3b8',
                            },
                        },
                    },
                },
            });
            hideSkeleton('skeleton-chart-paiements');
        });
    }

    // ============================================================
    // 6. GRAPHIQUE 3 — TAUX DE PRÉSENCE PAR CLASSE (DOUGHNUT)
    // ============================================================
    if (chartPresences) {
        fetchDashboardData('getPresenceParClasse', (data) => {
            const ctx = chartPresences.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: palette6,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverBorderWidth: 4,
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
                                padding: 20,
                                usePointStyle: true,
                                pointStyleWidth: 10,
                                font: { family: globalFont, size: 10 },
                                color: '#64748b',
                            },
                        },
                    },
                },
            });
            hideSkeleton('skeleton-chart-presences');
        });
    }

    // ============================================================
    // 7. GRAPHIQUE 4 — RÉPARTITION FILLES/GARÇONS (PIE)
    // ============================================================
    if (chartSexe) {
        fetchDashboardData('getRepartitionSexe', (data) => {
            const ctx = chartSexe.getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: ['#3b82f6', '#ec4899'],
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverBorderWidth: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyleWidth: 10,
                                font: { family: globalFont, size: 11 },
                                color: '#64748b',
                            },
                        },
                    },
                },
            });
            hideSkeleton('skeleton-chart-sexe');
        });
    }

    // ============================================================
    // 8. GRAPHIQUE 5 — RÉSULTATS PAR TRIMESTRE (BARRES GROUPÉES)
    // ============================================================
    if (chartResultats) {
        fetchDashboardData('getResultatsParTrimestre', (data) => {
            const ctx = chartResultats.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Réussite',
                            data: data.reussite,
                            backgroundColor: chartColors.green,
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Ajournement',
                            data: data.ajournement,
                            backgroundColor: chartColors.orange,
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Échec',
                            data: data.echec,
                            backgroundColor: chartColors.red,
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyleWidth: 10,
                                font: { family: globalFont, size: 10 },
                                color: '#64748b',
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            stacked: true,
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: {
                                font: { family: globalFont, size: 10 },
                                color: '#94a3b8',
                            },
                        },
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: {
                                font: { family: globalFont, size: 10 },
                                color: '#64748b',
                            },
                        },
                    },
                },
            });
            hideSkeleton('skeleton-chart-resultats');
        });
    }

    // ============================================================
    // 9. CHARGEMENT DES DONNÉES DES TABLEAUX
    // ============================================================
    if (tableDerniersEleves) {
        loadDerniersEleves();
    }

    if (tableAnniversaires) {
        loadAnniversaires();
    }

    if (tableActivites) {
        loadActivitesRecentes();
    }

    // ============================================================
    // 10. FONCTIONS DE CHARGEMENT
    // ============================================================
    async function fetchDashboardData(action, callback) {
        try {
            const response = await fetch('/gestion_scolaire/dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: `action=${action}`
            });

            const data = await response.json();
            if (data.success && callback) {
                callback(data);
            }
        } catch (error) {
            console.error('Erreur dashboard:', error);
        }
    }

    async function loadDerniersEleves() {
        try {
            const response = await fetch('/gestion_scolaire/dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: 'action=getDerniersEleves'
            });

            const data = await response.json();
            if (data.success) {
                const tbody = tableDerniersEleves.querySelector('tbody');
                if (data.eleves.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    <p>Aucun élève inscrit</p>
                                </div>
                            </td>
                        </tr>`;
                } else {
                    tbody.innerHTML = data.eleves.map(e => `
                        <tr>
                            <td><span class="badge badge-primary">${escapeHtml(e.matricule)}</span></td>
                            <td>
                                <div class="user-cell">
                                    <div class="table-avatar-placeholder" style="background:${palette12[Math.floor(Math.random() * 12)]}">
                                        ${escapeHtml(e.prenom.charAt(0))}${escapeHtml(e.nom.charAt(0))}
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name">${escapeHtml(e.prenom)} ${escapeHtml(e.nom)}</span>
                                        <span class="user-detail">${escapeHtml(e.classe || '—')}</span>
                                    </div>
                                </div>
                            </td>
                            <td>${escapeHtml(e.sexe === 'M' ? 'Masculin' : 'Féminin')}</td>
                            <td>${escapeHtml(e.date_inscription)}</td>
                            <td><span class="badge badge-success">Actif</span></td>
                        </tr>
                    `).join('');
                }
                hideSkeleton('skeleton-table-eleves');
            }
        } catch (error) {
            console.error('Erreur chargement élèves:', error);
        }
    }

    async function loadAnniversaires() {
        try {
            const response = await fetch('/gestion_scolaire/dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: 'action=getAnniversaires'
            });

            const data = await response.json();
            if (data.success) {
                const tbody = tableAnniversaires.querySelector('tbody');
                if (data.anniversaires.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fa-solid fa-cake-candles"></i>
                                    <p>Aucun anniversaire dans les 7 prochains jours</p>
                                </div>
                            </td>
                        </tr>`;
                } else {
                    tbody.innerHTML = data.anniversaires.map(a => `
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="table-avatar-placeholder" style="background:${palette12[Math.floor(Math.random() * 12)]}">
                                        ${escapeHtml(a.prenom.charAt(0))}${escapeHtml(a.nom.charAt(0))}
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name">${escapeHtml(a.prenom)} ${escapeHtml(a.nom)}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-info">${escapeHtml(a.date_naissance)}</span></td>
                            <td><strong>${escapeHtml(a.age)} ans</strong></td>
                            <td><i class="fa-solid fa-cake-candles" style="color:#f59e0b;"></i></td>
                        </tr>
                    `).join('');
                }
                hideSkeleton('skeleton-table-anniversaires');
            }
        } catch (error) {
            console.error('Erreur chargement anniversaires:', error);
        }
    }

    async function loadActivitesRecentes() {
        try {
            const response = await fetch('/gestion_scolaire/dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: 'action=getActivites'
            });

            const data = await response.json();
            if (data.success) {
                const tbody = tableActivites.querySelector('tbody');
                if (data.activites.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="2">
                                <div class="empty-state">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <p>Aucune activité récente</p>
                                </div>
                            </td>
                        </tr>`;
                } else {
                    const colors = ['green', 'blue', 'orange', 'purple', 'red', 'cyan'];
                    tbody.innerHTML = data.activites.map((a, i) => `
                        <tr>
                            <td>
                                <div class="activity-item">
                                    <span class="activity-dot ${colors[i % colors.length]}"></span>
                                    <div class="activity-content">
                                        <p class="activity-text">${escapeHtml(a.description)}</p>
                                        <span class="activity-time">
                                            <i class="fa-regular fa-clock me-1"></i>${escapeHtml(a.date_action)}
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }
                hideSkeleton('skeleton-table-activites');
            }
        } catch (error) {
            console.error('Erreur chargement activités:', error);
        }
    }

    // ============================================================
    // 11. UTILITAIRES
    // ============================================================
    function hideSkeleton(id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = 'none';
            const content = el.nextElementSibling;
            if (content) content.style.display = 'block';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================================
    // 12. RAFRAÎCHISSEMENT AUTOMATIQUE (toutes les 5 minutes)
    // ============================================================
    let refreshInterval = setInterval(() => {
        if (document.hidden) return; // Ne pas rafraîchir si l'onglet n'est pas actif
        location.reload();
    }, 300000); // 5 minutes

    // Arrêter le rafraîchissement si l'utilisateur quitte la page
    window.addEventListener('beforeunload', () => {
        clearInterval(refreshInterval);
    });

});