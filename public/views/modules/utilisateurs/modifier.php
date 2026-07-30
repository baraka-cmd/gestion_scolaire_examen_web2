<?php
/**
 * Fichier : public/views/modules/utilisateurs/modifier.php
 * Rôle    : Formulaire de modification d'un utilisateur
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('utilisateurs.modifier');

global $db;

// Récupérer l'ID depuis l'URL
$userId = (int) ($_GET['id'] ?? 0);

if ($userId <= 0) {
    setFlash('error', 'ID utilisateur invalide.');
    redirect('/gestion_scolaire/utilisateurs');
}

// Empêcher la modification de son propre compte via cette interface
if ($userId === (int) ($_SESSION['utilisateur_id'] ?? 0)) {
    setFlash('warning', 'Pour modifier votre propre profil, utilisez la page Mon Profil.');
    redirect('/gestion_scolaire/profil');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'UtilisateurController.php';
$userController = new UtilisateurController();

// Récupérer les informations de l'utilisateur
$utilisateur = $db->fetchOne(
    "SELECT u.*, r.nom AS role_nom
     FROM utilisateurs u
     INNER JOIN roles r ON u.role_id = r.id
     WHERE u.id = :id",
    ['id' => $userId]
);

if (!$utilisateur) {
    setFlash('error', 'Utilisateur introuvable.');
    redirect('/gestion_scolaire/utilisateurs');
}

// Récupérer les rôles disponibles
$roles = $userController->getRoles();

// Messages
$successMessage = $_SESSION['flash']['message'] ?? '';
$errorMessage   = '';
$errors         = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = post('nom', '');
    $prenom    = post('prenom', '');
    $email     = post('email', '');
    $telephone = post('telephone', '');
    $login     = post('login', '');
    $roleId    = (int) post('role_id', 0);
    $password  = $_POST['password'] ?? '';

    // Validation
    if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
    elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';

    if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
    elseif (mb_strlen($prenom) < 2) $errors['prenom'] = 'Minimum 2 caractères.';

    if (isBlank($email)) $errors['email'] = 'L\'email est obligatoire.';
    elseif (!isValidEmail($email)) $errors['email'] = 'Email invalide.';
    else {
        // Vérifier unicité email
        $emailExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM utilisateurs WHERE email = :email AND id != :id",
            ['email' => mb_strtolower(trim($email)), 'id' => $userId]
        );
        if ($emailExiste > 0) $errors['email'] = 'Cet email est déjà utilisé.';
    }

    if (!isBlank($telephone) && !isValidPhone($telephone)) {
        $errors['telephone'] = 'Numéro invalide.';
    }

    if (isBlank($login)) $errors['login'] = 'Le login est obligatoire.';
    else {
        $loginExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM utilisateurs WHERE login = :login AND id != :id",
            ['login' => mb_strtolower(trim($login)), 'id' => $userId]
        );
        if ($loginExiste > 0) $errors['login'] = 'Ce login est déjà utilisé.';
    }

    if ($roleId <= 0) $errors['role_id'] = 'Le rôle est obligatoire.';

    // Validation mot de passe (optionnel)
    if (!isBlank($password)) {
        if (mb_strlen($password) < 8) $errors['password'] = 'Minimum 8 caractères.';
        elseif (!preg_match('/[A-Z]/', $password)) $errors['password'] = 'Au moins une majuscule.';
        elseif (!preg_match('/[a-z]/', $password)) $errors['password'] = 'Au moins une minuscule.';
        elseif (!preg_match('/[0-9]/', $password)) $errors['password'] = 'Au moins un chiffre.';
        elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'\"\\|,.<>\/?~`]/', $password)) $errors['password'] = 'Au moins un caractère spécial.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $db->update(
                "UPDATE utilisateurs 
                 SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
                     login = :login, role_id = :role_id
                 WHERE id = :id",
                [
                    'nom'       => formatNom($nom),
                    'prenom'    => formatNom($prenom),
                    'email'     => mb_strtolower(trim($email)),
                    'telephone' => $telephone ?: null,
                    'login'     => mb_strtolower(trim($login)),
                    'role_id'   => $roleId,
                    'id'        => $userId
                ]
            );

            // Changer le mot de passe si fourni
            if (!isBlank($password)) {
                $hashedPassword = password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
                $db->update(
                    "UPDATE utilisateurs SET mot_de_passe = :password WHERE id = :id",
                    ['password' => $hashedPassword, 'id' => $userId]
                );
            }

            $db->commit();

            logActivity('Modification', 'utilisateurs', $userId, 
                "Modification de l'utilisateur {$nom} {$prenom}");

            setFlash('success', 'Utilisateur modifié avec succès.');
            redirect('/gestion_scolaire/utilisateurs/fiche/' . $userId);

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Erreur lors de la modification.';
            error_log('Erreur modification utilisateur: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Modifier — ' . $utilisateur['prenom'] . ' ' . $utilisateur['nom'];
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Modifier les informations de l\'utilisateur';
$extraCSS = ['modules/utilisateurs.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/utilisateurs">Utilisateurs</a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/utilisateurs/fiche/<?= $userId ?>"><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></a>
    <span class="separator">/</span>
    <span class="current">Modifier</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier l'utilisateur
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Modification du compte de <strong><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></strong>
        </p>
    </div>
    <div class="d-flex gap-sm">
        <a href="/gestion_scolaire/utilisateurs/fiche/<?= $userId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-eye"></i> Voir la fiche
        </a>
        <a href="/gestion_scolaire/utilisateurs" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Liste
        </a>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($errorMessage) ?></span>
    </div>
<?php endif; ?>

<div class="user-form-card">
    <div class="user-form-header">
        <h5>
            <div class="user-avatar-placeholder blue" style="display:inline-flex;width:32px;height:32px;font-size:0.7rem;margin-right:8px;">
                <?= mb_strtoupper(mb_substr($utilisateur['prenom'], 0, 1) . mb_substr($utilisateur['nom'], 0, 1)) ?>
            </div>
            <?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?>
        </h5>
    </div>
    <div class="user-form-body">
        <form method="POST" action="" autocomplete="off" novalidate>

            <!-- Section Identité -->
            <p class="form-section-title">
                <i class="fa-solid fa-id-card"></i> Identité
            </p>
            <div class="form-row">
                <div class="form-group">
                    <label for="nom" class="form-label">
                        <i class="fa-solid fa-user"></i> Nom <span class="required">*</span>
                    </label>
                    <input type="text" id="nom" name="nom" 
                           class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                           value="<?= e($_POST['nom'] ?? $utilisateur['nom']) ?>" required maxlength="100">
                    <?php if (isset($errors['nom'])): ?>
                        <span class="invalid-feedback"><?= e($errors['nom']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="prenom" class="form-label">
                        <i class="fa-solid fa-user"></i> Prénom <span class="required">*</span>
                    </label>
                    <input type="text" id="prenom" name="prenom" 
                           class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                           value="<?= e($_POST['prenom'] ?? $utilisateur['prenom']) ?>" required maxlength="100">
                    <?php if (isset($errors['prenom'])): ?>
                        <span class="invalid-feedback"><?= e($errors['prenom']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section Contact & Connexion -->
            <hr class="form-divider">
            <p class="form-section-title">
                <i class="fa-solid fa-address-book"></i> Contact & Connexion
            </p>
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fa-solid fa-envelope"></i> Email <span class="required">*</span>
                </label>
                <input type="email" id="email" name="email" 
                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       value="<?= e($_POST['email'] ?? $utilisateur['email']) ?>" required maxlength="150">
                <?php if (isset($errors['email'])): ?>
                    <span class="invalid-feedback"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="telephone" class="form-label">
                    <i class="fa-solid fa-phone"></i> Téléphone <span style="font-weight:400;color:var(--color-gray-400);">(optionnel)</span>
                </label>
                <input type="tel" id="telephone" name="telephone" 
                       class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                       value="<?= e($_POST['telephone'] ?? $utilisateur['telephone'] ?? '') ?>" maxlength="20" placeholder="+243XXXXXXXXX">
                <?php if (isset($errors['telephone'])): ?>
                    <span class="invalid-feedback"><?= e($errors['telephone']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="login" class="form-label">
                    <i class="fa-solid fa-keyboard"></i> Login <span class="required">*</span>
                </label>
                <input type="text" id="login" name="login" 
                       class="form-control <?= isset($errors['login']) ? 'is-invalid' : '' ?>"
                       value="<?= e($_POST['login'] ?? $utilisateur['login']) ?>" required maxlength="50">
                <?php if (isset($errors['login'])): ?>
                    <span class="invalid-feedback"><?= e($errors['login']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Section Sécurité -->
            <hr class="form-divider">
            <p class="form-section-title">
                <i class="fa-solid fa-lock"></i> Sécurité <span style="font-weight:400;color:var(--color-gray-400);font-size:0.75rem;">(optionnel — laisser vide pour ne pas changer)</span>
            </p>
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fa-solid fa-key"></i> Nouveau mot de passe
                </label>
                <div class="input-icon-wrapper">
                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" id="password" name="password" 
                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Laisser vide pour conserver l'ancien" minlength="8">
                    <button type="button" class="toggle-password" data-target="#password" title="Afficher/masquer">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <span class="invalid-feedback"><?= e($errors['password']) ?></span>
                <?php endif; ?>
                <small style="color:var(--color-gray-400);font-size:0.72rem;">
                    <i class="fa-solid fa-info-circle"></i> Minimum 8 caractères, avec majuscule, minuscule, chiffre et caractère spécial.
                </small>
            </div>

            <!-- Section Rôle -->
            <hr class="form-divider">
            <p class="form-section-title">
                <i class="fa-solid fa-user-shield"></i> Rôle & Permissions
            </p>
            <div class="form-group">
                <label for="role_id" class="form-label">
                    <i class="fa-solid fa-user-tag"></i> Rôle <span class="required">*</span>
                </label>
                <select id="role_id" name="role_id" class="form-control <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>" required>
                    <option value="">— Sélectionnez un rôle —</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= ($_POST['role_id'] ?? $utilisateur['role_id']) == $role['id'] ? 'selected' : '' ?>>
                            <?= e($role['nom']) ?> — <?= e($role['description']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['role_id'])): ?>
                    <span class="invalid-feedback"><?= e($errors['role_id']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Informations complémentaires -->
            <hr class="form-divider">
            <p class="form-section-title">
                <i class="fa-solid fa-circle-info"></i> Informations complémentaires
            </p>
            <div class="profile-grid">
                <div class="profile-field">
                    <span class="profile-field-label">Date de création</span>
                    <span class="profile-field-value"><?= formatDate($utilisateur['created_at'], 'd/m/Y à H:i') ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Dernière connexion</span>
                    <span class="profile-field-value"><?= $utilisateur['derniere_connexion'] ? formatDateTime($utilisateur['derniere_connexion']) : 'Jamais' ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Statut actuel</span>
                    <span class="profile-field-value">
                        <?php if ($utilisateur['est_actif']): ?>
                            <span class="badge badge-success">Actif</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactif</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Dernière IP</span>
                    <span class="profile-field-value"><?= e($utilisateur['derniere_ip'] ?? '—') ?></span>
                </div>
            </div>
        </form>
    </div>
    <div class="user-form-footer">
        <a href="/gestion_scolaire/utilisateurs/fiche/<?= $userId ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" form="formUpdateUser" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> Enregistrer les modifications
        </button>
    </div>
</div>

<!-- Script pour le toggle mot de passe -->
<script>
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
        const target = document.querySelector(this.dataset.target);
        if (target) {
            const isPassword = target.type === 'password';
            target.type = isPassword ? 'text' : 'password';
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
            target.focus();
        }
    });
});
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>