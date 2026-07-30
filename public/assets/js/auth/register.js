/* ============================================================
   Fichier : public/assets/js/auth/register.js
   Rôle    : Gestion du formulaire d'inscription
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const registerForm       = $('#registerForm');
    const nomInput           = $('#nom');
    const prenomInput        = $('#prenom');
    const emailInput         = $('#email');
    const telephoneInput     = $('#telephone');
    const passwordInput      = $('#password');
    const confirmPasswordInput = $('#confirm_password');
    const roleSelect         = $('#role_id');
    const togglePassword     = $('#togglePassword');
    const toggleConfirmPassword = $('#toggleConfirmPassword');
    const registerButton     = $('#registerButton');
    const registerError      = $('#registerError');
    const registerSuccess    = $('#registerSuccess');
    const registerErrorMessage  = $('#registerErrorMessage');
    const registerSuccessMessage = $('#registerSuccessMessage');
    const passwordStrength     = $('#passwordStrength');
    const strengthFill         = $('#strengthFill');
    const strengthText         = $('#strengthText');
    const passwordRequirements = $('#passwordRequirements');
    const termsCheckbox        = $('#terms');

    if (!registerForm) return;

    // ============================================================
    // 2. TOGGLE MOT DE PASSE
    // ============================================================
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', () => {
            togglePasswordVisibility(passwordInput, togglePassword);
        });
    }

    if (toggleConfirmPassword && confirmPasswordInput) {
        toggleConfirmPassword.addEventListener('click', () => {
            togglePasswordVisibility(confirmPasswordInput, toggleConfirmPassword);
        });
    }

    function togglePasswordVisibility(input, button) {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
        input.focus();
    }

    // ============================================================
    // 3. VALIDATION EN TEMPS RÉEL — NOM
    // ============================================================
    if (nomInput) {
        nomInput.addEventListener('input', validateNom);
        nomInput.addEventListener('blur', validateNom);
    }

    function validateNom() {
        const value = nomInput.value.trim();
        const error = $('#nomError');

        nomInput.classList.remove('is-invalid', 'is-valid');

        if (value === '') {
            nomInput.classList.add('is-invalid');
            if (error) error.textContent = 'Le nom est obligatoire.';
            return false;
        }

        if (value.length < 2) {
            nomInput.classList.add('is-invalid');
            if (error) error.textContent = 'Minimum 2 caractères.';
            return false;
        }

        if (value.length > 100) {
            nomInput.classList.add('is-invalid');
            if (error) error.textContent = 'Maximum 100 caractères.';
            return false;
        }

        if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(value)) {
            nomInput.classList.add('is-invalid');
            if (error) error.textContent = 'Caractères non autorisés.';
            return false;
        }

        nomInput.classList.add('is-valid');
        if (error) error.textContent = '';
        return true;
    }

    // ============================================================
    // 4. VALIDATION EN TEMPS RÉEL — PRÉNOM
    // ============================================================
    if (prenomInput) {
        prenomInput.addEventListener('input', validatePrenom);
        prenomInput.addEventListener('blur', validatePrenom);
    }

    function validatePrenom() {
        const value = prenomInput.value.trim();
        const error = $('#prenomError');

        prenomInput.classList.remove('is-invalid', 'is-valid');

        if (value === '') {
            prenomInput.classList.add('is-invalid');
            if (error) error.textContent = 'Le prénom est obligatoire.';
            return false;
        }

        if (value.length < 2) {
            prenomInput.classList.add('is-invalid');
            if (error) error.textContent = 'Minimum 2 caractères.';
            return false;
        }

        if (value.length > 100) {
            prenomInput.classList.add('is-invalid');
            if (error) error.textContent = 'Maximum 100 caractères.';
            return false;
        }

        if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(value)) {
            prenomInput.classList.add('is-invalid');
            if (error) error.textContent = 'Caractères non autorisés.';
            return false;
        }

        prenomInput.classList.add('is-valid');
        if (error) error.textContent = '';
        return true;
    }

    // ============================================================
    // 5. VALIDATION EN TEMPS RÉEL — EMAIL
    // ============================================================
    if (emailInput) {
        emailInput.addEventListener('input', validateEmail);
        emailInput.addEventListener('blur', validateEmail);
    }

    function validateEmail() {
        const value = emailInput.value.trim();
        const error = $('#emailError');

        emailInput.classList.remove('is-invalid', 'is-valid');

        if (value === '') {
            emailInput.classList.add('is-invalid');
            if (error) error.textContent = 'L\'adresse email est obligatoire.';
            return false;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            emailInput.classList.add('is-invalid');
            if (error) error.textContent = 'Adresse email invalide.';
            return false;
        }

        if (value.length > 150) {
            emailInput.classList.add('is-invalid');
            if (error) error.textContent = 'Maximum 150 caractères.';
            return false;
        }

        emailInput.classList.add('is-valid');
        if (error) error.textContent = '';
        return true;
    }

    // ============================================================
    // 6. VALIDATION EN TEMPS RÉEL — TÉLÉPHONE
    // ============================================================
    if (telephoneInput) {
        telephoneInput.addEventListener('input', validateTelephone);
        telephoneInput.addEventListener('blur', validateTelephone);
    }

    function validateTelephone() {
        const value = telephoneInput.value.trim();
        const error = $('#telephoneError');

        telephoneInput.classList.remove('is-invalid', 'is-valid');

        if (value === '') {
            telephoneInput.classList.add('is-valid'); // Optionnel
            if (error) error.textContent = '';
            return true;
        }

        if (!/^(\+243|0)?[0-9]{9}$/.test(value.replace(/\s/g, ''))) {
            telephoneInput.classList.add('is-invalid');
            if (error) error.textContent = 'Numéro invalide (ex: +243XXXXXXXXX).';
            return false;
        }

        telephoneInput.classList.add('is-valid');
        if (error) error.textContent = '';
        return true;
    }

    // ============================================================
    // 7. VALIDATION EN TEMPS RÉEL — MOT DE PASSE
    // ============================================================
    if (passwordInput) {
        passwordInput.addEventListener('input', () => {
            validatePassword();
            updatePasswordStrength();
            updatePasswordRequirements();
        });

        passwordInput.addEventListener('focus', () => {
            if (passwordRequirements) passwordRequirements.style.display = 'block';
        });

        passwordInput.addEventListener('blur', () => {
            validatePassword();
            if (passwordInput.value.trim() === '' && passwordRequirements) {
                passwordRequirements.style.display = 'none';
            }
            if (passwordInput.value.trim() === '' && passwordStrength) {
                passwordStrength.style.display = 'none';
            }
        });
    }

    function validatePassword() {
        const value = passwordInput.value;
        const error = $('#passwordError');

        passwordInput.classList.remove('is-invalid', 'is-valid');

        if (value === '') {
            passwordInput.classList.add('is-invalid');
            if (error) error.textContent = 'Le mot de passe est obligatoire.';
            return false;
        }

        if (value.length < 8) {
            passwordInput.classList.add('is-invalid');
            if (error) error.textContent = 'Minimum 8 caractères.';
            return false;
        }

        const hasUpper   = /[A-Z]/.test(value);
        const hasLower   = /[a-z]/.test(value);
        const hasNumber  = /[0-9]/.test(value);
        const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(value);

        if (!hasUpper || !hasLower || !hasNumber || !hasSpecial) {
            passwordInput.classList.add('is-invalid');
            if (error) error.textContent = 'Le mot de passe ne respecte pas les exigences.';
            return false;
        }

        passwordInput.classList.add('is-valid');
        if (error) error.textContent = '';
        return true;
    }

    // ============================================================
    // 8. INDICATEUR DE FORCE DU MOT DE PASSE
    // ============================================================
    function updatePasswordStrength() {
        const value = passwordInput.value;

        if (value === '') {
            if (passwordStrength) passwordStrength.style.display = 'none';
            return;
        }

        if (passwordStrength) passwordStrength.style.display = 'block';

        let score = 0;
        if (value.length >= 8) score++;
        if (value.length >= 12) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[a-z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(value)) score++;
        if (/[A-Z].*[A-Z]/.test(value)) score++;
        if (/[0-9].*[0-9]/.test(value)) score++;

        const levels = ['very-weak', 'weak', 'medium', 'strong', 'very-strong'];
        const labels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];

        let level;
        if (score <= 2) level = 0;
        else if (score <= 4) level = 1;
        else if (score <= 5) level = 2;
        else if (score <= 7) level = 3;
        else level = 4;

        if (strengthFill) {
            strengthFill.className = 'strength-fill ' + levels[level];
        }
        if (strengthText) {
            strengthText.textContent = labels[level];
            strengthText.className = 'strength-text ' + levels[level];
        }
    }

    // ============================================================
    // 9. VALIDATION DES EXIGENCES
    // ============================================================
    function updatePasswordRequirements() {
        const value = passwordInput.value;

        if (value === '') {
            if (passwordRequirements) passwordRequirements.style.display = 'none';
            return;
        }

        if (passwordRequirements) passwordRequirements.style.display = 'block';

        const reqs = {
            'req-length': value.length >= 8,
            'req-uppercase': /[A-Z]/.test(value),
            'req-lowercase': /[a-z]/.test(value),
            'req-number': /[0-9]/.test(value),
            'req-special': /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(value)
        };

        Object.keys(reqs).forEach(id => {
            const li = $('#' + id);
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
    // 10. VALIDATION CONFIRMATION MOT DE PASSE
    // ============================================================
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validateConfirmPassword);
        confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
    }

    function validateConfirmPassword() {
        const value = confirmPasswordInput.value;
        const passwordValue = passwordInput.value;
        const error = $('#confirmPasswordError');

        confirmPasswordInput.classList.remove('is-invalid', 'is-valid');

        if (value === '') {
            confirmPasswordInput.classList.add('is-invalid');
            if (error) error.textContent = 'Veuillez confirmer votre mot de passe.';
            return false;
        }

        if (value !== passwordValue) {
            confirmPasswordInput.classList.add('is-invalid');
            if (error) error.textContent = 'Les mots de passe ne correspondent pas.';
            return false;
        }

        confirmPasswordInput.classList.add('is-valid');
        if (error) error.textContent = '';
        return true;
    }

    // ============================================================
    // 11. SOUMISSION DU FORMULAIRE
    // ============================================================
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            hideMessages();

            // Valider tous les champs
            const isNomValid     = validateNom();
            const isPrenomValid  = validatePrenom();
            const isEmailValid   = validateEmail();
            const isTelValid     = validateTelephone();
            const isPassValid    = validatePassword();
            const isConfirmValid = validateConfirmPassword();
            const isTermsChecked = termsCheckbox ? termsCheckbox.checked : true;

            if (!isNomValid || !isPrenomValid || !isEmailValid || !isPassValid || !isConfirmValid) {
                showRegisterError('Veuillez corriger les erreurs dans le formulaire.');

                // Focus sur le premier champ invalide
                const firstInvalid = registerForm.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
                return;
            }

            if (!isTermsChecked) {
                showRegisterError('Vous devez accepter les conditions d\'utilisation.');
                if (termsCheckbox) termsCheckbox.focus();
                return;
            }

            // Activer l'état de chargement
            setButtonLoading(registerButton);

            const formData = new FormData(registerForm);

            try {
                const response = await fetch('/gestion_scolaire/public/views/auth/register.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showRegisterSuccess(data.message || 'Inscription réussie ! Redirection...');

                    // Rediriger vers la page de connexion
                    setTimeout(() => {
                        window.location.href = data.redirect || '/gestion_scolaire/login?created=1';
                    }, 1500);
                } else {
                    showRegisterError(data.message || 'Erreur lors de l\'inscription.');
                    resetButtonLoading(registerButton);

                    // Afficher les erreurs de validation serveur
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const input = $('#' + field);
                            const error = $('#' + field + 'Error');
                            if (input) input.classList.add('is-invalid');
                            if (error) error.textContent = data.errors[field];
                        });
                    }

                    shakeForm();
                }
            } catch (error) {
                showRegisterError('Erreur de connexion au serveur. Veuillez réessayer.');
                resetButtonLoading(registerButton);
                console.error('Erreur inscription:', error);
            }
        });
    }

    // ============================================================
    // 12. GESTION DES MESSAGES
    // ============================================================
    function hideMessages() {
        if (registerError) registerError.style.display = 'none';
        if (registerSuccess) registerSuccess.style.display = 'none';
    }

    function showRegisterError(message) {
        hideMessages();
        if (registerError && registerErrorMessage) {
            registerErrorMessage.textContent = message;
            registerError.style.display = 'flex';
            registerError.style.animation = 'none';
            registerError.offsetHeight;
            registerError.style.animation = 'alertSlideIn 0.3s ease';
        }
    }

    function showRegisterSuccess(message) {
        hideMessages();
        if (registerSuccess && registerSuccessMessage) {
            registerSuccessMessage.textContent = message;
            registerSuccess.style.display = 'flex';
            registerSuccess.style.animation = 'none';
            registerSuccess.offsetHeight;
            registerSuccess.style.animation = 'alertSlideIn 0.3s ease';
        }
    }

    function shakeForm() {
        if (registerForm) {
            registerForm.style.animation = 'none';
            registerForm.offsetHeight;
            registerForm.style.animation = 'shakeForm 0.5s ease';
        }
    }

    // ============================================================
    // 13. ANIMATION SHAKE
    // ============================================================
    const shakeStyle = document.createElement('style');
    shakeStyle.textContent = `
        @keyframes shakeForm {
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

});