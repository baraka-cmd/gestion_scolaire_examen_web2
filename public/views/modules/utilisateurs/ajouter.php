<?php
/**
 * Fichier : public/views/modules/utilisateurs/ajouter.php
 * Rôle    : Formulaire d'ajout d'un nouvel utilisateur
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('utilisateurs.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'UtilisateurController.php';
$userController = new UtilisateurController();

// Récupérer les rôles disponibles
$roles = $userController->getRoles();

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldValues      = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues = [
        'nom'       => post('nom', ''),
        'prenom'    => post('prenom', ''),
        'email'     => post('email', ''),
        'telephone' => post('telephone', ''),
        'login'     => post('login', ''),
        'role_id'   => post('role_id', ''),
    ];

    $nom       = $oldValues['nom'];
    $prenom    = $oldValues['prenom'];
    $email     = $oldValues['email'];
    $telephone = $oldValues['telephone'];
    $login     = $oldValues['login'];
    $password  = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $roleId    = (int) post('role_id', 0);

    // Validation
    if (isBlank($nom)) {
        $errors['nom'] = 'Le nom est obligatoire.';
    } elseif (mb_strlen($nom) < 2) {
        $errors['nom'] = 'Minimum 2 caractères.';
    } elseif (mb_strlen($nom) > 100) {
        $errors['nom'] = 'Maximum 100 caractères.';
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\'\-]+$/u', $nom)) {
        $errors['nom'] = 'Caractères non autorisés.';
    }

    if (isBlank($prenom)) {
        $errors['prenom'] = 'Le prénom est obligatoire.';
    } elseif (mb_strlen($prenom) < 2) {
        $errors['prenom'] = 'Minimum 2 caractères.';
    } elseif (mb_strlen($prenom) > 100) {
        $errors['prenom'] = 'Maximum 100 caractères.';
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\'\-]+$/u', $prenom)) {
        $errors['prenom'] = 'Caractères non autorisés.';
    }

    if (isBlank($email)) {
        $errors['email'] = 'L\'email est obligatoire.';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = 'Format d\'email invalide.';
    } elseif (mb_strlen($email) > 150) {
        $errors['email'] = 'Maximum 150 caractères.';
    } else {
        $emailExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM utilisateurs WHERE email = :email",
            ['email' => mb_strtolower(trim($email))]
        );
        if ($emailExiste > 0) {
            $errors['email'] = 'Cette adresse email est déjà utilisée.';
        }
    }

    if (!isBlank($telephone) && !isValidPhone($telephone)) {
        $errors['telephone'] = 'Format de téléphone invalide (ex: +243XXXXXXXXX).';
    }

    if (isBlank($login)) {
        $errors['login'] = 'Le login est obligatoire.';
    } elseif (mb_strlen($login) < 3) {
        $errors['login'] = 'Minimum 3 caractères.';
    } elseif (mb_strlen($login) > 50) {
        $errors['login'] = 'Maximum 50 caractères.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $login)) {
        $errors['login'] = 'Lettres, chiffres, points, tirets et underscores uniquement.';
    } else {
        $loginExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM utilisateurs WHERE login = :login",
            ['login' => mb_strtolower(trim($login))]
        );
        if ($loginExiste > 0) {
            $errors['login'] = 'Ce login est déjà utilisé.';
        }
    }

    if (isBlank($password)) {
        $errors['password'] = 'Le mot de passe est obligatoire.';
    } elseif (mb_strlen($password) < 8) {
        $errors['password'] = 'Minimum 8 caractères requis.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Au moins une lettre majuscule requise.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = 'Au moins une lettre minuscule requise.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Au moins un chiffre requis.';
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'\"\\|,.<>\/?~`]/', $password)) {
        $errors['password'] = 'Au moins un caractère spécial requis.';
    }

    if (isBlank($confirmPassword)) {
        $errors['confirm_password'] = 'Veuillez confirmer le mot de passe.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
    }

    if ($roleId <= 0) {
        $errors['role_id'] = 'Veuillez sélectionner un rôle.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

            $newUserId = $db->insert(
                "INSERT INTO utilisateurs (nom, prenom, email, telephone, login, mot_de_passe, role_id, est_actif)
                 VALUES (:nom, :prenom, :email, :telephone, :login, :password, :role_id, 1)",
                [
                    'nom'       => formatNom($nom),
                    'prenom'    => formatNom($prenom),
                    'email'     => mb_strtolower(trim($email)),
                    'telephone' => $telephone ?: null,
                    'login'     => mb_strtolower(trim($login)),
                    'password'  => $hashedPassword,
                    'role_id'   => $roleId
                ]
            );

            $db->commit();

            logActivity('Création', 'utilisateurs', $newUserId, 
                "Création de l'utilisateur {$nom} {$prenom} (rôle: {$roleId})");

            setFlash('success', 'Utilisateur créé avec succès.');
            redirect('/gestion_scolaire/utilisateurs/fiche/' . $newUserId);

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Une erreur est survenue lors de la création de l\'utilisateur.';
            error_log('Erreur création utilisateur: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ajouter un utilisateur';
$pageIcon  = 'fa-user-plus';
$pageDescription = 'Créer un nouveau compte utilisateur';
$extraCSS = ['modules/utilisateurs.css', 'modules/ajouter_utilisateur.css'];
$extraJS  = ['modules/ajouter_utilisateur.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/utilisateurs">Utilisateurs</a>
    <span class="separator">/</span>
    <span class="current">Ajouter</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-user-plus text-primary me-2"></i>Ajouter un utilisateur
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Créez un nouveau compte utilisateur avec un rôle et des permissions spécifiques.
        </p>
    </div>
    <a href="/gestion_scolaire/utilisateurs" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Retour à la liste
    </a>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error alert-dismissible">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($errorMessage) ?></span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<div class="add-user-container">
    <div class="add-user-card">
        <div class="add-user-card-header">
            <div class="add-user-card-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <div>
                <h5>Nouvel utilisateur</h5>
                <p>Remplissez tous les champs obligatoires pour créer un compte</p>
            </div>
        </div>
        <div class="add-user-card-body">
            <form method="POST" action="" autocomplete="off" novalidate id="formAddUser">

                <!-- Section Identité -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Identité</h6>
                            <p class="form-section-subtitle">Informations personnelles de l'utilisateur</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom" class="form-label">
                                <i class="fa-solid fa-user"></i> Nom <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                                <input type="text" id="nom" name="nom" 
                                       class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($oldValues['nom'] ?? '') ?>" 
                                       placeholder="Nom de famille" required maxlength="100" autofocus>
                            </div>
                            <?php if (isset($errors['nom'])): ?>
                                <span class="invalid-feedback"><?= e($errors['nom']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="prenom" class="form-label">
                                <i class="fa-solid fa-user"></i> Prénom <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                                <input type="text" id="prenom" name="prenom" 
                                       class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($oldValues['prenom'] ?? '') ?>" 
                                       placeholder="Prénom" required maxlength="100">
                            </div>
                            <?php if (isset($errors['prenom'])): ?>
                                <span class="invalid-feedback"><?= e($errors['prenom']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr class="form-divider">

                <!-- Section Contact -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fa-solid fa-address-book"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Contact</h6>
                            <p class="form-section-subtitle">Coordonnées de l'utilisateur</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fa-solid fa-envelope"></i> Adresse email <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon"><i class="fa-solid fa-at"></i></span>
                            <input type="email" id="email" name="email" 
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($oldValues['email'] ?? '') ?>" 
                                   placeholder="utilisateur@exemple.com" required maxlength="150">
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <span class="invalid-feedback"><?= e($errors['email']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="telephone" class="form-label">
                            <i class="fa-solid fa-phone"></i> Téléphone <span style="font-weight:400;color:var(--color-gray-400);">(optionnel)</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                            <input type="tel" id="telephone" name="telephone" 
                                   class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($oldValues['telephone'] ?? '') ?>" 
                                   placeholder="+243XXXXXXXXX" maxlength="20">
                        </div>
                        <?php if (isset($errors['telephone'])): ?>
                            <span class="invalid-feedback"><?= e($errors['telephone']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="form-divider">

                <!-- Section Connexion -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fa-solid fa-keyboard"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Identifiants de connexion</h6>
                            <p class="form-section-subtitle">Login unique pour accéder au système</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="login" class="form-label">
                            <i class="fa-solid fa-user-tag"></i> Login <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon"><i class="fa-solid fa-keyboard"></i></span>
                            <input type="text" id="login" name="login" 
                                   class="form-control <?= isset($errors['login']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($oldValues['login'] ?? '') ?>" 
                                   placeholder="Nom d'utilisateur unique" required maxlength="50">
                        </div>
                        <?php if (isset($errors['login'])): ?>
                            <span class="invalid-feedback"><?= e($errors['login']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="form-divider">

                <!-- Section Sécurité -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Sécurité</h6>
                            <p class="form-section-subtitle">Choisissez un mot de passe robuste</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fa-solid fa-key"></i> Mot de passe <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" id="password" name="password" 
                                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       placeholder="Minimum 8 caractères" required minlength="8">
                                <button type="button" class="password-toggle-btn" data-target="#password" title="Afficher/masquer">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <span class="invalid-feedback"><?= e($errors['password']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                <i class="fa-solid fa-check-circle"></i> Confirmer <span class="required">*</span>
                            </label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                                       placeholder="Répétez le mot de passe" required minlength="8">
                                <button type="button" class="password-toggle-btn" data-target="#confirm_password" title="Afficher/masquer">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span class="invalid-feedback"><?= e($errors['confirm_password']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Indicateur de force du mot de passe -->
                    <div class="strength-container" id="passwordStrength" style="display:none;">
                        <div class="strength-bar-wrapper">
                            <div class="strength-bar-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-label" id="strengthText"></span>
                    </div>

                    <!-- Exigences du mot de passe -->
                    <div class="requirements-container" id="passwordRequirements" style="display:none;">
                        <div class="requirements-title">
                            <i class="fa-solid fa-shield-halved"></i> Exigences de sécurité
                        </div>
                        <ul class="requirements-list">
                            <li class="requirement-item" id="req-length">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Minimum 8 caractères</span>
                            </li>
                            <li class="requirement-item" id="req-uppercase">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Au moins une lettre majuscule</span>
                            </li>
                            <li class="requirement-item" id="req-lowercase">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Au moins une lettre minuscule</span>
                            </li>
                            <li class="requirement-item" id="req-number">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Au moins un chiffre</span>
                            </li>
                            <li class="requirement-item" id="req-special">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Au moins un caractère spécial</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <hr class="form-divider">

                <!-- Section Rôle -->
                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Rôle & Permissions</h6>
                            <p class="form-section-subtitle">Détermine les accès de l'utilisateur</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="role_id" class="form-label">
                            <i class="fa-solid fa-user-tag"></i> Rôle de l'utilisateur <span class="required">*</span>
                        </label>
                        <select id="role_id" name="role_id" class="form-control <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>" required>
                            <option value="">— Sélectionnez un rôle —</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>" <?= ($oldValues['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                                    <?= e($role['nom']) ?> — <?= e($role['description']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['role_id'])): ?>
                            <span class="invalid-feedback"><?= e($errors['role_id']) ?></span>
                        <?php endif; ?>
                        <div class="form-help">
                            <i class="fa-solid fa-info-circle"></i> Le rôle détermine les permissions et l'accès aux fonctionnalités du système.
                        </div>
                    </div>

                    <!-- Aperçu des permissions -->
                    <div class="permissions-preview" id="rolePermissionsPreview">
                        <div class="permissions-preview-header">
                            <span class="permissions-preview-title">
                                <i class="fa-solid fa-list-check"></i> Aperçu des permissions
                            </span>
                            <span class="permissions-preview-count" id="permissionsCount">0 permission(s)</span>
                        </div>
                        <div class="permissions-module-list" id="permissionsList"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="add-user-card-footer">
            <a href="/gestion_scolaire/utilisateurs" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="submit" form="formAddUser" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i> Créer l'utilisateur
            </button>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>