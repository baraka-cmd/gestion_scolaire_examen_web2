<?php
/**
 * Fichier : public/views/modules/utilisateurs/changer_mot_de_passe.php
 * Rôle    : Formulaire de changement de mot de passe
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();

$successMessage = '';
$errorMessage   = '';
$errors         = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ancienMotDePasse       = $_POST['ancien_mot_de_passe'] ?? '';
    $nouveauMotDePasse      = $_POST['nouveau_mot_de_passe'] ?? '';
    $confirmationMotDePasse = $_POST['confirmation_mot_de_passe'] ?? '';

    if (isBlank($ancienMotDePasse)) {
        $errors['ancien'] = 'Veuillez entrer votre mot de passe actuel.';
    }

    if (isBlank($nouveauMotDePasse)) {
        $errors['nouveau'] = 'Veuillez entrer un nouveau mot de passe.';
    } elseif (mb_strlen($nouveauMotDePasse) < 8) {
        $errors['nouveau'] = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (!preg_match('/[A-Z]/', $nouveauMotDePasse)) {
        $errors['nouveau'] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
    } elseif (!preg_match('/[a-z]/', $nouveauMotDePasse)) {
        $errors['nouveau'] = 'Le mot de passe doit contenir au moins une lettre minuscule.';
    } elseif (!preg_match('/[0-9]/', $nouveauMotDePasse)) {
        $errors['nouveau'] = 'Le mot de passe doit contenir au moins un chiffre.';
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'\"\\|,.<>\/?~`]/', $nouveauMotDePasse)) {
        $errors['nouveau'] = 'Le mot de passe doit contenir au moins un caractère spécial.';
    } elseif ($nouveauMotDePasse === $ancienMotDePasse) {
        $errors['nouveau'] = 'Le nouveau mot de passe doit être différent de l\'ancien.';
    }

    if (isBlank($confirmationMotDePasse)) {
        $errors['confirmation'] = 'Veuillez confirmer votre nouveau mot de passe.';
    } elseif ($nouveauMotDePasse !== $confirmationMotDePasse) {
        $errors['confirmation'] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        $utilisateur = $db->fetchOne(
            "SELECT mot_de_passe FROM utilisateurs WHERE id = :id",
            ['id' => $_SESSION['utilisateur_id']]
        );

        if (!$utilisateur || !password_verify($ancienMotDePasse, $utilisateur['mot_de_passe'])) {
            $errors['ancien'] = 'Le mot de passe actuel est incorrect.';
        } else {
            $hashedPassword = password_hash($nouveauMotDePasse, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

            try {
                $db->update(
                    "UPDATE utilisateurs SET mot_de_passe = :password WHERE id = :id",
                    ['password' => $hashedPassword, 'id' => $_SESSION['utilisateur_id']]
                );

                logActivity('Réinitialisation mot de passe', 'utilisateurs', $_SESSION['utilisateur_id'], 
                    'Changement de mot de passe par l\'utilisateur');

                $successMessage = 'Votre mot de passe a été modifié avec succès. Pour des raisons de sécurité, veuillez vous reconnecter.';
                header('Refresh: 2; URL=/gestion_scolaire/logout');

            } catch (Exception $e) {
                $errorMessage = 'Une erreur est survenue lors du changement de mot de passe.';
                error_log('Erreur changement mot de passe: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Changer mon mot de passe';
$pageIcon  = 'fa-lock';
$pageDescription = 'Modifiez votre mot de passe pour sécuriser votre compte';
$extraCSS = ['modules/utilisateurs.css', 'modules/changer_mot_de_passe.css'];
$extraJS  = ['modules/changer_mot_de_passe.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/profil">Mon profil</a>
    <span class="separator">/</span>
    <span class="current">Changer mot de passe</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-lock text-primary me-2"></i>Changer mon mot de passe
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Pour des raisons de sécurité, choisissez un mot de passe robuste.
        </p>
    </div>
    <a href="/gestion_scolaire/profil" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Retour au profil
    </a>
</div>

<?php if (!empty($successMessage)): ?>
    <div class="success-animation">
        <i class="fa-solid fa-circle-check"></i>
        <h4>Mot de passe modifié !</h4>
        <p><?= e($successMessage) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($errorMessage) ?></span>
    </div>
<?php endif; ?>

<div class="password-change-container">
    <div class="password-card">
        <div class="password-card-header">
            <div class="password-card-icon">
                <i class="fa-solid fa-key"></i>
            </div>
            <div>
                <h5>Modifier mon mot de passe</h5>
                <p>Remplissez tous les champs pour changer votre mot de passe</p>
            </div>
        </div>
        <div class="password-card-body">
            <form method="POST" action="" autocomplete="off" id="formChangePassword">

                <!-- Ancien mot de passe -->
                <div class="password-input-group">
                    <label for="ancien_mot_de_passe" class="password-input-label">
                        <i class="fa-solid fa-lock"></i> Mot de passe actuel <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <span class="password-input-icon"><i class="fa-solid fa-key"></i></span>
                        <input type="password" id="ancien_mot_de_passe" name="ancien_mot_de_passe" 
                               class="password-input <?= isset($errors['ancien']) ? 'is-invalid' : '' ?>"
                               placeholder="Entrez votre mot de passe actuel" required autofocus>
                        <button type="button" class="password-toggle-btn" data-target="#ancien_mot_de_passe" title="Afficher/masquer">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['ancien'])): ?>
                        <span class="password-feedback" id="ancienFeedback"><?= e($errors['ancien']) ?></span>
                    <?php else: ?>
                        <span class="password-feedback" id="ancienFeedback"></span>
                    <?php endif; ?>
                </div>

                <hr class="password-divider">

                <!-- Nouveau mot de passe -->
                <div class="password-input-group">
                    <label for="nouveau_mot_de_passe" class="password-input-label">
                        <i class="fa-solid fa-lock"></i> Nouveau mot de passe <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <span class="password-input-icon"><i class="fa-solid fa-key"></i></span>
                        <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" 
                               class="password-input <?= isset($errors['nouveau']) ? 'is-invalid' : '' ?>"
                               placeholder="Minimum 8 caractères" required minlength="8">
                        <button type="button" class="password-toggle-btn" data-target="#nouveau_mot_de_passe" title="Afficher/masquer">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['nouveau'])): ?>
                        <span class="password-feedback" id="nouveauFeedback"><?= e($errors['nouveau']) ?></span>
                    <?php else: ?>
                        <span class="password-feedback" id="nouveauFeedback"></span>
                    <?php endif; ?>
                </div>

                <!-- Indicateur de force -->
                <div class="strength-container" id="passwordStrength" style="display:none;">
                    <div class="strength-bar-wrapper">
                        <div class="strength-bar-fill" id="strengthFill"></div>
                    </div>
                    <span class="strength-label" id="strengthText"></span>
                </div>

                <!-- Exigences -->
                <div class="requirements-container" id="passwordRequirements" style="display:none;">
                    <div class="requirements-title">
                        <i class="fa-solid fa-shield-halved"></i> Exigences de sécurité
                    </div>
                    <ul class="requirements-list">
                        <li class="requirement-item" id="req-length">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>Minimum 8 caractères</span>
                        </li>
                        <li class="requirement-item" id="req-uppercase">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>Au moins une lettre majuscule</span>
                        </li>
                        <li class="requirement-item" id="req-lowercase">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>Au moins une lettre minuscule</span>
                        </li>
                        <li class="requirement-item" id="req-number">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>Au moins un chiffre</span>
                        </li>
                        <li class="requirement-item" id="req-special">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>Au moins un caractère spécial</span>
                        </li>
                    </ul>
                </div>

                <!-- Confirmation -->
                <div class="password-input-group">
                    <label for="confirmation_mot_de_passe" class="password-input-label">
                        <i class="fa-solid fa-check-circle"></i> Confirmer le mot de passe <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <span class="password-input-icon"><i class="fa-solid fa-key"></i></span>
                        <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" 
                               class="password-input <?= isset($errors['confirmation']) ? 'is-invalid' : '' ?>"
                               placeholder="Répétez le nouveau mot de passe" required minlength="8">
                        <button type="button" class="password-toggle-btn" data-target="#confirmation_mot_de_passe" title="Afficher/masquer">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['confirmation'])): ?>
                        <span class="password-feedback" id="confirmationFeedback"><?= e($errors['confirmation']) ?></span>
                    <?php else: ?>
                        <span class="password-feedback" id="confirmationFeedback"></span>
                    <?php endif; ?>
                </div>

                <!-- Conseils -->
                <div class="security-tips">
                    <div class="security-tips-header">
                        <i class="fa-solid fa-lightbulb"></i> Conseils pour un mot de passe sécurisé
                    </div>
                    <ul class="security-tips-list">
                        <li><i class="fa-solid fa-check"></i> Utilisez au moins 12 caractères</li>
                        <li><i class="fa-solid fa-check"></i> Mélangez majuscules, minuscules, chiffres et caractères spéciaux</li>
                        <li><i class="fa-solid fa-check"></i> Évitez les informations personnelles (nom, date de naissance)</li>
                        <li><i class="fa-solid fa-check"></i> N'utilisez pas le même mot de passe sur plusieurs sites</li>
                        <li><i class="fa-solid fa-check"></i> Changez votre mot de passe régulièrement</li>
                    </ul>
                </div>
            </form>
        </div>
        <div class="password-card-footer">
            <a href="/gestion_scolaire/profil" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="submit" form="formChangePassword" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Changer mon mot de passe
            </button>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>