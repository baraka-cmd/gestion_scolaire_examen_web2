<?php
/**
 * Fichier : public/views/auth/forgot-password.php
 * Rôle    : Formulaire de récupération de mot de passe (page autonome)
 * Système : École primaire congolaise — Authentification
 * Version : 1.1 — Sans header/sidebar
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../src/config/app.php';

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    redirect('/gestion_scolaire/dashboard');
}

$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldEmail       = '';

// Récupérer les paramètres de l'école
$ecoleParams = $_SESSION['ecole_params'] ?? [];
$ecoleNom = $ecoleParams['nom_ecole'] ?? ECOLE_NOM;
$ecoleLogo = $ecoleParams['logo'] ?? '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = post('email', '');

    // Validation
    if (isBlank($oldEmail)) {
        $errors['email'] = 'Veuillez saisir votre adresse email.';
    } elseif (!isValidEmail($oldEmail)) {
        $errors['email'] = 'Veuillez saisir une adresse email valide.';
    }

    if (empty($errors)) {
        // Vérifier si l'utilisateur existe
        $utilisateur = $db->fetchOne(
            "SELECT u.id, u.nom, u.prenom, u.email, u.est_actif, u.login
             FROM utilisateurs u
             WHERE u.email = :email
             LIMIT 1",
            ['email' => mb_strtolower(trim($oldEmail))]
        );

        if ($utilisateur) {
            if (!$utilisateur['est_actif']) {
                $errorMessage = 'Votre compte a été désactivé. Contactez l\'administrateur.';
            } else {
                try {
                    // Générer un nouveau mot de passe aléatoire sécurisé
                    $majuscules = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $minuscules = 'abcdefghijklmnopqrstuvwxyz';
                    $chiffres   = '0123456789';
                    $speciaux   = '!@#$%&*';

                    // Garantir au moins un de chaque type
                    $nouveauMotDePasse  = $majuscules[random_int(0, strlen($majuscules) - 1)];
                    $nouveauMotDePasse .= $minuscules[random_int(0, strlen($minuscules) - 1)];
                    $nouveauMotDePasse .= $chiffres[random_int(0, strlen($chiffres) - 1)];
                    $nouveauMotDePasse .= $speciaux[random_int(0, strlen($speciaux) - 1)];

                    // Compléter avec des caractères aléatoires
                    $tous = $majuscules . $minuscules . $chiffres . $speciaux;
                    for ($i = 4; $i < 12; $i++) {
                        $nouveauMotDePasse .= $tous[random_int(0, strlen($tous) - 1)];
                    }

                    // Mélanger pour plus de sécurité
                    $nouveauMotDePasse = str_shuffle($nouveauMotDePasse);

                    // Hasher le nouveau mot de passe
                    $hashedPassword = password_hash($nouveauMotDePasse, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

                    // Mettre à jour en base de données
                    $db->update(
                        "UPDATE utilisateurs SET mot_de_passe = :password WHERE id = :id",
                        ['password' => $hashedPassword, 'id' => $utilisateur['id']]
                    );

                    // Envoyer l'email avec le nouveau mot de passe
                    $emailEnvoye = false;
                    try {
                        require_once SRC_PATH . 'services' . DIRECTORY_SEPARATOR . 'EmailService.php';
                        $emailService = new EmailService();

                        $sujet = 'Réinitialisation de votre mot de passe — ' . e($ecoleNom);

                        $contenu = '
                        <div style="text-align:center;margin-bottom:25px;">
                            <h3 style="color:#1e293b;margin-top:12px;">Réinitialisation de mot de passe</h3>
                        </div>
                        
                        <p>Bonjour <strong>' . e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) . '</strong>,</p>
                        
                        <p>Vous avez demandé la réinitialisation de votre mot de passe sur la plateforme de gestion scolaire <strong>' . e($ecoleNom) . '</strong>.</p>
                        
                        <div style="background:#f1f5f9;border:2px dashed #2c3e90;border-radius:14px;padding:20px;text-align:center;margin:20px 0;">
                            <p style="margin:0 0 10px 0;font-size:0.85rem;color:#64748b;">Votre nouveau mot de passe :</p>
                            <p style="margin:0;font-size:1.8rem;font-weight:700;color:#2c3e90;letter-spacing:3px;font-family:\'Courier New\',monospace;">' . e($nouveauMotDePasse) . '</p>
                        </div>
                        
                        <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:8px;margin:20px 0;">
                            <p style="margin:0;font-size:0.85rem;color:#92400e;">
                                <strong><i class="fa-solid fa-shield-halved"></i> Recommandation de sécurité :</strong><br>
                                Pour votre sécurité, nous vous recommandons de <strong>changer ce mot de passe</strong> 
                                dès votre prochaine connexion dans la section "Mon Profil".
                            </p>
                        </div>
                        
                        <p style="font-size:0.82rem;color:#64748b;text-align:center;margin-top:25px;">
                            <i class="fa-solid fa-info-circle"></i> Si vous n\'avez pas demandé cette réinitialisation, 
                            veuillez contacter immédiatement l\'administration de l\'école.
                        </p>';

                        $emailEnvoye = $emailService->envoyerTemplate($utilisateur['email'], $sujet, $contenu);
                    } catch (Exception $e) {
                        error_log('Erreur envoi email MDP: ' . $e->getMessage());
                    }

                    if ($emailEnvoye) {
                        logActivity('Réinitialisation MDP', 'utilisateurs', $utilisateur['id'], 
                            "Mot de passe réinitialisé par email");

                        $successMessage = 'Un nouveau mot de passe a été envoyé à votre adresse email. 
                            Vérifiez votre boîte de réception (et les spams).';
                    } else {
                        // Le MDP est changé mais l'email n'est pas parti
                        $successMessage = 'Votre mot de passe a été réinitialisé. 
                            Contactez l\'administrateur si vous ne recevez pas l\'email.';
                        error_log('Échec envoi email réinitialisation MDP à ' . $utilisateur['email']);
                    }

                } catch (Exception $e) {
                    $errorMessage = 'Une erreur technique est survenue. Veuillez réessayer.';
                    error_log('Erreur forgot-password: ' . $e->getMessage());
                }
            }
        } else {
            // Ne pas révéler si l'email existe ou non (sécurité)
            $successMessage = 'Si cette adresse email est associée à un compte, 
                vous recevrez un nouveau mot de passe dans quelques instants.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Récupération de mot de passe — <?= e($ecoleNom) ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e90">

    <title>Mot de passe oublié — <?= e($ecoleNom) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/gestion_scolaire/public/assets/images/favicon.ico">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/fontawesome/css/all.min.css">

    <!-- Styles communs -->
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/css/common/variables.css">
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/css/common/reset.css">
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/css/common/layout.css">

    <!-- Styles de la page -->
    <link rel="stylesheet" href="/gestion_scolaire/public/assets/css/auth/forgot-password.css">

    <style>
        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>

<!-- ============================================================
     CONTENEUR PRINCIPAL
     ============================================================ -->
<div class="forgot-password-wrapper">
    <div class="forgot-password-container">
        
        <!-- Logo et titre -->
        <div class="forgot-password-header">
            <div class="forgot-password-logo">
                <?php 
                $logoPath = UPLOADS_PATH . 'logos' . DIRECTORY_SEPARATOR . basename($ecoleLogo);
                if (!empty($ecoleLogo) && file_exists($logoPath)): 
                ?>
                    <img src="<?= ASSETS_URL . 'uploads/logos/' . basename($ecoleLogo) ?>" alt="Logo" class="forgot-password-logo-img">
                <?php else: ?>
                    <div class="forgot-password-logo-icon">
                        <i class="fa-solid fa-school"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h1><?= e($ecoleNom) ?></h1>
            <p>Récupération de mot de passe</p>
        </div>

        <!-- Messages -->
        <?php if (!empty($successMessage)): ?>
            <div class="forgot-password-success">
                <div class="success-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="success-content">
                    <h5>Email envoyé !</h5>
                    <p><?= nl2br(e($successMessage)) ?></p>
                </div>
                <a href="/gestion_scolaire/login" class="btn btn-primary btn-block mt-3">
                    <i class="fa-solid fa-arrow-left"></i> Retour à la connexion
                </a>
            </div>
        <?php else: ?>
            <!-- Icône cadenas -->
            <div class="forgot-password-icon">
                <i class="fa-solid fa-lock"></i>
            </div>

            <!-- Message d'erreur -->
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= e($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form method="POST" action="" autocomplete="off" novalidate id="formForgotPassword">
                <?= csrfField() ?>

                <p class="forgot-password-instruction">
                    <i class="fa-solid fa-info-circle"></i>
                    Saisissez votre adresse email. Vous recevrez un nouveau mot de passe par email.
                </p>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fa-solid fa-envelope"></i> Adresse email
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-at"></i></span>
                        <input type="email" id="email" name="email" 
                               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldEmail) ?>"
                               placeholder="votre.email@exemple.com"
                               required autofocus autocomplete="email">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <span class="invalid-feedback"><?= e($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="btnSubmit">
                    <i class="fa-solid fa-paper-plane"></i> Envoyer le nouveau mot de passe
                </button>
            </form>
        <?php endif; ?>

        <!-- Lien retour -->
        <div class="forgot-password-footer">
            <a href="/gestion_scolaire/login">
                <i class="fa-solid fa-arrow-left"></i> Retour à la connexion
            </a>
        </div>
    </div>

    <!-- Illustration décorative -->
    <div class="forgot-password-illustration">
        <div class="illustration-content">
            <i class="fa-solid fa-shield-halved illustration-icon"></i>
            <h2>Sécurité renforcée</h2>
            <p>Votre nouveau mot de passe est généré de manière aléatoire et sécurisée.</p>
            <div class="illustration-features">
                <div class="feature-item">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>12 caractères minimum</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Majuscules et minuscules</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Chiffres et caractères spéciaux</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Envoyé par email sécurisé</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="/gestion_scolaire/public/assets/js/auth/forgot-password.js"></script>

</body>
</html>