/**
 * Fichier : public/assets/js/auth/forgot-password.js
 * Rôle    : Scripts de la page mot de passe oublié
 * Système : École primaire congolaise — Authentification
 * Version : 1.0
 */

'use strict';

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initValidationEmail();
    initAnimation();
});

// ============================================================
// 1. VALIDATION EMAIL EN TEMPS RÉEL
// ============================================================
function initValidationEmail() {
    var champEmail = document.getElementById('email');
    var formulaire = document.getElementById('formForgotPassword');

    if (!champEmail || !formulaire) return;

    // Validation en temps réel
    champEmail.addEventListener('input', function() {
        var email = this.value.trim();
        var feedbackEl = this.parentElement.parentElement.querySelector('.invalid-feedback');

        if (email === '') {
            this.classList.remove('is-invalid', 'is-valid');
            if (feedbackEl) feedbackEl.textContent = '';
        } else if (!isValidEmail(email)) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
            if (feedbackEl) feedbackEl.textContent = 'Veuillez saisir une adresse email valide.';
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            if (feedbackEl) feedbackEl.textContent = '';
        }
    });

    // Validation à la soumission
    formulaire.addEventListener('submit', function(e) {
        var email = champEmail.value.trim();

        if (email === '') {
            e.preventDefault();
            champEmail.classList.add('is-invalid');
            var feedbackEl = champEmail.parentElement.parentElement.querySelector('.invalid-feedback');
            if (feedbackEl) feedbackEl.textContent = 'Veuillez saisir votre adresse email.';
            champEmail.focus();
            return;
        }

        if (!isValidEmail(email)) {
            e.preventDefault();
            champEmail.classList.add('is-invalid');
            var feedbackEl = champEmail.parentElement.parentElement.querySelector('.invalid-feedback');
            if (feedbackEl) feedbackEl.textContent = 'Veuillez saisir une adresse email valide.';
            champEmail.focus();
            return;
        }

        // Désactiver le bouton pour éviter le double envoi
        var btn = document.getElementById('btnSubmit');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Envoi en cours...';
        }
    });
}

// ============================================================
// 2. VALIDATION EMAIL (REGEX)
// ============================================================
function isValidEmail(email) {
    var regex = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    return regex.test(email);
}

// ============================================================
// 3. ANIMATIONS D'ENTRÉE
// ============================================================
function initAnimation() {
    var container = document.querySelector('.forgot-password-container');
    if (container) {
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';
    }

    // Animation de l'icône cadenas
    var icon = document.querySelector('.forgot-password-icon i');
    if (icon) {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1) rotate(-5deg)';
            this.style.transition = 'transform 0.3s ease';
        });

        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
        });
    }
}

// ============================================================
// 4. FOCUS AUTOMATIQUE SUR LE CHAMP EMAIL
// ============================================================
var champEmail = document.getElementById('email');
if (champEmail && !champEmail.value) {
    setTimeout(function() {
        champEmail.focus();
    }, 500);
}