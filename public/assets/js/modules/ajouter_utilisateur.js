/* ============================================================
   Fichier : public/assets/js/modules/ajouter_utilisateur.js
   Rôle    : Gestion du formulaire d'ajout d'utilisateur
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formAddUser          = document.getElementById('formAddUser');
    const nomInput             = document.getElementById('nom');
    const prenomInput          = document.getElementById('prenom');
    const emailInput           = document.getElementById('email');
    const telephoneInput       = document.getElementById('telephone');
    const loginInput           = document.getElementById('login');
    const passwordInput        = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const roleSelect           = document.getElementById('role_id');
    const strengthContainer    = document.getElementById('passwordStrength');
    const strengthFill         = document.getElementById('strengthFill');
    const strengthText         = document.getElementById('strengthText');
    const requirementsContainer = document.getElementById('passwordRequirements');
    const permissionsPreview   = document.getElementById('rolePermissionsPreview');
    const permissionsList      = document.getElementById('permissionsList');
    const toggleButtons        = document.querySelectorAll('.password-toggle-btn');

    if (!formAddUser) return;

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

            input.focus();
        });
    });

    // ============================================================
    // 3. VALIDATION EN TEMPS RÉEL — NOM
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
        emailInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('emailFeedback');

        if (value === '') {
            emailInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'L\'email est obligatoire.';
            return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            emailInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Format d\'email invalide.';
            return false;
        }
        if (value.length > 150) {
            emailInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 150 caractères.';
            return false;
        }

        emailInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
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
        telephoneInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('telephoneFeedback');

        if (value === '') {
            telephoneInput.classList.add('is-valid');
            if (feedback) feedback.textContent = '';
            return true; // Optionnel
        }
        if (!/^(\+243|0)?[0-9]{9}$/.test(value.replace(/\s/g, ''))) {
            telephoneInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Format invalide (ex: +243XXXXXXXXX).';
            return false;
        }

        telephoneInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        return true;
    }

    // ============================================================
    // 7. VALIDATION EN TEMPS RÉEL — LOGIN
    // ============================================================
    if (loginInput) {
        loginInput.addEventListener('input', validateLogin);
        loginInput.addEventListener('blur', validateLogin);
    }

    function validateLogin() {
        const value = loginInput.value.trim();
        loginInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('loginFeedback');

        if (value === '') {
            loginInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Le login est obligatoire.';
            return false;
        }
        if (value.length < 3) {
            loginInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Minimum 3 caractères.';
            return false;
        }
        if (value.length > 50) {
            loginInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 50 caractères.';
            return false;
        }
        if (!/^[a-zA-Z0-9._-]+$/.test(value)) {
            loginInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Lettres, chiffres, points, tirets et underscores uniquement.';
            return false;
        }

        loginInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        return true;
    }

    // ============================================================
    // 8. FORCE DU MOT DE PASSE + EXIGENCES
    // ============================================================
    if (passwordInput) {
        passwordInput.addEventListener('input', updatePasswordStrength);
        passwordInput.addEventListener('focus', () => {
            if (requirementsContainer) requirementsContainer.style.display = 'block';
        });
        passwordInput.addEventListener('blur', () => {
            if (passwordInput.value === '') {
                if (requirementsContainer) requirementsContainer.style.display = 'none';
                if (strengthContainer) strengthContainer.style.display = 'none';
            }
        });
    }

    function updatePasswordStrength() {
        const password = passwordInput.value;

        if (password === '') {
            if (strengthContainer) strengthContainer.style.display = 'none';
            if (requirementsContainer) requirementsContainer.style.display = 'none';
            return;
        }

        if (strengthContainer) strengthContainer.style.display = 'block';
        if (requirementsContainer) requirementsContainer.style.display = 'block';

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
        let level = score <= 2 ? 0 : score <= 4 ? 1 : score <= 5 ? 2 : score <= 7 ? 3 : 4;

        if (strengthFill) strengthFill.className = 'strength-bar-fill ' + levels[level];
        if (strengthText) {
            strengthText.textContent = labels[level];
            strengthText.className = 'strength-label ' + levels[level];
        }

        // Mise à jour des exigences
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
                li.classList.add('valid'); li.classList.remove('invalid');
                if (icon) { icon.classList.add('fa-circle-check'); icon.classList.remove('fa-circle-xmark'); }
            } else {
                li.classList.add('invalid'); li.classList.remove('valid');
                if (icon) { icon.classList.add('fa-circle-xmark'); icon.classList.remove('fa-circle-check'); }
            }
        });

        // Revalider la confirmation si déjà remplie
        if (confirmPasswordInput && confirmPasswordInput.value !== '') {
            validateConfirmPassword();
        }
    }

    // ============================================================
    // 9. VALIDATION CONFIRMATION MOT DE PASSE
    // ============================================================
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validateConfirmPassword);
        confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
    }

    function validateConfirmPassword() {
        const value = confirmPasswordInput.value;
        const passwordValue = passwordInput.value;
        confirmPasswordInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('confirmPasswordFeedback');

        if (value === '') {
            confirmPasswordInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Veuillez confirmer le mot de passe.';
            return false;
        }
        if (value !== passwordValue) {
            confirmPasswordInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Les mots de passe ne correspondent pas.';
            return false;
        }

        confirmPasswordInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        return true;
    }

    // ============================================================
    // 10. VALIDATION RÔLE
    // ============================================================
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const value = this.value;
            const feedback = document.getElementById('roleFeedback');
            this.classList.remove('is-invalid', 'is-valid');

            if (!value) {
                this.classList.add('is-invalid');
                if (feedback) feedback.textContent = 'Veuillez sélectionner un rôle.';
                if (permissionsPreview) permissionsPreview.classList.remove('show');
            } else {
                this.classList.add('is-valid');
                if (feedback) feedback.textContent = '';
                loadRolePermissions(value);
            }
        });
    }

    // ============================================================
    // 11. CHARGEMENT APERÇU DES PERMISSIONS DU RÔLE
    // ============================================================
    async function loadRolePermissions(roleId) {
        if (!permissionsPreview || !permissionsList) return;

        try {
            const formData = new FormData();
            formData.append('action', 'getRole');
            formData.append('role_id', roleId);

            const response = await fetch('/gestion_scolaire/roles', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();

            if (data.success && data.permissions) {
                const modules = {};
                data.permissions.forEach(perm => {
                    if (!modules[perm.module]) modules[perm.module] = [];
                    modules[perm.module].push(perm.libelle);
                });

                let html = '';
                Object.keys(modules).forEach(module => {
                    html += `<span class="permission-tag"><i class="fa-solid fa-check"></i> ${module} (${modules[module].length})</span>`;
                });

                permissionsList.innerHTML = html;
                permissionsPreview.classList.add('show');
            }
        } catch (error) {
            console.error('Erreur chargement permissions:', error);
        }
    }

    // ============================================================
    // 12. SOUMISSION DU FORMULAIRE
    // ============================================================
    formAddUser.addEventListener('submit', function(e) {
        let isValid = true;

        if (!validateNom()) isValid = false;
        if (!validatePrenom()) isValid = false;
        if (!validateEmail()) isValid = false;
        validateTelephone(); // Optionnel
        if (!validateLogin()) isValid = false;
        if (!updatePasswordStrength) updatePasswordStrength();
        if (!validateConfirmPassword()) isValid = false;

        if (!roleSelect.value) {
            roleSelect.classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();

            const firstError = formAddUser.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }

            // Animation shake
            formAddUser.style.animation = 'none';
            formAddUser.offsetHeight;
            formAddUser.style.animation = 'formShake 0.5s ease';

            showToast('Veuillez corriger les erreurs dans le formulaire.', 'error');
        }
    });

    // ============================================================
    // 13. GÉNÉRATION LOGIN AUTOMATIQUE
    // ============================================================
    function generateLogin() {
        if (nomInput && prenomInput && loginInput) {
            const nom = slugify(nomInput.value.trim());
            const prenom = slugify(prenomInput.value.trim());
            if (nom && prenom && !loginInput.dataset.manual) {
                loginInput.value = prenom.charAt(0) + nom;
            }
        }
    }

    function slugify(text) {
        return text
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]/g, '')
            .substring(0, 20);
    }

    if (nomInput && prenomInput && loginInput) {
        nomInput.addEventListener('input', generateLogin);
        prenomInput.addEventListener('input', generateLogin);
        loginInput.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    }

    // ============================================================
    // 14. ANIMATION SHAKE
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
    // 15. INITIALISATION
    // ============================================================
    console.log('%cModule Ajout Utilisateur%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});