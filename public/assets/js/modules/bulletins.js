/* ============================================================
   Fichier : public/assets/js/modules/bulletins.js
   Rôle    : Gestion des bulletins — Génération, consultation, impression
   Système : Bulletin congolais
   Version : 1.0
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. BOUTON GÉNÉRER LES BULLETINS
    // ============================================================
    const btnGenerer = document.getElementById('btnGenererBulletins');

    if (btnGenerer) {
        btnGenerer.addEventListener('click', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const classeId = urlParams.get('classe_id') || this.dataset.classeId;
            const trimestreId = urlParams.get('trimestre_id') || this.dataset.trimestreId;
            const anneeId = urlParams.get('annee_id') || this.dataset.anneeId;

            if (!classeId || !trimestreId) {
                showToast('Veuillez sélectionner une classe et un trimestre.', 'warning');
                return;
            }

            showConfirmDialog(
                'Voulez-vous générer les bulletins pour cette classe et ce trimestre ?<br><small class="text-muted">Les bulletins existants seront remplacés.</small>',
                function(confirmed) {
                    if (confirmed) {
                        btnGenerer.disabled = true;
                        btnGenerer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Génération en cours...';

                        const formData = new FormData();
                        formData.append('action', 'genererBulletinsClasse');
                        formData.append('classe_id', classeId);
                        formData.append('trimestre_id', trimestreId);
                        formData.append('annee_id', anneeId || '');

                        fetch('/gestion_scolaire/bulletins', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message, 'success');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showToast(data.message, 'error');
                                btnGenerer.disabled = false;
                                btnGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les bulletins maintenant';
                            }
                        })
                        .catch(err => {
                            showToast('Erreur de connexion.', 'error');
                            btnGenerer.disabled = false;
                            btnGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les bulletins maintenant';
                        });
                    }
                },
                {
                    title: 'Générer les bulletins',
                    confirmText: 'Générer',
                    confirmClass: 'btn-success',
                    icon: 'fa-file-invoice',
                    iconColor: '#10b981'
                }
            );
        });
    }

    // ============================================================
    // 2. BOUTON RÉGÉNÉRER LES BULLETINS
    // ============================================================
    const btnRegenerer = document.getElementById('btnRegenererBulletins');

    if (btnRegenerer) {
        btnRegenerer.addEventListener('click', function() {
            showConfirmDialog(
                'Voulez-vous régénérer tous les bulletins ?<br><small class="text-muted">Tous les bulletins existants seront remplacés.</small>',
                function(confirmed) {
                    if (confirmed && btnGenerer) {
                        btnGenerer.click();
                    }
                },
                {
                    title: 'Régénérer les bulletins',
                    confirmText: 'Régénérer',
                    confirmClass: 'btn-warning',
                    icon: 'fa-rotate',
                    iconColor: '#f59e0b'
                }
            );
        });
    }

    // ============================================================
    // 3. IMPRIMER UN BULLETIN SPÉCIFIQUE
    // ============================================================
    window.imprimerBulletin = function(eleveId) {
        const urlParams = new URLSearchParams(window.location.search);
        const anneeId = urlParams.get('annee_id') || '';
        const url = '/gestion_scolaire/bulletins/consulter?eleve_id=' + eleveId + '&annee_id=' + anneeId;
        window.open(url, '_blank');
    };

    // ============================================================
    // 4. CHARGER LES ÉLÈVES POUR L'IMPRESSION
    // ============================================================
    window.loadElevesForPrint = function(classeId) {
        const select = document.getElementById('selectElevePrint');
        if (!select) return;
        if (!classeId) {
            select.innerHTML = '<option value="">— Élève —</option>';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'getBulletinsClasse');
        formData.append('classe_id', classeId);

        fetch('/gestion_scolaire/bulletins', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            select.innerHTML = '<option value="">— Élève —</option>';
            if (data.success && data.bulletins) {
                const seen = new Set();
                data.bulletins.forEach(b => {
                    if (!seen.has(b.eleve_id)) {
                        seen.add(b.eleve_id);
                        const option = document.createElement('option');
                        option.value = b.eleve_id;
                        option.textContent = b.prenom + ' ' + b.nom + ' — ' + b.matricule;
                        select.appendChild(option);
                    }
                });
            }
        });
    };

    console.log('%cBulletins%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 700;', 'color: #64748b;');
});