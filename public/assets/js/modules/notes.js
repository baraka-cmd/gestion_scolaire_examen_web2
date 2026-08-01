/* ============================================================
   Fichier : public/assets/js/modules/evaluations.js
   Rôle    : Gestion de la liste des évaluations
   Système : Bulletin congolais — Filtres, chargement, actions
   Version : 2.0 — Refonte complète
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const filterClasse       = document.getElementById('filterClasse');
    const filterTrimestre    = document.getElementById('filterTrimestre');
    const evaluationsGrid    = document.getElementById('evaluationsGrid');
    const progressionContainer = document.getElementById('progressionContainer');
    const btnGenerer         = document.getElementById('btnGenerer');
    const flashMessage       = document.getElementById('flashMessage');

    if (!evaluationsGrid) return;

    // ============================================================
    // 2. CHARGEMENT DES ÉVALUATIONS
    // ============================================================

    function loadEvaluations() {
        if (!evaluationsGrid) return;

        // Afficher le loader
        evaluationsGrid.innerHTML = `
            <div style="text-align:center;padding:40px;grid-column:1/-1;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;color:var(--color-primary);"></i>
                <p style="margin-top:10px;color:var(--color-gray-500);">Chargement des évaluations...</p>
            </div>
        `;

        const formData = new FormData();
        formData.append('action', 'liste');

        if (filterClasse && filterClasse.value) {
            formData.append('classe_id', filterClasse.value);
        }
        if (filterTrimestre && filterTrimestre.value) {
            formData.append('trimestre_id', filterTrimestre.value);
        }

        fetch('/gestion_scolaire/notes', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderEvaluations(data.evaluations);
            } else {
                evaluationsGrid.innerHTML = `
                    <div class="empty-state" style="grid-column:1/-1;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <p>${data.message || 'Erreur lors du chargement.'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur chargement évaluations:', error);
            evaluationsGrid.innerHTML = `
                <div class="empty-state" style="grid-column:1/-1;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <p>Erreur de connexion au serveur.</p>
                </div>
            `;
        });
    }

    // ============================================================
    // 3. RENDU DES ÉVALUATIONS
    // ============================================================

    function renderEvaluations(evaluations) {
        if (!evaluationsGrid) return;

        if (!evaluations || evaluations.length === 0) {
            evaluationsGrid.innerHTML = `
                <div class="empty-state" style="grid-column:1/-1;">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <p>Aucune évaluation trouvée</p>
                    <a href="/gestion_scolaire/notes/ajouter" class="btn btn-primary btn-sm mt-3">
                        <i class="fa-solid fa-plus"></i> Créer une évaluation
                    </a>
                </div>
            `;
            return;
        }

        let html = '';

        evaluations.forEach(evaluation => {
            const nbSaisies = parseInt(evaluation.nb_notes_saisies) || 0;
            const nbAttendues = parseInt(evaluation.nb_notes_attendues) || 1;
            const pct = nbAttendues > 0 ? Math.round((nbSaisies / nbAttendues) * 100) : 0;

            // Déterminer les classes CSS selon le type
            let typeClass, typeBadgeClass, dotClass, iconBadge;

            switch (evaluation.type_evaluation) {
                case 'Période 1':
                    typeClass = 'periode1';
                    typeBadgeClass = 'periode1';
                    dotClass = 'periode1';
                    iconBadge = 'fa-1';
                    break;
                case 'Période 2':
                    typeClass = 'periode2';
                    typeBadgeClass = 'periode2';
                    dotClass = 'periode2';
                    iconBadge = 'fa-2';
                    break;
                case 'Examen':
                    typeClass = 'examen';
                    typeBadgeClass = 'examen';
                    dotClass = 'examen';
                    iconBadge = 'fa-file-pen';
                    break;
                default:
                    typeClass = '';
                    typeBadgeClass = '';
                    dotClass = '';
                    iconBadge = 'fa-circle';
            }

            // Progression
            const progressClass = pct >= 80 ? 'complete' : (pct > 0 ? 'in-progress' : 'empty');
            const progressLabel = pct >= 80 ? 'Complétée' : (pct > 0 ? 'En cours' : 'Aucune note');

            html += `
                <div class="evaluation-card" data-id="${evaluation.id}">
                    <div class="evaluation-card-header">
                        <div class="evaluation-card-type">
                            <span class="evaluation-type-dot ${dotClass}"></span>
                            <span class="evaluation-type-text">${escapeHtml(evaluation.type_evaluation)}</span>
                        </div>
                        <span class="evaluation-card-badge ${typeBadgeClass}">
                            <i class="fa-solid ${iconBadge}"></i> ${escapeHtml(evaluation.groupe_libelle)}
                        </span>
                    </div>
                    <div class="evaluation-card-body">
                        <div class="evaluation-card-info">
                            <div class="evaluation-card-info-row">
                                <i class="fa-solid fa-chalkboard"></i>
                                <span>Classe : <strong>${escapeHtml(evaluation.classe_nom)}</strong> (Niv. ${evaluation.classe_niveau})</span>
                            </div>
                            <div class="evaluation-card-info-row">
                                <i class="fa-solid fa-calendar-alt"></i>
                                <span>Trimestre : <strong>${escapeHtml(evaluation.trimestre_libelle)}</strong></span>
                            </div>
                            <div class="evaluation-card-info-row">
                                <i class="fa-regular fa-calendar"></i>
                                <span>Date : <strong>${formatDateDisplay(evaluation.date_evaluation)}</strong></span>
                            </div>
                            <div class="evaluation-card-info-row">
                                <i class="fa-solid fa-list-ul"></i>
                                <span>Sous-matières : <strong>${nbAttendues > 0 ? Math.round(nbAttendues / (parseInt(evaluation.nb_notes_saisies / Math.max(nbSaisies, 1)) || 1)) : '—'}</strong></span>
                            </div>
                        </div>

                        <!-- Barre de progression -->
                        <div class="evaluation-progress">
                            <div class="evaluation-progress-header">
                                <span class="evaluation-progress-label">Notes saisies</span>
                                <span class="evaluation-progress-value ${progressClass}">
                                    ${nbSaisies} / ${nbAttendues} (${pct}%)
                                </span>
                            </div>
                            <div class="evaluation-progress-bar">
                                <div class="evaluation-progress-fill ${progressClass}" style="width: ${pct}%;"></div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="evaluation-card-actions">
                            <a href="/gestion_scolaire/notes/saisie/${evaluation.id}" class="btn-saisir">
                                <i class="fa-solid fa-pen-to-square"></i> Saisir les notes
                            </a>
                            <a href="/gestion_scolaire/notes/ajouter?id=${evaluation.id}" class="btn-modifier" title="Modifier la date">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button type="button" class="btn-supprimer"
                                    onclick="supprimerEvaluation(${evaluation.id}, '${escapeHtml(evaluation.type_evaluation)}', '${escapeHtml(evaluation.groupe_libelle)}', '${escapeHtml(evaluation.classe_nom)}')"
                                    title="Supprimer"
                                    ${nbSaisies > 0 ? '' : ''}>
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        evaluationsGrid.innerHTML = html;
    }

    // ============================================================
    // 4. CHARGEMENT DE LA PROGRESSION
    // ============================================================

    function loadProgression() {
        if (!progressionContainer) return;

        const classeId = filterClasse ? filterClasse.value : '';
        const trimestreId = filterTrimestre ? filterTrimestre.value : '';

        if (!classeId || !trimestreId) {
            progressionContainer.innerHTML = '';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'getProgressionTrimestre');
        formData.append('classe_id', classeId);
        formData.append('trimestre_id', trimestreId);

        fetch('/gestion_scolaire/notes', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.progression) {
                renderProgression(data.progression);
            } else {
                progressionContainer.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Erreur chargement progression:', error);
            progressionContainer.innerHTML = '';
        });
    }

    function renderProgression(progression) {
        if (!progressionContainer) return;

        const types = ['Période 1', 'Période 2', 'Examen'];
        const icons = {
            'Période 1': 'fa-1',
            'Période 2': 'fa-2',
            'Examen': 'fa-file-pen'
        };
        const iconClasses = {
            'Période 1': 'periode1',
            'Période 2': 'periode2',
            'Examen': 'examen'
        };

        let html = `
            <div class="progression-card">
                <div class="progression-card-header">
                    <span class="progression-card-title">
                        <i class="fa-solid fa-chart-bar"></i> Progression du trimestre
                    </span>
                </div>
                <div class="progression-card-body">
                    <div class="progression-grid">
        `;

        types.forEach(type => {
            const prog = progression[type] || { nb_notes: 0, nb_attendues: 0, pourcentage: 0, nb_evaluations: 0 };
            const iconClass = iconClasses[type] || '';

            html += `
                <div class="progression-item">
                    <div class="progression-item-icon ${iconClass}">
                        <i class="fa-solid ${icons[type] || 'fa-circle'}"></i>
                    </div>
                    <div class="progression-item-title">${type}</div>
                    <div class="progression-item-stats">
                        ${prog.nb_evaluations} évaluation(s) — ${prog.nb_notes} / ${prog.nb_attendues} notes
                    </div>
                    <div class="progression-item-bar">
                        <div class="progression-item-fill ${iconClass}" style="width: ${prog.pourcentage}%;"></div>
                    </div>
                </div>
            `;
        });

        html += `
                    </div>
                </div>
            </div>
        `;

        progressionContainer.innerHTML = html;
    }

    // ============================================================
    // 5. GÉNÉRATION AUTOMATIQUE
    // ============================================================

    if (btnGenerer) {
        btnGenerer.addEventListener('click', function() {
            const classeId = filterClasse ? filterClasse.value : '';
            const trimestreId = filterTrimestre ? filterTrimestre.value : '';

            if (!classeId || !trimestreId) {
                showToast('Veuillez sélectionner une classe et un trimestre.', 'warning');
                return;
            }

            const classeNom = filterClasse ? filterClasse.selectedOptions[0].text : 'cette classe';
            const trimestreNom = filterTrimestre ? filterTrimestre.selectedOptions[0].text : 'ce trimestre';

            showConfirmDialog(
                `Voulez-vous générer automatiquement <strong>toutes les évaluations manquantes</strong> pour <strong>${classeNom}</strong> — <strong>${trimestreNom}</strong> ?<br><br>
                <small class="text-muted">Cela créera une évaluation par groupe de matières (P1, P2, Examen) si elle n'existe pas déjà.</small>`,
                function(confirmed) {
                    if (confirmed) {
                        executerGeneration(classeId, trimestreId);
                    }
                },
                {
                    title: 'Générer les évaluations',
                    confirmText: 'Générer',
                    confirmClass: 'btn-success',
                    icon: 'fa-wand-magic-sparkles',
                    iconColor: '#10b981'
                }
            );
        });
    }

    function executerGeneration(classeId, trimestreId) {
        if (btnGenerer) {
            btnGenerer.disabled = true;
            btnGenerer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Génération...';
        }

        const formData = new FormData();
        formData.append('action', 'genererEvaluations');
        formData.append('classe_id', classeId);
        formData.append('trimestre_id', trimestreId);

        fetch('/gestion_scolaire/notes', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (btnGenerer) {
                btnGenerer.disabled = false;
                btnGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les évaluations';
            }

            if (data.success) {
                showToast(data.message, 'success');
                loadEvaluations();
                loadProgression();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur génération:', error);
            if (btnGenerer) {
                btnGenerer.disabled = false;
                btnGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les évaluations';
            }
            showToast('Erreur lors de la génération.', 'error');
        });
    }

    // ============================================================
    // 6. SUPPRESSION D'UNE ÉVALUATION
    // ============================================================

    window.supprimerEvaluation = function(id, type, groupe, classe) {
        showConfirmDialog(
            `Voulez-vous vraiment supprimer l'évaluation <strong>${type} — ${groupe}</strong> de la classe <strong>${classe}</strong> ?<br>
            <small class="text-muted">Les notes déjà saisies seront également supprimées.</small>`,
            function(confirmed) {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('action', 'supprimer');
                    formData.append('id', id);

                    fetch('/gestion_scolaire/notes', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            loadEvaluations();
                            loadProgression();
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur suppression:', error);
                        showToast('Erreur de connexion.', 'error');
                    });
                }
            },
            {
                title: 'Supprimer l\'évaluation',
                confirmText: 'Supprimer',
                confirmClass: 'btn-danger',
                icon: 'fa-trash',
                iconColor: '#ef4444'
            }
        );
    };

    // ============================================================
    // 7. FILTRES
    // ============================================================

    if (filterClasse) {
        filterClasse.addEventListener('change', () => {
            loadEvaluations();
            loadProgression();
        });
    }

    if (filterTrimestre) {
        filterTrimestre.addEventListener('change', () => {
            loadEvaluations();
            loadProgression();
        });
    }

    // ============================================================
    // 8. FONCTIONS UTILITAIRES
    // ============================================================

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDateDisplay(dateStr) {
        if (!dateStr) return '—';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    // ============================================================
    // 9. INITIALISATION
    // ============================================================

    loadEvaluations();
    loadProgression();

    console.log('%cÉvaluations%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 700;', 'color: #64748b;');
});