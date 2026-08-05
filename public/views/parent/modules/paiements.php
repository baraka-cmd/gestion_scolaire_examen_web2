<?php
/**
 * Fichier : public/views/parent/modules/paiements.php
 * Rôle    : Consultation des paiements — Vue Parent
 * Système : École primaire congolaise — Espace Parent
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

// Récupérer le parent connecté
$userId = $_SESSION['utilisateur_id'] ?? 0;
$parentId = (int) $db->fetchColumn(
    "SELECT pt.id FROM parents_tuteurs pt WHERE pt.utilisateur_id = :uid LIMIT 1",
    ['uid' => $userId]
);

if ($parentId <= 0) {
    setFlash('warning', 'Aucun profil parent trouvé. Contactez l\'administration.');
    redirect('/gestion_scolaire/dashboard');
}

// Récupérer les enfants du parent
$enfants = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
            c.nom AS classe_nom, c.niveau AS classe_niveau
     FROM eleve_parent ep
     INNER JOIN eleves e ON ep.eleve_id = e.id AND e.est_actif = 1
     LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     LEFT JOIN classes c ON i.classe_id = c.id
     WHERE ep.parent_id = :pid
     ORDER BY ep.est_tuteur_principal DESC, e.nom",
    ['pid' => $parentId]
);

if (empty($enfants)) {
    setFlash('info', 'Aucun enfant n\'est actuellement lié à votre compte.');
    redirect('/gestion_scolaire/parent/enfants');
}

// Sélectionner l'enfant
$eleveId = (int) ($_GET['enfant_id'] ?? $enfants[0]['id']);
$enfantAutorise = false;
$selectedEnfant = null;
foreach ($enfants as $enf) {
    if ($enf['id'] === $eleveId) {
        $enfantAutorise = true;
        $selectedEnfant = $enf;
        break;
    }
}
if (!$enfantAutorise) $selectedEnfant = $enfants[0];

// Récupérer tous les frais de l'année active
$anneeActive = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

$fraisActifs = $db->fetchAll(
    "SELECT f.*, 
            COALESCE(p.montant_total, f.montant) AS montant_attendu,
            COALESCE(p.montant_paye, 0) AS montant_paye,
            COALESCE(p.statut, 'Impayé') AS statut_paiement,
            p.id AS paiement_id,
            p.mode_paiement,
            p.reference_paiement,
            DATE_FORMAT(p.date_paiement, '%d/%m/%Y') AS date_paiement_format,
            p.commentaire AS commentaire_paiement
     FROM frais_scolaires f
     LEFT JOIN paiements p ON f.id = p.frais_scolaire_id AND p.eleve_id = :eid
     WHERE f.annee_scolaire_id = :aid AND f.est_actif = 1
     ORDER BY f.est_obligatoire DESC, f.libelle",
    ['eid' => $selectedEnfant['id'], 'aid' => $anneeActive]
);

// Calculer les totaux
$totalDu = 0;
$totalPaye = 0;
$nbPayes = 0;
$nbPartiels = 0;
$nbImpayes = 0;

foreach ($fraisActifs as $frais) {
    $totalDu += (float) $frais['montant_attendu'];
    $totalPaye += (float) $frais['montant_paye'];
    if ($frais['statut_paiement'] === 'Payé') $nbPayes++;
    elseif ($frais['statut_paiement'] === 'Partiel') $nbPartiels++;
    else $nbImpayes++;
}

$resteAPayer = $totalDu - $totalPaye;
$tauxPaiement = $totalDu > 0 ? round(($totalPaye / $totalDu) * 100) : 0;

// Récupérer l'historique détaillé des paiements
$historiquePaiements = $db->fetchAll(
    "SELECT p.*, f.libelle AS frais_libelle,
            DATE_FORMAT(p.date_paiement, '%d/%m/%Y') AS date_format,
            TIME_FORMAT(p.date_paiement, '%H:%i') AS heure_format
     FROM paiements p
     INNER JOIN frais_scolaires f ON p.frais_scolaire_id = f.id
     WHERE p.eleve_id = :eid
     ORDER BY p.date_paiement DESC",
    ['eid' => $selectedEnfant['id']]
);

// Récupérer les reçus/factures
$factures = $db->fetchAll(
    "SELECT fa.*, p.montant_paye, p.mode_paiement, f.libelle AS frais_libelle,
            DATE_FORMAT(fa.date_emission, '%d/%m/%Y') AS date_format,
            fa.numero_facture
     FROM factures fa
     INNER JOIN paiements p ON fa.paiement_id = p.id
     INNER JOIN frais_scolaires f ON p.frais_scolaire_id = f.id
     WHERE fa.eleve_id = :eid
     ORDER BY fa.date_emission DESC
     LIMIT 10",
    ['eid' => $selectedEnfant['id']]
);

$pageTitle = 'Paiements — ' . e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']);
$pageIcon  = 'fa-money-bill-wave';
$pageDescription = 'Suivi des paiements et frais scolaires';
$extraCSS = ['modules/paiements.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/parent/enfants">Mes enfants</a>
    <span class="separator">/</span>
    <span class="current">Paiements — <?= e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']) ?></span>
</nav>

<!-- EN-TÊTE -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-money-bill-wave"></i> Paiements — <?= e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']) ?></h4>
        <p>
            <i class="fa-solid fa-graduation-cap"></i> <?= e($selectedEnfant['matricule']) ?> — 
            <i class="fa-solid fa-chalkboard"></i> <?= e($selectedEnfant['classe_nom'] ?? 'Non inscrit') ?>
        </p>
    </div>
    <div class="resultats-header-right">
        <?php if (count($enfants) > 1): ?>
        <select id="enfantSelector" class="form-control" style="width:250px;" onchange="changerEnfant(this.value)">
            <?php foreach ($enfants as $enf): ?>
                <option value="<?= $enf['id'] ?>" <?= $selectedEnfant['id'] === (int) $enf['id'] ? 'selected' : '' ?>>
                    <?= e($enf['prenom'] . ' ' . $enf['nom']) ?> — <?= e($enf['classe_nom'] ?? 'Non inscrit') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <a href="/gestion_scolaire/parent/enfants" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- ============================================================
     STATISTIQUES FINANCIÈRES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-calculator"></i></div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($totalDu, 0, ',', ' ') ?></div>
            <div class="stat-label">Total à payer</div>
            <span class="stat-change neutral"><?= count($fraisActifs) ?> frais</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($totalPaye, 0, ',', ' ') ?></div>
            <div class="stat-label">Total payé</div>
            <span class="stat-change up"><?= $tauxPaiement ?>% réglé</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="stat-content">
            <div class="stat-value">$ <?= number_format($resteAPayer, 0, ',', ' ') ?></div>
            <div class="stat-label">Reste à payer</div>
            <?php if ($resteAPayer > 0): ?>
                <span class="stat-change down"><?= $nbImpayes ?> impayé(s)</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-chart-pie"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $tauxPaiement ?>%</div>
            <div class="stat-label">Taux de recouvrement</div>
            <span class="stat-change <?= $tauxPaiement >= 80 ? 'up' : ($tauxPaiement >= 50 ? 'neutral' : 'down') ?>">
                <?= $nbPayes ?> payé(s) / <?= $nbPartiels ?> partiel(s)
            </span>
        </div>
    </div>
</div>

<!-- ============================================================
     BARRE DE PROGRESSION GLOBALE
     ============================================================ -->
<div class="card mb-4">
    <div class="card-body">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <span style="font-weight:600;color:#1e293b;">
                <i class="fa-solid fa-chart-line text-primary"></i> Progression des paiements
            </span>
            <span style="font-weight:700;font-size:1.1rem;color:<?= $tauxPaiement >= 80 ? '#10b981' : ($tauxPaiement >= 50 ? '#f59e0b' : '#ef4444') ?>;">
                <?= $tauxPaiement ?>%
            </span>
        </div>
        <div style="width:100%;height:16px;background:#e2e8f0;border-radius:20px;overflow:hidden;">
            <div style="width:<?= $tauxPaiement ?>%;height:100%;background:linear-gradient(90deg,<?= $tauxPaiement >= 80 ? '#10b981,#34d399' : ($tauxPaiement >= 50 ? '#f59e0b,#fbbf24' : '#ef4444,#f87171') ?>);border-radius:20px;transition:width 0.8s ease;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:0.72rem;color:#94a3b8;">
            <span>$ 0</span>
            <span>$ <?= number_format($totalDu, 0, ',', ' ') ?></span>
        </div>
    </div>
</div>

<!-- ============================================================
     DÉTAIL DES FRAIS
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon blue"><i class="fa-solid fa-list-check"></i></div>
            <div>
                <div class="card-header-title">Détail des frais scolaires</div>
                <div class="card-header-subtitle">Année scolaire en cours</div>
            </div>
        </div>
        <div>
            <span class="badge badge-success"><?= $nbPayes ?> payé(s)</span>
            <span class="badge badge-warning ms-1"><?= $nbPartiels ?> partiel(s)</span>
            <span class="badge badge-danger ms-1"><?= $nbImpayes ?> impayé(s)</span>
        </div>
    </div>
    <div class="card-body no-padding">
        <?php if (empty($fraisActifs)): ?>
            <div class="empty-state" style="padding:40px;">
                <i class="fa-solid fa-inbox"></i>
                <p>Aucun frais configuré pour cette année scolaire.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Frais</th>
                            <th>Type</th>
                            <th>Montant attendu</th>
                            <th>Montant payé</th>
                            <th>Reste</th>
                            <th>Progression</th>
                            <th>Statut</th>
                            <th>Dernier paiement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fraisActifs as $frais): 
                            $montantAttendu = (float) $frais['montant_attendu'];
                            $montantPaye = (float) $frais['montant_paye'];
                            $reste = $montantAttendu - $montantPaye;
                            $pctFrais = $montantAttendu > 0 ? round(($montantPaye / $montantAttendu) * 100) : 0;
                            $statutClass = $frais['statut_paiement'] === 'Payé' ? 'badge-success' : ($frais['statut_paiement'] === 'Partiel' ? 'badge-warning' : 'badge-danger');
                        ?>
                            <tr>
                                <td><strong><?= e($frais['libelle']) ?></strong></td>
                                <td>
                                    <?php if ($frais['est_obligatoire']): ?>
                                        <span class="badge badge-warning">Obligatoire</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Optionnel</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>$ <?= number_format($montantAttendu, 2, ',', ' ') ?></strong></td>
                                <td><strong style="color:#065f46;">$ <?= number_format($montantPaye, 2, ',', ' ') ?></strong></td>
                                <td>
                                    <?php if ($reste > 0): ?>
                                        <strong style="color:#ef4444;">$ <?= number_format($reste, 2, ',', ' ') ?></strong>
                                    <?php else: ?>
                                        <span style="color:#10b981;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="flex:1;height:8px;background:#e2e8f0;border-radius:10px;overflow:hidden;min-width:80px;">
                                            <div style="width:<?= $pctFrais ?>%;height:100%;background:<?= $pctFrais >= 80 ? '#10b981' : ($pctFrais >= 40 ? '#f59e0b' : '#ef4444') ?>;border-radius:10px;"></div>
                                        </div>
                                        <span style="font-size:0.72rem;font-weight:600;"><?= $pctFrais ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $statutClass ?>"><?= e($frais['statut_paiement']) ?></span>
                                </td>
                                <td>
                                    <?php if ($frais['date_paiement_format']): ?>
                                        <?= e($frais['date_paiement_format']) ?>
                                        <?php if ($frais['mode_paiement']): ?>
                                            <br><small class="text-muted"><?= e($frais['mode_paiement']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     HISTORIQUE DES PAIEMENTS
     ============================================================ -->
<div class="card mb-4">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon green"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <div class="card-header-title">Historique des transactions</div>
                <div class="card-header-subtitle"><?= count($historiquePaiements) ?> transaction(s)</div>
            </div>
        </div>
    </div>
    <div class="card-body no-padding">
        <?php if (empty($historiquePaiements)): ?>
            <div class="empty-state" style="padding:40px;">
                <i class="fa-solid fa-receipt"></i>
                <p>Aucune transaction enregistrée pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Frais</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Référence</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historiquePaiements as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= e($p['date_format']) ?></strong>
                                    <br><small class="text-muted"><?= e($p['heure_format']) ?></small>
                                </td>
                                <td><?= e($p['frais_libelle']) ?></td>
                                <td>
                                    <strong style="color:#065f46;">$ <?= number_format($p['montant_paye'], 2, ',', ' ') ?></strong>
                                    <?php if ($p['montant_total'] > $p['montant_paye']): ?>
                                        <br><small class="text-danger">/ $ <?= number_format($p['montant_total'], 2, ',', ' ') ?></small>
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
                                    $modeIcon = $modeIcons[$p['mode_paiement']] ?? 'fa-credit-card';
                                    ?>
                                    <i class="fa-solid <?= $modeIcon ?>"></i> <?= e($p['mode_paiement']) ?>
                                </td>
                                <td><small><?= e($p['reference_paiement'] ?? '—') ?></small></td>
                                <td>
                                    <span class="badge <?= $p['statut'] === 'Payé' ? 'badge-success' : ($p['statut'] === 'Partiel' ? 'badge-warning' : 'badge-danger') ?>">
                                        <?= e($p['statut']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     REÇUS / FACTURES
     ============================================================ -->
<?php if (!empty($factures)): ?>
<div class="card">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon purple"><i class="fa-solid fa-receipt"></i></div>
            <div>
                <div class="card-header-title">Reçus disponibles</div>
                <div class="card-header-subtitle"><?= count($factures) ?> dernier(s) reçu(s)</div>
            </div>
        </div>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>N° Reçu</th>
                        <th>Date</th>
                        <th>Frais</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($factures as $facture): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= e($facture['numero_facture']) ?></span></td>
                            <td><strong><?= e($facture['date_format']) ?></strong></td>
                            <td><?= e($facture['frais_libelle']) ?></td>
                            <td><strong style="color:#065f46;">$ <?= number_format($facture['montant_paye'], 2, ',', ' ') ?></strong></td>
                            <td><?= e($facture['mode_paiement']) ?></td>
                            <td>
                                <a href="/gestion_scolaire/paiements/recu?facture_id=<?= $facture['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Voir le reçu">
                                    <i class="fa-solid fa-eye"></i> Voir
                                </a>
                                <a href="/gestion_scolaire/paiements/recu?facture_id=<?= $facture['id'] ?>" class="btn btn-sm btn-outline-success" onclick="window.open(this.href); return false;" title="Imprimer">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     ACTIONS
     ============================================================ -->
<div class="d-flex gap-sm justify-content-center mt-4">
    <a href="/gestion_scolaire/parent/enfants" class="btn btn-outline-primary">
        <i class="fa-solid fa-arrow-left"></i> Retour
    </a>
    <a href="/gestion_scolaire/parent/bulletins?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-outline-primary">
        <i class="fa-solid fa-file-invoice"></i> Voir le bulletin
    </a>
</div>

<script>
function changerEnfant(enfantId) {
    var url = new URL(window.location.href);
    url.searchParams.set('enfant_id', enfantId);
    window.location.href = url.toString();
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>