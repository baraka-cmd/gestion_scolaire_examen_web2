<?php
/**
 * Fichier : src/config/app.php
 * Rôle    : Configuration générale, constantes et démarrage session
 */

defined('ACCESS') or die('Accès interdit');

// ============================================================
// CONSTANTES DE L'APPLICATION
// ============================================================
define('APP_NAME', 'Gestion Scolaire');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'BARAKA NTWALI');
define('ENVIRONMENT', 'development');

// ============================================================
// CHEMINS PHYSIQUES (serveur)
// ============================================================
define('ROOT_PATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
define('SRC_PATH', ROOT_PATH . 'src' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', ROOT_PATH . 'public' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', PUBLIC_PATH . 'views' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', PUBLIC_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ASSETS_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('STORAGE_PATH', ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR);

// ============================================================
// URL DE BASE
// ============================================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = dirname($_SERVER['SCRIPT_NAME']);
$script   = str_replace('\\', '/', $script);
$baseUrl  = rtrim($protocol . $host . $script, '/') . '/';

define('BASE_URL', $baseUrl);
define('ASSETS_URL', BASE_URL . 'assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMAGES_URL', ASSETS_URL . 'images/');
define('FONTS_URL', ASSETS_URL . 'fonts/');
define('UPLOADS_URL', ASSETS_URL . 'uploads/');

// ============================================================
// SESSION
// ============================================================
define('SESSION_NAME', 'GS_SESSION');
define('SESSION_LIFETIME', 7200);
define('SESSION_TIMEOUT', 1800);

// ============================================================
// SÉCURITÉ
// ============================================================
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600);
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_DURATION', 900);

// ============================================================
// CONFIGURATION SMTP / MAIL
// ============================================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'barakantwali06@gmail.com');     // <-- Ton adresse Gmail d'envoi
define('SMTP_PASS', 'iqrhxcjkllywytwo');
    // <-- Ton mot de passe d'application Gmail (16 caractères)
define('SMTP_PORT', 587);

// ============================================================
// PAGINATION
// ============================================================
define('ITEMS_PER_PAGE', 15);

// ============================================================
// UPLOAD
// ============================================================
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv');


// ID Client EXACT copié depuis la popup Google Cloud
define('GOOGLE_CLIENT_ID', '478956496236-h5t4ok1s614944esk2nssl9kbi1ffhmh.apps.googleusercontent.com');

// Client Secret
define('GOOGLE_CLIENT_SECRET', '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');

// URI de redirection (URL fixe conforme à Google Cloud)
define('GOOGLE_REDIRECT_URI', 'http://localhost/gestion_scolaire/auth/google-callback');




// ============================================================
// ÉCOLE (valeurs par défaut)
// ============================================================
define('ECOLE_NOM', 'École Primaire LA RÉFÉRENCE');
define('ECOLE_DEVIS', 'Discipline - Travail - Excellence');
define('BAREME_NOTE_MAX', 20);
define('MOYENNE_PASSAGE', 10);

// ============================================================
// FUSEAU HORAIRE
// ============================================================
date_default_timezone_set('Africa/Lubumbashi');
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');

// ============================================================
// GESTION DES ERREURS
// ============================================================
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Ne pas afficher, mais logger
    ini_set('log_errors', 1);
    ini_set('error_log', STORAGE_PATH . 'logs' . DIRECTORY_SEPARATOR . 'php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================================
// DÉMARRAGE SESSION SÉCURISÉE
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => ($protocol === 'https://'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ============================================================
// INCLUSION BASE DE DONNÉES
// ============================================================
require_once SRC_PATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once SRC_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

$db = Database::getInstance();

// Initialiser le gestionnaire de sécurité
require_once SRC_PATH . 'core' . DIRECTORY_SEPARATOR . 'Security.php';
$security = Security::getInstance();