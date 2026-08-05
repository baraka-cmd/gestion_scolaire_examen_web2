<?php
/**
 * Fichier : public/views/modules/messages/envoyer.php
 * Rôle    : Formulaire d'envoi de message — Messagerie interne (Style WhatsApp)
 * Système : École primaire congolaise — Communication
 * Version : 2.0 — Adapté aux conversations groupées
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('messages.envoyer');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'MessageController.php';
$messageController = new MessageController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $messageController->handleAjax();
}

$classes = $messageController->getClassesActives();

// Paramètres pour réponse
$destinataireId = (int) ($_GET['destinataire_id'] ?? 0);
$repondreId     = (int) ($_GET['repondre'] ?? 0);
$messageOrigine = null;

// Si c'est une réponse, récupérer le message d'origine
if ($repondreId > 0) {
    $messageOrigine = $db->fetchOne(
        "SELECT m.*, CONCAT(u.prenom, ' ', u.nom) AS expediteur_nom
         FROM messages m
         INNER JOIN utilisateurs u ON m.expediteur_id = u.id
         WHERE m.id = :id",
        ['id' => $repondreId]
    );
    if ($messageOrigine && !$destinataireId) {
        $destinataireId = (int) $messageOrigine['expediteur_id'];
    }
}

// Destinataire présélectionné
$destinatairePreselectionne = null;
if ($destinataireId > 0) {
    $destinatairePreselectionne = $db->fetchOne(
        "SELECT u.id, u.nom, u.prenom, u.login, r.nom AS role_nom
         FROM utilisateurs u
         INNER JOIN roles r ON u.role_id = r.id
         WHERE u.id = :id AND u.est_actif = 1",
        ['id' => $destinataireId]
    );
}

// Récupérer les rôles disponibles pour l'envoi groupé
$rolesDisponibles = $db->fetchAll(
    "SELECT r.nom, r.description, COUNT(u.id) AS nb_utilisateurs
     FROM roles r
     LEFT JOIN utilisateurs u ON r.id = u.role_id AND u.est_actif = 1
     WHERE r.est_actif = 1
     GROUP BY r.id, r.nom, r.description
     ORDER BY r.niveau_acces DESC"
);

$pageTitle = 'Nouveau message';
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Envoyer un message à un ou plusieurs destinataires';
$extraCSS = ['modules/messages.css'];
$extraJS  = ['modules/messages.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/messages">Messagerie</a>
    <span class="separator">/</span>
    <span class="current">Nouveau message</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-pen-to-square"></i> Nouveau message</h4>
        <p>Rédigez et envoyez un message à un ou plusieurs destinataires.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/messages" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Boîte de réception
        </a>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     FORMULAIRE D'ENVOI
     ============================================================ -->
<div class="message-form-grid">
    <div class="message-form-card">
        <div class="message-form-header">
            <div class="message-form-icon">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <div>
                <h5>Rédiger un message</h5>
                <p>Choisissez le type de destinataires et rédigez votre message</p>
            </div>
        </div>
        <div class="message-form-body">
            <form id="formMessage" autocomplete="off">
                <input type="hidden" id="message_parent_id" name="message_parent_id" value="<?= $repondreId ?>">

                <!-- ============================================================
                     ÉTAPE 1 : TYPE DE DESTINATAIRES
                     ============================================================ -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <h6 class="form-section-title">Destinataires</h6>
                            <p class="form-section-subtitle">Choisissez à qui envoyer ce message</p>
                        </div>
                    </div>

                    <!-- Sélecteur de type -->
                    <div class="destinataire-type-selector">
                        <label class="destinataire-type-card <?= !$destinataireId ? 'active' : '' ?>" id="cardTypeTous">
                            <input type="radio" name="destinataire_type" value="tous" <?= !$destinataireId ? 'checked' : '' ?> onchange="changerTypeDestinataire('tous')">
                            <div class="destinataire-type-content">
                                <div class="destinataire-type-icon tous"><i class="fa-solid fa-globe"></i></div>
                                <div class="destinataire-type-text">
                                    <strong>Tous les utilisateurs</strong>
                                    <span>Admin, Directeur, Enseignants, Parents, Comptable...</span>
                                </div>
                            </div>
                        </label>
                        <label class="destinataire-type-card" id="cardTypeRole">
                            <input type="radio" name="destinataire_type" value="role" onchange="changerTypeDestinataire('role')">
                            <div class="destinataire-type-content">
                                <div class="destinataire-type-icon role"><i class="fa-solid fa-user-tag"></i></div>
                                <div class="destinataire-type-text">
                                    <strong>Par rôle</strong>
                                    <span>Tous les parents, tous les enseignants...</span>
                                </div>
                            </div>
                        </label>
                        <label class="destinataire-type-card" id="cardTypeClasse">
                            <input type="radio" name="destinataire_type" value="classe" onchange="changerTypeDestinataire('classe')">
                            <div class="destinataire-type-content">
                                <div class="destinataire-type-icon classe"><i class="fa-solid fa-chalkboard"></i></div>
                                <div class="destinataire-type-text">
                                    <strong>Par classe</strong>
                                    <span>Parents + titulaire d'une classe</span>
                                </div>
                            </div>
                        </label>
                        <label class="destinataire-type-card <?= $destinataireId > 0 ? 'active' : '' ?>" id="cardTypeUtilisateur">
                            <input type="radio" name="destinataire_type" value="utilisateur" <?= $destinataireId > 0 ? 'checked' : '' ?> onchange="changerTypeDestinataire('utilisateur')">
                            <div class="destinataire-type-content">
                                <div class="destinataire-type-icon utilisateur"><i class="fa-solid fa-user"></i></div>
                                <div class="destinataire-type-text">
                                    <strong>Un utilisateur spécifique</strong>
                                    <span>Recherchez par nom, prénom ou login</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Sélecteur de rôle -->
                    <div class="form-group" id="groupeRole" style="display:none;">
                        <label class="form-label"><i class="fa-solid fa-user-tag"></i> Rôle des destinataires <span class="required">*</span></label>
                        <select id="role_destinataire" name="role_destinataire" class="form-control" onchange="mettreAJourResumeRole()">
                            <option value="">— Sélectionnez un rôle —</option>
                            <?php foreach ($rolesDisponibles as $role): ?>
                                <option value="<?= e($role['nom']) ?>" data-nb="<?= $role['nb_utilisateurs'] ?>">
                                    <?= e($role['nom']) ?>s — <?= $role['nb_utilisateurs'] ?> utilisateur(s)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="resumeRole" style="display:none;margin-top:8px;padding:10px 14px;background:#dbeafe;border-radius:8px;font-size:0.78rem;color:#1e40af;">
                            <i class="fa-solid fa-info-circle"></i> <span id="resumeRoleTexte"></span>
                        </div>
                    </div>

                    <!-- Sélecteur de classe -->
                    <div class="form-group" id="groupeClasse" style="display:none;">
                        <label class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe <span class="required">*</span></label>
                        <select id="classe_id" name="classe_id" class="form-control" onchange="mettreAJourResumeClasse()">
                            <option value="">— Sélectionnez une classe —</option>
                            <?php foreach ($classes as $cl): 
                                $nbEleves = (int) $db->fetchColumn("SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'", ['cid' => $cl['id']]);
                            ?>
                                <option value="<?= $cl['id'] ?>" data-nb="<?= $nbEleves ?>">
                                    <?= e($cl['nom']) ?> (Niv. <?= $cl['niveau'] ?>) — <?= $nbEleves ?> élève(s)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="resumeClasse" style="display:none;margin-top:8px;padding:10px 14px;background:#d1fae5;border-radius:8px;font-size:0.78rem;color:#065f46;">
                            <i class="fa-solid fa-info-circle"></i> <span id="resumeClasseTexte"></span>
                        </div>
                    </div>

                    <!-- Recherche d'utilisateur -->
                    <div class="form-group" id="groupeUtilisateur" style="display:<?= $destinataireId > 0 ? 'block' : 'none' ?>;">
                        <label class="form-label"><i class="fa-solid fa-search"></i> Rechercher un utilisateur</label>
                        
                        <?php if ($destinatairePreselectionne): ?>
                            <div class="destinataire-trouve-card">
                                <div class="destinataire-trouve-avatar">
                                    <?= mb_strtoupper(mb_substr($destinatairePreselectionne['prenom'], 0, 1) . mb_substr($destinatairePreselectionne['nom'], 0, 1)) ?>
                                </div>
                                <div class="destinataire-trouve-info">
                                    <strong><?= e($destinatairePreselectionne['prenom'] . ' ' . $destinatairePreselectionne['nom']) ?></strong>
                                    <span>@<?= e($destinatairePreselectionne['login']) ?> — <?= e($destinatairePreselectionne['role_nom']) ?></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="changerDestinataire()">
                                    <i class="fa-solid fa-rotate"></i> Changer
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="search-wrapper">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="rechercheUtilisateur" class="form-control" 
                                       placeholder="Rechercher par nom, prénom, login ou rôle..."
                                       autocomplete="off">
                            </div>
                            <div id="resultatsUtilisateurs" class="resultats-recherche"></div>
                        <?php endif; ?>
                        
                        <input type="hidden" id="destinataire_id" name="destinataire_id" value="<?= $destinataireId ?>">
                    </div>

                    <!-- Résumé des destinataires -->
                    <div id="resumeDestinataires" style="display:<?= ($destinataireId > 0 || !$destinataireId) ? 'block' : 'none' ?>;margin-top:12px;padding:12px 16px;background:<?= $destinataireId > 0 ? '#d1fae5' : '#dbeafe' ?>;border-radius:10px;font-size:0.82rem;">
                        <i class="fa-solid fa-paper-plane"></i> 
                        <span id="resumeTexte">
                            <?php if ($destinatairePreselectionne): ?>
                                Message à : <strong><?= e($destinatairePreselectionne['prenom'] . ' ' . $destinatairePreselectionne['nom']) ?></strong> (<?= e($destinatairePreselectionne['role_nom']) ?>)
                            <?php else: ?>
                                Message à : <strong>Tous les utilisateurs actifs</strong>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <hr class="form-divider">

                <!-- ============================================================
                     ÉTAPE 2 : MESSAGE ORIGINAL (SI RÉPONSE)
                     ============================================================ -->
                <?php if ($messageOrigine): ?>
                <div class="form-section">
                    <div class="message-reponse-original">
                        <div class="message-reponse-header">
                            <i class="fa-solid fa-reply"></i> En réponse à <strong><?= e($messageOrigine['expediteur_nom']) ?></strong>
                        </div>
                        <div class="message-reponse-sujet"><?= e($messageOrigine['sujet']) ?></div>
                        <div class="message-reponse-contenu"><?= nl2br(e($messageOrigine['contenu'])) ?></div>
                    </div>
                </div>
                <hr class="form-divider">
                <?php endif; ?>

                <!-- ============================================================
                     ÉTAPE 3 : SUJET ET CONTENU
                     ============================================================ -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div>
                            <h6 class="form-section-title">Message</h6>
                            <p class="form-section-subtitle">Rédigez votre message</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sujet" class="form-label">
                            <i class="fa-solid fa-heading"></i> Sujet <span class="required">*</span>
                        </label>
                        <input type="text" id="sujet" name="sujet" class="form-control" 
                               value="<?= $messageOrigine ? 'Re: ' . e($messageOrigine['sujet']) : '' ?>"
                               placeholder="Sujet de votre message..." required maxlength="255" autofocus>
                    </div>

                    <div class="form-group">
                        <label for="contenu" class="form-label">
                            <i class="fa-solid fa-align-left"></i> Message <span class="required">*</span>
                        </label>
                        <textarea id="contenu" name="contenu" class="form-control" rows="8" 
                                  placeholder="Rédigez votre message ici..."
                                  required></textarea>
                        <small class="form-help">
                            <i class="fa-solid fa-info-circle"></i> Soyez clair et concis. Le message sera visible dans la boîte de réception du destinataire.
                        </small>
                    </div>
                </div>
            </form>
        </div>
        <div class="message-form-footer">
            <a href="/gestion_scolaire/messages" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="button" class="btn btn-primary btn-lg" id="btnEnvoyerMessage">
                <i class="fa-solid fa-paper-plane"></i> Envoyer le message
            </button>
        </div>
    </div>

    <!-- ============================================================
         GUIDE
         ============================================================ -->
    <div>
        <!-- Guide des types de destinataires -->
        <div class="info-card-custom">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon blue"><i class="fa-solid fa-circle-info"></i></div>
                <h6>Types de destinataires</h6>
            </div>
            <div class="info-card-custom-body">
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div style="display:flex;align-items:flex-start;gap:10px;font-size:0.82rem;">
                        <span style="background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:20px;font-weight:600;font-size:0.7rem;flex-shrink:0;">TOUS</span>
                        <span style="color:#475569;">Envoie à <strong>tous les utilisateurs actifs</strong> du système (admin, directeur, secrétaire, enseignants, comptable, parents).</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;font-size:0.82rem;">
                        <span style="background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:20px;font-weight:600;font-size:0.7rem;flex-shrink:0;">RÔLE</span>
                        <span style="color:#475569;">Envoie à <strong>tous les utilisateurs d'un rôle</strong> (ex: tous les parents, tous les enseignants).</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;font-size:0.82rem;">
                        <span style="background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:20px;font-weight:600;font-size:0.7rem;flex-shrink:0;">CLASSE</span>
                        <span style="color:#475569;">Envoie aux <strong>parents des élèves + titulaire</strong> de la classe sélectionnée.</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;font-size:0.82rem;">
                        <span style="background:#e2e8f0;color:#475569;padding:4px 10px;border-radius:20px;font-weight:600;font-size:0.7rem;flex-shrink:0;">UTILISATEUR</span>
                        <span style="color:#475569;">Envoie à <strong>un seul utilisateur spécifique</strong> recherché par nom, prénom ou login.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conseils -->
        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon orange"><i class="fa-solid fa-lightbulb"></i></div>
                <h6>Conseils</h6>
            </div>
            <div class="info-card-custom-body">
                <ul class="info-list">
                    <li>Utilisez un <strong>sujet clair</strong> pour faciliter la recherche ultérieure.</li>
                    <li>Pour une communication urgente, privilégiez l'envoi de <strong>notifications</strong> (visibles immédiatement).</li>
                    <li>Les messages envoyés à plusieurs destinataires apparaîtront dans <strong>autant de conversations</strong> que de destinataires.</li>
                    <li>Vous pouvez <strong>répondre</strong> à un message reçu pour garder le fil de discussion.</li>
                    <li>Utilisez <strong>Ctrl+Entrée</strong> pour aller à la ligne dans le message.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
// ============================================================
// VARIABLES
// ============================================================
var envoiEnCours = false;
var searchTimeout;

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($destinataireId > 0): ?>
    changerTypeDestinataire('utilisateur');
    <?php endif; ?>
});

// ============================================================
// CHANGEMENT DE TYPE DE DESTINATAIRE
// ============================================================
function changerTypeDestinataire(type) {
    // Cacher tous les groupes
    document.getElementById('groupeRole').style.display = 'none';
    document.getElementById('groupeClasse').style.display = 'none';
    document.getElementById('groupeUtilisateur').style.display = 'none';
    
    // Cacher tous les résumés
    document.getElementById('resumeRole').style.display = 'none';
    document.getElementById('resumeClasse').style.display = 'none';
    
    // Réinitialiser les classes actives
    document.querySelectorAll('.destinataire-type-card').forEach(function(card) {
        card.classList.remove('active');
    });
    
    var resumeDest = document.getElementById('resumeDestinataires');
    var resumeTexte = document.getElementById('resumeTexte');
    
    switch(type) {
        case 'tous':
            document.getElementById('cardTypeTous').classList.add('active');
            resumeDest.style.display = 'block';
            resumeDest.style.background = '#dbeafe';
            resumeTexte.innerHTML = 'Message à : <strong>Tous les utilisateurs actifs</strong>';
            break;
            
        case 'role':
            document.getElementById('cardTypeRole').classList.add('active');
            document.getElementById('groupeRole').style.display = 'block';
            resumeDest.style.display = 'none';
            break;
            
        case 'classe':
            document.getElementById('cardTypeClasse').classList.add('active');
            document.getElementById('groupeClasse').style.display = 'block';
            resumeDest.style.display = 'none';
            break;
            
        case 'utilisateur':
            document.getElementById('cardTypeUtilisateur').classList.add('active');
            document.getElementById('groupeUtilisateur').style.display = 'block';
            <?php if (!$destinataireId): ?>
            resumeDest.style.display = 'none';
            <?php endif; ?>
            break;
    }
}

// ============================================================
// MISE À JOUR DES RÉSUMÉS
// ============================================================
function mettreAJourResumeRole() {
    var select = document.getElementById('role_destinataire');
    var option = select.selectedOptions[0];
    var resumeRole = document.getElementById('resumeRole');
    var resumeTexte = document.getElementById('resumeRoleTexte');
    
    if (option && option.value) {
        resumeRole.style.display = 'block';
        resumeTexte.textContent = 'Message envoyé à ' + option.dataset.nb + ' utilisateur(s) du rôle ' + option.text.split(' —')[0] + '.';
        
        var resumeDest = document.getElementById('resumeDestinataires');
        resumeDest.style.display = 'block';
        resumeDest.style.background = '#dbeafe';
        document.getElementById('resumeTexte').innerHTML = 'Message à : <strong>Tous les ' + option.text.split(' —')[0] + '</strong>';
    } else {
        resumeRole.style.display = 'none';
    }
}

function mettreAJourResumeClasse() {
    var select = document.getElementById('classe_id');
    var option = select.selectedOptions[0];
    var resumeClasse = document.getElementById('resumeClasse');
    var resumeTexte = document.getElementById('resumeClasseTexte');
    
    if (option && option.value) {
        resumeClasse.style.display = 'block';
        resumeTexte.textContent = 'Message envoyé aux parents des ' + option.dataset.nb + ' élève(s) + titulaire de la classe ' + option.text.split(' —')[0] + '.';
        
        var resumeDest = document.getElementById('resumeDestinataires');
        resumeDest.style.display = 'block';
        resumeDest.style.background = '#d1fae5';
        document.getElementById('resumeTexte').innerHTML = 'Message à : <strong>Parents + Titulaire de ' + option.text.split(' —')[0] + '</strong>';
    } else {
        resumeClasse.style.display = 'none';
    }
}

// ============================================================
// RECHERCHE D'UTILISATEUR
// ============================================================
document.getElementById('rechercheUtilisateur')?.addEventListener('keyup', function() {
    clearTimeout(searchTimeout);
    var query = this.value.trim();
    
    if (query.length < 2) {
        document.getElementById('resultatsUtilisateurs').innerHTML = '';
        document.getElementById('resultatsUtilisateurs').classList.remove('show');
        return;
    }

    searchTimeout = setTimeout(function() {
        var formData = new FormData();
        formData.append('action', 'rechercherUtilisateurs');
        formData.append('recherche', query);

        fetch('/gestion_scolaire/messages', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(reponse) { return reponse.json(); })
        .then(function(donnees) {
            var conteneur = document.getElementById('resultatsUtilisateurs');
            if (donnees.success && donnees.utilisateurs && donnees.utilisateurs.length > 0) {
                conteneur.innerHTML = donnees.utilisateurs.map(function(u) {
                    var initiales = (u.prenom.charAt(0) + u.nom.charAt(0)).toUpperCase();
                    return `
                    <div class="resultat-eleve-item" onclick="selectionnerDestinataire(${u.id}, '${escapeHtml(u.prenom + ' ' + u.nom)}', '${escapeHtml(u.login)}', '${escapeHtml(u.role_nom)}')">
                        <div class="resultat-eleve-avatar">${initiales}</div>
                        <div class="resultat-eleve-info">
                            <strong>${escapeHtml(u.prenom + ' ' + u.nom)}</strong>
                            <span>@${escapeHtml(u.login)} — ${escapeHtml(u.role_nom)}</span>
                        </div>
                    </div>`;
                }).join('');
                conteneur.classList.add('show');
            } else {
                conteneur.innerHTML = '<p style="padding:14px;color:#94a3b8;text-align:center;font-size:0.85rem;">Aucun utilisateur trouvé.</p>';
                conteneur.classList.add('show');
            }
        });
    }, 300);
});

// Fermer les résultats au clic extérieur
document.addEventListener('click', function(e) {
    var conteneur = document.getElementById('resultatsUtilisateurs');
    var champ = document.getElementById('rechercheUtilisateur');
    if (conteneur && champ && !conteneur.contains(e.target) && e.target !== champ) {
        conteneur.classList.remove('show');
    }
});

// ============================================================
// SÉLECTION D'UN DESTINATAIRE
// ============================================================
function selectionnerDestinataire(id, nom, login, role) {
    document.getElementById('destinataire_id').value = id;
    document.getElementById('rechercheUtilisateur').value = nom + ' (@' + login + ')';
    document.getElementById('resultatsUtilisateurs').classList.remove('show');
    
    var resumeDest = document.getElementById('resumeDestinataires');
    resumeDest.style.display = 'block';
    resumeDest.style.background = '#d1fae5';
    document.getElementById('resumeTexte').innerHTML = 'Message à : <strong>' + escapeHtml(nom) + '</strong> (' + escapeHtml(role) + ')';
}

function changerDestinataire() {
    document.getElementById('destinataire_id').value = '';
    document.getElementById('rechercheUtilisateur').value = '';
    document.getElementById('resumeDestinataires').style.display = 'none';
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