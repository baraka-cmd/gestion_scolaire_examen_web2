/* ============================================================
   Fichier : public/assets/js/modules/ajouter_matiere.js
   Rôle    : Gestion du formulaire d'ajout de matière
   Système : Bulletin congolais
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formAddMatiere    = document.getElementById('formAddMatiere');
    const libelleInput      = document.getElementById('libelle');
    const codeInput         = document.getElementById('code');
    const maximaInput       = document.getElementById('maxima');
    const parentSelect      = document.getElementById('parent_id');
    const estGroupeRadios   = document.querySelectorAll('input[name="est_groupe"]');
    const parentGroup       = document.getElementById('parentGroup');
    const maximaGroup       = document.getElementById('maximaGroup');
    const classesGroup      = document.getElementById('classesGroup');
    const cardGroupe        = document.getElementById('cardGroupe');
    const cardSousMatiere   = document.getElementById('cardSousMatiere');

    if (!formAddMatiere) return;

    // ============================================================
    // 2. TOGGLE TYPE DE MATIÈRE (GROUPE / SOUS-MATIÈRE)
    // ============================================================
    estGroupeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const isSousMatiere = this.value === '0';

            // Afficher/masquer les sections
            if (parentGroup) parentGroup.style.display = isSousMatiere ? 'block' : 'none';
            if (maximaGroup) maximaGroup.style.display = isSousMatiere ? 'block' : 'none';
            if (classesGroup) classesGroup.style.display = isSousMatiere ? 'block' : 'none';

            // Styliser les cartes
            if (cardGroupe) {
                cardGroupe.style.borderColor = isSousMatiere ? 'var(--border-color)' : '#2c3e90';
                cardGroupe.style.backgroundColor = isSousMatiere ? 'transparent' : '#e8eaf6';
            }
            if (cardSousMatiere) {
                cardSousMatiere.style.borderColor = isSousMatiere ? '#10b981' : 'var(--border-color)';
                cardSousMatiere.style.backgroundColor = isSousMatiere ? '#f0fdf4' : 'transparent';
            }

            // Réinitialiser les champs masqués
            if (!isSousMatiere) {
                if (maximaInput) {
                    maximaInput.value = '';
                    maximaInput.classList.remove('is-invalid', 'is-valid');
                }
                if (parentSelect) {
                    parentSelect.value = '';
                    parentSelect.classList.remove('is-invalid', 'is-valid');
                }
                // Décocher toutes les classes
                document.querySelectorAll('#classesGroup input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                    const item = cb.closest('label');
                    if (item) item.style.backgroundColor = '#fff';
                });
            }

            updateProgress();
        });
    });

    // ============================================================
    // 3. VALIDATION — LIBELLÉ
    // ============================================================
    if (libelleInput) {
        libelleInput.addEventListener('input', validateLibelle);
        libelleInput.addEventListener('blur', validateLibelle);
    }

    function validateLibelle() {
        const value = libelleInput.value.trim();
        libelleInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('libelleFeedback');

        if (value === '') {
            libelleInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Le libellé est obligatoire.';
            return false;
        }
        if (value.length < 2) {
            libelleInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Minimum 2 caractères.';
            return false;
        }
        if (value.length > 150) {
            libelleInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 150 caractères.';
            return false;
        }

        libelleInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateProgress();
        return true;
    }

    // ============================================================
    // 4. VALIDATION — CODE (OPTIONNEL)
    // ============================================================
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            validateCode();
        });
        codeInput.addEventListener('blur', validateCode);
    }

    function validateCode() {
        const value = codeInput.value.trim();
        codeInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('codeFeedback');

        if (value === '') {
            codeInput.classList.add('is-valid');
            if (feedback) feedback.textContent = '';
            return true;
        }
        if (value.length > 20) {
            codeInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 20 caractères.';
            return false;
        }
        if (!/^[A-Z0-9._-]+$/.test(value)) {
            codeInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Lettres majuscules, chiffres, points et tirets.';
            return false;
        }

        codeInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateProgress();
        return true;
    }

    // ============================================================
    // 5. VALIDATION — MAXIMA (UNIQUEMENT SI SOUS-MATIÈRE)
    // ============================================================
    if (maximaInput) {
        maximaInput.addEventListener('input', validateMaxima);
        maximaInput.addEventListener('blur', validateMaxima);
    }

    function validateMaxima() {
        const estGroupe = document.querySelector('input[name="est_groupe"]:checked')?.value;

        // Si c'est un groupe, pas de validation
        if (estGroupe === '1') {
            maximaInput.classList.remove('is-invalid', 'is-valid');
            const feedback = document.getElementById('maximaFeedback');
            if (feedback) feedback.textContent = '';
            return true;
        }

        const value = parseInt(maximaInput.value);
        maximaInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('maximaFeedback');

        if (isNaN(value) || value < 1) {
            maximaInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Le maxima doit être supérieur à 0.';
            return false;
        }
        if (value > 1000) {
            maximaInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Maximum 1000.';
            return false;
        }

        maximaInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateProgress();
        return true;
    }

    // ============================================================
    // 6. VALIDATION — PARENT (UNIQUEMENT SI SOUS-MATIÈRE)
    // ============================================================
    if (parentSelect) {
        parentSelect.addEventListener('change', function() {
            const estGroupe = document.querySelector('input[name="est_groupe"]:checked')?.value;
            if (estGroupe === '1') {
                this.classList.remove('is-invalid', 'is-valid');
                return;
            }
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
    // 7. SÉLECTION DES CLASSES — EFFET VISUEL
    // ============================================================
    document.querySelectorAll('#classesGroup input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', function() {
            const label = this.closest('label');
            if (label) {
                label.style.backgroundColor = this.checked ? '#d1fae5' : '#fff';
                label.style.borderColor = this.checked ? '#10b981' : '#e2e8f0';
            }
        });
    });

    // ============================================================
    // 8. GÉNÉRATION AUTO DU CODE À PARTIR DU LIBELLÉ
    // ============================================================
    if (libelleInput && codeInput) {
        libelleInput.addEventListener('input', function() {
            if (!codeInput.dataset.manual || codeInput.value.trim() === '') {
                const value = this.value.trim().toUpperCase();
                if (value.length >= 3) {
                    const words = value.split(/[\s\-/]+/);
                    let code = '';
                    words.forEach(w => {
                        if (w.length > 0) code += w.charAt(0);
                    });
                    codeInput.value = code.substring(0, 6);
                    validateCode();
                }
            }
        });

        codeInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.dataset.manual = 'true';
            }
        });
    }

    // ============================================================
    // 9. MISE À JOUR DE LA PROGRESSION
    // ============================================================
    function updateProgress() {
        const steps = document.querySelectorAll('.progress-dot');
        if (steps.length === 0) return;

        const estGroupe = document.querySelector('input[name="est_groupe"]:checked')?.value;
        let completed = 0;

        if (validateLibelle()) completed++;

        if (estGroupe === '1') {
            // Pour un groupe : juste le libellé est obligatoire
            completed = validateLibelle() ? 2 : 1;
        } else {
            // Pour une sous-matière : libellé + maxima + parent
            if (validateLibelle()) completed++;
            if (validateMaxima()) completed++;
            if (parentSelect && parentSelect.value) completed++;
        }

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
    formAddMatiere.addEventListener('submit', function(e) {
        let isValid = true;
        const estGroupe = document.querySelector('input[name="est_groupe"]:checked')?.value;

        if (!validateLibelle()) isValid = false;
        validateCode(); // Optionnel

        if (estGroupe === '0') {
            // Validation sous-matière
            if (!validateMaxima()) isValid = false;
            if (parentSelect && !parentSelect.value) {
                parentSelect.classList.add('is-invalid');
                isValid = false;
            }
        }

        if (!isValid) {
            e.preventDefault();

            const firstError = formAddMatiere.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }

            formAddMatiere.style.animation = 'none';
            formAddMatiere.offsetHeight;
            formAddMatiere.style.animation = 'formShake 0.5s ease';

            showToast('Veuillez corriger les erreurs dans le formulaire.', 'error');
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
    const checkedRadio = document.querySelector('input[name="est_groupe"]:checked');
    if (checkedRadio) {
        checkedRadio.dispatchEvent(new Event('change'));
    }
    updateProgress();

    console.log('%cModule Ajout Matière%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});