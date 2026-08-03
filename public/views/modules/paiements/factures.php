<?php
/**
 * Fichier : public/views/modules/paiements/factures.php
 * Rôle    : Liste des reçus/factures générés avec filtres et recherche
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

// Récupérer les classes pour le filtre
$classes = $db->fetchAll(
    "SELECT c.* FROM classes c
     WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
     ORDER BY c.niveau"
);

// Paramètres de filtre
$classeId = (int) ($_GET['classe_id'] ?? 0);
$dateDebut = $_GET['date_debut'] ?? '';
$dateFin = $_GET['date_fin'] ?? '';
$recherche = $_GET['recherche'] ?? '';

// Construire la requête
$where = [];
$params = [];

if ($classeId > 0) {
    $where[] = "c.id = :classe";
    $params['classe'] = $classeId;
}

if (!empty($dateDebut)) {
    $where[] = "f.date_emission >= :debut";
    $params['debut'] = $dateDebut;
}

if (!empty($dateFin)) {
    $where[] = "f.date_emission <= :fin";
    $params['fin'] = $dateFin;
}

if (!empty($recherche)) {
    $where[] = "(e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3 OR f.numero_facture LIKE :r4)";
    $params['r1'] = "%{$recherche}%";
    $params['r2'] = "%{$recherche}%";
    $params['r3'] = "%{$recherche}%";
    $params['r4'] = "%{$recherche}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Récupérer les factures
$factures = $db->fetchAll(
    "SELECT f.*, e.matricule, e.nom, e.prenom, e.sexe,
            c.nom AS classe_nom, c.niveau AS classe_niveau,
            fl.libelle AS frais_libelle,
            p.montant_paye, p.montant_total, p.mode_paiement, p.statut, p.reference_paiement,
            DATE_FORMAT(f.date_emission, '%d/%m/%Y') AS date_format,
            TIME_FORMAT(f.date_emission, '%H:%i') AS heure_format,
            CONCAT(u.prenom, ' ', u.nom) AS emis_par_nom
     FROM factures f
     INNER JOIN eleves e ON f.eleve_id = e.id
     INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     INNER JOIN classes c ON i.classe_id = c.id
     INNER JOIN paiements p ON f.paiement_id = p.id
     INNER JOIN frais_scolaires fl ON p.frais_scolaire_id = fl.id
     LEFT JOIN utilisateurs u ON f.emise_par = u.id
     {$whereClause}
     ORDER BY f.date_emission DESC
     LIMIT 200",
    $params
);

// Statistiques
$totalFactures = count($factures);
$totalMontant = array_sum(array_column($factures, 'montant_paye'));
$nbFacturesMois = count(array_filter($factures, function($f) {
    return substr($f['date_emission'], 5, 2) === date('m');
}));
$montantMois = array_sum(array_column(array_filter($factures, function($f) {
    return substr($f['date_emission'], 5, 2) === date('m');
}), 'montant_paye'));

$pageTitle = 'Factures et reçus';
$pageIcon  = 'fa-receipt';
$pageDescription = 'Liste des reçus et factures générés';
$extraCSS = ['modules/paiements.css'];
$extraJS  = ['modules/paiements.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/paiements">Paiements</a>
    <span class="separator">/</span>
    <span class="current">Factures</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-receipt"></i> Factures et reçus</h4>
        <p>Liste de tous les reçus générés lors des paiements.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/paiements/enregistrer" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Nouveau paiement
        </a>
        <a href="/gestion_scolaire/paiements" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Paiements
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="retards-stats mb-4">
    <div class="retard-stat-card">
        <div class="retard-stat-icon blue">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value"><?= number_format($totalFactures, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Total reçus</div>
            <span class="stat-change neutral"><?= $nbFacturesMois ?> ce mois</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon green">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value">$ <?= number_format($totalMontant, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Total encaissé</div>
            <span class="stat-change up">Tous les reçus</span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon orange">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div class="retard-stat-content">
            <div class="retard-stat-value">$ <?= number_format($montantMois, 0, ',', ' ') ?></div>
            <div class="retard-stat-label">Encaissé ce mois</div>
            <span class="stat-change neutral"><?= moisEnFrancais((int) date('m')) ?></span>
        </div>
    </div>
    <div class="retard-stat-card">
        <div class="retard-stat-icon purple">
            <i class="fa-solid fa-credit-card"></i>
        </div>
        <div class="retard-stat-content">
            <?php 
            $modes = array_count_values(array_column($factures, 'mode_paiement'));
            $modePrincipal = !empty($modes) ? array_key_first($modes) : '—';
            ?>
            <div class="retard-stat-value" style="font-size:1.1rem;"><?= e($modePrincipal) ?></div>
            <div class="retard-stat-label">Mode principal</div>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTRES
     ============================================================ -->
<div class="retards-filters no-print">
    <form method="GET" action="" class="resultats-selector-form" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;width:100%;">
        <div class="filter-group">
            <label><i class="fa-solid fa-chalkboard"></i> Classe</label>
            <select name="classe_id" class="form-control">
                <option value="">Toutes les classes</option>
                <?php foreach ($classes as $cl): ?>
                    <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>><?= e($cl['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><i class="fa-solid fa-calendar"></i> Du</label>
            <input type="date" name="date_debut" class="form-control" value="<?= e($dateDebut) ?>">
        </div>
        <div class="filter-group">
            <label><i class="fa-solid fa-calendar"></i> Au</label>
            <input type="date" name="date_fin" class="form-control" value="<?= e($dateFin) ?>">
        </div>
        <div class="filter-group">
            <label><i class="fa-solid fa-search"></i> Recherche</label>
            <input type="text" name="recherche" class="form-control" placeholder="Nom, matricule, N° reçu..." value="<?= e($recherche) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end;">
            <i class="fa-solid fa-filter"></i> Filtrer
        </button>
        <?php if (!empty($classeId) || !empty($dateDebut) || !empty($dateFin) || !empty($recherche)): ?>
            <a href="/gestion_scolaire/paiements/factures" class="btn btn-outline-primary btn-sm" style="align-self:flex-end;">
                <i class="fa-solid fa-rotate"></i> Réinitialiser
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- ============================================================
     TABLEAU
     ============================================================ -->
<div class="retards-table-card">
    <div class="table-header-info" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border-color-light);">
        <span><strong><?= $totalFactures ?></strong> reçu(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur <i class="fa-solid fa-eye"></i> pour voir le reçu
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Reçu</th>
                    <th>Date</th>
                    <th>Matricule</th>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>Frais</th>
                    <th>Montant</th>
                    <th>Mode</th>
                    <th>Statut</th>
                    <th>Émis par</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($factures)): ?>
                    <tr>
                        <td colspan="11">
                            <div class="empty-state" style="padding:40px;">
                                <i class="fa-solid fa-receipt"></i>
                                <p>Aucun reçu généré.</p>
                                <a href="/gestion_scolaire/paiements/enregistrer" class="btn btn-primary btn-sm mt-3">
                                    <i class="fa-solid fa-plus"></i> Enregistrer un paiement
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($factures as $f): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= e($f['numero_facture']) ?></span></td>
                            <td>
                                <strong><?= e($f['date_format']) ?></strong>
                                <br><small class="text-muted"><?= e($f['heure_format']) ?></small>
                            </td>
                            <td><span class="badge badge-secondary"><?= e($f['matricule']) ?></span></td>
                            <td><strong><?= e($f['prenom'] . ' ' . $f['nom']) ?></strong></td>
                            <td><?= e($f['classe_nom']) ?></td>
                            <td><?= e($f['frais_libelle']) ?></td>
                            <td>
                                <strong style="color:#065f46;">$ <?= number_format($f['montant_paye'], 2, ',', ' ') ?></strong>
                                <?php if ($f['montant_total'] > $f['montant_paye']): ?>
                                    <br><small class="text-danger">/ $ <?= number_format($f['montant_total'], 2, ',', ' ') ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $modeIcons = [
                                    'Espèces' => 'fa-money-bill',
                                    'Mobile Money' => 'fa-mobile-screen',
                                    'Chèque' => 'fa-money-check',
                                    'Virement bancaire' => 'fa-building-columns'
                                ];
                                $modeIcon = $modeIcons[$f['mode_paiement']] ?? 'fa-credit-card';
                                ?>
                                <i class="fa-solid <?= $modeIcon ?>"></i> <?= e($f['mode_paiement']) ?>
                                <?php if (!empty($f['reference_paiement'])): ?>
                                    <br><small class="text-muted"><?= e($f['reference_paiement']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $f['statut'] === 'Payé' ? 'badge-success' : ($f['statut'] === 'Partiel' ? 'badge-warning' : 'badge-danger') ?>">
                                    <?= e($f['statut']) ?>
                                </span>
                            </td>
                            <td><small><?= e($f['emis_par_nom'] ?? 'Système') ?></small></td>
                            <td>
                                <div class="d-flex gap-xs">
                                    <a href="/gestion_scolaire/paiements/recu?facture_id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir le reçu" target="_blank">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="/gestion_scolaire/paiements/recu?facture_id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-success" title="Imprimer" onclick="window.open(this.href); return false;">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalFactures >= 200): ?>
        <div style="padding:16px;text-align:center;background:#fef3c7;color:#92400e;font-size:0.82rem;">
            <i class="fa-solid fa-info-circle"></i> Affichage limité aux 200 derniers reçus. Utilisez les filtres pour affiner la recherche.
        </div>
    <?php endif; ?>
</div>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>