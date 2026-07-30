<?php
/**
 * Fichier : public/views/modules/eleves/modifier.php
 * Rôle    : Formulaire de modification d'un élève
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('eleves.modifier');

global $db;

// Récupérer l'ID
$eleveId = (int) ($_GET['id'] ?? 0);
if ($eleveId <= 0) {
    setFlash('error', 'ID élève invalide.');
    redirect('/gestion_scolaire/eleves');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EleveController.php';
$eleveController = new EleveController();

// Récupérer l'élève
$eleve = $eleveController->getEleveDetail($eleveId);
if (!$eleve) {
    setFlash('error', 'Élève introuvable.');
    redirect('/gestion_scolaire/eleves');
}

// Récupérer les classes et les parents
$classes = $eleveController->getClasses();
$parents = $eleveController->getParentsEleve($eleveId);

// Messages
$errorMessage = '';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom              = post('nom', '');
    $prenom           = post('prenom', '');
    $dateNaissance    = post('date_naissance', '');
    $lieuNaissance    = post('lieu_naissance', '');
    $sexe             = post('sexe', '');
    $adresse          = post('adresse', '');
    $telephone        = post('telephone', '');
    $email            = post('email', '');
    $groupeSanguin    = post('groupe_sanguin', '');
    $allergies        = post('allergies', '');
    $maladies         = post('maladies', '');

    if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
    if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
    if (isBlank($dateNaissance)) $errors['date_naissance'] = 'La date de naissance est obligatoire.';
    elseif (!isValidDate($dateNaissance)) $errors['date_naissance'] = 'Date invalide.';
    if (!in_array($sexe, ['M', 'F'])) $errors['sexe'] = 'Sexe invalide.';

    if (empty($errors)) {
        try {
            $db->update(
                "UPDATE eleves 
                 SET nom = :nom, prenom = :prenom, date_naissance = :dn, lieu_naissance = :ln,
                     sexe = :sexe, adresse = :adr, telephone = :tel, email = :email,
                     groupe_sanguin = :gs, allergies = :all, maladies = :mal
                 WHERE id = :id",
                [
                    'nom'   => formatNom($nom),
                    'prenom'=> formatNom($prenom),
                    'dn'    => $dateNaissance,
                    'ln'    => $lieuNaissance ?: null,
                    'sexe'  => $sexe,
                    'adr'   => $adresse ?: null,
                    'tel'   => $telephone ?: null,
                    'email' => $email ?: null,
                    'gs'    => $groupeSanguin ?: null,
                    'all'   => $allergies ?: null,
                    'mal'   => $maladies ?: null,
                    'id'    => $eleveId
                ]
            );

            logActivity('Modification', 'eleves', $eleveId, "Élève modifié : {$nom} {$prenom}");
            setFlash('success', 'Élève modifié avec succès.');
            redirect('/gestion_scolaire/eleves/fiche/' . $eleveId);

        } catch (Exception $e) {
            $errorMessage = 'Erreur lors de la modification.';
            error_log('Erreur modification élève: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Modifier — ' . $eleve['prenom'] . ' ' . $eleve['nom'];
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Modifier les informations de l\'élève';
$extraCSS = ['modules/eleves.css', 'modules/ajouter_eleve.css'];
$extraJS  = ['modules/ajouter_eleve.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/eleves">Élèves</a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/eleves/fiche/<?= $eleveId ?>"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></a>
    <span class="separator">/</span>
    <span class="current">Modifier</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier l'élève
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            <strong><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></strong> — 
            Matricule : <?= e($eleve['matricule']) ?> — 
            Classe : <?= e($eleve['classe_nom'] ?? 'Non inscrit') ?>
        </p>
    </div>
    <div class="d-flex gap-sm">
        <a href="/gestion_scolaire/eleves/fiche/<?= $eleveId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-eye"></i> Voir fiche
        </a>
        <a href="/gestion_scolaire/eleves" class="btn btn-outline-primary btn-sm">
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

<form method="POST" action="" autocomplete="off" novalidate id="formAddEleve">

    <!-- SECTION 1 : IDENTITÉ -->
    <div class="eleve-form-card mb-4">
        <div class="eleve-form-header">
            <div class="eleve-form-icon"><i class="fa-solid fa-id-card"></i></div>
            <div><h5>Identité de l'élève</h5><p>Informations personnelles</p></div>
        </div>
        <div class="eleve-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="nom" class="form-label"><i class="fa-solid fa-user"></i> Nom <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['nom'] ?? $eleve['nom']) ?>" required maxlength="100" autofocus>
                    </div>
                    <?php if (isset($errors['nom'])): ?><span class="invalid-feedback"><?= e($errors['nom']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="prenom" class="form-label"><i class="fa-solid fa-user"></i> Prénom <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['prenom'] ?? $eleve['prenom']) ?>" required maxlength="100">
                    </div>
                    <?php if (isset($errors['prenom'])): ?><span class="invalid-feedback"><?= e($errors['prenom']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="date_naissance" class="form-label"><i class="fa-solid fa-calendar"></i> Date de naissance <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-cake-candles"></i></span>
                        <input type="date" id="date_naissance" name="date_naissance" class="form-control <?= isset($errors['date_naissance']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['date_naissance'] ?? $eleve['date_naissance']) ?>" required>
                    </div>
                    <?php if (isset($errors['date_naissance'])): ?><span class="invalid-feedback"><?= e($errors['date_naissance']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="lieu_naissance" class="form-label"><i class="fa-solid fa-location-dot"></i> Lieu de naissance</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-map-pin"></i></span>
                        <input type="text" id="lieu_naissance" name="lieu_naissance" class="form-control" value="<?= e($_POST['lieu_naissance'] ?? $eleve['lieu_naissance'] ?? '') ?>" maxlength="150">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="sexe" class="form-label"><i class="fa-solid fa-venus-mars"></i> Sexe <span class="required">*</span></label>
                    <select id="sexe" name="sexe" class="form-control <?= isset($errors['sexe']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez —</option>
                        <option value="M" <?= ($_POST['sexe'] ?? $eleve['sexe']) === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= ($_POST['sexe'] ?? $eleve['sexe']) === 'F' ? 'selected' : '' ?>>Féminin</option>
                    </select>
                    <?php if (isset($errors['sexe'])): ?><span class="invalid-feedback"><?= e($errors['sexe']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="telephone" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" id="telephone" name="telephone" class="form-control" value="<?= e($_POST['telephone'] ?? $eleve['telephone'] ?? '') ?>" maxlength="20">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-at"></i></span>
                        <input type="email" id="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? $eleve['email'] ?? '') ?>" maxlength="150">
                    </div>
                </div>
                <div class="form-group">
                    <label for="adresse" class="form-label"><i class="fa-solid fa-house"></i> Adresse</label>
                    <div class="input-icon-wrapper"><span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <input type="text" id="adresse" name="adresse" class="form-control" value="<?= e($_POST['adresse'] ?? $eleve['adresse'] ?? '') ?>" maxlength="255">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2 : SANTÉ -->
    <div class="eleve-form-card mb-4">
        <div class="eleve-form-header">
            <div class="eleve-form-icon"><i class="fa-solid fa-notes-medical"></i></div>
            <div><h5>Informations médicales</h5><p>Santé de l'élève</p></div>
        </div>
        <div class="eleve-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="groupe_sanguin" class="form-label"><i class="fa-solid fa-droplet"></i> Groupe sanguin</label>
                    <select id="groupe_sanguin" name="groupe_sanguin" class="form-control">
                        <option value="">— Inconnu —</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $gs): ?>
                            <option value="<?= $gs ?>" <?= ($_POST['groupe_sanguin'] ?? $eleve['groupe_sanguin'] ?? '') === $gs ? 'selected' : '' ?>><?= $gs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="allergies" class="form-label"><i class="fa-solid fa-allergies"></i> Allergies</label>
                <textarea id="allergies" name="allergies" class="form-control" rows="2"><?= e($_POST['allergies'] ?? $eleve['allergies'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="maladies" class="form-label"><i class="fa-solid fa-heart-pulse"></i> Maladies / Conditions particulières</label>
                <textarea id="maladies" name="maladies" class="form-control" rows="2"><?= e($_POST['maladies'] ?? $eleve['maladies'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Boutons -->
    <div class="d-flex justify-content-end gap-sm mb-4">
        <a href="/gestion_scolaire/eleves/fiche/<?= $eleveId ?>" class="btn btn-outline-primary">
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