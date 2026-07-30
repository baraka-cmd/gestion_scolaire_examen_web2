/* ============================================================
   Fichier : public/assets/js/modules/ajouter_annee.js
   Rôle    : Gestion du formulaire d'ajout d'année scolaire
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const formAnnee        = document.getElementById('formAnnee');
    const libelleInput     = document.getElementById('libelle');
    const dateDebutInput   = document.getElementById('date_debut');
    const dateFinInput     = document.getElementById('date_fin');
    const creerTrimestres  = document.getElementById('creer_trimestres');
    const creerClasses     = document.getElementById('creer_classes');

    if (!formAnnee) return;

    // ============================================================
    // 2. VALIDATION — LIBELLÉ (FORMAT AAAA-AAAA)
    // ============================================================
    if (libelleInput) {
        libelleInput.addEventListener('input', function() {
            // Nettoyer : garder uniquement chiffres et tiret
            let value = this.value.replace(/[^0-9\-]/g, '');
            
            // Limiter à 9 caractères (AAAA-AAAA)
            if (value.length > 9) value = value.substring(0, 9);
            
            // Ajouter le tiret automatiquement après le 4ème chiffre
            if (value.length === 4 && !value.includes('-')) {
                value = value + '-';
            }
            
            // Supprimer les tirets supplémentaires
            const parts = value.split('-');
            if (parts.length > 2) {
                value = parts[0] + '-' + parts.slice(1).join('');
            }
            
            this.value = value;
            validateLibelle();
        });
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
        if (!/^\d{4}-\d{4}$/.test(value)) {
            libelleInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Format invalide (ex: 2026-2027).';
            return false;
        }

        // Vérifier la cohérence des années
        const years = value.split('-');
        const annee1 = parseInt(years[0]);
        const annee2 = parseInt(years[1]);

        if (annee2 !== annee1 + 1) {
            libelleInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'La deuxième année doit être l\'année suivante (ex: 2026-2027).';
            return false;
        }

        // Année raisonnable (entre 2020 et 2100)
        if (annee1 < 2020 || annee1 > 2100) {
            libelleInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'Année hors plage (2020-2100).';
            return false;
        }

        libelleInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateTrimestrePreview();
        return true;
    }

    // ============================================================
    // 3. VALIDATION — DATES
    // ============================================================
    if (dateDebutInput) {
        dateDebutInput.addEventListener('change', function() {
            this.classList.remove('is-invalid', 'is-valid');
            if (this.value) {
                this.classList.add('is-valid');
                if (dateFinInput && dateFinInput.value) validateDateFin();
                updateTrimestrePreview();
            }
        });
    }

    if (dateFinInput) {
        dateFinInput.addEventListener('change', validateDateFin);
    }

    function validateDateFin() {
        dateFinInput.classList.remove('is-invalid', 'is-valid');
        const feedback = document.getElementById('dateFinFeedback');

        if (!dateFinInput.value) {
            dateFinInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'La date de fin est obligatoire.';
            return false;
        }

        if (dateDebutInput && dateDebutInput.value && dateFinInput.value <= dateDebutInput.value) {
            dateFinInput.classList.add('is-invalid');
            if (feedback) feedback.textContent = 'La date de fin doit être après la date de début.';
            return false;
        }

        // Vérifier que l'année scolaire dure au moins 6 mois
        if (dateDebutInput && dateDebutInput.value) {
            const debut = new Date(dateDebutInput.value);
            const fin = new Date(dateFinInput.value);
            const diffDays = Math.floor((fin - debut) / (1000 * 60 * 60 * 24));

            if (diffDays < 180) {
                dateFinInput.classList.add('is-invalid');
                if (feedback) feedback.textContent = 'L\'année scolaire doit durer au moins 6 mois (180 jours).';
                return false;
            }
            if (diffDays > 400) {
                dateFinInput.classList.add('is-invalid');
                if (feedback) feedback.textContent = 'L\'année scolaire ne peut pas dépasser 400 jours.';
                return false;
            }
        }

        dateFinInput.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
        updateTrimestrePreview();
        return true;
    }

    // ============================================================
    // 4. MISE À JOUR DE L'APERÇU DES TRIMESTRES
    // ============================================================
    function updateTrimestrePreview() {
        const previewContainer = document.getElementById('trimestrePreview');
        if (!previewContainer || !creerTrimestres || !creerTrimestres.checked) return;

        const debut = dateDebutInput?.value;
        const fin = dateFinInput?.value;

        if (!debut || !fin) {
            previewContainer.innerHTML = '<p style="font-size:0.8rem;color:var(--color-gray-500);">Veuillez renseigner les dates pour voir l\'aperçu.</p>';
            return;
        }

        const dateDebut = new Date(debut);
        const dateFin = new Date(fin);
        const dureeTotale = Math.floor((dateFin - dateDebut) / (1000 * 60 * 60 * 24));
        const dureeTrimestre = Math.floor(dureeTotale / 3);

        const trimestres = [
            { numero: 1, libelle: '1er Trimestre', actif: true },
            { numero: 2, libelle: '2ème Trimestre', actif: false },
            { numero: 3, libelle: '3ème Trimestre', actif: false }
        ];

        let html = '';
        trimestres.forEach((trim, i) => {
            const tDebut = new Date(dateDebut);
            tDebut.setDate(tDebut.getDate() + (i * dureeTrimestre));

            const tFin = new Date(tDebut);
            if (i < 2) {
                tFin.setDate(tFin.getDate() + dureeTrimestre - 1);
            } else {
                tFin.setTime(dateFin.getTime());
            }
            if (tFin > dateFin) tFin.setTime(dateFin.getTime());

            const formatDate = (d) => {
                return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            };

            html += `
                <div class="trimestre-preview-item ${trim.actif ? 'actif' : 'inactif'}">
                    <span class="badge badge-${trim.actif ? 'success' : 'secondary'}" style="font-size:0.7rem;">
                        ${trim.actif ? 'Actif' : 'Inactif'}
                    </span>
                    <span style="font-size:0.82rem;"><strong>${trim.libelle}</strong></span>
                    <span style="font-size:0.72rem;color:var(--color-gray-500);margin-left:auto;">
                        ${formatDate(tDebut)} → ${formatDate(tFin)}
                    </span>
                </div>`;
        });

        previewContainer.innerHTML = html;
    }

    // ============================================================
    // 5. TOGGLE OPTIONS — AFFICHER/MASQUER APERÇU
    // ============================================================
    if (creerTrimestres) {
        creerTrimestres.addEventListener('change', function() {
            const previewCard = document.getElementById('trimestrePreviewCard');
            if (previewCard) {
                previewCard.style.display = this.checked ? 'block' : 'none';
            }
            if (this.checked) updateTrimestrePreview();
        });
    }

    // ============================================================
    // 6. AUTO-REMPLISSAGE DES DATES SELON LE LIBELLÉ
    // ============================================================
    if (libelleInput && dateDebutInput && dateFinInput) {
        libelleInput.addEventListener('change', function() {
            const value = this.value.trim();
            if (/^\d{4}-\d{4}$/.test(value) && !dateDebutInput.dataset.manual) {
                const years = value.split('-');
                dateDebutInput.value = `${years[0]}-09-01`;
                dateFinInput.value = `${years[1]}-07-05`;
                dateDebutInput.classList.add('is-valid');
                dateFinInput.classList.add('is-valid');
                updateTrimestrePreview();
            }
        });

        // Marquer les dates comme modifiées manuellement
        dateDebutInput.addEventListener('change', function() {
            this.dataset.manual = 'true';
        });
        dateFinInput.addEventListener('change', function() {
            this.dataset.manual = 'true';
        });
    }

    // ============================================================
    // 7. SOUMISSION DU FORMULAIRE
    // ============================================================
    formAnnee.addEventListener('submit', function(e) {
        let isValid = true;

        if (!validateLibelle()) isValid = false;

        if (dateDebutInput && !dateDebutInput.value) {
            dateDebutInput.classList.add('is-invalid');
            isValid = false;
        }

        if (!validateDateFin()) isValid = false;

        if (!isValid) {
            e.preventDefault();

            const firstError = formAnnee.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }

            formAnnee.style.animation = 'none';
            formAnnee.offsetHeight;
            formAnnee.style.animation = 'formShake 0.5s ease';

            showToast('Veuillez corriger les erreurs dans le formulaire.', 'error');
        }
    });

    // ============================================================
    // 8. TOAST
    // ============================================================
    function showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    }

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
    if (creerTrimestres && creerTrimestres.checked) {
        updateTrimestrePreview();
    }

    console.log('%cModule Ajout Année Scolaire%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});