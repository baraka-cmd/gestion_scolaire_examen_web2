<?php
/**
 * Fichier : public/views/modules/messages/conversation.php
 * Rôle    : Fil de discussion entre deux utilisateurs (Style WhatsApp)
 * Système : École primaire congolaise — Communication
 * Version : 2.0 — Adapté aux conversations groupées
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

// Récupérer l'interlocuteur depuis l'URL
$interlocuteurId = (int) ($_GET['id'] ?? 0);
if ($interlocuteurId <= 0) {
    setFlash('error', 'ID interlocuteur invalide.');
    redirect('/gestion_scolaire/messages');
}

$userId = $_SESSION['utilisateur_id'];

// Empêcher de discuter avec soi-même
if ($interlocuteurId === $userId) {
    setFlash('warning', 'Vous ne pouvez pas discuter avec vous-même.');
    redirect('/gestion_scolaire/messages');
}

// Récupérer les infos de l'interlocuteur
$interlocuteur = $db->fetchOne(
    "SELECT u.id, u.nom, u.prenom, u.login, u.photo, r.nom AS role_nom
     FROM utilisateurs u
     INNER JOIN roles r ON u.role_id = r.id
     WHERE u.id = :id AND u.est_actif = 1",
    ['id' => $interlocuteurId]
);

if (!$interlocuteur) {
    setFlash('error', 'Interlocuteur introuvable ou compte désactivé.');
    redirect('/gestion_scolaire/messages');
}

// Récupérer la conversation complète (tous les messages entre les deux)
$messages = $db->fetchAll(
    "SELECT m.*, 
            DATE_FORMAT(m.date_envoi, '%d/%m/%Y') AS date_format,
            TIME_FORMAT(m.date_envoi, '%H:%i') AS heure_format,
            CONCAT(exp.prenom, ' ', exp.nom) AS expediteur_nom,
            exp.login AS expediteur_login,
            CONCAT(dest.prenom, ' ', dest.nom) AS destinataire_nom,
            dest.login AS destinataire_login
     FROM messages m
     INNER JOIN utilisateurs exp ON m.expediteur_id = exp.id
     INNER JOIN utilisateurs dest ON m.destinataire_id = dest.id
     WHERE ((m.expediteur_id = :uid1 AND m.destinataire_id = :uid2 AND m.est_supprime_expediteur = 0)
         OR (m.expediteur_id = :uid3 AND m.destinataire_id = :uid4 AND m.est_supprime_destinataire = 0))
     ORDER BY m.date_envoi ASC",
    ['uid1' => $userId, 'uid2' => $interlocuteurId, 'uid3' => $interlocuteurId, 'uid4' => $userId]
);

// Marquer comme lus les messages reçus de cet interlocuteur
$db->update(
    "UPDATE messages SET est_lu = 1, date_lecture = NOW() 
     WHERE expediteur_id = :exp AND destinataire_id = :dest AND est_lu = 0",
    ['exp' => $interlocuteurId, 'dest' => $userId]
);

// Nombre total de messages
$totalMessages = count($messages);

// Initiales pour l'avatar
$initiales = mb_strtoupper(mb_substr($interlocuteur['prenom'], 0, 1) . mb_substr($interlocuteur['nom'], 0, 1));

$pageTitle = 'Conversation — ' . e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']);
$pageIcon  = 'fa-comments';
$pageDescription = 'Discussion avec ' . e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) . ' (' . $interlocuteur['role_nom'] . ')';
$extraCSS = ['modules/messages.css'];
$extraJS  = ['modules/messages.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/messages">Messagerie</a>
    <span class="separator">/</span>
    <span class="current"><?= e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) ?></span>
</nav>

<!-- ============================================================
     EN-TÊTE DE LA CONVERSATION
     ============================================================ -->
<div class="conversation-header-card">
    <div class="conversation-header-left">
        <a href="/gestion_scolaire/messages" class="btn btn-outline-primary btn-sm conversation-back-btn" title="Retour à la boîte de réception">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="conversation-avatar" onclick="afficherInfosInterlocuteur()" title="Voir les informations">
            <?= $initiales ?>
        </div>
        <div class="conversation-header-info">
            <h5><?= e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) ?></h5>
            <p>
                <span class="badge badge-primary">@<?= e($interlocuteur['login']) ?></span>
                <span class="badge badge-info ms-2"><?= e($interlocuteur['role_nom']) ?></span>
                <span class="ms-2" style="font-size:0.75rem;color:var(--color-gray-400);">
                    <i class="fa-solid fa-envelope"></i> <?= $totalMessages ?> message(s)
                </span>
            </p>
        </div>
    </div>
    <div class="conversation-header-right">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()" title="Imprimer la conversation">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="supprimerConversationComplete(<?= $interlocuteurId ?>, '<?= e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) ?>')" title="Supprimer toute la conversation">
            <i class="fa-solid fa-trash"></i> Supprimer
        </button>
    </div>
</div>

<!-- ============================================================
     FIL DE DISCUSSION
     ============================================================ -->
<div class="conversation-container" id="conversationContainer">
    <?php if (empty($messages)): ?>
        <div class="conversation-empty">
            <div class="conversation-empty-icon">
                <i class="fa-solid fa-comments"></i>
            </div>
            <h5>Aucun message</h5>
            <p>Commencez la conversation en envoyant un premier message à <strong><?= e($interlocuteur['prenom']) ?></strong>.</p>
        </div>
    <?php else: ?>
        <?php 
        $datePrecedente = '';
        foreach ($messages as $index => $message): 
            $estMien = (int) $message['expediteur_id'] === $userId;
            $dateMessage = $message['date_format'];
            
            // Déterminer si on affiche un séparateur de date
            $afficherSeparateur = ($dateMessage !== $datePrecedente);
            if ($afficherSeparateur) {
                $datePrecedente = $dateMessage;
            }
            
            // Déterminer si le message est le premier d'un groupe (pour le style)
            $messagePrecedent = $index > 0 ? $messages[$index - 1] : null;
            $messageSuivant = $index < count($messages) - 1 ? $messages[$index + 1] : null;
            
            $memeExpediteurQuePrecedent = $messagePrecedent && (int) $messagePrecedent['expediteur_id'] === (int) $message['expediteur_id'];
            $memeExpediteurQueSuivant = $messageSuivant && (int) $messageSuivant['expediteur_id'] === (int) $message['expediteur_id'];
            
            // Style de la bulle
            $bulleClass = $estMien ? 'moi' : 'autre';
            $bulleClass .= $memeExpediteurQueSuivant ? ' groupe' : '';
            $bulleClass .= !$memeExpediteurQuePrecedent ? ' premier' : '';
        ?>
            <?php if ($afficherSeparateur): ?>
                <div class="conversation-date-separator">
                    <span><?= $dateMessage ?></span>
                </div>
            <?php endif; ?>

            <div class="message-bulle-wrapper <?= $estMien ? 'moi' : 'autre' ?>">
                <?php if (!$estMien && !$memeExpediteurQuePrecedent): ?>
                    <div class="message-bulle-avatar">
                        <?= mb_strtoupper(mb_substr($message['expediteur_nom'], 0, 1)) ?>
                    </div>
                <?php elseif (!$estMien): ?>
                    <div class="message-bulle-avatar-spacer"></div>
                <?php endif; ?>

                <div class="message-bulle <?= $bulleClass ?>">
                    <?php if (!$estMien && !$memeExpediteurQuePrecedent): ?>
                        <div class="message-bulle-expediteur"><?= e($message['expediteur_nom']) ?></div>
                    <?php endif; ?>
                    
                    <div class="message-bulle-contenu">
                        <?php if ($message['sujet'] && !str_starts_with($message['sujet'], 'Re:')): ?>
                            <div class="message-bulle-sujet"><?= e($message['sujet']) ?></div>
                        <?php endif; ?>
                        <p><?= nl2br(e($message['contenu'])) ?></p>
                    </div>
                    
                    <div class="message-bulle-meta">
                        <span class="message-bulle-heure"><?= e($message['heure_format']) ?></span>
                        <?php if ($estMien): ?>
                            <span class="message-bulle-statut" title="<?= $message['est_lu'] ? 'Lu' : 'Envoyé' ?>">
                                <?php if ($message['est_lu']): ?>
                                    <i class="fa-solid fa-check-double" style="color:#3b82f6;"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-check" style="color:#94a3b8;"></i>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($estMien): ?>
                    <div class="message-bulle-actions">
                        <button type="button" class="btn-icon btn-icon-sm btn-icon-danger" 
                                onclick="supprimerMessageUnique(<?= $message['id'] ?>)" 
                                title="Supprimer ce message">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Ancre de fin pour le scroll automatique -->
    <div id="conversationEnd"></div>
</div>

<!-- ============================================================
     FORMULAIRE DE RÉPONSE RAPIDE
     ============================================================ -->
<div class="conversation-reponse">
    <form id="formReponse" autocomplete="off">
        <input type="hidden" name="destinataire_type" value="utilisateur">
        <input type="hidden" name="destinataire_id" value="<?= $interlocuteurId ?>">
        
        <div class="reponse-input-wrapper">
            <textarea id="reponseContenu" name="contenu" class="form-control reponse-textarea" 
                      rows="2" 
                      placeholder="Écrivez votre message... (Entrée pour envoyer, Shift+Entrée pour nouvelle ligne)"
                      onkeydown="gererEnvoiRapide(event)"></textarea>
            
            <div class="reponse-actions">
                <small class="reponse-shortcut">
                    <kbd>Entrée</kbd> pour envoyer · <kbd>Shift</kbd>+<kbd>Entrée</kbd> nouvelle ligne
                </small>
                <button type="button" class="btn btn-primary" id="btnRepondre">
                    <i class="fa-solid fa-paper-plane"></i> Envoyer
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
// ============================================================
// VARIABLES
// ============================================================
var envoiEnCours = false;
var rafraichissementAuto = null;

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Scroll en bas de la conversation
    scrollToBottom();
    
    // Focus sur le champ de réponse
    var textarea = document.getElementById('reponseContenu');
    if (textarea) {
        textarea.focus();
    }
    
    // Démarrer le rafraîchissement automatique
    demarrerRafraichissementAuto();
});

// ============================================================
// SCROLL AUTOMATIQUE
// ============================================================
function scrollToBottom() {
    var fin = document.getElementById('conversationEnd');
    if (fin) {
        setTimeout(function() {
            fin.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 150);
    }
}

// ============================================================
// ENVOI RAPIDE (TOUCHE ENTRÉE)
// ============================================================
function gererEnvoiRapide(evenement) {
    // Shift+Entrée = nouvelle ligne (comportement normal du textarea)
    if (evenement.key === 'Enter' && evenement.shiftKey) {
        return true;
    }
    
    // Entrée seule = envoyer le message
    if (evenement.key === 'Enter' && !envoiEnCours) {
        evenement.preventDefault();
        envoyerReponse();
    }
}

// ============================================================
// ENVOI DE LA RÉPONSE
// ============================================================
function envoyerReponse() {
    // Empêcher le double envoi
    if (envoiEnCours) return;
    
    var contenu = document.getElementById('reponseContenu')?.value?.trim();
    if (!contenu) return;
    
    envoiEnCours = true;
    
    var formulaire = document.getElementById('formReponse');
    var formData = new FormData(formulaire);
    formData.append('action', 'envoyer');
    formData.append('sujet', '');
    
    var bouton = document.getElementById('btnRepondre');
    var textarea = document.getElementById('reponseContenu');
    
    if (bouton) {
        bouton.disabled = true;
        bouton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }
    if (textarea) {
        textarea.disabled = true;
    }
    
    fetch('/gestion_scolaire/messages', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(reponse) { return reponse.json(); })
    .then(function(donnees) {
        if (donnees.success) {
            // Recharger la page pour voir le nouveau message
            location.reload();
        } else {
            showToast(donnees.message || 'Erreur lors de l\'envoi.', 'error');
            envoiEnCours = false;
            if (bouton) {
                bouton.disabled = false;
                bouton.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer';
            }
            if (textarea) {
                textarea.disabled = false;
                textarea.focus();
            }
        }
    })
    .catch(function() {
        showToast('Erreur de connexion au serveur.', 'error');
        envoiEnCours = false;
        if (bouton) {
            bouton.disabled = false;
            bouton.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer';
        }
        if (textarea) {
            textarea.disabled = false;
            textarea.focus();
        }
    });
}

// ============================================================
// BOUTON ENVOYER (CLIC)
// ============================================================
document.getElementById('btnRepondre')?.addEventListener('click', function() {
    envoyerReponse();
});

// ============================================================
// SUPPRIMER UN MESSAGE UNIQUE
// ============================================================
function supprimerMessageUnique(messageId) {
    showConfirmDialog(
        'Voulez-vous supprimer ce message ?<br><small>Il disparaîtra uniquement de votre vue.</small>',
        function(confirme) {
            if (confirme) {
                var formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('message_id', messageId);
                
                fetch('/gestion_scolaire/messages', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(reponse) { return reponse.json(); })
                .then(function(donnees) {
                    if (donnees.success) {
                        showToast('Message supprimé.', 'success');
                        location.reload();
                    } else {
                        showToast(donnees.message || 'Erreur.', 'error');
                    }
                })
                .catch(function() {
                    showToast('Erreur de connexion.', 'error');
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

// ============================================================
// SUPPRIMER TOUTE LA CONVERSATION
// ============================================================
function supprimerConversationComplete(interlocuteurId, nom) {
    showConfirmDialog(
        'Voulez-vous supprimer <strong>toute</strong> la conversation avec <strong>' + escapeHtml(nom) + '</strong> ?<br><small>Cette action est irréversible.</small>',
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
                        showToast('Conversation supprimée.', 'success');
                        setTimeout(function() {
                            window.location.href = '/gestion_scolaire/messages';
                        }, 800);
                    } else {
                        showToast(donnees.message || 'Erreur.', 'error');
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

// ============================================================
// RAFRAÎCHISSEMENT AUTOMATIQUE (toutes les 30 secondes)
// ============================================================
function demarrerRafraichissementAuto() {
    var totalMessagesActuels = <?= $totalMessages ?>;
    
    rafraichissementAuto = setInterval(function() {
        var formData = new FormData();
        formData.append('action', 'getConversation');
        formData.append('interlocuteur_id', <?= $interlocuteurId ?>);
        formData.append('page', 1);
        
        fetch('/gestion_scolaire/messages', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(reponse) { return reponse.json(); })
        .then(function(donnees) {
            if (donnees.success && donnees.total > totalMessagesActuels) {
                // De nouveaux messages sont arrivés, recharger la page
                location.reload();
            }
        })
        .catch(function() {
            // Silencieux — ne pas déranger l'utilisateur
        });
    }, 30000); // 30 secondes
}

// Arrêter le rafraîchissement en quittant la page
window.addEventListener('beforeunload', function() {
    if (rafraichissementAuto) {
        clearInterval(rafraichissementAuto);
        rafraichissementAuto = null;
    }
});

// ============================================================
// RACCOURCIS CLAVIER
// ============================================================
document.addEventListener('keydown', function(e) {
    // Échap = retour à la boîte de réception
    if (e.key === 'Escape' && !document.getElementById('reponseContenu')?.matches(':focus')) {
        window.location.href = '/gestion_scolaire/messages';
    }
});

// ============================================================
// AFFICHER LES INFOS DE L'INTERLOCUTEUR
// ============================================================
function afficherInfosInterlocuteur() {
    showToast(
        '<strong><?= e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) ?></strong><br>' +
        '@<?= e($interlocuteur['login']) ?> — <?= e($interlocuteur['role_nom']) ?>',
        'info',
        4000
    );
}

// ============================================================
// UTILITAIRES
// ============================================================
function escapeHtml(texte) {
    if (!texte) return '';
    var div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>