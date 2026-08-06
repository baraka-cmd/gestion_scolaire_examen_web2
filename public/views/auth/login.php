<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir la constante d'accès
defined('ACCESS') or defined('ACCESS') or define('ACCESS', true);

// Charger la configuration
require_once __DIR__ . '/../../../src/config/app.php';

// Traitement du formulaire de connexion (POST AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
    $auth = new AuthController();
    $auth->login();
}

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'views/dashboard/');
}

// Messages de retour
$successMessage = '';
$errorMessage   = '';

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $successMessage = 'Vous avez été déconnecté avec succès.';
}

if (isset($_GET['session_expired']) && $_GET['session_expired'] === '1') {
    $errorMessage = 'Votre session a expiré. Veuillez vous reconnecter.';
}

if (isset($_GET['access_denied']) && $_GET['access_denied'] === '1') {
    $errorMessage = 'Accès refusé. Vous n\'avez pas les permissions nécessaires.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Connexion — <?= e(ECOLE_NOM) ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e90">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>Connexion — <?= e(ECOLE_NOM) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="../assets/images/favicon.ico">

    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/gestion_scolaire/public/assets/css/common/variables.css">
<link rel="stylesheet" href="/gestion_scolaire/public/assets/css/common/reset.css">
<link rel="stylesheet" href="/gestion_scolaire/public/assets/css/auth/login.css">

    <!-- Préchargement des polices -->
<link rel="preload" href="/gestion_scolaire/public/assets/fonts/poppins/Poppins-Regular.ttf" as="font" type="font/ttf" crossorigin>
<link rel="preload" href="/gestion_scolaire/public/assets/fonts/poppins/Poppins-SemiBold.ttf" as="font" type="font/ttf" crossorigin>
<link rel="preload" href="/gestion_scolaire/public/assets/fonts/poppins/Poppins-Bold.ttf" as="font" type="font/ttf" crossorigin>
</head>
<body class="login-page">

    <div class="login-container">
        <!-- ============================================================
             PARTIE GAUCHE — INFORMATIONS ÉCOLE
             ============================================================ -->
        <div class="login-left">
            <div class="login-left-content">
                <!-- Logo et marque -->
<div class="login-brand">
    <div class="login-brand-icon">
        <?php 
        // 1. Récupération des paramètres BDD
        if (empty($_SESSION['ecole_params'])) {
            try {
                if (class_exists('Database')) {
                    $params = Database::getInstance()->fetchOne("SELECT * FROM parametres_ecole LIMIT 1");
                    if ($params) {
                        $_SESSION['ecole_params'] = $params;
                    }
                }
            } catch (Exception $e) {
                $_SESSION['ecole_params'] = [];
            }
        }

        $ecoleParams     = $_SESSION['ecole_params'] ?? [];
        $ecoleNomLogin    = !empty($ecoleParams['nom_ecole']) ? $ecoleParams['nom_ecole'] : ECOLE_NOM;
        $ecoleDeviseLogin = !empty($ecoleParams['devise']) ? $ecoleParams['devise'] : ECOLE_DEVIS;
        $ecoleLogoLogin   = $ecoleParams['logo'] ?? $ecoleParams['logo_ecole'] ?? '';

        // Construction de l'URL directe du logo
        $logoUrl = !empty($ecoleLogoLogin) ? ASSETS_URL . ltrim($ecoleLogoLogin, '/') : '';
        ?>

        <!-- Icône de secours toujours présente au fond -->
        <i class="fa-solid fa-school fallback-icon"></i>

        <?php if (!empty($logoUrl)): ?>
            <!-- Image du logo superposée avec gestion d'erreur JS automatique -->
            <img src="<?= $logoUrl ?>" 
                 alt="Logo École" 
                 class="school-logo-img"
                 onerror="this.style.display='none';">
        <?php endif; ?>
    </div>
    <h1><?= e($ecoleNomLogin) ?></h1>
    <p><?= e($ecoleDeviseLogin) ?></p>
</div>




                <!-- Points forts -->
                <div class="login-info">
                    <div class="login-info-item">
                        <div class="login-info-icon">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <strong>Suivi pédagogique complet</strong>
                            <p>Consultez les notes, moyennes, bulletins et résultats scolaires en temps réel.</p>
                        </div>
                    </div>

                    <div class="login-info-item">
                        <div class="login-info-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <strong>Présence et assiduité</strong>
                            <p>Suivez les absences et retards de vos enfants. Recevez des alertes instantanées.</p>
                        </div>
                    </div>

                    <div class="login-info-item">
                        <div class="login-info-icon">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <strong>Gestion financière transparente</strong>
                            <p>Consultez l'état des frais scolaires, paiements et téléchargez vos reçus.</p>
                        </div>
                    </div>

                    <div class="login-info-item">
                        <div class="login-info-icon">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div>
                            <strong>Communication directe</strong>
                            <p>Échangez avec les enseignants et l'administration via la messagerie intégrée.</p>
                        </div>
                    </div>
                </div>

                <!-- Pied gauche -->
                <div class="login-left-footer">
                    <p><i class="fa-regular fa-copyright"></i> <?= date('Y') ?> — Tous droits réservés</p>
                </div>
            </div>

            <!-- Décorations animées -->
            <div class="login-decoration">
                <div class="decoration-circle circle-1"></div>
                <div class="decoration-circle circle-2"></div>
                <div class="decoration-circle circle-3"></div>
            </div>
        </div>

        <!-- ============================================================
             PARTIE DROITE — FORMULAIRE DE CONNEXION
             ============================================================ -->
        <div class="login-right">
            <div class="login-form-wrapper">
                <!-- En-tête -->
                <div class="login-form-header">
                    <div class="login-avatar">
                        <i class="fa-solid fa-user-lock"></i>
                    </div>
                    <h2>Connexion</h2>
                    <p>Accédez à votre espace personnel sécurisé</p>
                </div>

                <!-- Message de succès -->
                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= e($successMessage) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Message d'erreur -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-error" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= e($errorMessage) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Conteneur erreur dynamique -->
                <div class="alert alert-error" id="loginError" style="display: none;" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="loginErrorMessage"></span>
                </div>

                <!-- Conteneur succès dynamique -->
                <div class="alert alert-success" id="loginSuccess" style="display: none;" role="alert">
                    <i class="fa-solid fa-circle-check"></i>
                    <span id="loginSuccessMessage"></span>
                </div>

                <!-- ============================================================
     FORMULAIRE
     ============================================================ -->
<form id="loginForm" autocomplete="off" novalidate>
    <!-- Token CSRF -->
    <?= csrfField() ?>

    <!-- Champ : Adresse email -->
    <div class="form-group">
        <label for="email" class="form-label">
            <i class="fa-solid fa-envelope"></i> Adresse email
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
                value=""
                required
                autofocus
                autocomplete="email"
            >
        </div>
        <span class="invalid-feedback" id="emailError"></span>
    </div>

    <!-- Champ : Mot de passe -->
    <div class="form-group">
        <label for="password" class="form-label">
            <i class="fa-solid fa-lock"></i> Mot de passe
        </label>
        <div class="input-icon-wrapper">
            <i class="fas fa-key input-icon"></i>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" id="togglePassword" class="btn-toggle-password" tabindex="-1" aria-label="Afficher/Masquer mot de passe">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        <span class="invalid-feedback" id="passwordError"></span>
    </div>

    <!-- Indicateur de force du mot de passe -->
    <div class="password-strength" id="passwordStrength" style="display: none;">
        <div class="strength-bar">
            <div class="strength-fill" id="strengthFill"></div>
        </div>
        <span class="strength-text" id="strengthText"></span>
    </div>

    <!-- Exigences du mot de passe -->
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

    <!-- Options -->
    <div class="form-options">
        <label class="checkbox-wrapper">
            <input type="checkbox" name="remember" id="remember">
            <span class="checkbox-custom">
                <i class="fa-solid fa-check"></i>
            </span>
            <span class="checkbox-label">Se souvenir de moi</span>
        </label>
        
        <div class="login-links">
            <a href="/gestion_scolaire/forgot-password" class="forgot-password-link" id="forgotPasswordLink">
                <i class="fa-solid fa-key"></i> Mot de passe oublié ?
            </a>
        </div>
    </div>

    <!-- Bouton de connexion -->
    <button type="submit" class="btn btn-login" id="loginButton">
        <i class="fa-solid fa-right-to-bracket"></i>
        <span>Se connecter</span>
        <i class="fa-solid fa-spinner fa-spin spinner" style="display: none;"></i>
    </button>

    <!-- Séparateur -->
    <div class="divider">
        <span>ou continuer avec</span>
    </div>

    <!-- Bouton Google connecté à la route de redirection -->
    <a href="/gestion_scolaire/auth/google" class="btn btn-google" id="googleButton" style="text-decoration: none;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        <span>Se connecter avec Google</span>
    </a>
</form>


                <!-- Pied du formulaire -->
                <div class="login-form-footer">
                    <p>
                        <i class="fa-solid fa-shield-halved"></i>
                        Connexion sécurisée — Toutes vos données sont chiffrées et protégées
                    </p>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/gestion_scolaire/public/assets/js/common/main.js"></script>
<script src="/gestion_scolaire/public/assets/js/auth/login.js"></script>
</body>
</html>