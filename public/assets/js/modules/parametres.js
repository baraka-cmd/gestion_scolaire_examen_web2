/**
 * Fichier : public/assets/js/modules/parametres.js
 * Rôle    : Scripts de la page des paramètres de l'école
 * Système : École primaire congolaise — Administration
 * Version : 1.0
 */

'use strict';

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initLogoUpload();
    initSauvegarde();
    initRaccourcisClavier();
});





// ============================================================
// 2. UPLOAD DU LOGO
// ============================================================
function initLogoUpload() {
    var btnChanger = document.getElementById('btnChangerLogo');
    var inputLogo = document.getElementById('logoInput');
    var logoPreview = document.getElementById('logoPreview');

    if (!btnChanger || !inputLogo || !logoPreview) return;

    // Ouvrir le sélecteur de fichier
    btnChanger.addEventListener('click', function() {
        inputLogo.click();
    });

    // Quand un fichier est sélectionné
    inputLogo.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var fichier = this.files[0];
            var extension = fichier.name.split('.').pop().toLowerCase();
            var allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            // Validation
            if (allowedExtensions.indexOf(extension) === -1) {
                showToast('Format non autorisé. Utilisez JPG, PNG ou GIF.', 'error');
                this.value = '';
                return;
            }

            if (fichier.size > 2 * 1024 * 1024) {
                showToast('Le logo ne doit pas dépasser 2 Mo.', 'error');
                this.value = '';
                return;
            }

            // Aperçu local avant upload
            var reader = new FileReader();
            reader.onload = function(e) {
                logoPreview.innerHTML = '<img src="' + e.target.result + '" alt="Logo" class="logo-image">';
            };
            reader.readAsDataURL(fichier);

            // Upload vers le serveur
            uploaderLogo(fichier);
        }
    });

    // Support drag & drop sur le logo
    logoPreview.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#2c3e90';
        this.style.background = '#e8eaf6';
    });

    logoPreview.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#cbd5e1';
        this.style.background = '#f8fafc';
    });

    logoPreview.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#cbd5e1';
        this.style.background = '#f8fafc';

        var fichier = e.dataTransfer.files[0];
        if (fichier) {
            inputLogo.files = e.dataTransfer.files;
            inputLogo.dispatchEvent(new Event('change'));
        }
    });
}

function uploaderLogo(fichier) {
    var formData = new FormData();
    formData.append('action', 'uploadLogo');
    formData.append('logo', fichier);

    var btnChanger = document.getElementById('btnChangerLogo');
    if (btnChanger) {
        btnChanger.disabled = true;
        btnChanger.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Upload...';
    }

    fetch('/gestion_scolaire/parametres', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast('Logo mis à jour avec succès !', 'success');
        } else {
            showToast(data.message || 'Erreur lors de l\'upload.', 'error');
        }
    })
    .catch(function() {
        showToast('Erreur de connexion lors de l\'upload.', 'error');
    })
    .finally(function() {
        if (btnChanger) {
            btnChanger.disabled = false;
            btnChanger.innerHTML = '<i class="fa-solid fa-upload"></i> Changer le logo';
        }
    });
}

// ============================================================
// 3. CHARGER LES TRIMESTRES SELON L'ANNÉE SÉLECTIONNÉE
// ============================================================
function chargerTrimestres() {
    var anneeId = document.getElementById('annee_scolaire_active_id').value;
    var selectTrimestre = document.getElementById('trimestre_actif_id');

    if (!anneeId || !selectTrimestre) return;

    selectTrimestre.innerHTML = '<option value="">Chargement...</option>';
    selectTrimestre.disabled = true;

    var formData = new FormData();
    formData.append('action', 'getTrimestres');
    formData.append('annee_id', anneeId);

    fetch('/gestion_scolaire/parametres', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        selectTrimestre.innerHTML = '<option value="">— Sélectionnez un trimestre —</option>';
        
        if (data.success && data.trimestres && data.trimestres.length > 0) {
            data.trimestres.forEach(function(t) {
                var option = document.createElement('option');
                option.value = t.id;
                option.textContent = t.libelle + (t.est_actif ? ' (Actif)' : '');
                if (t.est_actif) option.selected = true;
                selectTrimestre.appendChild(option);
            });
        } else {
            selectTrimestre.innerHTML = '<option value="">Aucun trimestre trouvé</option>';
        }
    })
    .catch(function() {
        selectTrimestre.innerHTML = '<option value="">Erreur de chargement</option>';
    })
    .finally(function() {
        selectTrimestre.disabled = false;
    });
}

// ============================================================
// 4. SAUVEGARDE DES PARAMÈTRES
// ============================================================
var sauvegardeEnCours = false;

function initSauvegarde() {
    var boutons = ['btnSauvegarder', 'btnSauvegarderTout'];
    
    boutons.forEach(function(id) {
        var btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', sauvegarderParametres);
        }
    });
}

function sauvegarderParametres() {
    if (sauvegardeEnCours) return;

   

    // Vérifier les champs obligatoires
    var nomEcole = document.getElementById('nom_ecole').value.trim();
    var anneeId = document.getElementById('annee_scolaire_active_id').value;
    var trimestreId = document.getElementById('trimestre_actif_id').value;

    if (!nomEcole) {
        showToast('Le nom de l\'école est obligatoire.', 'warning');
        document.getElementById('nom_ecole').focus();
        return;
    }
    if (!anneeId) {
        showToast('Veuillez sélectionner une année scolaire active.', 'warning');
        document.getElementById('annee_scolaire_active_id').focus();
        return;
    }
    if (!trimestreId) {
        showToast('Veuillez sélectionner un trimestre actif.', 'warning');
        document.getElementById('trimestre_actif_id').focus();
        return;
    }

    sauvegardeEnCours = true;

    var formData = new FormData(document.getElementById('formParametres'));
    formData.append('action', 'sauvegarder');

    // Désactiver tous les boutons de sauvegarde
    var boutons = document.querySelectorAll('#btnSauvegarder, #btnSauvegarderTout');
    boutons.forEach(function(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde en cours...';
    });

    fetch('/gestion_scolaire/parametres', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message, 'success');
            
            // Mettre à jour les badges "Actif" dans les sélecteurs
            setTimeout(function() {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message, 'error');
            reactiverBoutons();
        }
    })
    .catch(function() {
        showToast('Erreur de connexion au serveur.', 'error');
        reactiverBoutons();
    });
}

function reactiverBoutons() {
    sauvegardeEnCours = false;
    var boutons = document.querySelectorAll('#btnSauvegarder, #btnSauvegarderTout');
    boutons.forEach(function(btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarder tous les paramètres';
    });
}

// ============================================================
// 5. RACCOURCIS CLAVIER
// ============================================================
function initRaccourcisClavier() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+S : Sauvegarder les paramètres
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            sauvegarderParametres();
        }
    });
}

// ============================================================
// 6. CONFIRMATION AVANT DE QUITTER (si modifications non sauvegardées)
// ============================================================
var modificationsEnCours = false;

document.addEventListener('DOMContentLoaded', function() {
    var formulaires = document.querySelectorAll('#formParametres input, #formParametres select, #formParametres textarea');
    formulaires.forEach(function(el) {
        el.addEventListener('change', function() {
            modificationsEnCours = true;
        });
        el.addEventListener('input', function() {
            modificationsEnCours = true;
        });
    });
});

window.addEventListener('beforeunload', function(e) {
    if (modificationsEnCours && !sauvegardeEnCours) {
        e.preventDefault();
        e.returnValue = 'Vous avez des modifications non sauvegardées. Voulez-vous vraiment quitter ?';
        return e.returnValue;
    }
});