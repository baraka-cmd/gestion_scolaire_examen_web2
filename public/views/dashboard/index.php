<?php
defined('ACCESS') or define('ACCESS', true);
require_once __DIR__ . '/../../../src/config/app.php';
requireLogin();

$role = $_SESSION['utilisateur_role'] ?? 'Admin';

switch ($role) {
    case 'Admin':
    case 'Directeur':
        require_once __DIR__ . '/admin.php';
        break;
    case 'Secrétaire':
        require_once __DIR__ . '/secretaire.php';
        break;
    case 'Enseignant':
        require_once __DIR__ . '/enseignant.php';
        break;
    case 'Comptable':
        require_once __DIR__ . '/comptable.php';
        break;
    case 'Parent':
        require_once __DIR__ . '/parent.php';
        break;
    default:
        require_once __DIR__ . '/admin.php';
}