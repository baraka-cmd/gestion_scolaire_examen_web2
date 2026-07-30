/* ============================================================
   Fichier : public/assets/js/modules/ajouter_enseignant.js
   Rôle    : Gestion du formulaire d'ajout d'enseignant
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formAddEnseignant   = document.getElementById('formAddEnseignant');
    const nomInput            = document.getElementById('nom');
    const prenomInput         = document.getElementById('prenom');
    const dateNaissanceInput  = document.getElementById('date_naissance');
    const sexeSelect          = document.getElementById('sexe');
    const telephoneInput      = document.getElementById('telephone');
    const emailInput          = document.getElementById('email');
    const classeSelect        = document.getElementById('classe_id');
    const creerCompteCheck    = document.getElementById('creer_compte');

    if (!formAddEnseignant) return;

    // ============================================================
    // 2. VALIDATION — NOM
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
        if (isNaN(date.getTime()) || date > now) {
            dateNaissanceInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Date invalide.';
            return false;
        }
        const age = now.getFullYear() - date.getFullYear();
        if (age < 20 || age > 80) {
            dateNaissanceInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Âge invalide (20-80 ans).';
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
            if (!this.value) {
                this.classList.add('is-invalid');
            } else {
                this.classList.add('is-valid');
                updateProgress();
            }
        });
    }

    // ============================================================
    // 6. VALIDATION — TÉLÉPHONE
    // ============================================================
    if (telephoneInput) {
        telephoneInput.addEventListener('input', validateTelephone);
        telephoneInput.addEventListener('blur', validateTelephone);
    }

    function validateTelephone() {
        const value = telephoneInput.value.trim().replace(/\s/g, '');
        telephoneInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('telephoneFeedback');
        if (value === '') {
            telephoneInput.classList.add('is-valid');
            if (feedback) feedback.textContent = '';
            return true;
        }
        if (!/^(\+243|0)?[0-9]{9}$/.test(value)) {
            telephoneInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Format invalide (ex: +243XXXXXXXXX).';
            return false;
        }
        telephoneInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        return true;
    }

    // ============================================================
    // 7. VALIDATION — EMAIL
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
            emailInput.classList.add('is-valid');
            if (feedback) feedback.textContent = '';
            return true;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            emailInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Format d\'email invalide.';
            return false;
        }
        emailInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        return true;
    }

    // ============================================================
    // 8. COMPTE UTILISATEUR — DÉPENDANCE EMAIL
    // ============================================================
    if (creerCompteCheck && emailInput) {
        creerCompteCheck.addEventListener('change', function() {
            if (this.checked && emailInput.value.trim() === '') {
                showToast('Un email est requis pour créer un compte utilisateur.', 'warning');
                emailInput.focus();
            }
        });
    }

    // ============================================================
    // 9. MISE À JOUR DE LA PROGRESSION
    // ============================================================
    function updateProgress() {
        const steps = document.querySelectorAll('.progress-dot');
        if (steps.length === 0) return;

        let completed = 0;
        if (validateNom()) completed++;
        if (validatePrenom()) completed++;
        if (validateDateNaissance()) completed++;
        if (sexeSelect && sexeSelect.value) completed++;

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

        document.querySelectorAll('.progress-line').forEach((line, index) => {
            line.classList.toggle('completed', index < completed - 1);
        });
    }

    // ============================================================
    // 10. SOUMISSION DU FORMULAIRE
    // ============================================================
    formAddEnseignant.addEventListener('submit', function(e) {
        let isValid = true;

        if (!validateNom()) isValid = false;
        if (!validatePrenom()) isValid = false;
        if (!validateDateNaissance()) isValid = false;

        if (sexeSelect && !sexeSelect.value) {
            sexeSelect.classList.add('is-invalid');
            isValid = false;
        }

        validateTelephone();
        validateEmail();

        // Vérifier email si création de compte
        if (creerCompteCheck && creerCompteCheck.checked && emailInput.value.trim() === '') {
            emailInput.classList.add('is-invalid');
            const feedback = document.getElementById('emailFeedback');
            if (feedback) feedback.textContent = 'L\'email est obligatoire pour créer un compte.';
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            const firstError = formAddEnseignant.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            formAddEnseignant.style.animation = 'none';
            formAddEnseignant.offsetHeight;
            formAddEnseignant.style.animation = 'formShake 0.5s ease';
            showToast('Veuillez corriger les erreurs.', 'error');
        }
    });

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

    console.log('%cModule Ajout Enseignant%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});