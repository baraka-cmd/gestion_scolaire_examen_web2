<?php
/**
 * Fichier : public/index.php
 * Rôle    : Routeur frontal — Point d'entrée unique
 */


ob_start(); // Empêche les erreurs "Cannot modify header information"

// Reste de ton code dans index.php...

define('ACCESS', true);

require_once __DIR__ . '/../src/config/app.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Récupérer l'URL
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/gestion_scolaire';
$route = str_replace($basePath, '', $requestUri);
$route = '/' . trim($route, '/');

// ============================================================
// ROUTES
// ============================================================
// ============================================================
// ROUTES
// ============================================================
$routes = [

    // Ajoute le slash '/' au début ici :
    '/test-email-direct' => function() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        echo "<h2>--- DÉBUT DU TEST SMTP EN DIRECT ---</h2>";

        try {
            require_once SRC_PATH . 'services/EmailService.php';
            echo "<p>1. Chargement de EmailService : <strong>OK</strong></p>";

            $emailService = new EmailService();

            $destinataire = "barakantwali06@gmail.com"; 
            $sujet = "Test SMTP Direct";
            $message = "Ceci est un test en direct pour vérifier SMTP.";

            echo "<p>2. Connexion et envoi à <strong>{$destinataire}</strong> en cours...</p>";

            $resultat = $emailService->envoyerTemplate($destinataire, $sujet, $message);

            if ($resultat) {
                echo "<h3 style='color:green;'>SUCCESS ! L'e-mail a été envoyé avec succès !</h3>";
            } else {
                echo "<h3 style='color:red;'>ÉCHEC ! L'e-mail n'a pas pu être envoyé.</h3>";
            }

        } catch (Throwable $e) {
            echo "<h3 style='color:red;'>EXCEPTION CRASH DÉTECTÉE :</h3>";
            echo "<pre style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px;'>";
            echo "Message : " . $e->getMessage() . "\n";
            echo "Fichier : " . $e->getFile() . " (Ligne " . $e->getLine() . ")\n\n";
            echo "Trace :\n" . $e->getTraceAsString();
            echo "</pre>";
        }

        echo "<h2>--- FIN DU TEST ---</h2>";
        exit;
    },

    
        // ============================================================
    // ROUTE DE TEST EMAIL VIA LE CONTROLEUR
    // ============================================================
    '/test-email' => function() {
        require_once SRC_PATH . 'controllers/NotificationController.php';
        $controller = new NotificationController();
        $controller->testEmail();
    },

    // Auth
    '/'                          => 'views/auth/login.php',
    '/login'                     => 'views/auth/login.php',
    '/register'                  => 'views/auth/register.php',
    '/logout'                    => 'views/auth/logout.php',
    
    // Dashboard
    '/dashboard'                 => 'views/dashboard/index.php',
    
    // Rôles et permissions
    '/roles'                     => 'views/modules/roles/liste.php',
    
    // Utilisateurs
    '/utilisateurs'              => 'views/modules/utilisateurs/liste.php',
    '/utilisateurs/ajouter'      => 'views/modules/utilisateurs/ajouter.php',
    
    // Élèves
    '/eleves'                    => 'views/modules/eleves/liste.php',
    '/eleves/ajouter'            => 'views/modules/eleves/ajouter.php',
    '/eleves/import'             => 'views/modules/eleves/import.php',
    '/eleves/export'             => 'views/modules/eleves/export.php',
    
    // Parents
    '/parents'                   => 'views/modules/parents/liste.php',
    '/parents/ajouter'           => 'views/modules/parents/ajouter.php',
    
    // Enseignants
    '/enseignants'               => 'views/modules/enseignants/liste.php',
    '/enseignants/ajouter'       => 'views/modules/enseignants/ajouter.php',
    '/enseignants/affectations'  => 'views/modules/enseignants/affectations.php',
    
    // Classes
    '/classes'                   => 'views/modules/classes/liste.php',
    '/classes/ajouter'           => 'views/modules/classes/ajouter.php',
    
    // Matières
    '/matieres'                  => 'views/modules/matieres/liste.php',
    '/matieres/ajouter'          => 'views/modules/matieres/ajouter.php',
    
    // Inscriptions
    '/inscriptions'              => 'views/modules/inscriptions/liste.php',
    '/inscriptions/nouvelle'     => 'views/modules/inscriptions/nouvelle.php',
    '/inscriptions/reinscription' => 'views/modules/inscriptions/reinscription.php',
    
   

// ============================================================
// APRÈS (nouvelles routes à AJOUTER)
// ============================================================
'/notes'                     => 'views/modules/notes/liste.php',
'/notes/ajouter'             => 'views/modules/notes/ajouter.php',
'/notes/saisie'              => 'views/modules/notes/saisie.php',
'/notes/consulter'           => 'views/modules/notes/consulter.php',
    
    // Ajouter dans les routes statiques
'/resultats'                 => 'views/modules/resultats/consulter.php',
'/resultats/consulter'       => 'views/modules/resultats/consulter.php',
'/resultats/proclamation'    => 'views/modules/resultats/proclamation.php',
'/resultats/proces-verbaux'  => 'views/modules/resultats/proces_verbaux.php',
'/resultats/deliberations'   => 'views/modules/resultats/deliberations.php',
'/bulletins'                 => 'views/modules/bulletins/consulter.php',
'/bulletins/consulter'       => 'views/modules/bulletins/consulter.php',
'/bulletins/generer'         => 'views/modules/bulletins/generer.php',
'/bulletins/imprimer'        => 'views/modules/bulletins/imprimer.php',
    
    // Emploi du temps
'/emploi-du-temps'           => 'views/modules/emploi/consulter.php',
'/emploi-du-temps/ajouter'   => 'views/modules/emploi/consulter.php',

// Absences
'/absences'                  => 'views/modules/absences/liste.php',
'/absences/marquer'          => 'views/modules/absences/marquer.php',
'/absences/justifier'        => 'views/modules/absences/justifier.php',
'/absences/liste' => 'views/modules/absences/liste.php',

// Retards
'/retards'                   => 'views/modules/retards/liste.php',
'/retards/enregistrer'       => 'views/modules/retards/enregistrer.php',
    
    // Discipline
    '/discipline'                => 'views/modules/discipline/liste.php',
    '/discipline/ajouter'        => 'views/modules/discipline/ajouter.php',
    '/discipline/sanctions'      => 'views/modules/discipline/sanctions.php',
    
    // Paiements
    '/paiements'                 => 'views/modules/paiements/liste.php',
    '/paiements/enregistrer'     => 'views/modules/paiements/enregistrer.php',
    '/paiements/impayes'         => 'views/modules/paiements/impayes.php',
    '/paiements/factures'        => 'views/modules/paiements/factures.php',
    '/paiements/recu'            => 'views/modules/paiements/recu.php',
    
    // Frais scolaires
    '/frais-scolaires'           => 'views/modules/frais/liste.php',
    
    // Cantine
    '/cantine'                   => 'views/modules/cantine/liste.php',
    
    // Transport
    '/transport'                 => 'views/modules/transport/liste.php',
    
    // Calendrier
    '/calendrier'                => 'views/modules/calendrier/index.php',
    '/calendrier/ajouter'        => 'views/modules/calendrier/ajouter.php',
    
    // Messages
    '/messages'                  => 'views/modules/messages/boite.php',
    '/messages/envoyer'          => 'views/modules/messages/envoyer.php',
    
    // Documents
    '/documents'                 => 'views/modules/documents/liste.php',
    '/documents/upload'          => 'views/modules/documents/upload.php',
    
    // Notifications
    '/notifications'             => 'views/modules/notifications/liste.php',
    '/notifications/envoyer'     => 'views/modules/notifications/envoyer.php',
    
    // Rapports
    '/rapports/effectifs'        => 'views/modules/rapports/effectifs.php',
    '/rapports/statistiques'     => 'views/modules/rapports/statistiques.php',
    '/rapports/financiers'       => 'views/modules/rapports/financiers.php',
    '/rapports/personnalises'    => 'views/modules/rapports/personnalises.php',
    
    // Paramètres
    '/parametres'                => 'views/modules/parametres/ecole.php',
    '/parametres/annee'          => 'views/modules/parametres/annee.php',
    
    // Sauvegardes
    '/sauvegardes'               => 'views/modules/sauvegardes/liste.php',
    
    // Logs
    '/logs'                      => 'views/modules/logs/liste.php',
    
    // Profil
    '/profil'                    => 'views/modules/utilisateurs/profil.php',
    '/profil/modifier'           => 'views/modules/utilisateurs/modifier.php',
    '/profil/mot-de-passe'       => 'views/modules/utilisateurs/changer_mot_de_passe.php',
    
    // Années scolaires
    '/annees-scolaires'          => 'views/modules/annees/liste.php',
    '/annees-scolaires/ajouter'  => 'views/modules/annees/ajouter.php',
    
    // Aide
    '/aide'                      => 'views/modules/aide/index.php',
    
    // Pages statiques
    '/confidentialite'           => 'views/modules/static/confidentialite.php',
    '/conditions'                => 'views/modules/static/conditions.php',
    '/contact'                   => 'views/modules/static/contact.php',
    
    

 
    // ============================================================
// ROUTES ENSEIGNANT
// ============================================================
'/enseignant/eleves'        => 'views/enseignant/modules/eleves.php',
'/enseignant/notes'         => 'views/enseignant/modules/notes.php',
'/enseignant/absences'      => 'views/enseignant/modules/absences.php',
'/enseignant/emploi'        => 'views/enseignant/modules/emploi.php',
'/enseignant/resultats'     => 'views/enseignant/modules/resultats.php',
'/enseignant/bulletins'     => 'views/enseignant/modules/bulletins.php',

// ============================================================
// ROUTES PARENT
// ============================================================
'/parent/enfants'       => 'views/parent/modules/enfants.php',
'/parent/bulletins'     => 'views/parent/modules/bulletins.php',
'/parent/absences'      => 'views/parent/modules/absences.php',
'/parent/paiements'     => 'views/parent/modules/paiements.php',
'/parent/retards'       => 'views/parent/modules/retards.php',

// ============================================================
// ROUTES COMPTABLE
// ============================================================
'/comptable/paiements'  => 'views/comptable/modules/paiements.php',
'/comptable/enregistrer' => 'views/comptable/modules/enregistrer.php',
'/comptable/impayes'    => 'views/comptable/modules/impayes.php',
'/comptable/frais'      => 'views/comptable/modules/frais.php',
'/comptable/rapport-financier' => 'views/comptable/modules/rapport_financier.php',


'/documents/telecharger/(\d+)' => function($matches) {
    require_once SRC_PATH . 'controllers/DocumentController.php';
    (new DocumentController())->telecharger((int)$matches[1]);
},


'/sauvegardes/telecharger/(\d+)'  => function($matches) {
    require_once SRC_PATH . 'controllers/SauvegardeController.php';
    (new SauvegardeController())->telecharger((int)$matches[1]);
},

    // --- AJOUTE TES ROUTES GOOGLE ICI ---
    '/auth/google' => function() {
        require_once SRC_PATH . 'controllers/AuthController.php';
        $authController = new AuthController();
        $authController->redirectToGoogle();
    },
    
    '/auth/google-callback' => function() {
        require_once SRC_PATH . 'services/GoogleAuthService.php';
        require_once SRC_PATH . 'controllers/AuthController.php';

        if (isset($_GET['error'])) {
            redirect(BASE_URL . 'views/auth/login.php?error=google_cancelled');
        }

        $state = $_GET['state'] ?? '';
        if (empty($state) || $state !== ($_SESSION['oauth2state'] ?? '')) {
            unset($_SESSION['oauth2state']);
            redirect(BASE_URL . 'views/auth/login.php?error=invalid_state');
        }
        unset($_SESSION['oauth2state']);

        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            redirect(BASE_URL . 'views/auth/login.php?error=no_code');
        }

        $googleService = new GoogleAuthService();
        $accessToken   = $googleService->getAccessToken($code);

        if (!$accessToken) {
            redirect(BASE_URL . 'views/auth/login.php?error=token_failed');
        }

        $googleUser = $googleService->getUserInfo($accessToken);

        if (!$googleUser) {
            redirect(BASE_URL . 'views/auth/login.php?error=userinfo_failed');
        }

        $authController = new AuthController();
        $authController->handleGoogleCallback($googleUser);
    }



];

// ============================================================
// ROUTES DYNAMIQUES (avec paramètres)
// ============================================================
$dynamicRoutes = [
    // Rôle : /roles/gerer/5
    '#^/roles/gerer/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/roles/gerer.php';
    },
    // Utilisateur fiche : /utilisateurs/fiche/7
    '#^/utilisateurs/fiche/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/utilisateurs/fiche.php';
    },
    // Utilisateur modifier : /utilisateurs/modifier/7
    '#^/utilisateurs/modifier/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/utilisateurs/modifier.php';
    },
    // Élève fiche : /eleves/fiche/15
    '#^/eleves/fiche/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/eleves/fiche.php';
    },
    // Élève modifier : /eleves/modifier/15
    '#^/eleves/modifier/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/eleves/modifier.php';
    },
    // Enseignant fiche : /enseignants/fiche/3
    '#^/enseignants/fiche/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/enseignants/fiche.php';
    },
    // Enseignant modifier : /enseignants/modifier/3
    '#^/enseignants/modifier/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/enseignants/modifier.php';
    },
    // Parent fiche : /parents/fiche/8
    '#^/parents/fiche/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/parents/fiche.php';
    },
    // Parent modifier : /parents/modifier/8
    '#^/parents/modifier/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/parents/modifier.php';
    },
    // Matière modifier : /matieres/modifier/10
    '#^/matieres/modifier/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/matieres/modifier.php';
    },
    // Année scolaire modifier : /annees-scolaires/modifier/3
    '#^/annees-scolaires/modifier/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/annees/modifier.php';
    },
    // Classe détail : /classes/detail/13
    '#^/classes/detail/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/classes/detail.php';
    },
    // Classe modifier : /classes/modifier/13
    '#^/classes/modifier/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/classes/modifier.php';
    },
    // Message conversation : /messages/5
    '#^/messages/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/messages/conversation.php';
    },
    // Paiement reçu : /paiements/recu/12
    '#^/paiements/recu/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/paiements/recu.php';
    },
    // Bulletin élève : /bulletins/eleve/25
    '#^/bulletins/eleve/(\d+)$#' => function($matches) {
        $_GET['eleve_id'] = $matches[1];
        require_once __DIR__ . '/views/modules/bulletins/consulter.php';
    },
    // Notes saisie : /notes/saisie/113
    '#^/notes/saisie/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/notes/saisie.php';
    },
    // Notes modifier : /notes/ajouter/113
    '#^/notes/ajouter/(\d+)$#' => function($matches) {
        $_GET['id'] = $matches[1];
        require_once __DIR__ . '/views/modules/notes/ajouter.php';
    },
];


// ============================================================
// ROUTAGE
// ============================================================
// ============================================================
// ROUTAGE (CORRIGÉ)
// ============================================================
if (isset($routes[$route])) {
    $target = $routes[$route];

    // Si la route est une fonction anonyme (Closure) comme pour Google OAuth
    if (is_callable($target)) {
        $target();
    } 
    // Si la route est un chemin vers un fichier PHP statique
    elseif (is_string($target)) {
        require_once __DIR__ . '/' . $target;
    }
} else {
    $matched = false;
    foreach ($dynamicRoutes as $pattern => $handler) {
        if (preg_match($pattern, $route, $matches)) {
            // Si c'est une fonction (Closure)
            if (is_callable($handler)) {
                $handler($matches);
            } 
            // Si c'est un chemin vers un fichier PHP statique (string)
            elseif (is_string($handler)) {
                $_GET['matches'] = $matches; // Passe les paramètres regex
                require_once __DIR__ . '/' . $handler;
            }
            
            $matched = true;
            break;
        }
    }
    if (!$matched) {
        // Servir les fichiers statiques
        $filePath = __DIR__ . $route;
        if (file_exists($filePath) && is_file($filePath)) {
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
                'csv'   => 'text/csv',
                'xls'   => 'application/vnd.ms-excel',
                'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];

            if (isset($mimeTypes[$extension])) {
                header('Content-Type: ' . $mimeTypes[$extension]);
            }

            $cacheExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'];
            if (in_array($extension, $cacheExtensions)) {
                $cacheTime = 604800;
                header('Cache-Control: public, max-age=' . $cacheTime);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
            }

            readfile($filePath);
            exit;
        }

        // Page 404
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 — Page introuvable</title>
            <link rel="stylesheet" href="/gestion_scolaire/public/assets/fontawesome/css/all.min.css">
            <style>
                *{margin:0;padding:0;box-sizing:border-box}
                body{
                    font-family:'Poppins',sans-serif;
                    background:linear-gradient(135deg,#2c3e90,#1a274f);
                    min-height:100vh;display:flex;align-items:center;justify-content:center;
                    color:#fff;text-align:center;padding:20px;
                }
                .error-code{font-size:8rem;font-weight:800;opacity:0.2;line-height:1}
                .error-title{font-size:1.5rem;font-weight:600;margin:16px 0}
                .error-message{opacity:0.7;margin-bottom:30px}
                .btn-home{
                    display:inline-flex;align-items:center;gap:8px;
                    padding:12px 28px;background:#fff;color:#2c3e90;
                    border-radius:50px;text-decoration:none;font-weight:600;transition:all 0.3s;
                }
                .btn-home:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(0,0,0,0.3)}
            </style>
        </head>
        <body>
            <div>
                <div class="error-code">404</div>
                <h1 class="error-title"><i class="fa-solid fa-map-signs"></i> Page introuvable</h1>
                <p class="error-message">La page que vous recherchez n'existe pas.</p>
                <a href="/gestion_scolaire/dashboard" class="btn-home">
                    <i class="fa-solid fa-house"></i> Retour au tableau de bord
                </a>
            </div>
        </body>
        </html>
        <?php
    }
}
