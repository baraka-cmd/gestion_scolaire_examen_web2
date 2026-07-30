<?php
/**
 * Fichier : public/views/modules/enseignants/modifier.php
 * Rôle    : Formulaire de modification d'un enseignant
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('enseignants.modifier');

global $db;

// Récupérer l'ID
$enseignantId = (int) ($_GET['id'] ?? 0);
if ($enseignantId <= 0) {
    setFlash('error', 'ID enseignant invalide.');
    redirect('/gestion_scolaire/enseignants');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EnseignantController.php';
$enseignantController = new EnseignantController();

// Récupérer l'enseignant
$enseignant = $enseignantController->getEnseignantDetail($enseignantId);
if (!$enseignant) {
    setFlash('error', 'Enseignant introuvable.');
    redirect('/gestion_scolaire/enseignants');
}

// Récupérer les classes disponibles (sans titulaire + sa classe actuelle)
$classesDisponibles = $db->fetchAll(
    "SELECT c.id, c.nom, c.niveau, c.salle
     FROM classes c
     WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
     AND (c.id NOT IN (SELECT COALESCE(classe_id, 0) FROM enseignants WHERE est_actif = 1 AND id != :eid)
          OR c.id = :cid)
     ORDER BY c.niveau",
    ['eid' => $enseignantId, 'cid' => $enseignant['classe_id'] ?? 0]
);

// Messages
$errorMessage = '';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom            = post('nom', '');
    $prenom         = post('prenom', '');
    $dateNaissance  = post('date_naissance', '');
    $sexe           = post('sexe', '');
    $adresse        = post('adresse', '');
    $telephone      = post('telephone', '');
    $email          = post('email', '');
    $diplome        = post('diplome', '');
    $specialite     = post('specialite', '');
    $dateEmbauche   = post('date_embauche', '');
    $classeId       = (int) post('classe_id', 0);

    if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
    if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
    if (isBlank($dateNaissance)) $errors['date_naissance'] = 'Date obligatoire.';
    if (!in_array($sexe, ['M', 'F'])) $errors['sexe'] = 'Sexe invalide.';

    if (!isBlank($telephone) && !isValidPhone($telephone)) $errors['telephone'] = 'Format invalide.';
    else {
        $telExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM enseignants WHERE telephone = :tel AND id != :id",
            ['tel' => $telephone, 'id' => $enseignantId]
        );
        if ($telExiste > 0) $errors['telephone'] = 'Ce numéro est déjà utilisé.';
    }

    if (!isBlank($email) && !isValidEmail($email)) $errors['email'] = 'Email invalide.';
    else {
        $emailExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM enseignants WHERE email = :email AND id != :id",
            ['email' => $email, 'id' => $enseignantId]
        );
        if ($emailExiste > 0) $errors['email'] = 'Cet email est déjà utilisé.';
    }

    if ($classeId > 0) {
        $classeDejaPrise = $db->fetchColumn(
            "SELECT COUNT(*) FROM enseignants WHERE classe_id = :cid AND id != :eid AND est_actif = 1",
            ['cid' => $classeId, 'eid' => $enseignantId]
        );
        if ($classeDejaPrise > 0) $errors['classe_id'] = 'Cette classe a déjà un autre titulaire.';
    }

    if (empty($errors)) {
        try {
            $db->update(
                "UPDATE enseignants 
                 SET nom = :nom, prenom = :prenom, date_naissance = :dn, sexe = :sexe,
                     adresse = :adr, telephone = :tel, email = :email,
                     diplome = :diplome, specialite = :spe, date_embauche = :de, classe_id = :cid
                 WHERE id = :id",
                [
                    'nom'     => formatNom($nom),
                    'prenom'  => formatNom($prenom),
                    'dn'      => $dateNaissance,
                    'sexe'    => $sexe,
                    'adr'     => $adresse ?: null,
                    'tel'     => $telephone ?: null,
                    'email'   => $email ?: null,
                    'diplome' => $diplome ?: null,
                    'spe'     => $specialite ?: null,
                    'de'      => $dateEmbauche ?: null,
                    'cid'     => $classeId > 0 ? $classeId : null,
                    'id'      => $enseignantId
                ]
            );

            logActivity('Modification', 'enseignants', $enseignantId, "Enseignant modifié : {$nom} {$prenom}");
            setFlash('success', 'Enseignant modifié avec succès.');
            redirect('/gestion_scolaire/enseignants/fiche/' . $enseignantId);

        } catch (Exception $e) {
            $errorMessage = 'Erreur lors de la modification.';
            error_log('Erreur modification enseignant: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Modifier — ' . $enseignant['prenom'] . ' ' . $enseignant['nom'];
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Modifier les informations de l\'enseignant';
$extraCSS = ['modules/enseignants.css', 'modules/ajouter_enseignant.css'];
$extraJS  = ['modules/ajouter_enseignant.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/enseignants">Enseignants</a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/enseignants/fiche/<?= $enseignantId ?>"><?= e($enseignant['prenom'] . ' ' . $enseignant['nom']) ?></a>
    <span class="separator">/</span>
    <span class="current">Modifier</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier l'enseignant
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            <strong><?= e($enseignant['prenom'] . ' ' . $enseignant['nom']) ?></strong> — 
            Matricule : <?= e($enseignant['matricule']) ?>
        </p>
    </div>
    <div class="d-flex gap-sm">
        <a href="/gestion_scolaire/enseignants/fiche/<?= $enseignantId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-eye"></i> Voir fiche
        </a>
        <a href="/gestion_scolaire/enseignants" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Liste
        </a>
    </div>
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
            <div><h5>Identité</h5><p>Informations personnelles</p></div>
        </div>
        <div class="enseignant-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="nom" class="form-label"><i class="fa-solid fa-user"></i> Nom <span class="required">*</span></label>
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['nom'] ?? $enseignant['nom']) ?>" required maxlength="100" autofocus>
                    </div>
                    <?php if (isset($errors['nom'])): ?><span class="invalid-feedback"><?= e($errors['nom']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="prenom" class="form-label"><i class="fa-solid fa-user"></i> Prénom <span class="required">*</span></label>
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['prenom'] ?? $enseignant['prenom']) ?>" required maxlength="100">
                    </div>
                    <?php if (isset($errors['prenom'])): ?><span class="invalid-feedback"><?= e($errors['prenom']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="date_naissance" class="form-label"><i class="fa-solid fa-calendar"></i> Date de naissance <span class="required">*</span></label>
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-cake-candles"></i></span>
                        <input type="date" id="date_naissance" name="date_naissance" class="form-control <?= isset($errors['date_naissance']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['date_naissance'] ?? $enseignant['date_naissance']) ?>" required>
                    </div>
                    <?php if (isset($errors['date_naissance'])): ?><span class="invalid-feedback"><?= e($errors['date_naissance']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="sexe" class="form-label"><i class="fa-solid fa-venus-mars"></i> Sexe <span class="required">*</span></label>
                    <select id="sexe" name="sexe" class="form-control <?= isset($errors['sexe']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez —</option>
                        <option value="M" <?= ($_POST['sexe'] ?? $enseignant['sexe']) === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= ($_POST['sexe'] ?? $enseignant['sexe']) === 'F' ? 'selected' : '' ?>>Féminin</option>
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
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" id="telephone" name="telephone" class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['telephone'] ?? $enseignant['telephone'] ?? '') ?>" maxlength="20">
                    </div>
                    <?php if (isset($errors['telephone'])): ?><span class="invalid-feedback"><?= e($errors['telephone']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-at"></i></span>
                        <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['email'] ?? $enseignant['email'] ?? '') ?>" maxlength="150">
                    </div>
                    <?php if (isset($errors['email'])): ?><span class="invalid-feedback"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="adresse" class="form-label"><i class="fa-solid fa-house"></i> Adresse</label>
                <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <input type="text" id="adresse" name="adresse" class="form-control" value="<?= e($_POST['adresse'] ?? $enseignant['adresse'] ?? '') ?>" maxlength="255">
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
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-certificate"></i></span>
                        <input type="text" id="diplome" name="diplome" class="form-control" value="<?= e($_POST['diplome'] ?? $enseignant['diplome'] ?? '') ?>" maxlength="150">
                    </div>
                </div>
                <div class="form-group">
                    <label for="specialite" class="form-label"><i class="fa-solid fa-star"></i> Spécialité</label>
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-award"></i></span>
                        <input type="text" id="specialite" name="specialite" class="form-control" value="<?= e($_POST['specialite'] ?? $enseignant['specialite'] ?? '') ?>" maxlength="150">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="date_embauche" class="form-label"><i class="fa-solid fa-calendar-check"></i> Date d'embauche</label>
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                        <input type="date" id="date_embauche" name="date_embauche" class="form-control" value="<?= e($_POST['date_embauche'] ?? $enseignant['date_embauche'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="classe_id" class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe titulaire</label>
                    <select id="classe_id" name="classe_id" class="form-control <?= isset($errors['classe_id']) ? 'is-invalid' : '' ?>">
                        <option value="">— Aucune classe —</option>
                        <?php foreach ($classesDisponibles as $classe): ?>
                            <option value="<?= $classe['id'] ?>" <?= ($_POST['classe_id'] ?? $enseignant['classe_id']) == $classe['id'] ? 'selected' : '' ?>>
                                <?= e($classe['nom']) ?> — Niv. <?= $classe['niveau'] ?> (<?= e($classe['salle']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['classe_id'])): ?><span class="invalid-feedback"><?= e($errors['classe_id']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons -->
    <div class="d-flex justify-content-end gap-sm mb-4">
        <a href="/gestion_scolaire/enseignants/fiche/<?= $enseignantId ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-save"></i> Enregistrer les modifications
        </button>
    </div>
</form>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>