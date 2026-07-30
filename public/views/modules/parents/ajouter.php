<?php
/**
 * Fichier : public/views/modules/parents/ajouter.php
 * Rôle    : Formulaire d'ajout d'un parent/tuteur avec liaison aux élèves
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('parents.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ParentController.php';
$parentController = new ParentController();

// Récupérer les liens de parenté et les élèves disponibles
$liens = $parentController->getLiens();

// Récupérer les élèves disponibles (non liés à un parent)
$elevesDisponibles = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe,
            c.nom AS classe_nom
     FROM eleves e
     LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     LEFT JOIN classes c ON i.classe_id = c.id
     WHERE e.est_actif = 1
     ORDER BY e.nom, e.prenom"
);

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldValues      = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues = [
        'nom'                    => post('nom', ''),
        'prenom'                 => post('prenom', ''),
        'lien'                   => post('lien', ''),
        'profession'             => post('profession', ''),
        'telephone_principal'    => post('telephone_principal', ''),
        'telephone_secondaire'   => post('telephone_secondaire', ''),
        'email'                  => post('email', ''),
        'adresse'                => post('adresse', ''),
        'est_contact_urgence'    => post('est_contact_urgence', '1'),
        'creer_compte'           => post('creer_compte', '0'),
    ];

    $nom              = $oldValues['nom'];
    $prenom           = $oldValues['prenom'];
    $lien             = $oldValues['lien'];
    $profession       = $oldValues['profession'];
    $telephone        = $oldValues['telephone_principal'];
    $telephone2       = $oldValues['telephone_secondaire'];
    $email            = $oldValues['email'];
    $adresse          = $oldValues['adresse'];
    $estUrgence       = (int) $oldValues['est_contact_urgence'];
    $creerCompte      = (int) $oldValues['creer_compte'];
    $elevesLies       = $_POST['eleves_lies'] ?? [];

    // Validation
    if (isBlank($nom)) {
        $errors['nom'] = 'Le nom est obligatoire.';
    } elseif (mb_strlen($nom) < 2) {
        $errors['nom'] = 'Minimum 2 caractères.';
    }

    if (isBlank($prenom)) {
        $errors['prenom'] = 'Le prénom est obligatoire.';
    } elseif (mb_strlen($prenom) < 2) {
        $errors['prenom'] = 'Minimum 2 caractères.';
    }

    if (isBlank($telephone)) {
        $errors['telephone_principal'] = 'Le téléphone principal est obligatoire.';
    } elseif (!isValidPhone($telephone)) {
        $errors['telephone_principal'] = 'Format de téléphone invalide.';
    } else {
        $telExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM parents_tuteurs WHERE telephone_principal = :tel",
            ['tel' => $telephone]
        );
        if ($telExiste > 0) $errors['telephone_principal'] = 'Ce numéro est déjà utilisé par un autre parent.';
    }

    if (!isBlank($email) && !isValidEmail($email)) {
        $errors['email'] = 'Format d\'email invalide.';
    }

    if (!isBlank($telephone2) && !isValidPhone($telephone2)) {
        $errors['telephone_secondaire'] = 'Format de téléphone secondaire invalide.';
    }

    if (isBlank($lien)) {
        $errors['lien'] = 'Le lien de parenté est obligatoire.';
    }

    // Vérifier que l'email n'est pas déjà utilisé si création de compte
    if ($creerCompte && !empty($email)) {
        $emailExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM utilisateurs WHERE email = :email",
            ['email' => $email]
        );
        if ($emailExiste > 0) {
            $errors['email'] = 'Cet email est déjà utilisé par un compte utilisateur.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insérer le parent
            $parentId = $db->insert(
                "INSERT INTO parents_tuteurs (nom, prenom, lien, profession, telephone_principal, 
                     telephone_secondaire, email, adresse, est_contact_urgence)
                 VALUES (:nom, :prenom, :lien, :prof, :tel1, :tel2, :email, :adresse, :urgence)",
                [
                    'nom'      => formatNom($nom),
                    'prenom'   => formatNom($prenom),
                    'lien'     => $lien,
                    'prof'     => $profession ?: null,
                    'tel1'     => $telephone,
                    'tel2'     => $telephone2 ?: null,
                    'email'    => $email ?: null,
                    'adresse'  => $adresse ?: null,
                    'urgence'  => $estUrgence
                ]
            );

            // Créer un compte utilisateur si demandé
            if ($creerCompte && !empty($email)) {
                $login = 'parent.' . slugify($prenom) . '.' . slugify($nom);
                $hashedPassword = password_hash('Parent@2025', PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

                $userId = $db->insert(
                    "INSERT INTO utilisateurs (nom, prenom, email, login, mot_de_passe, role_id, est_actif)
                     VALUES (:nom, :prenom, :email, :login, :pass, 6, 1)",
                    [
                        'nom'    => formatNom($nom),
                        'prenom' => formatNom($prenom),
                        'email'  => $email,
                        'login'  => $login,
                        'pass'   => $hashedPassword
                    ]
                );

                $db->update(
                    "UPDATE parents_tuteurs SET utilisateur_id = :uid WHERE id = :pid",
                    ['uid' => $userId, 'pid' => $parentId]
                );
            }

            // Lier les élèves sélectionnés
            if (!empty($elevesLies)) {
                foreach ($elevesLies as $index => $eleveId) {
                    $eleveId = (int) trim($eleveId);
                    if ($eleveId > 0) {
                        $db->query(
                            "INSERT INTO eleve_parent (eleve_id, parent_id, est_tuteur_principal) 
                             VALUES (:eid, :pid, :principal)",
                            ['eid' => $eleveId, 'pid' => $parentId, 'principal' => $index === 0 ? 1 : 0]
                        );
                    }
                }
            }

            $db->commit();

            logActivity('Création', 'parents_tuteurs', $parentId, "Parent créé : {$nom} {$prenom}");

            if ($creerCompte && !empty($email)) {
                setFlash('success', 'Parent créé avec succès. Un compte utilisateur a été créé (login : ' . $login . ', mot de passe : Parent@2025).');
            } else {
                setFlash('success', 'Parent créé avec succès.');
            }
            redirect('/gestion_scolaire/parents/fiche/' . $parentId);

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Une erreur est survenue lors de la création du parent.';
            error_log('Erreur création parent: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ajouter un parent';
$pageIcon  = 'fa-user-plus';
$pageDescription = 'Ajouter un nouveau parent ou tuteur';
$extraCSS = ['modules/parents.css', 'modules/ajouter_parent.css'];
$extraJS  = ['modules/ajouter_parent.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/parents">Parents</a>
    <span class="separator">/</span>
    <span class="current">Ajouter</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-user-plus text-primary me-2"></i>Ajouter un parent
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Ajoutez un nouveau parent ou tuteur et liez-le à ses enfants.
        </p>
    </div>
    <a href="/gestion_scolaire/parents" class="btn btn-outline-primary btn-sm">
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

<form method="POST" action="" autocomplete="off" novalidate id="formAddParent">

    <!-- ============================================================
         SECTION 1 : IDENTITÉ
         ============================================================ -->
    <div class="parent-form-card mb-4">
        <div class="parent-form-header">
            <div class="parent-form-icon"><i class="fa-solid fa-id-card"></i></div>
            <div><h5>Identité du parent</h5><p>Informations personnelles</p></div>
        </div>
        <div class="parent-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="nom" class="form-label"><i class="fa-solid fa-user"></i> Nom <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['nom'] ?? '') ?>" placeholder="Nom de famille" required maxlength="100" autofocus>
                    </div>
                    <?php if (isset($errors['nom'])): ?><span class="invalid-feedback"><?= e($errors['nom']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="prenom" class="form-label"><i class="fa-solid fa-user"></i> Prénom <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['prenom'] ?? '') ?>" placeholder="Prénom" required maxlength="100">
                    </div>
                    <?php if (isset($errors['prenom'])): ?><span class="invalid-feedback"><?= e($errors['prenom']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="lien" class="form-label"><i class="fa-solid fa-link"></i> Lien de parenté <span class="required">*</span></label>
                    <select id="lien" name="lien" class="form-control <?= isset($errors['lien']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez —</option>
                        <?php foreach ($liens as $l): ?>
                            <option value="<?= $l ?>" <?= ($oldValues['lien'] ?? '') === $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['lien'])): ?><span class="invalid-feedback"><?= e($errors['lien']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="profession" class="form-label"><i class="fa-solid fa-briefcase"></i> Profession</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-briefcase"></i></span>
                        <input type="text" id="profession" name="profession" class="form-control" value="<?= e($oldValues['profession'] ?? '') ?>" placeholder="Profession" maxlength="150">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 2 : CONTACT
         ============================================================ -->
    <div class="parent-form-card mb-4">
        <div class="parent-form-header">
            <div class="parent-form-icon"><i class="fa-solid fa-address-book"></i></div>
            <div><h5>Coordonnées</h5><p>Informations de contact</p></div>
        </div>
        <div class="parent-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="telephone_principal" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone principal <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" id="telephone_principal" name="telephone_principal" class="form-control <?= isset($errors['telephone_principal']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['telephone_principal'] ?? '') ?>" placeholder="+243XXXXXXXXX" required maxlength="20">
                    </div>
                    <?php if (isset($errors['telephone_principal'])): ?><span class="invalid-feedback"><?= e($errors['telephone_principal']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="telephone_secondaire" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone secondaire</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" id="telephone_secondaire" name="telephone_secondaire" class="form-control <?= isset($errors['telephone_secondaire']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['telephone_secondaire'] ?? '') ?>" placeholder="+243XXXXXXXXX" maxlength="20">
                    </div>
                    <?php if (isset($errors['telephone_secondaire'])): ?><span class="invalid-feedback"><?= e($errors['telephone_secondaire']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-at"></i></span>
                        <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['email'] ?? '') ?>" placeholder="parent@exemple.com" maxlength="150">
                    </div>
                    <?php if (isset($errors['email'])): ?><span class="invalid-feedback"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="adresse" class="form-label"><i class="fa-solid fa-house"></i> Adresse</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <input type="text" id="adresse" name="adresse" class="form-control" value="<?= e($oldValues['adresse'] ?? '') ?>" placeholder="Adresse complète" maxlength="255">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-phone-volume"></i> Type de contact</label>
                <select id="est_contact_urgence" name="est_contact_urgence" class="form-control">
                    <option value="1" <?= ($oldValues['est_contact_urgence'] ?? '1') === '1' ? 'selected' : '' ?>>
                        <i class="fa-solid fa-phone"></i> Contact d'urgence prioritaire
                    </option>
                    <option value="0" <?= ($oldValues['est_contact_urgence'] ?? '') === '0' ? 'selected' : '' ?>>
                        Contact secondaire
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 3 : COMPTE UTILISATEUR
         ============================================================ -->
    <div class="parent-form-card mb-4">
        <div class="parent-form-header">
            <div class="parent-form-icon"><i class="fa-solid fa-user-gear"></i></div>
            <div><h5>Compte utilisateur</h5><p>Accès à la plateforme</p></div>
        </div>
        <div class="parent-form-body">
            <div class="form-group">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="creer_compte" id="creer_compte" value="1" <?= ($oldValues['creer_compte'] ?? '') === '1' ? 'checked' : '' ?>>
                    <span class="checkbox-custom"><i class="fa-solid fa-check"></i></span>
                    <span class="checkbox-label">Créer un compte utilisateur pour ce parent</span>
                </label>
                <small class="form-help" style="margin-top:8px;">
                    <i class="fa-solid fa-info-circle"></i> Un compte permettra au parent de se connecter et suivre ses enfants. 
                    Le mot de passe par défaut sera <strong>Parent@2025</strong>. L'email est requis pour créer le compte.
                </small>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 4 : ENFANTS LIÉS
         ============================================================ -->
    <div class="parent-form-card mb-4">
        <div class="parent-form-header">
            <div class="parent-form-icon"><i class="fa-solid fa-children"></i></div>
            <div><h5>Enfants liés</h5><p>Sélectionnez les enfants de ce parent</p></div>
        </div>
        <div class="parent-form-body">
            <div class="form-group">
                <div class="search-wrapper mb-3">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchEleves" class="form-control" placeholder="Rechercher un élève..." style="padding-left:36px;">
                </div>
            </div>
            <div class="eleves-selection" id="elevesContainer">
                <?php if (empty($elevesDisponibles)): ?>
                    <div class="empty-state" style="padding:20px;">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <p>Aucun élève disponible</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($elevesDisponibles as $eleve): ?>
                        <label class="eleves-selection-item">
                            <input type="checkbox" name="eleves_lies[]" value="<?= $eleve['id'] ?>">
                            <div class="eleves-selection-info">
                                <div class="eleves-selection-name"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></div>
                                <div class="eleves-selection-detail">
                                    <?= e($eleve['matricule']) ?> — <?= e($eleve['classe_nom'] ?? 'Non inscrit') ?> — 
                                    <?= $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille' ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
            <small class="form-help" style="margin-top:8px;">
                <i class="fa-solid fa-info-circle"></i> Le premier enfant sélectionné sera défini comme tuteur principal. Vous pourrez modifier cela plus tard.
            </small>
        </div>
    </div>

    <!-- Boutons -->
    <div class="d-flex justify-content-end gap-sm mb-4">
        <a href="/gestion_scolaire/parents" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-user-plus"></i> Créer le parent
        </button>
    </div>
</form>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>