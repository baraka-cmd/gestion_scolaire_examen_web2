<?php
/**
 * Fichier : public/views/modules/notifications/liste.php
 * Rôle    : Consultation des notifications (Admin voit tout, Parent voit les siennes)
 * Système : École primaire congolaise — Communication
 * Version : 2.0 — Admin voit toutes les notifications
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'NotificationController.php';
$notificationController = new NotificationController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $notificationController->handleAjax();
}

// Déterminer si admin
$isAdmin = hasRole(['Admin', 'Directeur']);
$userId = $_SESSION['utilisateur_id'];

// Récupérer les notifications pour affichage initial
if ($isAdmin) {
    $notificationsList = $db->fetchAll(
        "SELECT n.*, DATE_FORMAT(n.date_envoi, '%d/%m/%Y') AS date_format,
                TIME_FORMAT(n.date_envoi, '%H:%i') AS heure_format,
                DATE_FORMAT(n.date_lecture, '%d/%m/%Y') AS date_lecture_format,
                CONCAT(u.nom, ' ', u.prenom) AS destinataire_nom,
                u.login AS destinataire_login
         FROM notifications n
         LEFT JOIN utilisateurs u ON n.utilisateur_id = u.id
         ORDER BY n.date_envoi DESC
         LIMIT 50"
    );
} else {
    $notificationsList = $db->fetchAll(
        "SELECT n.*, DATE_FORMAT(n.date_envoi, '%d/%m/%Y') AS date_format,
                TIME_FORMAT(n.date_envoi, '%H:%i') AS heure_format,
                DATE_FORMAT(n.date_lecture, '%d/%m/%Y') AS date_lecture_format
         FROM notifications n
         WHERE n.utilisateur_id = :uid
         ORDER BY n.date_envoi DESC
         LIMIT 50",
        ['uid' => $userId]
    );
}

$nbNonLues = count(array_filter($notificationsList, fn($n) => !$n['est_lue']));
$nbLues = count($notificationsList) - $nbNonLues;
$nbAlertes = count(array_filter($notificationsList, fn($n) => $n['type_notification'] === 'Alerte' || $n['type_notification'] === 'Urgence'));

$pageTitle = $isAdmin ? 'Toutes les notifications' : 'Mes notifications';
$pageIcon  = 'fa-bell';
$pageDescription = $isAdmin ? 'Consultez toutes les notifications envoyées dans le système' : 'Consultez vos notifications';
$extraCSS = ['modules/notifications.css'];
$extraJS  = ['modules/notifications.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Notifications</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-bell"></i> <?= $isAdmin ? 'Toutes les notifications' : 'Mes notifications' ?></h4>
        <p><?= $isAdmin ? 'Consultez toutes les notifications envoyées dans le système.' : 'Consultez vos notifications et alertes.' ?></p>
    </div>
    <div class="resultats-header-right">
        <?php if ($nbNonLues > 0): ?>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnMarquerToutesLues">
            <i class="fa-solid fa-check-double"></i> Tout marquer comme lu
        </button>
        <?php endif; ?>
        <?php if (can('notifications.envoyer')): ?>
        <a href="/gestion_scolaire/notifications/envoyer" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-paper-plane"></i> Envoyer une notification
        </a>
        <?php endif; ?>
    </div>
</div>

<div id="flashMessage"></div>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="retards-stats mb-4">
    <div class="retard-stat-card">
        <div class="retard-stat-icon blue"><i class="fa-solid fa-bell"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= count($notificationsList) ?></div>
            <div class="retard-stat-label">Total notifications</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $nbNonLues ?></div>
            <div class="retard-stat-label">Non lues</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $nbLues ?></div>
            <div class="retard-stat-label">Lues</div>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon orange"><i class="fa-solid fa-envelope"></i></div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= $nbAlertes ?></div>
            <div class="retard-stat-label">Alertes / Urgences</div>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTRES
     ============================================================ -->
<div class="retards-filters no-print">
    <div class="filter-group">
        <label><i class="fa-solid fa-tag"></i> Type</label>
        <select id="filterType" onchange="appliquerFiltres()">
            <option value="">Tous les types</option>
            <option value="Information">Information</option>
            <option value="Alerte">Alerte</option>
            <option value="Rappel">Rappel</option>
            <option value="Urgence">Urgence</option>
            <option value="Succès">Succès</option>
        </select>
    </div>
    <div class="filter-group">
        <label><i class="fa-solid fa-check-circle"></i> Statut</label>
        <select id="filterLu" onchange="appliquerFiltres()">
            <option value="">Tous</option>
            <option value="0">Non lues</option>
            <option value="1">Lues</option>
        </select>
    </div>
    <?php if ($isAdmin): ?>
    <div class="filter-group">
        <label><i class="fa-solid fa-search"></i> Recherche</label>
        <input type="text" id="filterRecherche" placeholder="Nom destinataire..." onkeyup="appliquerFiltres()">
    </div>
    <?php endif; ?>
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="reinitialiserFiltres()" style="align-self:flex-end;">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     LISTE DES NOTIFICATIONS
     ============================================================ -->
<div class="notifications-list" id="notificationsList">
    <?php if (empty($notificationsList)): ?>
        <div class="empty-state" style="padding:60px 20px;">
            <i class="fa-solid fa-bell-slash"></i>
            <p><?= $isAdmin ? 'Aucune notification dans le système.' : 'Vous n\'avez aucune notification.' ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($notificationsList as $notif): 
            $typeIcon = match($notif['type_notification']) {
                'Alerte' => 'fa-circle-exclamation',
                'Urgence' => 'fa-triangle-exclamation',
                'Rappel' => 'fa-clock',
                'Succès' => 'fa-circle-check',
                default => 'fa-circle-info'
            };
            $typeColor = match($notif['type_notification']) {
                'Alerte' => '#f59e0b',
                'Urgence' => '#ef4444',
                'Rappel' => '#3b82f6',
                'Succès' => '#10b981',
                default => '#64748b'
            };
        ?>
            <div class="notification-item <?= !$notif['est_lue'] ? 'non-lue' : '' ?>" 
                 data-id="<?= $notif['id'] ?>"
                 data-lu="<?= $notif['est_lue'] ?>">
                
                <div class="notification-icon" style="background:<?= $typeColor ?>15;color:<?= $typeColor ?>;">
                    <i class="fa-solid <?= $typeIcon ?>"></i>
                </div>

                <div class="notification-content" <?= !$notif['est_lue'] ? 'onclick="marquerLue(' . $notif['id'] . ', this)"' : '' ?>>
                    <div class="notification-header">
                        <span class="notification-titre"><?= e($notif['titre']) ?></span>
                        <?php if (!$notif['est_lue']): ?>
                            <span class="notification-badge-non-lue">Nouveau</span>
                        <?php endif; ?>
                    </div>
                    <p class="notification-message"><?= nl2br(e($notif['message'])) ?></p>
                    <div class="notification-meta">
                        <span class="notification-date">
                            <i class="fa-regular fa-clock"></i>
                            <?= e($notif['date_format']) ?> à <?= e($notif['heure_format']) ?>
                        </span>
                        <?php if ($isAdmin && !empty($notif['destinataire_nom'])): ?>
                            <span class="notification-destinataire">
                                <i class="fa-solid fa-user"></i>
                                <?= e($notif['destinataire_nom']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($notif['est_lue'] && $notif['date_lecture_format']): ?>
                            <span class="notification-date-lecture">
                                <i class="fa-solid fa-check"></i> Lu le <?= e($notif['date_lecture_format']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="notification-type-badge" style="background:<?= $typeColor ?>20;color:<?= $typeColor ?>;">
                            <?= e($notif['type_notification']) ?>
                        </span>
                    </div>
                </div>

                <div class="notification-actions">
                    <?php if ($notif['lien']): ?>
                        <a href="<?= e($notif['lien']) ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-notif" 
                            data-id="<?= $notif['id'] ?>" title="Supprimer">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ============================================================
     PAGINATION
     ============================================================ -->
<div class="pagination-wrapper" id="paginationWrapper" style="display:none;">
    <div class="pagination-info" id="paginationInfo"></div>
    <div class="pagination" id="paginationNotifications"></div>
</div>

<script>
var isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

// ============================================================
// FILTRES
// ============================================================
function getFiltres() {
    var filtres = {
        type: document.getElementById('filterType')?.value || '',
        lu: document.getElementById('filterLu')?.value || ''
    };
    if (isAdmin) {
        filtres.recherche = document.getElementById('filterRecherche')?.value || '';
    }
    return filtres;
}

function appliquerFiltres() { chargerNotifications(1); }

function reinitialiserFiltres() {
    document.getElementById('filterType').value = '';
    document.getElementById('filterLu').value = '';
    if (isAdmin) {
        var rech = document.getElementById('filterRecherche');
        if (rech) rech.value = '';
    }
    chargerNotifications(1);
}

// ============================================================
// CHARGEMENT
// ============================================================
function chargerNotifications(page) {
    var formData = new FormData();
    formData.append('action', 'getListe');
    formData.append('page', page);

    var filtres = getFiltres();
    Object.keys(filtres).forEach(function(key) {
        if (filtres[key] !== '') formData.append(key, filtres[key]);
    });

    fetch('/gestion_scolaire/notifications', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) afficherNotifications(data);
    });
}

function afficherNotifications(data) {
    var container = document.getElementById('notificationsList');

    if (!data.notifications || data.notifications.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding:60px 20px;"><i class="fa-solid fa-bell-slash"></i><p>Aucune notification trouvée.</p></div>';
        document.getElementById('paginationWrapper').style.display = 'none';
        return;
    }

    var typeIcons = {
        'Alerte': 'fa-circle-exclamation',
        'Urgence': 'fa-triangle-exclamation',
        'Rappel': 'fa-clock',
        'Succès': 'fa-circle-check',
        'Information': 'fa-circle-info'
    };
    var typeColors = {
        'Alerte': '#f59e0b',
        'Urgence': '#ef4444',
        'Rappel': '#3b82f6',
        'Succès': '#10b981',
        'Information': '#64748b'
    };

    container.innerHTML = data.notifications.map(function(n) {
        var icon = typeIcons[n.type_notification] || 'fa-circle-info';
        var color = typeColors[n.type_notification] || '#64748b';
        var destinataireHtml = (data.is_admin && n.destinataire_nom) ? 
            '<span class="notification-destinataire"><i class="fa-solid fa-user"></i> ' + escapeHtml(n.destinataire_nom) + '</span>' : '';
        var lienHtml = n.lien ? 
            '<a href="' + escapeHtml(n.lien) + '" class="btn btn-sm btn-outline-primary" title="Voir"><i class="fa-solid fa-arrow-right"></i></a>' : '';

        return `
        <div class="notification-item ${!n.est_lue ? 'non-lue' : ''}" data-id="${n.id}" data-lu="${n.est_lue}">
            <div class="notification-icon" style="background:${color}15;color:${color};">
                <i class="fa-solid ${icon}"></i>
            </div>
            <div class="notification-content" ${!n.est_lue ? 'onclick="marquerLue(' + n.id + ', this)"' : ''}>
                <div class="notification-header">
                    <span class="notification-titre">${escapeHtml(n.titre)}</span>
                    ${!n.est_lue ? '<span class="notification-badge-non-lue">Nouveau</span>' : ''}
                </div>
                <p class="notification-message">${escapeHtml(n.message).replace(/\n/g, '<br>')}</p>
                <div class="notification-meta">
                    <span class="notification-date"><i class="fa-regular fa-clock"></i> ${n.date_format} à ${n.heure_format}</span>
                    ${destinataireHtml}
                    ${n.est_lue && n.date_lecture_format ? '<span class="notification-date-lecture"><i class="fa-solid fa-check"></i> Lu le ' + n.date_lecture_format + '</span>' : ''}
                    <span class="notification-type-badge" style="background:${color}20;color:${color};">${n.type_notification}</span>
                </div>
            </div>
            <div class="notification-actions">
                ${lienHtml}
                <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-notif" data-id="${n.id}" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>`;
    }).join('');

    // Pagination
    var paginationWrapper = document.getElementById('paginationWrapper');
    if (data.pages > 1) {
        paginationWrapper.style.display = 'flex';
        var html = '';
        for (var i = 1; i <= data.pages; i++) {
            html += '<a href="#" onclick="chargerNotifications(' + i + '); return false;" class="' + (i === data.page ? 'active' : '') + '">' + i + '</a>';
        }
        document.getElementById('paginationNotifications').innerHTML = html;
        document.getElementById('paginationInfo').textContent = 'Page ' + data.page + ' / ' + data.pages + ' — ' + data.total + ' notification(s)';
    } else {
        paginationWrapper.style.display = 'none';
    }

    attachEvents();
}

// ============================================================
// MARQUER COMME LUE
// ============================================================
function marquerLue(notificationId, element) {
    var item = element.closest('.notification-item');
    if (!item || item.dataset.lu === '1') return;

    var formData = new FormData();
    formData.append('action', 'marquerLue');
    formData.append('notification_id', notificationId);

    fetch('/gestion_scolaire/notifications', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            item.classList.remove('non-lue');
            item.dataset.lu = '1';
            var badge = item.querySelector('.notification-badge-non-lue');
            if (badge) badge.remove();
            item.querySelector('.notification-content').removeAttribute('onclick');
        }
    });
}

// ============================================================
// SUPPRIMER
// ============================================================
function attachEvents() {
    document.querySelectorAll('.btn-supprimer-notif').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var notificationId = this.dataset.id;
            var item = this.closest('.notification-item');

            var formData = new FormData();
            formData.append('action', 'supprimer');
            formData.append('notification_id', notificationId);

            fetch('/gestion_scolaire/notifications', {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (item) {
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(30px)';
                        item.style.transition = 'all 0.3s ease';
                        setTimeout(function() { item.remove(); }, 300);
                    }
                }
            });
        });
    });
}

// ============================================================
// TOUT MARQUER COMME LU
// ============================================================
document.getElementById('btnMarquerToutesLues')?.addEventListener('click', function() {
    var formData = new FormData();
    formData.append('action', 'marquerToutesLues');

    fetch('/gestion_scolaire/notifications', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(function() { location.reload(); }, 500);
        }
    });
});

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

attachEvents();
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>