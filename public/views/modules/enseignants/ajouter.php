<?php
/**
 * Fichier : public/views/modules/enseignants/ajouter.php
 * Rôle    : Formulaire d'ajout d'un nouvel enseignant
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('enseignants.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EnseignantController.php';
$enseignantController = new EnseignantController();

// Récupérer les classes disponibles (sans titulaire)
$classesDisponibles = $db->fetchAll(
    "SELECT c.id, c.nom, c.niveau, c.salle
     FROM classes c
     WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
     AND c.id NOT IN (SELECT COALESCE(classe_id, 0) FROM enseignants WHERE est_actif = 1)
     ORDER BY c.niveau"
);

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldValues      = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues = [
        'nom'            => post('nom', ''),
        'prenom'         => post('prenom', ''),
        'date_naissance' => post('date_naissance', ''),
        'sexe'           => post('sexe', ''),
        'adresse'        => post('adresse', ''),
        'telephone'      => post('telephone', ''),
        'email'          => post('email', ''),
        'diplome'        => post('diplome', ''),
        'specialite'     => post('specialite', ''),
        'date_embauche'  => post('date_embauche', date('Y-m-d')),
        'classe_id'      => post('classe_id', ''),
        'creer_compte'   => post('creer_compte', '0'),
    ];

    $nom            = $oldValues['nom'];
    $prenom         = $oldValues['prenom'];
    $dateNaissance  = $oldValues['date_naissance'];
    $sexe           = $oldValues['sexe'];
    $adresse        = $oldValues['adresse'];
    $telephone      = $oldValues['telephone'];
    $email          = $oldValues['email'];
    $diplome        = $oldValues['diplome'];
    $specialite     = $oldValues['specialite'];
    $dateEmbauche   = $oldValues['date_embauche'];
    $classeId       = (int) $oldValues['classe_id'];
    $creerCompte    = (int) $oldValues['creer_compte'];

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

    if (isBlank($dateNaissance)) {
        $errors['date_naissance'] = 'La date de naissance est obligatoire.';
    } elseif (!isValidDate($dateNaissance)) {
        $errors['date_naissance'] = 'Date invalide.';
    }

    if (!in_array($sexe, ['M', 'F'])) {
        $errors['sexe'] = 'Veuillez sélectionner le sexe.';
    }

    if (!isBlank($telephone) && !isValidPhone($telephone)) {
        $errors['telephone'] = 'Format de téléphone invalide.';
    } elseif (!isBlank($telephone)) {
        $telExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM enseignants WHERE telephone = :tel",
            ['tel' => $telephone]
        );
        if ($telExiste > 0) $errors['telephone'] = 'Ce numéro est déjà utilisé.';
    }

    if (!isBlank($email) && !isValidEmail($email)) {
        $errors['email'] = 'Format d\'email invalide.';
    } elseif (!isBlank($email)) {
        $emailExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM enseignants WHERE email = :email",
            ['email' => $email]
        );
        if ($emailExiste > 0) $errors['email'] = 'Cet email est déjà utilisé.';
    }

    // Vérifier que l'email est fourni si création de compte
    if ($creerCompte && isBlank($email)) {
        $errors['email'] = 'L\'email est obligatoire pour créer un compte utilisateur.';
    } elseif ($creerCompte && !isBlank($email)) {
        $emailUserExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM utilisateurs WHERE email = :email",
            ['email' => $email]
        );
        if ($emailUserExiste > 0) $errors['email'] = 'Cet email est déjà utilisé par un compte utilisateur.';
    }

    // Vérifier la classe
    if ($classeId > 0) {
        $classeDejaPrise = $db->fetchColumn(
            "SELECT COUNT(*) FROM enseignants WHERE classe_id = :cid AND est_actif = 1",
            ['cid' => $classeId]
        );
        if ($classeDejaPrise > 0) $errors['classe_id'] = 'Cette classe a déjà un titulaire.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Générer le matricule
            $matricule = 'ENS-' . date('y') . '-' . str_pad(((int) $db->fetchColumn("SELECT MAX(id) FROM enseignants") + 1), 3, '0', STR_PAD_LEFT);

            // Insérer l'enseignant
            $enseignantId = $db->insert(
                "INSERT INTO enseignants (matricule, nom, prenom, date_naissance, sexe, adresse, telephone, 
                     email, diplome, specialite, date_embauche, classe_id, est_actif)
                 VALUES (:mat, :nom, :prenom, :dn, :sexe, :adr, :tel, :email, :diplome, :spe, :de, :cid, 1)",
                [
                    'mat'     => $matricule,
                    'nom'     => formatNom($nom),
                    'prenom'  => formatNom($prenom),
                    'dn'      => $dateNaissance,
                    'sexe'    => $sexe,
                    'adr'     => $adresse ?: null,
                    'tel'     => $telephone ?: null,
                    'email'   => $email ?: null,
                    'diplome' => $diplome ?: null,
                    'spe'     => $specialite ?: null,
                    'de'      => $dateEmbauche ?: date('Y-m-d'),
                    'cid'     => $classeId > 0 ? $classeId : null
                ]
            );

            // Créer un compte utilisateur si demandé
            $login = '';
            if ($creerCompte && !empty($email)) {
                $login = 'ens.' . slugify($prenom) . '.' . slugify($nom);
                $baseLogin = $login;
                $i = 1;
                while ($db->fetchColumn("SELECT COUNT(*) FROM utilisateurs WHERE login = :login", ['login' => $login]) > 0) {
                    $login = $baseLogin . $i;
                    $i++;
                }

                $hashedPassword = password_hash('Enseignant@2025', PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

                $userId = $db->insert(
                    "INSERT INTO utilisateurs (nom, prenom, email, login, mot_de_passe, role_id, est_actif)
                     VALUES (:nom, :prenom, :email, :login, :pass, 4, 1)",
                    [
                        'nom'    => formatNom($nom),
                        'prenom' => formatNom($prenom),
                        'email'  => $email,
                        'login'  => $login,
                        'pass'   => $hashedPassword
                    ]
                );

                $db->update(
                    "UPDATE enseignants SET utilisateur_id = :uid WHERE id = :eid",
                    ['uid' => $userId, 'eid' => $enseignantId]
                );
            }

            $db->commit();

            logActivity('Création', 'enseignants', $enseignantId, "Enseignant créé : {$nom} {$prenom}");

            $msg = 'Enseignant créé avec succès. Matricule : ' . $matricule . '.';
            if ($creerCompte && !empty($email)) {
                $msg .= ' Compte créé (login : ' . $login . ', mot de passe : Enseignant@2025).';
            }
            setFlash('success', $msg);
            redirect('/gestion_scolaire/enseignants/fiche/' . $enseignantId);

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Une erreur est survenue lors de la création.';
            error_log('Erreur création enseignant: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ajouter un enseignant';
$pageIcon  = 'fa-user-plus';
$pageDescription = 'Ajouter un nouvel enseignant';
$extraCSS = ['modules/enseignants.css', 'modules/ajouter_enseignant.css'];
$extraJS  = ['modules/ajouter_enseignant.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/enseignants">Enseignants</a>
    <span class="separator">/</span>
    <span class="current">Ajouter</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-user-plus text-primary me-2"></i>Ajouter un enseignant
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Ajoutez un nouvel enseignant et assignez-lui une classe titulaire.
        </p>
    </div>
    <a href="/gestion_scolaire/enseignants" class="btn btn-outline-primary btn-sm">
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

<form method="POST" action="" autocomplete="off" novalidate id="formAddEnseignant">

    <!-- SECTION 1 : IDENTITÉ -->
    <div class="enseignant-form-card mb-4">
        <div class="enseignant-form-header">
            <div class="enseignant-form-icon"><i class="fa-solid fa-id-card"></i></div>
            <div><h5>Identité</h5><p>Informations personnelles de l'enseignant</p></div>
        </div>
        <div class="enseignant-form-body">
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
                    <label for="date_naissance" class="form-label"><i class="fa-solid fa-calendar"></i> Date de naissance <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-cake-candles"></i></span>
                        <input type="date" id="date_naissance" name="date_naissance" class="form-control <?= isset($errors['date_naissance']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['date_naissance'] ?? '') ?>" required>
                    </div>
                    <?php if (isset($errors['date_naissance'])): ?><span class="invalid-feedback"><?= e($errors['date_naissance']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="sexe" class="form-label"><i class="fa-solid fa-venus-mars"></i> Sexe <span class="required">*</span></label>
                    <select id="sexe" name="sexe" class="form-control <?= isset($errors['sexe']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez —</option>
                        <option value="M" <?= ($oldValues['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= ($oldValues['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                    </select>
                    <?php if (isset($errors['sexe'])): ?><span class="invalid-feedback"><?= e($errors['sexe']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2 : COORDONNÉES -->
    <div class="enseignant-form-card mb-4">
        <div class="enseignant-form-header">
            <div class="enseignant-form-icon"><i class="fa-solid fa-address-book"></i></div>
            <div><h5>Coordonnées</h5><p>Informations de contact</p></div>
        </div>
        <div class="enseignant-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="telephone" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" id="telephone" name="telephone" class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['telephone'] ?? '') ?>" placeholder="+243XXXXXXXXX" maxlength="20">
                    </div>
                    <?php if (isset($errors['telephone'])): ?><span class="invalid-feedback"><?= e($errors['telephone']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-at"></i></span>
                        <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['email'] ?? '') ?>" placeholder="enseignant@ecole.edu" maxlength="150">
                    </div>
                    <?php if (isset($errors['email'])): ?><span class="invalid-feedback"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="adresse" class="form-label"><i class="fa-solid fa-house"></i> Adresse</label>
                <div class="input-icon-wrapper">
                    <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <input type="text" id="adresse" name="adresse" class="form-control" value="<?= e($oldValues['adresse'] ?? '') ?>" placeholder="Adresse complète" maxlength="255">
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3 : CARRIÈRE -->
    <div class="enseignant-form-card mb-4">
        <div class="enseignant-form-header">
            <div class="enseignant-form-icon"><i class="fa-solid fa-graduation-cap"></i></div>
            <div><h5>Carrière</h5><p>Diplômes, spécialité et affectation</p></div>
        </div>
        <div class="enseignant-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="diplome" class="form-label"><i class="fa-solid fa-scroll"></i> Diplôme</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-certificate"></i></span>
                        <input type="text" id="diplome" name="diplome" class="form-control" value="<?= e($oldValues['diplome'] ?? '') ?>" placeholder="Ex: Licence en Sciences de l'Éducation" maxlength="150">
                    </div>
                </div>
                <div class="form-group">
                    <label for="specialite" class="form-label"><i class="fa-solid fa-star"></i> Spécialité</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-award"></i></span>
                        <input type="text" id="specialite" name="specialite" class="form-control" value="<?= e($oldValues['specialite'] ?? '') ?>" placeholder="Ex: Mathématiques, Français..." maxlength="150">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="date_embauche" class="form-label"><i class="fa-solid fa-calendar-check"></i> Date d'embauche</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                        <input type="date" id="date_embauche" name="date_embauche" class="form-control" value="<?= e($oldValues['date_embauche'] ?? date('Y-m-d')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="classe_id" class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe titulaire</label>
                    <select id="classe_id" name="classe_id" class="form-control <?= isset($errors['classe_id']) ? 'is-invalid' : '' ?>">
                        <option value="">— Aucune classe —</option>
                        <?php if (!empty($classesDisponibles)): ?>
                            <?php foreach ($classesDisponibles as $classe): ?>
                                <option value="<?= $classe['id'] ?>" <?= ($oldValues['classe_id'] ?? '') == $classe['id'] ? 'selected' : '' ?>>
                                    <?= e($classe['nom']) ?> — Niveau <?= $classe['niveau'] ?> (Salle <?= e($classe['salle']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if (isset($errors['classe_id'])): ?><span class="invalid-feedback"><?= e($errors['classe_id']) ?></span><?php endif; ?>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Seules les classes sans titulaire sont affichées.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 4 : COMPTE UTILISATEUR -->
    <div class="enseignant-form-card mb-4">
        <div class="enseignant-form-header">
            <div class="enseignant-form-icon"><i class="fa-solid fa-user-gear"></i></div>
            <div><h5>Compte utilisateur</h5><p>Accès à la plateforme</p></div>
        </div>
        <div class="enseignant-form-body">
            <div class="form-group">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="creer_compte" id="creer_compte" value="1" <?= ($oldValues['creer_compte'] ?? '') === '1' ? 'checked' : '' ?>>
                    <span class="checkbox-custom"><i class="fa-solid fa-check"></i></span>
                    <span class="checkbox-label">Créer un compte utilisateur pour cet enseignant</span>
                </label>
                <small class="form-help" style="margin-top:8px;">
                    <i class="fa-solid fa-info-circle"></i> Un compte permettra à l'enseignant de se connecter et gérer sa classe. 
                    Le mot de passe par défaut sera <strong>Enseignant@2025</strong>. L'email est requis.
                </small>
            </div>
        </div>
    </div>

    <!-- Boutons -->
    <div class="d-flex justify-content-end gap-sm mb-4">
        <a href="/gestion_scolaire/enseignants" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-user-plus"></i> Créer l'enseignant
        </button>
    </div>
</form>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>