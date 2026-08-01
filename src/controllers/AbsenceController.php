<?php
/**
 * Fichier : src/controllers/AbsenceController.php
 * Rôle    : Gestion des absences — Marquage, suivi, justification, statistiques
 * Système : École primaire congolaise
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class AbsenceController
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
    if (!can('absences.consulter')) {
        jsonResponse(false, 'Accès refusé.', [], 403);
    }

    if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Requête invalide.', [], 400);
    }

    $action = post('action', '');

    switch ($action) {
        case 'marquerAbsence':
            $this->marquerAbsence();
            break;
        case 'sauvegarderMotif':
            $this->sauvegarderMotif();
            break;
        case 'justifierAbsence':
            $this->justifierAbsence();
            break;
        case 'justifierAbsenceDirecte':
            $this->justifierAbsenceDirecte();
            break;
        case 'getListeAbsences':
            $this->getListeAbsences();
            break;
        case 'getStatsAbsences':
            $this->getStatsAbsences();
            break;
        case 'getAbsencesEleve':
            $this->getAbsencesEleve();
            break;
        default:
            jsonResponse(false, 'Action inconnue.', [], 400);
    }
}

/**
 * Justifie une absence directement par son ID
 */
public function justifierAbsenceDirecte(): void
{
    $absenceId   = (int) post('absence_id', 0);
    $motif       = post('motif', '');
    $commentaire = post('commentaire', '');

    if ($absenceId <= 0) jsonResponse(false, 'ID absence invalide.', [], 400);
    if (empty($motif)) jsonResponse(false, 'Veuillez sélectionner un motif.', [], 422);

    $motifComplet = $motif . ($commentaire ? ' — ' . $commentaire : '');

    $this->db->update(
        "UPDATE absences SET est_justifiee = 1, motif = :motif, date_justification = NOW() WHERE id = :id",
        ['motif' => $motifComplet, 'id' => $absenceId]
    );

    logActivity('Justification', 'absences', $absenceId, 'Absence justifiée directement');
    jsonResponse(true, 'Absence justifiée avec succès.');
}

    // ============================================================
    // 1. MARQUER UNE ABSENCE/PRÉSENCE
    // ============================================================

    /**
     * Marque un élève comme présent ou absent pour une date donnée
     */
    public function marquerAbsence(): void
    {
        requirePermission('absences.marquer');

        $eleveId     = (int) post('eleve_id', 0);
        $classeId    = (int) post('classe_id', 0);
        $dateAbsence = post('date_absence', date('Y-m-d'));
        $typeAbsence = post('type_absence', 'Absence');
        $motif       = post('motif', '');

        if ($eleveId <= 0) jsonResponse(false, 'ID élève invalide.', [], 400);
        if (!in_array($typeAbsence, ['Absence', 'Présent'])) {
            jsonResponse(false, 'Type invalide.', [], 400);
        }

        // Vérifier si l'élève existe et est inscrit
        $eleve = $this->db->fetchOne(
            "SELECT e.id FROM eleves e
             INNER JOIN inscriptions i ON e.id = i.eleve_id
             WHERE e.id = :eid AND i.statut = 'Actif' AND e.est_actif = 1",
            ['eid' => $eleveId]
        );

        if (!$eleve) jsonResponse(false, 'Élève introuvable ou non inscrit.', [], 404);

        try {
            // Vérifier si une absence existe déjà pour cette date
            $existante = $this->db->fetchColumn(
                "SELECT id FROM absences WHERE eleve_id = :eid AND date_absence = :date",
                ['eid' => $eleveId, 'date' => $dateAbsence]
            );

            if ($existante) {
                // Mettre à jour
                $this->db->update(
                    "UPDATE absences SET type_absence = :type, motif = :motif, saisie_par = :uid WHERE id = :id",
                    [
                        'type'  => $typeAbsence,
                        'motif' => $motif ?: null,
                        'uid'   => $_SESSION['utilisateur_id'],
                        'id'    => $existante
                    ]
                );
            } else {
                // Créer
                $this->db->insert(
                    "INSERT INTO absences (eleve_id, date_absence, type_absence, motif, saisie_par, created_at)
                     VALUES (:eid, :date, :type, :motif, :uid, NOW())",
                    [
                        'eid'   => $eleveId,
                        'date'  => $dateAbsence,
                        'type'  => $typeAbsence,
                        'motif' => $motif ?: null,
                        'uid'   => $_SESSION['utilisateur_id']
                    ]
                );
            }

            jsonResponse(true, 'Statut mis à jour avec succès.');

        } catch (Exception $e) {
            error_log('Erreur marquage absence: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du marquage.');
        }
    }

    // ============================================================
    // 2. SAUVEGARDER LE MOTIF
    // ============================================================

    /**
     * Sauvegarde uniquement le motif d'une absence
     */
    public function sauvegarderMotif(): void
    {
        $eleveId     = (int) post('eleve_id', 0);
        $dateAbsence = post('date_absence', '');
        $motif       = post('motif', '');

        if ($eleveId <= 0 || empty($dateAbsence)) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        $this->db->update(
            "UPDATE absences SET motif = :motif WHERE eleve_id = :eid AND date_absence = :date",
            ['motif' => $motif ?: null, 'eid' => $eleveId, 'date' => $dateAbsence]
        );

        jsonResponse(true, 'Motif sauvegardé.');
    }

    // ============================================================
    // 3. JUSTIFIER UNE ABSENCE
    // ============================================================

    /**
     * Justifie une absence (par le parent ou le secrétaire)
     */
    public function justifierAbsence(): void
    {
        $eleveId     = (int) post('eleve_id', 0);
        $dateAbsence = post('date_absence', '');
        $motif       = post('motif', '');
        $commentaire = post('commentaire', '');

        if ($eleveId <= 0 || empty($dateAbsence)) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        if (empty($motif)) {
            jsonResponse(false, 'Veuillez sélectionner un motif de justification.', [], 422);
        }

        $existante = $this->db->fetchColumn(
            "SELECT id FROM absences WHERE eleve_id = :eid AND date_absence = :date",
            ['eid' => $eleveId, 'date' => $dateAbsence]
        );

        if (!$existante) {
            // Créer l'absence puis la justifier
            $this->db->insert(
                "INSERT INTO absences (eleve_id, date_absence, type_absence, est_justifiee, motif, justificatif_document, date_justification, saisie_par)
                 VALUES (:eid, :date, 'Absence', 1, :motif, NULL, NOW(), :uid)",
                [
                    'eid'   => $eleveId,
                    'date'  => $dateAbsence,
                    'motif' => $motif . ($commentaire ? ' — ' . $commentaire : ''),
                    'uid'   => $_SESSION['utilisateur_id']
                ]
            );
        } else {
            $this->db->update(
                "UPDATE absences SET est_justifiee = 1, motif = :motif, date_justification = NOW() WHERE id = :id",
                [
                    'motif' => $motif . ($commentaire ? ' — ' . $commentaire : ''),
                    'id'    => $existante
                ]
            );
        }

        logActivity('Justification', 'absences', $existante, "Absence justifiée pour élève {$eleveId}");

        jsonResponse(true, 'Absence justifiée avec succès.');
    }

    // ============================================================
    // 4. LISTE DES ABSENCES
    // ============================================================

    /**
     * Récupère la liste paginée des absences avec filtres
     */
    public function getListeAbsences(): void
    {
        $page       = max(1, (int) post('page', 1));
        $classeId   = (int) post('classe_id', 0);
        $dateDebut  = post('date_debut', '');
        $dateFin    = post('date_fin', '');
        $type       = post('type', '');
        $justifiee  = post('justifiee', '');
        $recherche  = post('recherche', '');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if ($classeId > 0) {
            $where[] = "i.classe_id = :classe";
            $params['classe'] = $classeId;
        }

        if (!empty($dateDebut)) {
            $where[] = "a.date_absence >= :debut";
            $params['debut'] = $dateDebut;
        }

        if (!empty($dateFin)) {
            $where[] = "a.date_absence <= :fin";
            $params['fin'] = $dateFin;
        }

        if (!empty($type)) {
            $where[] = "a.type_absence = :type";
            $params['type'] = $type;
        }

        if ($justifiee !== '') {
            $where[] = "a.est_justifiee = :justifiee";
            $params['justifiee'] = (int) $justifiee;
        }

        if (!empty($recherche)) {
            $where[] = "(e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM absences a
             INNER JOIN eleves e ON a.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;

        $absences = $this->db->fetchAll(
            "SELECT a.*, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom,
                    DATE_FORMAT(a.date_absence, '%d/%m/%Y') AS date_format,
                    CONCAT(u.prenom, ' ', u.nom) AS saisie_par_nom
             FROM absences a
             INNER JOIN eleves e ON a.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             LEFT JOIN utilisateurs u ON a.saisie_par = u.id
             {$whereClause}
             ORDER BY a.date_absence DESC, e.nom
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'absences' => $absences,
            'total'    => $total,
            'page'     => $page,
            'pages'    => ceil($total / $parPage)
        ]);
    }

    // ============================================================
    // 5. STATISTIQUES DES ABSENCES
    // ============================================================

    /**
     * Récupère les statistiques d'absences
     */
    public function getStatsAbsences(): void
    {
        $classeId  = (int) post('classe_id', 0);
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
                $where[] = "a.date_absence BETWEEN :debut AND :fin";
                $params['debut'] = $trimestre['date_debut'];
                $params['fin']   = $trimestre['date_fin'];
            }
        }

        $whereClause = !empty($where) ? 'AND ' . implode(' AND ', $where) : '';

        // Total absences
        $totalAbsences = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM absences a
             INNER JOIN eleves e ON a.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             WHERE a.type_absence = 'Absence' {$whereClause}",
            $params
        );

        // Absences justifiées
        $absencesJustifiees = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM absences a
             INNER JOIN eleves e ON a.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             WHERE a.type_absence = 'Absence' AND a.est_justifiee = 1 {$whereClause}",
            $params
        );

        // Top 10 des élèves les plus absents
        $topAbsents = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom,
                    COUNT(a.id) AS nb_absences,
                    SUM(CASE WHEN a.est_justifiee = 1 THEN 1 ELSE 0 END) AS nb_justifiees
             FROM absences a
             INNER JOIN eleves e ON a.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             WHERE a.type_absence = 'Absence' {$whereClause}
             GROUP BY e.id, e.matricule, e.nom, e.prenom, e.sexe, c.nom
             ORDER BY nb_absences DESC
             LIMIT 10",
            $params
        );

        jsonResponse(true, 'Statistiques récupérées.', [
            'total_absences'      => $totalAbsences,
            'absences_justifiees' => $absencesJustifiees,
            'absences_non_justifiees' => $totalAbsences - $absencesJustifiees,
            'top_absents'         => $topAbsents
        ]);
    }

    // ============================================================
    // 6. ABSENCES D'UN ÉLÈVE
    // ============================================================

    /**
     * Récupère les absences d'un élève spécifique
     */
    public function getAbsencesEleve(): void
    {
        $eleveId = (int) post('eleve_id', 0);
        if ($eleveId <= 0) jsonResponse(false, 'ID élève invalide.', [], 400);

        $absences = $this->db->fetchAll(
            "SELECT a.*, DATE_FORMAT(a.date_absence, '%d/%m/%Y') AS date_format,
                    CONCAT(u.prenom, ' ', u.nom) AS saisie_par_nom
             FROM absences a
             LEFT JOIN utilisateurs u ON a.saisie_par = u.id
             WHERE a.eleve_id = :eid
             ORDER BY a.date_absence DESC",
            ['eid' => $eleveId]
        );

        // Statistiques de l'élève
        $totalJours = count($absences);
        $totalAbsences = count(array_filter($absences, fn($a) => $a['type_absence'] === 'Absence'));
        $totalJustifiees = count(array_filter($absences, fn($a) => $a['est_justifiee'] == 1));
        $tauxAbsenteisme = $totalJours > 0 ? round(($totalAbsences / $totalJours) * 100, 1) : 0;

        jsonResponse(true, 'Absences récupérées.', [
            'absences'          => $absences,
            'total_jours'       => $totalJours,
            'total_absences'    => $totalAbsences,
            'total_justifiees'  => $totalJustifiees,
            'taux_absenteisme'  => $tauxAbsenteisme
        ]);
    }

    // ============================================================
    // 7. MÉTHODES PUBLIQUES (pour les vues)
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
     * Récupère les statistiques d'absence pour le dashboard
     */
    public function getStatsDashboard(): array
    {
        $today = date('Y-m-d');

        $totalAbsences = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM absences WHERE date_absence = :date AND type_absence = 'Absence'",
            ['date' => $today]
        );

        $totalPresences = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM absences WHERE date_absence = :date AND type_absence = 'Présent'",
            ['date' => $today]
        );

        $totalMarques = $totalAbsences + $totalPresences;

        return [
            'absences_jour'  => $totalAbsences,
            'presences_jour' => $totalPresences,
            'total_marques'  => $totalMarques,
            'taux_presence'  => $totalMarques > 0 ? round(($totalPresences / $totalMarques) * 100, 1) : 0
        ];
    }
}