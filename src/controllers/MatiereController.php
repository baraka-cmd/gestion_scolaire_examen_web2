<?php
/**
 * Fichier : src/controllers/MatiereController.php
 * Rôle    : Gestion complète des matières, groupes, sous-matières et maxima
 * Système : Bulletin congolais — Groupes → Sous-matières → Maxima par période
 */

defined('ACCESS') or die('Accès interdit');

class MatiereController
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
        if (!can('matieres.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'liste':
                $this->getListe();
                break;
            case 'getMatiere':
                $this->getMatiere();
                break;
            case 'creer':
                $this->creer();
                break;
            case 'modifier':
                $this->modifier();
                break;
            case 'supprimer':
                $this->supprimer();
                break;
            case 'getSousMatieres':
                $this->getSousMatieres();
                break;
            case 'getClasses':
                $this->getClasses();
                break;
            case 'associerClasse':
                $this->associerClasse();
                break;
            case 'dissocierClasse':
                $this->dissocierClasse();
                break;
            case 'updateMaxima':
                $this->updateMaxima();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // LISTE HIÉRARCHIQUE : GROUPES → SOUS-MATIÈRES
    // ============================================================

    /**
     * Liste paginée : d'abord les groupes, puis leurs sous-matières
     */
    private function getListe(): void
    {
        $page       = max(1, (int) post('page', 1));
        $recherche  = post('recherche', '');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if (!empty($recherche)) {
            $where[] = "(m.libelle LIKE :r1 OR m.code LIKE :r2 OR m.description LIKE :r3)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Compter uniquement les groupes (est_groupe = 1)
        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM matieres m WHERE m.est_groupe = 1 AND m.est_actif = 1 {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;

        // Récupérer les groupes
        $groupes = $this->db->fetchAll(
            "SELECT m.*,
                    (SELECT COUNT(*) FROM matieres sm WHERE sm.parent_id = m.id AND sm.est_actif = 1) AS nb_sous_matieres,
                    (SELECT COUNT(*) FROM classe_matieres cm 
                     INNER JOIN matieres sm ON cm.matiere_id = sm.id 
                     WHERE sm.parent_id = m.id AND cm.est_active = 1) AS nb_associations
             FROM matieres m
             WHERE m.est_groupe = 1 AND m.est_actif = 1
             {$whereClause}
             ORDER BY m.id
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        // Pour chaque groupe, récupérer ses sous-matières
        foreach ($groupes as &$groupe) {
            $groupe['sous_matieres'] = $this->db->fetchAll(
                "SELECT m.*,
                        (SELECT COUNT(*) FROM classe_matieres cm WHERE cm.matiere_id = m.id AND cm.est_active = 1) AS nb_classes
                 FROM matieres m
                 WHERE m.parent_id = :pid AND m.est_groupe = 0 AND m.est_actif = 1
                 ORDER BY m.id",
                ['pid' => $groupe['id']]
            );

            // Pour chaque sous-matière, récupérer les classes associées
            foreach ($groupe['sous_matieres'] as &$sm) {
                $sm['classes'] = $this->db->fetchAll(
                    "SELECT c.nom, c.niveau, COALESCE(cm.maxima_override, m.maxima) AS maxima_final
                     FROM classe_matieres cm
                     INNER JOIN classes c ON cm.classe_id = c.id
                     INNER JOIN matieres m ON cm.matiere_id = m.id
                     WHERE cm.matiere_id = :mid AND cm.est_active = 1
                     AND c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
                     ORDER BY c.niveau",
                    ['mid' => $sm['id']]
                );
            }
            unset($sm);
        }
        unset($groupe);

        jsonResponse(true, 'Liste récupérée.', [
            'groupes' => $groupes,
            'total'   => $total,
            'page'    => $page,
            'pages'   => ceil($total / $parPage)
        ]);
    }

    // ============================================================
    // RÉCUPÉRATION D'UNE MATIÈRE (GROUPE OU SOUS-MATIÈRE)
    // ============================================================

    private function getMatiere(): void
    {
        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $matiere = $this->db->fetchOne(
            "SELECT m.*, p.libelle AS parent_libelle
             FROM matieres m
             LEFT JOIN matieres p ON m.parent_id = p.id
             WHERE m.id = :id",
            ['id' => $id]
        );

        if (!$matiere) jsonResponse(false, 'Matière introuvable.', [], 404);

        // Si c'est un groupe, récupérer ses sous-matières
        $sousMatieres = [];
        if ($matiere['est_groupe']) {
            $sousMatieres = $this->db->fetchAll(
                "SELECT * FROM matieres WHERE parent_id = :pid AND est_actif = 1 ORDER BY id",
                ['pid' => $id]
            );
        }

        // Récupérer les groupes disponibles (pour le parent)
        $groupes = $this->db->fetchAll(
            "SELECT id, code, libelle FROM matieres WHERE est_groupe = 1 AND est_actif = 1 ORDER BY libelle"
        );

        jsonResponse(true, 'Matière récupérée.', [
            'matiere'       => $matiere,
            'sous_matieres' => $sousMatieres,
            'groupes'       => $groupes
        ]);
    }

    // ============================================================
    // RÉCUPÉRATION DES SOUS-MATIÈRES D'UN GROUPE
    // ============================================================

    private function getSousMatieres(): void
    {
        $groupeId = (int) post('groupe_id', 0);

        if ($groupeId > 0) {
            $sousMatieres = $this->db->fetchAll(
                "SELECT * FROM matieres WHERE parent_id = :pid AND est_groupe = 0 AND est_actif = 1 ORDER BY id",
                ['pid' => $groupeId]
            );
        } else {
            $sousMatieres = $this->db->fetchAll(
                "SELECT m.*, p.libelle AS groupe_libelle
                 FROM matieres m
                 INNER JOIN matieres p ON m.parent_id = p.id
                 WHERE m.est_groupe = 0 AND m.est_actif = 1
                 ORDER BY p.libelle, m.id"
            );
        }

        jsonResponse(true, 'Sous-matières récupérées.', ['sous_matieres' => $sousMatieres]);
    }

    // ============================================================
    // CRÉATION D'UNE MATIÈRE (GROUPE OU SOUS-MATIÈRE)
    // ============================================================

    private function creer(): void
{
    requirePermission('matieres.creer');

    $code        = post('code', '');
    $libelle     = post('libelle', '');
    $description = post('description', '');
    $maxima      = (int) post('maxima', 20);
    $estGroupe   = (int) post('est_groupe', 0);
    $parentId    = (int) post('parent_id', 0);

    $errors = [];

    if (isBlank($libelle)) {
        $errors['libelle'] = 'Le libellé est obligatoire.';
    } elseif (mb_strlen($libelle) < 2) {
        $errors['libelle'] = 'Minimum 2 caractères.';
    } elseif (mb_strlen($libelle) > 150) {
        $errors['libelle'] = 'Maximum 150 caractères.';
    } else {
        $existe = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM matieres WHERE libelle = :libelle AND est_actif = 1",
            ['libelle' => trim($libelle)]
        );
        if ($existe > 0) $errors['libelle'] = 'Cette matière existe déjà.';
    }

    if (!isBlank($code)) {
        if (mb_strlen($code) > 20) {
            $errors['code'] = 'Maximum 20 caractères.';
        } else {
            $codeExiste = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM matieres WHERE code = :code",
                ['code' => mb_strtoupper(trim($code))]
            );
            if ($codeExiste > 0) $errors['code'] = 'Ce code est déjà utilisé.';
        }
    }

    // Validation du maxima UNIQUEMENT pour les sous-matières
    if (!$estGroupe) {
        if ($maxima < 1 || $maxima > 1000) {
            $errors['maxima'] = 'Le maxima doit être entre 1 et 1000.';
        }
    }

    // Si sous-matière, vérifier le parent
    if (!$estGroupe && $parentId <= 0) {
        $errors['parent_id'] = 'Veuillez sélectionner le groupe parent.';
    } elseif (!$estGroupe && $parentId > 0) {
        $parentExiste = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM matieres WHERE id = :id AND est_groupe = 1",
            ['id' => $parentId]
        );
        if ($parentExiste == 0) $errors['parent_id'] = 'Groupe parent invalide.';
    }

    if (!empty($errors)) {
        jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
    }

    try {
        $this->db->beginTransaction();

        // Si groupe, maxima = NULL. Si sous-matière, maxima = valeur saisie
        $maximaValue = $estGroupe ? null : $maxima;

        $matiereId = $this->db->insert(
            "INSERT INTO matieres (code, libelle, description, maxima, coefficient, est_groupe, parent_id, est_actif)
             VALUES (:code, :libelle, :desc, :maxima, 1, :est_groupe, :parent_id, 1)",
            [
                'code'       => !empty($code) ? mb_strtoupper(trim($code)) : null,
                'libelle'    => formatNom($libelle),
                'desc'       => $description ?: null,
                'maxima'     => $maximaValue,
                'est_groupe' => $estGroupe,
                'parent_id'  => $estGroupe ? null : ($parentId > 0 ? $parentId : null)
            ]
        );

        // Si c'est une sous-matière, associer aux classes sélectionnées
        if (!$estGroupe && !empty($_POST['classes'])) {
            foreach ($_POST['classes'] as $classeId) {
                $classeId = (int) $classeId;
                if ($classeId > 0) {
                    $existe = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM classe_matieres WHERE matiere_id = :mid AND classe_id = :cid",
                        ['mid' => $matiereId, 'cid' => $classeId]
                    );
                    if ($existe == 0) {
                        $this->db->query(
                            "INSERT INTO classe_matieres (matiere_id, classe_id, maxima_override, est_active) 
                             VALUES (:mid, :cid, NULL, 1)",
                            ['mid' => $matiereId, 'cid' => $classeId]
                        );
                    }
                }
            }
        }

        $this->db->commit();

        logActivity('Création', 'matieres', $matiereId, "Matière créée : {$libelle}");

        $typeMsg = $estGroupe ? 'Groupe' : 'Sous-matière';
        jsonResponse(true, "{$typeMsg} créé(e) avec succès.", ['id' => $matiereId]);

    } catch (Exception $e) {
        $this->db->rollback();
        error_log('Erreur création matière: ' . $e->getMessage());
        jsonResponse(false, 'Erreur lors de la création.');
    }
}


    // ============================================================
    // MODIFICATION D'UNE MATIÈRE
    // ============================================================

    private function modifier(): void
    {
        requirePermission('matieres.modifier');

        $id          = (int) post('id', 0);
        $code        = post('code', '');
        $libelle     = post('libelle', '');
        $description = post('description', '');
        $maxima      = (int) post('maxima', 20);
        $parentId    = (int) post('parent_id', 0);

        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $existant = $this->db->fetchOne("SELECT * FROM matieres WHERE id = :id", ['id' => $id]);
        if (!$existant) jsonResponse(false, 'Matière introuvable.', [], 404);

        $errors = [];

        if (isBlank($libelle)) $errors['libelle'] = 'Le libellé est obligatoire.';
        elseif (mb_strlen($libelle) < 2) $errors['libelle'] = 'Minimum 2 caractères.';
        else {
            $existe = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM matieres WHERE libelle = :libelle AND id != :id AND est_actif = 1",
                ['libelle' => trim($libelle), 'id' => $id]
            );
            if ($existe > 0) $errors['libelle'] = 'Cette matière existe déjà.';
        }

        if (!isBlank($code)) {
            $codeExiste = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM matieres WHERE code = :code AND id != :id",
                ['code' => mb_strtoupper(trim($code)), 'id' => $id]
            );
            if ($codeExiste > 0) $errors['code'] = 'Ce code est déjà utilisé.';
        }

        if ($maxima < 1 || $maxima > 1000) {
            $errors['maxima'] = 'Le maxima doit être entre 1 et 1000.';
        }

        // Ne pas permettre de changer est_groupe
        // Si c'est une sous-matière, vérifier le parent
        if (!$existant['est_groupe'] && $parentId > 0) {
            $parentExiste = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM matieres WHERE id = :id AND est_groupe = 1",
                ['id' => $parentId]
            );
            if ($parentExiste == 0) $errors['parent_id'] = 'Groupe parent invalide.';
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $updateData = [
                'code'       => !empty($code) ? mb_strtoupper(trim($code)) : null,
                'libelle'    => formatNom($libelle),
                'desc'       => $description ?: null,
                'maxima'     => $maxima,
                'id'         => $id
            ];

            $sql = "UPDATE matieres 
                    SET code = :code, libelle = :libelle, description = :desc, maxima = :maxima";

            // Ne changer le parent que pour les sous-matières
            if (!$existant['est_groupe'] && $parentId > 0) {
                $sql .= ", parent_id = :parent_id";
                $updateData['parent_id'] = $parentId;
            }

            $sql .= " WHERE id = :id";

            $this->db->update($sql, $updateData);

            logActivity('Modification', 'matieres', $id, "Matière modifiée : {$libelle}");

            jsonResponse(true, 'Matière modifiée avec succès.');

        } catch (Exception $e) {
            error_log('Erreur modification matière: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la modification.');
        }
    }

    // ============================================================
    // SUPPRESSION
    // ============================================================

    private function supprimer(): void
    {
        requirePermission('matieres.supprimer');

        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $matiere = $this->db->fetchOne("SELECT * FROM matieres WHERE id = :id", ['id' => $id]);
        if (!$matiere) jsonResponse(false, 'Matière introuvable.', [], 404);

        // Si c'est un groupe, vérifier qu'il n'a pas de sous-matières
        if ($matiere['est_groupe']) {
            $nbSousMatieres = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM matieres WHERE parent_id = :pid AND est_actif = 1",
                ['pid' => $id]
            );
            if ($nbSousMatieres > 0) {
                jsonResponse(false, "Impossible de supprimer ce groupe : {$nbSousMatieres} sous-matière(s) active(s).", [], 409);
            }
        }

        // Vérifier les évaluations
        $nbEvaluations = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM evaluations WHERE matiere_id = :mid",
            ['mid' => $id]
        );
        if ($nbEvaluations > 0) {
            jsonResponse(false, "Impossible de supprimer : {$nbEvaluations} évaluation(s) liée(s).", [], 409);
        }

        // Vérifier l'emploi du temps
        $nbEDT = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM emploi_du_temps WHERE matiere_id = :mid",
            ['mid' => $id]
        );
        if ($nbEDT > 0) {
            jsonResponse(false, "Impossible de supprimer : utilisée dans l'emploi du temps.", [], 409);
        }

        try {
            // Supprimer les associations
            $this->db->delete("DELETE FROM classe_matieres WHERE matiere_id = :mid", ['mid' => $id]);
            // Supprimer la matière
            $this->db->delete("DELETE FROM matieres WHERE id = :id", ['id' => $id]);

            logActivity('Suppression', 'matieres', $id, "Matière supprimée : {$matiere['libelle']}");

            jsonResponse(true, 'Matière supprimée avec succès.');

        } catch (Exception $e) {
            error_log('Erreur suppression matière: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la suppression.');
        }
    }

    // ============================================================
    // GESTION DES CLASSES (ASSOCIATION)
    // ============================================================

    private function getClasses(): void
    {
        $matiereId = (int) post('matiere_id', 0);

        $classes = $this->db->fetchAll(
            "SELECT c.id, c.nom, c.niveau, c.salle,
                    cm.maxima_override, cm.est_active,
                    COALESCE(cm.maxima_override, :max_defaut) AS maxima_final
             FROM classes c
             LEFT JOIN classe_matieres cm ON c.id = cm.classe_id AND cm.matiere_id = :mid
             CROSS JOIN (SELECT maxima FROM matieres WHERE id = :mid2) AS m
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau",
            ['mid' => $matiereId, 'mid2' => $matiereId, 'max_defaut' => 20]
        );

        $maximaGlobal = (int) $this->db->fetchColumn(
            "SELECT maxima FROM matieres WHERE id = :mid",
            ['mid' => $matiereId]
        );

        jsonResponse(true, 'Classes récupérées.', [
            'classes'       => $classes,
            'maxima_global' => $maximaGlobal
        ]);
    }

    private function associerClasse(): void
    {
        requirePermission('matieres.modifier');

        $matiereId = (int) post('matiere_id', 0);
        $classeId  = (int) post('classe_id', 0);
        $maxima    = post('maxima_override', null);

        if ($matiereId <= 0 || $classeId <= 0) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        $maximaValue = ($maxima !== '' && $maxima !== null) ? (int) $maxima : null;

        if ($maximaValue !== null && ($maximaValue < 1 || $maximaValue > 1000)) {
            jsonResponse(false, 'Maxima invalide (1-1000).', [], 422);
        }

        $existe = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM classe_matieres WHERE matiere_id = :mid AND classe_id = :cid",
            ['mid' => $matiereId, 'cid' => $classeId]
        );

        if ($existe > 0) {
            $this->db->update(
                "UPDATE classe_matieres SET est_active = 1, maxima_override = :max WHERE matiere_id = :mid AND classe_id = :cid",
                ['max' => $maximaValue, 'mid' => $matiereId, 'cid' => $classeId]
            );
        } else {
            $this->db->insert(
                "INSERT INTO classe_matieres (matiere_id, classe_id, maxima_override, est_active) VALUES (:mid, :cid, :max, 1)",
                ['mid' => $matiereId, 'cid' => $classeId, 'max' => $maximaValue]
            );
        }

        logActivity('Association', 'classe_matieres', null, "Matière {$matiereId} ↔ Classe {$classeId}");
        jsonResponse(true, 'Association réussie.');
    }

    private function dissocierClasse(): void
    {
        requirePermission('matieres.modifier');

        $matiereId = (int) post('matiere_id', 0);
        $classeId  = (int) post('classe_id', 0);

        if ($matiereId <= 0 || $classeId <= 0) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        // Vérifier les évaluations
        $nbEvaluations = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM evaluations WHERE matiere_id = :mid AND classe_id = :cid",
            ['mid' => $matiereId, 'cid' => $classeId]
        );

        if ($nbEvaluations > 0) {
            $this->db->update(
                "UPDATE classe_matieres SET est_active = 0 WHERE matiere_id = :mid AND classe_id = :cid",
                ['mid' => $matiereId, 'cid' => $classeId]
            );
            jsonResponse(true, 'Matière désactivée pour cette classe (évaluations existantes conservées).');
        } else {
            $this->db->delete(
                "DELETE FROM classe_matieres WHERE matiere_id = :mid AND classe_id = :cid",
                ['mid' => $matiereId, 'cid' => $classeId]
            );
            jsonResponse(true, 'Association supprimée.');
        }

        logActivity('Dissociation', 'classe_matieres', null, "Matière {$matiereId} ↮ Classe {$classeId}");
    }

    private function updateMaxima(): void
    {
        requirePermission('matieres.modifier');

        $matiereId = (int) post('matiere_id', 0);
        $classeId  = (int) post('classe_id', 0);
        $maxima    = post('maxima_override', null);

        if ($matiereId <= 0 || $classeId <= 0) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        $maximaValue = ($maxima !== '' && $maxima !== null) ? (int) $maxima : null;

        if ($maximaValue !== null && ($maximaValue < 1 || $maximaValue > 1000)) {
            jsonResponse(false, 'Maxima invalide (1-1000).', [], 422);
        }

        $this->db->update(
            "UPDATE classe_matieres SET maxima_override = :max WHERE matiere_id = :mid AND classe_id = :cid",
            ['max' => $maximaValue, 'mid' => $matiereId, 'cid' => $classeId]
        );

        jsonResponse(true, 'Maxima mis à jour.');
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère tous les groupes actifs
     */
    public function getGroupes(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM matieres WHERE est_groupe = 1 AND est_actif = 1 ORDER BY libelle"
        );
    }

    /**
     * Récupère les sous-matières d'un groupe
     */
    public function getSousMatieresByGroupe(int $groupeId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM matieres WHERE parent_id = :pid AND est_groupe = 0 AND est_actif = 1 ORDER BY id",
            ['pid' => $groupeId]
        );
    }

    /**
     * Récupère une matière par ID
     */
    public function getMatiereDetail(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT m.*, p.libelle AS parent_libelle
             FROM matieres m
             LEFT JOIN matieres p ON m.parent_id = p.id
             WHERE m.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Récupère les classes actives
     */
    public function getClassesActives(): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.nom, c.niveau, c.salle
             FROM classes c
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau"
        );
    }

    /**
     * Calcule le total des maxima pour une classe (1ère période)
     * Somme de tous les maxima de toutes les sous-matières actives
     */
    public function getTotalMaximaClasse(int $classeId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(COALESCE(cm.maxima_override, m.maxima)), 0)
             FROM classe_matieres cm
             INNER JOIN matieres m ON cm.matiere_id = m.id
             WHERE cm.classe_id = :cid AND cm.est_active = 1 AND m.est_actif = 1",
            ['cid' => $classeId]
        );
    }

    /**
     * Calcule la moyenne d'un élève pour une sous-matière sur un trimestre
     * Formule congolaise : Total = Note P1 + Note P2 + Note Examen
     * Pourcentage = (Total obtenu / Total maxima) × 100
     */
    public function calculerMoyenneSousMatiere(int $eleveId, int $matiereId, int $trimestreId): ?array
    {
        // Récupérer le maxima pour cette sous-matière
        $matiere = $this->db->fetchOne("SELECT maxima FROM matieres WHERE id = :id", ['id' => $matiereId]);
        if (!$matiere) return null;

        $maximaPeriode = (int) $matiere['maxima'];

        // Récupérer les notes
        $noteP1 = $this->db->fetchColumn(
            "SELECT n.valeur FROM notes n
             INNER JOIN evaluations ev ON n.evaluation_id = ev.id
             WHERE n.eleve_id = :eid AND ev.matiere_id = :mid 
             AND ev.trimestre_id = :tid AND ev.type_evaluation = 'Période 1'",
            ['eid' => $eleveId, 'mid' => $matiereId, 'tid' => $trimestreId]
        );

        $noteP2 = $this->db->fetchColumn(
            "SELECT n.valeur FROM notes n
             INNER JOIN evaluations ev ON n.evaluation_id = ev.id
             WHERE n.eleve_id = :eid AND ev.matiere_id = :mid 
             AND ev.trimestre_id = :tid AND ev.type_evaluation = 'Période 2'",
            ['eid' => $eleveId, 'mid' => $matiereId, 'tid' => $trimestreId]
        );

        $noteExamen = $this->db->fetchColumn(
            "SELECT n.valeur FROM notes n
             INNER JOIN evaluations ev ON n.evaluation_id = ev.id
             WHERE n.eleve_id = :eid AND ev.matiere_id = :mid 
             AND ev.trimestre_id = :tid AND ev.type_evaluation = 'Examen'",
            ['eid' => $eleveId, 'mid' => $matiereId, 'tid' => $trimestreId]
        );

        // Si aucune note, retourner null
        if ($noteP1 === null && $noteP2 === null && $noteExamen === null) {
            return null;
        }

        $totalObtenu = (float) ($noteP1 ?? 0) + (float) ($noteP2 ?? 0) + (float) ($noteExamen ?? 0);
        $totalMaxima = $maximaPeriode * 3; // P1 + P2 + Examen (chaque période a le même maxima)

        return [
            'total_obtenu' => round($totalObtenu, 2),
            'total_maxima' => $totalMaxima,
            'pourcentage'  => $totalMaxima > 0 ? round(($totalObtenu / $totalMaxima) * 100, 1) : 0
        ];
    }
}