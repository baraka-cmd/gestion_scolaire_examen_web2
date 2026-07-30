<?php
/**
 * Fichier : public/views/modules/matieres/liste.php
 * Rôle    : Liste hiérarchique des matières (Groupes → Sous-matières)
 * Système : Bulletin congolais
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('matieres.consulter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'MatiereController.php';
$matiereController = new MatiereController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $matiereController->handleAjax();
}

// Récupérer tous les groupes avec leurs sous-matières (données initiales)
$groupes = $db->fetchAll(
    "SELECT m.*,
            (SELECT COUNT(*) FROM matieres sm WHERE sm.parent_id = m.id AND sm.est_actif = 1) AS nb_sous_matieres
     FROM matieres m
     WHERE m.est_groupe = 1 AND m.est_actif = 1
     ORDER BY m.id"
);

foreach ($groupes as &$groupe) {
    $groupe['sous_matieres'] = $db->fetchAll(
        "SELECT m.*,
                (SELECT COUNT(*) FROM classe_matieres cm WHERE cm.matiere_id = m.id AND cm.est_active = 1) AS nb_classes
         FROM matieres m
         WHERE m.parent_id = :pid AND m.est_groupe = 0 AND m.est_actif = 1
         ORDER BY m.id",
        ['pid' => $groupe['id']]
    );

    foreach ($groupe['sous_matieres'] as &$sm) {
        $sm['classes'] = $db->fetchAll(
            "SELECT c.nom, c.niveau, COALESCE(cm.maxima_override, m2.maxima) AS maxima_final
             FROM classe_matieres cm
             INNER JOIN classes c ON cm.classe_id = c.id
             INNER JOIN matieres m2 ON cm.matiere_id = m2.id
             WHERE cm.matiere_id = :mid AND cm.est_active = 1
             AND c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau",
            ['mid' => $sm['id']]
        );
    }
    unset($sm);
}
unset($groupe);

// Statistiques
$totalGroupes = (int) $db->fetchColumn("SELECT COUNT(*) FROM matieres WHERE est_groupe = 1 AND est_actif = 1");
$totalSousMatieres = (int) $db->fetchColumn("SELECT COUNT(*) FROM matieres WHERE est_groupe = 0 AND parent_id IS NOT NULL AND est_actif = 1");
$totalAssociations = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM classe_matieres cm 
     INNER JOIN matieres m ON cm.matiere_id = m.id 
     WHERE m.est_groupe = 0 AND cm.est_active = 1"
);

// Métadonnées
$pageTitle = 'Gestion des matières';
$pageIcon  = 'fa-book-open';
$pageDescription = 'Gérez les groupes, sous-matières et leurs maxima';
$extraCSS = ['modules/matieres.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="matieres-header">
    <div class="matieres-header-left">
        <h4>
            <i class="fa-solid fa-book-open"></i> Gestion des matières
        </h4>
        <p>Gérez les groupes, sous-matières et leurs maxima par classe.</p>
    </div>
    <div class="matieres-header-right">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <?php if (can('matieres.creer')): ?>
            <a href="/gestion_scolaire/matieres/ajouter" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Nouvelle matière
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH (affiché par PHP en cas de succès/erreur)
     ============================================================ -->
<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
        <i class="fa-solid fa-<?= $flash['type'] === 'success' ? 'circle-check' : ($flash['type'] === 'error' ? 'circle-exclamation' : 'circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-layer-group"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalGroupes, 0, ',', ' ') ?></div>
            <div class="stat-label">Groupes de matières</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-list-ul"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalSousMatieres, 0, ',', ' ') ?></div>
            <div class="stat-label">Sous-matières</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-link"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalAssociations, 0, ',', ' ') ?></div>
            <div class="stat-label">Associations aux classes</div>
        </div>
    </div>
</div>

<!-- ============================================================
     LISTE HIÉRARCHIQUE DES MATIÈRES (RENDU PHP DIRECT)
     ============================================================ -->
<?php if (empty($groupes)): ?>
    <div class="empty-state" style="padding:40px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-book-open"></i>
        <p>Aucune matière trouvée</p>
        <?php if (can('matieres.creer')): ?>
            <a href="/gestion_scolaire/matieres/ajouter" class="btn btn-primary btn-sm mt-3">
                <i class="fa-solid fa-plus"></i> Créer une matière
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php 
    $colors = ['blue', 'green', 'orange', 'purple', 'pink', 'cyan'];
    $icons  = ['fa-book', 'fa-calculator', 'fa-flask', 'fa-palette', 'fa-landmark', 'fa-language'];
    ?>
    <?php foreach ($groupes as $gIndex => $groupe): ?>
        <?php $color = $colors[$gIndex % count($colors)]; ?>
        <?php $icon  = $icons[$gIndex % count($icons)]; ?>

        <!-- Carte Groupe -->
        <div style="background:#fff;border-radius:16px;border:1px solid var(--border-color);box-shadow:var(--shadow-xs);overflow:hidden;margin-bottom:24px;">
            
            <!-- En-tête du groupe -->
            <div style="background:linear-gradient(135deg,#2c3e90,#3b4fa0);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;color:#fff;">
                    <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                        <i class="fa-solid <?= $icon ?>"></i>
                    </div>
                    <div>
                        <strong style="font-size:1.05rem;"><?= e($groupe['libelle']) ?></strong>
                        <span style="display:block;font-size:0.72rem;opacity:0.75;">
                            <?= $groupe['code'] ? e($groupe['code']) . ' — ' : '' ?>
                            <?= $groupe['nb_sous_matieres'] ?> sous-matière(s)
                        </span>
                    </div>
                </div>
                <div style="display:flex;gap:6px;">
                    <?php if (can('matieres.modifier')): ?>
                        <a href="/gestion_scolaire/matieres/modifier/<?= $groupe['id'] ?>" 
                           class="btn btn-sm btn-outline-primary" 
                           style="color:#fff;border-color:rgba(255,255,255,0.4);">
                            <i class="fa-solid fa-pen-to-square"></i> Modifier
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sous-matières -->
            <div style="padding:16px 24px;">
                <?php if (empty($groupe['sous_matieres'])): ?>
                    <div class="empty-state" style="padding:20px;">
                        <i class="fa-solid fa-list-ul"></i>
                        <p>Aucune sous-matière dans ce groupe</p>
                    </div>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($groupe['sous_matieres'] as $sm): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;flex-wrap:wrap;gap:8px;">
                                
                                <!-- Infos sous-matière -->
                                <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:200px;">
                                    <div style="width:34px;height:34px;border-radius:8px;background:<?= $sm['nb_classes'] > 0 ? '#d1fae5' : '#f1f5f9' ?>;display:flex;align-items:center;justify-content:center;font-size:0.75rem;flex-shrink:0;">
                                        <i class="fa-solid fa-circle-<?= $sm['nb_classes'] > 0 ? 'check' : '' ?>" style="color:<?= $sm['nb_classes'] > 0 ? '#10b981' : '#94a3b8' ?>;"></i>
                                    </div>
                                    <div>
                                        <strong style="font-size:0.85rem;color:var(--color-dark);"><?= e($sm['libelle']) ?></strong>
                                        <?php if ($sm['code']): ?>
                                            <span style="font-size:0.7rem;color:var(--color-gray-400);margin-left:6px;">(<?= e($sm['code']) ?>)</span>
                                        <?php endif; ?>
                                        <div style="margin-top:3px;">
                                            <span class="badge badge-primary" style="font-size:0.68rem;">
                                                <i class="fa-solid fa-weight-scale"></i> Max: <?= $sm['maxima'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Classes associées -->
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <div style="max-width:300px;display:flex;flex-wrap:wrap;gap:3px;">
                                        <?php if (!empty($sm['classes'])): ?>
                                            <?php foreach ($sm['classes'] as $classe): ?>
                                                <span class="badge badge-info" style="font-size:0.66rem;padding:3px 8px;" 
                                                      title="Maxima: <?= $classe['maxima_final'] ?>">
                                                    <?= e($classe['nom']) ?> (<?= $classe['maxima_final'] ?>)
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="badge badge-secondary" style="font-size:0.66rem;">Aucune classe</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div style="display:flex;gap:4px;">
                                        <?php if (can('matieres.modifier')): ?>
                                            <a href="/gestion_scolaire/matieres/modifier/<?= $sm['id'] ?>" 
                                               class="btn-icon btn-icon-edit" style="width:30px;height:30px;" title="Modifier">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (can('matieres.supprimer')): ?>
                                            <button class="btn-icon btn-icon-delete" style="width:30px;height:30px;" 
                                                    onclick="deleteMatiere(<?= $sm['id'] ?>, '<?= e($sm['libelle']) ?>')" title="Supprimer">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Script pour la suppression -->
<script>
function deleteMatiere(id, nom) {
    if (confirm('Êtes-vous sûr de vouloir supprimer "' + nom + '" ? Cette action est irréversible.')) {
        var formData = new FormData();
        formData.append('action', 'supprimer');
        formData.append('id', id);
        
        fetch('/gestion_scolaire/matieres', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert('Erreur de connexion.'));
    }
}
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>