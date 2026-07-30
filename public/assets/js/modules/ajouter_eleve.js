/* ============================================================
   Fichier : public/assets/js/modules/ajouter_eleve.js
   Rôle    : Gestion du formulaire d'ajout d'élève
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formAddEleve      = document.getElementById('formAddEleve');
    const nomInput          = document.getElementById('nom');
    const prenomInput       = document.getElementById('prenom');
    const dateNaissanceInput = document.getElementById('date_naissance');
    const sexeSelect        = document.getElementById('sexe');
    const classeSelect      = document.getElementById('classe_id');
    const parent1Nom        = document.getElementById('parent1_nom');
    const parent1Prenom     = document.getElementById('parent1_prenom');
    const parent1Tel        = document.getElementById('parent1_telephone');
    const parent1Lien       = document.getElementById('parent1_lien');

    if (!formAddEleve) return;

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
            if (feedback) feedback.textContent = 'Le nom est obligatoire.';
            return false;
        }
        if (value.length < 2) {
            nomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Minimum 2 caractères.';
            return false;
        }
        if (value.length > 100) {
            nomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 100 caractères.';
            return false;
        }
        if (!/^[a-zA-ZÀ-ÿ\s\'\-]+$/u.test(value)) {
            nomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Caractères non autorisés.';
            return false;
        }

        nomInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateProgress();
        return true;
    }

    // ============================================================
    // 3. VALIDATION — PRÉNOM
    // ============================================================
    if (prenomInput) {
        prenomInput.addEventListener('input', validatePrenom);
        prenomInput.addEventListener('blur', validatePrenom);
    }

    function validatePrenom() {
        const value = prenomInput.value.trim();
        prenomInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('prenomFeedback');

        if (value === '') {
            prenomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Le prénom est obligatoire.';
            return false;
        }
        if (value.length < 2) {
            prenomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Minimum 2 caractères.';
            return false;
        }
        if (value.length > 100) {
            prenomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 100 caractères.';
            return false;
        }
        if (!/^[a-zA-ZÀ-ÿ\s\'\-]+$/u.test(value)) {
            prenomInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Caractères non autorisés.';
            return false;
        }

        prenomInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateProgress();
        return true;
    }

    // ============================================================
    // 4. VALIDATION — DATE DE NAISSANCE
    // ============================================================
    if (dateNaissanceInput) {
        dateNaissanceInput.addEventListener('change', validateDateNaissance);
        dateNaissanceInput.addEventListener('blur', validateDateNaissance);
    }

    function validateDateNaissance() {
        const value = dateNaissanceInput.value;
        dateNaissanceInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('dateNaissanceFeedback');

        if (value === '') {
            dateNaissanceInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'La date de naissance est obligatoire.';
            return false;
        }

        const date = new Date(value);
        const now = new Date();
        const age = now.getFullYear() - date.getFullYear();

        if (isNaN(date.getTime())) {
            dateNaissanceInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Date invalide.';
            return false;
        }
        if (age < 3) {
            dateNaissanceInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Âge minimum : 3 ans.';
            return false;
        }
        if (age > 25) {
            dateNaissanceInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Âge maximum : 25 ans.';
            return false;
        }
        if (date > now) {
            dateNaissanceInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'La date ne peut pas être dans le futur.';
            return false;
        }

        dateNaissanceInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateProgress();
        return true;
    }

    // ============================================================
    // 5. VALIDATION — SEXE
    // ============================================================
    if (sexeSelect) {
        sexeSelect.addEventListener('change', function() {
            this.classList.remove('is-invalid', 'is-valid');
            const feedback = document.getElementById('sexeFeedback');
            if (!this.value) {
                this.classList.add('is-invalid');
                if (feedback) feedback.textContent = 'Veuillez sélectionner le sexe.';
            } else {
                this.classList.add('is-valid');
                if (feedback) feedback.textContent = '';
                updateProgress();
            }
        });
    }

    // ============================================================
    // 6. VALIDATION — CLASSE
    // ============================================================
    if (classeSelect) {
        classeSelect.addEventListener('change', function() {
            this.classList.remove('is-invalid', 'is-valid');
            const feedback = document.getElementById('classeFeedback');
            if (!this.value) {
                this.classList.add('is-invalid');
                if (feedback) feedback.textContent = 'Veuillez sélectionner une classe.';
            } else {
                this.classList.add('is-valid');
                if (feedback) feedback.textContent = '';
                updateProgress();
            }
        });
    }

    // ============================================================
    // 7. VALIDATION — PARENT 1
    // ============================================================
    if (parent1Nom) {
        parent1Nom.addEventListener('input', validateParent1);
        parent1Nom.addEventListener('blur', validateParent1);
    }
    if (parent1Prenom) {
        parent1Prenom.addEventListener('input', validateParent1);
        parent1Prenom.addEventListener('blur', validateParent1);
    }
    if (parent1Tel) {
        parent1Tel.addEventListener('input', validateParent1);
        parent1Tel.addEventListener('blur', validateParent1);
    }
    if (parent1Lien) {
        parent1Lien.addEventListener('change', validateParent1);
    }

    function validateParent1() {
        let valid = true;

        if (parent1Nom) {
            parent1Nom.classList.remove('is-invalid', 'is-valid');
            if (parent1Nom.value.trim() === '') {
                parent1Nom.classList.add('is-invalid');
                valid = false;
            } else if (parent1Nom.value.trim().length < 2) {
                parent1Nom.classList.add('is-invalid');
                valid = false;
            } else {
                parent1Nom.classList.add('is-valid');
            }
        }

        if (parent1Prenom) {
            parent1Prenom.classList.remove('is-invalid', 'is-valid');
            if (parent1Prenom.value.trim() === '') {
                parent1Prenom.classList.add('is-invalid');
                valid = false;
            } else {
                parent1Prenom.classList.add('is-valid');
            }
        }

        if (parent1Tel) {
            parent1Tel.classList.remove('is-invalid', 'is-valid');
            const tel = parent1Tel.value.trim().replace(/\s/g, '');
            if (tel === '') {
                parent1Tel.classList.add('is-invalid');
                valid = false;
            } else if (!/^(\+243|0)?[0-9]{9}$/.test(tel)) {
                parent1Tel.classList.add('is-invalid');
                valid = false;
            } else {
                parent1Tel.classList.add('is-valid');
            }
        }

        if (parent1Lien) {
            parent1Lien.classList.remove('is-invalid', 'is-valid');
            if (!parent1Lien.value) {
                parent1Lien.classList.add('is-invalid');
                valid = false;
            } else {
                parent1Lien.classList.add('is-valid');
            }
        }

        updateProgress();
        return valid;
    }

    // ============================================================
    // 8. MISE À JOUR DE LA PROGRESSION
    // ============================================================
    function updateProgress() {
        const steps = document.querySelectorAll('.progress-dot');
        if (steps.length === 0) return;

        let completed = 0;
        if (validateNom()) completed++;
        if (validatePrenom()) completed++;
        if (validateDateNaissance()) completed++;
        if (sexeSelect && sexeSelect.value) completed++;
        if (classeSelect && classeSelect.value) completed++;
        if (validateParent1()) completed++;

        steps.forEach((dot, index) => {
            dot.classList.remove('completed', 'active');
            if (index < completed) {
                dot.classList.add('completed');
                dot.innerHTML = '<i class="fa-solid fa-check"></i>';
            } else if (index === completed) {
                dot.classList.add('active');
                dot.innerHTML = index + 1;
            } else {
                dot.innerHTML = index + 1;
            }
        });

        // Mettre à jour les lignes de progression
        document.querySelectorAll('.progress-line').forEach((line, index) => {
            line.classList.toggle('completed', index < completed - 1);
        });
    }

    // ============================================================
    // 9. SOUMISSION DU FORMULAIRE
    // ============================================================
    formAddEleve.addEventListener('submit', function(e) {
        let isValid = true;

        if (!validateNom()) isValid = false;
        if (!validatePrenom()) isValid = false;
        if (!validateDateNaissance()) isValid = false;

        if (sexeSelect && !sexeSelect.value) {
            sexeSelect.classList.add('is-invalid');
            isValid = false;
        }

        if (classeSelect && !classeSelect.value) {
            classeSelect.classList.add('is-invalid');
            isValid = false;
        }

        if (!validateParent1()) isValid = false;

        if (!isValid) {
            e.preventDefault();

            const firstError = formAddEleve.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }

            // Animation shake
            formAddEleve.style.animation = 'none';
            formAddEleve.offsetHeight;
            formAddEleve.style.animation = 'formShake 0.5s ease';

            showToast('Veuillez corriger les erreurs dans le formulaire.', 'error');
        }
    });

    // ============================================================
    // 10. CALCUL DE L'ÂGE EN TEMPS RÉEL
    // ============================================================
    const ageDisplay = document.getElementById('ageCalcule');
    if (dateNaissanceInput && ageDisplay) {
        dateNaissanceInput.addEventListener('change', function() {
            const date = new Date(this.value);
            if (!isNaN(date.getTime())) {
                const now = new Date();
                let age = now.getFullYear() - date.getFullYear();
                const monthDiff = now.getMonth() - date.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < date.getDate())) {
                    age--;
                }
                ageDisplay.textContent = age + ' an(s)';
                ageDisplay.style.display = 'inline';
            } else {
                ageDisplay.style.display = 'none';
            }
        });
    }

    // ============================================================
    // 11. TOAST
    // ============================================================
    function showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    }

    // ============================================================
    // 12. ANIMATION SHAKE
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
    // 13. INITIALISATION
    // ============================================================
    updateProgress();

    console.log('%cModule Ajout Élève%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});