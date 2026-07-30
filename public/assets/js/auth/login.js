/* ============================================================
   Fichier : public/assets/js/auth/login.js
   Rôle    : Gestion du formulaire de connexion
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const loginForm           = document.getElementById('loginForm');
    const emailInput          = document.getElementById('email');
    const passwordInput       = document.getElementById('password');
    const togglePassword      = document.getElementById('togglePassword');
    const loginButton         = document.getElementById('loginButton');
    const loginError          = document.getElementById('loginError');
    const loginSuccess        = document.getElementById('loginSuccess');
    const loginErrorMessage   = document.getElementById('loginErrorMessage');
    const loginSuccessMessage = document.getElementById('loginSuccessMessage');
    const passwordStrength    = document.getElementById('passwordStrength');
    const strengthFill        = document.getElementById('strengthFill');
    const strengthText        = document.getElementById('strengthText');
    const passwordRequirements = document.getElementById('passwordRequirements');
    const rememberCheckbox     = document.getElementById('remember');

    // Si on n'est pas sur la page login, sortir
    if (!loginForm) return;

    // ============================================================
    // 2. TOGGLE AFFICHAGE MOT DE PASSE (SANS REDÉCLARATION)
    // ============================================================
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function(e) {
            e.preventDefault();
            
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
            }
        });
    }

    // ============================================================
    // 3. VALIDATION EMAIL EN TEMPS RÉEL
    // ============================================================
    if (emailInput) {
        emailInput.addEventListener('input', () => {
            validateEmailField();
        });

        emailInput.addEventListener('blur', () => {
            validateEmailField();
        });
    }

    function validateEmailField() {
        const email = emailInput.value.trim();
        const emailError = document.getElementById('emailError');

        // Réinitialiser
        emailInput.classList.remove('is-invalid', 'is-valid');

        if (email === '') {
            emailInput.classList.add('is-invalid');
            if (emailError) emailError.textContent = 'L\'adresse email est obligatoire.';
            return false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            emailInput.classList.add('is-invalid');
            if (emailError) emailError.textContent = 'Veuillez entrer une adresse email valide.';
            return false;
        }

        emailInput.classList.add('is-valid');
        if (emailError) emailError.textContent = '';
        return true;
    }

    // ============================================================
    // 4. VALIDATION MOT DE PASSE EN TEMPS RÉEL + FORCE
    // ============================================================
    if (passwordInput) {
        passwordInput.addEventListener('input', () => {
            validatePasswordField();
            updatePasswordStrength();
            updatePasswordRequirements();
        });

        passwordInput.addEventListener('focus', () => {
            if (passwordRequirements) {
                passwordRequirements.style.display = 'block';
            }
        });

        passwordInput.addEventListener('blur', () => {
            validatePasswordField();
            if (passwordInput.value.trim() === '' && passwordRequirements) {
                passwordRequirements.style.display = 'none';
            }
            if (passwordInput.value.trim() === '' && passwordStrength) {
                passwordStrength.style.display = 'none';
            }
        });
    }

    function validatePasswordField() {
        const password = passwordInput.value;
        const passwordError = document.getElementById('passwordError');

        // Réinitialiser
        passwordInput.classList.remove('is-invalid', 'is-valid');

        if (password === '') {
            passwordInput.classList.add('is-invalid');
            if (passwordError) passwordError.textContent = 'Le mot de passe est obligatoire.';
            return false;
        }

        if (password.length < 8) {
            passwordInput.classList.add('is-invalid');
            if (passwordError) passwordError.textContent = 'Minimum 8 caractères requis.';
            return false;
        }

        // Vérifier les exigences de complexité
        const hasUpper   = /[A-Z]/.test(password);
        const hasLower   = /[a-z]/.test(password);
        const hasNumber  = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password);

        if (!hasUpper || !hasLower || !hasNumber || !hasSpecial) {
            passwordInput.classList.add('is-invalid');
            if (passwordError) passwordError.textContent = 'Le mot de passe ne respecte pas les exigences de sécurité.';
            return false;
        }

        passwordInput.classList.add('is-valid');
        if (passwordError) passwordError.textContent = '';
        return true;
    }

    // ============================================================
    // 5. INDICATEUR DE FORCE DU MOT DE PASSE
    // ============================================================
    function updatePasswordStrength() {
        const password = passwordInput.value;

        if (password === '') {
            if (passwordStrength) passwordStrength.style.display = 'none';
            return;
        }

        if (passwordStrength) passwordStrength.style.display = 'block';

        let score = 0;

        // Critères
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)) score++;
        if (/[A-Z].*[A-Z]/.test(password)) score++;
        if (/[0-9].*[0-9]/.test(password)) score++;

        // Classes de force
        const strengthClasses = ['very-weak', 'weak', 'medium', 'strong', 'very-strong'];
        const strengthLabels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];

        let level;
        if (score <= 2) level = 0;
        else if (score <= 4) level = 1;
        else if (score <= 5) level = 2;
        else if (score <= 7) level = 3;
        else level = 4;

        if (strengthFill) {
            strengthFill.className = 'strength-fill ' + strengthClasses[level];
        }

        if (strengthText) {
            strengthText.textContent = strengthLabels[level];
            strengthText.className = 'strength-text ' + strengthClasses[level];
        }
    }

    // ============================================================
    // 6. VALIDATION DES EXIGENCES EN TEMPS RÉEL
    // ============================================================
    function updatePasswordRequirements() {
        const password = passwordInput.value;

        if (password === '') {
            if (passwordRequirements) passwordRequirements.style.display = 'none';
            return;
        }

        if (passwordRequirements) passwordRequirements.style.display = 'block';

        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password)
        };

        Object.keys(requirements).forEach(key => {
            const li = document.getElementById('req-' + key);
            if (!li) return;

            const icon = li.querySelector('i');

            if (requirements[key]) {
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
    // 7. SOUMISSION DU FORMULAIRE
    // ============================================================
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            hideMessages();

            const isEmailValid = validateEmailField();
            const isPasswordValid = validatePasswordField();

            if (!isEmailValid || !isPasswordValid) {
                if (!isEmailValid) emailInput.focus();
                else passwordInput.focus();
                return;
            }

            if (typeof setButtonLoading === 'function') {
                setButtonLoading(loginButton);
            }

            const formData = new FormData(loginForm);

            try {
                const response = await fetch('/gestion_scolaire/public/views/auth/login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showLoginSuccess(data.message || 'Connexion réussie. Redirection...');
                    setTimeout(() => {
                        window.location.href = data.redirect || '/gestion_scolaire/dashboard';
                    }, 800);
                } else {
                    showLoginError(data.message || 'Identifiants incorrects.');
                    if (typeof resetButtonLoading === 'function') {
                        resetButtonLoading(loginButton);
                    }
                    shakeForm();
                }
            } catch (error) {
                showLoginError('Erreur de connexion au serveur. Veuillez réessayer.');
                if (typeof resetButtonLoading === 'function') {
                    resetButtonLoading(loginButton);
                }
                console.error('Erreur login:', error);
            }
        });
    }

    // ============================================================
    // 8. AFFICHAGE DES MESSAGES
    // ============================================================
    function hideMessages() {
        if (loginError) loginError.style.display = 'none';
        if (loginSuccess) loginSuccess.style.display = 'none';
    }

    function showLoginError(message) {
        hideMessages();
        if (loginError && loginErrorMessage) {
            loginErrorMessage.textContent = message;
            loginError.style.display = 'flex';
            loginError.style.animation = 'none';
            loginError.offsetHeight;
            loginError.style.animation = 'alertSlideIn 0.3s ease';
        }
    }

    function showLoginSuccess(message) {
        hideMessages();
        if (loginSuccess && loginSuccessMessage) {
            loginSuccessMessage.textContent = message;
            loginSuccess.style.display = 'flex';
            loginSuccess.style.animation = 'none';
            loginSuccess.offsetHeight;
            loginSuccess.style.animation = 'alertSlideIn 0.3s ease';
        }
    }

    // ============================================================
    // 9. ANIMATION DE SECOUSSE
    // ============================================================
    function shakeForm() {
        if (loginForm) {
            loginForm.style.animation = 'none';
            loginForm.offsetHeight;
            loginForm.style.animation = 'shakeForm 0.5s ease';
        }
    }

    // ============================================================
    // 10. GESTION DU BOUTON GOOGLE
    // ============================================================
    const googleButton = document.getElementById('googleButton') || document.querySelector('.btn-google');
    if (googleButton) {
        googleButton.addEventListener('click', () => {
            showLoginError('La connexion avec Google sera disponible prochainement.');
            setTimeout(() => hideMessages(), 4000);
        });
    }

    // ============================================================
    // 11. LIEN MOT DE PASSE OUBLIÉ
    // ============================================================
    const forgotLink = document.getElementById('forgotPasswordLink') || document.querySelector('.forgot-link');
    if (forgotLink) {
        forgotLink.addEventListener('click', (e) => {
            e.preventDefault();
            showLoginError('La réinitialisation du mot de passe sera disponible prochainement.');
            setTimeout(() => hideMessages(), 4000);
        });
    }

    // ============================================================
    // 12. RESTAURER L'EMAIL SI "SE SOUVENIR DE MOI"
    // ============================================================
    if (emailInput && rememberCheckbox) {
        if (typeof getCookie === 'function') {
            const savedEmail = getCookie('remembered_email');
            if (savedEmail) {
                emailInput.value = savedEmail;
                rememberCheckbox.checked = true;
            }
        }

        rememberCheckbox.addEventListener('change', () => {
            if (rememberCheckbox.checked && emailInput.value.trim() !== '') {
                if (typeof setCookie === 'function') setCookie('remembered_email', emailInput.value.trim(), 30);
            } else {
                if (typeof deleteCookie === 'function') deleteCookie('remembered_email');
            }
        });

        emailInput.addEventListener('input', () => {
            if (rememberCheckbox.checked && emailInput.value.trim() !== '') {
                if (typeof setCookie === 'function') setCookie('remembered_email', emailInput.value.trim(), 30);
            }
        });
    }

    // ============================================================
    // 13. FOCUS AUTOMATIQUE
    // ============================================================
    if (emailInput && emailInput.value.trim() !== '') {
        passwordInput?.focus();
    }

    // ============================================================
    // 14. ANIMATION CSS SUPPLÉMENTAIRE
    // ============================================================
    const style = document.createElement('style');
    style.textContent = `
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
    document.head.appendChild(style);

});
