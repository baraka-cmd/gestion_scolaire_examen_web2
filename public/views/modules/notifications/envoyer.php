<?php
/**
 * Fichier : public/views/modules/notifications/envoyer.php
 * Rôle    : Formulaire d'envoi de notifications manuelles
 * Système : École primaire congolaise — Communication
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('notifications.envoyer');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'NotificationController.php';
$notificationController = new NotificationController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $notificationController->handleAjax();
}

$classes = $notificationController->getClassesActives();

$pageTitle = 'Envoyer une notification';
$pageIcon  = 'fa-paper-plane';
$pageDescription = 'Envoyez une notification aux parents';
$extraCSS = ['modules/notifications.css'];
$extraJS  = ['modules/notifications.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/notifications">Notifications</a>
    <span class="separator">/</span>
    <span class="current">Envoyer</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-paper-plane"></i> Envoyer une notification</h4>
        <p>Envoyez une notification aux parents d'élèves.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/notifications" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-bell"></i> Mes notifications
        </a>
    </div>
</div>

<div id="flashMessage"></div>

<div class="paiement-form-grid">
    <!-- ============================================================
         FORMULAIRE D'ENVOI
         ============================================================ -->
    <div class="paiement-form-card">
        <div class="paiement-form-header">
            <div class="paiement-form-icon">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <div>
                <h5>Nouvelle notification</h5>
                <p>Rédigez votre message et choisissez les destinataires</p>
            </div>
        </div>
        <div class="paiement-form-body">
            <form id="formNotification" autocomplete="off">

                <!-- Étape 1 : Destinataires -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <h6 class="form-section-title">Destinataires</h6>
                            <p class="form-section-subtitle">Choisissez à qui envoyer cette notification</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="destinataire_type" class="form-label">
                            <i class="fa-solid fa-bullseye"></i> Type de destinataires <span class="required">*</span>
                        </label>
                        <select id="destinataire_type" name="destinataire_type" class="form-control" onchange="changerDestinataire()">
                            <option value="">— Sélectionnez —</option>
                            <option value="tous">Tous les parents</option>
                            <option value="classe">Parents d'une classe</option>
                            <option value="parent">Un parent spécifique</option>
                        </select>
                    </div>

                    <!-- Classe (affiché si type = classe OU type = parent) -->
                    <div class="form-group" id="groupeClasse" style="display:none;">
                        <label for="classe_id" class="form-label">
                            <i class="fa-solid fa-chalkboard"></i> Classe <span class="required">*</span>
                        </label>
                        <select id="classe_id" name="classe_id" class="form-control" onchange="auChangementClasse()">
                            <option value="">— Sélectionnez une classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>"><?= e($cl['nom']) ?> (Niv. <?= $cl['niveau'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help" id="aideClasse">
                            <i class="fa-solid fa-info-circle"></i> Sélectionne la classe concernée.
                        </small>
                    </div>

                    <!-- Parent (affiché uniquement si type = parent) -->
                    <div class="form-group" id="groupeParent" style="display:none;">
                        <label for="parent_id" class="form-label">
                            <i class="fa-solid fa-user"></i> Parent <span class="required">*</span>
                        </label>
                        <select id="parent_id" name="parent_id" class="form-control">
                            <option value="">— Sélectionnez d'abord une classe —</option>
                        </select>
                    </div>

                    <!-- Résumé destinataires -->
                    <div id="resumeDestinataires" style="display:none;margin-top:8px;padding:10px 14px;background:#dbeafe;border-radius:8px;font-size:0.82rem;color:#1e40af;">
                        <i class="fa-solid fa-info-circle"></i> <span id="resumeDestinatairesTexte"></span>
                    </div>
                </div>


                <hr class="form-divider">

                <!-- Étape 2 : Message -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div>
                            <h6 class="form-section-title">Message</h6>
                            <p class="form-section-subtitle">Rédigez le contenu de la notification</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="type_notification" class="form-label">
                                <i class="fa-solid fa-tag"></i> Type de notification
                            </label>
                            <select id="type_notification" name="type_notification" class="form-control">
                                <option value="Information">Information</option>
                                <option value="Rappel">Rappel</option>
                                <option value="Alerte">Alerte</option>
                                <option value="Urgence">Urgence</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="titre" class="form-label">
                            <i class="fa-solid fa-heading"></i> Titre <span class="required">*</span>
                        </label>
                        <input type="text" id="titre" name="titre" class="form-control" 
                               placeholder="Ex: Réunion des parents, Rappel de paiement..." 
                               required maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">
                            <i class="fa-solid fa-align-left"></i> Message <span class="required">*</span>
                        </label>
                        <textarea id="message" name="message" class="form-control" rows="6" 
                                  placeholder="Rédigez votre message ici..."
                                  required></textarea>
                        <small class="form-help">
                            <i class="fa-solid fa-info-circle"></i> Soyez clair et concis. Le message sera visible dans l'application et pourra être envoyé par email.
                        </small>
                    </div>

                    <!-- Option email -->
                    <div class="form-group">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" id="envoyer_email" name="envoyer_email" value="1">
                            <span class="checkbox-custom"><i class="fa-solid fa-check"></i></span>
                            <span class="checkbox-label">Envoyer également par email</span>
                        </label>
                        <small class="form-help" style="margin-left:30px;">
                            <i class="fa-solid fa-envelope"></i> Un email sera envoyé en plus de la notification dans l'application.
                        </small>
                    </div>
                </div>
            </form>
        </div>
        <div class="paiement-form-footer">
            <a href="/gestion_scolaire/notifications" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="button" class="btn btn-primary btn-lg" id="btnEnvoyerNotification">
                <i class="fa-solid fa-paper-plane"></i> Envoyer la notification
            </button>
        </div>
    </div>

    <!-- ============================================================
         GUIDE
         ============================================================ -->
    <div>
        <div class="info-card-custom">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon blue"><i class="fa-solid fa-circle-info"></i></div>
                <h6>Guide d'envoi</h6>
            </div>
            <div class="info-card-custom-body">
                <ul class="info-list">
                    <li><strong>Tous les parents :</strong> Envoie à tous les parents ayant un compte utilisateur.</li>
                    <li><strong>Parents d'une classe :</strong> Envoie uniquement aux parents des élèves de la classe sélectionnée.</li>
                    <li><strong>Un parent spécifique :</strong> Envoie à un seul parent.</li>
                    <li>La notification apparaîtra dans le <strong>centre de notifications</strong> du parent.</li>
                    <li>Cochez <strong>"Envoyer par email"</strong> pour doubler l'envoi par courrier électronique.</li>
                </ul>
            </div>
        </div>

        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon orange"><i class="fa-solid fa-lightbulb"></i></div>
                <h6>Types de notification</h6>
            </div>
            <div class="info-card-custom-body">
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:0.82rem;">
                        <span class="badge badge-info">Information</span>
                        <span style="color:var(--color-gray-600);">Message informatif général</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:0.82rem;">
                        <span class="badge badge-warning">Rappel</span>
                        <span style="color:var(--color-gray-600);">Rappel de paiement, événement...</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:0.82rem;">
                        <span class="badge badge-danger">Alerte</span>
                        <span style="color:var(--color-gray-600);">Absence non justifiée, retard répété...</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:0.82rem;">
                        <span class="badge" style="background:#7c3aed;color:#fff;">Urgence</span>
                        <span style="color:var(--color-gray-600);">Convocation immédiate, incident grave...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>