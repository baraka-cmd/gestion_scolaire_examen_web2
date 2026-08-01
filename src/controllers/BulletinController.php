<?php
/**
 * Fichier : src/controllers/BulletinController.php
 * Rôle    : Génération des bulletins pour une classe entière
 * Système : Bulletin congolais — 6 périodes + 3 examens + 3 trimestres + total annuel
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class BulletinController
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
        if (!can('bulletins.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'genererBulletinsClasse':
                $this->genererBulletinsClasse();
                break;
            case 'getBulletinsClasse':
                $this->getBulletinsClasse();
                break;
            case 'genererBulletinEleve':
                $this->genererBulletinEleve();
                break;
            case 'getStatistiquesClasse':
                $this->getStatistiquesClasse();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. GÉNÉRATION DES BULLETINS POUR UNE CLASSE
    // ============================================================

    /**
     * Génère les bulletins de tous les élèves d'une classe
     */
    public function genererBulletinsClasse(): void
    {
        requirePermission('bulletins.generer');

        $classeId = (int) post('classe_id', 0);
        $anneeId  = (int) post('annee_id', 0);
        $trimestreId = (int) post('trimestre_id', 0);

        if ($classeId <= 0) jsonResponse(false, 'ID classe invalide.', [], 400);

        // Si pas d'année, prendre l'année active
        if ($anneeId <= 0) {
            $anneeId = (int) $this->db->fetchColumn(
                "SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1"
            );
        }

        // Récupérer les élèves de la classe
        $eleves = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.classe_id = :cid AND i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.est_actif = 1
             ORDER BY e.nom, e.prenom",
            ['cid' => $classeId, 'aid' => $anneeId]
        );

        if (empty($eleves)) {
            jsonResponse(false, 'Aucun élève trouvé dans cette classe.', [], 404);
        }

        $generes = 0;
        $ignores = 0;

        try {
            $this->db->beginTransaction();

            foreach ($eleves as $eleve) {
                // Vérifier si le bulletin existe déjà pour ce trimestre
                if ($trimestreId > 0) {
                    $existe = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM bulletins 
                         WHERE eleve_id = :eid AND trimestre_id = :tid",
                        ['eid' => $eleve['id'], 'tid' => $trimestreId]
                    );

                    if ($existe > 0) {
                        $ignores++;
                        continue;
                    }
                }

                // Récupérer les résultats calculés
                $resultats = [];
                if ($trimestreId > 0) {
                    $resultats = $this->db->fetchAll(
                        "SELECT * FROM resultats_trimestriels 
                         WHERE eleve_id = :eid AND trimestre_id = :tid",
                        ['eid' => $eleve['id'], 'tid' => $trimestreId]
                    );
                } else {
                    // Tous les trimestres
                    $resultats = $this->db->fetchAll(
                        "SELECT * FROM resultats_trimestriels 
                         WHERE eleve_id = :eid 
                         AND trimestre_id IN (SELECT id FROM trimestres WHERE annee_scolaire_id = :aid)
                         ORDER BY trimestre_id",
                        ['eid' => $eleve['id'], 'aid' => $anneeId]
                    );
                }

                if (empty($resultats)) {
                    $ignores++;
                    continue;
                }

                // Générer le bulletin pour chaque trimestre
                foreach ($resultats as $resultat) {
                    $numeroBulletin = 'BUL-' . $eleve['matricule'] . '-T' . $resultat['trimestre_id'] . '-' . date('Y');

                    $bulletinId = $this->db->insert(
                        "INSERT INTO bulletins (eleve_id, trimestre_id, numero_bulletin, date_edition, 
                         edite_par, commentaire_enseignant, est_imprime)
                         VALUES (:eid, :tid, :numero, CURDATE(), :uid, :commentaire, 0)",
                        [
                            'eid'         => $eleve['id'],
                            'tid'         => $resultat['trimestre_id'],
                            'numero'      => $numeroBulletin,
                            'uid'         => $_SESSION['utilisateur_id'],
                            'commentaire' => 'Bulletin généré automatiquement.'
                        ]
                    );

                    $generes++;
                }
            }

            $this->db->commit();

            logActivity('Génération', 'bulletins', null, "{$generes} bulletin(s) généré(s) pour la classe {$classeId}");

            jsonResponse(true, "{$generes} bulletin(s) généré(s), {$ignores} ignoré(s).");

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur génération bulletins: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la génération des bulletins.');
        }
    }

    // ============================================================
    // 2. RÉCUPÉRATION DES BULLETINS D'UNE CLASSE
    // ============================================================

    /**
     * Récupère la liste des bulletins générés pour une classe
     */
    public function getBulletinsClasse(): void
    {
        $classeId    = (int) post('classe_id', 0);
        $trimestreId = (int) post('trimestre_id', 0);

        if ($classeId <= 0) jsonResponse(false, 'ID classe invalide.', [], 400);

        $where = [];
        $params = ['cid' => $classeId];

        if ($trimestreId > 0) {
            $where[] = "b.trimestre_id = :tid";
            $params['tid'] = $trimestreId;
        }

        $whereClause = !empty($where) ? 'AND ' . implode(' AND ', $where) : '';

        $bulletins = $this->db->fetchAll(
            "SELECT b.*, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom, t.libelle AS trimestre_libelle,
                    DATE_FORMAT(b.date_edition, '%d/%m/%Y %H:%i') AS date_edition_format,
                    rt.moyenne_generale, rt.mention, rt.decision, rt.rang, rt.effectif_classe
             FROM bulletins b
             INNER JOIN eleves e ON b.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             INNER JOIN trimestres t ON b.trimestre_id = t.id
             LEFT JOIN resultats_trimestriels rt ON b.eleve_id = rt.eleve_id AND b.trimestre_id = rt.trimestre_id
             WHERE i.classe_id = :cid {$whereClause}
             ORDER BY e.nom, e.prenom, t.numero",
            $params
        );

        jsonResponse(true, 'Bulletins récupérés.', [
            'bulletins' => $bulletins,
            'total'     => count($bulletins)
        ]);
    }

    // ============================================================
    // 3. GÉNÉRATION DU BULLETIN D'UN ÉLÈVE
    // ============================================================

    /**
     * Génère le bulletin complet d'un élève pour une année scolaire
     */
    public function genererBulletinEleve(): void
    {
        $eleveId = (int) post('eleve_id', 0);
        $anneeId = (int) post('annee_id', 0);

        if ($eleveId <= 0) jsonResponse(false, 'ID élève invalide.', [], 400);

        if ($anneeId <= 0) {
            $anneeId = (int) $this->db->fetchColumn(
                "SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1"
            );
        }

        // Récupérer les infos de l'élève
        $eleve = $this->db->fetchOne(
            "SELECT e.*, c.nom AS classe_nom, c.niveau AS classe_niveau, c.id AS classe_id
             FROM eleves e
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             WHERE e.id = :eid AND i.annee_scolaire_id = :aid",
            ['eid' => $eleveId, 'aid' => $anneeId]
        );

        if (!$eleve) jsonResponse(false, 'Élève introuvable.', [], 404);

        // Récupérer tous les groupes
        $groupes = $this->db->fetchAll(
            "SELECT * FROM matieres WHERE est_groupe = 1 AND est_actif = 1 ORDER BY id"
        );

        // Construire le bulletin
        $bulletin = [
            'eleve'     => $eleve,
            'annee'     => $this->db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $anneeId]),
            'ecole'     => $this->db->fetchOne("SELECT * FROM parametres_ecole LIMIT 1"),
            'groupes'   => [],
            'totaux'    => [
                'T1' => ['max' => 0, 'obt' => 0, 'p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
                'T2' => ['max' => 0, 'obt' => 0, 'p3' => ['max' => 0, 'obt' => 0], 'p4' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
                'T3' => ['max' => 0, 'obt' => 0, 'p5' => ['max' => 0, 'obt' => 0], 'p6' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
                'annuel' => ['max' => 0, 'obt' => 0]
            ]
        ];

        foreach ($groupes as $groupe) {
            $sousMatieres = $this->db->fetchAll(
                "SELECT m.id, m.libelle, m.code, 
                        COALESCE(cm.maxima_override, m.maxima) AS maxima_periode
                 FROM matieres m
                 LEFT JOIN classe_matieres cm ON m.id = cm.matiere_id AND cm.classe_id = :cid AND cm.est_active = 1
                 WHERE m.parent_id = :gid AND m.est_groupe = 0 AND m.est_actif = 1
                 ORDER BY m.id",
                ['gid' => $groupe['id'], 'cid' => $eleve['classe_id']]
            );

            $groupeData = [
                'libelle'       => $groupe['libelle'],
                'code'          => $groupe['code'],
                'sous_matieres' => [],
                'sous_total'    => [
                    'T1' => ['max' => 0, 'obt' => 0],
                    'T2' => ['max' => 0, 'obt' => 0],
                    'T3' => ['max' => 0, 'obt' => 0],
                    'annuel' => ['max' => 0, 'obt' => 0]
                ]
            ];

            foreach ($sousMatieres as $sm) {
                $maxPeriode = (int) $sm['maxima_periode'];
                $maxExamen  = $maxPeriode * 2;
                $maxTrimestre = $maxPeriode * 4;

                $notes = [
                    'T1' => $this->calculerNotesTrimestre($eleveId, $sm['id'], 1, $maxPeriode),
                    'T2' => $this->calculerNotesTrimestre($eleveId, $sm['id'], 2, $maxPeriode),
                    'T3' => $this->calculerNotesTrimestre($eleveId, $sm['id'], 3, $maxPeriode),
                ];

                $smData = [
                    'libelle'       => $sm['libelle'],
                    'code'          => $sm['code'],
                    'max_periode'   => $maxPeriode,
                    'max_examen'    => $maxExamen,
                    'max_trimestre' => $maxTrimestre,
                    'max_annuel'    => $maxTrimestre * 3,
                    'notes'         => $notes,
                    'annuel'        => [
                        'max' => $maxTrimestre * 3,
                        'obt' => $notes['T1']['total_obt'] + $notes['T2']['total_obt'] + $notes['T3']['total_obt']
                    ]
                ];

                // Ajouter aux totaux
                foreach (['T1', 'T2', 'T3'] as $trimKey) {
                    $bulletin['totaux'][$trimKey]['max'] += $notes[$trimKey]['total_max'];
                    $bulletin['totaux'][$trimKey]['obt'] += $notes[$trimKey]['total_obt'];
                    $groupeData['sous_total'][$trimKey]['max'] += $notes[$trimKey]['total_max'];
                    $groupeData['sous_total'][$trimKey]['obt'] += $notes[$trimKey]['total_obt'];
                }
                $bulletin['totaux']['annuel']['max'] += $smData['max_annuel'];
                $bulletin['totaux']['annuel']['obt'] += $smData['annuel']['obt'];
                $groupeData['sous_total']['annuel']['max'] += $smData['max_annuel'];
                $groupeData['sous_total']['annuel']['obt'] += $smData['annuel']['obt'];

                $groupeData['sous_matieres'][] = $smData;
            }

            $bulletin['groupes'][] = $groupeData;
        }

        // Calculer les pourcentages et mentions
        $stats = [];
        foreach (['T1', 'T2', 'T3', 'annuel'] as $key) {
            $pct = $bulletin['totaux'][$key]['max'] > 0 
                ? round(($bulletin['totaux'][$key]['obt'] / $bulletin['totaux'][$key]['max']) * 100, 1) 
                : 0;
            $stats[$key] = [
                'pourcentage' => $pct,
                'mention'     => $this->determinerMention($pct),
                'decision'    => $this->determinerDecision($pct)
            ];
        }

        $bulletin['stats'] = $stats;

        jsonResponse(true, 'Bulletin généré.', ['bulletin' => $bulletin]);
    }

    // ============================================================
    // 4. STATISTIQUES D'UNE CLASSE
    // ============================================================

    /**
     * Récupère les statistiques d'une classe pour un trimestre
     */
    public function getStatistiquesClasse(): void
    {
        $classeId    = (int) post('classe_id', 0);
        $trimestreId = (int) post('trimestre_id', 0);

        if ($classeId <= 0 || $trimestreId <= 0) {
            jsonResponse(false, 'Classe et trimestre obligatoires.', [], 400);
        }

        $stats = [
            'effectif'        => 0,
            'admis'           => 0,
            'ajournes'        => 0,
            'taux_reussite'   => 0,
            'moyenne_classe'  => 0,
            'moyenne_haute'   => 0,
            'moyenne_basse'   => 0,
            'mentions'        => []
        ];

        $resultats = $this->db->fetchAll(
            "SELECT moyenne_generale, mention, decision
             FROM resultats_trimestriels
             WHERE classe_id = :cid AND trimestre_id = :tid",
            ['cid' => $classeId, 'tid' => $trimestreId]
        );

        if (!empty($resultats)) {
            $stats['effectif'] = count($resultats);
            $stats['admis'] = count(array_filter($resultats, fn($r) => $r['decision'] === 'Admis'));
            $stats['ajournes'] = $stats['effectif'] - $stats['admis'];
            $stats['taux_reussite'] = $stats['effectif'] > 0 
                ? round(($stats['admis'] / $stats['effectif']) * 100, 1) : 0;

            $moyennes = array_column($resultats, 'moyenne_generale');
            $stats['moyenne_classe'] = round(array_sum($moyennes) / count($moyennes), 1);
            $stats['moyenne_haute'] = max($moyennes);
            $stats['moyenne_basse'] = min($moyennes);

            // Compter les mentions
            foreach ($resultats as $r) {
                $mention = $r['mention'];
                if (!isset($stats['mentions'][$mention])) {
                    $stats['mentions'][$mention] = 0;
                }
                $stats['mentions'][$mention]++;
            }
        }

        jsonResponse(true, 'Statistiques récupérées.', ['stats' => $stats]);
    }

    // ============================================================
    // 5. MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Calcule les notes d'une sous-matière pour un trimestre
     */
    private function calculerNotesTrimestre(int $eleveId, int $sousMatiereId, int $trimestreNum, int $maxPeriode): array
    {
        $maxExamen = $maxPeriode * 2;
        $maxTrimestre = ($maxPeriode * 2) + $maxExamen;

        switch ($trimestreNum) {
            case 1: $typeP1 = 'Période 1'; $typeP2 = 'Période 2'; $typeEx = 'Examen'; break;
            case 2: $typeP1 = 'Période 3'; $typeP2 = 'Période 4'; $typeEx = 'Examen'; break;
            case 3: $typeP1 = 'Période 5'; $typeP2 = 'Période 6'; $typeEx = 'Examen'; break;
            default:
                return [
                    'p1' => ['max' => $maxPeriode, 'obt' => 0],
                    'p2' => ['max' => $maxPeriode, 'obt' => 0],
                    'ex' => ['max' => $maxExamen, 'obt' => 0],
                    'total_max' => $maxTrimestre,
                    'total_obt' => 0
                ];
        }

        $noteP1 = (float) $this->db->fetchColumn(
            "SELECT COALESCE(n.valeur, 0) FROM notes n
             INNER JOIN evaluations ev ON n.evaluation_id = ev.id
             INNER JOIN trimestres t ON ev.trimestre_id = t.id
             WHERE n.eleve_id = :eid AND n.sous_matiere_id = :smid 
             AND t.numero = :tnum AND ev.type_evaluation = :type AND n.absence = 0 LIMIT 1",
            ['eid' => $eleveId, 'smid' => $sousMatiereId, 'tnum' => $trimestreNum, 'type' => $typeP1]
        );

        $noteP2 = (float) $this->db->fetchColumn(
            "SELECT COALESCE(n.valeur, 0) FROM notes n
             INNER JOIN evaluations ev ON n.evaluation_id = ev.id
             INNER JOIN trimestres t ON ev.trimestre_id = t.id
             WHERE n.eleve_id = :eid AND n.sous_matiere_id = :smid 
             AND t.numero = :tnum AND ev.type_evaluation = :type AND n.absence = 0 LIMIT 1",
            ['eid' => $eleveId, 'smid' => $sousMatiereId, 'tnum' => $trimestreNum, 'type' => $typeP2]
        );

        $noteEx = (float) $this->db->fetchColumn(
            "SELECT COALESCE(n.valeur, 0) FROM notes n
             INNER JOIN evaluations ev ON n.evaluation_id = ev.id
             INNER JOIN trimestres t ON ev.trimestre_id = t.id
             WHERE n.eleve_id = :eid AND n.sous_matiere_id = :smid 
             AND t.numero = :tnum AND ev.type_evaluation = :type AND n.absence = 0 LIMIT 1",
            ['eid' => $eleveId, 'smid' => $sousMatiereId, 'tnum' => $trimestreNum, 'type' => $typeEx]
        );

        return [
            'p1' => ['max' => $maxPeriode, 'obt' => $noteP1],
            'p2' => ['max' => $maxPeriode, 'obt' => $noteP2],
            'ex' => ['max' => $maxExamen, 'obt' => $noteEx],
            'total_max' => $maxTrimestre,
            'total_obt' => $noteP1 + $noteP2 + $noteEx
        ];
    }

    /**
     * Détermine la mention selon le pourcentage
     */
    public function determinerMention(float $pourcentage): string
    {
        if ($pourcentage >= 90) return 'Excellent';
        if ($pourcentage >= 80) return 'Très Bien';
        if ($pourcentage >= 70) return 'Bien';
        if ($pourcentage >= 60) return 'Assez Bien';
        if ($pourcentage >= 50) return 'Passable';
        return 'Insuffisant';
    }

    /**
     * Détermine la décision selon le pourcentage
     */
    public function determinerDecision(float $pourcentage): string
    {
        return $pourcentage >= 50 ? 'Admis' : 'Ajourné';
    }

    // ============================================================
    // 6. MÉTHODES PUBLIQUES (pour les vues)
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
     * Récupère les trimestres de l'année active
     */
    public function getTrimestresActifs(): array
    {
        return $this->db->fetchAll(
            "SELECT t.* FROM trimestres t
             WHERE t.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY t.numero"
        );
    }

    /**
     * Récupère les années scolaires
     */
    public function getAnneesScolaires(): array
    {
        return $this->db->fetchAll("SELECT * FROM annees_scolaires ORDER BY date_debut DESC");
    }

    /**
     * Récupère un bulletin par ID
     */
    public function getBulletinById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT b.*, e.matricule, e.nom, e.prenom, e.sexe,
                    c.nom AS classe_nom, t.libelle AS trimestre_libelle,
                    DATE_FORMAT(b.date_edition, '%d/%m/%Y %H:%i') AS date_edition_format
             FROM bulletins b
             INNER JOIN eleves e ON b.eleve_id = e.id
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             INNER JOIN trimestres t ON b.trimestre_id = t.id
             WHERE b.id = :id",
            ['id' => $id]
        );
    }
}