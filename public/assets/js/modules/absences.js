/* ============================================================
   Fichier : public/assets/js/modules/absences.js
   Rôle    : Gestion interactive des absences
   Système : École primaire congolaise
   Version : 1.0
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const modalJustifier       = document.getElementById('modalJustifier');
    const formJustifier        = document.getElementById('formJustifier');
    const btnSaveJustification = document.getElementById('btnSaveJustification');
    const btnTousPresents      = document.getElementById('btnTousPresents');
    const btnTousAbsents       = document.getElementById('btnTousAbsents');

    // ============================================================
    // 2. MARQUER UN ÉLÈVE PRÉSENT/ABSENT
    // ============================================================
    window.marquerEleve = function(eleveId, type, btn) {
        const card = btn.closest('.absence-eleve-card');
        if (!card) return;

        // Désactiver tous les boutons de la carte
        card.querySelectorAll('.btn-marquer').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Changer les classes de la carte
        card.classList.remove('present', 'absent', 'non-marque');
        card.classList.add(type === 'Présent' ? 'present' : 'absent');

        // Mettre à jour le badge de statut
        const badgeContainer = card.querySelector('.absence-eleve-statut');
        if (badgeContainer) {
            if (type === 'Présent') {
                badgeContainer.innerHTML = '<span class="badge badge-success">Présent</span>';
            } else {
                badgeContainer.innerHTML = '<span class="badge badge-danger">Absent</span>';
            }
        }

        // Gérer l'affichage du motif et du bouton justifier
        if (type === 'Présent') {
            // Cacher le motif
            const motifDiv = card.querySelector('.absence-motif-rapide');
            if (motifDiv) motifDiv.style.display = 'none';
            // Cacher le bouton justifier
            const btnJustifier = card.querySelector('.btn-marquer.justifier');
            if (btnJustifier) btnJustifier.style.display = 'none';
        } else {
            // Afficher ou créer le champ motif
            let motifDiv = card.querySelector('.absence-motif-rapide');
            if (!motifDiv) {
                motifDiv = document.createElement('div');
                motifDiv.className = 'absence-motif-rapide';
                motifDiv.innerHTML = '<input type="text" class="form-control form-control-sm" placeholder="Motif (optionnel)" data-eleve-id="' + eleveId + '" onchange="sauvegarderMotif(' + eleveId + ', this.value)">';
                card.appendChild(motifDiv);
            } else {
                motifDiv.style.display = 'block';
            }

            // Afficher ou créer le bouton justifier
            let btnJustifier = card.querySelector('.btn-marquer.justifier');
            if (!btnJustifier) {
                btnJustifier = document.createElement('button');
                btnJustifier.type = 'button';
                btnJustifier.className = 'btn-marquer justifier';
                btnJustifier.title = 'Justifier l\'absence';
                btnJustifier.innerHTML = '<i class="fa-solid fa-file-medical"></i>';
                btnJustifier.onclick = function() { justifierAbsence(eleveId); };
                card.querySelector('.absence-eleve-actions').appendChild(btnJustifier);
            } else {
                btnJustifier.style.display = 'inline-flex';
            }
        }

        // Sauvegarder automatiquement
        sauvegarderAbsence(eleveId, type);

        // Mettre à jour les compteurs
        updateCompteurs();
    };


    // Fonction complète de sauvegarde de justification (pour absences.js)
async function sauvegarderJustification() {
    let motif = document.getElementById('justifier_motif')?.value || document.getElementById('just_motif')?.value;
    let commentaire = document.getElementById('justifier_commentaire')?.value || document.getElementById('just_commentaire')?.value || '';
    let eleveId = document.getElementById('justifier_eleve_id')?.value || '';
    let absenceId = document.getElementById('just_absence_id')?.value || '';

    if (!motif) {
        showToast('Veuillez sélectionner un motif de justification.', 'warning');
        return;
    }

    const formData = new FormData();

    if (absenceId) {
        formData.append('action', 'justifierAbsenceDirecte');
        formData.append('absence_id', absenceId);
    } else {
        formData.append('action', 'justifierAbsence');
        formData.append('eleve_id', eleveId);
        formData.append('date_absence', typeof dateAbsence !== 'undefined' ? dateAbsence : '');
    }

    formData.append('motif', motif);
    formData.append('commentaire', commentaire);

    const btn = document.getElementById('btnSaveJustification');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';
    }

    try {
        const res = await fetch('/gestion_scolaire/absences', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const text = await res.text();
        console.log('Status:', res.status, 'Réponse:', text);

        if (text) {
            const data = JSON.parse(text);
            if (data.success) {
                showToast(data.message, 'success');
                if (typeof closeModalJustifier === 'function') closeModalJustifier();
                if (typeof closeModalJustification === 'function') closeModalJustification();
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(data.message, 'error');
            }
        }
    } catch (e) {
        console.error('Erreur:', e);
        showToast('Erreur de connexion.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer';
        }
    }
}
    // ============================================================
    // 3. SAUVEGARDER UNE ABSENCE
    // ============================================================
    async function sauvegarderAbsence(eleveId, type) {
    const formData = new FormData();
    formData.append('action', 'marquerAbsence');
    formData.append('eleve_id', eleveId);
    formData.append('classe_id', classeId);
    formData.append('date_absence', dateAbsence);
    formData.append('type_absence', type);

    try {
        const res = await fetch('/gestion_scolaire/absences', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        console.log('Status:', res.status);
        const text = await res.text();
        console.log('Réponse:', text);
        
        if (text) {
            const data = JSON.parse(text);
            if (!data.success) {
                showToast(data.message, 'error');
            }
        }
    } catch (e) {
        console.error('Erreur:', e);
    }
}

    // ============================================================
    // 4. SAUVEGARDER LE MOTIF
    // ============================================================
    window.sauvegarderMotif = async function(eleveId, motif) {
        const formData = new FormData();
        formData.append('action', 'sauvegarderMotif');
        formData.append('eleve_id', eleveId);
        formData.append('date_absence', dateAbsence);
        formData.append('motif', motif);

        try {
            const res = await fetch('/gestion_scolaire/absences', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                showToast('Motif sauvegardé.', 'success');
            } else {
                showToast(data.message, 'error');
            }
        } catch (e) {
            console.error('Erreur motif:', e);
        }
    };

    // ============================================================
    // 5. JUSTIFIER UNE ABSENCE
    // ============================================================
    window.justifierAbsence = function(eleveId) {
        if (!modalJustifier) return;
        document.getElementById('justifier_eleve_id').value = eleveId;
        modalJustifier.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.closeModalJustifier = function() {
        if (!modalJustifier) return;
        modalJustifier.classList.remove('show');
        document.body.style.overflow = '';
        if (formJustifier) formJustifier.reset();
    };

    // Fermer au clic extérieur
    if (modalJustifier) {
        modalJustifier.addEventListener('click', function(e) {
            if (e.target === modalJustifier) closeModalJustifier();
        });
    }

    // Chercher le bouton dans les deux pages possibles
const btnSaveJustification = document.getElementById('btnSaveJustification');

if (btnSaveJustification) {
    btnSaveJustification.addEventListener('click', async function() {
        // Essayer les IDs de marquer.php d'abord
        let motif = document.getElementById('justifier_motif')?.value || document.getElementById('just_motif')?.value;
        let commentaire = document.getElementById('justifier_commentaire')?.value || document.getElementById('just_commentaire')?.value || '';
        let eleveId = document.getElementById('justifier_eleve_id')?.value || '';
        let absenceId = document.getElementById('just_absence_id')?.value || '';

        if (!motif) {
            showToast('Veuillez sélectionner un motif de justification.', 'warning');
            return;
        }

        const formData = new FormData();
        
        if (absenceId) {
            // Mode justifier.php
            formData.append('action', 'justifierAbsenceDirecte');
            formData.append('absence_id', absenceId);
        } else {
            // Mode marquer.php
            formData.append('action', 'justifierAbsence');
            formData.append('eleve_id', eleveId);
            formData.append('date_absence', dateAbsence || '');
        }
        
        formData.append('motif', motif);
        formData.append('commentaire', commentaire);

        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';

        try {
            const res = await fetch('/gestion_scolaire/absences', {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                if (typeof closeModalJustifier === 'function') closeModalJustifier();
                if (typeof closeModalJustification === 'function') closeModalJustification();
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(data.message, 'error');
            }
        } catch (e) {
            showToast('Erreur de connexion.', 'error');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer';
        }
    });
}

    // ============================================================
    // 6. TOUS PRÉSENTS / TOUS ABSENTS
    // ============================================================
    if (btnTousPresents) {
        btnTousPresents.addEventListener('click', function() {
            const cards = document.querySelectorAll('.absence-eleve-card');
            if (cards.length === 0) return;

            showConfirmDialog(
                'Voulez-vous marquer <strong>tous</strong> les élèves comme <strong>présents</strong> ?',
                function(confirmed) {
                    if (confirmed) {
                        cards.forEach(card => {
                            const btn = card.querySelector('.btn-marquer.present');
                            if (btn) marquerEleve(card.dataset.eleveId, 'Présent', btn);
                        });
                        showToast('Tous les élèves sont marqués présents.', 'success');
                    }
                },
                { title: 'Tous présents', confirmText: 'Confirmer', confirmClass: 'btn-success', icon: 'fa-check-double', iconColor: '#10b981' }
            );
        });
    }

    if (btnTousAbsents) {
        btnTousAbsents.addEventListener('click', function() {
            const cards = document.querySelectorAll('.absence-eleve-card');
            if (cards.length === 0) return;

            showConfirmDialog(
                'Voulez-vous marquer <strong>tous</strong> les élèves comme <strong>absents</strong> ?',
                function(confirmed) {
                    if (confirmed) {
                        cards.forEach(card => {
                            const btn = card.querySelector('.btn-marquer.absent');
                            if (btn) marquerEleve(card.dataset.eleveId, 'Absence', btn);
                        });
                        showToast('Tous les élèves sont marqués absents.', 'warning');
                    }
                },
                { title: 'Tous absents', confirmText: 'Confirmer', confirmClass: 'btn-danger', icon: 'fa-xmark', iconColor: '#ef4444' }
            );
        });
    }

    // ============================================================
    // 7. METTRE À JOUR LES COMPTEURS
    // ============================================================
    function updateCompteurs() {
        const cards = document.querySelectorAll('.absence-eleve-card');
        let presents = 0;
        let absents = 0;
        let nonMarques = 0;

        cards.forEach(card => {
            if (card.classList.contains('present')) presents++;
            else if (card.classList.contains('absent')) absents++;
            else nonMarques++;
        });

        // Mettre à jour les compteurs dans la barre d'info
        const infoItems = document.querySelectorAll('.absence-info-item');
        infoItems.forEach(item => {
            if (item.classList.contains('present')) {
                item.innerHTML = '<i class="fa-solid fa-check-circle"></i><span><strong>' + presents + '</strong> présent(s)</span>';
            } else if (item.classList.contains('absent')) {
                item.innerHTML = '<i class="fa-solid fa-times-circle"></i><span><strong>' + absents + '</strong> absent(s)</span>';
            } else if (item.textContent.includes('non marqué')) {
                item.innerHTML = '<i class="fa-solid fa-clock"></i><span><strong>' + nonMarques + '</strong> non marqué(s)</span>';
            }
        });
    }

    // ============================================================
    // 8. RACCOURCI CLAVIER
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalJustifier && modalJustifier.classList.contains('show')) {
            closeModalJustifier();
        }
    });

    // ============================================================
    // 9. INITIALISATION
    // ============================================================
    console.log('%cAbsences%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 700;', 'color: #64748b;');
});