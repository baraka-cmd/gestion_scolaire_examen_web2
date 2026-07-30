/* ============================================================
   Fichier : public/assets/js/modules/ajouter_classe.js
   Rôle    : Gestion du formulaire d'ajout de classe
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formAddClasse       = document.getElementById('formAddClasse');
    const nomInput            = document.getElementById('nom');
    const niveauSelect        = document.getElementById('niveau');
    const salleInput          = document.getElementById('salle');
    const capaciteInput       = document.getElementById('capacite_max');
    const anneeSelect         = document.getElementById('annee_scolaire_id');
    const titulaireSelect     = document.getElementById('titulaire_id');
    const descriptionInput    = document.getElementById('description');
    const classePreview       = document.getElementById('classePreview');

    if (!formAddClasse) return;

    // ============================================================
    // 2. VALIDATION EN TEMPS RÉEL — NOM
    // ============================================================
    if (nomInput) {
        nomInput.addEventListener('input', validateNom);
        nomInput.addEventListener('blur', validateNom);
    }

    function validateNom() {
        const value = nomInput.value.trim();
        nomInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('nomFeedback');

        if (value === '') {
            nomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Le nom de la classe est obligatoire.';
            return false;
        }
        if (value.length < 2) {
            nomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Minimum 2 caractères.';
            return false;
        }
        if (value.length > 50) {
            nomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 50 caractères.';
            return false;
        }

        nomInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updatePreview();
        return true;
    }

    // ============================================================
    // 3. VALIDATION — NIVEAU
    // ============================================================
    if (niveauSelect) {
        niveauSelect.addEventListener('change', function() {
            this.classList.remove('is-invalid', 'is-valid');
            const feedback = document.getElementById('niveauFeedback');
            if (!this.value) {
                this.classList.add('is-invalid');
                if (feedback) feedback.textContent = 'Veuillez sélectionner un niveau.';
            } else {
                this.classList.add('is-valid');
                if (feedback) feedback.textContent = '';
                updatePreview();
            }
        });
    }

    // ============================================================
    // 4. VALIDATION — SALLE
    // ============================================================
    if (salleInput) {
        salleInput.addEventListener('input', validateSalle);
        salleInput.addEventListener('blur', validateSalle);
    }

    function validateSalle() {
        const value = salleInput.value.trim();
        salleInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('salleFeedback');

        if (value === '') {
            salleInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'La salle est obligatoire.';
            return false;
        }
        if (value.length > 20) {
            salleInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 20 caractères.';
            return false;
        }

        salleInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updatePreview();
        return true;
    }

    // ============================================================
    // 5. VALIDATION — CAPACITÉ
    // ============================================================
    if (capaciteInput) {
        capaciteInput.addEventListener('input', validateCapacite);
        capaciteInput.addEventListener('blur', validateCapacite);
    }

    function validateCapacite() {
        const value = parseInt(capaciteInput.value);
        capaciteInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('capaciteFeedback');

        if (isNaN(value) || value < 1) {
            capaciteInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Minimum 1 élève.';
            return false;
        }
        if (value > 200) {
            capaciteInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 200 élèves.';
            return false;
        }

        capaciteInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updatePreview();
        return true;
    }

    // ============================================================
    // 6. VALIDATION — ANNÉE SCOLAIRE
    // ============================================================
    if (anneeSelect) {
        anneeSelect.addEventListener('change', function() {
            this.classList.remove('is-invalid', 'is-valid');
            const feedback = document.getElementById('anneeFeedback');
            if (!this.value) {
                this.classList.add('is-invalid');
                if (feedback) feedback.textContent = 'Veuillez sélectionner une année.';
            } else {
                this.classList.add('is-valid');
                if (feedback) feedback.textContent = '';
            }
        });
    }

    // ============================================================
    // 7. PRÉVISUALISATION DE LA CLASSE
    // ============================================================
    function updatePreview() {
        if (!classePreview) return;

        const nom = nomInput?.value.trim() || '—';
        const niveau = niveauSelect?.value || '—';
        const niveauLabel = niveauSelect?.options[niveauSelect.selectedIndex]?.text || '—';
        const salle = salleInput?.value.trim() || '—';
        const capacite = capaciteInput?.value || '—';
        const titulaire = titulaireSelect?.options[titulaireSelect.selectedIndex]?.text || 'Aucun';

        if (nom !== '—' || salle !== '—') {
            classePreview.innerHTML = `
                <div class="classe-preview-header">
                    <i class="fa-solid fa-eye"></i> Aperçu de la classe
                </div>
                <div class="classe-preview-grid">
                    <div class="classe-preview-item">
                        <i class="fa-solid fa-chalkboard"></i>
                        <span>Nom : <strong>${escapeHtml(nom)}</strong></span>
                    </div>
                    <div class="classe-preview-item">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Niveau : <strong>${escapeHtml(niveauLabel)}</strong></span>
                    </div>
                    <div class="classe-preview-item">
                        <i class="fa-solid fa-door-open"></i>
                        <span>Salle : <strong>${escapeHtml(salle)}</strong></span>
                    </div>
                    <div class="classe-preview-item">
                        <i class="fa-solid fa-users"></i>
                        <span>Capacité : <strong>${capacite} élèves</strong></span>
                    </div>
                    <div class="classe-preview-item">
                        <i class="fa-solid fa-user-tie"></i>
                        <span>Titulaire : <strong>${escapeHtml(titulaire)}</strong></span>
                    </div>
                </div>`;
            classePreview.classList.add('show');
        } else {
            classePreview.classList.remove('show');
        }
    }

    // ============================================================
    // 8. GÉNÉRATION AUTOMATIQUE DU NOM
    // ============================================================
    if (niveauSelect && nomInput) {
        niveauSelect.addEventListener('change', function() {
            if (!nomInput.dataset.manual || nomInput.value.trim() === '') {
                const niveauValue = this.value;
                if (niveauValue) {
                    const niveauLabels = {
                        '1': '1ère Primaire', '2': '2ème Primaire', '3': '3ème Primaire',
                        '4': '4ème Primaire', '5': '5ème Primaire', '6': '6ème Primaire',
                        '7': '1ère Secondaire', '8': '2ème Secondaire', '9': '3ème Secondaire',
                        '10': '4ème Secondaire', '11': '5ème Secondaire', '12': '6ème Secondaire'
                    };
                    if (niveauLabels[niveauValue]) {
                        nomInput.value = niveauLabels[niveauValue];
                        validateNom();
                    }
                }
            }
        });

        nomInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.dataset.manual = 'true';
            }
        });
    }

    // ============================================================
    // 9. SOUMISSION DU FORMULAIRE
    // ============================================================
    formAddClasse.addEventListener('submit', function(e) {
        let isValid = true;

        if (!validateNom()) isValid = false;

        if (!niveauSelect.value) {
            niveauSelect.classList.add('is-invalid');
            const feedback = document.getElementById('niveauFeedback');
            if (feedback) feedback.textContent = 'Veuillez sélectionner un niveau.';
            isValid = false;
        }

        if (!validateSalle()) isValid = false;
        if (!validateCapacite()) isValid = false;

        if (!anneeSelect.value) {
            anneeSelect.classList.add('is-invalid');
            const feedback = document.getElementById('anneeFeedback');
            if (feedback) feedback.textContent = 'Veuillez sélectionner une année.';
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();

            const firstError = formAddClasse.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }

            // Animation shake
            formAddClasse.style.animation = 'none';
            formAddClasse.offsetHeight;
            formAddClasse.style.animation = 'formShake 0.5s ease';

            showToast('Veuillez corriger les erreurs dans le formulaire.', 'error');
        }
    });

    // ============================================================
    // 10. TOAST NOTIFICATION
    // ============================================================
    function showToast(message, type = 'info') {
        // Utilise la fonction globale si disponible
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================================
    // 11. ANIMATION SHAKE
    // ============================================================
    const shakeStyle = document.createElement('style');
    shakeStyle.textContent = `
        @keyframes formShake {
            0%, 100% { transform: translateX(0); }
            10% { transform: translateX(-6px); }
            20% { transform: translateX(6px); }
            30% { transform: translateX(-5px); }
            40% { transform: translateX(5px); }
            50% { transform: translateX(-4px); }
            60% { transform: translateX(4px); }
            70% { transform: translateX(-2px); }
            80% { transform: translateX(2px); }
            90% { transform: translateX(-1px); }
        }
    `;
    document.head.appendChild(shakeStyle);

    // ============================================================
    // 12. INITIALISATION
    // ============================================================
    if (classePreview) {
        // Ajouter le conteneur de prévisualisation après le formulaire
        const formBody = document.querySelector('.classe-form-body');
        if (formBody && !document.getElementById('classePreview')) {
            const previewDiv = document.createElement('div');
            previewDiv.id = 'classePreview';
            previewDiv.className = 'classe-preview';
            formBody.appendChild(previewDiv);
        }
    }

    console.log('%cModule Ajout Classe%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});