/* ============================================================
   Fichier : public/assets/js/modules/resultats.js
   Rôle    : Gestion des résultats, procès-verbaux, délibérations
   Système : Bulletin congolais
   Version : 1.2 (Délégation d'événements globale)
   ============================================================ */

'use strict';

// 1. Écouteur global par délégation de clic
document.addEventListener('click', function (e) {

    // ------------------------------------------------------------
    // A. BOUTON CALCULER LES RÉSULTATS (Calculer / CalculerBis)
    // ------------------------------------------------------------
    const btnCalculer = e.target.closest('#btnCalculerResultats, #btnCalculerResultatsBis');
    
    if (btnCalculer) {
        e.preventDefault();

        const urlParams = new URLSearchParams(window.location.search);
        const classeId = btnCalculer.dataset.classeId || urlParams.get('classe_id');
        const trimestreId = btnCalculer.dataset.trimestreId || urlParams.get('trimestre_id');

        if (!classeId || !trimestreId) {
            afficherMessage('Veuillez sélectionner une classe et un trimestre.', 'warning');
            return;
        }

        const executerCalcul = function () {
            btnCalculer.disabled = true;
            btnCalculer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calcul en cours...';

            const formData = new FormData();
            formData.append('action', 'calculerResultatsClasse');
            formData.append('classe_id', classeId);
            formData.append('trimestre_id', trimestreId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    afficherMessage(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const errorMsg = data ? data.message : 'Erreur inconnue du serveur.';
                    afficherMessage(errorMsg, 'error');
                    btnCalculer.disabled = false;
                    btnCalculer.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculer les résultats';
                }
            })
            .catch(err => {
                console.error('Erreur Fetch Calcul:', err);
                afficherMessage('Erreur de connexion lors du calcul.', 'error');
                btnCalculer.disabled = false;
                btnCalculer.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculer les résultats';
            });
        };

        if (typeof showConfirmDialog === 'function') {
            showConfirmDialog(
                'Voulez-vous calculer les résultats pour cette classe et ce trimestre ?<br><small class="text-muted">Les résultats existants seront remplacés.</small>',
                function (confirmed) {
                    if (confirmed) executerCalcul();
                },
                {
                    title: 'Calculer les résultats',
                    confirmText: 'Calculer',
                    confirmClass: 'btn-primary',
                    icon: 'fa-calculator',
                    iconColor: '#2c3e90'
                }
            );
        } else {
            if (confirm('Voulez-vous calculer les résultats pour cette classe et ce trimestre ?')) {
                executerCalcul();
            }
        }
        return;
    }

    // ------------------------------------------------------------
    // B. BOUTON GÉNÉRER LES BULLETINS
    // ------------------------------------------------------------
    const btnGenerer = e.target.closest('#btnGenererBulletins');
    
    if (btnGenerer) {
        e.preventDefault();

        const urlParams = new URLSearchParams(window.location.search);
        const classeId = btnGenerer.dataset.classeId || urlParams.get('classe_id');
        const trimestreId = btnGenerer.dataset.trimestreId || urlParams.get('trimestre_id');
        const anneeId = btnGenerer.dataset.anneeId || urlParams.get('annee_id');

        if (!classeId || !trimestreId) {
            afficherMessage('Veuillez sélectionner une classe et un trimestre.', 'warning');
            return;
        }

        const executerGeneration = function () {
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
                if (data && data.success) {
                    afficherMessage(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const errorMsg = data ? data.message : 'Erreur lors de la génération.';
                    afficherMessage(errorMsg, 'error');
                    btnGenerer.disabled = false;
                    btnGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les bulletins maintenant';
                }
            })
            .catch(err => {
                console.error('Erreur Fetch Génération:', err);
                afficherMessage('Erreur de connexion.', 'error');
                btnGenerer.disabled = false;
                btnGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer les bulletins maintenant';
            });
        };

        if (typeof showConfirmDialog === 'function') {
            showConfirmDialog(
                'Voulez-vous générer les bulletins pour cette classe et ce trimestre ?<br><small class="text-muted">Les bulletins existants seront remplacés.</small>',
                function (confirmed) {
                    if (confirmed) executerGeneration();
                },
                {
                    title: 'Générer les bulletins',
                    confirmText: 'Générer',
                    confirmClass: 'btn-success',
                    icon: 'fa-file-invoice',
                    iconColor: '#10b981'
                }
            );
        } else {
            if (confirm('Voulez-vous générer les bulletins pour cette classe et ce trimestre ?')) {
                executerGeneration();
            }
        }
        return;
    }

    // ------------------------------------------------------------
    // C. BOUTON VALIDER TOUTES LES DÉCISIONS
    // ------------------------------------------------------------
    if (e.target.closest('#btnValiderDeliberations')) {
        document.querySelectorAll('.validation-checkbox').forEach(cb => {
            cb.checked = true;
            const row = cb.closest('.deliberation-row');
            if (row) {
                row.classList.add('validee');
                row.classList.remove('en-attente');
            }
        });
        afficherMessage('Toutes les décisions sont validées.', 'success');
        return;
    }

    // ------------------------------------------------------------
    // D. DÉLIBÉRATIONS (Admettre tous, Ajourner tous, Réinitialiser)
    // ------------------------------------------------------------
    if (e.target.closest('#btnAdmettreTous')) {
        document.querySelectorAll('.decision-select').forEach(select => {
            select.value = 'Admis';
            select.style.borderColor = '#10b981';
            const row = select.closest('.deliberation-row');
            if (row) updateRowState(row);
        });
        afficherMessage('Tous les élèves sont marqués "Admis".', 'info');
        return;
    }

    if (e.target.closest('#btnAjournerTous')) {
        document.querySelectorAll('.decision-select').forEach(select => {
            select.value = 'Ajourné';
            select.style.borderColor = '#ef4444';
            const row = select.closest('.deliberation-row');
            if (row) updateRowState(row);
        });
        afficherMessage('Tous les élèves sont marqués "Ajourné".', 'warning');
        return;
    }

    if (e.target.closest('#btnReinitialiserDecisions')) {
        document.querySelectorAll('.deliberation-row').forEach(row => {
            const decisionAuto = row.dataset.decisionAuto;
            const select = row.querySelector('.decision-select');
            if (select) {
                select.value = decisionAuto;
                select.style.borderColor = '';
                updateRowState(row);
            }
        });
        afficherMessage('Décisions réinitialisées aux valeurs automatiques.', 'info');
        return;
    }

    // ------------------------------------------------------------
    // E. SAUVEGARDE INDIVIDUELLE D'UNE DÉLIBÉRATION
    // ------------------------------------------------------------
    const btnSaveIndiv = e.target.closest('.btn-save-deliberation');
    if (btnSaveIndiv) {
        const resultatId = btnSaveIndiv.dataset.resultatId;
        sauvegarderDeliberation(resultatId);
        return;
    }

    // ------------------------------------------------------------
    // F. SAUVEGARDE GLOBALE DES DÉLIBÉRATIONS
    // ------------------------------------------------------------
    const btnSaveTout = e.target.closest('#btnSauvegarderTout');
    if (btnSaveTout) {
        const rows = document.querySelectorAll('.deliberation-row');
        const total = rows.length;
        if (total === 0) return;

        btnSaveTout.disabled = true;
        btnSaveTout.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde en cours...';

        const promises = Array.from(rows).map(row => {
            return sauvegarderDeliberationAsync(row.dataset.resultatId);
        });

        Promise.all(promises).then(results => {
            const successCount = results.filter(Boolean).length;
            btnSaveTout.disabled = false;
            btnSaveTout.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarder toutes les délibérations';
            afficherMessage(successCount + '/' + total + ' délibération(s) sauvegardée(s).', 'success');
        }).catch(err => {
            console.error('Erreur Sauvegarde globale:', err);
            btnSaveTout.disabled = false;
            btnSaveTout.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarder toutes les délibérations';
            afficherMessage('Erreur lors de la sauvegarde.', 'error');
        });
        return;
    }
});

// 2. Écouteur pour les changements dans les <select> de décision
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('decision-select')) {
        const row = e.target.closest('.deliberation-row');
        if (row) updateRowState(row);
    }
});

// 3. Fonctions Utilitaire Globales
window.imprimerBulletin = function (eleveId) {
    const urlParams = new URLSearchParams(window.location.search);
    const anneeId = urlParams.get('annee_id') || '';
    const url = '/gestion_scolaire/bulletins/consulter?eleve_id=' + eleveId + '&annee_id=' + anneeId;
    window.open(url, '_blank');
};

window.loadElevesForPrint = function (classeId) {
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
        if (data && data.success && data.bulletins) {
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
    })
    .catch(err => console.error('Erreur chargement élèves:', err));
};

// Functions internes
function afficherMessage(msg, type = 'info') {
    if (typeof showToast === 'function') {
        showToast(msg, type);
    } else {
        alert(msg);
    }
}

function sauvegarderDeliberation(resultatId) {
    const row = document.querySelector(`.deliberation-row[data-resultat-id="${resultatId}"]`);
    if (!row) return Promise.resolve(false);

    const decision = row.querySelector('.decision-select')?.value || '';
    const appreciation = row.querySelector('.appreciation-input')?.value || '';
    const decisionFinale = row.querySelector('.validation-checkbox')?.checked ? 1 : 0;

    const formData = new FormData();
    formData.append('action', 'sauvegarderDeliberation');
    formData.append('resultat_id', resultatId);
    formData.append('decision', decision);
    formData.append('appreciation', appreciation);
    formData.append('decision_finale', decisionFinale);

    return fetch('/gestion_scolaire/resultats', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            row.classList.add('validee');
            row.classList.remove('en-attente');
        }
        return !!(data && data.success);
    })
    .catch(() => false);
}

async function sauvegarderDeliberationAsync(resultatId) {
    try {
        return await sauvegarderDeliberation(resultatId);
    } catch (e) {
        return false;
    }
}

function updateRowState(row) {
    const select = row.querySelector('.decision-select');
    if (select) {
        if (select.value !== row.dataset.decisionAuto) {
            row.classList.add('modifiee');
        } else {
            row.classList.remove('modifiee');
        }
    }
}

console.log('%cModule Résultats (Event Delegation)%c prêt.', 'color: #2c3e90; font-weight: 700;', 'color: #64748b;');
