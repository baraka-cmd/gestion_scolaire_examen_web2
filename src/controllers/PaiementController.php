<?php
/**
 * Fichier : src/controllers/PaiementController.php
 * Rôle    : Gestion financière — Paiements, reçus, impayés
 * Système : École primaire congolaise
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class PaiementController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
 * Point d'entrée pour les requêtes AJAX — AJOUTER CES 3 CASES
 */
public function handleAjax(): void
{
    if (!can('paiements.consulter')) {
        jsonResponse(false, 'Accès refusé.', [], 403);
    }

    if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Requête invalide.', [], 400);
    }

    $action = post('action', '');

    switch ($action) {
        case 'getListePaiements':
            $this->getListePaiements();
            break;
        case 'enregistrerPaiement':
            $this->enregistrerPaiement();
            break;
        case 'getImpayes':
            $this->getImpayes();
            break;
        case 'getRecu':
            $this->getRecu();
            break;
        case 'getStatsPaiements':
            $this->getStatsPaiements();
            break;
        case 'rechercherEleve':
            $this->rechercherEleve();
            break;
        case 'annulerPaiement':
            $this->annulerPaiement();
            break;
        // ============================================================
        // FRAIS SCOLAIRES
        // ============================================================
        case 'getListeFrais':
            $this->getListeFrais();
            break;
        case 'ajouterFrais':
            $this->ajouterFrais();
            break;
        case 'modifierFrais':
            $this->modifierFrais();
            break;
        case 'supprimerFrais':
            $this->supprimerFrais();
            break;
        case 'toggleStatutFrais':
            $this->toggleStatutFrais();
            break;
        default:
            jsonResponse(false, 'Action inconnue.', [], 400);
    }
}

// ============================================================
// MÉTHODES FRAIS SCOLAIRES
// ============================================================

/**
 * Liste des frais scolaires
 */
public function getListeFrais(): void
{
    $anneeId = (int) post('annee_id', 0);
    if ($anneeId <= 0) {
        $anneeId = (int) $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
    }

    $frais = $this->db->fetchAll(
        "SELECT f.*, 
                (SELECT COUNT(*) FROM paiements p WHERE p.frais_scolaire_id = f.id) AS nb_paiements,
                (SELECT COALESCE(SUM(p.montant_paye), 0) FROM paiements p WHERE p.frais_scolaire_id = f.id) AS total_encaisse
         FROM frais_scolaires f
         WHERE f.annee_scolaire_id = :aid
         ORDER BY f.est_obligatoire DESC, f.libelle",
        ['aid' => $anneeId]
    );

    $totalFrais = count($frais);
    $totalEncaisse = array_sum(array_column($frais, 'total_encaisse'));

    jsonResponse(true, 'Liste récupérée.', [
        'frais'          => $frais,
        'total_frais'    => $totalFrais,
        'total_encaisse' => $totalEncaisse
    ]);
}

/**
 * Ajouter un nouveau frais scolaire
 */
public function ajouterFrais(): void
{
    requirePermission('paiements.modifier');

    $libelle       = post('libelle', '');
    $description   = post('description', '');
    $montant       = (float) post('montant', 0);
    $estObligatoire = (int) post('est_obligatoire', 1);
    $classeId      = (int) post('classe_id', 0);

    if (isBlank($libelle)) jsonResponse(false, 'Le libellé est obligatoire.', [], 422);
    if ($montant <= 0) jsonResponse(false, 'Le montant doit être supérieur à 0.', [], 422);

    $anneeId = (int) $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

    // Vérifier l'unicité
    $existe = $this->db->fetchColumn(
        "SELECT COUNT(*) FROM frais_scolaires WHERE libelle = :libelle AND annee_scolaire_id = :aid",
        ['libelle' => $libelle, 'aid' => $anneeId]
    );
    if ($existe > 0) jsonResponse(false, 'Ce frais existe déjà pour cette année scolaire.', [], 409);

    try {
        $fraisId = $this->db->insert(
            "INSERT INTO frais_scolaires (libelle, description, montant, classe_id, annee_scolaire_id, est_obligatoire, est_actif)
             VALUES (:libelle, :desc, :montant, :classe, :annee, :obligatoire, 1)",
            [
                'libelle'     => formatNom($libelle),
                'desc'        => $description ?: null,
                'montant'     => $montant,
                'classe'      => $classeId > 0 ? $classeId : null,
                'annee'       => $anneeId,
                'obligatoire' => $estObligatoire
            ]
        );

        logActivity('Création', 'frais_scolaires', $fraisId, "Frais créé : {$libelle}");
        jsonResponse(true, 'Frais créé avec succès.', ['id' => $fraisId]);

    } catch (Exception $e) {
        error_log('Erreur création frais: ' . $e->getMessage());
        jsonResponse(false, 'Erreur lors de la création du frais.');
    }
}

/**
 * Modifier un frais scolaire existant
 */
public function modifierFrais(): void
{
    requirePermission('paiements.modifier');

    // Récupérer les données directement depuis $_POST
    $fraisId        = (int) ($_POST['frais_id'] ?? 0);
    $libelle        = trim($_POST['libelle'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $montant        = (float) ($_POST['montant'] ?? 0);
    $estObligatoire = isset($_POST['est_obligatoire']) ? 1 : 0;
    $classeId       = (int) ($_POST['classe_id'] ?? 0);

    if ($fraisId <= 0) jsonResponse(false, 'ID frais invalide.', [], 400);
    if (empty($libelle)) jsonResponse(false, 'Le libellé est obligatoire.', [], 422);
    if ($montant <= 0) jsonResponse(false, 'Le montant doit être supérieur à 0.', [], 422);

    $frais = $this->db->fetchOne("SELECT id FROM frais_scolaires WHERE id = :id", ['id' => $fraisId]);
    if (!$frais) jsonResponse(false, 'Frais introuvable.', [], 404);

    $anneeId = (int) $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

    // Vérifier l'unicité
    $existe = $this->db->fetchColumn(
        "SELECT COUNT(*) FROM frais_scolaires WHERE libelle = :libelle AND annee_scolaire_id = :aid AND id != :id",
        ['libelle' => $libelle, 'aid' => $anneeId, 'id' => $fraisId]
    );
    if ($existe > 0) jsonResponse(false, 'Ce libellé existe déjà.', [], 409);

    try {
        $this->db->update(
            "UPDATE frais_scolaires 
             SET libelle = :libelle, description = :desc, montant = :montant, 
                 classe_id = :classe, est_obligatoire = :obligatoire
             WHERE id = :id",
            [
                'libelle'     => $libelle,
                'desc'        => $description ?: null,
                'montant'     => $montant,
                'classe'      => $classeId > 0 ? $classeId : null,
                'obligatoire' => $estObligatoire,
                'id'          => $fraisId
            ]
        );

        logActivity('Modification', 'frais_scolaires', $fraisId, "Frais modifié : {$libelle}");
        jsonResponse(true, 'Frais modifié avec succès.');

    } catch (Exception $e) {
        error_log('Erreur modification frais: ' . $e->getMessage());
        jsonResponse(false, 'Erreur lors de la modification: ' . $e->getMessage());
    }
}

/**
 * Supprimer un frais scolaire
 */
public function supprimerFrais(): void
{
    requirePermission('paiements.modifier');

    $fraisId = (int) post('frais_id', 0);
    if ($fraisId <= 0) jsonResponse(false, 'ID frais invalide.', [], 400);

    // Vérifier si des paiements sont liés
    $nbPaiements = (int) $this->db->fetchColumn(
        "SELECT COUNT(*) FROM paiements WHERE frais_scolaire_id = :fid",
        ['fid' => $fraisId]
    );

    if ($nbPaiements > 0) {
        jsonResponse(false, "Impossible de supprimer : {$nbPaiements} paiement(s) lié(s). Désactivez-le plutôt.", [], 409);
    }

    $this->db->delete("DELETE FROM frais_scolaires WHERE id = :id", ['id' => $fraisId]);
    logActivity('Suppression', 'frais_scolaires', $fraisId, 'Frais supprimé');
    jsonResponse(true, 'Frais supprimé avec succès.');
}

/**
 * Activer/Désactiver un frais
 */
public function toggleStatutFrais(): void
{
    requirePermission('paiements.modifier');

    $fraisId = (int) post('frais_id', 0);
    if ($fraisId <= 0) jsonResponse(false, 'ID frais invalide.', [], 400);

    $frais = $this->db->fetchOne("SELECT id, est_actif, libelle FROM frais_scolaires WHERE id = :id", ['id' => $fraisId]);
    if (!$frais) jsonResponse(false, 'Frais introuvable.', [], 404);

    $nouveauStatut = $frais['est_actif'] ? 0 : 1;

    $this->db->update(
        "UPDATE frais_scolaires SET est_actif = :statut WHERE id = :id",
        ['statut' => $nouveauStatut, 'id' => $fraisId]
    );

    $action = $nouveauStatut ? 'activé' : 'désactivé';
    logActivity('Modification', 'frais_scolaires', $fraisId, "Frais {$action} : {$frais['libelle']}");
    jsonResponse(true, "Frais {$action} avec succès.", ['est_actif' => $nouveauStatut]);
}

    // ============================================================
    // 1. LISTE DES PAIEMENTS
    // ============================================================

    public function getListePaiements(): void
    {
        $page      = max(1, (int) post('page', 1));
        $classeId  = (int) post('classe_id', 0);
        $statut    = post('statut', '');
        $dateDebut = post('date_debut', '');
        $dateFin   = post('date_fin', '');
        $recherche = post('recherche', '');
        $parPage   = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if ($classeId > 0) {
            $where[] = "i.classe_id = :classe";
            $params['classe'] = $classeId;
        }

        if (!empty($statut)) {
            $where[] = "p.statut = :statut";
            $params['statut'] = $statut;
        }

        if (!empty($dateDebut)) {
            $where[] = "p.date_paiement >= :debut";
            $params['debut'] = $dateDebut;
        }

        if (!empty($dateFin)) {
            $where[] = "p.date_paiement <= :fin";
            $params['fin'] = $dateFin;
        }

        if (!empty($recherche)) {
            $where[] = "(e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM paiements p
             INNER JOIN eleves e ON p.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;

        $paiements = $this->db->fetchAll(
            "SELECT p.*, e.matricule, e.nom, e.prenom,
                    c.nom AS classe_nom,
                    f.libelle AS frais_libelle,
                    DATE_FORMAT(p.date_paiement, '%d/%m/%Y') AS date_format,
                    CONCAT(u.prenom, ' ', u.nom) AS enregistre_par_nom
             FROM paiements p
             INNER JOIN eleves e ON p.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             INNER JOIN frais_scolaires f ON p.frais_scolaire_id = f.id
             LEFT JOIN utilisateurs u ON p.enregistre_par = u.id
             {$whereClause}
             ORDER BY p.date_paiement DESC, e.nom
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'paiements' => $paiements,
            'total'     => $total,
            'page'      => $page,
            'pages'     => ceil($total / $parPage)
        ]);
    }

    // ============================================================
    // 2. ENREGISTRER UN PAIEMENT
    // ============================================================

     public function enregistrerPaiement(): void
{
    requirePermission('paiements.enregistrer');

    $eleveId      = (int) ($_POST['eleve_id'] ?? 0);
    $fraisId      = (int) ($_POST['frais_scolaire_id'] ?? 0);
    $montantPaye  = (float) ($_POST['montant_paye'] ?? 0);
    $modePaiement = $_POST['mode_paiement'] ?? 'Espèces';
    $reference    = $_POST['reference_paiement'] ?? '';
    $commentaire  = $_POST['commentaire'] ?? '';

    if ($eleveId <= 0) jsonResponse(false, 'Veuillez sélectionner un élève.', [], 400);
    if ($fraisId <= 0) jsonResponse(false, 'Veuillez sélectionner un type de frais.', [], 400);
    if ($montantPaye <= 0) jsonResponse(false, 'Le montant payé doit être supérieur à 0.', [], 400);

    // Récupérer le frais
    $frais = $this->db->fetchOne(
        "SELECT * FROM frais_scolaires WHERE id = :id",
        ['id' => $fraisId]
    );

    if (!$frais) jsonResponse(false, 'Type de frais introuvable.', [], 404);

    $montantTotal = (float) $frais['montant'];

    try {
        $this->db->beginTransaction();

        // Vérifier si un paiement existe déjà pour ce frais et cet élève
        $paiementExistant = $this->db->fetchOne(
            "SELECT id, montant_paye, statut FROM paiements WHERE eleve_id = :eid AND frais_scolaire_id = :fid ORDER BY id DESC LIMIT 1",
            ['eid' => $eleveId, 'fid' => $fraisId]
        );

        $numeroRecu = 'RECU-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        if ($paiementExistant && $paiementExistant['statut'] !== 'Payé') {
            // ============================================================
            // PAIEMENT EXISTANT — AJOUTER LE MONTANT
            // ============================================================
            $nouveauMontant = (float) $paiementExistant['montant_paye'] + $montantPaye;
            $nouveauStatut = $nouveauMontant >= $montantTotal ? 'Payé' : 'Partiel';

            // Mettre à jour le paiement
            $this->db->update(
                "UPDATE paiements SET montant_paye = :montant, statut = :statut, mode_paiement = :mode, reference_paiement = :ref, commentaire = :comm WHERE id = :id",
                [
                    'montant' => $nouveauMontant,
                    'statut'  => $nouveauStatut,
                    'mode'    => $modePaiement,
                    'ref'     => $reference ?: null,
                    'comm'    => $commentaire ?: null,
                    'id'      => $paiementExistant['id']
                ]
            );

            $paiementId = $paiementExistant['id'];
            $statutFinal = $nouveauStatut;

            // Créer UNE SEULE facture pour ce versement
            $this->db->insert(
                "INSERT INTO factures (numero_facture, paiement_id, eleve_id, date_emission, montant_HT, montant_TTC, taxe, statut_facture, emise_par)
                 VALUES (:num, :pid, :eid, CURDATE(), :ht, :ttc, 0, 'Payée', :uid)",
                [
                    'num' => $numeroRecu,
                    'pid' => $paiementId,
                    'eid' => $eleveId,
                    'ht'  => $montantPaye,
                    'ttc' => $montantPaye,
                    'uid' => $_SESSION['utilisateur_id']
                ]
            );

        } else {
            // ============================================================
            // NOUVEAU PAIEMENT
            // ============================================================
            $statut = $montantPaye >= $montantTotal ? 'Payé' : 'Partiel';

            $paiementId = $this->db->insert(
                "INSERT INTO paiements (eleve_id, frais_scolaire_id, montant_total, montant_paye, mode_paiement, reference_paiement, statut, commentaire, enregistre_par, date_paiement)
                 VALUES (:eid, :fid, :total, :paye, :mode, :ref, :statut, :commentaire, :uid, CURDATE())",
                [
                    'eid'         => $eleveId,
                    'fid'         => $fraisId,
                    'total'       => $montantTotal,
                    'paye'        => $montantPaye,
                    'mode'        => $modePaiement,
                    'ref'         => $reference ?: null,
                    'statut'      => $statut,
                    'commentaire' => $commentaire ?: null,
                    'uid'         => $_SESSION['utilisateur_id']
                ]
            );

            $statutFinal = $statut;

            // Créer UNE SEULE facture pour ce nouveau paiement
            $this->db->insert(
                "INSERT INTO factures (numero_facture, paiement_id, eleve_id, date_emission, montant_HT, montant_TTC, taxe, statut_facture, emise_par)
                 VALUES (:num, :pid, :eid, CURDATE(), :ht, :ttc, 0, 'Payée', :uid)",
                [
                    'num' => $numeroRecu,
                    'pid' => $paiementId,
                    'eid' => $eleveId,
                    'ht'  => $montantPaye,
                    'ttc' => $montantPaye,
                    'uid' => $_SESSION['utilisateur_id']
                ]
            );
        }

        $this->db->commit();

        logActivity('Paiement', 'paiements', $paiementId, "Paiement de {$montantPaye} — {$modePaiement}");

        jsonResponse(true, 'Paiement enregistré avec succès.', [
            'paiement_id' => $paiementId,
            'numero_recu' => $numeroRecu,
            'statut'      => $statutFinal
        ]);

    } catch (Exception $e) {
        $this->db->rollback();
        error_log('Erreur enregistrement paiement: ' . $e->getMessage());
        jsonResponse(false, 'Erreur lors de l\'enregistrement du paiement.');
    }
}

    // ============================================================
    // 3. LISTE DES IMPAYÉS
    // ============================================================

    public function getImpayes(): void
    {
        $classeId = (int) post('classe_id', 0);
        $recherche = post('recherche', '');

        $where = [];
        $params = [];

        $where[] = "i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)";
        $where[] = "i.statut = 'Actif'";
        $where[] = "e.est_actif = 1";

        if ($classeId > 0) {
            $where[] = "i.classe_id = :classe";
            $params['classe'] = $classeId;
        }

        if (!empty($recherche)) {
            $where[] = "(e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Récupérer tous les élèves avec leurs paiements
        $impayes = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom, c.id AS classe_id,
                    COALESCE(SUM(p.montant_total), 0) AS total_du,
                    COALESCE(SUM(p.montant_paye), 0) AS total_paye,
                    COALESCE(SUM(p.montant_total - p.montant_paye), 0) AS reste_a_payer,
                    GROUP_CONCAT(DISTINCT CONCAT(f.libelle, ':', p.statut) SEPARATOR '|') AS details
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             INNER JOIN classes c ON i.classe_id = c.id
             LEFT JOIN paiements p ON e.id = p.eleve_id
             LEFT JOIN frais_scolaires f ON p.frais_scolaire_id = f.id
             {$whereClause}
             GROUP BY e.id, e.matricule, e.nom, e.prenom, e.sexe, c.nom, c.id
             HAVING reste_a_payer > 0 OR total_du = 0
             ORDER BY reste_a_payer DESC",
            $params
        );

        jsonResponse(true, 'Impayés récupérés.', [
            'impayes' => $impayes,
            'total'   => count($impayes)
        ]);
    }

    // ============================================================
    // 4. RÉCUPÉRER UN REÇU
    // ============================================================

    public function getRecu(): void
    {
        $factureId = (int) post('facture_id', 0);
        $paiementId = (int) post('paiement_id', 0);

        if ($factureId > 0) {
            $facture = $this->db->fetchOne(
                "SELECT f.*, e.matricule, e.nom, e.prenom,
                        c.nom AS classe_nom,
                        DATE_FORMAT(f.date_emission, '%d/%m/%Y') AS date_format,
                        fl.libelle AS frais_libelle,
                        p.mode_paiement, p.montant_total, p.montant_paye,
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
            jsonResponse(true, 'Reçu récupéré.', ['recu' => $facture]);
        } elseif ($paiementId > 0) {
            $facture = $this->db->fetchOne(
                "SELECT f.*, e.matricule, e.nom, e.prenom,
                        c.nom AS classe_nom,
                        DATE_FORMAT(f.date_emission, '%d/%m/%Y') AS date_format,
                        fl.libelle AS frais_libelle,
                        p.mode_paiement, p.montant_total, p.montant_paye,
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
            jsonResponse(true, 'Reçu récupéré.', ['recu' => $facture]);
        } else {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }
    }

    // ============================================================
    // 5. STATISTIQUES DES PAIEMENTS
    // ============================================================

    public function getStatsPaiements(): void
    {
        $totalAttendu = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(f.montant), 0)
             FROM frais_scolaires f
             WHERE f.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             AND f.est_obligatoire = 1 AND f.est_actif = 1"
        ) * (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1) AND statut = 'Actif'"
        );

        $totalEncaissement = (float) $this->db->fetchColumn("SELECT COALESCE(SUM(montant_paye), 0) FROM paiements");
        $totalImpayes = $totalAttendu - $totalEncaissement;
        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalEncaissement / $totalAttendu) * 100, 1) : 0;

        $nbPayes = (int) $this->db->fetchColumn("SELECT COUNT(DISTINCT eleve_id) FROM paiements WHERE statut = 'Payé'");
        $nbPartiels = (int) $this->db->fetchColumn("SELECT COUNT(DISTINCT eleve_id) FROM paiements WHERE statut = 'Partiel'");
        $nbImpayes = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT i.eleve_id)
             FROM inscriptions i
             WHERE i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             AND i.statut = 'Actif'
             AND i.eleve_id NOT IN (SELECT DISTINCT eleve_id FROM paiements WHERE statut = 'Payé')"
        );

        jsonResponse(true, 'Stats récupérées.', [
            'total_attendu'      => $totalAttendu,
            'total_encaissement' => $totalEncaissement,
            'total_impayes'      => $totalImpayes,
            'taux_recouvrement'  => $tauxRecouvrement,
            'nb_payes'           => $nbPayes,
            'nb_partiels'        => $nbPartiels,
            'nb_impayes'         => $nbImpayes
        ]);
    }

    // ============================================================
    // 6. RECHERCHER UN ÉLÈVE
    // ============================================================

    public function rechercherEleve(): void
    {
        $recherche = post('recherche', '');

        if (empty($recherche)) {
            jsonResponse(false, 'Veuillez entrer un nom, prénom ou matricule.', [], 400);
        }

        $eleves = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom
             FROM eleves e
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             WHERE e.est_actif = 1
             AND (e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3)
             ORDER BY e.nom
             LIMIT 10",
            ['r1' => "%{$recherche}%", 'r2' => "%{$recherche}%", 'r3' => "%{$recherche}%"]
        );

        jsonResponse(true, 'Élèves trouvés.', ['eleves' => $eleves]);
    }

    // ============================================================
    // 7. ANNULER UN PAIEMENT
    // ============================================================

    public function annulerPaiement(): void
    {
        requirePermission('paiements.supprimer');

        $paiementId = (int) post('paiement_id', 0);
        if ($paiementId <= 0) jsonResponse(false, 'ID paiement invalide.', [], 400);

        $this->db->update(
            "UPDATE paiements SET statut = 'Impayé', montant_paye = 0 WHERE id = :id",
            ['id' => $paiementId]
        );

        $this->db->delete("DELETE FROM factures WHERE paiement_id = :pid", ['pid' => $paiementId]);

        logActivity('Annulation', 'paiements', $paiementId, 'Paiement annulé');
        jsonResponse(true, 'Paiement annulé avec succès.');
    }

    // ============================================================
    // 8. MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    public function getClassesActives(): array
    {
        return $this->db->fetchAll(
            "SELECT c.* FROM classes c
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau"
        );
    }

    public function getFraisActifs(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM frais_scolaires
             WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             AND est_actif = 1
             ORDER BY est_obligatoire DESC, libelle"
        );
    }

    public function getStatsDashboard(): array
    {
        $totalEncaissement = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(montant_paye), 0) FROM paiements"
        );
        $paiementsMois = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(montant_paye), 0) FROM paiements WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())"
        );
        $nbImpayes = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT i.eleve_id)
             FROM inscriptions i
             WHERE i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             AND i.statut = 'Actif'
             AND i.eleve_id NOT IN (SELECT DISTINCT eleve_id FROM paiements WHERE statut = 'Payé')"
        );

        return [
            'total_encaissement' => $totalEncaissement,
            'paiements_mois'     => $paiementsMois,
            'nb_impayes'         => $nbImpayes
        ];
    }
}