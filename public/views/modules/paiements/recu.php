<?php
/**
 * Fichier : public/views/modules/paiements/recu.php
 * Rôle    : Reçu de paiement imprimable
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

// Paramètres
$paiementId = (int) ($_GET['paiement_id'] ?? 0);
$factureId  = (int) ($_GET['facture_id'] ?? 0);

if ($paiementId <= 0 && $factureId <= 0) {
    setFlash('error', 'Paramètres invalides.');
    redirect('/gestion_scolaire/paiements');
}

// Récupérer le reçu
$recu = null;
if ($factureId > 0) {
    $recu = $db->fetchOne(
        "SELECT f.*, e.matricule, e.nom, e.prenom, e.sexe,
                c.nom AS classe_nom, c.niveau AS classe_niveau,
                DATE_FORMAT(f.date_emission, '%d/%m/%Y') AS date_format,
                TIME_FORMAT(f.date_emission, '%H:%i') AS heure_format,
                fl.libelle AS frais_libelle,
                p.mode_paiement, p.montant_total, p.montant_paye, p.statut, p.reference_paiement, p.commentaire,
                CONCAT(u.prenom, ' ', u.nom) AS emis_par_nom
         FROM factures f
         INNER JOIN eleves e ON f.eleve_id = e.id
         INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
         INNER JOIN classes c ON i.classe_id = c.id
         INNER JOIN paiements p ON f.paiement_id = p.id
         INNER JOIN frais_scolaires fl ON p.frais_scolaire_id = fl.id
         LEFT JOIN utilisateurs u ON f.emise_par = u.id
         WHERE f.id = :id",
        ['id' => $factureId]
    );
} elseif ($paiementId > 0) {
    $recu = $db->fetchOne(
        "SELECT f.*, e.matricule, e.nom, e.prenom, e.sexe,
                c.nom AS classe_nom, c.niveau AS classe_niveau,
                DATE_FORMAT(f.date_emission, '%d/%m/%Y') AS date_format,
                TIME_FORMAT(f.date_emission, '%H:%i') AS heure_format,
                fl.libelle AS frais_libelle,
                p.mode_paiement, p.montant_total, p.montant_paye, p.statut, p.reference_paiement, p.commentaire,
                CONCAT(u.prenom, ' ', u.nom) AS emis_par_nom
         FROM factures f
         INNER JOIN eleves e ON f.eleve_id = e.id
         INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
         INNER JOIN classes c ON i.classe_id = c.id
         INNER JOIN paiements p ON f.paiement_id = p.id
         INNER JOIN frais_scolaires fl ON p.frais_scolaire_id = fl.id
         LEFT JOIN utilisateurs u ON f.emise_par = u.id
         WHERE f.paiement_id = :pid
         ORDER BY f.date_emission DESC LIMIT 1",
        ['pid' => $paiementId]
    );
}

if (!$recu) {
    setFlash('error', 'Reçu introuvable.');
    redirect('/gestion_scolaire/paiements');
}

$ecole = $db->fetchOne("SELECT * FROM parametres_ecole LIMIT 1");
$anneeScolaire = $db->fetchOne("SELECT libelle FROM annees_scolaires WHERE id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)");

$pageTitle = 'Reçu N° ' . e($recu['numero_facture']);
$pageIcon  = 'fa-receipt';
$pageDescription = 'Reçu de paiement';
$extraCSS = ['modules/paiements.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb no-print">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/paiements">Paiements</a>
    <span class="separator">/</span>
    <span class="current">Reçu N° <?= e($recu['numero_facture']) ?></span>
</nav>

<!-- BOUTONS D'ACTION -->
<div class="no-print" style="display:flex;gap:12px;margin-bottom:20px;justify-content:center;flex-wrap:wrap;">
    <button type="button" class="btn btn-primary btn-lg" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Imprimer le reçu
    </button>
    <a href="/gestion_scolaire/paiements" class="btn btn-outline-primary">
        <i class="fa-solid fa-arrow-left"></i> Retour aux paiements
    </a>
</div>

<!-- ============================================================
     REÇU IMPRIMABLE
     ============================================================ -->
<div class="recu-document" id="recuDocument">
    
    <!-- En-tête -->
    <div class="recu-header">
        <div class="recu-header-top">
            <div class="recu-logo">
                <i class="fa-solid fa-school"></i>
            </div>
            <div class="recu-ecole-info">
                <h2><?= e($ecole['nom_ecole'] ?? 'École Primaire') ?></h2>
                <p><?= e($ecole['devise'] ?? 'Discipline - Travail - Excellence') ?></p>
                <p><?= e($ecole['adresse'] ?? '') ?> — <?= e($ecole['telephone'] ?? '') ?></p>
                <p><?= e($ecole['email'] ?? '') ?></p>
            </div>
            <div class="recu-titre-wrapper">
                <h1 class="recu-titre">REÇU DE PAIEMENT</h1>
                <div class="recu-numero">N° <?= e($recu['numero_facture']) ?></div>
            </div>
        </div>
    </div>

    <!-- Corps -->
    <div class="recu-body">
        <div class="recu-info-grid">
            <!-- Infos élève -->
            <div class="recu-section">
                <div class="recu-section-title">Informations de l'élève</div>
                <div class="recu-field">
                    <span class="recu-field-label">Élève</span>
                    <span class="recu-field-value"><?= e(mb_strtoupper($recu['nom'] . ' ' . $recu['prenom'])) ?></span>
                </div>
                <div class="recu-field">
                    <span class="recu-field-label">Matricule</span>
                    <span class="recu-field-value"><?= e($recu['matricule']) ?></span>
                </div>
                <div class="recu-field">
                    <span class="recu-field-label">Sexe</span>
                    <span class="recu-field-value"><?= $recu['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span>
                </div>
                <div class="recu-field">
                    <span class="recu-field-label">Classe</span>
                    <span class="recu-field-value"><?= e($recu['classe_nom']) ?> (<?= $recu['classe_niveau'] ?>)</span>
                </div>
                <div class="recu-field">
                    <span class="recu-field-label">Année scolaire</span>
                    <span class="recu-field-value"><?= e($anneeScolaire['libelle'] ?? '') ?></span>
                </div>
            </div>

            <!-- Infos paiement -->
            <div class="recu-section">
                <div class="recu-section-title">Détails du paiement</div>
                <div class="recu-field">
                    <span class="recu-field-label">Type de frais</span>
                    <span class="recu-field-value"><strong><?= e($recu['frais_libelle']) ?></strong></span>
                </div>
                <div class="recu-field">
                    <span class="recu-field-label">Montant total attendu</span>
                    <span class="recu-field-value">$ <?= number_format($recu['montant_total'], 2, ',', ' ') ?></span>
                </div>
                <div class="recu-field highlight">
                    <span class="recu-field-label">Montant payé</span>
                    <span class="recu-field-value recu-montant">$ <?= number_format($recu['montant_paye'], 2, ',', ' ') ?></span>
                </div>
                <div class="recu-field">
                    <span class="recu-field-label">Reste à payer</span>
                    <?php $reste = $recu['montant_total'] - $recu['montant_paye']; ?>
                    <span class="recu-field-value" style="color:<?= $reste > 0 ? '#ef4444' : '#10b981' ?>;font-weight:700;">
                        $ <?= number_format($reste, 2, ',', ' ') ?>
                    </span>
                </div>
                <div class="recu-field">
                    <span class="recu-field-label">Statut</span>
                    <span class="recu-field-value">
                        <?php 
                        $statutClass = $recu['statut'] === 'Payé' ? 'recu-statut-paye' : ($recu['statut'] === 'Partiel' ? 'recu-statut-partiel' : 'recu-statut-impaye');
                        ?>
                        <span class="recu-statut-badge <?= $statutClass ?>"><?= e($recu['statut']) ?></span>
                    </span>
                </div>
                <div class="recu-field">
                    <span class="recu-field-label">Mode de paiement</span>
                    <span class="recu-field-value"><?= e($recu['mode_paiement']) ?></span>
                </div>
                <?php if (!empty($recu['reference_paiement'])): ?>
                <div class="recu-field">
                    <span class="recu-field-label">Référence</span>
                    <span class="recu-field-value"><?= e($recu['reference_paiement']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($recu['commentaire'])): ?>
                <div class="recu-field">
                    <span class="recu-field-label">Commentaire</span>
                    <span class="recu-field-value"><?= e($recu['commentaire']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Date et émetteur -->
        <div class="recu-meta">
            <div class="recu-meta-item">
                <i class="fa-regular fa-calendar"></i>
                <span>Fait le <?= e($recu['date_format']) ?> à <?= e($recu['heure_format']) ?></span>
            </div>
            <div class="recu-meta-item">
                <i class="fa-solid fa-user"></i>
                <span>Enregistré par : <strong><?= e($recu['emis_par_nom'] ?? '—') ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="recu-signatures">
        <div class="recu-signature-block">
            <div class="recu-signature-line"></div>
            <span>Le Caissier / Comptable</span>
        </div>
        <div class="recu-signature-block">
            <div class="recu-signature-line"></div>
            <span>Le Parent / Tuteur</span>
        </div>
        <div class="recu-signature-block">
            <div class="recu-signature-line"></div>
            <span>Cachet de l'école</span>
        </div>
    </div>

    <!-- Pied -->
    <div class="recu-footer">
        <p><i class="fa-solid fa-shield-halved"></i> Ce reçu est un document officiel. Conservez-le précieusement.</p>
        <p><?= e($ecole['nom_ecole'] ?? 'École Primaire') ?> — <?= e($anneeScolaire['libelle'] ?? '') ?></p>
    </div>
</div>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>