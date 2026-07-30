/* ============================================================
   Fichier : public/assets/js/modules/annees.js
   Rôle    : Gestion du module des années scolaires
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const flashMessage = document.getElementById('flashMessage');

    // ============================================================
    // 2. ACTIVER UNE ANNÉE
    // ============================================================
    window.activerAnnee = function(id, libelle) {
        showConfirmDialog(
            `Voulez-vous activer l'année <strong>"${escapeHtml(libelle)}"</strong> ?<br>
             <small>L'année actuellement active sera automatiquement désactivée.</small>`,
            async (confirmed) => {
                if (!confirmed) return;

                const formData = new FormData();
                formData.append('action', 'activer');
                formData.append('id', id);

                try {
                    const res = await fetch('/gestion_scolaire/annees-scolaires', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        showFlash('success', data.message);
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showFlash('error', data.message);
                    }
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            },
            { confirmText: 'Activer', confirmClass: 'btn-success', iconColor: '#10b981' }
        );
    };

    // ============================================================
    // 3. CLÔTURER UNE ANNÉE
    // ============================================================
    window.cloturerAnnee = function(id, libelle) {
        showConfirmDialog(
            `Voulez-vous clôturer l'année <strong>"${escapeHtml(libelle)}"</strong> ?<br>
             <small>Une année clôturée ne peut plus être modifiée ni activée. Cette action est irréversible.</small>`,
            async (confirmed) => {
                if (!confirmed) return;

                const formData = new FormData();
                formData.append('action', 'cloturer');
                formData.append('id', id);

                try {
                    const res = await fetch('/gestion_scolaire/annees-scolaires', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        showFlash('success', data.message);
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showFlash('error', data.message);
                    }
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            },
            { confirmText: 'Clôturer', confirmClass: 'btn-warning', iconColor: '#f59e0b' }
        );
    };

    // ============================================================
    // 4. SUPPRIMER UNE ANNÉE
    // ============================================================
    window.supprimerAnnee = function(id, libelle) {
        showConfirmDialog(
            `Êtes-vous sûr de vouloir supprimer l'année <strong>"${escapeHtml(libelle)}"</strong> ?<br>
             <small>Cette action est irréversible. Les trimestres seront également supprimés.</small>`,
            async (confirmed) => {
                if (!confirmed) return;

                const formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('id', id);

                try {
                    const res = await fetch('/gestion_scolaire/annees-scolaires', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        showFlash('success', data.message);
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showFlash('error', data.message);
                    }
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            },
            { confirmText: 'Supprimer', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
        );
    };

    // ============================================================
    // 5. VALIDATION FORMULAIRE (ajout/modification)
    // ============================================================
    const formAnnee = document.getElementById('formAnnee');
    
    if (formAnnee) {
        const libelleInput = document.getElementById('libelle');
        const dateDebutInput = document.getElementById('date_debut');
        const dateFinInput = document.getElementById('date_fin');

        // Validation libellé
        if (libelleInput) {
            libelleInput.addEventListener('input', function() {
                this.classList.remove('is-invalid', 'is-valid');
                const value = this.value.trim();
                if (value === '') {
                    this.classList.add('is-invalid');
                } else if (!/^\d{4}-\d{4}$/.test(value)) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.add('is-valid');
                }
            });
        }

        // Validation dates
        function validateDates() {
            if (dateDebutInput && dateFinInput) {
                const debut = dateDebutInput.value;
                const fin = dateFinInput.value;
                
                dateFinInput.classList.remove('is-invalid', 'is-valid');
                
                if (debut && fin && debut >= fin) {
                    dateFinInput.classList.add('is-invalid');
                    const feedback = document.getElementById('dateFinFeedback');
                    if (feedback) feedback.textContent = 'La date de fin doit être après la date de début.';
                    return false;
                }
                if (fin) {
                    dateFinInput.classList.add('is-valid');
                }
                return true;
            }
            return true;
        }

        if (dateDebutInput) {
            dateDebutInput.addEventListener('change', validateDates);
        }
        if (dateFinInput) {
            dateFinInput.addEventListener('change', validateDates);
        }

        // Soumission
        formAnnee.addEventListener('submit', function(e) {
            let isValid = true;

            // Valider libellé
            if (libelleInput) {
                const value = libelleInput.value.trim();
                if (value === '' || !/^\d{4}-\d{4}$/.test(value)) {
                    libelleInput.classList.add('is-invalid');
                    isValid = false;
                }
            }

            // Valider dates
            if (dateDebutInput && !dateDebutInput.value) {
                dateDebutInput.classList.add('is-invalid');
                isValid = false;
            }
            if (dateFinInput && !dateFinInput.value) {
                dateFinInput.classList.add('is-invalid');
                isValid = false;
            }
            if (!validateDates()) isValid = false;

            if (!isValid) {
                e.preventDefault();
                const firstError = formAnnee.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                showToast('Veuillez corriger les erreurs.', 'error');
            }
        });
    }

    // ============================================================
    // 6. MESSAGES FLASH
    // ============================================================
    function showFlash(type, message) {
        if (!flashMessage) return;
        const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
        flashMessage.innerHTML = `
            <div class="alert alert-${type} alert-dismissible">
                <i class="fa-solid ${icons[type] || icons.info}"></i>
                <span>${escapeHtml(message)}</span>
                <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
            </div>`;
        flashMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================================
    // 7. INITIALISATION
    // ============================================================
    console.log('%cModule Années Scolaires%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});