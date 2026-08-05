<?php
/**
 * Fichier : public/views/comptable/modules/frais.php
 * Rôle    : Configuration des frais scolaires — Vue Comptable
 * Système : École primaire congolaise — Gestion financière
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('paiements.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'PaiementController.php';
$paiementController = new PaiementController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $paiementController->handleAjax();
}

$anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
$frais = $db->fetchAll(
    "SELECT f.*, 
            (SELECT COUNT(*) FROM paiements p WHERE p.frais_scolaire_id = f.id) AS nb_paiements,
            (SELECT COALESCE(SUM(p.montant_paye), 0) FROM paiements p WHERE p.frais_scolaire_id = f.id) AS total_encaisse
     FROM frais_scolaires f
     WHERE f.annee_scolaire_id = :aid
     ORDER BY f.est_obligatoire DESC, f.libelle",
    ['aid' => $anneeId]
);

$classes = $paiementController->getClassesActives();
$totalFrais = count($frais);
$totalEncaisse = array_sum(array_column($frais, 'total_encaisse'));

$pageTitle = 'Configuration des frais scolaires';
$pageIcon  = 'fa-tags';
$pageDescription = 'Gérez les types de frais et leurs montants';
$extraCSS = ['modules/paiements.css'];
$extraJS  = ['modules/paiements.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/comptable/paiements">Paiements</a>
    <span class="separator">/</span>
    <span class="current">Frais scolaires</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-tags"></i> Configuration des frais scolaires</h4>
        <p>Gérez les types de frais, leurs montants et leur obligation.</p>
    </div>
    <div class="resultats-header-right">
        <button type="button" class="btn btn-primary btn-sm" id="btnAjouterFrais">
            <i class="fa-solid fa-plus"></i> Nouveau frais
        </button>
        <a href="/gestion_scolaire/comptable/paiements" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Paiements
        </a>
    </div>
</div>

<div id="flashMessage"></div>

<div class="retards-stats mb-4">
    <div class="retard-stat-card">
        <div class="retard-stat-icon blue"><i class="fa-solid fa-tags"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $totalFrais ?></div>
            <div class="retard-stat-label">Frais configurés</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
        <div class="retard-stat-content">
            <?php $actifs = count(array_filter($frais, fn($f) => $f['est_actif'])); ?>
            <div class="retard-stat-value"><?= $actifs ?></div>
            <div class="retard-stat-label">Frais actifs</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon orange"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value">$ <?= number_format($totalEncaisse, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Total encaissé</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon purple"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="retard-stat-content">
            <?php $obligatoires = count(array_filter($frais, fn($f) => $f['est_obligatoire'])); ?>
            <div class="retard-stat-value"><?= $obligatoires ?></div>
            <div class="retard-stat-label">Frais obligatoires</div>
        </div>
    </div>
</div>

<div class="retards-table-card">
    <div class="table-header-info" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color-light);">
        <span><strong><?= $totalFrais ?></strong> frais trouvé(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Libellé</th>
                    <th>Montant</th>
                    <th>Classe</th>
                    <th>Obligatoire</th>
                    <th>Statut</th>
                    <th>Paiements</th>
                    <th>Encaissé</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($frais)): ?>
                    <tr><td colspan="8"><div class="empty-state" style="padding:40px;"><i class="fa-solid fa-tags"></i><p>Aucun frais configuré.</p><button type="button" class="btn btn-primary btn-sm mt-3" id="btnAjouterFraisBis"><i class="fa-solid fa-plus"></i> Créer un frais</button></div></td></tr>
                <?php else: ?>
                    <?php foreach ($frais as $fraisItem): ?>
                        <tr class="<?= !$fraisItem['est_actif'] ? 'ligne-inactive' : '' ?>">
                            <td><strong><?= e($fraisItem['libelle']) ?></strong></td>
                            <td><strong style="color:#065f46;">$ <?= number_format($fraisItem['montant'], 2, ',', ' ') ?></strong></td>
                            <td><?= $fraisItem['classe_id'] ? e($db->fetchColumn("SELECT nom FROM classes WHERE id = :id", ['id' => $fraisItem['classe_id']]) ?: '—') : '<span class="badge badge-info">Toutes</span>' ?></td>
                            <td><?= $fraisItem['est_obligatoire'] ? '<span class="badge badge-warning">Obligatoire</span>' : '<span class="badge badge-secondary">Optionnel</span>' ?></td>
                            <td><?= $fraisItem['est_actif'] ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-danger">Inactif</span>' ?></td>
                            <td><span class="badge badge-primary"><?= $fraisItem['nb_paiements'] ?></span></td>
                            <td><strong>$ <?= number_format($fraisItem['total_encaisse'], 0, ',', ' ') ?></strong></td>
                            <td>
                                <div class="d-flex gap-xs">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-modifier-frais"
                                            data-id="<?= $fraisItem['id'] ?>" data-libelle="<?= e($fraisItem['libelle']) ?>"
                                            data-description="<?= e($fraisItem['description'] ?? '') ?>" data-montant="<?= $fraisItem['montant'] ?>"
                                            data-classe="<?= $fraisItem['classe_id'] ?? '' ?>" data-obligatoire="<?= $fraisItem['est_obligatoire'] ?>" title="Modifier">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info btn-toggle-frais"
                                            data-id="<?= $fraisItem['id'] ?>" data-statut="<?= $fraisItem['est_actif'] ?>"
                                            title="<?= $fraisItem['est_actif'] ? 'Désactiver' : 'Activer' ?>">
                                        <i class="fa-solid fa-<?= $fraisItem['est_actif'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                    </button>
                                    <?php if ($fraisItem['nb_paiements'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-frais"
                                            data-id="<?= $fraisItem['id'] ?>" data-libelle="<?= e($fraisItem['libelle']) ?>" title="Supprimer">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalFrais">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header"><h5><i class="fa-solid fa-tag"></i> <span id="modalFraisTitre">Ajouter un frais</span></h5><button type="button" class="modal-close" id="btnCloseFrais">&times;</button></div>
        <div class="modal-body">
            <form id="formFrais" autocomplete="off">
                <input type="hidden" id="frais_id" name="frais_id" value="">
                <div class="form-group"><label for="frais_libelle" class="form-label"><i class="fa-solid fa-tag"></i> Libellé <span class="required">*</span></label><input type="text" id="frais_libelle" name="libelle" class="form-control" placeholder="Ex: Minerval, Frais de cantine..." required maxlength="150"></div>
                <div class="form-group"><label for="frais_description" class="form-label"><i class="fa-solid fa-align-left"></i> Description</label><textarea id="frais_description" name="description" class="form-control" rows="2" placeholder="Description du frais..."></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label for="frais_montant" class="form-label"><i class="fa-solid fa-dollar-sign"></i> Montant <span class="required">*</span></label><div class="input-icon-wrapper"><span class="input-icon">$</span><input type="number" id="frais_montant" name="montant" class="form-control" placeholder="0,00" min="0.01" step="0.01" required></div></div>
                    <div class="form-group"><label for="frais_classe" class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe</label><select id="frais_classe" name="classe_id" class="form-control"><option value="">Toutes les classes</option><?php foreach ($classes as $cl): ?><option value="<?= $cl['id'] ?>"><?= e($cl['nom']) ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="form-group"><label class="checkbox-wrapper"><input type="checkbox" id="frais_obligatoire" name="est_obligatoire" value="1" checked><span class="checkbox-custom"><i class="fa-solid fa-check"></i></span><span class="checkbox-label">Frais obligatoire</span></label></div>
            </form>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-primary" id="btnCancelFrais"><i class="fa-solid fa-xmark"></i> Annuler</button><button type="button" class="btn btn-primary" id="btnSaveFrais"><i class="fa-solid fa-save"></i> Enregistrer</button></div>
    </div>
</div>

<script>
const modalFrais = document.getElementById('modalFrais');
const modalFraisTitre = document.getElementById('modalFraisTitre');
const formFrais = document.getElementById('formFrais');
function openModalFrais() { modalFrais.classList.add('show'); document.body.style.overflow = 'hidden'; }
function closeModalFrais() { modalFrais.classList.remove('show'); document.body.style.overflow = ''; formFrais.reset(); }
document.getElementById('btnCloseFrais')?.addEventListener('click', closeModalFrais);
document.getElementById('btnCancelFrais')?.addEventListener('click', closeModalFrais);
modalFrais?.addEventListener('click', function(e) { if (e.target === modalFrais) closeModalFrais(); });
document.getElementById('btnAjouterFrais')?.addEventListener('click', function() { modalFraisTitre.textContent = 'Ajouter un frais'; document.getElementById('frais_id').value = ''; formFrais.reset(); document.getElementById('frais_obligatoire').checked = true; openModalFrais(); });
document.getElementById('btnAjouterFraisBis')?.addEventListener('click', function() { document.getElementById('btnAjouterFrais').click(); });
document.querySelectorAll('.btn-modifier-frais').forEach(btn => { btn.addEventListener('click', function() { modalFraisTitre.textContent = 'Modifier le frais'; document.getElementById('frais_id').value = this.dataset.id; document.getElementById('frais_libelle').value = this.dataset.libelle; document.getElementById('frais_description').value = this.dataset.description; document.getElementById('frais_montant').value = this.dataset.montant; document.getElementById('frais_classe').value = this.dataset.classe; document.getElementById('frais_obligatoire').checked = this.dataset.obligatoire === '1'; openModalFrais(); }); });
document.getElementById('btnSaveFrais').addEventListener('click', async function() {
    const libelle = document.getElementById('frais_libelle').value.trim();
    const montant = document.getElementById('frais_montant').value;
    if (!libelle) { showToast('Le libellé est obligatoire.', 'warning'); return; }
    if (!montant || parseFloat(montant) <= 0) { showToast('Le montant est obligatoire.', 'warning'); return; }
    const formData = new FormData(formFrais);
    formData.append('action', document.getElementById('frais_id').value ? 'modifierFrais' : 'ajouterFrais');
    this.disabled = true; this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';
    try {
        const res = await fetch('/gestion_scolaire/paiements', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        if (data.success) { showToast(data.message, 'success'); closeModalFrais(); setTimeout(() => location.reload(), 500); }
        else { showToast(data.message, 'error'); }
    } catch (e) { showToast('Erreur de connexion.', 'error'); }
    finally { this.disabled = false; this.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer'; }
});
document.querySelectorAll('.btn-toggle-frais').forEach(btn => { btn.addEventListener('click', async function() { const formData = new FormData(); formData.append('action', 'toggleStatutFrais'); formData.append('frais_id', this.dataset.id); try { const res = await fetch('/gestion_scolaire/paiements', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } }); const data = await res.json(); if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 400); } else { showToast(data.message, 'error'); } } catch (e) { showToast('Erreur de connexion.', 'error'); } }); });
document.querySelectorAll('.btn-supprimer-frais').forEach(btn => { btn.addEventListener('click', function() { const fraisId = this.dataset.id; const libelle = this.dataset.libelle; showConfirmDialog('Voulez-vous supprimer le frais <strong>' + escapeHtml(libelle) + '</strong> ?', async function(confirmed) { if (confirmed) { const formData = new FormData(); formData.append('action', 'supprimerFrais'); formData.append('frais_id', fraisId); try { const res = await fetch('/gestion_scolaire/paiements', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } }); const data = await res.json(); if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 400); } else { showToast(data.message, 'error'); } } catch (e) { showToast('Erreur de connexion.', 'error'); } } }, { title: 'Supprimer le frais', confirmText: 'Supprimer', confirmClass: 'btn-danger', icon: 'fa-trash', iconColor: '#ef4444' }); }); });
function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text || ''; return div.innerHTML; }
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>