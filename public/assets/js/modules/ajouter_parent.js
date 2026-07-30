/* ============================================================
   Fichier : public/assets/js/modules/ajouter_parent.js
   Rôle    : Gestion du formulaire d'ajout de parent/tuteur
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formAddParent      = document.getElementById('formAddParent');
    const nomInput           = document.getElementById('nom');
    const prenomInput        = document.getElementById('prenom');
    const lienSelect         = document.getElementById('lien');
    const telephoneInput     = document.getElementById('telephone_principal');
    const telephone2Input    = document.getElementById('telephone_secondaire');
    const emailInput         = document.getElementById('email');
    const searchEleves       = document.getElementById('searchEleves');
    const elevesContainer    = document.getElementById('elevesContainer');
    const creerCompteCheck   = document.getElementById('creer_compte');

    if (!formAddParent) return;

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
        if (!/^[a-zA-ZÀ-ÿ\s'\-]+$/u.test(value)) {
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
        if (!/^[a-zA-ZÀ-ÿ\s'\-]+$/u.test(value)) {
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
    // 4. VALIDATION — LIEN DE PARENTÉ
    // ============================================================
    if (lienSelect) {
        lienSelect.addEventListener('change', function() {
            this.classList.remove('is-invalid', 'is-valid');
            const feedback = document.getElementById('lienFeedback');
            if (!this.value) {
                this.classList.add('is-invalid');
                if (feedback) feedback.textContent = 'Le lien de parenté est obligatoire.';
            } else {
                this.classList.add('is-valid');
                if (feedback) feedback.textContent = '';
                updateProgress();
            }
        });
    }

    // ============================================================
    // 5. VALIDATION — TÉLÉPHONE PRINCIPAL
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
            telephoneInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Le téléphone principal est obligatoire.';
            return false;
        }
        if (!/^(\+243|0)?[0-9]{9}$/.test(value)) {
            telephoneInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Format invalide (ex: +243XXXXXXXXX).';
            return false;
        }

        telephoneInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateProgress();
        return true;
    }

    // ============================================================
    // 6. VALIDATION — TÉLÉPHONE SECONDAIRE
    // ============================================================
    if (telephone2Input) {
        telephone2Input.addEventListener('input', validateTelephone2);
        telephone2Input.addEventListener('blur', validateTelephone2);
    }

    function validateTelephone2() {
        const value = telephone2Input.value.trim().replace(/\s/g, '');
        telephone2Input.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('telephone2Feedback');

        if (value === '') {
            telephone2Input.classList.add('is-valid');
            if (feedback) feedback.textContent = '';
            return true;
        }
        if (!/^(\+243|0)?[0-9]{9}$/.test(value)) {
            telephone2Input.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Format invalide.';
            return false;
        }

        telephone2Input.classList.add('is-valid');
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
// 8. RECHERCHE D'ÉLÈVES
// ============================================================
if (searchEleves && elevesContainer) {
    searchEleves.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const items = elevesContainer.querySelectorAll('.eleves-selection-item');
        let visibleCount = 0;

        items.forEach(item => {
            const name = item.querySelector('.eleves-selection-name')?.textContent || '';
            const detail = item.querySelector('.eleves-selection-detail')?.textContent || '';
            const fullText = (name + ' ' + detail).toLowerCase();

            if (query === '' || fullText.includes(query)) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Gestion du message "Aucun résultat"
        const oldMsg = elevesContainer.querySelector('.no-results-msg');
        if (oldMsg) oldMsg.remove();

        if (visibleCount === 0 && query !== '') {
            const msg = document.createElement('div');
            msg.className = 'no-results-msg eleves-selection-empty';
            msg.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i><p>Aucun élève trouvé</p>';
            msg.style.padding = '20px';
            msg.style.textAlign = 'center';
            elevesContainer.appendChild(msg);
        }
    });
}


    // ============================================================
    // 9. SÉLECTION D'ÉLÈVES — VISUEL
    // ============================================================
    if (elevesContainer) {
        elevesContainer.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox') {
                const item = e.target.closest('.eleves-selection-item');
                if (item) {
                    item.classList.toggle('selected', e.target.checked);
                }
            }
        });

        // Initialiser l'état visuel
        elevesContainer.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            const item = cb.closest('.eleves-selection-item');
            if (item) item.classList.add('selected');
        });
    }

    // ============================================================
    // 10. COMPTE UTILISATEUR — DÉPENDANCE EMAIL
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
    // 11. MISE À JOUR DE LA PROGRESSION
    // ============================================================
    function updateProgress() {
        const steps = document.querySelectorAll('.progress-dot');
        if (steps.length === 0) return;

        let completed = 0;
        if (validateNom()) completed++;
        if (validatePrenom()) completed++;
        if (lienSelect && lienSelect.value) completed++;
        if (validateTelephone()) completed++;

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
    // 12. SOUMISSION DU FORMULAIRE
    // ============================================================
    formAddParent.addEventListener('submit', function(e) {
        let isValid = true;

        if (!validateNom()) isValid = false;
        if (!validatePrenom()) isValid = false;

        if (lienSelect && !lienSelect.value) {
            lienSelect.classList.add('is-invalid');
            isValid = false;
        }

        if (!validateTelephone()) isValid = false;
        validateTelephone2();
        validateEmail();

        // Vérifier que l'email est rempli si création de compte cochée
        if (creerCompteCheck && creerCompteCheck.checked && emailInput.value.trim() === '') {
            emailInput.classList.add('is-invalid');
            const feedback = document.getElementById('emailFeedback');
            if (feedback) feedback.textContent = 'L\'email est obligatoire pour créer un compte.';
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();

            const firstError = formAddParent.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }

            formAddParent.style.animation = 'none';
            formAddParent.offsetHeight;
            formAddParent.style.animation = 'formShake 0.5s ease';

            showToast('Veuillez corriger les erreurs dans le formulaire.', 'error');
        }
    });

    // ============================================================
    // 13. TOAST
    // ============================================================
    function showToast(message, type = 'info') {
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
    updateProgress();

    console.log('%cModule Ajout Parent%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});