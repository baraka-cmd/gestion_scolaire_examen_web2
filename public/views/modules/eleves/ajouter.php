<?php
/**
 * Fichier : public/views/modules/eleves/ajouter.php
 * Rôle    : Formulaire d'ajout d'un nouvel élève avec inscription et parents
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('eleves.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EleveController.php';
$eleveController = new EleveController();

// Récupérer les classes disponibles
$classes = $eleveController->getClasses();

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldValues      = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues = [
        'nom'                => post('nom', ''),
        'prenom'             => post('prenom', ''),
        'date_naissance'     => post('date_naissance', ''),
        'lieu_naissance'     => post('lieu_naissance', ''),
        'sexe'               => post('sexe', ''),
        'adresse'            => post('adresse', ''),
        'telephone'          => post('telephone', ''),
        'email'              => post('email', ''),
        'groupe_sanguin'     => post('groupe_sanguin', ''),
        'allergies'          => post('allergies', ''),
        'maladies'           => post('maladies', ''),
        'classe_id'          => post('classe_id', ''),
        'parent1_nom'        => post('parent1_nom', ''),
        'parent1_prenom'     => post('parent1_prenom', ''),
        'parent1_lien'       => post('parent1_lien', ''),
        'parent1_telephone'  => post('parent1_telephone', ''),
        'parent1_email'      => post('parent1_email', ''),
        'parent1_profession' => post('parent1_profession', ''),
        'parent2_nom'        => post('parent2_nom', ''),
        'parent2_prenom'     => post('parent2_prenom', ''),
        'parent2_lien'       => post('parent2_lien', ''),
        'parent2_telephone'  => post('parent2_telephone', ''),
    ];

    // Validation...
    $nom           = $oldValues['nom'];
    $prenom        = $oldValues['prenom'];
    $dateNaissance = $oldValues['date_naissance'];
    $sexe          = $oldValues['sexe'];
    $classeId      = (int) $oldValues['classe_id'];
    $parent1Nom    = $oldValues['parent1_nom'];
    $parent1Prenom = $oldValues['parent1_prenom'];
    $parent1Tel    = $oldValues['parent1_telephone'];
    $parent1Lien   = $oldValues['parent1_lien'];

    if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
    elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';

    if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
    elseif (mb_strlen($prenom) < 2) $errors['prenom'] = 'Minimum 2 caractères.';

    if (isBlank($dateNaissance)) $errors['date_naissance'] = 'La date de naissance est obligatoire.';
    elseif (!isValidDate($dateNaissance)) $errors['date_naissance'] = 'Date invalide.';

    if (!in_array($sexe, ['M', 'F'])) $errors['sexe'] = 'Veuillez sélectionner le sexe.';

    if ($classeId <= 0) $errors['classe_id'] = 'Veuillez sélectionner une classe.';

    // Parent 1 obligatoire
    if (isBlank($parent1Nom)) $errors['parent1_nom'] = 'Le nom du parent/tuteur est obligatoire.';
    if (isBlank($parent1Prenom)) $errors['parent1_prenom'] = 'Le prénom est obligatoire.';
    if (isBlank($parent1Tel)) $errors['parent1_telephone'] = 'Le téléphone est obligatoire.';
    if (isBlank($parent1Lien)) $errors['parent1_lien'] = 'Le lien de parenté est obligatoire.';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Générer le matricule
            $matricule = 'EL-' . date('y') . '-' . str_pad(((int) $db->fetchColumn("SELECT MAX(id) FROM eleves") + 1), 5, '0', STR_PAD_LEFT);

            // Insérer l'élève
            $eleveId = $db->insert(
                "INSERT INTO eleves (matricule, nom, prenom, date_naissance, lieu_naissance, sexe, 
                     adresse, telephone, email, groupe_sanguin, allergies, maladies, date_inscription, est_actif)
                 VALUES (:mat, :nom, :prenom, :dn, :ln, :sexe, :adr, :tel, :email, :gs, :all, :mal, CURDATE(), 1)",
                [
                    'mat'   => $matricule,
                    'nom'   => formatNom($nom),
                    'prenom'=> formatNom($prenom),
                    'dn'    => $dateNaissance,
                    'ln'    => post('lieu_naissance') ?: null,
                    'sexe'  => $sexe,
                    'adr'   => post('adresse') ?: null,
                    'tel'   => post('telephone') ?: null,
                    'email' => post('email') ?: null,
                    'gs'    => post('groupe_sanguin') ?: null,
                    'all'   => post('allergies') ?: null,
                    'mal'   => post('maladies') ?: null
                ]
            );

            // Inscription dans la classe
            $anneeActive = $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
            $db->query(
                "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                 VALUES (:eid, :cid, :aid, CURDATE(), 'Actif')",
                ['eid' => $eleveId, 'cid' => $classeId, 'aid' => $anneeActive]
            );

            // Parent 1
            $parent1Id = $db->insert(
                "INSERT INTO parents_tuteurs (nom, prenom, lien, profession, telephone_principal, email, est_contact_urgence)
                 VALUES (:nom, :prenom, :lien, :prof, :tel, :email, 1)",
                [
                    'nom'   => formatNom($parent1Nom),
                    'prenom'=> formatNom($parent1Prenom),
                    'lien'  => $parent1Lien,
                    'prof'  => post('parent1_profession') ?: null,
                    'tel'   => $parent1Tel,
                    'email' => post('parent1_email') ?: null
                ]
            );
            $db->query(
                "INSERT INTO eleve_parent (eleve_id, parent_id, est_tuteur_principal) VALUES (:eid, :pid, 1)",
                ['eid' => $eleveId, 'pid' => $parent1Id]
            );

            // Parent 2 (optionnel)
            $parent2Nom = post('parent2_nom', '');
            $parent2Tel = post('parent2_telephone', '');
            if (!isBlank($parent2Nom) && !isBlank($parent2Tel)) {
                $parent2Id = $db->insert(
                    "INSERT INTO parents_tuteurs (nom, prenom, lien, telephone_principal, est_contact_urgence)
                     VALUES (:nom, :prenom, :lien, :tel, 1)",
                    [
                        'nom'   => formatNom($parent2Nom),
                        'prenom'=> formatNom(post('parent2_prenom', '')),
                        'lien'  => post('parent2_lien', 'Tuteur'),
                        'tel'   => $parent2Tel
                    ]
                );
                $db->query(
                    "INSERT INTO eleve_parent (eleve_id, parent_id, est_tuteur_principal) VALUES (:eid, :pid, 0)",
                    ['eid' => $eleveId, 'pid' => $parent2Id]
                );
            }

            $db->commit();

            logActivity('Création', 'eleves', $eleveId, "Élève créé : {$nom} {$prenom}");

            setFlash('success', 'Élève inscrit avec succès. Matricule : ' . $matricule);
            redirect('/gestion_scolaire/eleves/fiche/' . $eleveId);

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Une erreur est survenue lors de l\'inscription.';
            error_log('Erreur création élève: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ajouter un élève';
$pageIcon  = 'fa-user-plus';
$pageDescription = 'Inscrire un nouvel élève';
$extraCSS = ['modules/eleves.css', 'modules/ajouter_eleve.css'];
$extraJS  = ['modules/ajouter_eleve.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/eleves">Élèves</a>
    <span class="separator">/</span>
    <span class="current">Ajouter</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-user-plus text-primary me-2"></i>Ajouter un élève
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Inscrivez un nouvel élève et renseignez les informations de ses parents.
        </p>
    </div>
    <a href="/gestion_scolaire/eleves" class="btn btn-outline-primary btn-sm">
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

<form method="POST" action="" autocomplete="off" novalidate id="formAddEleve">

    <!-- ============================================================
         SECTION 1 : IDENTITÉ DE L'ÉLÈVE
         ============================================================ -->
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
                    <label for="lieu_naissance" class="form-label"><i class="fa-solid fa-location-dot"></i> Lieu de naissance</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-map-pin"></i></span>
                        <input type="text" id="lieu_naissance" name="lieu_naissance" class="form-control" value="<?= e($oldValues['lieu_naissance'] ?? '') ?>" placeholder="Ville de naissance" maxlength="150">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="sexe" class="form-label"><i class="fa-solid fa-venus-mars"></i> Sexe <span class="required">*</span></label>
                    <select id="sexe" name="sexe" class="form-control <?= isset($errors['sexe']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez —</option>
                        <option value="M" <?= ($oldValues['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= ($oldValues['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                    </select>
                    <?php if (isset($errors['sexe'])): ?><span class="invalid-feedback"><?= e($errors['sexe']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="telephone" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" id="telephone" name="telephone" class="form-control" value="<?= e($oldValues['telephone'] ?? '') ?>" placeholder="+243XXXXXXXXX" maxlength="20">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-at"></i></span>
                        <input type="email" id="email" name="email" class="form-control" value="<?= e($oldValues['email'] ?? '') ?>" placeholder="email@exemple.com" maxlength="150">
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
    </div>

    <!-- ============================================================
         SECTION 2 : INSCRIPTION SCOLAIRE
         ============================================================ -->
    <div class="eleve-form-card mb-4">
        <div class="eleve-form-header">
            <div class="eleve-form-icon"><i class="fa-solid fa-school"></i></div>
            <div><h5>Inscription scolaire</h5><p>Classe et année</p></div>
        </div>
        <div class="eleve-form-body">
            <div class="form-group">
                <label for="classe_id" class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe <span class="required">*</span></label>
                <select id="classe_id" name="classe_id" class="form-control <?= isset($errors['classe_id']) ? 'is-invalid' : '' ?>" required>
                    <option value="">— Sélectionnez une classe —</option>
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?= $classe['id'] ?>" <?= ($oldValues['classe_id'] ?? '') == $classe['id'] ? 'selected' : '' ?>>
                            <?= e($classe['nom']) ?> — Niveau <?= $classe['niveau'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['classe_id'])): ?><span class="invalid-feedback"><?= e($errors['classe_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="groupe_sanguin" class="form-label"><i class="fa-solid fa-droplet"></i> Groupe sanguin</label>
                    <select id="groupe_sanguin" name="groupe_sanguin" class="form-control">
                        <option value="">— Inconnu —</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $gs): ?>
                            <option value="<?= $gs ?>" <?= ($oldValues['groupe_sanguin'] ?? '') === $gs ? 'selected' : '' ?>><?= $gs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 3 : INFORMATIONS MÉDICALES
         ============================================================ -->
    <div class="eleve-form-card mb-4">
        <div class="eleve-form-header">
            <div class="eleve-form-icon"><i class="fa-solid fa-notes-medical"></i></div>
            <div><h5>Informations médicales</h5><p>Allergies et conditions particulières</p></div>
        </div>
        <div class="eleve-form-body">
            <div class="form-group">
                <label for="allergies" class="form-label"><i class="fa-solid fa-allergies"></i> Allergies</label>
                <textarea id="allergies" name="allergies" class="form-control" rows="2" placeholder="Ex: Arachides, pollen, pénicilline..."><?= e($oldValues['allergies'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="maladies" class="form-label"><i class="fa-solid fa-heart-pulse"></i> Maladies / Conditions particulières</label>
                <textarea id="maladies" name="maladies" class="form-control" rows="2" placeholder="Ex: Asthme, diabète, épilepsie..."><?= e($oldValues['maladies'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 4 : PARENT / TUTEUR 1 (OBLIGATOIRE)
         ============================================================ -->
    <div class="eleve-form-card mb-4">
        <div class="eleve-form-header">
            <div class="eleve-form-icon"><i class="fa-solid fa-people-roof"></i></div>
            <div><h5>Parent / Tuteur principal <span class="required">*</span></h5><p>Responsable légal de l'élève</p></div>
        </div>
        <div class="eleve-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="parent1_nom" class="form-label"><i class="fa-solid fa-user"></i> Nom <span class="required">*</span></label>
                    <input type="text" id="parent1_nom" name="parent1_nom" class="form-control <?= isset($errors['parent1_nom']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['parent1_nom'] ?? '') ?>" placeholder="Nom du parent" required>
                    <?php if (isset($errors['parent1_nom'])): ?><span class="invalid-feedback"><?= e($errors['parent1_nom']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="parent1_prenom" class="form-label"><i class="fa-solid fa-user"></i> Prénom <span class="required">*</span></label>
                    <input type="text" id="parent1_prenom" name="parent1_prenom" class="form-control <?= isset($errors['parent1_prenom']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['parent1_prenom'] ?? '') ?>" placeholder="Prénom du parent" required>
                    <?php if (isset($errors['parent1_prenom'])): ?><span class="invalid-feedback"><?= e($errors['parent1_prenom']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="parent1_lien" class="form-label"><i class="fa-solid fa-link"></i> Lien de parenté <span class="required">*</span></label>
                    <select id="parent1_lien" name="parent1_lien" class="form-control <?= isset($errors['parent1_lien']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez —</option>
                        <?php foreach (['Père', 'Mère', 'Tuteur', 'Tutrice', 'Grand-père', 'Grand-mère', 'Oncle', 'Tante', 'Frère', 'Sœur', 'Autre'] as $lien): ?>
                            <option value="<?= $lien ?>" <?= ($oldValues['parent1_lien'] ?? '') === $lien ? 'selected' : '' ?>><?= $lien ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['parent1_lien'])): ?><span class="invalid-feedback"><?= e($errors['parent1_lien']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="parent1_telephone" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone <span class="required">*</span></label>
                    <input type="tel" id="parent1_telephone" name="parent1_telephone" class="form-control <?= isset($errors['parent1_telephone']) ? 'is-invalid' : '' ?>" value="<?= e($oldValues['parent1_telephone'] ?? '') ?>" placeholder="+243XXXXXXXXX" required>
                    <?php if (isset($errors['parent1_telephone'])): ?><span class="invalid-feedback"><?= e($errors['parent1_telephone']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="parent1_email" class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                    <input type="email" id="parent1_email" name="parent1_email" class="form-control" value="<?= e($oldValues['parent1_email'] ?? '') ?>" placeholder="email@exemple.com">
                </div>
                <div class="form-group">
                    <label for="parent1_profession" class="form-label"><i class="fa-solid fa-briefcase"></i> Profession</label>
                    <input type="text" id="parent1_profession" name="parent1_profession" class="form-control" value="<?= e($oldValues['parent1_profession'] ?? '') ?>" placeholder="Profession">
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 5 : PARENT / TUTEUR 2 (OPTIONNEL)
         ============================================================ -->
    <div class="eleve-form-card mb-4">
        <div class="eleve-form-header">
            <div class="eleve-form-icon"><i class="fa-solid fa-user-group"></i></div>
            <div><h5>Second parent / Tuteur <span style="font-weight:400;opacity:0.75;">(optionnel)</span></h5><p>Contact secondaire</p></div>
        </div>
        <div class="eleve-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="parent2_nom" class="form-label"><i class="fa-solid fa-user"></i> Nom</label>
                    <input type="text" id="parent2_nom" name="parent2_nom" class="form-control" value="<?= e($oldValues['parent2_nom'] ?? '') ?>" placeholder="Nom">
                </div>
                <div class="form-group">
                    <label for="parent2_prenom" class="form-label"><i class="fa-solid fa-user"></i> Prénom</label>
                    <input type="text" id="parent2_prenom" name="parent2_prenom" class="form-control" value="<?= e($oldValues['parent2_prenom'] ?? '') ?>" placeholder="Prénom">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="parent2_lien" class="form-label"><i class="fa-solid fa-link"></i> Lien de parenté</label>
                    <select id="parent2_lien" name="parent2_lien" class="form-control">
                        <option value="">— Sélectionnez —</option>
                        <?php foreach (['Père', 'Mère', 'Tuteur', 'Tutrice', 'Grand-père', 'Grand-mère', 'Oncle', 'Tante', 'Frère', 'Sœur', 'Autre'] as $lien): ?>
                            <option value="<?= $lien ?>" <?= ($oldValues['parent2_lien'] ?? '') === $lien ? 'selected' : '' ?>><?= $lien ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="parent2_telephone" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone</label>
                    <input type="tel" id="parent2_telephone" name="parent2_telephone" class="form-control" value="<?= e($oldValues['parent2_telephone'] ?? '') ?>" placeholder="+243XXXXXXXXX">
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons -->
    <div class="d-flex justify-content-end gap-sm mb-4">
        <a href="/gestion_scolaire/eleves" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-user-plus"></i> Inscrire l'élève
        </button>
    </div>
</form>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>