<?php
/**
 * Fichier : public/views/modules/utilisateurs/profil.php
 * Rôle    : Profil de l'utilisateur connecté (consultation et modification)
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'UtilisateurController.php';
$userController = new UtilisateurController();

// Traitement du formulaire de modification du profil
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateProfile') {
    $nom       = post('nom', '');
    $prenom    = post('prenom', '');
    $email     = post('email', '');
    $telephone = post('telephone', '');

    $errors = [];

    if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
    if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
    if (isBlank($email)) $errors['email'] = 'L\'email est obligatoire.';
    elseif (!isValidEmail($email)) $errors['email'] = 'Email invalide.';

    if (empty($errors)) {
        try {
            $db->update(
                "UPDATE utilisateurs 
                 SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone
                 WHERE id = :id",
                [
                    'nom'       => formatNom($nom),
                    'prenom'    => formatNom($prenom),
                    'email'     => mb_strtolower(trim($email)),
                    'telephone' => $telephone ?: null,
                    'id'        => $_SESSION['utilisateur_id']
                ]
            );

            // Mettre à jour la session
            $_SESSION['utilisateur_nom']    = formatNom($nom);
            $_SESSION['utilisateur_prenom'] = formatNom($prenom);
            $_SESSION['utilisateur_email']  = mb_strtolower(trim($email));

            logActivity('Modification', 'utilisateurs', $_SESSION['utilisateur_id'], 'Mise à jour du profil');
            $successMessage = 'Profil mis à jour avec succès.';

        } catch (Exception $e) {
            $errorMessage = 'Erreur lors de la mise à jour.';
            error_log('Erreur update profil: ' . $e->getMessage());
        }
    }
}

// Récupérer les informations actuelles
$userId = $_SESSION['utilisateur_id'];
$utilisateur = $db->fetchOne(
    "SELECT u.*, r.nom AS role_nom, r.niveau_acces, r.description AS role_description
     FROM utilisateurs u
     INNER JOIN roles r ON u.role_id = r.id
     WHERE u.id = :id",
    ['id' => $userId]
);

// Statistiques personnelles
$statsPersonnelles = [
    'total_connexions' => (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM logs_activites WHERE utilisateur_id = :uid AND action = 'Connexion'",
        ['uid' => $userId]
    ),
    'total_actions' => (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM logs_activites WHERE utilisateur_id = :uid AND action IN ('Création', 'Modification', 'Suppression')",
        ['uid' => $userId]
    ),
    'messages_non_lus' => (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM messages WHERE destinataire_id = :uid AND est_lu = 0 AND est_supprime_destinataire = 0",
        ['uid' => $userId]
    ),
];

$derniereConnexion = $db->fetchColumn(
    "SELECT date_action FROM logs_activites WHERE utilisateur_id = :uid AND action = 'Connexion' ORDER BY date_action DESC LIMIT 1 OFFSET 1",
    ['uid' => $userId]
);

$pageTitle = 'Mon profil';
$pageIcon  = 'fa-user';
$pageDescription = 'Gérez vos informations personnelles';
$extraCSS = ['modules/utilisateurs.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Mon profil</span>
</nav>

<!-- Messages -->
<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= e($successMessage) ?></span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error alert-dismissible">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($errorMessage) ?></span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<div class="bottom-grid">
    <!-- Carte profil -->
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar-placeholder-large">
                <?= mb_strtoupper(mb_substr($utilisateur['prenom'], 0, 1) . mb_substr($utilisateur['nom'], 0, 1)) ?>
            </div>
            <div class="profile-header-info">
                <h3><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></h3>
                <p>
                    <span class="role-badge <?= strtolower($utilisateur['role_nom']) ?>">
                        <i class="fa-solid fa-circle"></i> <?= e($utilisateur['role_nom']) ?>
                    </span>
                </p>
                <p style="font-size:0.82rem;opacity:0.8;">
                    <?= e($utilisateur['role_description']) ?>
                </p>
                <p style="font-size:0.75rem;opacity:0.7;">
                    <i class="fa-solid fa-calendar"></i> Membre depuis le <?= formatDate($utilisateur['created_at'], 'd/m/Y') ?>
                    <?php if ($derniereConnexion): ?>
                        <span class="mx-2">|</span>
                        <i class="fa-solid fa-clock"></i> Dernière connexion : <?= formatDateTime($derniereConnexion) ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="profile-body">
            <!-- Formulaire de modification -->
            <div class="profile-section">
                <h5 class="profile-section-title">
                    <i class="fa-solid fa-pen-to-square"></i> Modifier mes informations
                </h5>
                <form method="POST" action="" autocomplete="off">
                    <input type="hidden" name="action" value="updateProfile">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="profil_nom" class="form-label">
                                <i class="fa-solid fa-user"></i> Nom <span class="required">*</span>
                            </label>
                            <input type="text" id="profil_nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" 
                                   value="<?= e($_POST['nom'] ?? $utilisateur['nom']) ?>" required maxlength="100">
                            <?php if (isset($errors['nom'])): ?>
                                <span class="invalid-feedback"><?= e($errors['nom']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="profil_prenom" class="form-label">
                                <i class="fa-solid fa-user"></i> Prénom <span class="required">*</span>
                            </label>
                            <input type="text" id="profil_prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($_POST['prenom'] ?? $utilisateur['prenom']) ?>" required maxlength="100">
                            <?php if (isset($errors['prenom'])): ?>
                                <span class="invalid-feedback"><?= e($errors['prenom']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="profil_email" class="form-label">
                                <i class="fa-solid fa-envelope"></i> Email <span class="required">*</span>
                            </label>
                            <input type="email" id="profil_email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($_POST['email'] ?? $utilisateur['email']) ?>" required maxlength="150">
                            <?php if (isset($errors['email'])): ?>
                                <span class="invalid-feedback"><?= e($errors['email']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="profil_telephone" class="form-label">
                                <i class="fa-solid fa-phone"></i> Téléphone <span style="font-weight:400;color:var(--color-gray-400);">(optionnel)</span>
                            </label>
                            <input type="tel" id="profil_telephone" name="telephone" class="form-control"
                                   value="<?= e($_POST['telephone'] ?? $utilisateur['telephone'] ?? '') ?>" maxlength="20" placeholder="+243XXXXXXXXX">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-keyboard"></i> Login
                        </label>
                        <input type="text" class="form-control" value="<?= e($utilisateur['login']) ?>" disabled style="background:#f1f5f9;">
                        <small style="color:var(--color-gray-400);font-size:0.7rem;">
                            <i class="fa-solid fa-info-circle"></i> Le login ne peut pas être modifié. Contactez un administrateur si nécessaire.
                        </small>
                    </div>

                    <div class="d-flex gap-sm mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Enregistrer les modifications
                        </button>
                        <a href="/gestion_scolaire/profil/mot-de-passe" class="btn btn-outline-primary">
                            <i class="fa-solid fa-lock"></i> Changer mon mot de passe
                        </a>
                    </div>
                </form>
            </div>

            <!-- Informations du compte -->
            <div class="profile-section">
                <h5 class="profile-section-title">
                    <i class="fa-solid fa-circle-info"></i> Informations du compte
                </h5>
                <div class="profile-grid">
                    <div class="profile-field">
                        <span class="profile-field-label">Rôle</span>
                        <span class="profile-field-value">
                            <span class="role-badge <?= strtolower($utilisateur['role_nom']) ?>">
                                <i class="fa-solid fa-circle"></i> <?= e($utilisateur['role_nom']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="profile-field">
                        <span class="profile-field-label">Niveau d'accès</span>
                        <span class="profile-field-value"><?= $utilisateur['niveau_acces'] ?>/10</span>
                    </div>
                    <div class="profile-field">
                        <span class="profile-field-label">Date de création</span>
                        <span class="profile-field-value"><?= formatDate($utilisateur['created_at'], 'd/m/Y à H:i') ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="profile-field-label">Dernière connexion</span>
                        <span class="profile-field-value"><?= $utilisateur['derniere_connexion'] ? formatDateTime($utilisateur['derniere_connexion']) : 'Jamais' ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="profile-field-label">Statut</span>
                        <span class="profile-field-value">
                            <?php if ($utilisateur['est_actif']): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Actif</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fa-solid fa-times-circle"></i> Inactif</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="profile-field">
                        <span class="profile-field-label">Permissions</span>
                        <span class="profile-field-value">Gérées par votre rôle</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte statistiques -->
    <div>
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fa-solid fa-chart-simple"></i> Mes statistiques</h5>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:#f8fafc;border-radius:10px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                                <i class="fa-solid fa-right-to-bracket" style="color:#3b82f6;"></i>
                            </div>
                            <span style="font-weight:500;">Total connexions</span>
                        </div>
                        <strong style="font-size:1.3rem;color:#3b82f6;"><?= $statsPersonnelles['total_connexions'] ?></strong>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:#f8fafc;border-radius:10px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:#d1fae5;display:flex;align-items:center;justify-content:center;">
                                <i class="fa-solid fa-pen-to-square" style="color:#10b981;"></i>
                            </div>
                            <span style="font-weight:500;">Actions effectuées</span>
                        </div>
                        <strong style="font-size:1.3rem;color:#10b981;"><?= $statsPersonnelles['total_actions'] ?></strong>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:#f8fafc;border-radius:10px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                                <i class="fa-solid fa-envelope" style="color:#ef4444;"></i>
                            </div>
                            <span style="font-weight:500;">Messages non lus</span>
                        </div>
                        <strong style="font-size:1.3rem;color:#ef4444;"><?= $statsPersonnelles['messages_non_lus'] ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sécurité -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fa-solid fa-shield-halved"></i> Sécurité</h5>
            </div>
            <div class="card-body">
                <p style="font-size:0.85rem;color:var(--color-gray-600);margin-bottom:16px;">
                    <i class="fa-solid fa-info-circle"></i> Pour des raisons de sécurité, changez régulièrement votre mot de passe.
                </p>
                <a href="/gestion_scolaire/profil/mot-de-passe" class="btn btn-outline-primary btn-block">
                    <i class="fa-solid fa-lock"></i> Changer mon mot de passe
                </a>
            </div>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>