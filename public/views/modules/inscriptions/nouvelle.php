<?php
/**
 * Fichier : public/views/modules/inscriptions/nouvelle.php
 * Rôle    : Formulaire de nouvelle inscription individuelle
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('inscriptions.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'InscriptionController.php';
$inscriptionController = new InscriptionController();

// Récupérer les élèves non inscrits cette année
$elevesNonInscrits = $inscriptionController->getElevesNonInscrits();

// Récupérer les classes avec capacités
$classesActives = $inscriptionController->getClassesActives();

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eleveId  = (int) post('eleve_id', 0);
    $classeId = (int) post('classe_id', 0);
    $statut   = post('statut', 'Actif');

    $errors = [];

    if ($eleveId <= 0) $errors['eleve_id'] = 'Veuillez sélectionner un élève.';
    if ($classeId <= 0) $errors['classe_id'] = 'Veuillez sélectionner une classe.';

    // Vérifier la capacité
    if ($classeId > 0) {
        $capacite = (int) $db->fetchColumn("SELECT capacite_max FROM classes WHERE id = :id", ['id' => $classeId]);
        $nbInscrits = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
            ['cid' => $classeId]
        );
        if ($nbInscrits >= $capacite) {
            $errors['classe_id'] = "Classe complète ({$capacite} élèves maximum).";
        }
    }

    // Vérifier que l'élève n'est pas déjà inscrit
    if ($eleveId > 0) {
        $anneeActive = $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
        $dejaInscrit = $db->fetchOne(
            "SELECT c.nom FROM inscriptions i
             INNER JOIN classes c ON i.classe_id = c.id
             WHERE i.eleve_id = :eid AND i.annee_scolaire_id = :aid AND i.statut = 'Actif'",
            ['eid' => $eleveId, 'aid' => $anneeActive]
        );
        if ($dejaInscrit) {
            $errors['eleve_id'] = "Cet élève est déjà inscrit en {$dejaInscrit['nom']}.";
        }
    }

    if (empty($errors)) {
        try {
            $anneeActive = $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
            
            $inscriptionId = $db->insert(
                "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                 VALUES (:eid, :cid, :aid, CURDATE(), :statut)",
                ['eid' => $eleveId, 'cid' => $classeId, 'aid' => $anneeActive, 'statut' => $statut]
            );

            logActivity('Création', 'inscriptions', $inscriptionId, "Inscription élève {$eleveId}");

            setFlash('success', 'Élève inscrit avec succès.');
            redirect('/gestion_scolaire/inscriptions');

        } catch (Exception $e) {
            $errorMessage = 'Une erreur est survenue lors de l\'inscription.';
            error_log('Erreur inscription: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Nouvelle inscription';
$pageIcon  = 'fa-user-plus';
$pageDescription = 'Inscrire un élève dans une classe';
$extraCSS = ['modules/inscriptions.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/inscriptions">Inscriptions</a>
    <span class="separator">/</span>
    <span class="current">Nouvelle</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-user-plus text-primary me-2"></i>Nouvelle inscription
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Inscrivez un élève dans une classe pour l'année scolaire en cours.
        </p>
    </div>
    <a href="/gestion_scolaire/inscriptions" class="btn btn-outline-primary btn-sm">
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

<div class="inscription-form-card">
    <div class="inscription-form-header">
        <div class="inscription-form-icon">
            <i class="fa-solid fa-file-pen"></i>
        </div>
        <div>
            <h5>Nouvelle inscription</h5>
            <p>Sélectionnez l'élève et sa classe</p>
        </div>
    </div>
    <div class="inscription-form-body">
        <form method="POST" action="" autocomplete="off" novalidate id="formInscription">

            <!-- Sélection de l'élève -->
            <div class="form-group">
                <label for="eleve_id" class="form-label">
                    <i class="fa-solid fa-graduation-cap"></i> Élève <span class="required">*</span>
                </label>
                <?php if (empty($elevesNonInscrits)): ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Tous les élèves sont déjà inscrits pour cette année scolaire.</span>
                    </div>
                <?php else: ?>
                    <div class="eleve-search-select">
                        <input type="text" id="searchEleve" class="search-input" 
                               placeholder="Rechercher un élève par nom, prénom ou matricule..."
                               autocomplete="off">
                        <div class="eleve-search-results" id="eleveResults">
                            <?php foreach ($elevesNonInscrits as $eleve): ?>
                                <div class="eleve-search-item" data-id="<?= $eleve['id'] ?>" 
                                     data-name="<?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?>"
                                     data-matricule="<?= e($eleve['matricule']) ?>">
                                    <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#2c3e90,#4a6cf7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:0.75rem;flex-shrink:0;">
                                        <?= mb_strtoupper(mb_substr($eleve['prenom'], 0, 1) . mb_substr($eleve['nom'], 0, 1)) ?>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-weight:500;font-size:0.85rem;"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></div>
                                        <div style="font-size:0.72rem;color:var(--color-gray-400);"><?= e($eleve['matricule']) ?> — <?= $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille' ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <input type="hidden" name="eleve_id" id="eleve_id" value="">
                    <div id="eleveSelected" style="display:none;margin-top:10px;padding:12px;background:#d1fae5;border-radius:10px;border:1px solid #a7f3d0;">
                        <i class="fa-solid fa-check-circle text-success"></i>
                        <strong id="eleveSelectedName"></strong>
                        <span id="eleveSelectedMatricule" style="font-size:0.75rem;color:#065f46;margin-left:8px;"></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($errors['eleve_id'])): ?>
                    <span class="invalid-feedback"><?= e($errors['eleve_id']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Sélection de la classe -->
            <div class="form-group">
                <label for="classe_id" class="form-label">
                    <i class="fa-solid fa-chalkboard"></i> Classe <span class="required">*</span>
                </label>
                <select id="classe_id" name="classe_id" class="form-control <?= isset($errors['classe_id']) ? 'is-invalid' : '' ?>" required>
                    <option value="">— Sélectionnez une classe —</option>
                    <?php foreach ($classesActives as $classe): 
                        $complet = $classe['nb_inscrits'] >= $classe['capacite_max'];
                    ?>
                        <option value="<?= $classe['id'] ?>" <?= $complet ? 'disabled' : '' ?>>
                            <?= e($classe['nom']) ?> — Niv. <?= $classe['niveau'] ?> 
                            (<?= $classe['nb_inscrits'] ?>/<?= $classe['capacite_max'] ?>)
                            <?= $complet ? ' [COMPLET]' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['classe_id'])): ?>
                    <span class="invalid-feedback"><?= e($errors['classe_id']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Statut -->
            <div class="form-group">
                <label for="statut" class="form-label">
                    <i class="fa-solid fa-flag"></i> Statut
                </label>
                <select id="statut" name="statut" class="form-control">
                    <option value="Actif">Actif — Inscription normale</option>
                    <option value="Redoublant">Redoublant — Reprend la même classe</option>
                </select>
            </div>
        </form>
    </div>
    <div class="inscription-form-footer">
        <a href="/gestion_scolaire/inscriptions" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" form="formInscription" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Inscrire l'élève
        </button>
    </div>
</div>

<!-- Script recherche élève -->
<script>
const searchEleve = document.getElementById('searchEleve');
const eleveResults = document.getElementById('eleveResults');
const eleveId = document.getElementById('eleve_id');
const eleveSelected = document.getElementById('eleveSelected');
const eleveSelectedName = document.getElementById('eleveSelectedName');
const eleveSelectedMatricule = document.getElementById('eleveSelectedMatricule');

if (searchEleve && eleveResults) {
    // Afficher les résultats au focus
    searchEleve.addEventListener('focus', () => {
        eleveResults.classList.add('show');
        filterEleves();
    });

    // Filtrer les élèves
    searchEleve.addEventListener('input', filterEleves);

    // Cacher les résultats au clic en dehors
    document.addEventListener('click', (e) => {
        if (!searchEleve.contains(e.target) && !eleveResults.contains(e.target)) {
            eleveResults.classList.remove('show');
        }
    });

    // Sélectionner un élève
    eleveResults.addEventListener('click', (e) => {
        const item = e.target.closest('.eleve-search-item');
        if (item) {
            eleveId.value = item.dataset.id;
            eleveSelectedName.textContent = item.dataset.name;
            eleveSelectedMatricule.textContent = '(' + item.dataset.matricule + ')';
            eleveSelected.style.display = 'block';
            searchEleve.value = item.dataset.name;
            eleveResults.classList.remove('show');
            
            // Mettre en évidence l'élément sélectionné
            document.querySelectorAll('.eleve-search-item').forEach(el => el.classList.remove('selected'));
            item.classList.add('selected');
        }
    });

    function filterEleves() {
        const query = searchEleve.value.toLowerCase().trim();
        const items = eleveResults.querySelectorAll('.eleve-search-item');
        let visible = 0;
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (query === '' || text.includes(query)) {
                item.style.display = 'flex';
                visible++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Afficher message si aucun résultat
        let noResult = eleveResults.querySelector('.no-result');
        if (visible === 0 && query !== '') {
            if (!noResult) {
                noResult = document.createElement('div');
                noResult.className = 'no-result';
                noResult.style.cssText = 'padding:16px;text-align:center;color:var(--color-gray-400);font-size:0.82rem;';
                noResult.innerHTML = '<i class="fa-solid fa-search"></i> Aucun élève trouvé';
                eleveResults.appendChild(noResult);
            }
            noResult.style.display = 'block';
        } else if (noResult) {
            noResult.style.display = 'none';
        }
    }
}
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>