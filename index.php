<?php
/**
 * Fichier : index.php
 * Rôle    : Routeur frontal — Point d'entrée unique de l'application
 */

// Définir la constante d'accès pour tous les fichiers inclus
defined('ACCESS') or define('ACCESS', true);

// Charger la configuration
require_once __DIR__ . '/src/config/app.php';

// ============================================================
// ROUTAGE SIMPLE
// ============================================================

// Récupérer l'URL demandée
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = parse_url($requestUri, PHP_URL_PATH);

// Supprimer le dossier de base de l'URL
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = str_replace('\\', '/', $basePath);
$route = str_replace($basePath, '', $requestUri);
$route = '/' . trim($route, '/');

// Si la racine est demandée
if ($route === '/' || $route === '/index.php') {
    if (isLoggedIn()) {
        redirect('/gestion_scolaire/dashboard');
    } else {
        redirect('/gestion_scolaire/login');
    }
}
// Si un fichier physique existe, le servir directement (CSS, JS, images, etc.)
$filePath = __DIR__ . $route;
if (file_exists($filePath) && is_file($filePath)) {
    // Déterminer le type MIME
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'pdf'   => 'application/pdf',
    ];

    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    }

    // Cache pour les assets statiques
    if (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'])) {
        $cacheTime = 604800; // 7 jours
        header('Cache-Control: public, max-age=' . $cacheTime);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
    }

    readfile($filePath);
    exit;
}

// ============================================================
// PAGE NON TROUVÉE (404)
// ============================================================

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>fontawesome/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #2c3e90 0%, #1a274f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .error-container {
            text-align: center;
            padding: 40px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            opacity: 0.3;
            line-height: 1;
        }
        .error-icon {
            font-size: 4rem;
            margin: 20px 0;
            opacity: 0.8;
        }
        .error-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .error-message {
            font-size: 1rem;
            opacity: 0.8;
            margin-bottom: 30px;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: #fff;
            color: #2c3e90;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <div class="error-icon">
            <i class="fa-solid fa-map-signs"></i>
        </div>
        <h1 class="error-title">Page introuvable</h1>
        <p class="error-message">La page que vous recherchez n'existe pas ou a été déplacée.</p>
        <a href="<?= BASE_URL ?>" class="btn-home">
            <i class="fa-solid fa-house"></i> Retour à l'accueil
        </a>
    </div>
</body>
</html>