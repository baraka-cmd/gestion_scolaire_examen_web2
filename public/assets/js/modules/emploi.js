/* ============================================================
   Fichier : public/assets/js/modules/emploi.js
   Rôle    : Gestion interactive de l'emploi du temps
   Système : École primaire congolaise
   Version : 1.0
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const modalCreneau          = document.getElementById('modalCreneau');
    const modalCreneauTitre     = document.getElementById('modalCreneauTitre');
    const formCreneau           = document.getElementById('formCreneau');
    const btnSaveCreneau        = document.getElementById('btnSaveCreneau');
    const btnCloseCreneau       = document.getElementById('btnCloseCreneau');
    const btnCancelCreneau      = document.getElementById('btnCancelCreneau');
    const btnAjouterCreneau     = document.getElementById('btnAjouterCreneau') || document.getElementById('btnAjouterCreneauBis');
    const btnAjouterHoraire     = document.getElementById('btnAjouterHoraire') || document.getElementById('btnAjouterHoraireBis');
    const btnSupprimerTout      = document.getElementById('btnSupprimerTout');
    const creneauType           = document.getElementById('creneau_type');
    const creneauChampsCours    = document.getElementById('creneauChampsCours');
    const creneauChampsLibelle  = document.getElementById('creneauChampsLibelle');

    if (!formCreneau) return;

    // ============================================================
    // 2. OUVERTURE MODALE — AJOUTER UN CRÉNEAU
    // ============================================================
    if (btnAjouterCreneau) {
        btnAjouterCreneau.addEventListener('click', function() {
            resetCreneauForm();
            modalCreneauTitre.textContent = 'Ajouter un créneau';
            document.getElementById('creneau_id').value = '';
            openModal(modalCreneau);
        });
    }

    // ============================================================
    // 3. OUVERTURE MODALE — AJOUTER UN HORAIRE
    // ============================================================
    if (btnAjouterHoraire) {
        btnAjouterHoraire.addEventListener('click', function() {
            resetCreneauForm();
            modalCreneauTitre.textContent = 'Ajouter un horaire (créneau vide pour tous les jours)';
            document.getElementById('creneau_id').value = '';
            document.getElementById('creneau_matiere').value = '';
            document.getElementById('creneau_enseignant').value = '';
            openModal(modalCreneau);
        });
    }

    // ============================================================
    // 4. FERMETURE MODALE
    // ============================================================
    function closeModal() {
        if (modalCreneau) {
            modalCreneau.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    function openModal(modal) {
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    if (btnCloseCreneau) btnCloseCreneau.addEventListener('click', closeModal);
    if (btnCancelCreneau) btnCancelCreneau.addEventListener('click', closeModal);

    if (modalCreneau) {
        modalCreneau.addEventListener('click', function(e) {
            if (e.target === modalCreneau) closeModal();
        });
    }

    // ============================================================
    // 5. CHANGEMENT TYPE → AFFICHER/MASQUER CHAMPS
    // ============================================================
    if (creneauType) {
        creneauType.addEventListener('change', function() {
            toggleChampsType();
        });
    }

    function toggleChampsType() {
        const type = creneauType.value;
        if (type === 'cours') {
            creneauChampsCours.style.display = 'block';
            creneauChampsLibelle.style.display = 'none';
        } else {
            creneauChampsCours.style.display = 'none';
            creneauChampsLibelle.style.display = 'block';
            if (type === 'recréation') {
                document.getElementById('creneau_libelle').placeholder = 'Ex: Récréation, Pause déjeuner...';
            } else if (type === 'cernafor') {
                document.getElementById('creneau_libelle').placeholder = 'Ex: CERNAFOR';
            }
        }
    }

    // ============================================================
    // 6. SAUVEGARDE DU CRÉNEAU
    // ============================================================
    if (btnSaveCreneau) {
        btnSaveCreneau.addEventListener('click', function() {
            const jour        = document.getElementById('creneau_jour').value;
            const heureDebut  = document.getElementById('creneau_debut').value;
            const heureFin    = document.getElementById('creneau_fin').value;

            if (!jour || !heureDebut || !heureFin) {
                showToast('Veuillez remplir le jour, l\'heure de début et l\'heure de fin.', 'warning');
                return;
            }

            if (heureDebut >= heureFin) {
                showToast('L\'heure de début doit être avant l\'heure de fin.', 'warning');
                return;
            }

            const formData = new FormData(formCreneau);
            formData.append('action', 'sauvegarderCreneau');

            btnSaveCreneau.disabled = true;
            btnSaveCreneau.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';

            fetch('/gestion_scolaire/emploi-du-temps', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Erreur de connexion.', 'error');
            })
            .finally(() => {
                btnSaveCreneau.disabled = false;
                btnSaveCreneau.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer';
            });
        });
    }

    // ============================================================
    // 7. ÉDITER UN CRÉNEAU EXISTANT
    // ============================================================
    window.editerCreneau = function(cell) {
        if (!peutModifier) return;

        const creneauId   = cell.dataset.creneauId;
        const jour        = cell.dataset.jour;
        const debut       = cell.dataset.debut;
        const fin         = cell.dataset.fin;
        const matiere     = cell.dataset.matiere;
        const enseignant  = cell.dataset.enseignant;
        const type        = cell.dataset.type;
        const libelle     = cell.dataset.libelle;

        modalCreneauTitre.textContent = 'Modifier le créneau';
        document.getElementById('creneau_id').value = creneauId;
        document.getElementById('creneau_jour').value = jour;
        document.getElementById('creneau_debut').value = debut;
        document.getElementById('creneau_fin').value = fin;
        document.getElementById('creneau_type').value = type;

        if (type === 'cours') {
            document.getElementById('creneau_matiere').value = matiere;
            document.getElementById('creneau_enseignant').value = enseignant;
        } else {
            document.getElementById('creneau_libelle').value = libelle;
        }

        toggleChampsType();
        openModal(modalCreneau);
    };

    // ============================================================
    // 8. AJOUTER UN CRÉNEAU DEPUIS UNE CASE VIDE
    // ============================================================
    window.ajouterCreneau = function(jour, debut, fin) {
        if (!peutModifier) return;

        resetCreneauForm();
        modalCreneauTitre.textContent = 'Ajouter un créneau';
        document.getElementById('creneau_id').value = '';
        document.getElementById('creneau_jour').value = jour;
        document.getElementById('creneau_debut').value = debut;
        document.getElementById('creneau_fin').value = fin;
        document.getElementById('creneau_type').value = 'cours';
        toggleChampsType();
        openModal(modalCreneau);
    };

    // ============================================================
    // 9. SUPPRIMER UN CRÉNEAU
    // ============================================================
    window.supprimerCreneau = function(creneauId) {
        if (!peutModifier) return;

        showConfirmDialog(
            'Voulez-vous supprimer ce créneau ?',
            function(confirmed) {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('action', 'supprimerCreneau');
                    formData.append('creneau_id', creneauId);

                    fetch('/gestion_scolaire/emploi-du-temps', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => location.reload(), 400);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(() => showToast('Erreur de connexion.', 'error'));
                }
            },
            { title: 'Supprimer le créneau', confirmText: 'Supprimer', confirmClass: 'btn-danger', icon: 'fa-trash', iconColor: '#ef4444' }
        );
    };

    // ============================================================
    // 10. SUPPRIMER UN HORAIRE
    // ============================================================
    window.supprimerHoraire = function(debut, fin) {
        if (!peutModifier) return;

        showConfirmDialog(
            'Voulez-vous supprimer tous les créneaux de l\'horaire ' + debut.substring(0,5) + ' — ' + fin.substring(0,5) + ' ?<br><small class="text-muted">Tous les cours de cette ligne seront supprimés pour tous les jours.</small>',
            function(confirmed) {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('action', 'supprimerHoraire');
                    formData.append('classe_id', classeId);
                    formData.append('annee_id', anneeId);
                    formData.append('heure_debut', debut);
                    formData.append('heure_fin', fin);

                    fetch('/gestion_scolaire/emploi-du-temps', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => location.reload(), 400);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(() => showToast('Erreur de connexion.', 'error'));
                }
            },
            { title: 'Supprimer l\'horaire', confirmText: 'Supprimer', confirmClass: 'btn-danger', icon: 'fa-trash', iconColor: '#ef4444' }
        );
    };

    // ============================================================
    // 11. SUPPRIMER TOUT L'EMPLOI DU TEMPS
    // ============================================================
    if (btnSupprimerTout) {
        btnSupprimerTout.addEventListener('click', function() {
            showConfirmDialog(
                'Voulez-vous supprimer tout l\'emploi du temps de cette classe ?<br><small class="text-muted">Cette action est irréversible.</small>',
                function(confirmed) {
                    if (confirmed) {
                        const formData = new FormData();
                        formData.append('action', 'supprimerTout');
                        formData.append('classe_id', classeId);
                        formData.append('annee_id', anneeId);

                        fetch('/gestion_scolaire/emploi-du-temps', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message, 'success');
                                setTimeout(() => location.reload(), 400);
                            } else {
                                showToast(data.message, 'error');
                            }
                        })
                        .catch(() => showToast('Erreur de connexion.', 'error'));
                    }
                },
                { title: 'Vider l\'emploi du temps', confirmText: 'Tout supprimer', confirmClass: 'btn-danger', icon: 'fa-trash', iconColor: '#ef4444' }
            );
        });
    }

    // ============================================================
    // 12. RÉINITIALISER LE FORMULAIRE
    // ============================================================
    function resetCreneauForm() {
        if (formCreneau) formCreneau.reset();
        document.getElementById('creneau_id').value = '';
        document.getElementById('creneau_classe_id').value = classeId;
        document.getElementById('creneau_annee_id').value = anneeId;
        creneauType.value = 'cours';
        toggleChampsType();
    }

    // ============================================================
    // 13. RACCOURCI CLAVIER
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalCreneau && modalCreneau.classList.contains('show')) {
            closeModal();
        }
    });

    // ============================================================
    // 14. INITIALISATION
    // ============================================================
    toggleChampsType();

    console.log('%cEmploi du temps%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 700;', 'color: #64748b;');
});