<?php
/**
 * Fichier : public/views/modules/resultats/deliberations.php
 * Rôle    : Délibérations du conseil de classe — Modification des décisions et appréciations
 * Système : Bulletin congolais — Délibérations par classe et trimestre
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('resultats.modifier');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ResultatController.php';
$resultatController = new ResultatController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $resultatController->handleAjax();
}

// Récupérer les données pour les filtres
$classes = $resultatController->getClassesActives();
$trimestres = $resultatController->getTrimestresActifs();
$annees = $resultatController->getAnneesScolaires();

// Paramètres
$classeId = (int) ($_GET['classe_id'] ?? 0);
$trimestreId = (int) ($_GET['trimestre_id'] ?? 0);
$anneeId = (int) ($_GET['annee_id'] ?? 0);

if ($anneeId <= 0) {
    $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
}

// Récupérer les infos
$classe = null;
$trimestre = null;
$annee = null;
$resultats = [];
$stats = [];

if ($classeId > 0 && $trimestreId > 0) {
    $classe = $db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]);
    $trimestre = $db->fetchOne("SELECT * FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
    $annee = $db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $anneeId]);

    // Récupérer les résultats avec les décisions du conseil
    $resultats = $db->fetchAll(
        "SELECT rt.*, e.matricule, e.nom, e.prenom, e.sexe,
                DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age
         FROM resultats_trimestriels rt
         INNER JOIN eleves e ON rt.eleve_id = e.id
         WHERE rt.classe_id = :cid AND rt.trimestre_id = :tid
         ORDER BY rt.rang ASC",
        ['cid' => $classeId, 'tid' => $trimestreId]
    );

    // Statistiques
    if (!empty($resultats)) {
        $admis = count(array_filter($resultats, fn($r) => ($r['decision_finale'] ? $r['decision'] : $r['decision']) === 'Admis'));
        $ajournes = count($resultats) - $admis;
        $moyennes = array_column($resultats, 'moyenne_generale');
        $stats = [
            'effectif'      => count($resultats),
            'admis'         => $admis,
            'ajournes'      => $ajournes,
            'taux_reussite' => count($resultats) > 0 ? round(($admis / count($resultats)) * 100, 1) : 0,
            'moyenne_classe' => count($moyennes) > 0 ? round(array_sum($moyennes) / count($moyennes), 1) : 0,
            'moyenne_haute' => count($moyennes) > 0 ? max($moyennes) : 0,
            'moyenne_basse' => count($moyennes) > 0 ? min($moyennes) : 0,
            'decisions_finales' => count(array_filter($resultats, fn($r) => $r['decision_finale'] == 1))
        ];
    }

    // Récupérer l'enseignant titulaire
    $titulaire = $db->fetchOne(
        "SELECT CONCAT(e.prenom, ' ', e.nom) AS nom_complet
         FROM enseignants e
         WHERE e.classe_id = :cid AND e.est_actif = 1
         LIMIT 1",
        ['cid' => $classeId]
    );
}

$pageTitle = 'Délibérations du conseil de classe';
$pageIcon  = 'fa-gavel';
$pageDescription = 'Délibérations — Décisions finales et appréciations';
$extraCSS = ['modules/resultats.css'];
$extraJS  = ['modules/resultats.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/resultats">Résultats</a>
    <span class="separator">/</span>
    <span class="current">Délibérations</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4>
            <i class="fa-solid fa-gavel"></i> Délibérations du conseil de classe
        </h4>
        <p>Examinez les résultats, modifiez les décisions et ajoutez des appréciations.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/resultats" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Résultats
        </a>
        <?php if (!empty($resultats)): ?>
            <button type="button" class="btn btn-success btn-sm" id="btnValiderDeliberations">
                <i class="fa-solid fa-check-double"></i> Valider toutes les décisions
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Imprimer le PV
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     SÉLECTEURS
     ============================================================ -->
<div class="resultats-selectors">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header">
            <i class="fa-solid fa-filter"></i> Sélectionner la classe et le trimestre
        </div>
        <div class="resultats-selector-body">
            <form method="GET" action="" id="formSelectClasse" class="resultats-selector-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="classe_id" class="form-label">
                            <i class="fa-solid fa-chalkboard"></i> Classe
                        </label>
                        <select id="classe_id" name="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez une classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>>
                                    <?= e($cl['nom']) ?> (Niv. <?= $cl['niveau'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="trimestre_id" class="form-label">
                            <i class="fa-solid fa-calendar-alt"></i> Trimestre
                        </label>
                        <select id="trimestre_id" name="trimestre_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez un trimestre —</option>
                            <?php foreach ($trimestres as $trim): ?>
                                <option value="<?= $trim['id'] ?>" <?= $trimestreId === (int) $trim['id'] ? 'selected' : '' ?>>
                                    <?= e($trim['libelle']) ?> <?= $trim['est_actif'] ? '(Actif)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="annee_id" class="form-label">
                            <i class="fa-solid fa-calendar"></i> Année
                        </label>
                        <select id="annee_id" name="annee_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $anneeId === (int) $a['id'] ? 'selected' : '' ?>>
                                    <?= e($a['libelle']) ?> <?= $a['est_active'] ? '(Active)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     DÉLIBÉRATIONS
     ============================================================ -->
<?php if ($classeId <= 0 || $trimestreId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-gavel"></i>
        <p>Sélectionnez une classe et un trimestre pour accéder aux délibérations.</p>
    </div>

<?php elseif (empty($resultats)): ?>
    <div class="resultats-non-calcules">
        <div class="resultats-non-calcules-icon">
            <i class="fa-solid fa-calculator"></i>
        </div>
        <div>
            <h5>Aucun résultat trouvé</h5>
            <p>Les résultats pour cette classe et ce trimestre n'ont pas encore été calculés.</p>
            <a href="/gestion_scolaire/resultats/consulter?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-primary btn-sm mt-3">
                <i class="fa-solid fa-calculator"></i> Calculer les résultats
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- En-tête des délibérations -->
    <div class="deliberation-document" id="deliberationDocument">
        <div class="deliberation-header">
            <div class="deliberation-header-top">
                <div class="deliberation-icon">
                    <i class="fa-solid fa-gavel"></i>
                </div>
                <div class="deliberation-info">
                    <h2>Délibérations du Conseil de Classe</h2>
                    <p>
                        <strong>Classe :</strong> <?= e($classe['nom']) ?> (Niveau <?= $classe['niveau'] ?>) — 
                        <strong>Trimestre :</strong> <?= e($trimestre['libelle']) ?> — 
                        <strong>Année :</strong> <?= e($annee['libelle'] ?? '') ?>
                    </p>
                    <p>
                        <strong>Titulaire :</strong> <?= e($titulaire['nom_complet'] ?? 'Non assigné') ?> — 
                        <strong>Effectif :</strong> <?= $stats['effectif'] ?> élève(s) — 
                        <strong>Date :</strong> <?= formatDate(date('Y-m-d'), 'd/m/Y') ?>
                    </p>
                </div>
                <div class="deliberation-badge">
                    <span class="deliberation-stat <?= $stats['decisions_finales'] === $stats['effectif'] ? 'complete' : 'pending' ?>">
                        <?= $stats['decisions_finales'] ?>/<?= $stats['effectif'] ?>
                    </span>
                    <span style="font-size:0.7rem;">décisions validées</span>
                </div>
            </div>
        </div>

        <!-- Barre d'outils -->
        <div class="deliberation-toolbar">
            <div class="deliberation-toolbar-left">
                <span class="deliberation-legend">
                    <span class="legend-dot auto"></span> Décision automatique
                </span>
                <span class="deliberation-legend">
                    <span class="legend-dot modified"></span> Décision modifiée
                </span>
                <span class="deliberation-legend">
                    <span class="legend-dot validated"></span> Validée par le conseil
                </span>
            </div>
            <div class="deliberation-toolbar-right">
                <button type="button" class="btn btn-outline-success btn-sm" id="btnAdmettreTous">
                    <i class="fa-solid fa-check-circle"></i> Admettre tous
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnAjournerTous">
                    <i class="fa-solid fa-times-circle"></i> Ajourner tous
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnReinitialiserDecisions">
                    <i class="fa-solid fa-rotate"></i> Réinitialiser
                </button>
            </div>
        </div>

        <!-- Tableau des délibérations -->
        <div class="deliberation-table-wrapper">
            <table class="deliberation-table" id="deliberationTable">
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Matricule</th>
                        <th>Élève</th>
                        <th>Sexe</th>
                        <th>Âge</th>
                        <th>Moyenne</th>
                        <th>Décision auto</th>
                        <th>Décision conseil</th>
                        <th>Appréciation</th>
                        <th>Validé</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultats as $resultat): ?>
                        <tr class="deliberation-row <?= $resultat['decision_finale'] ? 'validee' : 'en-attente' ?>" 
                            data-resultat-id="<?= $resultat['id'] ?>"
                            data-decision-auto="<?= e($resultat['decision']) ?>">
                            
                            <td>
                                <?php if ($resultat['rang'] === 1): ?>
                                    <span class="rank-badge gold"><i class="fa-solid fa-trophy"></i> 1er</span>
                                <?php elseif ($resultat['rang'] === 2): ?>
                                    <span class="rank-badge silver"><i class="fa-solid fa-medal"></i> 2ème</span>
                                <?php elseif ($resultat['rang'] === 3): ?>
                                    <span class="rank-badge bronze"><i class="fa-solid fa-award"></i> 3ème</span>
                                <?php else: ?>
                                    <span class="rank-number"><?= $resultat['rang'] ?>ème</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-primary"><?= e($resultat['matricule']) ?></span></td>
                            <td><strong><?= e($resultat['prenom'] . ' ' . $resultat['nom']) ?></strong></td>
                            <td><?= $resultat['sexe'] === 'M' ? 'M' : 'F' ?></td>
                            <td><?= $resultat['age'] ?? '—' ?></td>
                            <td>
                                <span class="pourcentage-badge <?= $resultat['moyenne_generale'] >= 70 ? 'success' : ($resultat['moyenne_generale'] >= 50 ? 'warning' : 'danger') ?>">
                                    <?= number_format($resultat['moyenne_generale'], 1) ?>%
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $resultat['decision'] === 'Admis' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= e($resultat['decision']) ?>
                                </span>
                            </td>
                            <td>
                                <select class="form-control decision-select" 
                                        data-resultat-id="<?= $resultat['id'] ?>"
                                        style="padding:6px 10px;font-size:0.78rem;min-width:120px;">
                                    <option value="Admis" <?= $resultat['decision'] === 'Admis' ? 'selected' : '' ?>>
                                        ✅ Admis
                                    </option>
                                    <option value="Ajourné" <?= $resultat['decision'] === 'Ajourné' ? 'selected' : '' ?>>
                                        ❌ Ajourné
                                    </option>
                                    <option value="Redouble" <?= $resultat['decision'] === 'Redouble' ? 'selected' : '' ?>>
                                        🔄 Redouble
                                    </option>
                                    <option value="Exclu" <?= $resultat['decision'] === 'Exclu' ? 'selected' : '' ?>>
                                        🚫 Exclu
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control appreciation-input" 
                                       data-resultat-id="<?= $resultat['id'] ?>"
                                       value="<?= e($resultat['appreciation_conseil'] ?? '') ?>"
                                       placeholder="Appréciation..."
                                       style="padding:6px 10px;font-size:0.76rem;min-width:180px;">
                            </td>
                            <td class="text-center">
                                <label class="checkbox-wrapper" style="margin:0;">
                                    <input type="checkbox" class="validation-checkbox" 
                                           data-resultat-id="<?= $resultat['id'] ?>"
                                           <?= $resultat['decision_finale'] ? 'checked' : '' ?>>
                                    <span class="checkbox-custom"><i class="fa-solid fa-check"></i></span>
                                </label>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-save-deliberation" 
                                        data-resultat-id="<?= $resultat['id'] ?>"
                                        title="Sauvegarder">
                                    <i class="fa-solid fa-save"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Appréciation générale -->
        <div class="deliberation-appreciation-generale">
            <h5><i class="fa-solid fa-comment-dots"></i> Appréciation générale du conseil de classe</h5>
            <textarea id="appreciationGenerale" class="form-control" rows="3" 
                      placeholder="Appréciation générale pour l'ensemble de la classe..."><?= e($db->fetchColumn("SELECT appreciation_conseil FROM resultats_trimestriels WHERE classe_id = :cid AND trimestre_id = :tid AND appreciation_conseil IS NOT NULL LIMIT 1", ['cid' => $classeId, 'tid' => $trimestreId]) ?? '') ?></textarea>
        </div>

        <!-- Signatures -->
        <div class="deliberation-signatures">
            <div class="deliberation-signature-block">
                <div class="deliberation-signature-line"></div>
                <span>Le Secrétaire</span>
            </div>
            <div class="deliberation-signature-block">
                <div class="deliberation-signature-line"></div>
                <span>L'Enseignant Titulaire</span>
            </div>
            <div class="deliberation-signature-block">
                <div class="deliberation-signature-line"></div>
                <span>Le Directeur</span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="deliberation-actions no-print">
        <button type="button" class="btn btn-success btn-lg" id="btnSauvegarderTout">
            <i class="fa-solid fa-save"></i> Sauvegarder toutes les délibérations
        </button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer le PV de délibération
        </button>
        <a href="/gestion_scolaire/resultats/proces-verbaux?classe_id=<?= $classeId ?>&trimestre_id=<?= $trimestreId ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-file-contract"></i> Voir le procès-verbal
        </a>
    </div>
<?php endif; ?>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Sauvegarder une délibération individuelle
    document.querySelectorAll('.btn-save-deliberation').forEach(btn => {
        btn.addEventListener('click', function() {
            const resultatId = this.dataset.resultatId;
            sauvegarderDeliberation(resultatId);
        });
    });

    // Sauvegarder tout
    const btnSauvegarderTout = document.getElementById('btnSauvegarderTout');
    if (btnSauvegarderTout) {
        btnSauvegarderTout.addEventListener('click', function() {
            const rows = document.querySelectorAll('.deliberation-row');
            let saved = 0;
            const total = rows.length;

            btnSauvegarderTout.disabled = true;
            btnSauvegarderTout.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde en cours...';

            const promises = [];
            rows.forEach(row => {
                const resultatId = row.dataset.resultatId;
                promises.push(sauvegarderDeliberationAsync(resultatId));
            });

            Promise.all(promises).then(results => {
                const successCount = results.filter(r => r).length;
                btnSauvegarderTout.disabled = false;
                btnSauvegarderTout.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarder toutes les délibérations';
                showToast(successCount + '/' + total + ' délibération(s) sauvegardée(s).', 'success');
            }).catch(err => {
                btnSauvegarderTout.disabled = false;
                btnSauvegarderTout.innerHTML = '<i class="fa-solid fa-save"></i> Sauvegarder toutes les délibérations';
                showToast('Erreur lors de la sauvegarde.', 'error');
            });
        });
    }

    // Admettre tous
    const btnAdmettreTous = document.getElementById('btnAdmettreTous');
    if (btnAdmettreTous) {
        btnAdmettreTous.addEventListener('click', function() {
            document.querySelectorAll('.decision-select').forEach(select => {
                select.value = 'Admis';
                select.style.borderColor = '#10b981';
                updateRowState(select.closest('.deliberation-row'));
            });
            showToast('Tous les élèves sont marqués "Admis".', 'info');
        });
    }

    // Ajourner tous
    const btnAjournerTous = document.getElementById('btnAjournerTous');
    if (btnAjournerTous) {
        btnAjournerTous.addEventListener('click', function() {
            document.querySelectorAll('.decision-select').forEach(select => {
                select.value = 'Ajourné';
                select.style.borderColor = '#ef4444';
                updateRowState(select.closest('.deliberation-row'));
            });
            showToast('Tous les élèves sont marqués "Ajourné".', 'warning');
        });
    }

    // Réinitialiser les décisions
    const btnReinitialiser = document.getElementById('btnReinitialiserDecisions');
    if (btnReinitialiser) {
        btnReinitialiser.addEventListener('click', function() {
            document.querySelectorAll('.deliberation-row').forEach(row => {
                const decisionAuto = row.dataset.decisionAuto;
                const select = row.querySelector('.decision-select');
                if (select) {
                    select.value = decisionAuto;
                    select.style.borderColor = '';
                    updateRowState(row);
                }
            });
            showToast('Décisions réinitialisées aux valeurs automatiques.', 'info');
        });
    }

    // Changement de décision
    document.querySelectorAll('.decision-select').forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('.deliberation-row');
            updateRowState(row);
        });
    });

    // Valider toutes les décisions
    const btnValider = document.getElementById('btnValiderDeliberations');
    if (btnValider) {
        btnValider.addEventListener('click', function() {
            document.querySelectorAll('.validation-checkbox').forEach(cb => {
                cb.checked = true;
                const row = cb.closest('.deliberation-row');
                row.classList.add('validee');
                row.classList.remove('en-attente');
            });
            showToast('Toutes les décisions sont validées.', 'success');
        });
    }
});

// Mettre à jour l'état visuel d'une ligne
function updateRowState(row) {
    const select = row.querySelector('.decision-select');
    if (select) {
        if (select.value !== row.dataset.decisionAuto) {
            row.classList.add('modifiee');
        } else {
            row.classList.remove('modifiee');
        }
    }
}

// Sauvegarder une délibération
function sauvegarderDeliberation(resultatId) {
    const row = document.querySelector(`.deliberation-row[data-resultat-id="${resultatId}"]`);
    if (!row) return;

    const decision = row.querySelector('.decision-select')?.value || '';
    const appreciation = row.querySelector('.appreciation-input')?.value || '';
    const decisionFinale = row.querySelector('.validation-checkbox')?.checked ? 1 : 0;

    const formData = new FormData();
    formData.append('action', 'sauvegarderDeliberation');
    formData.append('resultat_id', resultatId);
    formData.append('decision', decision);
    formData.append('appreciation', appreciation);
    formData.append('decision_finale', decisionFinale);

    return fetch('/gestion_scolaire/resultats', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            row.classList.add('validee');
            row.classList.remove('en-attente');
        }
        return data.success;
    });
}

// Version asynchrone pour Promise.all
async function sauvegarderDeliberationAsync(resultatId) {
    try {
        return await sauvegarderDeliberation(resultatId);
    } catch (e) {
        return false;
    }
}
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';