<?php
/**
 * Fichier : src/controllers/RetardController.php
 * Rôle    : Gestion des retards — Enregistrement, suivi, statistiques
 * Système : École primaire congolaise
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class RetardController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Point d'entrée pour les requêtes AJAX
     */
    public function handleAjax(): void
    {
        if (!can('retards.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'enregistrerRetard':
                $this->enregistrerRetard();
                break;
            case 'getListeRetards':
                $this->getListeRetards();
                break;
            case 'getStatsRetards':
                $this->getStatsRetards();
                break;
            case 'justifierRetard':
                $this->justifierRetard();
                break;
            case 'getRetardsEleve':
                $this->getRetardsEleve();
                break;
            case 'supprimerRetard':
                $this->supprimerRetard();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. ENREGISTRER UN RETARD
    // ============================================================

    /**
     * Enregistre un retard pour un élève
     */
    public function enregistrerRetard(): void
    {
        requirePermission('retards.enregistrer');

        $eleveId      = (int) post('eleve_id', 0);
        $dateRetard   = post('date_retard', date('Y-m-d'));
        $heureArrivee = post('heure_arrivee', '');
        $heurePrevue  = post('heure_prevue', '07:30');
        $motif        = post('motif', '');
        $estJustifie  = (int) post('est_justifie', 0);

        if ($eleveId <= 0) jsonResponse(false, 'ID élève invalide.', [], 400);
        if (empty($heureArrivee)) jsonResponse(false, 'L\'heure d\'arrivée est obligatoire.', [], 400);

        // Calculer la durée du retard en minutes
        $dureeRetard = $this->calculerDureeRetard($heurePrevue, $heureArrivee);

        if ($dureeRetard <= 0) {
            jsonResponse(false, 'L\'heure d\'arrivée doit être après l\'heure prévue.', [], 400);
        }

        // Vérifier que l'élève existe et est inscrit
        $eleve = $this->db->fetchOne(
            "SELECT e.id, e.nom, e.prenom FROM eleves e
             INNER JOIN inscriptions i ON e.id = i.eleve_id
             WHERE e.id = :eid AND i.statut = 'Actif' AND e.est_actif = 1",
            ['eid' => $eleveId]
        );

        if (!$eleve) jsonResponse(false, 'Élève introuvable ou non inscrit.', [], 404);

        // Vérifier si un retard existe déjà pour cette date
        $existant = $this->db->fetchColumn(
            "SELECT id FROM retards WHERE eleve_id = :eid AND date_retard = :date",
            ['eid' => $eleveId, 'date' => $dateRetard]
        );

        if ($existant) {
            jsonResponse(false, 'Un retard a déjà été enregistré pour cet élève à cette date.', [], 409);
        }

        try {
            $retardId = $this->db->insert(
                "INSERT INTO retards (eleve_id, date_retard, heure_arrivee, heure_prevue, duree_retard, motif, est_justifie, saisie_par, created_at)
                 VALUES (:eid, :date, :arrivee, :prevue, :duree, :motif, :justifie, :uid, NOW())",
                [
                    'eid'      => $eleveId,
                    'date'     => $dateRetard,
                    'arrivee'  => $heureArrivee,
                    'prevue'   => $heurePrevue,
                    'duree'    => $dureeRetard,
                    'motif'    => $motif ?: null,
                    'justifie' => $estJustifie,
                    'uid'      => $_SESSION['utilisateur_id']
                ]
            );

            // Vérifier le nombre de retards et alerter si nécessaire
            $nbRetards = $this->getNbRetardsEleve($eleveId);

            $message = 'Retard enregistré avec succès.';
            $alerte = null;

            if ($nbRetards >= 10) {
                $alerte = '⚠️ Cet élève totalise ' . $nbRetards . ' retards. Une convocation des parents est recommandée.';
            } elseif ($nbRetards >= 5) {
                $alerte = '⚠️ Cet élève totalise ' . $nbRetards . ' retards. Une alerte aux parents est recommandée.';
            } elseif ($nbRetards >= 3) {
                $alerte = 'Cet élève totalise ' . $nbRetards . ' retards.';
            }

            logActivity('Création', 'retards', $retardId, "Retard de {$eleve['nom']} {$eleve['prenom']} — {$dureeRetard} min");

            jsonResponse(true, $message, [
                'retard_id'    => $retardId,
                'duree_retard' => $dureeRetard,
                'nb_retards'   => $nbRetards,
                'alerte'       => $alerte
            ]);

        } catch (Exception $e) {
            error_log('Erreur enregistrement retard: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de l\'enregistrement du retard.');
        }
    }

    // ============================================================
    // 2. LISTE DES RETARDS
    // ============================================================

    /**
     * Récupère la liste paginée des retards avec filtres
     */
    public function getListeRetards(): void
    {
        $page       = max(1, (int) post('page', 1));
        $classeId   = (int) post('classe_id', 0);
        $dateDebut  = post('date_debut', '');
        $dateFin    = post('date_fin', '');
        $justifie   = post('justifie', '');
        $recherche  = post('recherche', '');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if ($classeId > 0) {
            $where[] = "i.classe_id = :classe";
            $params['classe'] = $classeId;
        }

        if (!empty($dateDebut)) {
            $where[] = "r.date_retard >= :debut";
            $params['debut'] = $dateDebut;
        }

        if (!empty($dateFin)) {
            $where[] = "r.date_retard <= :fin";
            $params['fin'] = $dateFin;
        }

        if ($justifie !== '') {
            $where[] = "r.est_justifie = :justifie";
            $params['justifie'] = (int) $justifie;
        }

        if (!empty($recherche)) {
            $where[] = "(e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM retards r
             INNER JOIN eleves e ON r.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;

        $retards = $this->db->fetchAll(
            "SELECT r.*, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom,
                    DATE_FORMAT(r.date_retard, '%d/%m/%Y') AS date_format,
                    TIME_FORMAT(r.heure_arrivee, '%H:%i') AS heure_arrivee_format,
                    TIME_FORMAT(r.heure_prevue, '%H:%i') AS heure_prevue_format,
                    CONCAT(u.prenom, ' ', u.nom) AS saisie_par_nom
             FROM retards r
             INNER JOIN eleves e ON r.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             LEFT JOIN utilisateurs u ON r.saisie_par = u.id
             {$whereClause}
             ORDER BY r.date_retard DESC, e.nom
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'retards' => $retards,
            'total'   => $total,
            'page'    => $page,
            'pages'   => ceil($total / $parPage)
        ]);
    }

    // ============================================================
    // 3. STATISTIQUES DES RETARDS
    // ============================================================

    /**
     * Récupère les statistiques des retards
     */
    public function getStatsRetards(): void
    {
        $classeId    = (int) post('classe_id', 0);
        $trimestreId = (int) post('trimestre_id', 0);

        $where = [];
        $params = [];

        if ($classeId > 0) {
            $where[] = "i.classe_id = :classe";
            $params['classe'] = $classeId;
        }

        if ($trimestreId > 0) {
            $trimestre = $this->db->fetchOne("SELECT date_debut, date_fin FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
            if ($trimestre) {
                $where[] = "r.date_retard BETWEEN :debut AND :fin";
                $params['debut'] = $trimestre['date_debut'];
                $params['fin']   = $trimestre['date_fin'];
            }
        }

        $whereClause = !empty($where) ? 'AND ' . implode(' AND ', $where) : '';

        // Total retards
        $totalRetards = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM retards r
             INNER JOIN eleves e ON r.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             WHERE 1=1 {$whereClause}",
            $params
        );

        // Retards justifiés
        $retardsJustifies = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM retards r
             INNER JOIN eleves e ON r.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             WHERE r.est_justifie = 1 {$whereClause}",
            $params
        );

        // Durée moyenne des retards
        $dureeMoyenne = (float) $this->db->fetchColumn(
            "SELECT COALESCE(AVG(r.duree_retard), 0) FROM retards r
             INNER JOIN eleves e ON r.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             WHERE 1=1 {$whereClause}",
            $params
        );

        // Top 10 des élèves les plus en retard
        $topRetards = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom,
                    COUNT(r.id) AS nb_retards,
                    SUM(r.duree_retard) AS total_duree,
                    ROUND(AVG(r.duree_retard), 1) AS moyenne_duree,
                    SUM(CASE WHEN r.est_justifie = 1 THEN 1 ELSE 0 END) AS nb_justifies
             FROM retards r
             INNER JOIN eleves e ON r.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             WHERE 1=1 {$whereClause}
             GROUP BY e.id, e.matricule, e.nom, e.prenom, e.sexe, c.nom
             ORDER BY nb_retards DESC
             LIMIT 10",
            $params
        );

        jsonResponse(true, 'Statistiques récupérées.', [
            'total_retards'       => $totalRetards,
            'retards_justifies'   => $retardsJustifies,
            'retards_non_justifies' => $totalRetards - $retardsJustifies,
            'duree_moyenne'       => round($dureeMoyenne, 1),
            'top_retards'         => $topRetards
        ]);
    }

    // ============================================================
    // 4. JUSTIFIER UN RETARD
    // ============================================================

    /**
     * Justifie un retard existant
     */
    public function justifierRetard(): void
    {
        $retardId = (int) post('retard_id', 0);
        $motif    = post('motif', '');

        if ($retardId <= 0) jsonResponse(false, 'ID retard invalide.', [], 400);
        if (empty($motif)) jsonResponse(false, 'Le motif est obligatoire.', [], 422);

        $retard = $this->db->fetchOne("SELECT id FROM retards WHERE id = :id", ['id' => $retardId]);
        if (!$retard) jsonResponse(false, 'Retard introuvable.', [], 404);

        $this->db->update(
            "UPDATE retards SET est_justifie = 1, motif = :motif WHERE id = :id",
            ['motif' => $motif, 'id' => $retardId]
        );

        logActivity('Justification', 'retards', $retardId, 'Retard justifié');
        jsonResponse(true, 'Retard justifié avec succès.');
    }

    // ============================================================
    // 5. RETARDS D'UN ÉLÈVE
    // ============================================================

    /**
     * Récupère les retards d'un élève spécifique
     */
    public function getRetardsEleve(): void
    {
        $eleveId = (int) post('eleve_id', 0);
        if ($eleveId <= 0) jsonResponse(false, 'ID élève invalide.', [], 400);

        $retards = $this->db->fetchAll(
            "SELECT r.*, 
                    DATE_FORMAT(r.date_retard, '%d/%m/%Y') AS date_format,
                    TIME_FORMAT(r.heure_arrivee, '%H:%i') AS heure_arrivee_format,
                    TIME_FORMAT(r.heure_prevue, '%H:%i') AS heure_prevue_format,
                    CONCAT(u.prenom, ' ', u.nom) AS saisie_par_nom
             FROM retards r
             LEFT JOIN utilisateurs u ON r.saisie_par = u.id
             WHERE r.eleve_id = :eid
             ORDER BY r.date_retard DESC",
            ['eid' => $eleveId]
        );

        $nbRetards = count($retards);
        $totalDuree = array_sum(array_column($retards, 'duree_retard'));
        $moyenneDuree = $nbRetards > 0 ? round($totalDuree / $nbRetards, 1) : 0;

        jsonResponse(true, 'Retards récupérés.', [
            'retards'       => $retards,
            'nb_retards'    => $nbRetards,
            'total_duree'   => $totalDuree,
            'moyenne_duree' => $moyenneDuree
        ]);
    }

    // ============================================================
    // 6. SUPPRIMER UN RETARD
    // ============================================================

    /**
     * Supprime un retard
     */
    public function supprimerRetard(): void
    {
        requirePermission('retards.supprimer');

        $retardId = (int) post('retard_id', 0);
        if ($retardId <= 0) jsonResponse(false, 'ID retard invalide.', [], 400);

        $this->db->delete("DELETE FROM retards WHERE id = :id", ['id' => $retardId]);
        jsonResponse(true, 'Retard supprimé avec succès.');
    }

    // ============================================================
    // 7. MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Calcule la durée du retard en minutes
     */
    private function calculerDureeRetard(string $heurePrevue, string $heureArrivee): int
    {
        $prevue  = strtotime($heurePrevue);
        $arrivee = strtotime($heureArrivee);

        if ($prevue === false || $arrivee === false) return 0;

        $diff = $arrivee - $prevue;
        return $diff > 0 ? (int) round($diff / 60) : 0;
    }

    /**
     * Récupère le nombre total de retards d'un élève
     */
    private function getNbRetardsEleve(int $eleveId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM retards WHERE eleve_id = :eid",
            ['eid' => $eleveId]
        );
    }

    // ============================================================
    // 8. MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère les classes de l'année active
     */
    public function getClassesActives(): array
    {
        return $this->db->fetchAll(
            "SELECT c.* FROM classes c
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau"
        );
    }

    /**
     * Récupère la classe de l'enseignant connecté
     */
    public function getClasseEnseignant(): int
    {
        $userId = $_SESSION['utilisateur_id'] ?? 0;
        return (int) $this->db->fetchColumn(
            "SELECT e.classe_id FROM enseignants e WHERE e.utilisateur_id = :uid AND e.est_actif = 1 LIMIT 1",
            ['uid' => $userId]
        );
    }

    /**
     * Récupère les statistiques des retards pour le dashboard
     */
    public function getStatsDashboard(): array
    {
        $today = date('Y-m-d');
        $totalRetardsJour = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM retards WHERE date_retard = :date",
            ['date' => $today]
        );

        $totalRetardsMois = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM retards WHERE MONTH(date_retard) = MONTH(CURDATE()) AND YEAR(date_retard) = YEAR(CURDATE())"
        );

        return [
            'retards_jour'  => $totalRetardsJour,
            'retards_mois'  => $totalRetardsMois
        ];
    }
}