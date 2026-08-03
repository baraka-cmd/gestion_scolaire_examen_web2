<?php
/**
 * Fichier : views/auth/google-callback.php
 * Rôle    : Endpoint de retour (Callback) appelé par Google après authentification
 */

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../src/config/app.php';
require_once SRC_PATH . 'services/GoogleAuthService.php';
require_once SRC_PATH . 'controllers/AuthController.php';

// 1. Vérification des erreurs retournées par Google
if (isset($_GET['error'])) {
    error_log('Google Auth Error : ' . $_GET['error']);
    redirect(BASE_URL . 'views/auth/login.php?error=google_cancelled');
}

// 2. Vérification du token anti-CSRF state
$state = $_GET['state'] ?? '';
if (empty($state) || $state !== ($_SESSION['oauth2state'] ?? '')) {
    unset($_SESSION['oauth2state']);
    redirect(BASE_URL . 'views/auth/login.php?error=invalid_state');
}

// Nettoyage du token state
unset($_SESSION['oauth2state']);

// 3. Récupération du code d'autorisation
$code = $_GET['code'] ?? '';
if (empty($code)) {
    redirect(BASE_URL . 'views/auth/login.php?error=no_code');
}

// 4. Traitement OAuth
$googleService = new GoogleAuthService();

// Échange Code -> Token
$accessToken = $googleService->getAccessToken($code);

if (!$accessToken) {
    redirect(BASE_URL . 'views/auth/login.php?error=token_failed');
}

// Récupération Profil Utilisateur
$googleUser = $googleService->getUserInfo($accessToken);

if (!$googleUser) {
    redirect(BASE_URL . 'views/auth/login.php?error=userinfo_failed');
}

// 5. Délégation au contrôleur pour la connexion/inscription BDD
$authController = new AuthController();
$authController->handleGoogleCallback($googleUser);
