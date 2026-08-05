<?php
/**
 * Fichier : src/controllers/DocumentController.php
 * Rôle    : Gestion complète des documents (upload, liste, téléchargement, suppression)
 * Système : École primaire congolaise — Gestion documentaire
 * Version : 1.1 — Corrigée pour la structure réelle de la table documents
 */

defined('ACCESS') or die('Accès interdit');

class DocumentController
{
    private Database $db;
    private string $uploadDir;
    private array $allowedExtensions;
    private int $maxFileSize;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->uploadDir = UPLOADS_PATH . 'documents' . DIRECTORY_SEPARATOR;
        $this->allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'ppt', 'pptx'];
        $this->maxFileSize = 10 * 1024 * 1024; // 10 Mo

        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Point d'entrée pour les requêtes AJAX
     */
    public function handleAjax(): void
    {
        if (!can('documents.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'getListe':
                $this->getListe();
                break;
            case 'upload':
                $this->upload();
                break;
            case 'supprimer':
                $this->supprimer();
                break;
            case 'getDocument':
                $this->getDocument();
                break;
            case 'toggleVisibilite':
                $this->toggleVisibilite();
                break;
            case 'getCategories':
                $this->getCategories();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. LISTE DES DOCUMENTS
    // ============================================================

    private function getListe(): void
    {
        $page       = max(1, (int) post('page', 1));
        $categorie  = post('categorie', '');
        $visibilite = post('visibilite', '');
        $recherche  = post('recherche', '');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        // Filtre par visibilité selon le rôle
        $userId = $_SESSION['utilisateur_id'];
        $isAdmin = hasRole(['Admin', 'Directeur']);

        if (!$isAdmin) {
            // Les non-admins ne voient que les documents publics
            $where[] = "d.est_public = 1";
        }

        if (!empty($categorie)) {
            $where[] = "d.type_document = :cat";
            $params['cat'] = $categorie;
        }

        if ($visibilite !== '' && $isAdmin) {
            $where[] = "d.est_public = :vis";
            $params['vis'] = (int) $visibilite;
        }

        if (!empty($recherche)) {
            $where[] = "(d.titre LIKE :r1 OR d.description LIKE :r2 OR d.nom_fichier LIKE :r3 OR d.extension LIKE :r4)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
            $params['r4'] = "%{$recherche}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM documents d {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;

        $documents = $this->db->fetchAll(
            "SELECT d.*, 
                    DATE_FORMAT(d.created_at, '%d/%m/%Y') AS date_format,
                    TIME_FORMAT(d.created_at, '%H:%i') AS heure_format,
                    CONCAT(u.prenom, ' ', u.nom) AS upload_par_nom,
                    ROUND(d.taille_fichier / 1024, 1) AS taille_ko,
                    CASE 
                        WHEN d.taille_fichier > 1048576 THEN CONCAT(ROUND(d.taille_fichier / 1048576, 1), ' Mo')
                        WHEN d.taille_fichier > 1024 THEN CONCAT(ROUND(d.taille_fichier / 1024, 1), ' Ko')
                        ELSE CONCAT(d.taille_fichier, ' o')
                    END AS taille_formatee,
                    d.extension AS type_fichier,
                    d.nom_fichier AS nom_original,
                    d.type_document AS categorie,
                    0 AS nb_telechargements
             FROM documents d
             LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
             {$whereClause}
             ORDER BY d.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        // Statistiques
        $totalFichiers = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM documents");
        $tailleTotale = (int) $this->db->fetchColumn("SELECT COALESCE(SUM(taille_fichier), 0) FROM documents");
        $nbParCategorie = $this->db->fetchAll(
            "SELECT type_document AS categorie, COUNT(*) AS nb FROM documents GROUP BY type_document ORDER BY nb DESC"
        );

        jsonResponse(true, 'Liste récupérée.', [
            'documents'      => $documents,
            'total'          => $total,
            'page'           => $page,
            'pages'          => ceil($total / $parPage),
            'stats'          => [
                'total_fichiers' => $totalFichiers,
                'taille_totale'  => $tailleTotale > 1048576 ? round($tailleTotale / 1048576, 1) . ' Mo' : round($tailleTotale / 1024, 1) . ' Ko',
                'par_categorie'  => $nbParCategorie
            ]
        ]);
    }

    // ============================================================
    // 2. UPLOAD DE FICHIER(S)
    // ============================================================

    private function upload(): void
    {
        requirePermission('documents.creer');

        // Vérifier si des fichiers ont été envoyés
        if (!isset($_FILES['fichiers']) || empty($_FILES['fichiers']['name'][0])) {
            jsonResponse(false, 'Aucun fichier sélectionné.', [], 400);
        }

        $categorie   = post('categorie', 'Autre');
        $description = post('description', '');
        $estPublic   = (int) post('est_public', 1);
        $userId      = $_SESSION['utilisateur_id'];

        $fichiers = $_FILES['fichiers'];
        $nbFichiers = count($fichiers['name']);
        $uploades = 0;
        $erreurs = [];
        $documentsUploades = [];

        // Valider la catégorie selon l'enum de la table
        $categoriesValides = ['Règlement', 'Programme', 'Circulaire', 'Cours', 'Administratif', 'Pédagogique', 'Financier', 'Communication', 'Autre'];
        if (!in_array($categorie, $categoriesValides)) {
            $categorie = 'Autre';
        }

        for ($i = 0; $i < $nbFichiers; $i++) {
            $nomOriginal = $fichiers['name'][$i];
            $taille      = $fichiers['size'][$i];
            $tmpName     = $fichiers['tmp_name'][$i];
            $error       = $fichiers['error'][$i];

            // Vérifier les erreurs PHP
            if ($error !== UPLOAD_ERR_OK) {
                $erreurs[] = "{$nomOriginal} : erreur lors de l'upload (code {$error}).";
                continue;
            }

            // Vérifier la taille
            if ($taille > $this->maxFileSize) {
                $erreurs[] = "{$nomOriginal} : fichier trop volumineux (max 10 Mo).";
                continue;
            }

            // Vérifier l'extension
            $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->allowedExtensions)) {
                $erreurs[] = "{$nomOriginal} : extension .{$extension} non autorisée.";
                continue;
            }

            // Vérifier le type MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            $mimesAutorises = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv',
                'text/plain',
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];

            if (!in_array($mimeType, $mimesAutorises)) {
                $erreurs[] = "{$nomOriginal} : type de fichier non autorisé.";
                continue;
            }

            // Générer un nom unique
            $nomBase = pathinfo($nomOriginal, PATHINFO_FILENAME);
            $nomBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nomBase);
            $nomBase = substr($nomBase, 0, 100);
            $nomUnique = $nomBase . '_' . date('Ymd_His') . '.' . $extension;

            // Vérifier que le nom n'existe pas déjà
            $compteur = 1;
            while (file_exists($this->uploadDir . $nomUnique)) {
                $nomUnique = $nomBase . '_' . date('Ymd_His') . '_' . $compteur . '.' . $extension;
                $compteur++;
            }
            $cheminDestination = $this->uploadDir . $nomUnique;

            // Déplacer le fichier
            if (!move_uploaded_file($tmpName, $cheminDestination)) {
                $erreurs[] = "{$nomOriginal} : impossible de sauvegarder le fichier.";
                continue;
            }

            // Enregistrer dans la base de données
            try {
                $docId = $this->db->insert(
                    "INSERT INTO documents (titre, description, type_document, nom_fichier, chemin_fichier, 
                     taille_fichier, extension, est_public, utilisateur_id, created_at)
                     VALUES (:titre, :desc, :type_doc, :nom_fichier, :chemin, :taille, :ext, :public, :uid, NOW())",
                    [
                        'titre'       => pathinfo($nomOriginal, PATHINFO_FILENAME),
                        'desc'        => $description ?: null,
                        'type_doc'    => $categorie,
                        'nom_fichier' => $nomOriginal,
                        'chemin'      => 'uploads/documents/' . $nomUnique,
                        'taille'      => $taille,
                        'ext'         => $extension,
                        'public'      => $estPublic,
                        'uid'         => $userId
                    ]
                );

                $documentsUploades[] = [
                    'id'           => $docId,
                    'nom_original' => $nomOriginal,
                    'taille'       => $taille > 1048576 ? round($taille / 1048576, 1) . ' Mo' : round($taille / 1024, 1) . ' Ko',
                    'extension'    => $extension
                ];

                $uploades++;

            } catch (Exception $e) {
                // Supprimer le fichier si l'insertion échoue
                if (file_exists($cheminDestination)) {
                    unlink($cheminDestination);
                }
                $erreurs[] = "{$nomOriginal} : erreur base de données.";
                error_log('Erreur upload document: ' . $e->getMessage());
            }
        }

        // Journaliser
        if ($uploades > 0) {
            logActivity('Upload', 'documents', null, "{$uploades} document(s) uploadé(s)");
        }

        // Réponse
        $message = "{$uploades} document(s) uploadé(s) avec succès.";
        if (!empty($erreurs)) {
            $message .= ' ' . count($erreurs) . ' erreur(s).';
        }

        jsonResponse(true, $message, [
            'uploades'   => $uploades,
            'erreurs'    => $erreurs,
            'documents'  => $documentsUploades
        ]);
    }

    // ============================================================
    // 3. SUPPRIMER UN DOCUMENT
    // ============================================================

    private function supprimer(): void
    {
        requirePermission('documents.supprimer');

        $docId = (int) post('document_id', 0);
        if ($docId <= 0) jsonResponse(false, 'ID document invalide.', [], 400);

        $document = $this->db->fetchOne(
            "SELECT id, nom_fichier, chemin_fichier FROM documents WHERE id = :id",
            ['id' => $docId]
        );

        if (!$document) jsonResponse(false, 'Document introuvable.', [], 404);

        // Supprimer le fichier physique
        $cheminPhysique = UPLOADS_PATH . $document['chemin_fichier'];
        if (file_exists($cheminPhysique)) {
            unlink($cheminPhysique);
        }

        // Supprimer de la base
        $this->db->delete("DELETE FROM documents WHERE id = :id", ['id' => $docId]);

        logActivity('Suppression', 'documents', $docId, "Document supprimé : {$document['nom_fichier']}");

        jsonResponse(true, 'Document supprimé avec succès.');
    }

    // ============================================================
    // 4. RÉCUPÉRER UN DOCUMENT (INFOS)
    // ============================================================

    private function getDocument(): void
    {
        $docId = (int) post('document_id', 0);
        if ($docId <= 0) jsonResponse(false, 'ID document invalide.', [], 400);

        $document = $this->db->fetchOne(
            "SELECT d.*, 
                    d.nom_fichier AS nom_original,
                    d.extension AS type_fichier,
                    d.type_document AS categorie,
                    DATE_FORMAT(d.created_at, '%d/%m/%Y à %H:%i') AS date_format,
                    CONCAT(u.prenom, ' ', u.nom) AS upload_par_nom,
                    CASE 
                        WHEN d.taille_fichier > 1048576 THEN CONCAT(ROUND(d.taille_fichier / 1048576, 1), ' Mo')
                        WHEN d.taille_fichier > 1024 THEN CONCAT(ROUND(d.taille_fichier / 1024, 1), ' Ko')
                        ELSE CONCAT(d.taille_fichier, ' o')
                    END AS taille_formatee,
                    0 AS nb_telechargements
             FROM documents d
             LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
             WHERE d.id = :id",
            ['id' => $docId]
        );

        if (!$document) jsonResponse(false, 'Document introuvable.', [], 404);

        jsonResponse(true, 'Document récupéré.', ['document' => $document]);
    }

    // ============================================================
    // 5. BASCULER VISIBILITÉ (PUBLIC / RESTREINT)
    // ============================================================

    private function toggleVisibilite(): void
    {
        requirePermission('documents.modifier');

        $docId = (int) post('document_id', 0);
        if ($docId <= 0) jsonResponse(false, 'ID document invalide.', [], 400);

        $document = $this->db->fetchOne(
            "SELECT id, est_public, nom_fichier FROM documents WHERE id = :id",
            ['id' => $docId]
        );

        if (!$document) jsonResponse(false, 'Document introuvable.', [], 404);

        $nouvelleVisibilite = $document['est_public'] ? 0 : 1;

        $this->db->update(
            "UPDATE documents SET est_public = :vis WHERE id = :id",
            ['vis' => $nouvelleVisibilite, 'id' => $docId]
        );

        $statut = $nouvelleVisibilite ? 'public' : 'restreint';
        logActivity('Modification', 'documents', $docId, "Visibilité changée : {$statut}");

        jsonResponse(true, "Document passé en {$statut}.", ['est_public' => $nouvelleVisibilite]);
    }

    // ============================================================
    // 6. LISTE DES CATÉGORIES
    // ============================================================

    private function getCategories(): void
    {
        $categories = [
            ['valeur' => 'Administratif', 'libelle' => '📁 Administratif', 'description' => 'Règlements, PV, listes officielles'],
            ['valeur' => 'Pédagogique',   'libelle' => '📚 Pédagogique',   'description' => 'Cours, exercices, évaluations'],
            ['valeur' => 'Financier',     'libelle' => '💰 Financier',     'description' => 'Reçus, factures, rapports financiers'],
            ['valeur' => 'Communication', 'libelle' => '📢 Communication', 'description' => 'Courriers, circulaires, notes aux parents'],
            ['valeur' => 'Autre',         'libelle' => '📎 Autre',         'description' => 'Documents divers']
        ];

        jsonResponse(true, 'Catégories récupérées.', ['categories' => $categories]);
    }

    // ============================================================
    // 7. MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère les documents récents (pour dashboard)
     */
    public function getDocumentsRecents(int $limite = 5): array
    {
        return $this->db->fetchAll(
            "SELECT d.*, 
                    d.nom_fichier AS nom_original,
                    d.extension AS type_fichier,
                    DATE_FORMAT(d.created_at, '%d/%m/%Y') AS date_format,
                    CONCAT(u.prenom, ' ', u.nom) AS upload_par_nom
             FROM documents d
             LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
             ORDER BY d.created_at DESC
             LIMIT :limite",
            ['limite' => $limite]
        );
    }

    /**
     * Récupère les statistiques des documents
     */
    public function getStatsDocuments(): array
    {
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM documents");
        $tailleTotale = (int) $this->db->fetchColumn("SELECT COALESCE(SUM(taille_fichier), 0) FROM documents");
        $nbPublics = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM documents WHERE est_public = 1");
        $nbCeMois = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM documents WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
        );

        return [
            'total'        => $total,
            'taille_totale'=> $tailleTotale > 1048576 ? round($tailleTotale / 1048576, 1) . ' Mo' : round($tailleTotale / 1024, 1) . ' Ko',
            'nb_publics'   => $nbPublics,
            'nb_ce_mois'   => $nbCeMois
        ];
    }

    /**
     * Télécharge un document (force le téléchargement)
     */
    public function telecharger(int $docId): void
    {
        $document = $this->db->fetchOne(
            "SELECT id, nom_fichier, chemin_fichier, est_public FROM documents WHERE id = :id",
            ['id' => $docId]
        );

        if (!$document) {
            setFlash('error', 'Document introuvable.');
            redirect('/gestion_scolaire/documents');
        }

        // Vérifier les droits d'accès
        if (!$document['est_public'] && !hasRole(['Admin', 'Directeur'])) {
            setFlash('error', 'Vous n\'avez pas accès à ce document.');
            redirect('/gestion_scolaire/documents');
        }

        $cheminPhysique = UPLOADS_PATH . $document['chemin_fichier'];

        if (!file_exists($cheminPhysique)) {
            setFlash('error', 'Le fichier n\'existe plus sur le serveur.');
            redirect('/gestion_scolaire/documents');
        }

        // Envoyer le fichier
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $document['nom_fichier'] . '"');
        header('Content-Length: ' . filesize($cheminPhysique));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($cheminPhysique);
        exit;
    }

    /**
     * Récupère l'icône correspondant au type de fichier
     */
    public function getIconeFichier(string $extension): array
    {
        $icones = [
            'pdf'  => ['icon' => 'fa-file-pdf',  'color' => '#ef4444'],
            'doc'  => ['icon' => 'fa-file-word',  'color' => '#3b82f6'],
            'docx' => ['icon' => 'fa-file-word',  'color' => '#3b82f6'],
            'xls'  => ['icon' => 'fa-file-excel', 'color' => '#10b981'],
            'xlsx' => ['icon' => 'fa-file-excel', 'color' => '#10b981'],
            'csv'  => ['icon' => 'fa-file-csv',   'color' => '#10b981'],
            'ppt'  => ['icon' => 'fa-file-powerpoint', 'color' => '#f59e0b'],
            'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => '#f59e0b'],
            'jpg'  => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
            'jpeg' => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
            'png'  => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
            'gif'  => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
            'txt'  => ['icon' => 'fa-file-lines', 'color' => '#64748b'],
        ];

        return $icones[$extension] ?? ['icon' => 'fa-file', 'color' => '#64748b'];
    }
}