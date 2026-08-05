<?php
/**
 * Fichier : src/controllers/ParametreController.php
 * Rôle    : Gestion des paramètres de l'école
 * Système : École primaire congolaise — Administration
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class ParametreController
{
    private Database $db;
    private string $uploadDir;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->uploadDir = UPLOADS_PATH . 'logos' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Point d'entrée pour les requêtes AJAX
     */
    public function handleAjax(): void
    {
        if (!can('parametres.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'getParametres':
                $this->getParametres();
                break;
            case 'sauvegarder':
                $this->sauvegarder();
                break;
            case 'uploadLogo':
                $this->uploadLogo();
                break;
            case 'getAnnees':
                $this->getAnnees();
                break;
            case 'getTrimestres':
                $this->getTrimestres();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. RÉCUPÉRER LES PARAMÈTRES ACTUELS
    // ============================================================

    private function getParametres(): void
    {
        $params = $this->db->fetchOne("SELECT * FROM parametres_ecole WHERE id = 1");

        if (!$params) {
            // Valeurs par défaut si la table est vide
            $params = [
                'id'                        => 1,
                'nom_ecole'                 => defined('ECOLE_NOM') ? ECOLE_NOM : 'École Primaire',
                'devise'                    => defined('ECOLE_DEVIS') ? ECOLE_DEVIS : 'Discipline - Travail - Excellence',
                'adresse'                   => '',
                'telephone'                 => '',
                'email'                     => '',
                'site_web'                  => '',
                'logo'                      => '',
                'pays'                      => 'RÉPUBLIQUE DÉMOCRATIQUE DU CONGO',
                'ministere'                 => "MINISTÈRE DE L'ENSEIGNEMENT PRIMAIRE, SECONDAIRE ET PROFESSIONNEL",
                'ville'                     => '',
                'commune_territoire'        => '',
                'code_etablissement'        => '',
                'annee_scolaire_active_id'  => null,
                'trimestre_actif_id'        => null,
                'bareme_note_max'           => 20,
                'moyenne_passage'           => 10,
                'pourcentage_interro'       => 30,
                'pourcentage_devoir'        => 30,
                'pourcentage_examen'        => 40,
                'format_matricule'          => 'EL-{ANNEE}-{ID}',
            ];
        }

        // Récupérer le libellé de l'année active
        if ($params['annee_scolaire_active_id']) {
            $annee = $this->db->fetchOne("SELECT libelle FROM annees_scolaires WHERE id = :id", ['id' => $params['annee_scolaire_active_id']]);
            $params['annee_active_libelle'] = $annee ? $annee['libelle'] : '';
        }

        // Récupérer le libellé du trimestre actif
        if ($params['trimestre_actif_id']) {
            $trimestre = $this->db->fetchOne("SELECT libelle FROM trimestres WHERE id = :id", ['id' => $params['trimestre_actif_id']]);
            $params['trimestre_actif_libelle'] = $trimestre ? $trimestre['libelle'] : '';
        }

        jsonResponse(true, 'Paramètres récupérés.', ['parametres' => $params]);
    }

    // ============================================================
    // 2. SAUVEGARDER LES PARAMÈTRES
    // ============================================================

    private function sauvegarder(): void
    {
        requirePermission('parametres.modifier');

        $nomEcole            = post('nom_ecole', '');
        $devise              = post('devise', '');
        $adresse             = post('adresse', '');
        $telephone           = post('telephone', '');
        $email               = post('email', '');
        $siteWeb             = post('site_web', '');
        $pays                = post('pays', '');
        $ministere           = post('ministere', '');
        $ville               = post('ville', '');
        $commune             = post('commune_territoire', '');
        $codeEtablissement   = post('code_etablissement', '');
        $anneeActiveId       = (int) post('annee_scolaire_active_id', 0);
        $trimestreActifId    = (int) post('trimestre_actif_id', 0);
        $baremeNoteMax       = (float) post('bareme_note_max', 20);
        $moyennePassage      = (float) post('moyenne_passage', 10);
        $pourcentageInterro  = (float) post('pourcentage_interro', 30);
        $pourcentageDevoir   = (float) post('pourcentage_devoir', 30);
        $pourcentageExamen   = (float) post('pourcentage_examen', 40);
        $formatMatricule     = post('format_matricule', 'EL-{ANNEE}-{ID}');

        // Validation
        $errors = [];
        if (isBlank($nomEcole)) $errors['nom_ecole'] = 'Le nom de l\'école est obligatoire.';
        if ($anneeActiveId <= 0) $errors['annee_scolaire_active_id'] = 'Veuillez sélectionner une année scolaire active.';
        if ($trimestreActifId <= 0) $errors['trimestre_actif_id'] = 'Veuillez sélectionner un trimestre actif.';

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        // Vérifier que la somme des pourcentages = 100
        $totalPct = $pourcentageInterro + $pourcentageDevoir + $pourcentageExamen;
        if (abs($totalPct - 100) > 0.01) {
            jsonResponse(false, 'La somme des pourcentages (interro + devoir + examen) doit être égale à 100%. Actuellement : ' . $totalPct . '%', [], 422);
        }

        try {
            // Vérifier si la ligne existe
            $existe = $this->db->fetchColumn("SELECT COUNT(*) FROM parametres_ecole WHERE id = 1");

            if ($existe > 0) {
                // Mettre à jour
                $this->db->update(
                    "UPDATE parametres_ecole SET 
                        nom_ecole = :nom_ecole,
                        devise = :devise,
                        adresse = :adresse,
                        telephone = :telephone,
                        email = :email,
                        site_web = :site_web,
                        pays = :pays,
                        ministere = :ministere,
                        ville = :ville,
                        commune_territoire = :commune,
                        code_etablissement = :code_etab,
                        annee_scolaire_active_id = :annee_id,
                        trimestre_actif_id = :trim_id,
                        bareme_note_max = :bareme,
                        moyenne_passage = :moyenne,
                        pourcentage_interro = :pct_interro,
                        pourcentage_devoir = :pct_devoir,
                        pourcentage_examen = :pct_examen,
                        format_matricule = :format_mat
                     WHERE id = 1",
                    [
                        'nom_ecole'   => $nomEcole,
                        'devise'      => $devise,
                        'adresse'     => $adresse ?: null,
                        'telephone'   => $telephone ?: null,
                        'email'       => $email ?: null,
                        'site_web'    => $siteWeb ?: null,
                        'pays'        => $pays ?: null,
                        'ministere'   => $ministere ?: null,
                        'ville'       => $ville ?: null,
                        'commune'     => $commune ?: null,
                        'code_etab'   => $codeEtablissement ?: null,
                        'annee_id'    => $anneeActiveId,
                        'trim_id'     => $trimestreActifId,
                        'bareme'      => $baremeNoteMax,
                        'moyenne'     => $moyennePassage,
                        'pct_interro' => $pourcentageInterro,
                        'pct_devoir'  => $pourcentageDevoir,
                        'pct_examen'  => $pourcentageExamen,
                        'format_mat'  => $formatMatricule
                    ]
                );
            } else {
                // Créer
                $this->db->insert(
                    "INSERT INTO parametres_ecole (nom_ecole, devise, adresse, telephone, email, site_web, 
                     pays, ministere, ville, commune_territoire, code_etablissement,
                     annee_scolaire_active_id, trimestre_actif_id, bareme_note_max, moyenne_passage,
                     pourcentage_interro, pourcentage_devoir, pourcentage_examen, format_matricule)
                     VALUES (:nom_ecole, :devise, :adresse, :telephone, :email, :site_web,
                     :pays, :ministere, :ville, :commune, :code_etab,
                     :annee_id, :trim_id, :bareme, :moyenne, :pct_interro, :pct_devoir, :pct_examen, :format_mat)",
                    [
                        'nom_ecole'   => $nomEcole,
                        'devise'      => $devise,
                        'adresse'     => $adresse ?: null,
                        'telephone'   => $telephone ?: null,
                        'email'       => $email ?: null,
                        'site_web'    => $siteWeb ?: null,
                        'pays'        => $pays ?: null,
                        'ministere'   => $ministere ?: null,
                        'ville'       => $ville ?: null,
                        'commune'     => $commune ?: null,
                        'code_etab'   => $codeEtablissement ?: null,
                        'annee_id'    => $anneeActiveId,
                        'trim_id'     => $trimestreActifId,
                        'bareme'      => $baremeNoteMax,
                        'moyenne'     => $moyennePassage,
                        'pct_interro' => $pourcentageInterro,
                        'pct_devoir'  => $pourcentageDevoir,
                        'pct_examen'  => $pourcentageExamen,
                        'format_mat'  => $formatMatricule
                    ]
                );
            }

            // Mettre à jour l'année active dans la table annees_scolaires
            $this->db->update("UPDATE annees_scolaires SET est_active = 0");
            $this->db->update("UPDATE annees_scolaires SET est_active = 1 WHERE id = :id", ['id' => $anneeActiveId]);

            // Mettre à jour le trimestre actif
            $this->db->update("UPDATE trimestres SET est_actif = 0");
            $this->db->update("UPDATE trimestres SET est_actif = 1 WHERE id = :id", ['id' => $trimestreActifId]);

            logActivity('Modification', 'parametres_ecole', 1, 'Paramètres de l\'école mis à jour');

            jsonResponse(true, 'Paramètres sauvegardés avec succès.');

        } catch (Exception $e) {
            error_log('Erreur sauvegarde paramètres: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la sauvegarde des paramètres.');
        }
    }

    // ============================================================
    // 3. UPLOAD DU LOGO
    // ============================================================

    private function uploadLogo(): void
    {
        requirePermission('parametres.modifier');

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Aucun fichier sélectionné ou erreur d\'upload.', [], 400);
        }

        $fichier = $_FILES['logo'];
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            jsonResponse(false, 'Format d\'image non autorisé (JPG, PNG, GIF uniquement).', [], 400);
        }

        if ($fichier['size'] > 2 * 1024 * 1024) {
            jsonResponse(false, 'Le logo ne doit pas dépasser 2 Mo.', [], 400);
        }

        // Supprimer l'ancien logo
        $ancienLogo = $this->db->fetchColumn("SELECT logo FROM parametres_ecole WHERE id = 1");
        if ($ancienLogo && file_exists(UPLOADS_PATH . $ancienLogo)) {
            unlink(UPLOADS_PATH . $ancienLogo);
        }

        // Sauvegarder le nouveau
        $nomFichier = 'logo_' . time() . '.' . $extension;
        $cheminRelatif = 'uploads/logos/' . $nomFichier;
        $cheminAbsolu = UPLOADS_PATH . 'logos' . DIRECTORY_SEPARATOR . $nomFichier;

        if (!move_uploaded_file($fichier['tmp_name'], $cheminAbsolu)) {
            jsonResponse(false, 'Erreur lors de la sauvegarde du logo.', [], 500);
        }

        $this->db->update(
            "UPDATE parametres_ecole SET logo = :logo WHERE id = 1",
            ['logo' => $cheminRelatif]
        );

        jsonResponse(true, 'Logo mis à jour avec succès.', ['logo_url' => ASSETS_URL . $cheminRelatif]);
    }

    // ============================================================
    // 4. LISTE DES ANNÉES SCOLAIRES
    // ============================================================

    private function getAnnees(): void
    {
        $annees = $this->db->fetchAll("SELECT * FROM annees_scolaires ORDER BY date_debut DESC");
        jsonResponse(true, 'Années récupérées.', ['annees' => $annees]);
    }

    // ============================================================
    // 5. LISTE DES TRIMESTRES
    // ============================================================

    private function getTrimestres(): void
    {
        $anneeId = (int) post('annee_id', 0);
        if ($anneeId <= 0) {
            $anneeId = (int) $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
        }

        $trimestres = $this->db->fetchAll(
            "SELECT * FROM trimestres WHERE annee_scolaire_id = :aid ORDER BY numero",
            ['aid' => $anneeId]
        );

        jsonResponse(true, 'Trimestres récupérés.', ['trimestres' => $trimestres]);
    }
}