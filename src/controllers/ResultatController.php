<?php
/**
 * Fichier : src/controllers/ResultatController.php
 * Rôle    : Calcul des résultats, moyennes, rangs, mentions, décisions
 * Système : Bulletin congolais — 6 périodes + 3 examens + 3 trimestres + total annuel
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class ResultatController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Point d'entrée pour les requêtes AJAX
     */
    /**
 * Point d'entrée pour les requêtes AJAX
 */
public function handleAjax(): void
{
    if (!can('resultats.consulter')) {
        jsonResponse(false, 'Accès refusé.', [], 403);
    }

    if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Requête invalide.', [], 400);
    }

    $action = post('action', '');

    switch ($action) {
        case 'getResultatsEleve':
            $this->getResultatsEleve();
            break;
        case 'getProcesVerbal':
            $this->getProcesVerbal();
            break;
        case 'calculerResultatsClasse':
            $this->calculerResultatsClasse();
            break;
        case 'getDeliberations':
            $this->getDeliberations();
            break;
        case 'sauvegarderDeliberation':
            $this->sauvegarderDeliberation();
            break;
        case 'getProclamationPeriode':
            $this->getProclamationPeriode();
            break;
        default:
            jsonResponse(false, 'Action inconnue.', [], 400);
    }
}

    // ============================================================
    // 1. RÉSULTATS D'UN ÉLÈVE (BULLETIN COMPLET)
    // ============================================================

    /**
     * Récupère les résultats complets d'un élève pour une année scolaire
     */
    public function getResultatsEleve(): void
    {
        $eleveId  = (int) post('eleve_id', 0);
        $anneeId  = (int) post('annee_id', 0);

        if ($eleveId <= 0) jsonResponse(false, 'ID élève invalide.', [], 400);

        // Si pas d'année spécifiée, prendre l'année active
        if ($anneeId <= 0) {
            $anneeId = (int) $this->db->fetchColumn(
                "SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1"
            );
        }

        // Récupérer les infos de l'élève
        $eleve = $this->db->fetchOne(
            "SELECT e.*, c.nom AS classe_nom, c.niveau AS classe_niveau, c.id AS classe_id,
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age
             FROM eleves e
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             WHERE e.id = :eid AND i.annee_scolaire_id = :aid",
            ['eid' => $eleveId, 'aid' => $anneeId]
        );

        if (!$eleve) jsonResponse(false, 'Élève introuvable ou non inscrit.', [], 404);

        // Récupérer les trimestres
        $trimestres = $this->db->fetchAll(
            "SELECT * FROM trimestres WHERE annee_scolaire_id = :aid ORDER BY numero",
            ['aid' => $anneeId]
        );

        // Récupérer tous les groupes actifs
        $groupes = $this->db->fetchAll(
            "SELECT m.* FROM matieres m 
             WHERE m.est_groupe = 1 AND m.est_actif = 1 
             ORDER BY m.id"
        );

        // Construire les résultats par groupe
        $resultats = [];
        $totauxGeneraux = [
            'T1' => ['max' => 0, 'obt' => 0, 'p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
            'T2' => ['max' => 0, 'obt' => 0, 'p3' => ['max' => 0, 'obt' => 0], 'p4' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
            'T3' => ['max' => 0, 'obt' => 0, 'p5' => ['max' => 0, 'obt' => 0], 'p6' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
            'annuel' => ['max' => 0, 'obt' => 0]
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
                'id'          => $groupe['id'],
                'libelle'     => $groupe['libelle'],
                'code'        => $groupe['code'],
                'sous_matieres' => [],
                'sous_total'  => [
                    'T1' => ['max' => 0, 'obt' => 0, 'p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
                    'T2' => ['max' => 0, 'obt' => 0, 'p3' => ['max' => 0, 'obt' => 0], 'p4' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
                    'T3' => ['max' => 0, 'obt' => 0, 'p5' => ['max' => 0, 'obt' => 0], 'p6' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0]],
                    'annuel' => ['max' => 0, 'obt' => 0]
                ]
            ];

            foreach ($sousMatieres as $sm) {
                $maxPeriode = (int) $sm['maxima_periode'];
                $maxExamen  = $maxPeriode * 2;
                $maxTrimestre = $maxPeriode * 4; // P1 + P2 + Ex(2×)

                $smData = [
                    'id'          => $sm['id'],
                    'libelle'     => $sm['libelle'],
                    'code'        => $sm['code'],
                    'max_periode' => $maxPeriode,
                    'max_examen'  => $maxExamen,
                    'max_trimestre' => $maxTrimestre,
                    'max_annuel'  => $maxTrimestre * 3,
                    'T1' => $this->calculerNotesTrimestre($eleveId, $sm['id'], 1, $maxPeriode),
                    'T2' => $this->calculerNotesTrimestre($eleveId, $sm['id'], 2, $maxPeriode),
                    'T3' => $this->calculerNotesTrimestre($eleveId, $sm['id'], 3, $maxPeriode),
                ];

                // Calculer le total annuel
                $smData['annuel'] = [
                    'max' => $smData['max_annuel'],
                    'obt' => $smData['T1']['total_obt'] + $smData['T2']['total_obt'] + $smData['T3']['total_obt']
                ];

                // Ajouter aux sous-totaux du groupe
                foreach (['T1', 'T2', 'T3'] as $trimKey) {
                    foreach (['p1', 'p2', 'ex'] as $perKey) {
                        if ($trimKey === 'T1') $key = $perKey;
                        elseif ($trimKey === 'T2') $key = ($perKey === 'p1' ? 'p3' : ($perKey === 'p2' ? 'p4' : 'ex'));
                        else $key = ($perKey === 'p1' ? 'p5' : ($perKey === 'p2' ? 'p6' : 'ex'));

                        if (isset($smData[$trimKey][$perKey])) {
                            $groupeData['sous_total'][$trimKey][$key]['max'] += $smData[$trimKey][$perKey]['max'];
                            $groupeData['sous_total'][$trimKey][$key]['obt'] += $smData[$trimKey][$perKey]['obt'];
                            $totauxGeneraux[$trimKey][$key]['max'] += $smData[$trimKey][$perKey]['max'];
                            $totauxGeneraux[$trimKey][$key]['obt'] += $smData[$trimKey][$perKey]['obt'];
                        }
                    }
                    $groupeData['sous_total'][$trimKey]['max'] += $smData[$trimKey]['total_max'];
                    $groupeData['sous_total'][$trimKey]['obt'] += $smData[$trimKey]['total_obt'];
                    $totauxGeneraux[$trimKey]['max'] += $smData[$trimKey]['total_max'];
                    $totauxGeneraux[$trimKey]['obt'] += $smData[$trimKey]['total_obt'];
                }
                $groupeData['sous_total']['annuel']['max'] += $smData['max_annuel'];
                $groupeData['sous_total']['annuel']['obt'] += $smData['annuel']['obt'];
                $totauxGeneraux['annuel']['max'] += $smData['max_annuel'];
                $totauxGeneraux['annuel']['obt'] += $smData['annuel']['obt'];

                $groupeData['sous_matieres'][] = $smData;
            }

            $resultats[] = $groupeData;
        }

        // Calculer les pourcentages et mentions
        $stats = [];
        foreach (['T1', 'T2', 'T3', 'annuel'] as $key) {
            $pct = $totauxGeneraux[$key]['max'] > 0 
                ? round(($totauxGeneraux[$key]['obt'] / $totauxGeneraux[$key]['max']) * 100, 1) 
                : 0;
            $stats[$key] = [
                'max'         => $totauxGeneraux[$key]['max'],
                'obt'         => $totauxGeneraux[$key]['obt'],
                'pourcentage' => $pct,
                'mention'     => $this->determinerMention($pct),
                'decision'    => $this->determinerDecision($pct)
            ];
        }

        // Récupérer les rangs depuis la table resultats_trimestriels
        $rangs = $this->getRangsEleve($eleveId, $anneeId);

        jsonResponse(true, 'Résultats récupérés.', [
            'eleve'      => $eleve,
            'trimestres' => $trimestres,
            'resultats'  => $resultats,
            'totaux'     => $totauxGeneraux,
            'stats'      => $stats,
            'rangs'      => $rangs
        ]);
    }

    // ============================================================
    // 2. CALCUL DES NOTES D'UNE SOUS-MATIÈRE POUR UN TRIMESTRE
    // ============================================================

    /**
     * Calcule P1, P2, Examen et Total pour un trimestre
     * Trimestre 1 = Période 1 + Période 2 + Examen 1
     * Trimestre 2 = Période 3 + Période 4 + Examen 2
     * Trimestre 3 = Période 5 + Période 6 + Examen 3
     */
    /**
 * Récupère la note d'un élève pour une sous-matière, un trimestre et un type d'évaluation
 */
private function getNoteEleve(int $eleveId, int $sousMatiereId, int $trimestreNum, string $typeEvaluation): float
{
    $sql = "SELECT COALESCE(n.valeur, 0)
            FROM notes n
            INNER JOIN evaluations ev ON n.evaluation_id = ev.id
            INNER JOIN trimestres t ON ev.trimestre_id = t.id
            WHERE n.eleve_id = :eid 
            AND n.sous_matiere_id = :smid 
            AND t.numero = :tnum 
            AND ev.type_evaluation = :type
            AND n.absence = 0
            LIMIT 1";

    $result = $this->db->fetchColumn($sql, [
        'eid'  => $eleveId,
        'smid' => $sousMatiereId,
        'tnum' => $trimestreNum,
        'type' => $typeEvaluation
    ]);

    return $result !== null ? (float) $result : 0;
}

/**
 * Calcule P1, P2, Examen et Total pour un trimestre
 * CORRIGÉ : Tous les trimestres utilisent Période 1, Période 2, Examen
 */
private function calculerNotesTrimestre(int $eleveId, int $sousMatiereId, int $trimestreNum, int $maxPeriode): array
{
    $maxExamen = $maxPeriode * 2;
    $maxTrimestre = ($maxPeriode * 2) + $maxExamen;

    // Tous les trimestres utilisent les mêmes types d'évaluation
    // C'est le trimestre_num qui les différencie dans la requête SQL
    $noteP1 = $this->getNoteEleve($eleveId, $sousMatiereId, $trimestreNum, 'Période 1');
    $noteP2 = $this->getNoteEleve($eleveId, $sousMatiereId, $trimestreNum, 'Période 2');
    $noteEx = $this->getNoteEleve($eleveId, $sousMatiereId, $trimestreNum, 'Examen');

    $totalObt = $noteP1 + $noteP2 + $noteEx;

    return [
        'p1' => ['max' => $maxPeriode, 'obt' => $noteP1],
        'p2' => ['max' => $maxPeriode, 'obt' => $noteP2],
        'ex' => ['max' => $maxExamen, 'obt' => $noteEx],
        'total_max' => $maxTrimestre,
        'total_obt' => $totalObt
    ];
}

    // ============================================================
    // 3. PROCÈS-VERBAL (tous les élèves d'une classe)
    // ============================================================

    /**
     * Récupère le procès-verbal d'une classe pour un trimestre
     */
    public function getProcesVerbal(): void
    {
        $classeId    = (int) post('classe_id', 0);
        $trimestreId = (int) post('trimestre_id', 0);

        if ($classeId <= 0) jsonResponse(false, 'ID classe invalide.', [], 400);

        // Récupérer les élèves de la classe
        $eleves = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe, e.date_naissance
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1
             ORDER BY e.nom, e.prenom",
            ['cid' => $classeId]
        );

        // Récupérer le trimestre
        $trimestre = $this->db->fetchOne("SELECT * FROM trimestres WHERE id = :id", ['id' => $trimestreId]);

        // Calculer les résultats pour chaque élève
        $resultats = [];
        foreach ($eleves as $eleve) {
            $totalMax = 0;
            $totalObt = 0;

            // Récupérer tous les groupes
            $groupes = $this->db->fetchAll(
                "SELECT id FROM matieres WHERE est_groupe = 1 AND est_actif = 1"
            );

            foreach ($groupes as $groupe) {
                $sousMatieres = $this->db->fetchAll(
                    "SELECT m.id, COALESCE(cm.maxima_override, m.maxima) AS maxima_periode
                     FROM matieres m
                     LEFT JOIN classe_matieres cm ON m.id = cm.matiere_id AND cm.classe_id = :cid AND cm.est_active = 1
                     WHERE m.parent_id = :gid AND m.est_groupe = 0 AND m.est_actif = 1",
                    ['gid' => $groupe['id'], 'cid' => $classeId]
                );

                foreach ($sousMatieres as $sm) {
                    $maxPeriode = (int) $sm['maxima_periode'];
                    $maxTrimestre = $maxPeriode * 4;
                    $notes = $this->calculerNotesTrimestre($eleve['id'], $sm['id'], $trimestre['numero'], $maxPeriode);
                    $totalMax += $maxTrimestre;
                    $totalObt += $notes['total_obt'];
                }
            }

            $pct = $totalMax > 0 ? round(($totalObt / $totalMax) * 100, 1) : 0;

            $resultats[] = [
                'eleve_id'       => $eleve['id'],
                'matricule'      => $eleve['matricule'],
                'nom'            => $eleve['nom'],
                'prenom'         => $eleve['prenom'],
                'sexe'           => $eleve['sexe'],
                'total_max'      => $totalMax,
                'total_obt'      => $totalObt,
                'pourcentage'    => $pct,
                'mention'        => $this->determinerMention($pct),
                'decision'       => $this->determinerDecision($pct)
            ];
        }

        // Trier par pourcentage décroissant
        usort($resultats, function($a, $b) {
            return $b['pourcentage'] <=> $a['pourcentage'];
        });

        // Attribuer les rangs
        $effectif = count($resultats);
        foreach ($resultats as $index => &$r) {
            $r['rang'] = $index + 1;
            $r['effectif'] = $effectif;
        }
        unset($r);

        jsonResponse(true, 'Procès-verbal récupéré.', [
            'classe'    => $this->db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]),
            'trimestre' => $trimestre,
            'resultats' => $resultats,
            'effectif'  => $effectif
        ]);
    }

    // ============================================================
    // 4. CALCUL ET STOCKAGE DES RÉSULTATS D'UNE CLASSE
    // ============================================================

    /**
     * Calcule et stocke les résultats de tous les élèves d'une classe pour un trimestre
     */
    public function calculerResultatsClasse(): void
    {
        requirePermission('resultats.modifier');

        $classeId    = (int) post('classe_id', 0);
        $trimestreId = (int) post('trimestre_id', 0);

        if ($classeId <= 0 || $trimestreId <= 0) {
            jsonResponse(false, 'Classe et trimestre obligatoires.', [], 400);
        }

        $trimestre = $this->db->fetchOne("SELECT * FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
        if (!$trimestre) jsonResponse(false, 'Trimestre introuvable.', [], 404);

        // Récupérer le procès-verbal
        // (on simule l'appel interne)
        $_POST['classe_id'] = $classeId;
        $_POST['trimestre_id'] = $trimestreId;

        // Calculer les résultats
        $eleves = $this->db->fetchAll(
            "SELECT e.id FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1",
            ['cid' => $classeId]
        );

        $sauvegardes = 0;

        try {
            $this->db->beginTransaction();

            foreach ($eleves as $eleve) {
                $totalMax = 0;
                $totalObt = 0;

                $groupes = $this->db->fetchAll("SELECT id FROM matieres WHERE est_groupe = 1 AND est_actif = 1");

                foreach ($groupes as $groupe) {
                    $sousMatieres = $this->db->fetchAll(
                        "SELECT m.id, COALESCE(cm.maxima_override, m.maxima) AS maxima_periode
                         FROM matieres m
                         LEFT JOIN classe_matieres cm ON m.id = cm.matiere_id AND cm.classe_id = :cid AND cm.est_active = 1
                         WHERE m.parent_id = :gid AND m.est_groupe = 0 AND m.est_actif = 1",
                        ['gid' => $groupe['id'], 'cid' => $classeId]
                    );

                    foreach ($sousMatieres as $sm) {
                        $maxPeriode = (int) $sm['maxima_periode'];
                        $notes = $this->calculerNotesTrimestre($eleve['id'], $sm['id'], $trimestre['numero'], $maxPeriode);
                        $totalMax += $notes['total_max'];
                        $totalObt += $notes['total_obt'];
                    }
                }

                $pct = $totalMax > 0 ? round(($totalObt / $totalMax) * 100, 1) : 0;
                $mention = $this->determinerMention($pct);
                $decision = $this->determinerDecision($pct);

                // Supprimer l'ancien résultat
                $this->db->delete(
                    "DELETE FROM resultats_trimestriels WHERE eleve_id = :eid AND trimestre_id = :tid",
                    ['eid' => $eleve['id'], 'tid' => $trimestreId]
                );

                // Remplacez l'insertion SQL dans calculerResultatsClasse() par :

                $this->db->insert(
                    "INSERT INTO resultats_trimestriels (eleve_id, classe_id, trimestre_id, moyenne_generale, 
                    total_points, total_coefficients, mention, decision)
                    VALUES (:eid, :cid, :tid, :pct, :obt, :max, :mention, :decision)",
                    [
                        'eid'      => $eleve['id'],
                        'cid'      => $classeId,
                        'tid'      => $trimestreId,
                        'pct'      => $pct,
                        'obt'      => $totalObt, // Point obtenus -> total_points
                        'max'      => $totalMax, // Maxima -> total_coefficients
                        'mention'  => $mention,
                        'decision' => $decision
                    ]
                );


                $sauvegardes++;
            }

            // Calculer les rangs
            $this->calculerRangs($classeId, $trimestreId);

            $this->db->commit();

            logActivity('Calcul', 'resultats_trimestriels', null, "Résultats calculés pour classe {$classeId}, trimestre {$trimestreId}");

            jsonResponse(true, "{$sauvegardes} résultat(s) calculé(s) et sauvegardé(s).");

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur calcul résultats: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du calcul des résultats.');
        }
    }

    // ============================================================
    // 5. DÉLIBÉRATIONS
    // ============================================================

    /**
     * Récupère les résultats pour les délibérations
     */
    public function getDeliberations(): void
    {
        $classeId    = (int) post('classe_id', 0);
        $trimestreId = (int) post('trimestre_id', 0);

        if ($classeId <= 0 || $trimestreId <= 0) {
            jsonResponse(false, 'Classe et trimestre obligatoires.', [], 400);
        }

        $resultats = $this->db->fetchAll(
            "SELECT rt.*, e.matricule, e.nom, e.prenom, e.sexe,
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format
             FROM resultats_trimestriels rt
             INNER JOIN eleves e ON rt.eleve_id = e.id
             WHERE rt.classe_id = :cid AND rt.trimestre_id = :tid
             ORDER BY rt.rang ASC",
            ['cid' => $classeId, 'tid' => $trimestreId]
        );

        jsonResponse(true, 'Délibérations récupérées.', [
            'resultats' => $resultats
        ]);
    }

    /**
     * Sauvegarde une décision de délibération
     */
    public function sauvegarderDeliberation(): void
    {
        requirePermission('resultats.modifier');

        $resultatId   = (int) post('resultat_id', 0);
        $decision     = post('decision', '');
        $appreciation = post('appreciation', '');
        $decisionFinale = (int) post('decision_finale', 0);

        if ($resultatId <= 0) jsonResponse(false, 'ID résultat invalide.', [], 400);

        $this->db->update(
            "UPDATE resultats_trimestriels 
             SET decision = :decision, appreciation_conseil = :appreciation, decision_finale = :finale
             WHERE id = :id",
            [
                'decision'     => $decision,
                'appreciation' => $appreciation,
                'finale'       => $decisionFinale,
                'id'           => $resultatId
            ]
        );

        jsonResponse(true, 'Délibération sauvegardée.');
    }

    // ============================================================
    // 6. MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Calcule les rangs pour une classe et un trimestre
     */
    private function calculerRangs(int $classeId, int $trimestreId): void
    {
        $resultats = $this->db->fetchAll(
            "SELECT id, moyenne_generale FROM resultats_trimestriels 
             WHERE classe_id = :cid AND trimestre_id = :tid 
             ORDER BY moyenne_generale DESC",
            ['cid' => $classeId, 'tid' => $trimestreId]
        );

        $effectif = count($resultats);

        foreach ($resultats as $index => $r) {
            $this->db->update(
                "UPDATE resultats_trimestriels SET rang = :rang, effectif_classe = :eff WHERE id = :id",
                ['rang' => $index + 1, 'eff' => $effectif, 'id' => $r['id']]
            );
        }
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

    /**
     * Récupère les rangs d'un élève
     */
    private function getRangsEleve(int $eleveId, int $anneeId): array
    {
        $rangs = [];
        $trimestres = $this->db->fetchAll(
            "SELECT id, numero, libelle FROM trimestres WHERE annee_scolaire_id = :aid ORDER BY numero",
            ['aid' => $anneeId]
        );

        foreach ($trimestres as $trimestre) {
            $resultat = $this->db->fetchOne(
                "SELECT rang, effectif_classe, moyenne_generale, mention, decision
                 FROM resultats_trimestriels 
                 WHERE eleve_id = :eid AND trimestre_id = :tid",
                ['eid' => $eleveId, 'tid' => $trimestre['id']]
            );

            $rangs['T' . $trimestre['numero']] = $resultat ?: null;
        }

        return $rangs;
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
 * Calcule les pourcentages détaillés pour le bulletin
 * Retourne : %P1, %P2, %Ex, %Trimestre, %Périodes, %Examens, %Annuel
 */
public function getResultatsCompletsEleve(int $eleveId, int $anneeId): array
{
    // Récupérer les infos de l'élève
    $eleve = $this->db->fetchOne(
        "SELECT e.*, c.nom AS classe_nom, c.niveau AS classe_niveau, c.id AS classe_id
         FROM eleves e
         INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
         INNER JOIN classes c ON i.classe_id = c.id
         WHERE e.id = :eid AND i.annee_scolaire_id = :aid",
        ['eid' => $eleveId, 'aid' => $anneeId]
    );

    if (!$eleve) return [];

    $groupes = $this->db->fetchAll("SELECT * FROM matieres WHERE est_groupe = 1 AND est_actif = 1 ORDER BY id");

    $resultats = [];
    $totaux = [
        'T1' => ['p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0], 'total' => ['max' => 0, 'obt' => 0]],
        'T2' => ['p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0], 'total' => ['max' => 0, 'obt' => 0]],
        'T3' => ['p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0], 'total' => ['max' => 0, 'obt' => 0]],
        'annuel' => ['max' => 0, 'obt' => 0],
        'periodes' => ['max' => 0, 'obt' => 0],
        'examens' => ['max' => 0, 'obt' => 0]
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
                'T1' => ['p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0], 'total' => ['max' => 0, 'obt' => 0]],
                'T2' => ['p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0], 'total' => ['max' => 0, 'obt' => 0]],
                'T3' => ['p1' => ['max' => 0, 'obt' => 0], 'p2' => ['max' => 0, 'obt' => 0], 'ex' => ['max' => 0, 'obt' => 0], 'total' => ['max' => 0, 'obt' => 0]],
                'annuel' => ['max' => 0, 'obt' => 0]
            ]
        ];

        foreach ($sousMatieres as $sm) {
            $maxPeriode = (int) $sm['maxima_periode'];
            $maxExamen  = $maxPeriode * 2;
            $maxTrimestre = ($maxPeriode * 2) + $maxExamen;

            $t1 = $this->calculerNotesTrimestre($eleveId, $sm['id'], 1, $maxPeriode);
            $t2 = $this->calculerNotesTrimestre($eleveId, $sm['id'], 2, $maxPeriode);
            $t3 = $this->calculerNotesTrimestre($eleveId, $sm['id'], 3, $maxPeriode);

            $smData = [
                'libelle'       => $sm['libelle'],
                'code'          => $sm['code'],
                'max_periode'   => $maxPeriode,
                'max_examen'    => $maxExamen,
                'max_trimestre' => $maxTrimestre,
                'max_annuel'    => $maxTrimestre * 3,
                'T1' => $t1,
                'T2' => $t2,
                'T3' => $t3,
                'annuel' => ['max' => $maxTrimestre * 3, 'obt' => $t1['total_obt'] + $t2['total_obt'] + $t3['total_obt']]
            ];

            // Ajouter aux sous-totaux du groupe
            foreach (['T1', 'T2', 'T3'] as $trimKey) {
                $groupeData['sous_total'][$trimKey]['p1']['max'] += $smData[$trimKey]['p1']['max'];
                $groupeData['sous_total'][$trimKey]['p1']['obt'] += $smData[$trimKey]['p1']['obt'];
                $groupeData['sous_total'][$trimKey]['p2']['max'] += $smData[$trimKey]['p2']['max'];
                $groupeData['sous_total'][$trimKey]['p2']['obt'] += $smData[$trimKey]['p2']['obt'];
                $groupeData['sous_total'][$trimKey]['ex']['max'] += $smData[$trimKey]['ex']['max'];
                $groupeData['sous_total'][$trimKey]['ex']['obt'] += $smData[$trimKey]['ex']['obt'];
                $groupeData['sous_total'][$trimKey]['total']['max'] += $smData[$trimKey]['total_max'];
                $groupeData['sous_total'][$trimKey]['total']['obt'] += $smData[$trimKey]['total_obt'];

                $totaux[$trimKey]['p1']['max'] += $smData[$trimKey]['p1']['max'];
                $totaux[$trimKey]['p1']['obt'] += $smData[$trimKey]['p1']['obt'];
                $totaux[$trimKey]['p2']['max'] += $smData[$trimKey]['p2']['max'];
                $totaux[$trimKey]['p2']['obt'] += $smData[$trimKey]['p2']['obt'];
                $totaux[$trimKey]['ex']['max'] += $smData[$trimKey]['ex']['max'];
                $totaux[$trimKey]['ex']['obt'] += $smData[$trimKey]['ex']['obt'];
                $totaux[$trimKey]['total']['max'] += $smData[$trimKey]['total_max'];
                $totaux[$trimKey]['total']['obt'] += $smData[$trimKey]['total_obt'];

                // Accumuler périodes et examens
                $totaux['periodes']['max'] += $smData[$trimKey]['p1']['max'] + $smData[$trimKey]['p2']['max'];
                $totaux['periodes']['obt'] += $smData[$trimKey]['p1']['obt'] + $smData[$trimKey]['p2']['obt'];
                $totaux['examens']['max'] += $smData[$trimKey]['ex']['max'];
                $totaux['examens']['obt'] += $smData[$trimKey]['ex']['obt'];
            }

            $groupeData['sous_total']['annuel']['max'] += $smData['max_annuel'];
            $groupeData['sous_total']['annuel']['obt'] += $smData['annuel']['obt'];
            $totaux['annuel']['max'] += $smData['max_annuel'];
            $totaux['annuel']['obt'] += $smData['annuel']['obt'];

            $groupeData['sous_matieres'][] = $smData;
        }

        $resultats[] = $groupeData;
    }

    // Calculer tous les pourcentages
    $pourcentages = [
        'T1' => [
            'p1' => $totaux['T1']['p1']['max'] > 0 ? round(($totaux['T1']['p1']['obt'] / $totaux['T1']['p1']['max']) * 100, 1) : 0,
            'p2' => $totaux['T1']['p2']['max'] > 0 ? round(($totaux['T1']['p2']['obt'] / $totaux['T1']['p2']['max']) * 100, 1) : 0,
            'ex' => $totaux['T1']['ex']['max'] > 0 ? round(($totaux['T1']['ex']['obt'] / $totaux['T1']['ex']['max']) * 100, 1) : 0,
            'trimestre' => $totaux['T1']['total']['max'] > 0 ? round(($totaux['T1']['total']['obt'] / $totaux['T1']['total']['max']) * 100, 1) : 0,
            'mention' => $this->determinerMention($totaux['T1']['total']['max'] > 0 ? round(($totaux['T1']['total']['obt'] / $totaux['T1']['total']['max']) * 100, 1) : 0),
            'decision' => $this->determinerDecision($totaux['T1']['total']['max'] > 0 ? round(($totaux['T1']['total']['obt'] / $totaux['T1']['total']['max']) * 100, 1) : 0)
        ],
        'T2' => [
            'p1' => $totaux['T2']['p1']['max'] > 0 ? round(($totaux['T2']['p1']['obt'] / $totaux['T2']['p1']['max']) * 100, 1) : 0,
            'p2' => $totaux['T2']['p2']['max'] > 0 ? round(($totaux['T2']['p2']['obt'] / $totaux['T2']['p2']['max']) * 100, 1) : 0,
            'ex' => $totaux['T2']['ex']['max'] > 0 ? round(($totaux['T2']['ex']['obt'] / $totaux['T2']['ex']['max']) * 100, 1) : 0,
            'trimestre' => $totaux['T2']['total']['max'] > 0 ? round(($totaux['T2']['total']['obt'] / $totaux['T2']['total']['max']) * 100, 1) : 0,
            'mention' => $this->determinerMention($totaux['T2']['total']['max'] > 0 ? round(($totaux['T2']['total']['obt'] / $totaux['T2']['total']['max']) * 100, 1) : 0),
            'decision' => $this->determinerDecision($totaux['T2']['total']['max'] > 0 ? round(($totaux['T2']['total']['obt'] / $totaux['T2']['total']['max']) * 100, 1) : 0)
        ],
        'T3' => [
            'p1' => $totaux['T3']['p1']['max'] > 0 ? round(($totaux['T3']['p1']['obt'] / $totaux['T3']['p1']['max']) * 100, 1) : 0,
            'p2' => $totaux['T3']['p2']['max'] > 0 ? round(($totaux['T3']['p2']['obt'] / $totaux['T3']['p2']['max']) * 100, 1) : 0,
            'ex' => $totaux['T3']['ex']['max'] > 0 ? round(($totaux['T3']['ex']['obt'] / $totaux['T3']['ex']['max']) * 100, 1) : 0,
            'trimestre' => $totaux['T3']['total']['max'] > 0 ? round(($totaux['T3']['total']['obt'] / $totaux['T3']['total']['max']) * 100, 1) : 0,
            'mention' => $this->determinerMention($totaux['T3']['total']['max'] > 0 ? round(($totaux['T3']['total']['obt'] / $totaux['T3']['total']['max']) * 100, 1) : 0),
            'decision' => $this->determinerDecision($totaux['T3']['total']['max'] > 0 ? round(($totaux['T3']['total']['obt'] / $totaux['T3']['total']['max']) * 100, 1) : 0)
        ],
        'periodes' => $totaux['periodes']['max'] > 0 ? round(($totaux['periodes']['obt'] / $totaux['periodes']['max']) * 100, 1) : 0,
        'examens'  => $totaux['examens']['max'] > 0 ? round(($totaux['examens']['obt'] / $totaux['examens']['max']) * 100, 1) : 0,
        'annuel'   => [
            'pct'      => $totaux['annuel']['max'] > 0 ? round(($totaux['annuel']['obt'] / $totaux['annuel']['max']) * 100, 1) : 0,
            'mention'  => $this->determinerMention($totaux['annuel']['max'] > 0 ? round(($totaux['annuel']['obt'] / $totaux['annuel']['max']) * 100, 1) : 0),
            'decision' => $this->determinerDecision($totaux['annuel']['max'] > 0 ? round(($totaux['annuel']['obt'] / $totaux['annuel']['max']) * 100, 1) : 0)
        ]
    ];

    // Récupérer les rangs
    $rangs = [];
    for ($i = 1; $i <= 3; $i++) {
        $r = $this->db->fetchOne(
            "SELECT rang, effectif_classe FROM resultats_trimestriels 
             WHERE eleve_id = :eid AND trimestre_id = :tid",
            ['eid' => $eleveId, 'tid' => $i]
        );
        $rangs['T' . $i] = $r ?: null;
    }

    return [
        'eleve'        => $eleve,
        'groupes'      => $resultats,
        'totaux'       => $totaux,
        'pourcentages' => $pourcentages,
        'rangs'        => $rangs
    ];
}





/**
 * Calcule les résultats pour une période spécifique (P1 à P6, Examen, Trimestre)
 * 
 * @param int $classeId    ID de la classe
 * @param int $trimestreId ID du trimestre (1, 2, 3)
 * @param string $type     Type : 'Période 1', 'Période 2', 'Période 3', 'Période 4', 'Période 5', 'Période 6', 'Examen', 'Trimestre'
 * @return array
 */
/**
 * Calcule les résultats pour une période spécifique
 */
public function getResultatsPeriode(int $classeId, int $trimestreId, string $type): array
{
    $trimestreNum = (int) $this->db->fetchColumn("SELECT numero FROM trimestres WHERE id = :id", ['id' => $trimestreId]);

    // Mapping : type sélecteur → type_evaluation dans la base + trimestre_id
    $mapping = [
        'Période 1' => ['trimestre' => 1, 'type_eval' => 'Période 1'],
        'Période 2' => ['trimestre' => 1, 'type_eval' => 'Période 2'],
        'Période 3' => ['trimestre' => 2, 'type_eval' => 'Période 1'],
        'Période 4' => ['trimestre' => 2, 'type_eval' => 'Période 2'],
        'Période 5' => ['trimestre' => 3, 'type_eval' => 'Période 1'],
        'Période 6' => ['trimestre' => 3, 'type_eval' => 'Période 2'],
        'Examen'    => ['trimestre' => $trimestreNum, 'type_eval' => 'Examen'],
        'Trimestre' => ['trimestre' => $trimestreNum, 'type_eval' => 'Trimestre'],
    ];

    $map = $mapping[$type] ?? $mapping['Période 1'];
    $trimestreCible = $map['trimestre'];
    $typeEvalCible  = $map['type_eval'];

    // Récupérer l'ID du trimestre cible
    $trimestreCibleId = (int) $this->db->fetchColumn(
        "SELECT id FROM trimestres WHERE annee_scolaire_id = (SELECT annee_scolaire_id FROM trimestres WHERE id = :tid) AND numero = :num",
        ['tid' => $trimestreId, 'num' => $trimestreCible]
    );

    $eleves = $this->db->fetchAll(
        "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe
         FROM inscriptions i
         INNER JOIN eleves e ON i.eleve_id = e.id
         WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1
         ORDER BY e.nom, e.prenom",
        ['cid' => $classeId]
    );

    if (empty($eleves)) return [];

    $sousMatieres = $this->db->fetchAll(
        "SELECT m.id, m.libelle, m.code,
                COALESCE(cm.maxima_override, m.maxima) AS maxima_periode
         FROM matieres m
         INNER JOIN classe_matieres cm ON m.id = cm.matiere_id AND cm.classe_id = :cid AND cm.est_active = 1
         WHERE m.est_groupe = 0 AND m.est_actif = 1
         ORDER BY m.parent_id, m.id",
        ['cid' => $classeId]
    );

    $resultats = [];
    $totalGarcons = 0;
    $totalFilles = 0;
    $totalAdmis = 0;
    $totalGarconsAdmis = 0;
    $totalFillesAdmises = 0;

    foreach ($eleves as $eleve) {
        $totalMax = 0;
        $totalObt = 0;

        foreach ($sousMatieres as $sm) {
            $maxPeriode = (int) $sm['maxima_periode'];

            if ($typeEvalCible === 'Trimestre') {
                $max = $maxPeriode * 4;
                $note = 0;
                foreach (['Période 1', 'Période 2', 'Examen'] as $te) {
                    $note += (float) $this->db->fetchColumn(
                        "SELECT COALESCE(n.valeur, 0) FROM notes n
                         INNER JOIN evaluations ev ON n.evaluation_id = ev.id
                         WHERE n.eleve_id = :eid AND n.sous_matiere_id = :smid
                         AND ev.trimestre_id = :tid AND ev.type_evaluation = :te
                         AND n.absence = 0 LIMIT 1",
                        ['eid' => $eleve['id'], 'smid' => $sm['id'], 'tid' => $trimestreCibleId, 'te' => $te]
                    );
                }
            } elseif ($typeEvalCible === 'Examen') {
                $max = $maxPeriode * 2;
                $note = (float) $this->db->fetchColumn(
                    "SELECT COALESCE(n.valeur, 0) FROM notes n
                     INNER JOIN evaluations ev ON n.evaluation_id = ev.id
                     WHERE n.eleve_id = :eid AND n.sous_matiere_id = :smid
                     AND ev.trimestre_id = :tid AND ev.type_evaluation = :te
                     AND n.absence = 0 LIMIT 1",
                    ['eid' => $eleve['id'], 'smid' => $sm['id'], 'tid' => $trimestreCibleId, 'te' => $typeEvalCible]
                );
            } else {
                $max = $maxPeriode;
                $note = (float) $this->db->fetchColumn(
                    "SELECT COALESCE(n.valeur, 0) FROM notes n
                     INNER JOIN evaluations ev ON n.evaluation_id = ev.id
                     WHERE n.eleve_id = :eid AND n.sous_matiere_id = :smid
                     AND ev.trimestre_id = :tid AND ev.type_evaluation = :te
                     AND n.absence = 0 LIMIT 1",
                    ['eid' => $eleve['id'], 'smid' => $sm['id'], 'tid' => $trimestreCibleId, 'te' => $typeEvalCible]
                );
            }

            $totalMax += $max;
            $totalObt += $note;
        }

        $pourcentage = $totalMax > 0 ? round(($totalObt / $totalMax) * 100, 1) : 0;
        $mention = $this->determinerMention($pourcentage);
        $decision = $this->determinerDecision($pourcentage);
        $isAdmis = $decision === 'Admis';

        if ($eleve['sexe'] === 'M') $totalGarcons++;
        else $totalFilles++;

        if ($isAdmis) {
            $totalAdmis++;
            if ($eleve['sexe'] === 'M') $totalGarconsAdmis++;
            else $totalFillesAdmises++;
        }

        $resultats[] = [
            'eleve_id'    => $eleve['id'],
            'matricule'   => $eleve['matricule'],
            'nom'         => $eleve['nom'],
            'prenom'      => $eleve['prenom'],
            'sexe'        => $eleve['sexe'],
            'total_max'   => $totalMax,
            'total_obt'   => $totalObt,
            'pourcentage' => $pourcentage,
            'mention'     => $mention,
            'decision'    => $decision,
            'is_admis'    => $isAdmis
        ];
    }

    usort($resultats, function($a, $b) {
        return $b['pourcentage'] <=> $a['pourcentage'];
    });

    $effectif = count($resultats);
    foreach ($resultats as $index => &$r) {
        $r['rang'] = $index + 1;
        $r['effectif'] = $effectif;
    }
    unset($r);

    $totalEchecs = $effectif - $totalAdmis;
    $tauxReussite = $effectif > 0 ? round(($totalAdmis / $effectif) * 100, 1) : 0;
    $moyennes = array_column($resultats, 'pourcentage');
    $moyenneClasse = count($moyennes) > 0 ? round(array_sum($moyennes) / count($moyennes), 1) : 0;

    return [
        'resultats'        => $resultats,
        'effectif'         => $effectif,
        'total_garcons'    => $totalGarcons,
        'total_filles'     => $totalFilles,
        'total_admis'      => $totalAdmis,
        'garcons_admis'    => $totalGarconsAdmis,
        'filles_admises'   => $totalFillesAdmises,
        'total_echecs'     => $totalEchecs,
        'garcons_echecs'   => $totalGarcons - $totalGarconsAdmis,
        'filles_echecs'    => $totalFilles - $totalFillesAdmises,
        'taux_reussite'    => $tauxReussite,
        'moyenne_classe'   => $moyenneClasse
    ];
}
}