<?php
/**
 * Fichier : public/views/auth/logout.php
 * Rôle    : Déconnexion de l'utilisateur
 */

// Définir la constante d'accès
defined('ACCESS') or define('ACCESS', true);

// Charger la configuration
require_once __DIR__ . '/../../../src/config/app.php';

// Charger le contrôleur d'authentification
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';

// Exécuter la déconnexion
$auth = new AuthController();
$auth->logout();