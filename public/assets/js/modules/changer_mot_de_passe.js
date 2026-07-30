/* ============================================================
   Fichier : public/assets/js/modules/changer_mot_de_passe.js
   Rôle    : Gestion du formulaire de changement de mot de passe
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formChangePassword     = document.getElementById('formChangePassword');
    const ancienInput            = document.getElementById('ancien_mot_de_passe');
    const nouveauInput           = document.getElementById('nouveau_mot_de_passe');
    const confirmationInput      = document.getElementById('confirmation_mot_de_passe');
    const strengthContainer      = document.getElementById('passwordStrength');
    const strengthFill           = document.getElementById('strengthFill');
    const strengthText           = document.getElementById('strengthText');
    const requirementsContainer  = document.getElementById('passwordRequirements');
    const toggleButtons          = document.querySelectorAll('.password-toggle-btn');

    if (!formChangePassword) return;

    // ============================================================
    // 2. TOGGLE AFFICHAGE MOT DE PASSE
    // ============================================================
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.querySelector(targetId);

            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.remove(isPassword ? 'fa-eye' : 'fa-eye-slash');
                icon.classList.add(isPassword ? 'fa-eye-slash' : 'fa-eye');
            }

            // Garder le focus sur le champ
            input.focus();
        });
    });

    // ============================================================
    // 3. VALIDATION EN TEMPS RÉEL — NOUVEAU MOT DE PASSE
    // ============================================================
    if (nouveauInput) {
        nouveauInput.addEventListener('input', function() {
            validateNouveauMotDePasse();
            updateStrengthIndicator();
            updateRequirements();
            validateConfirmation(); // Revalider la confirmation si déjà remplie
        });

        nouveauInput.addEventListener('focus', function() {
            if (requirementsContainer) {
                requirementsContainer.style.display = 'block';
            }
        });

        nouveauInput.addEventListener('blur', function() {
            validateNouveauMotDePasse();
            if (this.value.trim() === '' && requirementsContainer) {
                requirementsContainer.style.display = 'none';
            }
            if (this.value.trim() === '' && strengthContainer) {
                strengthContainer.style.display = 'none';
            }
        });
    }

    function validateNouveauMotDePasse() {
        const value = nouveauInput.value;
        const feedback = document.getElementById('nouveauFeedback');

        nouveauInput.classList.remove('is-invalid', 'is-valid');

        if (value === '') {
            nouveauInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Veuillez entrer un nouveau mot de passe.';
            return false;
        }

        if (value.length < 8) {
            nouveauInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Minimum 8 caractères requis.';
            return false;
        }

        const hasUpper   = /[A-Z]/.test(value);
        const hasLower   = /[a-z]/.test(value);
        const hasNumber  = /[0-9]/.test(value);
        const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(value);

        if (!hasUpper || !hasLower || !hasNumber || !hasSpecial) {
            nouveauInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Le mot de passe ne respecte pas les exigences de sécurité.';
            return false;
        }

        // Vérifier si identique à l'ancien
        if (ancienInput && value === ancienInput.value) {
            nouveauInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Le nouveau mot de passe doit être différent de l\'ancien.';
            return false;
        }

        nouveauInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        return true;
    }

    // ============================================================
    // 4. INDICATEUR DE FORCE
    // ============================================================
    function updateStrengthIndicator() {
        const password = nouveauInput.value;

        if (password === '') {
            if (strengthContainer) strengthContainer.style.display = 'none';
            return;
        }

        if (strengthContainer) strengthContainer.style.display = 'block';

        let score = 0;
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) score++;
        if (/[A-Z].*[A-Z]/.test(password)) score++;
        if (/[0-9].*[0-9]/.test(password)) score++;

        const levels = ['very-weak', 'weak', 'medium', 'strong', 'very-strong'];
        const labels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];

        let level;
        if (score <= 2) level = 0;
        else if (score <= 4) level = 1;
        else if (score <= 5) level = 2;
        else if (score <= 7) level = 3;
        else level = 4;

        if (strengthFill) {
            strengthFill.className = 'strength-bar-fill ' + levels[level];
        }
        if (strengthText) {
            strengthText.textContent = labels[level];
            strengthText.className = 'strength-label ' + levels[level];
        }
    }

    // ============================================================
    // 5. EXIGENCES EN TEMPS RÉEL
    // ============================================================
    function updateRequirements() {
        const password = nouveauInput.value;

        if (password === '') {
            if (requirementsContainer) requirementsContainer.style.display = 'none';
            return;
        }

        if (requirementsContainer) requirementsContainer.style.display = 'block';

        const reqs = {
            'req-length': password.length >= 8,
            'req-uppercase': /[A-Z]/.test(password),
            'req-lowercase': /[a-z]/.test(password),
            'req-number': /[0-9]/.test(password),
            'req-special': /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)
        };

        Object.keys(reqs).forEach(id => {
            const li = document.getElementById(id);
            if (!li) return;

            const icon = li.querySelector('i');

            if (reqs[id]) {
                li.classList.add('valid');
                li.classList.remove('invalid');
                if (icon) {
                    icon.classList.add('fa-circle-check');
                    icon.classList.remove('fa-circle-xmark');
                }
            } else {
                li.classList.add('invalid');
                li.classList.remove('valid');
                if (icon) {
                    icon.classList.add('fa-circle-xmark');
                    icon.classList.remove('fa-circle-check');
                }
            }
        });
    }

    // ============================================================
    // 6. VALIDATION CONFIRMATION
    // ============================================================
    if (confirmationInput) {
        confirmationInput.addEventListener('input', validateConfirmation);
        confirmationInput.addEventListener('blur', validateConfirmation);
    }

    function validateConfirmation() {
        const value = confirmationInput.value;
        const passwordValue = nouveauInput.value;
        const feedback = document.getElementById('confirmationFeedback');

        confirmationInput.classList.remove('is-invalid', 'is-valid');

        if (value === '') {
            confirmationInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Veuillez confirmer votre mot de passe.';
            return false;
        }

        if (value !== passwordValue) {
            confirmationInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Les mots de passe ne correspondent pas.';
            return false;
        }

        confirmationInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        return true;
    }

    // ============================================================
    // 7. VALIDATION ANCIEN MOT DE PASSE
    // ============================================================
    if (ancienInput) {
        ancienInput.addEventListener('blur', validateAncienMotDePasse);
    }

    function validateAncienMotDePasse() {
        const value = ancienInput.value;
        const feedback = document.getElementById('ancienFeedback');

        ancienInput.classList.remove('is-invalid');

        if (value === '') {
            ancienInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Veuillez entrer votre mot de passe actuel.';
            return false;
        }

        if (feedback) feedback.textContent = '';
        return true;
    }

    // ============================================================
    // 8. SOUMISSION DU FORMULAIRE
    // ============================================================
    formChangePassword.addEventListener('submit', function(e) {
        let isValid = true;

        if (!validateAncienMotDePasse()) isValid = false;
        if (!validateNouveauMotDePasse()) isValid = false;
        if (!validateConfirmation()) isValid = false;

        if (!isValid) {
            e.preventDefault();

            // Scroll vers la première erreur
            const firstError = formChangePassword.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }

            // Animation de secousse
            formChangePassword.style.animation = 'none';
            formChangePassword.offsetHeight;
            formChangePassword.style.animation = 'formShake 0.5s ease';
        }
    });

    // ============================================================
    // 9. ANIMATION SHAKE
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
    // 10. INITIALISATION
    // ============================================================
    console.log('%cModule Changement Mot de Passe%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});