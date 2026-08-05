<?php
/**
 * Fichier : src/controllers/StatistiqueController.php
 * Rôle    : Gestion des rapports statistiques globaux
 * Système : École primaire congolaise — Rapports
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class StatistiqueController
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
        if (!can('rapports.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'getKPIs':
                $this->getKPIs();
                break;
            case 'getEffectifsParClasse':
                $this->getEffectifsParClasse();
                break;
            case 'getResultatsParTrimestre':
                $this->getResultatsParTrimestre();
                break;
            case 'getRecouvrementParClasse':
                $this->getRecouvrementParClasse();
                break;
            case 'getPresencesParMois':
                $this->getPresencesParMois();
                break;
            case 'getIncidentsParMois':
                $this->getIncidentsParMois();
                break;
            case 'getTopClasses':
                $this->getTopClasses();
                break;
            case 'getClassesARisque':
                $this->getClassesARisque();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. INDICATEURS CLÉS (KPIs)
    // ============================================================

    private function getKPIs(): void
    {
        $anneeActive = $this->getAnneeActiveId();
        $trimestreActif = $this->getTrimestreActifId();

        // Total élèves
        $totalEleves = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :aid AND statut = 'Actif'",
            ['aid' => $anneeActive]
        );

        // Total enseignants
        $totalEnseignants = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM enseignants WHERE est_actif = 1"
        );

        // Total classes
        $totalClasses = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM classes WHERE annee_scolaire_id = :aid",
            ['aid' => $anneeActive]
        );

        // Ratio élèves/enseignant
        $ratioElevesEnseignant = $totalEnseignants > 0 ? round($totalEleves / $totalEnseignants, 1) : 0;

        // Taux de présence (aujourd'hui)
        $presencesJour = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM absences WHERE date_absence = CURDATE() AND type_absence = 'Présent'"
        );
        $absencesJour = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM absences WHERE date_absence = CURDATE() AND type_absence = 'Absence'"
        );
        $totalMarques = $presencesJour + $absencesJour;
        $tauxPresence = $totalMarques > 0 ? round(($presencesJour / $totalMarques) * 100, 1) : 0;

        // Taux de réussite (trimestre actif)
        $totalResultats = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM resultats_trimestriels WHERE trimestre_id = :tid",
            ['tid' => $trimestreActif]
        );
        $totalAdmis = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM resultats_trimestriels WHERE trimestre_id = :tid AND decision = 'Admis'",
            ['tid' => $trimestreActif]
        );
        $tauxReussite = $totalResultats > 0 ? round(($totalAdmis / $totalResultats) * 100, 1) : 0;

        // Taux de recouvrement financier
        $totalAttendu = (float) $this->db->fetchColumn("SELECT COALESCE(SUM(montant_total), 0) FROM paiements");
        $totalPaye = (float) $this->db->fetchColumn("SELECT COALESCE(SUM(montant_paye), 0) FROM paiements");
        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 1) : 0;

        // Moyenne générale du trimestre
        $moyenneGenerale = (float) $this->db->fetchColumn(
            "SELECT ROUND(AVG(moyenne_generale), 1) FROM resultats_trimestriels WHERE trimestre_id = :tid",
            ['tid' => $trimestreActif]
        );

        // Top enseignant (meilleur taux de réussite)
        $topEnseignant = $this->db->fetchOne(
            "SELECT CONCAT(e.prenom, ' ', e.nom) AS nom, c.nom AS classe_nom,
                    ROUND(AVG(rt.moyenne_generale), 1) AS moyenne_classe
             FROM resultats_trimestriels rt
             INNER JOIN classes c ON rt.classe_id = c.id
             INNER JOIN enseignants e ON c.id = e.classe_id
             WHERE rt.trimestre_id = :tid AND e.est_actif = 1
             GROUP BY e.id, e.prenom, e.nom, c.nom
             ORDER BY moyenne_classe DESC LIMIT 1",
            ['tid' => $trimestreActif]
        );

        // Évolution vs mois dernier
        $elevesMoisDernier = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions 
             WHERE annee_scolaire_id = :aid AND statut = 'Actif'
             AND date_inscription <= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)",
            ['aid' => $anneeActive]
        );
        $evolutionMensuelle = $elevesMoisDernier > 0 ? round((($totalEleves - $elevesMoisDernier) / $elevesMoisDernier) * 100, 1) : 0;

        jsonResponse(true, 'KPIs récupérés.', [
            'kpis' => [
                'total_eleves'              => $totalEleves,
                'total_enseignants'         => $totalEnseignants,
                'total_classes'             => $totalClasses,
                'ratio_eleves_enseignant'   => $ratioElevesEnseignant,
                'taux_presence'             => $tauxPresence,
                'taux_reussite'             => $tauxReussite,
                'taux_recouvrement'         => $tauxRecouvrement,
                'moyenne_generale'          => $moyenneGenerale,
                'top_enseignant'            => $topEnseignant,
                'evolution_mensuelle'       => $evolutionMensuelle,
                'presences_jour'            => $presencesJour,
                'absences_jour'             => $absencesJour
            ]
        ]);
    }

    // ============================================================
    // 2. EFFECTIFS PAR CLASSE
    // ============================================================

    private function getEffectifsParClasse(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $effectifs = $this->db->fetchAll(
            "SELECT c.nom AS classe_nom, c.niveau,
                    COUNT(i.id) AS total,
                    COALESCE(SUM(CASE WHEN e.sexe = 'M' THEN 1 ELSE 0 END), 0) AS garcons,
                    COALESCE(SUM(CASE WHEN e.sexe = 'F' THEN 1 ELSE 0 END), 0) AS filles,
                    c.capacite_max,
                    ROUND((COUNT(i.id) / c.capacite_max) * 100, 1) AS taux_remplissage
             FROM classes c
             LEFT JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
             LEFT JOIN eleves e ON i.eleve_id = e.id
             WHERE c.annee_scolaire_id = :aid
             GROUP BY c.id, c.nom, c.niveau, c.capacite_max
             ORDER BY c.niveau",
            ['aid' => $anneeActive]
        );

        jsonResponse(true, 'Effectifs récupérés.', ['effectifs' => $effectifs]);
    }

    // ============================================================
    // 3. RÉSULTATS PAR TRIMESTRE
    // ============================================================

    private function getResultatsParTrimestre(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $resultats = $this->db->fetchAll(
            "SELECT t.libelle AS trimestre, t.numero,
                    COUNT(rt.id) AS nb_eleves,
                    COALESCE(SUM(CASE WHEN rt.decision = 'Admis' THEN 1 ELSE 0 END), 0) AS admis,
                    COALESCE(SUM(CASE WHEN rt.decision != 'Admis' THEN 1 ELSE 0 END), 0) AS echoues,
                    ROUND(AVG(rt.moyenne_generale), 1) AS moyenne,
                    ROUND((COALESCE(SUM(CASE WHEN rt.decision = 'Admis' THEN 1 ELSE 0 END), 0) / COUNT(rt.id)) * 100, 1) AS taux_reussite
             FROM trimestres t
             LEFT JOIN resultats_trimestriels rt ON t.id = rt.trimestre_id
             WHERE t.annee_scolaire_id = :aid
             GROUP BY t.id, t.libelle, t.numero
             ORDER BY t.numero",
            ['aid' => $anneeActive]
        );

        jsonResponse(true, 'Résultats récupérés.', ['resultats' => $resultats]);
    }

    // ============================================================
    // 4. RECOUVREMENT PAR CLASSE
    // ============================================================

    private function getRecouvrementParClasse(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $recouvrement = $this->db->fetchAll(
            "SELECT c.nom AS classe_nom, c.niveau,
                    COALESCE(SUM(p.montant_total), 0) AS montant_du,
                    COALESCE(SUM(p.montant_paye), 0) AS montant_paye,
                    COALESCE(SUM(p.montant_total - p.montant_paye), 0) AS montant_impaye,
                    CASE 
                        WHEN COALESCE(SUM(p.montant_total), 0) > 0 
                        THEN ROUND((COALESCE(SUM(p.montant_paye), 0) / COALESCE(SUM(p.montant_total), 1)) * 100, 1)
                        ELSE 0 
                    END AS taux_recouvrement
             FROM classes c
             INNER JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
             LEFT JOIN paiements p ON i.eleve_id = p.eleve_id
             WHERE c.annee_scolaire_id = :aid
             GROUP BY c.id, c.nom, c.niveau
             ORDER BY c.niveau",
            ['aid' => $anneeActive]
        );

        jsonResponse(true, 'Recouvrement récupéré.', ['recouvrement' => $recouvrement]);
    }

    // ============================================================
    // 5. PRÉSENCES PAR MOIS
    // ============================================================

    private function getPresencesParMois(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $presences = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = date('Y-m', strtotime("-{$i} months"));
            $total = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM absences WHERE DATE_FORMAT(date_absence, '%Y-%m') = :mois",
                ['mois' => $mois]
            );
            $presents = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM absences WHERE DATE_FORMAT(date_absence, '%Y-%m') = :mois AND type_absence = 'Présent'",
                ['mois' => $mois]
            );
            $absences = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM absences WHERE DATE_FORMAT(date_absence, '%Y-%m') = :mois AND type_absence = 'Absence'",
                ['mois' => $mois]
            );
            $taux = $total > 0 ? round(($presents / $total) * 100, 1) : 0;

            $presences[] = [
                'mois'     => $mois,
                'label'    => moisEnFrancais((int)substr($mois, 5, 2)) . ' ' . substr($mois, 0, 4),
                'total'    => $total,
                'presents' => $presents,
                'absences' => $absences,
                'taux'     => $taux
            ];
        }

        jsonResponse(true, 'Présences récupérées.', ['presences' => $presences]);
    }

    // ============================================================
    // 6. INCIDENTS PAR MOIS
    // ============================================================

    private function getIncidentsParMois(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $incidents = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = date('Y-m', strtotime("-{$i} months"));
            $nb = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM discipline WHERE DATE_FORMAT(date_incident, '%Y-%m') = :mois",
                ['mois' => $mois]
            );

            $parGravite = $this->db->fetchAll(
                "SELECT s.gravite, COUNT(*) AS nb
                 FROM discipline d
                 INNER JOIN sanctions s ON d.sanction_id = s.id
                 WHERE DATE_FORMAT(d.date_incident, '%Y-%m') = :mois
                 GROUP BY s.gravite",
                ['mois' => $mois]
            );

            $legeres = 0;
            $moyennes = 0;
            $graves = 0;
            foreach ($parGravite as $g) {
                if ($g['gravite'] === 'légère') $legeres = (int)$g['nb'];
                elseif ($g['gravite'] === 'moyenne') $moyennes = (int)$g['nb'];
                elseif ($g['gravite'] === 'grave') $graves = (int)$g['nb'];
            }

            $incidents[] = [
                'mois'    => $mois,
                'label'   => moisEnFrancais((int)substr($mois, 5, 2)) . ' ' . substr($mois, 0, 4),
                'total'   => $nb,
                'legeres' => $legeres,
                'moyennes'=> $moyennes,
                'graves'  => $graves
            ];
        }

        jsonResponse(true, 'Incidents récupérés.', ['incidents' => $incidents]);
    }

    // ============================================================
    // 7. TOP 5 CLASSES (MEILLEURES MOYENNES)
    // ============================================================

    private function getTopClasses(): void
    {
        $trimestreActif = $this->getTrimestreActifId();

        $topClasses = $this->db->fetchAll(
            "SELECT c.nom AS classe_nom, c.niveau,
                    COUNT(rt.id) AS nb_eleves,
                    ROUND(AVG(rt.moyenne_generale), 1) AS moyenne_classe,
                    ROUND((COALESCE(SUM(CASE WHEN rt.decision = 'Admis' THEN 1 ELSE 0 END), 0) / COUNT(rt.id)) * 100, 1) AS taux_reussite
             FROM resultats_trimestriels rt
             INNER JOIN classes c ON rt.classe_id = c.id
             WHERE rt.trimestre_id = :tid
             GROUP BY c.id, c.nom, c.niveau
             HAVING COUNT(rt.id) > 0
             ORDER BY moyenne_classe DESC
             LIMIT 5",
            ['tid' => $trimestreActif]
        );

        jsonResponse(true, 'Top classes récupéré.', ['top_classes' => $topClasses]);
    }

    // ============================================================
    // 8. CLASSES À RISQUE (MOINS DE 50% DE RÉUSSITE)
    // ============================================================

    private function getClassesARisque(): void
    {
        $trimestreActif = $this->getTrimestreActifId();

        $classesRisque = $this->db->fetchAll(
            "SELECT c.nom AS classe_nom, c.niveau,
                    COUNT(rt.id) AS nb_eleves,
                    ROUND(AVG(rt.moyenne_generale), 1) AS moyenne_classe,
                    ROUND((COALESCE(SUM(CASE WHEN rt.decision = 'Admis' THEN 1 ELSE 0 END), 0) / COUNT(rt.id)) * 100, 1) AS taux_reussite,
                    COALESCE(SUM(CASE WHEN rt.decision != 'Admis' THEN 1 ELSE 0 END), 0) AS nb_echecs
             FROM resultats_trimestriels rt
             INNER JOIN classes c ON rt.classe_id = c.id
             WHERE rt.trimestre_id = :tid
             GROUP BY c.id, c.nom, c.niveau
             HAVING taux_reussite < 50 AND COUNT(rt.id) > 0
             ORDER BY taux_reussite ASC",
            ['tid' => $trimestreActif]
        );

        jsonResponse(true, 'Classes à risque récupérées.', ['classes_risque' => $classesRisque]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    private function getAnneeActiveId(): int
    {
        $id = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
        if (!$id) {
            $id = $this->db->fetchColumn("SELECT id FROM annees_scolaires WHERE est_active = 1 LIMIT 1");
        }
        return (int) ($id ?? 1);
    }

    private function getTrimestreActifId(): int
    {
        $id = $this->db->fetchColumn("SELECT trimestre_actif_id FROM parametres_ecole LIMIT 1");
        if (!$id) {
            $id = $this->db->fetchColumn("SELECT id FROM trimestres WHERE est_actif = 1 LIMIT 1");
        }
        return (int) ($id ?? 1);
    }
}