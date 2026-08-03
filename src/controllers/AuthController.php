<?php
/**
 * Fichier : src/controllers/AuthController.php
 * Rôle    : Traitement backend de l'authentification (login + register)
 */

defined('ACCESS') or die('Accès interdit');

class AuthController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ============================================================
    // LOGIN
    // ============================================================

    /**
     * Traite la connexion d'un utilisateur
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Méthode non autorisée.', [], 405);
        }

        $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(false, 'Session expirée. Veuillez rafraîchir la page.');
        }

        $email    = post('email');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        $errors = [];

        if (isBlank($email)) {
            $errors['email'] = 'L\'adresse email est obligatoire.';
        } elseif (!isValidEmail($email)) {
            $errors['email'] = 'Adresse email invalide.';
        }

        if (isBlank($password)) {
            $errors['password'] = 'Le mot de passe est obligatoire.';
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $sql = "SELECT u.id, u.nom, u.prenom, u.email, u.login, u.mot_de_passe, 
                           u.photo, u.est_actif, u.tentatives_echecs, u.bloque_jusqua,
                           r.nom AS role_nom, r.niveau_acces
                    FROM utilisateurs u
                    INNER JOIN roles r ON u.role_id = r.id
                    WHERE u.email = :email
                    LIMIT 1";

            $utilisateur = $this->db->fetchOne($sql, ['email' => $email]);

            if (!$utilisateur) {
                $this->logFailedAttempt(null, $email);
                jsonResponse(false, 'Email ou mot de passe incorrect.');
            }

            if (!$utilisateur['est_actif']) {
                jsonResponse(false, 'Votre compte a été désactivé. Contactez l\'administrateur.');
            }

            if (!empty($utilisateur['bloque_jusqua'])) {
                $bloqueJusqua = strtotime($utilisateur['bloque_jusqua']);
                if ($bloqueJusqua > time()) {
                    $tempsRestant = ceil(($bloqueJusqua - time()) / 60);
                    jsonResponse(false, "Compte temporairement bloqué. Réessayez dans {$tempsRestant} minute(s).");
                } else {
                    $this->resetLoginAttempts($utilisateur['id']);
                }
            }

            if (!password_verify($password, $utilisateur['mot_de_passe'])) {
                $this->incrementLoginAttempts($utilisateur);
                jsonResponse(false, 'Email ou mot de passe incorrect.');
            }

            if (password_needs_rehash($utilisateur['mot_de_passe'], PASSWORD_ALGO, ['cost' => PASSWORD_COST])) {
                $this->updatePasswordHash($utilisateur['id'], $password);
            }

            $this->handleSuccessfulLogin($utilisateur, $remember);

            logActivity('Connexion', 'utilisateurs', $utilisateur['id'], 'Connexion réussie');

            $redirect = '/gestion_scolaire/dashboard';

            jsonResponse(true, 'Connexion réussie. Redirection en cours...', [
                'redirect' => $redirect
            ]);

        } catch (Exception $e) {
            error_log('Erreur login : ' . $e->getMessage());
            jsonResponse(false, 'Une erreur technique est survenue.');
        }
    }

    // ============================================================
    // REGISTER
    // ============================================================

    /**
     * Traite l'inscription d'un nouvel utilisateur
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Méthode non autorisée.', [], 405);
        }

        $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(false, 'Session expirée. Veuillez rafraîchir la page.');
        }

        // Récupérer les champs
        $nom             = post('nom');
        $prenom          = post('prenom');
        $email           = post('email');
        $telephone       = post('telephone');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $roleId          = (int) post('role_id', 0);
        $terms           = isset($_POST['terms']);

        // Validation
        $errors = [];

        // Nom
        if (isBlank($nom)) {
            $errors['nom'] = 'Le nom est obligatoire.';
        } elseif (mb_strlen($nom) < 2) {
            $errors['nom'] = 'Minimum 2 caractères.';
        } elseif (mb_strlen($nom) > 100) {
            $errors['nom'] = 'Maximum 100 caractères.';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\'\-]+$/u', $nom)) {
            $errors['nom'] = 'Caractères non autorisés.';
        }

        // Prénom
        if (isBlank($prenom)) {
            $errors['prenom'] = 'Le prénom est obligatoire.';
        } elseif (mb_strlen($prenom) < 2) {
            $errors['prenom'] = 'Minimum 2 caractères.';
        } elseif (mb_strlen($prenom) > 100) {
            $errors['prenom'] = 'Maximum 100 caractères.';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\'\-]+$/u', $prenom)) {
            $errors['prenom'] = 'Caractères non autorisés.';
        }

        // Email
        if (isBlank($email)) {
            $errors['email'] = 'L\'adresse email est obligatoire.';
        } elseif (!isValidEmail($email)) {
            $errors['email'] = 'Adresse email invalide.';
        } elseif (mb_strlen($email) > 150) {
            $errors['email'] = 'Maximum 150 caractères.';
        } else {
            // Vérifier l'unicité de l'email
            $emailExists = $this->db->exists(
                "SELECT id FROM utilisateurs WHERE email = :email",
                ['email' => $email]
            );
            if ($emailExists) {
                $errors['email'] = 'Cette adresse email est déjà utilisée.';
            }
        }

        // Téléphone (optionnel mais validé si fourni)
        if (!isBlank($telephone)) {
            if (!isValidPhone($telephone)) {
                $errors['telephone'] = 'Numéro de téléphone invalide.';
            }
        }

        // Mot de passe
        if (isBlank($password)) {
            $errors['password'] = 'Le mot de passe est obligatoire.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'] = 'Minimum 8 caractères.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Au moins une lettre majuscule.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = 'Au moins une lettre minuscule.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Au moins un chiffre.';
        } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'\"\\|,.<>\/?~`]/', $password)) {
            $errors['password'] = 'Au moins un caractère spécial.';
        }

        // Confirmation
        if (isBlank($confirmPassword)) {
            $errors['confirm_password'] = 'Veuillez confirmer le mot de passe.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
        }

        // Rôle
        $allowedRoles = [4, 6]; // Enseignant (4) ou Parent (6) seulement à l'inscription
        if (!in_array($roleId, $allowedRoles)) {
            $errors['role_id'] = 'Rôle invalide.';
        }

        // Conditions
        if (!$terms) {
            $errors['terms'] = 'Vous devez accepter les conditions d\'utilisation.';
        }

        // Retourner les erreurs s'il y en a
        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        // Créer l'utilisateur
        try {
            $this->db->beginTransaction();

            // Générer le login à partir de l'email
            $login = $this->generateUniqueLogin($nom, $prenom);

            // Hasher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

            // Insérer l'utilisateur
            $userId = $this->db->insert(
                "INSERT INTO utilisateurs (nom, prenom, email, telephone, login, mot_de_passe, role_id, est_actif)
                 VALUES (:nom, :prenom, :email, :telephone, :login, :password, :role_id, 1)",
                [
                    'nom'       => formatNom($nom),
                    'prenom'    => formatNom($prenom),
                    'email'     => mb_strtolower(trim($email)),
                    'telephone' => $telephone ?: null,
                    'login'     => $login,
                    'password'  => $hashedPassword,
                    'role_id'   => $roleId
                ]
            );

            $this->db->commit();

            // Journaliser
            logActivity('Création', 'utilisateurs', $userId, "Inscription de {$nom} {$prenom}");

            // Message de succès
            $roleNom = $roleId === 4 ? 'Enseignant' : 'Parent';
            jsonResponse(true, "Inscription réussie ! Votre compte {$roleNom} a été créé. Vous pouvez maintenant vous connecter.", [
                'redirect' => '/gestion_scolaire/login?created=1'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur inscription : ' . $e->getMessage());

            // Vérifier si c'est une erreur de doublon (login)
            if (str_contains($e->getMessage(), 'login')) {
                jsonResponse(false, 'Ce login est déjà utilisé. Veuillez réessayer.');
            }

            jsonResponse(false, 'Une erreur technique est survenue lors de l\'inscription.');
        }
    }

    // ============================================================
    // LOGOUT
    // ============================================================

    /**
     * Déconnecte l'utilisateur
     */
    public function logout(): void
    {
        if (isLoggedIn()) {
            logActivity('Déconnexion', 'utilisateurs', $_SESSION['utilisateur_id'], 'Déconnexion réussie');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        redirect(BASE_URL . 'views/auth/login.php?logout=success');
    }

    /**
     * Vérifie si l'utilisateur est connecté (pour AJAX)
     */
    public function checkSession(): void
    {
        if (!isLoggedIn()) {
            jsonResponse(false, 'Session expirée.', [], 401);
        }

        $sessionTime = $_SESSION['session_time'] ?? 0;
        if (time() - $sessionTime > SESSION_TIMEOUT) {
            $this->logout();
        }

        $_SESSION['session_time'] = time();
        jsonResponse(true, 'Session active.');
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    private function handleSuccessfulLogin(array $utilisateur, bool $remember): void
    {
        $this->resetLoginAttempts($utilisateur['id']);

        $this->db->update(
            "UPDATE utilisateurs 
             SET derniere_connexion = NOW(), 
                 derniere_ip = :ip,
                 tentatives_echecs = 0,
                 bloque_jusqua = NULL
             WHERE id = :id",
            [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'id' => $utilisateur['id']
            ]
        );

        session_regenerate_id(true);

        $_SESSION['utilisateur_id']       = $utilisateur['id'];
        $_SESSION['utilisateur_nom']      = $utilisateur['nom'];
        $_SESSION['utilisateur_prenom']   = $utilisateur['prenom'];
        $_SESSION['utilisateur_email']    = $utilisateur['email'];
        $_SESSION['utilisateur_login']    = $utilisateur['login'];
        $_SESSION['utilisateur_role']     = $utilisateur['role_nom'];
        $_SESSION['utilisateur_photo']    = $utilisateur['photo'];
        $_SESSION['utilisateur_niveau']   = $utilisateur['niveau_acces'];
        $_SESSION['session_time']         = time();

        if ($remember) {
            $this->setRememberMeCookie($utilisateur['id']);
        }
    }

    private function incrementLoginAttempts(array $utilisateur): void
    {
        $tentatives = $utilisateur['tentatives_echecs'] + 1;
        $bloqueJusqua = null;

        if ($tentatives >= MAX_LOGIN_ATTEMPTS) {
            $bloqueJusqua = date('Y-m-d H:i:s', time() + LOGIN_BLOCK_DURATION);
        }

        $this->db->update(
            "UPDATE utilisateurs SET tentatives_echecs = :tentatives, bloque_jusqua = :bloque WHERE id = :id",
            [
                'tentatives' => $tentatives,
                'bloque'     => $bloqueJusqua,
                'id'         => $utilisateur['id']
            ]
        );

        logActivity('Tentative échouée', 'utilisateurs', $utilisateur['id'],
            "Tentative {$tentatives}/" . MAX_LOGIN_ATTEMPTS);
    }

    private function resetLoginAttempts(int $userId): void
    {
        $this->db->update(
            "UPDATE utilisateurs SET tentatives_echecs = 0, bloque_jusqua = NULL WHERE id = :id",
            ['id' => $userId]
        );
    }

    private function updatePasswordHash(int $userId, string $password): void
    {
        $newHash = password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
        $this->db->update(
            "UPDATE utilisateurs SET mot_de_passe = :hash WHERE id = :id",
            ['hash' => $newHash, 'id' => $userId]
        );
    }

    private function logFailedAttempt(?int $userId, string $email): void
    {
        try {
            $this->db->query(
                "INSERT INTO logs_activites (utilisateur_id, action, description, adresse_ip, navigateur)
                 VALUES (:uid, 'Tentative échouée', :description, :ip, :navigateur)",
                [
                    'uid'         => $userId,
                    'description' => "Tentative de connexion avec email: {$email}",
                    'ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'navigateur'  => $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
                ]
            );
        } catch (Exception $e) {
            error_log('Erreur logFailedAttempt: ' . $e->getMessage());
        }
    }

        /**
     * Traite l'authentification / Inscription via Google
     */
        /**
     * Traite l'authentification / Inscription via Google
     */
    public function handleGoogleCallback(array $googleUser): void
    {
        $googleId = $googleUser['sub'] ?? null;
        $email    = mb_strtolower(trim($googleUser['email'] ?? ''));
        $nom      = $googleUser['family_name'] ?? 'Utilisateur';
        $prenom   = $googleUser['given_name'] ?? 'Google';
        $picture  = $googleUser['picture'] ?? null;

        if (!$googleId || !$email) {
            header('Location: ' . BASE_URL . 'views/auth/login.php?error=google_data_missing');
            exit();
        }

        try {
            // 1. Chercher si l'utilisateur existe déjà via son google_id
            $sql = "SELECT u.id, u.nom, u.prenom, u.email, u.login, u.photo, u.est_actif, 
                           u.tentatives_echecs, u.bloque_jusqua, r.nom AS role_nom, r.niveau_acces
                    FROM utilisateurs u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.google_id = :google_id LIMIT 1";

            $utilisateur = $this->db->fetchOne($sql, ['google_id' => $googleId]);

            // 2. Si non trouvé par google_id, vérifier par adresse email
            if (!$utilisateur) {
                $sqlByEmail = "SELECT u.id, u.nom, u.prenom, u.email, u.login, u.photo, u.est_actif, 
                                      u.tentatives_echecs, u.bloque_jusqua, r.nom AS role_nom, r.niveau_acces
                               FROM utilisateurs u
                               LEFT JOIN roles r ON u.role_id = r.id
                               WHERE u.email = :email LIMIT 1";

                $utilisateur = $this->db->fetchOne($sqlByEmail, ['email' => $email]);

                if ($utilisateur) {
                    // Compte existe : Lier le google_id et mettre à jour la photo si absente
                    $this->db->update(
                        "UPDATE utilisateurs SET google_id = :google_id WHERE id = :id",
                        ['google_id' => $googleId, 'id' => $utilisateur['id']]
                    );
                } else {
                    // 3. Aucun compte n'existe : CRÉATION AUTOMATIQUE (Inscription)
                    $this->db->beginTransaction();

                    $login  = $this->generateUniqueLogin($nom, $prenom);
                    $roleId = 6; // Assure-toi que l'ID de rôle 6 existe bien dans la table `roles` !

                    $userId = $this->db->insert(
                        "INSERT INTO utilisateurs (nom, prenom, email, login, google_id, photo, role_id, est_actif)
                         VALUES (:nom, :prenom, :email, :login, :google_id, :photo, :role_id, 1)",
                        [
                            'nom'       => formatNom($nom),
                            'prenom'    => formatNom($prenom),
                            'email'     => $email,
                            'login'     => $login,
                            'google_id' => $googleId,
                            'photo'     => $picture,
                            'role_id'   => $roleId
                        ]
                    );

                    $this->db->commit();
                    logActivity('Création via Google', 'utilisateurs', $userId, "Nouvelle inscription Google : {$email}");

                    // Récupérer les données complètes avec le rôle
                    $utilisateur = $this->db->fetchOne($sql, ['google_id' => $googleId]);
                }
            }

            // Vérification de la validité du compte
            if (isset($utilisateur['est_actif']) && !$utilisateur['est_actif']) {
                header('Location: ' . BASE_URL . 'views/auth/login.php?error=account_disabled');
                exit();
            }

            // Connexion effective (Remplissage de la SESSION)
            $this->handleSuccessfulLogin($utilisateur, true);
            logActivity('Connexion Google', 'utilisateurs', $utilisateur['id'], 'Connexion réussie via Google OAuth');

            // Redirection vers le tableau de bord
            header('Location: ' . BASE_URL . 'views/dashboard/');
            exit();

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log('Erreur handleGoogleCallback : ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'views/auth/login.php?error=technical');
            exit();
        }
    }



    private function setRememberMeCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expire = time() + (30 * 24 * 3600);

        setcookie(
            'remember_token',
            $userId . ':' . $token,
            [
                'expires'  => $expire,
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Génère un login unique à partir du nom et prénom
     */
    private function generateUniqueLogin(string $nom, string $prenom): string
    {
        // Nettoyer et formater
        $nomClean    = slugify($nom);
        $prenomClean = slugify($prenom);

        // Base du login : première lettre prénom + nom
        $baseLogin = mb_substr($prenomClean, 0, 1) . $nomClean;

        // Tronquer à 20 caractères max
        $baseLogin = mb_substr($baseLogin, 0, 20);

        // Vérifier l'unicité et ajouter un suffixe si nécessaire
        $login = $baseLogin;
        $counter = 1;

        while ($this->db->exists("SELECT id FROM utilisateurs WHERE login = :login", ['login' => $login])) {
            $login = $baseLogin . $counter;
            $counter++;
        }

        return $login;
    }


    /**
 * Redirige l'utilisateur vers la page d'authentification de Google
 */
public function redirectToGoogle(): void
{
    require_once SRC_PATH . 'services/GoogleAuthService.php';
    $googleService = new GoogleAuthService();
    $authUrl = $googleService->getAuthUrl();

    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit();
}


}