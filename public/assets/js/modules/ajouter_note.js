/* ============================================================
   Fichier : public/assets/js/modules/ajouter_evaluation.js
   Rôle    : Gestion du formulaire d'ajout d'évaluation
   Système : Bulletin congolais
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formEvaluation      = document.getElementById('formEvaluation');
    const selectClasse        = document.getElementById('classe_id');
    const selectTrimestre     = document.getElementById('trimestre_id');
    const dateEvaluation      = document.getElementById('date_evaluation');
    const dateHelp            = document.getElementById('dateHelp');
    const existingEvals       = document.getElementById('existingEvals');
    const typeCards           = document.querySelectorAll('.eval-type-card');

    if (!formEvaluation) return;

    // ============================================================
    // 2. SÉLECTION VISUELLE DU TYPE D'ÉVALUATION
    // ============================================================
    typeCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                
                // Effet visuel
                typeCards.forEach(c => {
                    c.querySelector('.eval-type-content').style.borderColor = 'var(--border-color)';
                    c.querySelector('.eval-type-content').style.backgroundColor = 'var(--color-white)';
                    c.querySelector('.eval-type-content').style.transform = 'translateY(0)';
                });
                
                const content = this.querySelector('.eval-type-content');
                content.style.borderColor = 'var(--color-primary)';
                content.style.backgroundColor = 'var(--color-primary-lighter)';
                content.style.transform = 'translateY(-2px)';
                content.style.boxShadow = '0 4px 16px rgba(44, 62, 144, 0.12)';
            }
        });

        // Accessibilité clavier
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // ============================================================
    // 3. VALIDATION DE LA DATE (doit être dans le trimestre)
    // ============================================================
    function validateDateInTrimestre() {
        const trimestreOption = selectTrimestre?.selectedOptions[0];
        if (!trimestreOption || !dateEvaluation) return;

        const dateDebut = trimestreOption.dataset.debut;
        const dateFin   = trimestreOption.dataset.fin;
        const dateValue = dateEvaluation.value;

        if (!dateValue || !dateDebut || !dateFin) {
            dateHelp.innerHTML = '<i class="fa-solid fa-info-circle"></i> La date doit être comprise dans le trimestre sélectionné.';
            dateHelp.style.color = 'var(--color-gray-400)';
            dateEvaluation.classList.remove('is-valid', 'is-invalid');
            return;
        }

        if (dateValue < dateDebut || dateValue > dateFin) {
            dateEvaluation.classList.add('is-invalid');
            dateEvaluation.classList.remove('is-valid');
            dateHelp.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> La date doit être comprise entre le <strong>' + 
                formatDateDisplay(dateDebut) + '</strong> et le <strong>' + formatDateDisplay(dateFin) + '</strong>.';
            dateHelp.style.color = 'var(--color-error)';
        } else {
            dateEvaluation.classList.remove('is-invalid');
            dateEvaluation.classList.add('is-valid');
            dateHelp.innerHTML = '<i class="fa-solid fa-check-circle"></i> Date valide pour ce trimestre.';
            dateHelp.style.color = 'var(--color-success)';
        }
    }

    function formatDateDisplay(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    // Écouteurs
    if (selectTrimestre) {
        selectTrimestre.addEventListener('change', () => {
            validateDateInTrimestre();
            loadExistingEvals();
        });
    }

    if (dateEvaluation) {
        dateEvaluation.addEventListener('change', validateDateInTrimestre);
        dateEvaluation.addEventListener('input', validateDateInTrimestre);
    }

    if (selectClasse) {
        selectClasse.addEventListener('change', loadExistingEvals);
    }

    // ============================================================
    // 4. CHARGEMENT DES ÉVALUATIONS EXISTANTES
    // ============================================================
    function loadExistingEvals() {
        const classeId    = selectClasse?.value;
        const trimestreId = selectTrimestre?.value;

        if (!existingEvals) return;
        if (!classeId || !trimestreId) {
            existingEvals.innerHTML = '<p style="font-size:0.82rem;color:var(--color-gray-500);">Sélectionnez une classe et un trimestre pour voir les évaluations déjà créées.</p>';
            resetTypeCards();
            return;
        }

        existingEvals.innerHTML = '<div style="text-align:center;padding:12px;"><i class="fa-solid fa-spinner fa-spin" style="color:var(--color-primary);"></i><p style="font-size:0.75rem;color:var(--color-gray-400);margin-top:6px;">Chargement...</p></div>';

        const formData = new FormData();
        formData.append('action', 'liste');
        formData.append('classe_id', classeId);
        formData.append('trimestre_id', trimestreId);

        fetch('/gestion_scolaire/notes', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.evaluations && data.evaluations.length > 0) {
                const typesExistants = data.evaluations.map(e => e.type_evaluation);
                
                existingEvals.innerHTML = data.evaluations.map(e => {
                    const badgeClass = e.type_evaluation === 'Période 1' ? 'info' : 
                                      (e.type_evaluation === 'Période 2' ? 'warning' : 'danger');
                    const nbNotes = e.nb_notes_saisies || 0;
                    const nbAttendues = e.nb_notes_attendues || 0;
                    const pct = nbAttendues > 0 ? Math.round((nbNotes / nbAttendues) * 100) : 0;
                    
                    return `
                        <div class="eval-existing-item" style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span class="badge badge-${badgeClass}" style="font-size:0.7rem;">${e.type_evaluation}</span>
                                <span style="font-size:0.78rem;color:var(--color-gray-600);">${formatDateDisplay(e.date_evaluation)}</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:80px;height:5px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
                                    <div style="width:${pct}%;height:100%;background:${pct >= 80 ? '#10b981' : (pct >= 40 ? '#f59e0b' : '#ef4444')};border-radius:10px;"></div>
                                </div>
                                <span style="font-size:0.72rem;color:var(--color-gray-400);">${nbNotes}/${nbAttendues}</span>
                            </div>
                        </div>
                    `;
                }).join('');

                // Désactiver les types déjà créés
                disableExistingTypes(typesExistants);
            } else {
                existingEvals.innerHTML = '<p style="font-size:0.82rem;color:var(--color-gray-500);">Aucune évaluation créée pour cette sélection.</p>';
                resetTypeCards();
            }
        })
        .catch(err => {
            console.error('Erreur chargement évaluations:', err);
            existingEvals.innerHTML = '<p style="font-size:0.82rem;color:var(--color-error);"><i class="fa-solid fa-triangle-exclamation"></i> Erreur de chargement.</p>';
        });
    }

    function disableExistingTypes(typesExistants) {
        typeCards.forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            if (radio && typesExistants.includes(radio.value)) {
                card.style.opacity = '0.4';
                card.style.pointerEvents = 'none';
                card.setAttribute('title', 'Cette évaluation existe déjà pour ce trimestre et cette classe');
            } else {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                card.removeAttribute('title');
            }
        });
    }

    function resetTypeCards() {
        typeCards.forEach(card => {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            card.removeAttribute('title');
        });
    }

    // ============================================================
    // 5. VALIDATION DU FORMULAIRE AVANT SOUMISSION
    // ============================================================
    if (formEvaluation) {
        formEvaluation.addEventListener('submit', function(e) {
            let hasError = false;

            // Vérifier la classe
            if (!selectClasse || selectClasse.value === '') {
                if (selectClasse) {
                    selectClasse.classList.add('is-invalid');
                }
                hasError = true;
            }

            // Vérifier le trimestre
            if (!selectTrimestre || selectTrimestre.value === '') {
                if (selectTrimestre) {
                    selectTrimestre.classList.add('is-invalid');
                }
                hasError = true;
            }

            // Vérifier le type d'évaluation
            const typeSelected = document.querySelector('input[name="type_evaluation"]:checked');
            if (!typeSelected) {
                showToast('Veuillez sélectionner un type d\'évaluation.', 'warning');
                hasError = true;
            }

            // Vérifier la date
            if (!dateEvaluation || dateEvaluation.value === '') {
                if (dateEvaluation) {
                    dateEvaluation.classList.add('is-invalid');
                }
                hasError = true;
            } else {
                validateDateInTrimestre();
                if (dateEvaluation.classList.contains('is-invalid')) {
                    hasError = true;
                }
            }

            if (hasError) {
                e.preventDefault();
                // Scroll vers la première erreur
                const firstError = formEvaluation.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });

        // Enlever les classes d'erreur au changement
        [selectClasse, selectTrimestre].forEach(select => {
            if (select) {
                select.addEventListener('change', function() {
                    this.classList.remove('is-invalid');
                });
            }
        });

        if (dateEvaluation) {
            dateEvaluation.addEventListener('change', function() {
                this.classList.remove('is-invalid');
            });
            dateEvaluation.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        }
    }

    // ============================================================
    // 6. SUGGESTION DE DATE SELON LE TYPE
    // ============================================================
    function suggestDate() {
        const trimestreOption = selectTrimestre?.selectedOptions[0];
        const typeRadio = document.querySelector('input[name="type_evaluation"]:checked');
        
        if (!trimestreOption || !typeRadio || !dateEvaluation) return;

        const dateDebut = trimestreOption.dataset.debut;
        const dateFin   = trimestreOption.dataset.fin;
        const type      = typeRadio.value;

        if (!dateDebut || !dateFin) return;

        let suggestedDate = dateDebut;

        switch (type) {
            case 'Période 1':
                suggestedDate = addDaysToDate(dateDebut, 30);
                break;
            case 'Période 2':
                suggestedDate = addDaysToDate(dateDebut, 60);
                break;
            case 'Examen':
                suggestedDate = addDaysToDate(dateFin, -7);
                break;
        }

        // Ne pas suggérer si l'utilisateur a déjà modifié la date manuellement
        if (!dateEvaluation.dataset.manualEdit) {
            dateEvaluation.value = suggestedDate;
        }
    }

    function addDaysToDate(dateStr, days) {
        const date = new Date(dateStr);
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }

    // Écouter les changements de type
    document.querySelectorAll('input[name="type_evaluation"]').forEach(radio => {
        radio.addEventListener('change', suggestDate);
    });

    // Marquer comme modifié manuellement
    if (dateEvaluation) {
        dateEvaluation.addEventListener('input', function() {
            this.dataset.manualEdit = 'true';
        });
    }

    // ============================================================
    // 7. RACCOURCI CLAVIER
    // ============================================================
    document.addEventListener('keydown', function(e) {
        // Ctrl+Enter pour soumettre
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (document.activeElement && document.activeElement.closest('#formEvaluation')) {
                formEvaluation.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        }
    });

    // ============================================================
    // 8. INITIALISATION
    // ============================================================
    // Charger les évaluations existantes au démarrage
    loadExistingEvals();

    // Valider la date initiale
    validateDateInTrimestre();

    // Suggérer une date initiale
    suggestDate();

    console.log('%cAjouter Évaluation%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});