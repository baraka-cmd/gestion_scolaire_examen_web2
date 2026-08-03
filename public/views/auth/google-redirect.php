<?php
// Désactiver temporairement le masquage pour voir les vraies erreurs PHP s'il y en a
ini_set('display_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

// Ajustement des chemins d'accès selon votre arborescence
$configPath = __DIR__ . '/../../../src/config/app.php';
$servicePath = __DIR__ . '/../../../src/services/GoogleAuthService.php';

if (!file_exists($configPath)) {
    die("Erreur : Le fichier de configuration est introuvable sur le chemin : " . realpath(__DIR__) . '/../../../src/config/app.php');
}

require_once $configPath;
require_once SRC_PATH . 'services/GoogleAuthService.php';

$googleService = new GoogleAuthService();
$authUrl = $googleService->getAuthUrl();

// Redirection stricte
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit();
