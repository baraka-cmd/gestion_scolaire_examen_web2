<?php
/**
 * Fichier : src/controllers/EffectifController.php
 * Rôle    : Gestion des rapports d'effectifs — Statistiques et graphiques
 * Système : École primaire congolaise — Rapports
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class EffectifController
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
            case 'getStatistiques':
                $this->getStatistiques();
                break;
            case 'getEffectifsParClasse':
                $this->getEffectifsParClasse();
                break;
            case 'getRepartitionSexe':
                $this->getRepartitionSexe();
                break;
            case 'getEvolutionEffectifs':
                $this->getEvolutionEffectifs();
                break;
            case 'getPyramideAges':
                $this->getPyramideAges();
                break;
            case 'getRepartitionParAge':
                $this->getRepartitionParAge();
                break;
            case 'getTopClasses':
                $this->getTopClasses();
                break;
            case 'getNouvellesInscriptions':
                $this->getNouvellesInscriptions();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. STATISTIQUES GLOBALES (KPIs)
    // ============================================================

    private function getStatistiques(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        // Total élèves actifs
        $totalEleves = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions 
             WHERE annee_scolaire_id = :aid AND statut = 'Actif'",
            ['aid' => $anneeActive]
        );

        // Garçons
        $totalGarcons = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.sexe = 'M'",
            ['aid' => $anneeActive]
        );

        // Filles
        $totalFilles = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.sexe = 'F'",
            ['aid' => $anneeActive]
        );

        // Pourcentages
        $pctGarcons = $totalEleves > 0 ? round(($totalGarcons / $totalEleves) * 100, 1) : 0;
        $pctFilles = $totalEleves > 0 ? round(($totalFilles / $totalEleves) * 100, 1) : 0;

        // Ratio G/F
        $ratioGF = $totalFilles > 0 ? round($totalGarcons / $totalFilles, 2) : 0;

        // Nouvelles inscriptions ce mois
        $nouvellesInscriptions = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions 
             WHERE annee_scolaire_id = :aid 
             AND MONTH(date_inscription) = MONTH(CURDATE()) 
             AND YEAR(date_inscription) = YEAR(CURDATE())",
            ['aid' => $anneeActive]
        );

        // Nombre de classes
        $nbClasses = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM classes WHERE annee_scolaire_id = :aid",
            ['aid' => $anneeActive]
        );

        // Moyenne élèves par classe
        $moyenneParClasse = $nbClasses > 0 ? round($totalEleves / $nbClasses, 1) : 0;

        // Année précédente pour comparaison
        $anneePrecedente = $this->db->fetchOne(
            "SELECT id, libelle FROM annees_scolaires 
             WHERE date_debut < (SELECT date_debut FROM annees_scolaires WHERE id = :aid) 
             ORDER BY date_debut DESC LIMIT 1",
            ['aid' => $anneeActive]
        );

        $evolution = 0;
        $totalPrecedent = 0;
        if ($anneePrecedente) {
            $totalPrecedent = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :aid AND statut = 'Actif'",
                ['aid' => $anneePrecedente['id']]
            );
            $evolution = $totalPrecedent > 0 ? round((($totalEleves - $totalPrecedent) / $totalPrecedent) * 100, 1) : 0;
        }

        // Répartition par statut
        $parStatut = $this->db->fetchAll(
            "SELECT statut, COUNT(*) AS nb FROM inscriptions 
             WHERE annee_scolaire_id = :aid 
             GROUP BY statut ORDER BY nb DESC",
            ['aid' => $anneeActive]
        );

        jsonResponse(true, 'Statistiques récupérées.', [
            'stats' => [
                'total_eleves'           => $totalEleves,
                'total_garcons'          => $totalGarcons,
                'total_filles'           => $totalFilles,
                'pct_garcons'            => $pctGarcons,
                'pct_filles'             => $pctFilles,
                'ratio_gf'               => $ratioGF,
                'nouvelles_inscriptions' => $nouvellesInscriptions,
                'nb_classes'             => $nbClasses,
                'moyenne_par_classe'     => $moyenneParClasse,
                'evolution'              => $evolution,
                'total_precedent'        => $totalPrecedent,
                'annee_precedente'       => $anneePrecedente ? $anneePrecedente['libelle'] : null,
                'par_statut'             => $parStatut
            ]
        ]);
    }

    // ============================================================
    // 2. EFFECTIFS PAR CLASSE (DÉTAILLÉ)
    // ============================================================

    private function getEffectifsParClasse(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $effectifs = $this->db->fetchAll(
            "SELECT 
                c.id AS classe_id,
                c.nom AS classe_nom,
                c.niveau,
                c.capacite_max,
                COUNT(i.id) AS total_eleves,
                COALESCE(SUM(CASE WHEN e.sexe = 'M' THEN 1 ELSE 0 END), 0) AS garcons,
                COALESCE(SUM(CASE WHEN e.sexe = 'F' THEN 1 ELSE 0 END), 0) AS filles,
                ROUND((COUNT(i.id) / c.capacite_max) * 100, 1) AS taux_remplissage
             FROM classes c
             LEFT JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
             LEFT JOIN eleves e ON i.eleve_id = e.id
             WHERE c.annee_scolaire_id = :aid
             GROUP BY c.id, c.nom, c.niveau, c.capacite_max
             ORDER BY c.niveau",
            ['aid' => $anneeActive]
        );

        // Ajouter le nom du titulaire
        foreach ($effectifs as &$classe) {
            $titulaire = $this->db->fetchOne(
                "SELECT CONCAT(e.prenom, ' ', e.nom) AS nom_complet
                 FROM enseignants e
                 WHERE e.classe_id = :cid AND e.est_actif = 1 LIMIT 1",
                ['cid' => $classe['classe_id']]
            );
            $classe['titulaire'] = $titulaire ? $titulaire['nom_complet'] : 'Non assigné';
        }

        jsonResponse(true, 'Effectifs par classe récupérés.', [
            'effectifs' => $effectifs
        ]);
    }

    // ============================================================
    // 3. RÉPARTITION GARÇONS / FILLES (GLOBAL + PAR CLASSE)
    // ============================================================

    private function getRepartitionSexe(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        // Global
        $global = $this->db->fetchOne(
            "SELECT 
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN e.sexe = 'M' THEN 1 ELSE 0 END), 0) AS garcons,
                COALESCE(SUM(CASE WHEN e.sexe = 'F' THEN 1 ELSE 0 END), 0) AS filles
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif'",
            ['aid' => $anneeActive]
        );

        // Par classe
        $parClasse = $this->db->fetchAll(
            "SELECT 
                c.nom AS classe_nom,
                c.niveau,
                COUNT(i.id) AS total,
                COALESCE(SUM(CASE WHEN e.sexe = 'M' THEN 1 ELSE 0 END), 0) AS garcons,
                COALESCE(SUM(CASE WHEN e.sexe = 'F' THEN 1 ELSE 0 END), 0) AS filles
             FROM classes c
             LEFT JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
             LEFT JOIN eleves e ON i.eleve_id = e.id
             WHERE c.annee_scolaire_id = :aid
             GROUP BY c.id, c.nom, c.niveau
             ORDER BY c.niveau",
            ['aid' => $anneeActive]
        );

        jsonResponse(true, 'Répartition par sexe récupérée.', [
            'global'    => $global,
            'par_classe'=> $parClasse
        ]);
    }

    // ============================================================
    // 4. ÉVOLUTION DES EFFECTIFS (5 DERNIÈRES ANNÉES)
    // ============================================================

    private function getEvolutionEffectifs(): void
    {
        $annees = $this->db->fetchAll(
            "SELECT id, libelle FROM annees_scolaires 
             ORDER BY date_debut DESC LIMIT 5"
        );

        $evolution = [];
        foreach (array_reverse($annees) as $annee) {
            $total = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :aid AND statut = 'Actif'",
                ['aid' => $annee['id']]
            );
            $garcons = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions i
                 INNER JOIN eleves e ON i.eleve_id = e.id
                 WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.sexe = 'M'",
                ['aid' => $annee['id']]
            );
            $filles = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions i
                 INNER JOIN eleves e ON i.eleve_id = e.id
                 WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.sexe = 'F'",
                ['aid' => $annee['id']]
            );

            $evolution[] = [
                'annee'    => $annee['libelle'],
                'total'    => $total,
                'garcons'  => $garcons,
                'filles'   => $filles
            ];
        }

        jsonResponse(true, 'Évolution récupérée.', ['evolution' => $evolution]);
    }

    // ============================================================
    // 5. PYRAMIDE DES ÂGES
    // ============================================================

    private function getPyramideAges(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $ages = $this->db->fetchAll(
            "SELECT 
                TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age,
                e.sexe,
                COUNT(*) AS nb
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif'
             GROUP BY age, e.sexe
             ORDER BY age",
            ['aid' => $anneeActive]
        );

        // Organiser par âge
        $pyramide = [];
        $ageMin = 3;
        $ageMax = 20;

        for ($age = $ageMin; $age <= $ageMax; $age++) {
            $garcons = 0;
            $filles = 0;
            foreach ($ages as $a) {
                if ((int)$a['age'] === $age) {
                    if ($a['sexe'] === 'M') $garcons = (int)$a['nb'];
                    if ($a['sexe'] === 'F') $filles = (int)$a['nb'];
                }
            }
            $pyramide[] = [
                'age'     => $age,
                'garcons' => $garcons,
                'filles'  => $filles,
                'total'   => $garcons + $filles
            ];
        }

        // Statistiques d'âge
        $ageMoyen = $this->db->fetchColumn(
            "SELECT ROUND(AVG(TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE())), 1)
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif'",
            ['aid' => $anneeActive]
        );

        $ageMinGlobal = $this->db->fetchColumn(
            "SELECT MIN(TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()))
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif'",
            ['aid' => $anneeActive]
        );

        $ageMaxGlobal = $this->db->fetchColumn(
            "SELECT MAX(TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()))
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif'",
            ['aid' => $anneeActive]
        );

        jsonResponse(true, 'Pyramide des âges récupérée.', [
            'pyramide'      => $pyramide,
            'age_moyen'     => round((float)$ageMoyen, 1),
            'age_min'       => (int)$ageMinGlobal,
            'age_max'       => (int)$ageMaxGlobal
        ]);
    }

    // ============================================================
    // 6. RÉPARTITION PAR TRANCHE D'ÂGE
    // ============================================================

    private function getRepartitionParAge(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $repartition = $this->db->fetchAll(
            "SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) BETWEEN 3 AND 5 THEN '3-5 ans'
                    WHEN TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) BETWEEN 6 AND 8 THEN '6-8 ans'
                    WHEN TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) BETWEEN 9 AND 11 THEN '9-11 ans'
                    WHEN TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) BETWEEN 12 AND 14 THEN '12-14 ans'
                    WHEN TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) BETWEEN 15 AND 17 THEN '15-17 ans'
                    ELSE '18 ans et plus'
                END AS tranche_age,
                COUNT(*) AS nb,
                COALESCE(SUM(CASE WHEN e.sexe = 'M' THEN 1 ELSE 0 END), 0) AS garcons,
                COALESCE(SUM(CASE WHEN e.sexe = 'F' THEN 1 ELSE 0 END), 0) AS filles
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif'
             GROUP BY tranche_age
             ORDER BY MIN(TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()))",
            ['aid' => $anneeActive]
        );

        jsonResponse(true, 'Répartition par âge récupérée.', ['repartition' => $repartition]);
    }

    // ============================================================
    // 7. TOP 5 CLASSES LES PLUS REMPLIES
    // ============================================================

    private function getTopClasses(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $topClasses = $this->db->fetchAll(
            "SELECT 
                c.nom AS classe_nom,
                c.niveau,
                c.capacite_max,
                COUNT(i.id) AS nb_eleves,
                ROUND((COUNT(i.id) / c.capacite_max) * 100, 1) AS taux_remplissage,
                (c.capacite_max - COUNT(i.id)) AS places_disponibles
             FROM classes c
             LEFT JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
             WHERE c.annee_scolaire_id = :aid
             GROUP BY c.id, c.nom, c.niveau, c.capacite_max
             ORDER BY taux_remplissage DESC
             LIMIT 5",
            ['aid' => $anneeActive]
        );

        jsonResponse(true, 'Top classes récupéré.', ['top_classes' => $topClasses]);
    }

    // ============================================================
    // 8. NOUVELLES INSCRIPTIONS (12 DERNIERS MOIS)
    // ============================================================

    private function getNouvellesInscriptions(): void
    {
        $anneeActive = $this->getAnneeActiveId();

        $inscriptions = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = date('Y-m', strtotime("-{$i} months"));
            $nb = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions 
                 WHERE annee_scolaire_id = :aid 
                 AND DATE_FORMAT(date_inscription, '%Y-%m') = :mois",
                ['aid' => $anneeActive, 'mois' => $mois]
            );
            $inscriptions[] = [
                'mois'   => $mois,
                'label'  => moisEnFrancais((int)substr($mois, 5, 2)) . ' ' . substr($mois, 0, 4),
                'nb'     => $nb
            ];
        }

        jsonResponse(true, 'Inscriptions récupérées.', ['inscriptions' => $inscriptions]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Récupère l'ID de l'année scolaire active
     */
    private function getAnneeActiveId(): int
    {
        $id = $this->db->fetchColumn(
            "SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1"
        );
        if (!$id) {
            $id = $this->db->fetchColumn("SELECT id FROM annees_scolaires WHERE est_active = 1 LIMIT 1");
        }
        return (int) ($id ?? 1);
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère les statistiques pour l'affichage initial (PHP direct)
     */
    public function getStatsInitiales(): array
    {
        $anneeActive = $this->getAnneeActiveId();

        return [
            'total_eleves'  => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :aid AND statut = 'Actif'", ['aid' => $anneeActive]),
            'total_garcons' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM inscriptions i INNER JOIN eleves e ON i.eleve_id = e.id WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.sexe = 'M'", ['aid' => $anneeActive]),
            'total_filles'  => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM inscriptions i INNER JOIN eleves e ON i.eleve_id = e.id WHERE i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.sexe = 'F'", ['aid' => $anneeActive]),
            'nb_classes'    => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM classes WHERE annee_scolaire_id = :aid", ['aid' => $anneeActive]),
        ];
    }

    /**
     * Récupère les années scolaires (pour les filtres)
     */
    public function getAnneesScolaires(): array
    {
        return $this->db->fetchAll("SELECT * FROM annees_scolaires ORDER BY date_debut DESC");
    }
}