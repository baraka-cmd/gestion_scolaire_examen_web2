<?php
/**
 * Fichier : public/views/modules/sauvegardes/liste.php
 * Rôle    : Gestion des sauvegardes de la base de données
 * Système : École primaire congolaise — Administration
 * Version : 1.1 — Adaptée à la structure réelle de la table
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('sauvegardes.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'SauvegardeController.php';
$sauvegardeController = new SauvegardeController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $sauvegardeController->handleAjax();
}

// Statistiques
$totalSauvegardes = (int) $db->fetchColumn("SELECT COUNT(*) FROM sauvegardes");
$tailleTotale = (int) $db->fetchColumn("SELECT COALESCE(SUM(taille_fichier), 0) FROM sauvegardes");
$derniereSauvegarde = $db->fetchOne(
    "SELECT s.*, DATE_FORMAT(s.date_creation, '%d/%m/%Y à %H:%i') AS date_format,
            CONCAT(u.prenom, ' ', u.nom) AS auteur_nom
     FROM sauvegardes s
     LEFT JOIN utilisateurs u ON s.cree_par = u.id
     ORDER BY s.date_creation DESC LIMIT 1"
);
$sauvegardesCeMois = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM sauvegardes WHERE MONTH(date_creation) = MONTH(CURDATE()) AND YEAR(date_creation) = YEAR(CURDATE())"
);
$sauvegardesReussies = (int) $db->fetchColumn("SELECT COUNT(*) FROM sauvegardes WHERE statut = 'Succès'");
$sauvegardesEchecs = (int) $db->fetchColumn("SELECT COUNT(*) FROM sauvegardes WHERE statut = 'Échec'");

// Vérifier l'espace disque
$espaceDisque = disk_free_space(STORAGE_PATH . 'backups');
$espaceDisqueFormate = $espaceDisque > 1073741824 
    ? round($espaceDisque / 1073741824, 1) . ' Go' 
    : round($espaceDisque / 1048576, 1) . ' Mo';

$pageTitle = 'Sauvegardes';
$pageIcon  = 'fa-database';
$pageDescription = 'Gérez les sauvegardes et la restauration de la base de données';
$extraCSS = ['modules/sauvegardes.css'];
$extraJS  = ['modules/sauvegardes.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Sauvegardes</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-database"></i> Sauvegardes de la base de données</h4>
        <p>Gérez les sauvegardes et la restauration de votre base de données. Une sauvegarde régulière est essentielle pour la sécurité de vos données.</p>
    </div>
    <div class="resultats-header-right">
        <?php if (can('sauvegardes.creer')): ?>
            <button type="button" class="btn btn-primary btn-lg" id="btnSauvegarder">
                <i class="fa-solid fa-floppy-disk"></i> Sauvegarder maintenant
            </button>
        <?php endif; ?>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-database"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalSauvegardes ?></div>
            <div class="stat-label">Total sauvegardes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-hard-drive"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $tailleTotale > 1048576 ? round($tailleTotale / 1048576, 1) . ' Mo' : round($tailleTotale / 1024, 1) . ' Ko' ?></div>
            <div class="stat-label">Espace utilisé</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $sauvegardesCeMois ?></div>
            <div class="stat-label">Sauvegardes ce mois</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fa-solid fa-microchip"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $espaceDisqueFormate ?></div>
            <div class="stat-label">Espace libre disque</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $sauvegardesReussies ?></div>
            <div class="stat-label">Sauvegardes réussies</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $sauvegardesEchecs ?></div>
            <div class="stat-label">Sauvegardes échouées</div>
        </div>
    </div>
</div>

<!-- ============================================================
     ALERTE DERNIÈRE SAUVEGARDE
     ============================================================ -->
<?php if ($derniereSauvegarde): ?>
    <?php 
    $dateDerniere = strtotime($derniereSauvegarde['date_creation']);
    $heuresEcoulees = (time() - $dateDerniere) / 3600;
    $alerteClass = $heuresEcoulees > 72 ? 'danger' : ($heuresEcoulees > 24 ? 'warning' : 'success');
    ?>
    <div class="alerte-sauvegarde alerte-<?= $alerteClass ?> mb-4">
        <div class="alerte-sauvegarde-icon">
            <?php if ($alerteClass === 'danger'): ?>
                <i class="fa-solid fa-triangle-exclamation"></i>
            <?php elseif ($alerteClass === 'warning'): ?>
                <i class="fa-solid fa-clock"></i>
            <?php else: ?>
                <i class="fa-solid fa-circle-check"></i>
            <?php endif; ?>
        </div>
        <div class="alerte-sauvegarde-content">
            <strong>
                <?php if ($alerteClass === 'danger'): ?>
                    <i class="fa-solid fa-triangle-exclamation"></i> Aucune sauvegarde récente ! La dernière date de plus de 3 jours.
                <?php elseif ($alerteClass === 'warning'): ?>
                    <i class="fa-solid fa-clock"></i> La dernière sauvegarde date de plus de 24 heures.
                <?php else: ?>
                    <i class="fa-solid fa-circle-check"></i> Dernière sauvegarde effectuée récemment.
                <?php endif; ?>
            </strong>
            <span>Dernière sauvegarde : <strong><?= e($derniereSauvegarde['date_format']) ?></strong> par <?= e($derniereSauvegarde['auteur_nom']) ?> — <?= $derniereSauvegarde['taille_fichier'] > 1048576 ? round($derniereSauvegarde['taille_fichier'] / 1048576, 1) . ' Mo' : round($derniereSauvegarde['taille_fichier'] / 1024, 1) . ' Ko' ?></span>
        </div>
        <?php if ($alerteClass !== 'success'): ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btnSauvegarder').click()">
                <i class="fa-solid fa-floppy-disk"></i> Sauvegarder
            </button>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="alerte-sauvegarde alerte-danger mb-4">
        <div class="alerte-sauvegarde-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="alerte-sauvegarde-content">
            <strong><i class="fa-solid fa-triangle-exclamation"></i> Aucune sauvegarde trouvée !</strong>
            <span>Il est fortement recommandé de créer une sauvegarde immédiatement pour protéger vos données.</span>
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btnSauvegarder').click()">
            <i class="fa-solid fa-floppy-disk"></i> Créer une sauvegarde
        </button>
    </div>
<?php endif; ?>

<!-- ============================================================
     LISTE DES SAUVEGARDES
     ============================================================ -->
<!-- ============================================================
     LISTE DES SAUVEGARDES
     ============================================================ -->
<div class="sauvegardes-table-card">
    <div class="table-header-info">
        <span><strong id="totalSauvegardes">—</strong> sauvegarde(s) trouvée(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Les sauvegardes sont stockées dans <code>/storage/backups/</code>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px;">Fichier</th>
                    <th>Nom</th>
                    <th>Taille</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Auteur</th>
                    <th>Statut</th>
                    <th style="width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodySauvegardes">
                <tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement...</p></div></td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationSauvegardes"></div>
    </div>
</div>

<!-- ============================================================
     GUIDE DES BONNES PRATIQUES
     ============================================================ -->
<div class="info-card-custom mt-4">
    <div class="info-card-custom-header">
        <div class="info-card-custom-icon blue">
            <i class="fa-solid fa-circle-info"></i>
        </div>
        <h6>Bonnes pratiques de sauvegarde</h6>
    </div>
    <div class="info-card-custom-body">
        <ul class="info-list">
            <li><strong><i class="fa-solid fa-clock"></i> Fréquence recommandée :</strong> Effectuez une sauvegarde quotidienne, surtout avant toute opération sensible (modification des notes, clôture de trimestre, etc.).</li>
            <li><strong><i class="fa-solid fa-cloud-arrow-up"></i> Stockage externe :</strong> Téléchargez régulièrement les sauvegardes sur un support externe (clé USB, disque dur) ou un service cloud (Google Drive, Dropbox).</li>
            <li><strong><i class="fa-solid fa-shield-halved"></i> Restauration :</strong> Une sauvegarde de sécurité est automatiquement créée avant chaque restauration. En cas de problème, vous pourrez revenir en arrière.</li>
            <li><strong><i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i> Attention :</strong> La restauration remplace toutes les données actuelles par celles de la sauvegarde. Cette action est irréversible.</li>
            <li><strong><i class="fa-solid fa-hard-drive"></i> Espace disque :</strong> Surveillez l'espace disponible (actuellement <strong><?= $espaceDisqueFormate ?></strong> libres). Supprimez les anciennes sauvegardes inutiles.</li>
            <li><strong><i class="fa-solid fa-gear"></i> Automatisation :</strong> Pour les environnements de production, configurez une tâche planifiée (cron) pour automatiser les sauvegardes quotidiennes.</li>
        </ul>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    chargerSauvegardes(1);
    initBoutonSauvegarde();
    initRaccourcisClavier();
});

// ============================================================
// CHARGEMENT DES SAUVEGARDES
// ============================================================
function chargerSauvegardes(page) {
    var tbody = document.getElementById('tbodySauvegardes');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state" style="padding:30px;"><i class="fa-solid fa-spinner fa-spin"></i><p>Chargement des sauvegardes...</p></div></td></tr>';

    var formData = new FormData();
    formData.append('action', 'getListe');
    formData.append('page', page || 1);

    fetch('/gestion_scolaire/sauvegardes', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            afficherSauvegardes(data);
        } else {
            showToast(data.message || 'Erreur de chargement.', 'error');
        }
    })
    .catch(function() {
        tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state" style="padding:30px;"><i class="fa-solid fa-circle-exclamation"></i><p>Erreur de connexion au serveur.</p></div></td></tr>';
    });
}

// ============================================================
// AFFICHAGE DES SAUVEGARDES
// ============================================================
function afficherSauvegardes(data) {
    var tbody = document.getElementById('tbodySauvegardes');
    var totalSpan = document.getElementById('totalSauvegardes');
    var paginationDiv = document.getElementById('paginationSauvegardes');
    var paginationInfo = document.getElementById('paginationInfo');

    if (!tbody) return;
    if (totalSpan) totalSpan.textContent = data.total;

    if (!data.sauvegardes || data.sauvegardes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state" style="padding:40px;"><i class="fa-solid fa-database"></i><p>Aucune sauvegarde trouvée.</p><p style="font-size:0.75rem;color:#94a3b8;">Cliquez sur "Sauvegarder maintenant" pour créer votre première sauvegarde.</p></div></td></tr>';
        if (paginationDiv) paginationDiv.innerHTML = '';
        if (paginationInfo) paginationInfo.textContent = '';
        return;
    }

    tbody.innerHTML = data.sauvegardes.map(function(s) {
        var typeBadge = s.type_sauvegarde === 'Manuelle' 
            ? '<span class="badge badge-primary"><i class="fa-solid fa-hand-pointer"></i> Manuelle</span>'
            : '<span class="badge badge-info"><i class="fa-solid fa-robot"></i> Automatique</span>';
        
        var statutBadge = '';
        if (s.statut === 'Succès') {
            statutBadge = s.fichier_existe 
                ? '<span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Disponible</span>'
                : '<span class="badge badge-danger"><i class="fa-solid fa-triangle-exclamation"></i> Fichier manquant</span>';
        } else if (s.statut === 'Échec') {
            statutBadge = '<span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Échec</span>';
        } else {
            statutBadge = '<span class="badge badge-warning"><i class="fa-solid fa-spinner"></i> ' + escapeHtml(s.statut) + '</span>';
        }

        return `
        <tr>
            <td><i class="fa-solid fa-file-zipper" style="font-size:1.3rem;color:#2c3e90;"></i></td>
            <td><strong>${escapeHtml(s.nom_fichier)}</strong></td>
            <td>${escapeHtml(s.taille_formatee)}</td>
            <td>${typeBadge}</td>
            <td>${escapeHtml(s.date_format)} <small class="text-muted">${escapeHtml(s.heure_format)}</small></td>
            <td><small>${escapeHtml(s.auteur_nom || 'Système')}</small></td>
            <td>${statutBadge}</td>
            <td>
                <div class="d-flex gap-xs">
                    ${s.fichier_existe && s.statut === 'Succès' ? `
                    <a href="/gestion_scolaire/sauvegardes/telecharger/${s.id}" class="btn btn-sm btn-outline-success" title="Télécharger cette sauvegarde">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="restaurerSauvegarde(${s.id}, '${escapeHtml(s.nom_fichier)}')" title="Restaurer cette sauvegarde">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                    ` : ''}
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="supprimerSauvegarde(${s.id}, '${escapeHtml(s.nom_fichier)}')" title="Supprimer cette sauvegarde">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    // Pagination
    if (data.pages > 1) {
        var html = '';
        for (var i = 1; i <= data.pages; i++) {
            html += '<a href="#" onclick="chargerSauvegardes(' + i + '); return false;" class="' + (i === data.page ? 'active' : '') + '">' + i + '</a>';
        }
        if (paginationDiv) paginationDiv.innerHTML = html;
        if (paginationInfo) paginationInfo.textContent = 'Page ' + data.page + ' / ' + data.pages + ' — ' + data.total + ' sauvegarde(s)';
    } else {
        if (paginationDiv) paginationDiv.innerHTML = '';
        if (paginationInfo) paginationInfo.textContent = '';
    }
}

// ============================================================
// BOUTON SAUVEGARDER
// ============================================================
function initBoutonSauvegarde() {
    var btn = document.getElementById('btnSauvegarder');
    if (!btn) return;

    btn.addEventListener('click', function() {
        if (this.disabled) return;

        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde en cours...';

        var formData = new FormData();
        formData.append('action', 'sauvegarder');

        fetch('/gestion_scolaire/sauvegardes', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast(data.message + ' (' + data.taille + ')', 'success');
                chargerSauvegardes(1);
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showToast(data.message || 'Erreur lors de la sauvegarde.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Sauvegarder maintenant';
            }
        })
        .catch(function() {
            showToast('Erreur de connexion au serveur.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Sauvegarder maintenant';
        });
    });
}

// ============================================================
// SUPPRIMER UNE SAUVEGARDE
// ============================================================
function supprimerSauvegarde(id, nom) {
    showConfirmDialog(
        'Voulez-vous supprimer la sauvegarde <strong>' + escapeHtml(nom) + '</strong> ?<br><small>Cette action est irréversible. Le fichier sera définitivement supprimé du serveur.</small>',
        function(confirmed) {
            if (confirmed) {
                var formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('sauvegarde_id', id);

                fetch('/gestion_scolaire/sauvegardes', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        showToast(data.message, 'success');
                        chargerSauvegardes(1);
                    } else {
                        showToast(data.message || 'Erreur lors de la suppression.', 'error');
                    }
                })
                .catch(function() {
                    showToast('Erreur de connexion.', 'error');
                });
            }
        },
        {
            title: 'Supprimer la sauvegarde',
            confirmText: 'Supprimer',
            confirmClass: 'btn-danger',
            icon: 'fa-trash',
            iconColor: '#ef4444'
        }
    );
}

// ============================================================
// RESTAURER UNE SAUVEGARDE
// ============================================================
function restaurerSauvegarde(id, nom) {
    showConfirmDialog(
        '<div style="text-align:left;">' +
        '<div style="background:#fef2f2;border:2px solid #ef4444;border-radius:12px;padding:16px;margin-bottom:14px;">' +
        '<div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#991b1b;font-size:0.9rem;margin-bottom:8px;">' +
        '<i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;font-size:1.2rem;"></i> ATTENTION : Cette action est irréversible !</div>' +
        '<div style="font-size:0.82rem;color:#b91c1c;line-height:1.6;">' +
        'Vous allez restaurer la sauvegarde <strong>' + escapeHtml(nom) + '</strong>.<br>' +
        'Toutes les données actuelles seront remplacées par celles de la sauvegarde.</div>' +
        '</div>' +
        '<p style="font-size:0.82rem;color:#64748b;margin-bottom:0;">' +
        '<i class="fa-solid fa-info-circle"></i> Une sauvegarde de sécurité sera automatiquement créée avant la restauration.</p>' +
        '</div>',
        function(confirmed) {
            if (confirmed) {
                var formData = new FormData();
                formData.append('action', 'restaurer');
                formData.append('sauvegarde_id', id);

                var btn = document.getElementById('btnSauvegarder');
                if (btn) btn.disabled = true;

                showToast('Restauration en cours... Veuillez patienter.', 'info', 30000);

                fetch('/gestion_scolaire/sauvegardes', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        showToast(data.message || 'Erreur lors de la restauration.', 'error');
                        if (btn) btn.disabled = false;
                    }
                })
                .catch(function() {
                    showToast('Erreur de connexion au serveur.', 'error');
                    if (btn) btn.disabled = false;
                });
            }
        },
        {
            title: '⚠️ Restaurer la base de données',
            confirmText: 'Je comprends, restaurer',
            confirmClass: 'btn-warning',
            cancelText: 'Annuler',
            icon: 'fa-triangle-exclamation',
            iconColor: '#ef4444'
        }
    );
}

// ============================================================
// RACCOURCIS CLAVIER
// ============================================================
function initRaccourcisClavier() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+B : Sauvegarder
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            var btn = document.getElementById('btnSauvegarder');
            if (btn && !btn.disabled) {
                btn.click();
            }
        }
    });
}

// ============================================================
// UTILITAIRES
// ============================================================
function escapeHtml(texte) {
    if (!texte) return '';
    var div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>