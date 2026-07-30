<?php
/**
 * Fichier : public/views/auth/register.php
 * Rôle    : Page d'inscription — Vue
 */

// Activer les erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir la constante d'accès
defined('ACCESS') or define('ACCESS', true);

// Charger la configuration
require_once __DIR__ . '/../../../src/config/app.php';

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'views/dashboard/');
}

// Traitement du formulaire d'inscription (POST AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
    $auth = new AuthController();
    $auth->register();
}

// Récupérer les rôles disponibles pour l'inscription (Enseignant et Parent)
$roles = $db->fetchAll(
    "SELECT id, nom, description FROM roles WHERE id IN (4, 6) ORDER BY id"
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Inscription — <?= e(ECOLE_NOM) ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e90">

    <title>Inscription — <?= e(ECOLE_NOM) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/gestion_scolaire/public/assets/images/favicon.ico">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/fontawesome/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/css/common/variables.css">
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/css/common/reset.css">
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/css/auth/register.css">
</head>
<body class="register-page">

    <div class="register-container">
        <!-- ============================================================
             PARTIE GAUCHE — INFORMATIONS
             ============================================================ -->
        <div class="register-left">
            <div class="register-left-content">
                <!-- Logo -->
                <div class="register-brand">
                    <div class="register-brand-icon">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h1><?= e(ECOLE_NOM) ?></h1>
                    <p><?= e(ECOLE_DEVIS) ?></p>
                </div>

                <!-- Avantages -->
                <div class="register-benefits">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="benefit-text">
                            <strong>Compte sécurisé</strong>
                            <p>Vos données sont protégées avec un chiffrement de niveau bancaire.</p>
                        </div>
                    </div>

                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div class="benefit-text">
                            <strong>Inscription rapide</strong>
                            <p>Créez votre compte en moins de 2 minutes et accédez à votre espace.</p>
                        </div>
                    </div>

                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="benefit-text">
                            <strong>Suivi en temps réel</strong>
                            <p>Consultez les notes, absences et bulletins dès votre connexion.</p>
                        </div>
                    </div>

                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <div class="benefit-text">
                            <strong>Support réactif</strong>
                            <p>Une équipe disponible pour vous accompagner en cas de difficulté.</p>
                        </div>
                    </div>
                </div>

                <!-- Pied -->
                <div class="register-left-footer">
                    <p><i class="fa-regular fa-copyright"></i> <?= date('Y') ?> — Tous droits réservés</p>
                </div>
            </div>

            <!-- Décorations -->
            <div class="register-decoration">
                <div class="decoration-circle circle-1"></div>
                <div class="decoration-circle circle-2"></div>
                <div class="decoration-circle circle-3"></div>
            </div>
        </div>

        <!-- ============================================================
             PARTIE DROITE — FORMULAIRE
             ============================================================ -->
        <div class="register-right">
            <div class="register-form-wrapper">
                <!-- En-tête -->
                <div class="register-form-header">
                    <div class="register-avatar">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h2>Créer un compte</h2>
                    <p>Rejoignez la communauté éducative</p>
                </div>

                <!-- Message d'erreur -->
                <div class="alert alert-error" id="registerError" style="display: none;" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="registerErrorMessage"></span>
                </div>

                <!-- Message de succès -->
                <div class="alert alert-success" id="registerSuccess" style="display: none;" role="alert">
                    <i class="fa-solid fa-circle-check"></i>
                    <span id="registerSuccessMessage"></span>
                </div>

                <!-- Formulaire -->
                <form id="registerForm" autocomplete="off" novalidate>
                    <?= csrfField() ?>

                    <!-- Ligne 1 : Nom + Prénom -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom" class="form-label">
                                <i class="fa-solid fa-user"></i> Nom <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input
                                    type="text"
                                    id="nom"
                                    name="nom"
                                    class="form-control"
                                    placeholder="Votre nom"
                                    required
                                    minlength="2"
                                    maxlength="100"
                                    autocomplete="family-name"
                                >
                            </div>
                            <span class="invalid-feedback" id="nomError"></span>
                        </div>

                        <div class="form-group">
                            <label for="prenom" class="form-label">
                                <i class="fa-solid fa-user"></i> Prénom <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input
                                    type="text"
                                    id="prenom"
                                    name="prenom"
                                    class="form-control"
                                    placeholder="Votre prénom"
                                    required
                                    minlength="2"
                                    maxlength="100"
                                    autocomplete="given-name"
                                >
                            </div>
                            <span class="invalid-feedback" id="prenomError"></span>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fa-solid fa-envelope"></i> Adresse email <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">
                                <i class="fa-solid fa-at"></i>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="vous@exemple.com"
                                required
                                maxlength="150"
                                autocomplete="email"
                            >
                        </div>
                        <span class="invalid-feedback" id="emailError"></span>
                    </div>

                    <!-- Téléphone -->
                    <div class="form-group">
                        <label for="telephone" class="form-label">
                            <i class="fa-solid fa-phone"></i> Téléphone <span style="font-weight:400;color:var(--color-gray-400);">(optionnel)</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">
                                <i class="fa-solid fa-mobile-screen"></i>
                            </span>
                            <input
                                type="tel"
                                id="telephone"
                                name="telephone"
                                class="form-control"
                                placeholder="+243XXXXXXXXX"
                                maxlength="20"
                                autocomplete="tel"
                            >
                        </div>
                        <span class="invalid-feedback" id="telephoneError"></span>
                    </div>

                    <!-- Ligne 2 : Mot de passe + Confirmation -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fa-solid fa-lock"></i> Mot de passe <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">
                                    <i class="fa-solid fa-key"></i>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Min. 8 caractères"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    class="toggle-password"
                                    id="togglePassword"
                                    aria-label="Afficher le mot de passe"
                                    title="Afficher/masquer"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <span class="invalid-feedback" id="passwordError"></span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                <i class="fa-solid fa-lock"></i> Confirmer <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">
                                    <i class="fa-solid fa-key"></i>
                                </span>
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    class="form-control"
                                    placeholder="Répétez le mot de passe"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    class="toggle-password"
                                    id="toggleConfirmPassword"
                                    aria-label="Afficher le mot de passe"
                                    title="Afficher/masquer"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <span class="invalid-feedback" id="confirmPasswordError"></span>
                        </div>
                    </div>

                    <!-- Force du mot de passe -->
                    <div class="password-strength" id="passwordStrength" style="display: none;">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText"></span>
                    </div>

                    <!-- Exigences mot de passe -->
                    <div class="password-requirements" id="passwordRequirements" style="display: none;">
                        <p class="requirements-title">
                            <i class="fa-solid fa-shield-halved"></i> Exigences de sécurité
                        </p>
                        <ul>
                            <li id="req-length">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Minimum 8 caractères</span>
                            </li>
                            <li id="req-uppercase">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Au moins une lettre majuscule</span>
                            </li>
                            <li id="req-lowercase">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Au moins une lettre minuscule</span>
                            </li>
                            <li id="req-number">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Au moins un chiffre</span>
                            </li>
                            <li id="req-special">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Au moins un caractère spécial</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Rôle -->
                    <div class="form-group">
                        <label for="role_id" class="form-label">
                            <i class="fa-solid fa-user-tag"></i> Vous êtes <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">
                                <i class="fa-solid fa-id-badge"></i>
                            </span>
                            <select
                                id="role_id"
                                name="role_id"
                                class="form-control"
                                required
                            >
                                <option value="">— Sélectionnez votre profil —</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>">
                                        <?= e($role['nom']) ?> — <?= e($role['description']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <span class="invalid-feedback" id="role_idError"></span>
                    </div>

                    <!-- Conditions -->
                    <div class="form-checkbox">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="terms" id="terms" required>
                            <span class="checkbox-custom">
                                <i class="fa-solid fa-check"></i>
                            </span>
                            <span class="checkbox-label">
                                J'accepte les <a href="#" target="_blank">conditions d'utilisation</a> et la <a href="#" target="_blank">politique de confidentialité</a> <span class="required">*</span>
                            </span>
                        </label>
                        <span class="invalid-feedback" id="termsError"></span>
                    </div>

                    <!-- Bouton d'inscription -->
                    <button type="submit" class="btn btn-register" id="registerButton">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Créer mon compte</span>
                        <i class="fa-solid fa-spinner fa-spin spinner" style="display: none;"></i>
                    </button>

                    <!-- Séparateur -->
                    <div class="divider">
                        <span>ou continuer avec</span>
                    </div>

                    <!-- Bouton Google -->
                    <button type="button" class="btn btn-google" id="googleRegisterButton">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>S'inscrire avec Google</span>
                    </button>
                </form>

                <!-- Pied du formulaire -->
                <div class="register-form-footer">
                    <p>
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Déjà un compte ?
                        <a href="/gestion_scolaire/login">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/gestion_scolaire/public/assets/js/common/main.js"></script>
    <script src="/gestion_scolaire/public/assets/js/auth/register.js"></script>
</body>
</html>