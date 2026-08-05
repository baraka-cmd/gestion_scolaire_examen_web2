<?php
/**
 * Fichier : public/views/modules/messages/boite.php
 * Rôle    : Boîte de réception — Messagerie interne (Style WhatsApp)
 * Système : École primaire congolaise — Communication
 * Version : 2.0 — Regroupement par interlocuteur
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('messages.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'MessageController.php';
$messageController = new MessageController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $messageController->handleAjax();
}

// Compteurs
$userId = $_SESSION['utilisateur_id'];
$nbNonLus = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM messages WHERE destinataire_id = :uid AND est_lu = 0 AND est_supprime_destinataire = 0 AND est_archive_destinataire = 0",
    ['uid' => $userId]
);
$nbRecus = (int) $db->fetchColumn(
    "SELECT COUNT(DISTINCT expediteur_id) FROM messages WHERE destinataire_id = :uid AND est_supprime_destinataire = 0 AND est_archive_destinataire = 0",
    ['uid' => $userId]
);
$nbEnvoyes = (int) $db->fetchColumn(
    "SELECT COUNT(DISTINCT destinataire_id) FROM messages WHERE expediteur_id = :uid AND est_supprime_expediteur = 0",
    ['uid' => $userId]
);
$nbArchives = (int) $db->fetchColumn(
    "SELECT COUNT(DISTINCT expediteur_id) FROM messages WHERE destinataire_id = :uid AND est_archive_destinataire = 1 AND est_supprime_destinataire = 0",
    ['uid' => $userId]
);

$pageTitle = 'Messagerie';
$pageIcon  = 'fa-envelope';
$pageDescription = 'Boîte de réception — ' . $nbNonLus . ' message(s) non lu(s)';
$extraCSS = ['modules/messages.css'];
$extraJS  = ['modules/messages.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Messagerie</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-envelope"></i> Messagerie interne</h4>
        <p>Gérez vos conversations et communications avec les autres utilisateurs.</p>
    </div>
    <div class="resultats-header-right">
        <?php if (can('messages.envoyer')): ?>
            <a href="/gestion_scolaire/messages/envoyer" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-pen-to-square"></i> Nouveau message
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnActualiser" title="Actualiser les conversations">
            <i class="fa-solid fa-rotate"></i>
        </button>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     ONGLETS : RÉCEPTION / ENVOYÉS / ARCHIVES
     ============================================================ -->
<div class="messages-tabs">
    <button type="button" class="message-tab active" data-tab="recus" onclick="changerOnglet('recus', this)">
        <i class="fa-solid fa-inbox"></i>
        <span>Boîte de réception</span>
        <?php if ($nbNonLus > 0): ?>
            <span class="tab-badge"><?= $nbNonLus ?></span>
        <?php endif; ?>
    </button>
    <button type="button" class="message-tab" data-tab="envoyes" onclick="changerOnglet('envoyes', this)">
        <i class="fa-solid fa-paper-plane"></i>
        <span>Messages envoyés</span>
        <?php if ($nbEnvoyes > 0): ?>
            <span class="tab-badge tab-badge-light"><?= $nbEnvoyes ?></span>
        <?php endif; ?>
    </button>
    <button type="button" class="message-tab" data-tab="archives" onclick="changerOnglet('archives', this)">
        <i class="fa-solid fa-box-archive"></i>
        <span>Archives</span>
        <?php if ($nbArchives > 0): ?>
            <span class="tab-badge tab-badge-light"><?= $nbArchives ?></span>
        <?php endif; ?>
    </button>
</div>

<!-- ============================================================
     BARRE DE RECHERCHE ET FILTRES
     ============================================================ -->
<div class="messages-filters">
    <div class="filter-group" style="flex:1;max-width:450px;">
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchMessages" class="search-input" 
                   placeholder="Rechercher par nom, prénom ou contenu du message..." 
                   onkeyup="debounceSearch()">
        </div>
    </div>
    <div class="filter-group">
        <select id="filterLu" class="form-control" onchange="appliquerFiltres()">
            <option value="">Toutes les conversations</option>
            <option value="0">Avec messages non lus</option>
            <option value="1">Tout lu</option>
        </select>
    </div>
    <div class="filter-group">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="marquerToutLu()" id="btnToutLu" <?= $nbNonLus === 0 ? 'disabled' : '' ?>>
            <i class="fa-solid fa-check-double"></i> Tout marquer comme lu
        </button>
    </div>
</div>

<!-- ============================================================
     LISTE DES CONVERSATIONS (STYLE WHATSAPP)
     ============================================================ -->
<div class="messages-container" id="messagesContainer">
    <!-- État de chargement -->
    <div class="messages-loading" id="messagesLoading">
        <i class="fa-solid fa-spinner fa-spin"></i>
        <p>Chargement des conversations...</p>
    </div>

    <!-- Liste des conversations -->
    <div class="messages-list" id="messagesList"></div>

    <!-- État vide -->
    <div class="messages-empty" id="messagesEmpty" style="display:none;">
        <i class="fa-solid fa-envelope-open"></i>
        <h5>Aucune conversation</h5>
        <p id="messagesEmptyText">Votre boîte de réception est vide.</p>
        <?php if (can('messages.envoyer')): ?>
            <a href="/gestion_scolaire/messages/envoyer" class="btn btn-primary btn-sm mt-3">
                <i class="fa-solid fa-pen-to-square"></i> Envoyer un message
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     PAGINATION
     ============================================================ -->
<div class="pagination-wrapper" id="paginationWrapper" style="display:none;">
    <div class="pagination-info" id="paginationInfo"></div>
    <div class="pagination" id="paginationMessages"></div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
// ============================================================
// VARIABLES GLOBALES
// ============================================================
var ongletActif = 'recus';
var searchTimeout;
var rafraichissementAuto = null;

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    chargerConversations(1);
    demarrerRafraichissementAuto();
});

// ============================================================
// CHANGEMENT D'ONGLET
// ============================================================
function changerOnglet(type, bouton) {
    ongletActif = type;
    
    // Mettre à jour les classes actives
    document.querySelectorAll('.message-tab').forEach(function(tab) {
        tab.classList.remove('active');
    });
    bouton.classList.add('active');
    
    // Adapter le placeholder de recherche
    var champRecherche = document.getElementById('searchMessages');
    if (champRecherche) {
        champRecherche.placeholder = 'Rechercher par nom, prénom ou contenu du message...';
    }
    
    // Adapter le message vide
    var texteVide = document.getElementById('messagesEmptyText');
    if (texteVide) {
        switch(type) {
            case 'recus':
                texteVide.textContent = 'Votre boîte de réception est vide.';
                break;
            case 'envoyes':
                texteVide.textContent = 'Aucun message envoyé.';
                break;
            case 'archives':
                texteVide.textContent = 'Aucune conversation archivée.';
                break;
        }
    }
    
    // Recharger les conversations
    chargerConversations(1);
}

// ============================================================
// CHARGEMENT DES CONVERSATIONS GROUPÉES
// ============================================================
function chargerConversations(page) {
    var conteneur = document.getElementById('messagesList');
    var chargement = document.getElementById('messagesLoading');
    var vide = document.getElementById('messagesEmpty');
    
    // Afficher le loader
    conteneur.innerHTML = '';
    chargement.style.display = 'flex';
    vide.style.display = 'none';

    // Préparer les données
    var formData = new FormData();
    formData.append('action', 'getConversationsGroupees');
    formData.append('page', page || 1);
    formData.append('type', ongletActif);
    
    var filtreLu = document.getElementById('filterLu');
    var champRecherche = document.getElementById('searchMessages');
    
    if (filtreLu && filtreLu.value !== '') {
        formData.append('lu', filtreLu.value);
    }
    if (champRecherche && champRecherche.value.trim() !== '') {
        formData.append('recherche', champRecherche.value.trim());
    }

    // Appel AJAX
    fetch('/gestion_scolaire/messages', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(reponse) { return reponse.json(); })
    .then(function(donnees) {
        chargement.style.display = 'none';
        if (donnees.success) {
            afficherConversations(donnees);
        } else {
            showToast(donnees.message || 'Erreur de chargement.', 'error');
        }
    })
    .catch(function() {
        chargement.style.display = 'none';
        showToast('Erreur de connexion au serveur.', 'error');
    });
}

// ============================================================
// AFFICHAGE DES CONVERSATIONS
// ============================================================
function afficherConversations(donnees) {
    var conteneur = document.getElementById('messagesList');
    var vide = document.getElementById('messagesEmpty');
    var paginationWrapper = document.getElementById('paginationWrapper');

    // Aucune conversation
    if (!donnees.conversations || donnees.conversations.length === 0) {
        conteneur.innerHTML = '';
        vide.style.display = 'flex';
        paginationWrapper.style.display = 'none';
        return;
    }

    vide.style.display = 'none';

    // Construire la liste des conversations
    conteneur.innerHTML = donnees.conversations.map(function(conv) {
        var nomAffiche = ongletActif === 'envoyes' ? conv.destinataire_nom : conv.expediteur_nom;
        var roleAffiche = ongletActif === 'envoyes' ? conv.destinataire_role : conv.expediteur_role;
        var interlocuteurId = conv.interlocuteur_id; // Clé uniforme retournée par SQL
        var initiales = (nomAffiche || '?').charAt(0).toUpperCase();
        var aDesNonLus = parseInt(conv.nb_non_lus) > 0;
        var dateDernier = conv.dernier_message_date || '';
        var heureDernier = conv.dernier_message_heure || '';
        var dernierMessage = conv.dernier_message || '';

        return `
        <div class="conversation-item ${aDesNonLus ? 'non-lue' : ''}" 
             data-interlocuteur="${interlocuteurId}"
             data-nom="${escapeHtml(nomAffiche)}"
             onclick="ouvrirConversation(${interlocuteurId})"
             title="Voir la conversation avec ${escapeHtml(nomAffiche)}">
            
            <!-- Avatar -->
            <div class="conversation-item-avatar">
                <div class="conversation-avatar-placeholder" style="background:linear-gradient(135deg, #2c3e90, #4a6cf7);">
                    ${initiales}
                </div>
                ${aDesNonLus ? '<span class="conversation-badge-non-lu">' + conv.nb_non_lus + '</span>' : ''}
            </div>
            
            <!-- Contenu -->
            <div class="conversation-item-content">
                <div class="conversation-item-header">
                    <span class="conversation-item-nom">${escapeHtml(nomAffiche)}</span>
                    ${roleAffiche ? '<span class="conversation-item-role">' + escapeHtml(roleAffiche) + '</span>' : ''}
                </div>
                <div class="conversation-item-dernier-message">
                    <span class="dernier-message-texte ${aDesNonLus ? 'non-lu' : ''}">${escapeHtml(dernierMessage)}</span>
                </div>
            </div>
            
            <!-- Méta (date + heure) -->
            <div class="conversation-item-meta">
                <span class="conversation-item-date">${dateDernier}</span>
                <span class="conversation-item-heure">${heureDernier}</span>
            </div>
            
            <!-- Actions -->
            <div class="conversation-item-actions" onclick="event.stopPropagation();">
                ${ongletActif !== 'archives' ? `
                <button type="button" class="btn-icon btn-icon-sm" onclick="archiverConversation(${interlocuteurId})" title="Archiver la conversation">
                    <i class="fa-solid fa-box-archive"></i>
                </button>
                ` : `
                <button type="button" class="btn-icon btn-icon-sm" onclick="desarchiverConversation(${interlocuteurId})" title="Désarchiver la conversation">
                    <i class="fa-solid fa-inbox"></i>
                </button>
                `}
                <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" onclick="supprimerConversation(${interlocuteurId}, '${escapeHtml(nomAffiche)}')" title="Supprimer la conversation">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');

    // Pagination
    if (donnees.pages > 1) {
        paginationWrapper.style.display = 'flex';
        var paginationHtml = '';
        for (var i = 1; i <= donnees.pages; i++) {
            paginationHtml += '<a href="#" onclick="chargerConversations(' + i + '); return false;" class="' + (i === donnees.page ? 'active' : '') + '">' + i + '</a>';
        }
        document.getElementById('paginationMessages').innerHTML = paginationHtml;
        document.getElementById('paginationInfo').textContent = 'Page ' + donnees.page + ' / ' + donnees.pages + ' — ' + donnees.total + ' conversation(s)';
    } else {
        paginationWrapper.style.display = 'none';
    }
}

// ============================================================
// ACTIONS SUR LES CONVERSATIONS
// ============================================================

/**
 * Ouvre la conversation avec un interlocuteur
 */
function ouvrirConversation(interlocuteurId) {
    if (interlocuteurId && interlocuteurId > 0) {
        window.location.href = '/gestion_scolaire/messages/' + interlocuteurId;
    }
}

/**
 * Archive une conversation entière
 */
function archiverConversation(interlocuteurId) {
    var formData = new FormData();
    formData.append('action', 'archiverConversation');
    formData.append('interlocuteur_id', interlocuteurId);

    fetch('/gestion_scolaire/messages', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(reponse) { return reponse.json(); })
    .then(function(donnees) {
        if (donnees.success) {
            showToast('Conversation archivée avec succès.', 'success');
            chargerConversations(1);
        } else {
            showToast(donnees.message || 'Erreur lors de l\'archivage.', 'error');
        }
    })
    .catch(function() {
        showToast('Erreur de connexion.', 'error');
    });
}

/**
 * Désarchive une conversation
 */
function desarchiverConversation(interlocuteurId) {
    var formData = new FormData();
    formData.append('action', 'desarchiverConversation');
    formData.append('interlocuteur_id', interlocuteurId);

    fetch('/gestion_scolaire/messages', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(reponse) { return reponse.json(); })
    .then(function(donnees) {
        if (donnees.success) {
            showToast('Conversation désarchivée avec succès.', 'success');
            chargerConversations(1);
        } else {
            showToast(donnees.message || 'Erreur lors du désarchivage.', 'error');
        }
    })
    .catch(function() {
        showToast('Erreur de connexion.', 'error');
    });
}

/**
 * Supprime une conversation entière
 */
function supprimerConversation(interlocuteurId, nom) {
    showConfirmDialog(
        'Voulez-vous supprimer toute la conversation avec <strong>' + escapeHtml(nom) + '</strong> ?<br><small>Cette action est irréversible.</small>',
        function(confirme) {
            if (confirme) {
                var formData = new FormData();
                formData.append('action', 'supprimerConversation');
                formData.append('interlocuteur_id', interlocuteurId);

                fetch('/gestion_scolaire/messages', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(reponse) { return reponse.json(); })
                .then(function(donnees) {
                    if (donnees.success) {
                        showToast('Conversation supprimée avec succès.', 'success');
                        chargerConversations(1);
                    } else {
                        showToast(donnees.message || 'Erreur lors de la suppression.', 'error');
                    }
                })
                .catch(function() {
                    showToast('Erreur de connexion.', 'error');
                });
            }
        },
        { 
            title: 'Supprimer la conversation', 
            confirmText: 'Supprimer', 
            confirmClass: 'btn-danger', 
            icon: 'fa-trash', 
            iconColor: '#ef4444' 
        }
    );
}

/**
 * Marque tous les messages comme lus
 */
function marquerToutLu() {
    var formData = new FormData();
    formData.append('action', 'marquerToutLu');

    var bouton = document.getElementById('btnToutLu');
    if (bouton) {
        bouton.disabled = true;
        bouton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> En cours...';
    }

    fetch('/gestion_scolaire/messages', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(reponse) { return reponse.json(); })
    .then(function(donnees) {
        if (donnees.success) {
            showToast(donnees.message || 'Tous les messages sont marqués comme lus.', 'success');
            chargerConversations(1);
        } else {
            showToast(donnees.message || 'Erreur.', 'error');
            if (bouton) {
                bouton.disabled = false;
                bouton.innerHTML = '<i class="fa-solid fa-check-double"></i> Tout marquer comme lu';
            }
        }
    })
    .catch(function() {
        showToast('Erreur de connexion.', 'error');
        if (bouton) {
            bouton.disabled = false;
            bouton.innerHTML = '<i class="fa-solid fa-check-double"></i> Tout marquer comme lu';
        }
    });
}

// ============================================================
// FILTRES ET RECHERCHE
// ============================================================

/**
 * Applique les filtres et recharge
 */
function appliquerFiltres() {
    chargerConversations(1);
}

/**
 * Recherche avec debounce (attend 400ms après la dernière frappe)
 */
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        chargerConversations(1);
    }, 400);
}

// ============================================================
// RAFRAÎCHISSEMENT AUTOMATIQUE
// ============================================================

/**
 * Démarre le rafraîchissement automatique toutes les 2 minutes
 */
function demarrerRafraichissementAuto() {
    rafraichissementAuto = setInterval(function() {
        chargerConversations(1);
    }, 120000); // 120 secondes = 2 minutes
}

/**
 * Arrête le rafraîchissement automatique (appelé avant de quitter la page)
 */
window.addEventListener('beforeunload', function() {
    if (rafraichissementAuto) {
        clearInterval(rafraichissementAuto);
        rafraichissementAuto = null;
    }
});

// ============================================================
// ACTUALISATION MANUELLE
// ============================================================
document.getElementById('btnActualiser')?.addEventListener('click', function() {
    chargerConversations(1);
    showToast('Conversations actualisées.', 'info', 1500);
});

// ============================================================
// RACCOURCIS CLAVIER
// ============================================================
document.addEventListener('keydown', function(e) {
    // Ctrl+N : Nouveau message
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        window.location.href = '/gestion_scolaire/messages/envoyer';
    }

    // Ctrl+R : Actualiser (sur la page boîte uniquement)
    if (e.ctrlKey && e.key === 'r' && document.getElementById('messagesContainer')) {
        e.preventDefault();
        chargerConversations(1);
    }
});

// ============================================================
// UTILITAIRES
// ============================================================

/**
 * Échappe les caractères HTML pour éviter les failles XSS
 */
function escapeHtml(texte) {
    if (!texte) return '';
    var div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>