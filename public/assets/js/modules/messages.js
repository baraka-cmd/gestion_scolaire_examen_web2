/**
 * Fichier : public/assets/js/modules/messages.js
 * Rôle    : Scripts de la messagerie interne
 * Système : École primaire congolaise — Communication
 * Version : 1.0
 */

'use strict';

// ============================================================
// VARIABLES GLOBALES
// ============================================================
var ongletActif = 'recus';
var currentMessageId = null;
var currentExpediteurId = null;
var currentMessageSujet = null;
var searchTimeout;
var refreshInterval;

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initMessagerie();
    initRaccourcisClavier();
    initRefreshAuto();
});

// ============================================================
// 1. INITIALISATION PRINCIPALE
// ============================================================
function initMessagerie() {
    // Déterminer la page active
    var isBoitePage = document.getElementById('messagesContainer') !== null;
    var isEnvoiPage = document.getElementById('formMessage') !== null;
    var isConversationPage = document.getElementById('conversationContainer') !== null;

    if (isBoitePage) {
        chargerMessages(1);
        initFiltresBoite();
        initApercuMessage();
    }

    if (isEnvoiPage) {
        initFormulaireEnvoi();
    }

    if (isConversationPage) {
        initConversation();
    }

    // Initialiser le compteur de messages non lus dans la navbar
    updateCompteurNavbar();
}

// ============================================================
// 2. BOÎTE DE RÉCEPTION — CHARGEMENT
// ============================================================
function chargerMessages(page) {
    var container = document.getElementById('messagesList');
    var loading = document.getElementById('messagesLoading');
    var empty = document.getElementById('messagesEmpty');

    if (!container) return;

    container.innerHTML = '';
    if (loading) loading.style.display = 'flex';
    if (empty) empty.style.display = 'none';

    var formData = new FormData();
    formData.append('action', 'getListe');
    formData.append('page', page || 1);
    formData.append('type', ongletActif);

    var lu = document.getElementById('filterLu')?.value || '';
    var recherche = document.getElementById('searchMessages')?.value || '';

    if (lu !== '') formData.append('lu', lu);
    if (recherche) formData.append('recherche', recherche);

    fetch('/gestion_scolaire/messages', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (loading) loading.style.display = 'none';
        if (data.success) {
            afficherMessages(data);
        } else {
            showToast(data.message || 'Erreur de chargement.', 'error');
        }
    })
    .catch(function() {
        if (loading) loading.style.display = 'none';
        showToast('Erreur de connexion au serveur.', 'error');
    });
}

// ============================================================
// 3. BOÎTE DE RÉCEPTION — AFFICHAGE
// ============================================================
function afficherMessages(data) {
    var container = document.getElementById('messagesList');
    var empty = document.getElementById('messagesEmpty');
    var paginationWrapper = document.getElementById('paginationWrapper');

    if (!container) return;

    if (!data.messages || data.messages.length === 0) {
        container.innerHTML = '';
        if (empty) {
            empty.style.display = 'flex';
            var emptyText = document.getElementById('messagesEmptyText');
            if (emptyText) {
                emptyText.textContent = ongletActif === 'recus' ? 'Votre boîte de réception est vide.' :
                                       ongletActif === 'envoyes' ? 'Aucun message envoyé.' :
                                       'Aucun message archivé.';
            }
        }
        if (paginationWrapper) paginationWrapper.style.display = 'none';
        return;
    }

    if (empty) empty.style.display = 'none';

    container.innerHTML = data.messages.map(function(m) {
        return renderMessageItem(m);
    }).join('');

    // Pagination
    if (paginationWrapper) {
        if (data.pages > 1) {
            paginationWrapper.style.display = 'flex';
            var paginationHtml = '';
            for (var i = 1; i <= data.pages; i++) {
                paginationHtml += '<a href="#" class="pagination-link' + (i === data.page ? ' active' : '') + '" data-page="' + i + '">' + i + '</a>';
            }
            document.getElementById('paginationMessages').innerHTML = paginationHtml;
            document.getElementById('paginationInfo').textContent = 'Page ' + data.page + ' / ' + data.pages + ' — ' + data.total + ' message(s)';

            // Attacher les événements de pagination
            document.querySelectorAll('.pagination-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    chargerMessages(parseInt(this.dataset.page));
                });
            });
        } else {
            paginationWrapper.style.display = 'none';
        }
    }

    // Mettre à jour le bouton "Tout marquer comme lu"
    var btnToutLu = document.getElementById('btnToutLu');
    if (btnToutLu) {
        btnToutLu.disabled = (data.nb_non_lus === 0);
    }

    // Attacher les événements sur les items
    attachMessageItemEvents();
}

function renderMessageItem(m) {
    var isNonLu = ongletActif === 'recus' && !m.est_lu;
    var nomAffiche = ongletActif === 'envoyes' ? m.destinataire_nom : m.expediteur_nom;
    var loginAffiche = ongletActif === 'envoyes' ? m.destinataire_login : m.expediteur_login;
    var interlocuteurId = ongletActif === 'envoyes' ? m.destinataire_id : m.expediteur_id;

    var avatarLettre = (nomAffiche || '?').charAt(0).toUpperCase();

    return `
    <div class="message-item ${isNonLu ? 'non-lu' : ''}" 
         data-id="${m.id}" 
         data-expediteur="${m.expediteur_id}"
         data-sujet="${escapeHtml(m.sujet)}"
         data-contenu="${escapeHtml(m.contenu)}"
         data-date="${m.date_format} à ${m.heure_format}"
         data-expediteur-nom="${escapeHtml(m.expediteur_nom)}"
         data-destinataire-nom="${escapeHtml(m.destinataire_nom)}"
         data-interlocuteur="${interlocuteurId}"
         data-interlocuteur-nom="${escapeHtml(nomAffiche)}">
        
        <div class="message-item-avatar" onclick="ouvrirConversation(${interlocuteurId})" title="Voir la conversation avec ${escapeHtml(nomAffiche)}">
            <div class="message-avatar-placeholder">${avatarLettre}</div>
        </div>
        
        <div class="message-item-content">
            <div class="message-item-header">
                <span class="message-item-nom">${escapeHtml(nomAffiche)}</span>
                <span class="message-item-login">@${escapeHtml(loginAffiche)}</span>
                ${isNonLu ? '<span class="message-badge-non-lu">Nouveau</span>' : ''}
            </div>
            <div class="message-item-sujet">${escapeHtml(m.sujet)}</div>
            <div class="message-item-extrait">${tronquerTexte(m.contenu, 120)}</div>
        </div>
        
        <div class="message-item-meta">
            <span class="message-item-date" title="${m.date_format} à ${m.heure_format}">
                <i class="fa-regular fa-clock"></i> ${m.date_format}
            </span>
        </div>
        
        <div class="message-item-actions">
            <button type="button" class="btn-icon btn-icon-sm" onclick="ouvrirConversation(${interlocuteurId})" title="Voir la conversation">
                <i class="fa-solid fa-comments"></i>
            </button>
            ${ongletActif !== 'archives' ? `
            <button type="button" class="btn-icon btn-icon-sm" onclick="archiverMessage(${m.id}, event)" title="Archiver">
                <i class="fa-solid fa-box-archive"></i>
            </button>
            ` : `
            <button type="button" class="btn-icon btn-icon-sm" onclick="desarchiverMessage(${m.id}, event)" title="Désarchiver">
                <i class="fa-solid fa-inbox"></i>
            </button>
            `}
            <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" onclick="supprimerMessage(${m.id}, event)" title="Supprimer">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </div>`;
}

function attachMessageItemEvents() {
    document.querySelectorAll('.message-item-content').forEach(function(content) {
        content.addEventListener('click', function() {
            var item = this.closest('.message-item');
            if (!item) return;

            var id = item.dataset.id;
            var sujet = item.dataset.sujet;
            var contenu = item.dataset.contenu;
            var expediteurNom = item.dataset.expediteurNom;
            var interlocuteurNom = item.dataset.interlocuteurNom;
            var date = item.dataset.date;
            var expediteurId = item.dataset.expediteur;
            var login = '';

            if (ongletActif === 'envoyes') {
                login = item.dataset.destinataireNom || '';
            }

            ouvrirApercu(id, sujet, contenu, interlocuteurNom || expediteurNom, login, date, parseInt(expediteurId));
        });
    });

    document.querySelectorAll('.message-item-avatar').forEach(function(avatar) {
        avatar.addEventListener('click', function(e) {
            e.stopPropagation();
            var item = this.closest('.message-item');
            if (item) {
                var interlocuteurId = parseInt(item.dataset.interlocuteur);
                if (interlocuteurId) {
                    ouvrirConversation(interlocuteurId);
                }
            }
        });
    });
}

// ============================================================
// 4. BOÎTE DE RÉCEPTION — FILTRES
// ============================================================
function initFiltresBoite() {
    var filterLu = document.getElementById('filterLu');
    var searchInput = document.getElementById('searchMessages');
    var btnActualiser = document.getElementById('btnActualiser');
    var btnToutLu = document.getElementById('btnToutLu');

    if (filterLu) {
        filterLu.addEventListener('change', function() {
            chargerMessages(1);
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                chargerMessages(1);
            }, 400);
        });
    }

    if (btnActualiser) {
        btnActualiser.addEventListener('click', function() {
            chargerMessages(1);
            updateCompteurNavbar();
            showToast('Messages actualisés.', 'info', 1500);
        });
    }

    if (btnToutLu) {
        btnToutLu.addEventListener('click', function() {
            marquerToutLu();
        });
    }
}

function changerOnglet(type, btn) {
    ongletActif = type;
    
    document.querySelectorAll('.message-tab').forEach(function(t) {
        t.classList.remove('active');
    });
    
    if (btn) {
        btn.classList.add('active');
    } else {
        var tab = document.querySelector('.message-tab[data-tab="' + type + '"]');
        if (tab) tab.classList.add('active');
    }

    var searchInput = document.getElementById('searchMessages');
    if (searchInput) {
        searchInput.placeholder = type === 'recus' ? 'Rechercher par sujet, contenu, expéditeur...' :
                                  type === 'envoyes' ? 'Rechercher par sujet, contenu, destinataire...' :
                                  'Rechercher dans les archives...';
    }

    chargerMessages(1);
}

// ============================================================
// 5. APERÇU DU MESSAGE
// ============================================================
function initApercuMessage() {
    var overlay = document.getElementById('messagePreview');
    if (!overlay) return;

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            fermerApercu();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fermerApercu();
        }
    });

    var btnRepondre = document.getElementById('btnRepondrePreview');
    if (btnRepondre) {
        btnRepondre.addEventListener('click', function() {
            repondreMessage();
        });
    }
}

function ouvrirApercu(id, sujet, contenu, nom, login, date, expediteurId) {
    currentMessageId = id;
    currentExpediteurId = expediteurId;
    currentMessageSujet = sujet;

    var previewSujet = document.getElementById('previewSujet');
    var previewMeta = document.getElementById('previewMeta');
    var previewContenu = document.getElementById('previewContenu');
    var overlay = document.getElementById('messagePreview');

    if (previewSujet) previewSujet.textContent = sujet;
    if (previewMeta) previewMeta.innerHTML = '<i class="fa-solid fa-user"></i> ' + escapeHtml(nom) + (login ? ' (@' + escapeHtml(login) + ')' : '') + ' — <i class="fa-regular fa-clock"></i> ' + escapeHtml(date);
    if (previewContenu) previewContenu.innerHTML = '<p>' + escapeHtml(contenu).replace(/\n/g, '<br>') + '</p>';
    if (overlay) {
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    if (ongletActif === 'recus') {
        marquerLu(id);
    }
}

function fermerApercu() {
    var overlay = document.getElementById('messagePreview');
    if (overlay) {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
    currentMessageId = null;
    currentExpediteurId = null;
    currentMessageSujet = null;
}

function repondreMessage() {
    if (currentExpediteurId) {
        window.location.href = '/gestion_scolaire/messages/envoyer?destinataire_id=' + currentExpediteurId + '&repondre=' + currentMessageId;
    }
    fermerApercu();
}

// ============================================================
// 6. ACTIONS SUR MESSAGES
// ============================================================
function marquerLu(messageId) {
    var formData = new FormData();
    formData.append('action', 'marquerLu');
    formData.append('message_id', messageId);

    fetch('/gestion_scolaire/messages', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var item = document.querySelector('.message-item[data-id="' + messageId + '"]');
            if (item) {
                item.classList.remove('non-lu');
                var badge = item.querySelector('.message-badge-non-lu');
                if (badge) badge.remove();
            }
            updateCompteurNavbar();
        }
    });
}

function marquerToutLu() {
    var items = document.querySelectorAll('.message-item.non-lu');
    if (items.length === 0) return;

    var promises = [];
    items.forEach(function(item) {
        var formData = new FormData();
        formData.append('action', 'marquerLu');
        formData.append('message_id', item.dataset.id);
        promises.push(
            fetch('/gestion_scolaire/messages', {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
        );

        item.classList.remove('non-lu');
        var badge = item.querySelector('.message-badge-non-lu');
        if (badge) badge.remove();
    });

    Promise.all(promises).then(function() {
        showToast(items.length + ' message(s) marqué(s) comme lu(s).', 'success');
        var btnToutLu = document.getElementById('btnToutLu');
        if (btnToutLu) btnToutLu.disabled = true;
        updateCompteurNavbar();
    });
}

function archiverMessage(messageId, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    var formData = new FormData();
    formData.append('action', 'archiver');
    formData.append('message_id', messageId);

    fetch('/gestion_scolaire/messages', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast('Message archivé.', 'success');
            animerEtSupprimerItem(messageId);
        }
    });
}

function desarchiverMessage(messageId, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    var formData = new FormData();
    formData.append('action', 'archiver');
    formData.append('message_id', messageId);

    fetch('/gestion_scolaire/messages', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast('Message désarchivé.', 'success');
            animerEtSupprimerItem(messageId);
        }
    });
}

function supprimerMessage(messageId, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    showConfirmDialog(
        'Voulez-vous supprimer ce message ? Cette action est irréversible.',
        function(confirmed) {
            if (confirmed) {
                var formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('message_id', messageId);

                fetch('/gestion_scolaire/messages', {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        showToast('Message supprimé.', 'success');
                        animerEtSupprimerItem(messageId);
                        updateCompteurNavbar();
                    }
                });
            }
        },
        { 
            title: 'Supprimer le message', 
            confirmText: 'Supprimer', 
            confirmClass: 'btn-danger', 
            icon: 'fa-trash', 
            iconColor: '#ef4444' 
        }
    );
}

function animerEtSupprimerItem(messageId) {
    var item = document.querySelector('.message-item[data-id="' + messageId + '"]');
    if (item) {
        item.style.opacity = '0';
        item.style.transform = 'translateX(60px)';
        item.style.transition = 'all 0.35s ease';
        setTimeout(function() {
            if (item.parentNode) item.parentNode.removeChild(item);
            // Vérifier s'il reste des messages
            var container = document.getElementById('messagesList');
            if (container && container.children.length === 0) {
                var empty = document.getElementById('messagesEmpty');
                if (empty) empty.style.display = 'flex';
            }
        }, 350);
    }
}

// ============================================================
// 7. NAVIGATION VERS CONVERSATION
// ============================================================
function ouvrirConversation(interlocuteurId) {
    if (interlocuteurId && interlocuteurId > 0) {
        window.location.href = '/gestion_scolaire/messages/' + interlocuteurId;
    }
}

// ============================================================
// 8. FORMULAIRE D'ENVOI
// ============================================================
function initFormulaireEnvoi() {
    var btnEnvoyer = document.getElementById('btnEnvoyerMessage');
    var rechercheInput = document.getElementById('rechercheUtilisateur');
    var typeRadios = document.querySelectorAll('input[name="destinataire_type"]');

    if (btnEnvoyer) {
        btnEnvoyer.addEventListener('click', envoyerMessage);
    }

    if (rechercheInput) {
        rechercheInput.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            var query = this.value.trim();
            if (query.length < 2) {
                var results = document.getElementById('resultatsUtilisateurs');
                if (results) {
                    results.innerHTML = '';
                    results.classList.remove('show');
                }
                return;
            }
            searchTimeout = setTimeout(function() {
                rechercherUtilisateurs(query);
            }, 300);
        });
    }

    if (typeRadios.length > 0) {
        typeRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                changerTypeDestinataire(this.value);
            });
        });
    }

    // Fermer les résultats de recherche au clic extérieur
    document.addEventListener('click', function(e) {
        var container = document.getElementById('resultatsUtilisateurs');
        var input = document.getElementById('rechercheUtilisateur');
        if (container && input && !container.contains(e.target) && e.target !== input) {
            container.classList.remove('show');
        }
    });
}

function changerTypeDestinataire(type) {
    var groupeRole = document.getElementById('groupeRole');
    var groupeClasse = document.getElementById('groupeClasse');
    var groupeUtilisateur = document.getElementById('groupeUtilisateur');
    var resumeDest = document.getElementById('resumeDestinataires');
    var resumeTexte = document.getElementById('resumeTexte');

    if (groupeRole) groupeRole.style.display = 'none';
    if (groupeClasse) groupeClasse.style.display = 'none';
    if (groupeUtilisateur) groupeUtilisateur.style.display = 'none';

    document.querySelectorAll('.destinataire-type-card').forEach(function(card) {
        card.classList.remove('active');
    });

    switch(type) {
        case 'tous':
            var cardTous = document.getElementById('cardTypeTous');
            if (cardTous) cardTous.classList.add('active');
            if (resumeDest) resumeDest.style.display = 'block';
            if (resumeTexte) resumeTexte.innerHTML = 'Message à : <strong>Tous les utilisateurs actifs</strong>';
            break;
        case 'role':
            var cardRole = document.getElementById('cardTypeRole');
            if (cardRole) cardRole.classList.add('active');
            if (groupeRole) groupeRole.style.display = 'block';
            if (resumeDest) resumeDest.style.display = 'none';
            break;
        case 'classe':
            var cardClasse = document.getElementById('cardTypeClasse');
            if (cardClasse) cardClasse.classList.add('active');
            if (groupeClasse) groupeClasse.style.display = 'block';
            if (resumeDest) resumeDest.style.display = 'none';
            break;
        case 'utilisateur':
            var cardUtilisateur = document.getElementById('cardTypeUtilisateur');
            if (cardUtilisateur) cardUtilisateur.classList.add('active');
            if (groupeUtilisateur) groupeUtilisateur.style.display = 'block';
            if (resumeDest) resumeDest.style.display = 'none';
            break;
    }
}

function rechercherUtilisateurs(query) {
    var formData = new FormData();
    formData.append('action', 'rechercherUtilisateurs');
    formData.append('recherche', query);

    fetch('/gestion_scolaire/messages', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var container = document.getElementById('resultatsUtilisateurs');
        if (!container) return;

        if (data.success && data.utilisateurs && data.utilisateurs.length > 0) {
            container.innerHTML = data.utilisateurs.map(function(u) {
                var avatarLettre = (u.prenom.charAt(0) + u.nom.charAt(0)).toUpperCase();
                return `
                <div class="resultat-eleve-item" onclick="selectionnerDestinataire(${u.id}, '${escapeHtml(u.prenom + ' ' + u.nom)}', '${escapeHtml(u.login)}', '${escapeHtml(u.role_nom)}')">
                    <div class="resultat-eleve-avatar">${avatarLettre}</div>
                    <div class="resultat-eleve-info">
                        <strong>${escapeHtml(u.prenom + ' ' + u.nom)}</strong>
                        <span>@${escapeHtml(u.login)} — ${escapeHtml(u.role_nom)}</span>
                    </div>
                </div>`;
            }).join('');
            container.classList.add('show');
        } else {
            container.innerHTML = '<p style="padding:14px;color:#94a3b8;text-align:center;font-size:0.85rem;">Aucun utilisateur trouvé.</p>';
            container.classList.add('show');
        }
    });
}

function selectionnerDestinataire(id, nom, login, role) {
    var destinataireId = document.getElementById('destinataire_id');
    var rechercheInput = document.getElementById('rechercheUtilisateur');
    var resultsContainer = document.getElementById('resultatsUtilisateurs');
    var resumeDest = document.getElementById('resumeDestinataires');
    var resumeTexte = document.getElementById('resumeTexte');

    if (destinataireId) destinataireId.value = id;
    if (rechercheInput) rechercheInput.value = nom + ' (@' + login + ')';
    if (resultsContainer) resultsContainer.classList.remove('show');
    if (resumeDest) resumeDest.style.display = 'block';
    if (resumeTexte) resumeTexte.innerHTML = 'Message à : <strong>' + escapeHtml(nom) + '</strong> (' + escapeHtml(role) + ')';
}

function envoyerMessage() {
    var sujet = document.getElementById('sujet')?.value?.trim() || '';
    var contenu = document.getElementById('contenu')?.value?.trim() || '';
    var destinataireType = document.querySelector('input[name="destinataire_type"]:checked')?.value || '';
    var formMessage = document.getElementById('formMessage');

    if (!sujet) {
        showToast('Veuillez saisir un sujet.', 'warning');
        var sujetInput = document.getElementById('sujet');
        if (sujetInput) sujetInput.focus();
        return;
    }
    if (!contenu) {
        showToast('Veuillez rédiger un message.', 'warning');
        var contenuInput = document.getElementById('contenu');
        if (contenuInput) contenuInput.focus();
        return;
    }
    if (!destinataireType) {
        showToast('Veuillez choisir un type de destinataire.', 'warning');
        return;
    }

    if (destinataireType === 'utilisateur') {
        var destId = document.getElementById('destinataire_id')?.value || '';
        if (!destId || parseInt(destId) <= 0) {
            showToast('Veuillez sélectionner un destinataire.', 'warning');
            return;
        }
    }

    if (destinataireType === 'classe') {
        var classeId = document.getElementById('classe_id')?.value || '';
        if (!classeId || parseInt(classeId) <= 0) {
            showToast('Veuillez sélectionner une classe.', 'warning');
            return;
        }
    }

    if (destinataireType === 'role') {
        var roleDest = document.getElementById('role_destinataire')?.value || '';
        if (!roleDest) {
            showToast('Veuillez sélectionner un rôle.', 'warning');
            return;
        }
    }

    var formData = new FormData(formMessage);
    formData.append('action', 'envoyer');

    var btn = document.getElementById('btnEnvoyerMessage');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Envoi en cours...';
    }

    fetch('/gestion_scolaire/messages', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(function() {
                window.location.href = '/gestion_scolaire/messages';
            }, 1500);
        } else {
            showToast(data.message, 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer le message';
            }
        }
    })
    .catch(function() {
        showToast('Erreur de connexion.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer le message';
        }
    });
}

// ============================================================
// 9. CONVERSATION
// ============================================================
function initConversation() {
    scrollToBottom();
    initReponseRapide();
    initRefreshConversation();
}

function scrollToBottom() {
    var end = document.getElementById('conversationEnd');
    if (end) {
        setTimeout(function() {
            end.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 100);
    }
}

function initReponseRapide() {
    var textarea = document.getElementById('reponseContenu');
    var btnRepondre = document.getElementById('btnRepondre');

    if (textarea) {
        textarea.focus();
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                envoyerReponse();
            }
        });
    }

    if (btnRepondre) {
        btnRepondre.addEventListener('click', envoyerReponse);
    }
}

function envoyerReponse() {
    var contenu = document.getElementById('reponseContenu')?.value?.trim();
    if (!contenu) return;

    var formReponse = document.getElementById('formReponse');
    var formData = new FormData(formReponse);
    formData.append('action', 'envoyer');
    formData.append('sujet', 'Re: Conversation');

    var btn = document.getElementById('btnRepondre');
    var textarea = document.getElementById('reponseContenu');

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }
    if (textarea) textarea.disabled = true;

    fetch('/gestion_scolaire/messages', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message || 'Erreur d\'envoi.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer';
            }
            if (textarea) textarea.disabled = false;
        }
    })
    .catch(function() {
        showToast('Erreur de connexion.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer';
        }
        if (textarea) textarea.disabled = false;
    });
}

function initRefreshConversation() {
    var totalMessages = document.querySelectorAll('.message-bulle-wrapper').length;

    refreshInterval = setInterval(function() {
        var interlocuteurId = document.querySelector('input[name="destinataire_id"]')?.value;
        if (!interlocuteurId) return;

        var formData = new FormData();
        formData.append('action', 'getConversation');
        formData.append('interlocuteur_id', interlocuteurId);
        formData.append('page', 1);

        fetch('/gestion_scolaire/messages', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.total > totalMessages) {
                location.reload();
            }
        })
        .catch(function() {
            // Silencieux
        });
    }, 30000);
}

// ============================================================
// 10. COMPTEUR NAVBAR
// ============================================================
function updateCompteurNavbar() {
    var formData = new FormData();
    formData.append('action', 'getCount');

    fetch('/gestion_scolaire/messages', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var badge = document.querySelector('.navbar-icon-btn .badge-count');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    })
    .catch(function() {
        // Silencieux
    });
}

// ============================================================
// 11. RACCOURCIS CLAVIER GLOBAUX
// ============================================================
function initRaccourcisClavier() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+N : Nouveau message
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            window.location.href = '/gestion_scolaire/messages/envoyer';
        }

        // Ctrl+R : Actualiser la boîte
        if (e.ctrlKey && e.key === 'r' && document.getElementById('messagesContainer')) {
            e.preventDefault();
            chargerMessages(1);
            updateCompteurNavbar();
        }

        // Échap : Fermer l'aperçu
        if (e.key === 'Escape') {
            fermerApercu();
        }
    });
}

// ============================================================
// 12. ACTUALISATION AUTOMATIQUE
// ============================================================
function initRefreshAuto() {
    // Actualiser toutes les 2 minutes si sur la page boîte
    if (document.getElementById('messagesContainer')) {
        refreshInterval = setInterval(function() {
            chargerMessages(1);
            updateCompteurNavbar();
        }, 120000);
    }
}

// Nettoyer l'intervalle en quittant la page
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// ============================================================
// 13. UTILITAIRES// ============================================================
function tronquerTexte(texte, longueur) {
    if (!texte) return '';
    if (texte.length <= longueur) return escapeHtml(texte);
    return escapeHtml(texte.substring(0, longueur)) + '...';
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}