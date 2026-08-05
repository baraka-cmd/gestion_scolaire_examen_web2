-- ============================================================
-- Sauvegarde complète de la base de données
-- Base : gestion_scolaire
-- Date : 2026-08-05 13:38:48
-- Généré par : Gestion Scolaire v1.0
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- --------------------------------------------------------
-- Table : `absences`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `absences`;
CREATE TABLE `absences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `date_absence` date NOT NULL,
  `type_absence` enum('Absence','Présent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Absence',
  `est_justifiee` tinyint(1) NOT NULL DEFAULT 0,
  `motif` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `justificatif_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'certificat médical, mot des parents',
  `date_justification` date DEFAULT NULL,
  `saisie_par` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_absence` (`eleve_id`,`date_absence`),
  KEY `fk_absences_saisie` (`saisie_par`),
  CONSTRAINT `fk_absences_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_absences_saisie` FOREIGN KEY (`saisie_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=479 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `absences`
LOCK TABLES `absences` WRITE;
/*!40000 ALTER TABLE `absences` DISABLE KEYS */;
INSERT INTO `absences` VALUES ('437', '107', '2025-10-05', 'Absence', '1', 'Maladie — Certificat médical fourni', NULL, '2025-10-06', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('438', '107', '2026-02-14', 'Absence', '1', 'Décès familial — Grand-mère', NULL, '2026-02-15', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('439', '107', '2026-08-01', 'Présent', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('440', '108', '2025-11-20', 'Absence', '1', 'Consultation médicale — Dentiste', NULL, '2025-11-21', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('441', '108', '2026-01-15', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('442', '108', '2026-05-10', 'Absence', '1', 'Intempéries — Pluie diluvienne', NULL, '2026-05-11', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('443', '108', '2026-08-01', 'Présent', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('444', '109', '2025-10-12', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('445', '109', '2025-12-05', 'Absence', '1', 'Maladie — Paludisme', NULL, '2025-12-08', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('446', '109', '2026-01-20', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('447', '109', '2026-03-15', 'Absence', '1', 'Obligation familiale — Mariage', NULL, '2026-03-16', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('448', '109', '2026-06-01', 'Absence', '0', 'Transport — Panne de bus', NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('449', '109', '2026-08-01', 'Présent', '1', 'Maladie', NULL, NULL, '1', '2026-08-01 20:29:04', '2026-08-03 23:52:53');
INSERT INTO `absences` VALUES ('450', '110', '2025-09-15', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('451', '110', '2025-10-25', 'Absence', '1', 'Maladie — Grippe', NULL, '2025-10-28', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('452', '110', '2025-11-30', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('453', '110', '2026-01-10', 'Absence', '0', 'Réveil tardif', NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('454', '110', '2026-02-28', 'Absence', '1', 'Hospitalisation — 3 jours', NULL, '2026-03-03', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('455', '110', '2026-04-12', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('456', '110', '2026-06-20', 'Absence', '0', 'Transport', NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('457', '110', '2026-08-01', 'Absence', '0', 'Maladie', NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('458', '111', '2025-09-08', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('459', '111', '2025-09-22', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('460', '111', '2025-10-10', 'Absence', '0', 'Réveil tardif', NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('461', '111', '2025-11-05', 'Absence', '1', 'Maladie — Certificat médical', NULL, '2025-11-07', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('462', '111', '2025-12-01', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('463', '111', '2026-01-18', 'Absence', '0', 'Transport', NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('464', '111', '2026-02-05', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('465', '111', '2026-03-10', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('466', '111', '2026-04-02', 'Absence', '1', 'Intempéries', NULL, '2026-04-03', '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('467', '111', '2026-05-15', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('468', '111', '2026-06-08', 'Absence', '0', 'Réveil tardif', NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('469', '111', '2026-07-01', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('470', '111', '2026-08-01', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-01 20:29:04', NULL);
INSERT INTO `absences` VALUES ('471', '107', '2026-08-03', 'Présent', '0', NULL, NULL, NULL, '1', '2026-08-03 23:52:05', NULL);
INSERT INTO `absences` VALUES ('472', '108', '2026-08-03', 'Présent', '0', NULL, NULL, NULL, '1', '2026-08-03 23:52:05', NULL);
INSERT INTO `absences` VALUES ('473', '109', '2026-08-03', 'Présent', '0', NULL, NULL, NULL, '1', '2026-08-03 23:52:05', NULL);
INSERT INTO `absences` VALUES ('474', '110', '2026-08-03', 'Présent', '0', NULL, NULL, NULL, '1', '2026-08-03 23:52:05', NULL);
INSERT INTO `absences` VALUES ('475', '111', '2026-08-03', 'Absence', '0', NULL, NULL, NULL, '1', '2026-08-03 23:52:05', NULL);
/*!40000 ALTER TABLE `absences` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `annees_scolaires`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `annees_scolaires`;
CREATE TABLE `annees_scolaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: 2025-2026',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `est_active` tinyint(1) NOT NULL DEFAULT 0,
  `est_cloturee` tinyint(1) NOT NULL DEFAULT 0,
  `commentaire` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `libelle` (`libelle`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `annees_scolaires`
LOCK TABLES `annees_scolaires` WRITE;
/*!40000 ALTER TABLE `annees_scolaires` DISABLE KEYS */;
INSERT INTO `annees_scolaires` VALUES ('5', '2025-2026', '2025-09-01', '2026-07-05', '1', '0', NULL, '2026-08-01 02:12:29', '2026-08-05 12:54:48');
/*!40000 ALTER TABLE `annees_scolaires` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `bulletins`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `bulletins`;
CREATE TABLE `bulletins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `trimestre_id` int(11) NOT NULL,
  `numero_bulletin` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_edition` date NOT NULL DEFAULT curdate(),
  `edite_par` int(11) NOT NULL COMMENT 'utilisateur qui a généré le bulletin',
  `signature_directeur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_enseignant` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_parent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_signature_parent` datetime DEFAULT NULL,
  `commentaire_directeur` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commentaire_enseignant` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_imprime` tinyint(1) NOT NULL DEFAULT 0,
  `est_envoye` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_bulletin` (`numero_bulletin`),
  UNIQUE KEY `uk_bulletin` (`eleve_id`,`trimestre_id`),
  KEY `fk_bulletins_trimestre` (`trimestre_id`),
  KEY `fk_bulletins_editeur` (`edite_par`),
  CONSTRAINT `fk_bulletins_editeur` FOREIGN KEY (`edite_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bulletins_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bulletins_trimestre` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `calendrier_scolaire`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `calendrier_scolaire`;
CREATE TABLE `calendrier_scolaire` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `annee_scolaire_id` int(11) NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_evenement` enum('Rentrée','Vacances','Jour férié','Examen','Réunion parents','Fête scolaire','Sortie pédagogique','Conseil de classe','Formation','Autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Autre',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `lieu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `concerne_classes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'classes concernées, NULL = toute l''école',
  `est_public` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'visible par tous les utilisateurs',
  `created_par` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_calendrier_annee` (`annee_scolaire_id`),
  KEY `fk_calendrier_utilisateur` (`created_par`),
  CONSTRAINT `fk_calendrier_annee` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_calendrier_utilisateur` FOREIGN KEY (`created_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `cantine`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `cantine`;
CREATE TABLE `cantine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `annee_scolaire_id` int(11) NOT NULL,
  `est_inscrit` tinyint(1) NOT NULL DEFAULT 1,
  `date_inscription` date NOT NULL DEFAULT curdate(),
  `tarif_journalier` decimal(6,2) NOT NULL DEFAULT 0.00,
  `tarif_mensuel` decimal(8,2) NOT NULL DEFAULT 0.00,
  `regime_particulier` enum('Aucun','Végétarien','Sans porc','Allergie','Autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aucun',
  `detail_regime` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'précisions sur le régime alimentaire',
  `date_debut_service` date NOT NULL,
  `date_fin_service` date DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cantine_eleve_annee` (`eleve_id`,`annee_scolaire_id`),
  KEY `fk_cantine_annee` (`annee_scolaire_id`),
  CONSTRAINT `fk_cantine_annee` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cantine_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `classe_matieres`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `classe_matieres`;
CREATE TABLE `classe_matieres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classe_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `maxima_override` int(11) DEFAULT NULL COMMENT 'Override du maxima pour cette classe spécifique. NULL = utilise le maxima de la matière',
  `est_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Matière activée pour cette classe',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_classe_matiere` (`classe_id`,`matiere_id`),
  KEY `fk_cm_matiere` (`matiere_id`),
  CONSTRAINT `fk_cm_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cm_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=271 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `classe_matieres`
LOCK TABLES `classe_matieres` WRITE;
/*!40000 ALTER TABLE `classe_matieres` DISABLE KEYS */;
INSERT INTO `classe_matieres` VALUES ('246', '21', '62', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('247', '21', '63', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('248', '21', '64', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('249', '21', '65', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('250', '21', '66', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('251', '21', '67', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('252', '21', '68', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('253', '21', '69', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('254', '21', '70', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('255', '21', '71', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('256', '21', '72', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('257', '21', '73', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('258', '21', '74', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('259', '21', '75', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('260', '21', '76', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('261', '21', '77', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('262', '21', '78', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('263', '21', '79', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('264', '21', '80', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('265', '21', '81', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('266', '21', '82', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('267', '21', '83', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('268', '21', '84', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('269', '21', '85', NULL, '1', '2026-08-01 02:12:30', NULL);
INSERT INTO `classe_matieres` VALUES ('270', '21', '86', NULL, '1', '2026-08-01 02:12:30', NULL);
/*!40000 ALTER TABLE `classe_matieres` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `classes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `niveau` tinyint(4) NOT NULL COMMENT '1 à 6 pour primaire',
  `annee_scolaire_id` int(11) NOT NULL,
  `capacite_max` int(11) NOT NULL DEFAULT 50,
  `salle` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'numéro ou nom de salle',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_classe_annee` (`nom`,`annee_scolaire_id`),
  KEY `fk_classes_annee` (`annee_scolaire_id`),
  CONSTRAINT `fk_classes_annee` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `classes`
LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES ('21', '1ère Primaire', '1', '5', '50', 'Salle 1', NULL, '1', '2026-08-01 02:12:30', NULL);
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `discipline`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `discipline`;
CREATE TABLE `discipline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `sanction_id` int(11) NOT NULL,
  `date_incident` date NOT NULL,
  `lieu` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'description détaillée de l''incident',
  `temoin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'noms des témoins éventuels',
  `mesure_prise` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'détail de la sanction appliquée',
  `date_debut_sanction` date DEFAULT NULL,
  `date_fin_sanction` date DEFAULT NULL,
  `notifie_parent` tinyint(1) NOT NULL DEFAULT 0,
  `date_notification` date DEFAULT NULL,
  `moyen_notification` enum('Carnet','Téléphone','Email','Convocation','Autre') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_parent_retour` tinyint(1) NOT NULL DEFAULT 0,
  `date_signature` date DEFAULT NULL,
  `suivi` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'observations post-sanction, comportement après',
  `est_cloture` tinyint(1) NOT NULL DEFAULT 0,
  `date_cloture` date DEFAULT NULL,
  `enregistre_par` int(11) NOT NULL COMMENT 'utilisateur ayant enregistré l''incident',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_discipline_eleve` (`eleve_id`),
  KEY `fk_discipline_sanction` (`sanction_id`),
  KEY `fk_discipline_utilisateur` (`enregistre_par`),
  CONSTRAINT `fk_discipline_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_discipline_sanction` FOREIGN KEY (`sanction_id`) REFERENCES `sanctions` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_discipline_utilisateur` FOREIGN KEY (`enregistre_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_document` enum('Règlement','Programme','Circulaire','Cours','Exercice','Formulaire','Rapport','Autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Autre',
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin_fichier` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taille_fichier` int(11) NOT NULL COMMENT 'en octets',
  `extension` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe_id` int(11) DEFAULT NULL COMMENT 'NULL = document général',
  `matiere_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) NOT NULL COMMENT 'qui a uploadé',
  `est_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_documents_classe` (`classe_id`),
  KEY `fk_documents_matiere` (`matiere_id`),
  KEY `fk_documents_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_documents_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_documents_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_documents_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `documents`
LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES ('1', 'Classeur1', 'finance', '', 'Classeur1.pdf', 'uploads/documents/doc_6a71fbbea30ca_1785854910.pdf', '355253', 'pdf', NULL, NULL, '14', '1', '2026-08-04 16:48:30', NULL);
INSERT INTO `documents` VALUES ('2', 'BUREAUTIQUE', 'bbbb', '', 'BUREAUTIQUE.pdf', 'uploads/documents/doc_6a71fc819e09b_1785855105.pdf', '2723292', 'pdf', NULL, NULL, '14', '1', '2026-08-04 16:51:45', NULL);
INSERT INTO `documents` VALUES ('3', '1000029305', 'photo', '', '1000029305.jpg', 'uploads/documents/doc_6a71fd29c91d4_1785855273.jpg', '52433', 'jpg', NULL, NULL, '14', '1', '2026-08-04 16:54:33', NULL);
INSERT INTO `documents` VALUES ('4', '1000029641', 'xxxxxxxx', '', '1000029641.jpg', 'uploads/documents/1000029641_20260804_165913.jpg', '38007', 'jpg', NULL, NULL, '14', '1', '2026-08-04 16:59:13', NULL);
INSERT INTO `documents` VALUES ('5', '1000029654', 'document prive', '', '1000029654.jpg', 'uploads/documents/1000029654_20260805_115744.jpg', '123176', 'jpg', NULL, NULL, '14', '0', '2026-08-05 11:57:44', NULL);
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `eleve_parent`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `eleve_parent`;
CREATE TABLE `eleve_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `est_tuteur_principal` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'responsable principal de l''élève',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_eleve_parent` (`eleve_id`,`parent_id`),
  KEY `fk_ep_parent` (`parent_id`),
  CONSTRAINT `fk_ep_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ep_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents_tuteurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `eleve_parent`
LOCK TABLES `eleve_parent` WRITE;
/*!40000 ALTER TABLE `eleve_parent` DISABLE KEYS */;
INSERT INTO `eleve_parent` VALUES ('62', '107', '50', '1', '2026-08-02 13:40:25');
INSERT INTO `eleve_parent` VALUES ('63', '108', '51', '1', '2026-08-02 13:40:25');
INSERT INTO `eleve_parent` VALUES ('64', '109', '52', '1', '2026-08-02 13:40:25');
INSERT INTO `eleve_parent` VALUES ('65', '110', '53', '1', '2026-08-02 13:40:25');
INSERT INTO `eleve_parent` VALUES ('66', '111', '54', '1', '2026-08-02 13:40:25');
INSERT INTO `eleve_parent` VALUES ('67', '113', '55', '1', '2026-08-04 01:04:59');
/*!40000 ALTER TABLE `eleve_parent` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `eleves`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `eleves`;
CREATE TABLE `eleves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricule` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_naissance` date NOT NULL,
  `lieu_naissance` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sexe` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groupe_sanguin` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maladies` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_inscription` date NOT NULL DEFAULT curdate(),
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `numero_permanent` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'N° permanent / N° ID national',
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricule` (`matricule`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `eleves`
LOCK TABLES `eleves` WRITE;
/*!40000 ALTER TABLE `eleves` DISABLE KEYS */;
INSERT INTO `eleves` VALUES ('107', 'EL-25-00001', 'LUKUSA', 'Emmanuel', '2017-02-14', 'Lubumbashi', 'M', '45 Avenue Lumumba, Kaleme', '+243810000001', NULL, NULL, 'O+', NULL, NULL, '2025-09-01', '1', '2026-08-01 02:13:56', NULL, NULL);
INSERT INTO `eleves` VALUES ('108', 'EL-25-00002', 'KABONGO', 'Grâce', '2017-05-20', 'Lubumbashi', 'F', '78 Avenue Kasa-Vubu, Kaleme', '+243810000003', NULL, NULL, 'A+', 'Arachides', NULL, '2025-09-01', '1', '2026-08-01 02:13:56', NULL, NULL);
INSERT INTO `eleves` VALUES ('109', 'EL-25-00003', 'MULONDA', 'Daniel', '2017-08-10', 'Lubumbashi', 'M', '12 Avenue Moero, Kaleme', '+243810000005', NULL, NULL, 'B+', NULL, 'Asthme léger', '2025-09-01', '1', '2026-08-01 02:13:57', NULL, NULL);
INSERT INTO `eleves` VALUES ('110', 'EL-25-00004', 'TSHIBOLA', 'Esther', '2017-11-25', 'Lubumbashi', 'F', '34 Avenue Tanganyika, Kaleme', '+243810000007', NULL, NULL, 'AB+', NULL, NULL, '2025-09-01', '1', '2026-08-01 02:13:57', NULL, NULL);
INSERT INTO `eleves` VALUES ('111', 'EL-25-00005', 'KANKU', 'Joseph', '2017-09-05', 'Lubumbashi', 'M', '56 Avenue Kivu, Kaleme', '+243810000009', NULL, NULL, 'O-', NULL, NULL, '2025-09-01', '1', '2026-08-01 02:13:57', NULL, NULL);
INSERT INTO `eleves` VALUES ('113', 'EL-26-00112', 'Hhhhh', 'Hhhhhhhh', '2026-08-19', 'jjjjjjjj', 'M', 'bbbbbbbbbb', '99999999', 'eleve@gmail.com', NULL, 'A-', 'jjjjjjj', 'hhhhhhhhhhhhh', '2026-08-04', '1', '2026-08-04 01:04:59', NULL, NULL);
/*!40000 ALTER TABLE `eleves` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `emploi_du_temps`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `emploi_du_temps`;
CREATE TABLE `emploi_du_temps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classe_id` int(11) NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `matiere_id` int(11) DEFAULT NULL,
  `enseignant_id` int(11) DEFAULT NULL,
  `type_creneau` enum('cours','recréation','cernafor') COLLATE utf8mb4_unicode_ci DEFAULT 'cours',
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annee_scolaire_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_creneau` (`classe_id`,`jour`,`heure_debut`,`annee_scolaire_id`),
  KEY `fk_edt_matiere` (`matiere_id`),
  KEY `fk_edt_enseignant` (`enseignant_id`),
  KEY `fk_edt_annee` (`annee_scolaire_id`),
  CONSTRAINT `fk_edt_annee` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_edt_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_edt_enseignant` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_edt_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `emploi_du_temps`
LOCK TABLES `emploi_du_temps` WRITE;
/*!40000 ALTER TABLE `emploi_du_temps` DISABLE KEYS */;
INSERT INTO `emploi_du_temps` VALUES ('65', '21', 'Lundi', '07:30:00', '08:30:00', '69', '12', 'cours', NULL, '5', '2026-08-01 18:52:40', NULL);
INSERT INTO `emploi_du_temps` VALUES ('66', '21', 'Lundi', '08:30:00', '09:30:00', '73', '12', 'cours', NULL, '5', '2026-08-01 18:52:40', NULL);
INSERT INTO `emploi_du_temps` VALUES ('67', '21', 'Lundi', '09:30:00', '10:00:00', NULL, NULL, 'recréation', 'RÉCRÉATION', '5', '2026-08-01 18:52:40', NULL);
INSERT INTO `emploi_du_temps` VALUES ('68', '21', 'Lundi', '10:00:00', '11:00:00', '79', '12', 'cours', NULL, '5', '2026-08-01 18:52:40', NULL);
INSERT INTO `emploi_du_temps` VALUES ('69', '21', 'Lundi', '11:00:00', '12:00:00', '85', '12', 'cours', NULL, '5', '2026-08-01 18:52:40', NULL);
INSERT INTO `emploi_du_temps` VALUES ('70', '21', 'Lundi', '12:00:00', '12:30:00', NULL, NULL, 'recréation', 'PAUSE DÉJEUNER', '5', '2026-08-01 18:52:40', NULL);
INSERT INTO `emploi_du_temps` VALUES ('71', '21', 'Lundi', '12:30:00', '13:30:00', '62', '12', 'cours', NULL, '5', '2026-08-01 18:52:41', NULL);
INSERT INTO `emploi_du_temps` VALUES ('72', '21', 'Lundi', '13:30:00', '14:30:00', '68', '12', 'cours', NULL, '5', '2026-08-01 18:52:41', NULL);
INSERT INTO `emploi_du_temps` VALUES ('73', '21', 'Mardi', '07:30:00', '08:30:00', '73', '12', 'cours', NULL, '5', '2026-08-01 18:52:41', NULL);
INSERT INTO `emploi_du_temps` VALUES ('74', '21', 'Mardi', '08:30:00', '09:30:00', '70', '12', 'cours', NULL, '5', '2026-08-01 18:52:41', NULL);
INSERT INTO `emploi_du_temps` VALUES ('75', '21', 'Mardi', '09:30:00', '10:00:00', NULL, NULL, 'recréation', 'RÉCRÉATION', '5', '2026-08-01 18:52:41', NULL);
INSERT INTO `emploi_du_temps` VALUES ('76', '21', 'Mardi', '10:00:00', '11:00:00', '65', '12', 'cours', NULL, '5', '2026-08-01 18:52:41', NULL);
INSERT INTO `emploi_du_temps` VALUES ('77', '21', 'Mardi', '11:00:00', '12:00:00', '80', '12', 'cours', NULL, '5', '2026-08-01 18:52:41', NULL);
INSERT INTO `emploi_du_temps` VALUES ('78', '21', 'Mardi', '12:00:00', '12:30:00', NULL, NULL, 'recréation', 'PAUSE DÉJEUNER', '5', '2026-08-01 18:52:41', NULL);
INSERT INTO `emploi_du_temps` VALUES ('79', '21', 'Mardi', '12:30:00', '13:30:00', '63', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('80', '21', 'Mardi', '13:30:00', '14:30:00', '84', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('81', '21', 'Mercredi', '07:30:00', '08:30:00', '72', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('82', '21', 'Mercredi', '08:30:00', '09:30:00', '75', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('83', '21', 'Mercredi', '09:30:00', '10:00:00', NULL, NULL, 'recréation', 'RÉCRÉATION', '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('84', '21', 'Mercredi', '10:00:00', '11:00:00', '82', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('85', '21', 'Mercredi', '11:00:00', '12:00:00', '71', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('86', '21', 'Mercredi', '12:00:00', '12:30:00', NULL, NULL, 'recréation', 'PAUSE DÉJEUNER', '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('87', '21', 'Mercredi', '12:30:00', '13:30:00', '81', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('88', '21', 'Mercredi', '13:30:00', '14:30:00', '66', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('89', '21', 'Jeudi', '07:30:00', '08:30:00', '80', '12', 'cours', NULL, '5', '2026-08-01 18:52:42', NULL);
INSERT INTO `emploi_du_temps` VALUES ('90', '21', 'Jeudi', '08:30:00', '09:30:00', '71', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('91', '21', 'Jeudi', '09:30:00', '10:00:00', NULL, NULL, 'recréation', 'RÉCRÉATION', '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('92', '21', 'Jeudi', '10:00:00', '11:00:00', '74', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('93', '21', 'Jeudi', '11:00:00', '12:00:00', '67', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('94', '21', 'Jeudi', '12:00:00', '12:30:00', NULL, NULL, 'recréation', 'PAUSE DÉJEUNER', '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('95', '21', 'Jeudi', '12:30:00', '13:30:00', '83', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('96', '21', 'Jeudi', '13:30:00', '14:30:00', '64', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('97', '21', 'Vendredi', '07:30:00', '08:30:00', '76', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('98', '21', 'Vendredi', '08:30:00', '09:30:00', '78', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('99', '21', 'Vendredi', '09:30:00', '10:00:00', NULL, NULL, 'recréation', 'RÉCRÉATION', '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('100', '21', 'Vendredi', '10:00:00', '11:00:00', '70', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('101', '21', 'Vendredi', '11:00:00', '12:00:00', '86', '12', 'cours', NULL, '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('102', '21', 'Vendredi', '12:00:00', '12:30:00', NULL, NULL, 'recréation', 'PAUSE DÉJEUNER', '5', '2026-08-01 18:52:43', NULL);
INSERT INTO `emploi_du_temps` VALUES ('103', '21', 'Vendredi', '12:30:00', '13:30:00', '68', '12', 'cours', NULL, '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('104', '21', 'Vendredi', '13:30:00', '14:30:00', '77', '12', 'cours', NULL, '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('105', '21', 'Samedi', '07:30:00', '08:30:00', '69', '12', 'cours', NULL, '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('106', '21', 'Samedi', '08:30:00', '09:30:00', '68', '12', 'cours', NULL, '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('107', '21', 'Samedi', '09:30:00', '10:00:00', NULL, NULL, 'recréation', 'RÉCRÉATION', '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('108', '21', 'Samedi', '10:00:00', '11:00:00', NULL, NULL, 'cernafor', 'CERNAFOR', '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('109', '21', 'Samedi', '11:00:00', '12:00:00', '75', '12', 'cours', NULL, '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('110', '21', 'Samedi', '12:00:00', '12:30:00', NULL, NULL, 'recréation', 'PAUSE DÉJEUNER', '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('111', '21', 'Samedi', '12:30:00', '13:30:00', '82', '12', 'cours', NULL, '5', '2026-08-01 18:52:44', NULL);
INSERT INTO `emploi_du_temps` VALUES ('112', '21', 'Samedi', '13:30:00', '14:30:00', '62', '12', 'cours', NULL, '5', '2026-08-01 18:52:44', NULL);
/*!40000 ALTER TABLE `emploi_du_temps` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `enseignants`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `enseignants`;
CREATE TABLE `enseignants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricule` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `sexe` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diplome` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialite` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `classe_id` int(11) DEFAULT NULL COMMENT 'classe dont il est titulaire',
  `utilisateur_id` int(11) DEFAULT NULL COMMENT 'compte utilisateur lié',
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricule` (`matricule`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uk_enseignant_classe` (`classe_id`) COMMENT 'Un enseignant = une seule classe',
  KEY `fk_enseignants_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_enseignants_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_enseignants_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `enseignants`
LOCK TABLES `enseignants` WRITE;
/*!40000 ALTER TABLE `enseignants` DISABLE KEYS */;
INSERT INTO `enseignants` VALUES ('12', 'ENS-25-001', 'KASONGO', 'Marie', '1985-03-15', 'F', '12 Avenue de l\'École, Quartier Kaleme', '+243812345678', 'marie.kasongo@ecole.edu', 'Licence en Sciences de l\'Éducation', 'Pédagogie Générale', '2020-09-01', '21', '7', NULL, '1', '2026-08-01 02:13:56', '2026-08-04 00:00:18');
/*!40000 ALTER TABLE `enseignants` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `evaluations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `evaluations`;
CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trimestre_id` int(11) NOT NULL,
  `groupe_id` int(11) NOT NULL COMMENT 'FK vers matieres (groupes uniquement, est_groupe=1)',
  `classe_id` int(11) NOT NULL,
  `type_evaluation` enum('Période 1','Période 2','Examen') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_evaluation` date NOT NULL COMMENT 'Date de l''évaluation',
  `bareme` decimal(5,2) NOT NULL DEFAULT 20.00,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_eval_unique` (`trimestre_id`,`groupe_id`,`classe_id`,`type_evaluation`),
  KEY `fk_evaluations_groupe` (`groupe_id`),
  KEY `fk_evaluations_classe` (`classe_id`),
  CONSTRAINT `fk_evaluations_groupe` FOREIGN KEY (`groupe_id`) REFERENCES `matieres` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=244 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `evaluations`
LOCK TABLES `evaluations` WRITE;
/*!40000 ALTER TABLE `evaluations` DISABLE KEYS */;
INSERT INTO `evaluations` VALUES ('189', '13', '56', '21', 'Période 1', '2025-10-15', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('190', '13', '56', '21', 'Période 2', '2025-11-15', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('191', '13', '56', '21', 'Examen', '2025-11-30', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('192', '13', '57', '21', 'Période 1', '2025-10-15', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('193', '13', '57', '21', 'Période 2', '2025-11-15', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('194', '13', '57', '21', 'Examen', '2025-11-30', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('195', '13', '58', '21', 'Période 1', '2025-10-16', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('196', '13', '58', '21', 'Période 2', '2025-11-16', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('197', '13', '58', '21', 'Examen', '2025-12-01', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('198', '13', '59', '21', 'Période 1', '2025-10-17', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('199', '13', '59', '21', 'Période 2', '2025-11-17', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('200', '13', '59', '21', 'Examen', '2025-12-02', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('201', '13', '60', '21', 'Période 1', '2025-10-18', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('202', '13', '60', '21', 'Période 2', '2025-11-18', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('203', '13', '60', '21', 'Examen', '2025-12-03', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('204', '13', '61', '21', 'Période 1', '2025-10-19', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('205', '13', '61', '21', 'Période 2', '2025-11-19', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('206', '13', '61', '21', 'Examen', '2025-12-04', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('207', '14', '56', '21', 'Période 1', '2026-01-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('208', '14', '56', '21', 'Période 2', '2026-02-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('209', '14', '56', '21', 'Examen', '2026-03-15', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('210', '14', '57', '21', 'Période 1', '2026-01-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('211', '14', '57', '21', 'Période 2', '2026-02-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('212', '14', '57', '21', 'Examen', '2026-03-15', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('213', '14', '58', '21', 'Période 1', '2026-01-21', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('214', '14', '58', '21', 'Période 2', '2026-02-21', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('215', '14', '58', '21', 'Examen', '2026-03-16', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('216', '14', '59', '21', 'Période 1', '2026-01-22', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('217', '14', '59', '21', 'Période 2', '2026-02-22', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('218', '14', '59', '21', 'Examen', '2026-03-17', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('219', '14', '60', '21', 'Période 1', '2026-01-23', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('220', '14', '60', '21', 'Période 2', '2026-02-23', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('221', '14', '60', '21', 'Examen', '2026-03-18', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('222', '14', '61', '21', 'Période 1', '2026-01-24', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('223', '14', '61', '21', 'Période 2', '2026-02-24', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('224', '14', '61', '21', 'Examen', '2026-03-19', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('225', '15', '56', '21', 'Période 1', '2026-04-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('226', '15', '56', '21', 'Période 2', '2026-05-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('227', '15', '56', '21', 'Examen', '2026-06-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('228', '15', '57', '21', 'Période 1', '2026-04-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('229', '15', '57', '21', 'Période 2', '2026-05-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('230', '15', '57', '21', 'Examen', '2026-06-20', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('231', '15', '58', '21', 'Période 1', '2026-04-21', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('232', '15', '58', '21', 'Période 2', '2026-05-21', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('233', '15', '58', '21', 'Examen', '2026-06-21', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('234', '15', '59', '21', 'Période 1', '2026-04-22', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('235', '15', '59', '21', 'Période 2', '2026-05-22', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('236', '15', '59', '21', 'Examen', '2026-06-22', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('237', '15', '60', '21', 'Période 1', '2026-04-23', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('238', '15', '60', '21', 'Période 2', '2026-05-23', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('239', '15', '60', '21', 'Examen', '2026-06-23', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('240', '15', '61', '21', 'Période 1', '2026-04-24', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('241', '15', '61', '21', 'Période 2', '2026-05-24', '20.00', NULL, '2026-08-01 15:36:33', NULL);
INSERT INTO `evaluations` VALUES ('242', '15', '61', '21', 'Examen', '2026-06-24', '20.00', NULL, '2026-08-01 15:36:33', NULL);
/*!40000 ALTER TABLE `evaluations` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `evaluations_backup`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `evaluations_backup`;
CREATE TABLE `evaluations_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `trimestre_id` int(11) NOT NULL,
  `groupe_id` int(11) NOT NULL COMMENT 'FK vers matieres (groupes uniquement, est_groupe=1)',
  `classe_id` int(11) NOT NULL,
  `type_evaluation` enum('Période 1','Période 2','Examen') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_evaluation` date NOT NULL COMMENT 'Date de l''évaluation',
  `bareme` decimal(5,2) NOT NULL DEFAULT 20.00,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `factures`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `factures`;
CREATE TABLE `factures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_facture` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paiement_id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  `date_emission` date NOT NULL DEFAULT curdate(),
  `montant_HT` decimal(10,2) NOT NULL,
  `montant_TTC` decimal(10,2) NOT NULL,
  `taxe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `statut_facture` enum('Émise','Payée','Annulée','Impayée') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Émise',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'détail des frais facturés',
  `conditions_paiement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_echeance` date DEFAULT NULL,
  `emise_par` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_facture` (`numero_facture`),
  KEY `fk_factures_paiement` (`paiement_id`),
  KEY `fk_factures_eleve` (`eleve_id`),
  KEY `fk_factures_utilisateur` (`emise_par`),
  CONSTRAINT `fk_factures_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_factures_paiement` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_factures_utilisateur` FOREIGN KEY (`emise_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `factures`
LOCK TABLES `factures` WRITE;
/*!40000 ALTER TABLE `factures` DISABLE KEYS */;
INSERT INTO `factures` VALUES ('4', 'RECU-20260801-9325', '142', '110', '2026-08-01', '100.00', '100.00', '0.00', 'Payée', NULL, NULL, NULL, '14', '2026-08-01 22:22:54', NULL);
INSERT INTO `factures` VALUES ('10', 'RECU-20260801-4170', '143', '108', '2026-08-01', '10.00', '10.00', '0.00', 'Payée', NULL, NULL, NULL, '14', '2026-08-01 22:28:07', NULL);
INSERT INTO `factures` VALUES ('16', 'RECU-20260802-2311', '144', '109', '2026-08-02', '70.00', '70.00', '0.00', 'Payée', NULL, NULL, NULL, '14', '2026-08-02 10:30:14', NULL);
INSERT INTO `factures` VALUES ('20', 'RECU-20260802-0289', '145', '111', '2026-08-02', '10.00', '10.00', '0.00', 'Payée', NULL, NULL, NULL, '14', '2026-08-02 10:33:58', NULL);
INSERT INTO `factures` VALUES ('21', 'RECU-20260802-3404', '144', '109', '2026-08-02', '70.00', '70.00', '0.00', 'Payée', NULL, NULL, NULL, '14', '2026-08-02 10:45:59', NULL);
INSERT INTO `factures` VALUES ('22', 'RECU-20260802-2410', '146', '107', '2026-08-02', '10.00', '10.00', '0.00', 'Payée', NULL, NULL, NULL, '14', '2026-08-02 10:49:11', NULL);
/*!40000 ALTER TABLE `factures` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `frais_scolaires`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `frais_scolaires`;
CREATE TABLE `frais_scolaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `classe_id` int(11) DEFAULT NULL COMMENT 'NULL = frais commun à toutes les classes',
  `annee_scolaire_id` int(11) NOT NULL,
  `est_obligatoire` tinyint(1) NOT NULL DEFAULT 1,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_frais_classe` (`classe_id`),
  KEY `fk_frais_annee` (`annee_scolaire_id`),
  CONSTRAINT `fk_frais_annee` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_frais_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `frais_scolaires`
LOCK TABLES `frais_scolaires` WRITE;
/*!40000 ALTER TABLE `frais_scolaires` DISABLE KEYS */;
INSERT INTO `frais_scolaires` VALUES ('15', 'Frais Scolaire', NULL, '500.00', NULL, '5', '1', '1', '2026-08-01 22:21:26', NULL);
INSERT INTO `frais_scolaires` VALUES ('16', 'Inscription', NULL, '10.00', NULL, '5', '1', '1', '2026-08-01 22:21:58', NULL);
/*!40000 ALTER TABLE `frais_scolaires` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `inscriptions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `inscriptions`;
CREATE TABLE `inscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `annee_scolaire_id` int(11) NOT NULL,
  `date_inscription` date NOT NULL DEFAULT curdate(),
  `statut` enum('Actif','Redoublant','Transféré','Exclu','Diplômé','Abandon') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Actif',
  `motif_statut` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'raison du changement de statut',
  `date_changement_statut` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inscription_eleve_annee` (`eleve_id`,`annee_scolaire_id`) COMMENT 'Un élève = une seule inscription par année',
  KEY `fk_inscriptions_classe` (`classe_id`),
  KEY `fk_inscriptions_annee` (`annee_scolaire_id`),
  CONSTRAINT `fk_inscriptions_annee` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_inscriptions_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_inscriptions_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `inscriptions`
LOCK TABLES `inscriptions` WRITE;
/*!40000 ALTER TABLE `inscriptions` DISABLE KEYS */;
INSERT INTO `inscriptions` VALUES ('112', '107', '21', '5', '2025-09-01', 'Actif', NULL, NULL, '2026-08-01 02:15:22', NULL);
INSERT INTO `inscriptions` VALUES ('113', '108', '21', '5', '2025-09-01', 'Actif', NULL, NULL, '2026-08-01 02:15:22', NULL);
INSERT INTO `inscriptions` VALUES ('114', '109', '21', '5', '2025-09-01', 'Actif', NULL, NULL, '2026-08-01 02:15:22', NULL);
INSERT INTO `inscriptions` VALUES ('115', '110', '21', '5', '2025-09-01', 'Actif', NULL, NULL, '2026-08-01 02:15:22', NULL);
INSERT INTO `inscriptions` VALUES ('116', '111', '21', '5', '2025-09-01', 'Actif', NULL, NULL, '2026-08-01 02:15:22', NULL);
INSERT INTO `inscriptions` VALUES ('117', '113', '21', '5', '2026-08-04', 'Actif', NULL, NULL, '2026-08-04 01:04:59', NULL);
/*!40000 ALTER TABLE `inscriptions` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `logs_activites`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `logs_activites`;
CREATE TABLE `logs_activites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) DEFAULT NULL COMMENT 'NULL si action système automatique',
  `action` enum('Connexion','Déconnexion','Création','Modification','Suppression','Consultation','Impression','Export','Import','Activation','Désactivation','Réinitialisation mot de passe','Sauvegarde','Restauration','Erreur','Tentative échouée','Autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_concernee` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'nom de la table modifiée',
  `enregistrement_id` int(11) DEFAULT NULL COMMENT 'ID de l''enregistrement concerné',
  `ancienne_valeur` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'valeur avant modification (JSON)',
  `nouvelle_valeur` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'valeur après modification (JSON)',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'détail lisible de l''action',
  `adresse_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `navigateur` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_logs_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_logs_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=553 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `logs_activites`
LOCK TABLES `logs_activites` WRITE;
/*!40000 ALTER TABLE `logs_activites` DISABLE KEYS */;
INSERT INTO `logs_activites` VALUES ('1', '5', 'Connexion', 'utilisateurs', '5', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 22:07:42');
INSERT INTO `logs_activites` VALUES ('2', '1', 'Connexion', 'utilisateurs', NULL, NULL, NULL, 'Connexion réussie', '127.0.0.1', NULL, '2026-07-29 22:43:50');
INSERT INTO `logs_activites` VALUES ('3', '1', 'Création', 'eleves', NULL, NULL, NULL, 'Ajout des élèves de test', '127.0.0.1', NULL, '2026-07-29 22:43:50');
INSERT INTO `logs_activites` VALUES ('4', '1', 'Création', 'enseignants', NULL, NULL, NULL, 'Ajout des enseignants de test', '127.0.0.1', NULL, '2026-07-29 22:43:50');
INSERT INTO `logs_activites` VALUES ('5', '1', 'Création', 'inscriptions', NULL, NULL, NULL, 'Inscriptions des élèves', '127.0.0.1', NULL, '2026-07-29 22:43:50');
INSERT INTO `logs_activites` VALUES ('6', '5', 'Déconnexion', 'utilisateurs', '5', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:04:44');
INSERT INTO `logs_activites` VALUES ('7', NULL, 'Tentative échouée', 'utilisateurs', '1', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:05:02');
INSERT INTO `logs_activites` VALUES ('8', '5', 'Connexion', 'utilisateurs', '5', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:05:12');
INSERT INTO `logs_activites` VALUES ('9', '5', 'Déconnexion', 'utilisateurs', '5', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:06:26');
INSERT INTO `logs_activites` VALUES ('10', NULL, 'Création', 'utilisateurs', '13', NULL, NULL, 'Inscription de Parent Felix', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:07:25');
INSERT INTO `logs_activites` VALUES ('11', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:07:34');
INSERT INTO `logs_activites` VALUES ('12', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:08:01');
INSERT INTO `logs_activites` VALUES ('13', '6', 'Connexion', 'utilisateurs', '6', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:08:08');
INSERT INTO `logs_activites` VALUES ('14', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:18:09');
INSERT INTO `logs_activites` VALUES ('15', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:18:09');
INSERT INTO `logs_activites` VALUES ('16', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:22:56');
INSERT INTO `logs_activites` VALUES ('17', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:22:56');
INSERT INTO `logs_activites` VALUES ('18', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:22:57');
INSERT INTO `logs_activites` VALUES ('19', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:23:15');
INSERT INTO `logs_activites` VALUES ('20', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:23:15');
INSERT INTO `logs_activites` VALUES ('21', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:24:08');
INSERT INTO `logs_activites` VALUES ('22', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:24:08');
INSERT INTO `logs_activites` VALUES ('23', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:27:19');
INSERT INTO `logs_activites` VALUES ('24', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:27:19');
INSERT INTO `logs_activites` VALUES ('25', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:29:37');
INSERT INTO `logs_activites` VALUES ('26', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:29:38');
INSERT INTO `logs_activites` VALUES ('27', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:30:15');
INSERT INTO `logs_activites` VALUES ('28', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:30:15');
INSERT INTO `logs_activites` VALUES ('29', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:31:14');
INSERT INTO `logs_activites` VALUES ('30', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:31:15');
INSERT INTO `logs_activites` VALUES ('31', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:31:24');
INSERT INTO `logs_activites` VALUES ('32', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:31:24');
INSERT INTO `logs_activites` VALUES ('33', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:35:46');
INSERT INTO `logs_activites` VALUES ('34', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:35:46');
INSERT INTO `logs_activites` VALUES ('35', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:37:00');
INSERT INTO `logs_activites` VALUES ('36', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:37:00');
INSERT INTO `logs_activites` VALUES ('37', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:38:40');
INSERT INTO `logs_activites` VALUES ('38', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:38:40');
INSERT INTO `logs_activites` VALUES ('39', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:52:28');
INSERT INTO `logs_activites` VALUES ('40', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:52:28');
INSERT INTO `logs_activites` VALUES ('41', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:54:37');
INSERT INTO `logs_activites` VALUES ('42', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:54:37');
INSERT INTO `logs_activites` VALUES ('43', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:55:42');
INSERT INTO `logs_activites` VALUES ('44', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:55:43');
INSERT INTO `logs_activites` VALUES ('45', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:56:59');
INSERT INTO `logs_activites` VALUES ('46', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:56:59');
INSERT INTO `logs_activites` VALUES ('47', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:57:48');
INSERT INTO `logs_activites` VALUES ('48', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:57:48');
INSERT INTO `logs_activites` VALUES ('49', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:58:37');
INSERT INTO `logs_activites` VALUES ('50', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:58:37');
INSERT INTO `logs_activites` VALUES ('51', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:59:11');
INSERT INTO `logs_activites` VALUES ('52', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:59:11');
INSERT INTO `logs_activites` VALUES ('53', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:59:27');
INSERT INTO `logs_activites` VALUES ('54', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:59:27');
INSERT INTO `logs_activites` VALUES ('55', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:59:41');
INSERT INTO `logs_activites` VALUES ('56', '6', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : dashboard.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-29 23:59:41');
INSERT INTO `logs_activites` VALUES ('57', '6', 'Déconnexion', 'utilisateurs', '6', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:06:00');
INSERT INTO `logs_activites` VALUES ('58', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:06:30');
INSERT INTO `logs_activites` VALUES ('59', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:11:48');
INSERT INTO `logs_activites` VALUES ('60', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:12:01');
INSERT INTO `logs_activites` VALUES ('61', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:12:11');
INSERT INTO `logs_activites` VALUES ('62', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:14:47');
INSERT INTO `logs_activites` VALUES ('63', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:15:12');
INSERT INTO `logs_activites` VALUES ('64', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:15:18');
INSERT INTO `logs_activites` VALUES ('65', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:17:07');
INSERT INTO `logs_activites` VALUES ('66', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:17:17');
INSERT INTO `logs_activites` VALUES ('67', '6', 'Connexion', 'utilisateurs', '6', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:17:31');
INSERT INTO `logs_activites` VALUES ('68', '6', 'Déconnexion', 'utilisateurs', '6', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:18:55');
INSERT INTO `logs_activites` VALUES ('69', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:19:04');
INSERT INTO `logs_activites` VALUES ('70', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:23:32');
INSERT INTO `logs_activites` VALUES ('71', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:23:38');
INSERT INTO `logs_activites` VALUES ('72', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:23:43');
INSERT INTO `logs_activites` VALUES ('73', '6', 'Connexion', 'utilisateurs', '6', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:23:51');
INSERT INTO `logs_activites` VALUES ('74', '6', 'Déconnexion', 'utilisateurs', '6', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:29:42');
INSERT INTO `logs_activites` VALUES ('75', '6', 'Connexion', 'utilisateurs', '6', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:29:49');
INSERT INTO `logs_activites` VALUES ('76', '6', 'Déconnexion', 'utilisateurs', '6', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:30:58');
INSERT INTO `logs_activites` VALUES ('77', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:31:06');
INSERT INTO `logs_activites` VALUES ('78', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:40:38');
INSERT INTO `logs_activites` VALUES ('79', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:40:44');
INSERT INTO `logs_activites` VALUES ('80', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:40:51');
INSERT INTO `logs_activites` VALUES ('81', '5', 'Connexion', 'utilisateurs', '5', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:40:59');
INSERT INTO `logs_activites` VALUES ('82', '5', 'Déconnexion', 'utilisateurs', '5', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:59:21');
INSERT INTO `logs_activites` VALUES ('83', NULL, 'Tentative échouée', 'utilisateurs', '8', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 00:59:39');
INSERT INTO `logs_activites` VALUES ('84', '8', 'Connexion', 'utilisateurs', '8', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:05:23');
INSERT INTO `logs_activites` VALUES ('85', '8', 'Déconnexion', 'utilisateurs', '8', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:06:03');
INSERT INTO `logs_activites` VALUES ('86', '8', 'Connexion', 'utilisateurs', '8', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:06:30');
INSERT INTO `logs_activites` VALUES ('87', '8', 'Déconnexion', 'utilisateurs', '8', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:20:00');
INSERT INTO `logs_activites` VALUES ('88', '8', 'Connexion', 'utilisateurs', '8', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:20:08');
INSERT INTO `logs_activites` VALUES ('89', '8', 'Déconnexion', 'utilisateurs', '8', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:20:26');
INSERT INTO `logs_activites` VALUES ('90', NULL, 'Tentative échouée', 'utilisateurs', '1', NULL, NULL, 'Tentative 3/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:20:38');
INSERT INTO `logs_activites` VALUES ('91', NULL, 'Tentative échouée', 'utilisateurs', '1', NULL, NULL, 'Tentative 4/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:21:12');
INSERT INTO `logs_activites` VALUES ('92', NULL, 'Tentative échouée', 'utilisateurs', '1', NULL, NULL, 'Tentative 5/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:21:20');
INSERT INTO `logs_activites` VALUES ('93', '8', 'Connexion', 'utilisateurs', '8', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 10:33:08');
INSERT INTO `logs_activites` VALUES ('94', '8', 'Déconnexion', 'utilisateurs', '8', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:07:06');
INSERT INTO `logs_activites` VALUES ('95', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:07:16');
INSERT INTO `logs_activites` VALUES ('96', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:12:42');
INSERT INTO `logs_activites` VALUES ('97', '6', 'Connexion', 'utilisateurs', '6', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:12:52');
INSERT INTO `logs_activites` VALUES ('98', '6', 'Déconnexion', 'utilisateurs', '6', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:15:03');
INSERT INTO `logs_activites` VALUES ('99', NULL, 'Tentative échouée', 'utilisateurs', '12', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:15:45');
INSERT INTO `logs_activites` VALUES ('100', '8', 'Connexion', 'utilisateurs', '8', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:16:41');
INSERT INTO `logs_activites` VALUES ('101', '8', 'Déconnexion', 'utilisateurs', '8', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:17:40');
INSERT INTO `logs_activites` VALUES ('102', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:17:49');
INSERT INTO `logs_activites` VALUES ('103', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:20:30');
INSERT INTO `logs_activites` VALUES ('104', NULL, 'Tentative échouée', 'utilisateurs', '16', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:20:48');
INSERT INTO `logs_activites` VALUES ('105', '16', 'Connexion', 'utilisateurs', '16', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:22:49');
INSERT INTO `logs_activites` VALUES ('106', '16', 'Déconnexion', 'utilisateurs', '16', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:28:25');
INSERT INTO `logs_activites` VALUES ('107', NULL, 'Tentative échouée', 'utilisateurs', '17', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:28:30');
INSERT INTO `logs_activites` VALUES ('108', '17', 'Connexion', 'utilisateurs', '17', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:29:46');
INSERT INTO `logs_activites` VALUES ('109', '17', 'Déconnexion', 'utilisateurs', '17', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:37:53');
INSERT INTO `logs_activites` VALUES ('110', NULL, 'Tentative échouée', 'utilisateurs', '18', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:37:58');
INSERT INTO `logs_activites` VALUES ('111', '18', 'Connexion', 'utilisateurs', '18', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:39:11');
INSERT INTO `logs_activites` VALUES ('112', '18', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : utilisateurs.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:58:38');
INSERT INTO `logs_activites` VALUES ('113', '18', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : utilisateurs.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:59:08');
INSERT INTO `logs_activites` VALUES ('114', '18', 'Déconnexion', 'utilisateurs', '18', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:59:17');
INSERT INTO `logs_activites` VALUES ('115', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 11:59:30');
INSERT INTO `logs_activites` VALUES ('116', '14', 'Création', 'utilisateurs', '19', NULL, NULL, 'Création de l\'utilisateur Bonane Rutanga', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 12:04:23');
INSERT INTO `logs_activites` VALUES ('117', '14', 'Modification', 'roles', '3', NULL, NULL, 'Permissions mises à jour', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 12:30:11');
INSERT INTO `logs_activites` VALUES ('118', '14', 'Modification', 'roles', '3', NULL, NULL, 'Permissions mises à jour', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 12:30:25');
INSERT INTO `logs_activites` VALUES ('119', '14', 'Réinitialisation mot de passe', 'utilisateurs', '14', NULL, NULL, 'Changement de mot de passe par l\'utilisateur', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 12:52:42');
INSERT INTO `logs_activites` VALUES ('120', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 12:52:45');
INSERT INTO `logs_activites` VALUES ('121', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 12:52:57');
INSERT INTO `logs_activites` VALUES ('122', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 12:53:11');
INSERT INTO `logs_activites` VALUES ('123', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 12:53:22');
INSERT INTO `logs_activites` VALUES ('124', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 13:09:59');
INSERT INTO `logs_activites` VALUES ('125', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 13:10:05');
INSERT INTO `logs_activites` VALUES ('126', '6', 'Connexion', 'utilisateurs', '6', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 13:10:15');
INSERT INTO `logs_activites` VALUES ('127', '6', 'Déconnexion', 'utilisateurs', '6', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 14:50:43');
INSERT INTO `logs_activites` VALUES ('128', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 14:50:56');
INSERT INTO `logs_activites` VALUES ('129', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 3/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 14:51:06');
INSERT INTO `logs_activites` VALUES ('130', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 14:51:15');
INSERT INTO `logs_activites` VALUES ('131', '14', 'Création', 'utilisateurs', '20', NULL, NULL, 'Création de l\'utilisateur Tufurahi Amijay (rôle: 6)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 15:52:59');
INSERT INTO `logs_activites` VALUES ('132', '14', 'Création', 'classes', '13', NULL, NULL, 'Classe créée : 6ème B', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 16:28:54');
INSERT INTO `logs_activites` VALUES ('133', '14', 'Modification', 'classes', '13', NULL, NULL, 'Classe modifiée : 6Ème B', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 16:38:17');
INSERT INTO `logs_activites` VALUES ('134', '14', 'Modification', 'classes', '13', NULL, NULL, 'Classe modifiée : 6Ème B', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 16:40:19');
INSERT INTO `logs_activites` VALUES ('135', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 16:52:30');
INSERT INTO `logs_activites` VALUES ('136', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 16:52:41');
INSERT INTO `logs_activites` VALUES ('137', '14', 'Création', 'eleves', '91', NULL, NULL, 'Élève créé : Baraka Ntwali', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 16:59:23');
INSERT INTO `logs_activites` VALUES ('138', '14', 'Modification', 'eleves', '91', NULL, NULL, 'Élève modifié : Baraka Ntwali', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 17:19:50');
INSERT INTO `logs_activites` VALUES ('139', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 19:42:32');
INSERT INTO `logs_activites` VALUES ('140', '14', 'Import', 'eleves', NULL, NULL, NULL, 'Import CSV : 1 élève(s) importé(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 20:04:00');
INSERT INTO `logs_activites` VALUES ('141', '14', 'Import', 'eleves', NULL, NULL, NULL, 'Import CSV : 4 élève(s) importé(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 20:06:36');
INSERT INTO `logs_activites` VALUES ('142', '14', 'Import', 'eleves', NULL, NULL, NULL, 'Import CSV : 10 élève(s) importé(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 20:09:43');
INSERT INTO `logs_activites` VALUES ('143', '14', 'Création', 'parents_tuteurs', '48', NULL, NULL, 'Parent créé : Baraka Baba', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 21:11:12');
INSERT INTO `logs_activites` VALUES ('144', '14', 'Modification', 'parents_tuteurs', '48', NULL, NULL, 'Parent modifié : Baraka Baba', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 21:16:09');
INSERT INTO `logs_activites` VALUES ('145', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 21:43:45');
INSERT INTO `logs_activites` VALUES ('146', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 21:43:55');
INSERT INTO `logs_activites` VALUES ('147', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 21:44:01');
INSERT INTO `logs_activites` VALUES ('148', '14', 'Création', 'parents_tuteurs', '49', NULL, NULL, 'Parent créé : josias kub', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:00:10');
INSERT INTO `logs_activites` VALUES ('149', '14', '', 'eleve_parent', NULL, NULL, NULL, 'Élève 80 lié au parent 49', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:00:47');
INSERT INTO `logs_activites` VALUES ('150', '14', '', 'eleve_parent', NULL, NULL, NULL, 'Élève 66 lié au parent 49', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:05:35');
INSERT INTO `logs_activites` VALUES ('151', '14', 'Modification', 'parents_tuteurs', '49', NULL, NULL, 'Parent modifié : Josias Kub', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:07:35');
INSERT INTO `logs_activites` VALUES ('152', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:14:03');
INSERT INTO `logs_activites` VALUES ('153', '21', 'Connexion', 'utilisateurs', '21', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:14:36');
INSERT INTO `logs_activites` VALUES ('154', '21', 'Déconnexion', 'utilisateurs', '21', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:15:29');
INSERT INTO `logs_activites` VALUES ('155', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:15:45');
INSERT INTO `logs_activites` VALUES ('156', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:15:51');
INSERT INTO `logs_activites` VALUES ('157', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 3/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:15:57');
INSERT INTO `logs_activites` VALUES ('158', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 4/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:16:07');
INSERT INTO `logs_activites` VALUES ('159', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:16:14');
INSERT INTO `logs_activites` VALUES ('160', '14', 'Création', 'enseignants', '11', NULL, NULL, 'Enseignant créé : bienve charles', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:40:01');
INSERT INTO `logs_activites` VALUES ('161', '14', 'Création', 'classes', '14', NULL, NULL, 'Classe créée : 2B', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:41:54');
INSERT INTO `logs_activites` VALUES ('162', '14', 'Modification', 'enseignants', '11', NULL, NULL, 'Enseignant modifié : Bienve Charles', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-30 22:43:32');
INSERT INTO `logs_activites` VALUES ('163', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 00:17:03');
INSERT INTO `logs_activites` VALUES ('164', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 00:17:16');
INSERT INTO `logs_activites` VALUES ('165', '14', 'Création', 'matieres', '35', NULL, NULL, 'Matière créée : Environnement', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 00:22:09');
INSERT INTO `logs_activites` VALUES ('166', '14', 'Création', 'matieres', '36', NULL, NULL, 'Matière créée : ESE', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 00:23:16');
INSERT INTO `logs_activites` VALUES ('167', '14', 'Création', 'matieres', '37', NULL, NULL, 'Matière créée : ECM', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 00:24:04');
INSERT INTO `logs_activites` VALUES ('168', '14', 'Création', 'matieres', '38', NULL, NULL, 'Matière créée : Civisme', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 00:43:03');
INSERT INTO `logs_activites` VALUES ('169', '14', 'Modification', 'matieres', '37', NULL, NULL, 'Matière modifiée : Ecm', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 00:43:41');
INSERT INTO `logs_activites` VALUES ('170', '14', 'Création', 'annees_scolaires', '3', NULL, NULL, 'Année créée : 2027-2028', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:04:22');
INSERT INTO `logs_activites` VALUES ('171', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:40:10');
INSERT INTO `logs_activites` VALUES ('172', '6', 'Connexion', 'utilisateurs', '6', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:40:20');
INSERT INTO `logs_activites` VALUES ('173', '6', 'Déconnexion', 'utilisateurs', '6', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:44:41');
INSERT INTO `logs_activites` VALUES ('174', '20', 'Connexion', 'utilisateurs', '20', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:44:57');
INSERT INTO `logs_activites` VALUES ('175', '20', 'Déconnexion', 'utilisateurs', '20', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:45:11');
INSERT INTO `logs_activites` VALUES ('176', '19', 'Connexion', 'utilisateurs', '19', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:45:17');
INSERT INTO `logs_activites` VALUES ('177', '19', 'Déconnexion', 'utilisateurs', '19', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:45:25');
INSERT INTO `logs_activites` VALUES ('178', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:45:40');
INSERT INTO `logs_activites` VALUES ('179', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 01:45:45');
INSERT INTO `logs_activites` VALUES ('180', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 15:36:39');
INSERT INTO `logs_activites` VALUES ('181', '14', '', 'notes', NULL, NULL, NULL, '3 note(s) pour évaluation 50', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 16:37:34');
INSERT INTO `logs_activites` VALUES ('182', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 17:49:19');
INSERT INTO `logs_activites` VALUES ('183', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 22:28:57');
INSERT INTO `logs_activites` VALUES ('184', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 22:29:05');
INSERT INTO `logs_activites` VALUES ('185', '14', 'Création', 'evaluations', '113', NULL, NULL, 'Évaluation créée : Période 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 23:17:16');
INSERT INTO `logs_activites` VALUES ('186', '14', '', 'notes', NULL, NULL, NULL, '2 note(s) pour évaluation 113', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 23:17:50');
INSERT INTO `logs_activites` VALUES ('187', '14', '', 'notes', NULL, NULL, NULL, '4 note(s) pour évaluation 113', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-07-31 23:18:32');
INSERT INTO `logs_activites` VALUES ('188', '14', 'Création', 'evaluations', '114', NULL, NULL, 'Évaluation créée : Période 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 00:22:21');
INSERT INTO `logs_activites` VALUES ('189', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 00:31:31');
INSERT INTO `logs_activites` VALUES ('190', '14', 'Création', 'evaluations', '115', NULL, NULL, 'Évaluation créée : Période 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 00:37:20');
INSERT INTO `logs_activites` VALUES ('191', '14', 'Création', 'evaluations', '116', NULL, NULL, 'Évaluation créée : Période 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 00:42:35');
INSERT INTO `logs_activites` VALUES ('192', '14', 'Création', 'evaluations', '117', NULL, NULL, 'Évaluation créée : Période 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 00:43:32');
INSERT INTO `logs_activites` VALUES ('193', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 02:31:58');
INSERT INTO `logs_activites` VALUES ('194', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 03:23:47');
INSERT INTO `logs_activites` VALUES ('195', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 14', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 03:54:56');
INSERT INTO `logs_activites` VALUES ('196', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 03:55:08');
INSERT INTO `logs_activites` VALUES ('197', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 15:25:17');
INSERT INTO `logs_activites` VALUES ('198', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 15:50:42');
INSERT INTO `logs_activites` VALUES ('199', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 15:55:15');
INSERT INTO `logs_activites` VALUES ('200', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 14', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 15:58:33');
INSERT INTO `logs_activites` VALUES ('201', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 15:58:43');
INSERT INTO `logs_activites` VALUES ('202', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 14', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:00:37');
INSERT INTO `logs_activites` VALUES ('203', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:00:48');
INSERT INTO `logs_activites` VALUES ('204', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:25:25');
INSERT INTO `logs_activites` VALUES ('205', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 14', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:25:32');
INSERT INTO `logs_activites` VALUES ('206', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:25:40');
INSERT INTO `logs_activites` VALUES ('207', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:29:09');
INSERT INTO `logs_activites` VALUES ('208', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 14', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:29:17');
INSERT INTO `logs_activites` VALUES ('209', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:29:24');
INSERT INTO `logs_activites` VALUES ('210', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:48:57');
INSERT INTO `logs_activites` VALUES ('211', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 14', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:49:08');
INSERT INTO `logs_activites` VALUES ('212', '14', '', 'resultats_trimestriels', NULL, NULL, NULL, 'Résultats calculés pour classe 21, trimestre 15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 16:49:16');
INSERT INTO `logs_activites` VALUES ('213', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:29:56');
INSERT INTO `logs_activites` VALUES ('214', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:31:55');
INSERT INTO `logs_activites` VALUES ('215', NULL, 'Tentative échouée', 'utilisateurs', '14', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:32:02');
INSERT INTO `logs_activites` VALUES ('216', '13', 'Connexion', 'utilisateurs', '13', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:32:12');
INSERT INTO `logs_activites` VALUES ('217', '13', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : bulletins.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:32:24');
INSERT INTO `logs_activites` VALUES ('218', '13', 'Déconnexion', 'utilisateurs', '13', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:36:43');
INSERT INTO `logs_activites` VALUES ('219', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:36:55');
INSERT INTO `logs_activites` VALUES ('220', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:44:04');
INSERT INTO `logs_activites` VALUES ('221', NULL, 'Tentative échouée', NULL, NULL, NULL, NULL, 'Tentative de connexion avec email: charles@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:44:31');
INSERT INTO `logs_activites` VALUES ('222', NULL, 'Tentative échouée', 'utilisateurs', '23', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:44:49');
INSERT INTO `logs_activites` VALUES ('223', NULL, 'Tentative échouée', NULL, NULL, NULL, NULL, 'Tentative de connexion avec email: Charles@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:45:12');
INSERT INTO `logs_activites` VALUES ('224', NULL, 'Tentative échouée', NULL, NULL, NULL, NULL, 'Tentative de connexion avec email: Charles@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:45:39');
INSERT INTO `logs_activites` VALUES ('225', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:45:47');
INSERT INTO `logs_activites` VALUES ('226', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:46:18');
INSERT INTO `logs_activites` VALUES ('227', NULL, 'Tentative échouée', 'utilisateurs', '23', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:46:36');
INSERT INTO `logs_activites` VALUES ('228', '22', 'Connexion', 'utilisateurs', '22', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 17:46:56');
INSERT INTO `logs_activites` VALUES ('229', '22', 'Déconnexion', 'utilisateurs', '22', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 18:09:06');
INSERT INTO `logs_activites` VALUES ('230', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 18:09:17');
INSERT INTO `logs_activites` VALUES ('231', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 20:11:03');
INSERT INTO `logs_activites` VALUES ('232', '14', '', 'absences', '415', NULL, NULL, 'Absence justifiée directement', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 20:23:58');
INSERT INTO `logs_activites` VALUES ('233', '14', 'Création', 'retards', '42', NULL, NULL, 'Retard de KABONGO Grâce — 30 min', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 20:45:26');
INSERT INTO `logs_activites` VALUES ('234', '14', 'Création', 'retards', '43', NULL, NULL, 'Retard de LUKUSA Emmanuel — 150 min', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 20:45:47');
INSERT INTO `logs_activites` VALUES ('235', '14', '', 'paiements', '141', NULL, NULL, 'Paiement de 30 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 21:54:05');
INSERT INTO `logs_activites` VALUES ('236', '14', '', 'paiements', '141', NULL, NULL, 'Paiement de 30 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 21:54:05');
INSERT INTO `logs_activites` VALUES ('237', '14', 'Création', 'frais_scolaires', '11', NULL, NULL, 'Frais créé : MON DROIT', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:04:27');
INSERT INTO `logs_activites` VALUES ('238', '14', 'Modification', 'frais_scolaires', '11', NULL, NULL, 'Frais modifié : Mon Droit', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:04:43');
INSERT INTO `logs_activites` VALUES ('239', '14', 'Modification', 'frais_scolaires', '11', NULL, NULL, 'Frais modifié : Mon Droit', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:06:28');
INSERT INTO `logs_activites` VALUES ('240', '14', 'Modification', 'frais_scolaires', '10', NULL, NULL, 'Frais modifié : Assurance scolaire', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:06:41');
INSERT INTO `logs_activites` VALUES ('241', '14', 'Modification', 'frais_scolaires', '11', NULL, NULL, 'Frais modifié : Mon Droit', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:07:24');
INSERT INTO `logs_activites` VALUES ('242', '14', 'Modification', 'frais_scolaires', '10', NULL, NULL, 'Frais modifié : Assurance Scolaire', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:07:53');
INSERT INTO `logs_activites` VALUES ('243', '14', 'Suppression', 'frais_scolaires', '11', NULL, NULL, 'Frais supprimé', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:08:19');
INSERT INTO `logs_activites` VALUES ('244', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:12:06');
INSERT INTO `logs_activites` VALUES ('245', '14', 'Modification', 'frais_scolaires', '10', NULL, NULL, 'Frais modifié : Assurance Scolaire', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:12:29');
INSERT INTO `logs_activites` VALUES ('246', '14', 'Création', 'frais_scolaires', '12', NULL, NULL, 'Frais créé : MON DON', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:13:52');
INSERT INTO `logs_activites` VALUES ('247', '14', 'Suppression', 'frais_scolaires', '12', NULL, NULL, 'Frais supprimé', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:14:38');
INSERT INTO `logs_activites` VALUES ('248', '14', 'Création', 'frais_scolaires', '13', NULL, NULL, 'Frais créé : MON DON', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:15:01');
INSERT INTO `logs_activites` VALUES ('249', '14', 'Modification', 'frais_scolaires', '13', NULL, NULL, 'Frais modifié : Mon Don', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:18:35');
INSERT INTO `logs_activites` VALUES ('250', '14', 'Création', 'frais_scolaires', '14', NULL, NULL, 'Frais créé : JHFGG', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:18:56');
INSERT INTO `logs_activites` VALUES ('251', '14', 'Création', 'frais_scolaires', '15', NULL, NULL, 'Frais créé : Frais scolaire', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:21:26');
INSERT INTO `logs_activites` VALUES ('252', '14', 'Création', 'frais_scolaires', '16', NULL, NULL, 'Frais créé : Inscription', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:21:58');
INSERT INTO `logs_activites` VALUES ('253', '14', '', 'paiements', '142', NULL, NULL, 'Paiement de 100 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:22:54');
INSERT INTO `logs_activites` VALUES ('254', '14', '', 'paiements', '142', NULL, NULL, 'Paiement de 100 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:22:55');
INSERT INTO `logs_activites` VALUES ('255', '14', '', 'paiements', '143', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:24:19');
INSERT INTO `logs_activites` VALUES ('256', '14', '', 'paiements', '143', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:24:19');
INSERT INTO `logs_activites` VALUES ('257', '14', '', 'paiements', '143', NULL, NULL, 'Paiement de 50 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:27:06');
INSERT INTO `logs_activites` VALUES ('258', '14', '', 'paiements', '143', NULL, NULL, 'Paiement de 50 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:27:06');
INSERT INTO `logs_activites` VALUES ('259', '14', '', 'paiements', '143', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:28:07');
INSERT INTO `logs_activites` VALUES ('260', '14', '', 'paiements', '143', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:28:07');
INSERT INTO `logs_activites` VALUES ('261', '14', '', 'paiements', '144', NULL, NULL, 'Paiement de 30 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:31:37');
INSERT INTO `logs_activites` VALUES ('262', '14', '', 'paiements', '144', NULL, NULL, 'Paiement de 30 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:31:38');
INSERT INTO `logs_activites` VALUES ('263', '14', '', 'paiements', '144', NULL, NULL, 'Paiement de 40 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:32:41');
INSERT INTO `logs_activites` VALUES ('264', '14', '', 'paiements', '144', NULL, NULL, 'Paiement de 40 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-01 22:32:41');
INSERT INTO `logs_activites` VALUES ('265', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:14:06');
INSERT INTO `logs_activites` VALUES ('266', '14', '', 'paiements', '144', NULL, NULL, 'Paiement de 70 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:30:13');
INSERT INTO `logs_activites` VALUES ('267', '14', '', 'paiements', '144', NULL, NULL, 'Paiement de 70 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:30:14');
INSERT INTO `logs_activites` VALUES ('268', '14', '', 'paiements', '145', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:31:00');
INSERT INTO `logs_activites` VALUES ('269', '14', '', 'paiements', '145', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:31:01');
INSERT INTO `logs_activites` VALUES ('270', '14', '', 'paiements', '145', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:33:58');
INSERT INTO `logs_activites` VALUES ('271', '14', '', 'paiements', '145', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:33:58');
INSERT INTO `logs_activites` VALUES ('272', '14', '', 'paiements', '144', NULL, NULL, 'Paiement de 70 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:45:59');
INSERT INTO `logs_activites` VALUES ('273', '14', '', 'paiements', '146', NULL, NULL, 'Paiement de 10 — Espèces', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 10:49:11');
INSERT INTO `logs_activites` VALUES ('274', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 12:52:35');
INSERT INTO `logs_activites` VALUES ('275', '14', '', 'notifications', NULL, NULL, NULL, '5 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 13:42:31');
INSERT INTO `logs_activites` VALUES ('276', '14', '', 'notifications', NULL, NULL, NULL, '5 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 13:42:32');
INSERT INTO `logs_activites` VALUES ('277', '14', '', 'notifications', NULL, NULL, NULL, '5 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 13:45:38');
INSERT INTO `logs_activites` VALUES ('278', '14', '', 'notifications', NULL, NULL, NULL, '5 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 13:45:38');
INSERT INTO `logs_activites` VALUES ('279', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 13:57:45');
INSERT INTO `logs_activites` VALUES ('280', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 13:57:45');
INSERT INTO `logs_activites` VALUES ('281', '14', '', 'notifications', NULL, NULL, NULL, '5 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 13:58:41');
INSERT INTO `logs_activites` VALUES ('282', '14', '', 'notifications', NULL, NULL, NULL, '5 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 13:58:44');
INSERT INTO `logs_activites` VALUES ('283', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 14:34:07');
INSERT INTO `logs_activites` VALUES ('284', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 17:25:51');
INSERT INTO `logs_activites` VALUES ('285', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 17:26:01');
INSERT INTO `logs_activites` VALUES ('286', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 17:28:26');
INSERT INTO `logs_activites` VALUES ('287', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 17:30:21');
INSERT INTO `logs_activites` VALUES ('288', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 17:30:24');
INSERT INTO `logs_activites` VALUES ('289', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 17:31:03');
INSERT INTO `logs_activites` VALUES ('290', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 17:54:35');
INSERT INTO `logs_activites` VALUES ('291', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 17:57:10');
INSERT INTO `logs_activites` VALUES ('292', NULL, '', 'utilisateurs', '36', NULL, NULL, 'Nouvelle inscription Google : barakantwali06@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:04:08');
INSERT INTO `logs_activites` VALUES ('293', '36', '', 'utilisateurs', '36', NULL, NULL, 'Connexion réussie via Google OAuth', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:04:08');
INSERT INTO `logs_activites` VALUES ('294', '36', 'Déconnexion', 'utilisateurs', '36', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:04:50');
INSERT INTO `logs_activites` VALUES ('295', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:04:55');
INSERT INTO `logs_activites` VALUES ('296', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:06:50');
INSERT INTO `logs_activites` VALUES ('297', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:06:54');
INSERT INTO `logs_activites` VALUES ('298', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:33:05');
INSERT INTO `logs_activites` VALUES ('299', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:33:12');
INSERT INTO `logs_activites` VALUES ('300', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:33:20');
INSERT INTO `logs_activites` VALUES ('301', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:33:27');
INSERT INTO `logs_activites` VALUES ('302', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:33:35');
INSERT INTO `logs_activites` VALUES ('303', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:34:03');
INSERT INTO `logs_activites` VALUES ('304', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:34:03');
INSERT INTO `logs_activites` VALUES ('305', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:35:47');
INSERT INTO `logs_activites` VALUES ('306', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:35:55');
INSERT INTO `logs_activites` VALUES ('307', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:36:01');
INSERT INTO `logs_activites` VALUES ('308', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:40:31');
INSERT INTO `logs_activites` VALUES ('309', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:40:31');
INSERT INTO `logs_activites` VALUES ('310', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:41:10');
INSERT INTO `logs_activites` VALUES ('311', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:41:17');
INSERT INTO `logs_activites` VALUES ('312', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:56:07');
INSERT INTO `logs_activites` VALUES ('313', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 19:56:15');
INSERT INTO `logs_activites` VALUES ('314', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 20:04:05');
INSERT INTO `logs_activites` VALUES ('315', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 20:04:05');
INSERT INTO `logs_activites` VALUES ('316', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 20:04:48');
INSERT INTO `logs_activites` VALUES ('317', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 20:04:56');
INSERT INTO `logs_activites` VALUES ('318', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 20:13:44');
INSERT INTO `logs_activites` VALUES ('319', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 20:13:52');
INSERT INTO `logs_activites` VALUES ('320', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:13:59');
INSERT INTO `logs_activites` VALUES ('321', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:15:06');
INSERT INTO `logs_activites` VALUES ('322', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:15:10');
INSERT INTO `logs_activites` VALUES ('323', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:24:28');
INSERT INTO `logs_activites` VALUES ('324', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:24:35');
INSERT INTO `logs_activites` VALUES ('325', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:48:24');
INSERT INTO `logs_activites` VALUES ('326', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:48:30');
INSERT INTO `logs_activites` VALUES ('327', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:56:22');
INSERT INTO `logs_activites` VALUES ('328', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:56:27');
INSERT INTO `logs_activites` VALUES ('329', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:57:47');
INSERT INTO `logs_activites` VALUES ('330', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:57:48');
INSERT INTO `logs_activites` VALUES ('331', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:58:23');
INSERT INTO `logs_activites` VALUES ('332', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:58:28');
INSERT INTO `logs_activites` VALUES ('333', '14', '', 'notifications', NULL, NULL, NULL, '5 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:59:27');
INSERT INTO `logs_activites` VALUES ('334', '14', '', 'notifications', NULL, NULL, NULL, '5 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 21:59:59');
INSERT INTO `logs_activites` VALUES ('335', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:02:21');
INSERT INTO `logs_activites` VALUES ('336', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:02:29');
INSERT INTO `logs_activites` VALUES ('337', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:08:22');
INSERT INTO `logs_activites` VALUES ('338', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:08:28');
INSERT INTO `logs_activites` VALUES ('339', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:12:56');
INSERT INTO `logs_activites` VALUES ('340', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:14:37');
INSERT INTO `logs_activites` VALUES ('341', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:19:09');
INSERT INTO `logs_activites` VALUES ('342', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:20:32');
INSERT INTO `logs_activites` VALUES ('343', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:43:38');
INSERT INTO `logs_activites` VALUES ('344', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:44:15');
INSERT INTO `logs_activites` VALUES ('345', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:49:16');
INSERT INTO `logs_activites` VALUES ('346', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:55:25');
INSERT INTO `logs_activites` VALUES ('347', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:57:51');
INSERT INTO `logs_activites` VALUES ('348', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:57:56');
INSERT INTO `logs_activites` VALUES ('349', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:58:27');
INSERT INTO `logs_activites` VALUES ('350', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 22:58:32');
INSERT INTO `logs_activites` VALUES ('351', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:00:05');
INSERT INTO `logs_activites` VALUES ('352', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:00:06');
INSERT INTO `logs_activites` VALUES ('353', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:00:46');
INSERT INTO `logs_activites` VALUES ('354', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:00:52');
INSERT INTO `logs_activites` VALUES ('355', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:10:36');
INSERT INTO `logs_activites` VALUES ('356', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:21:01');
INSERT INTO `logs_activites` VALUES ('357', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s) avec succès. 1 email(s) envoyé(s).', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:25:59');
INSERT INTO `logs_activites` VALUES ('358', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s) avec succès. 1 email(s) envoyé(s).', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:34:17');
INSERT INTO `logs_activites` VALUES ('359', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s) avec succès. 1 email(s) envoyé(s).', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:37:46');
INSERT INTO `logs_activites` VALUES ('360', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s) avec succès. 1 email(s) envoyé(s).', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:40:19');
INSERT INTO `logs_activites` VALUES ('361', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s) avec succès. 1 email(s) envoyé(s).', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:43:16');
INSERT INTO `logs_activites` VALUES ('362', '14', '', 'notifications', NULL, NULL, NULL, '1 notification(s) envoyée(s) avec succès. 1 email(s) envoyé(s).', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-02 23:46:30');
INSERT INTO `logs_activites` VALUES ('363', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 20:43:02');
INSERT INTO `logs_activites` VALUES ('364', NULL, 'Tentative échouée', 'utilisateurs', '7', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:22:07');
INSERT INTO `logs_activites` VALUES ('365', NULL, 'Tentative échouée', 'utilisateurs', '7', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:22:16');
INSERT INTO `logs_activites` VALUES ('366', '20', 'Connexion', 'utilisateurs', '20', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:22:28');
INSERT INTO `logs_activites` VALUES ('367', '20', 'Déconnexion', 'utilisateurs', '20', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:23:18');
INSERT INTO `logs_activites` VALUES ('368', NULL, 'Tentative échouée', 'utilisateurs', '29', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:23:34');
INSERT INTO `logs_activites` VALUES ('369', NULL, 'Tentative échouée', 'utilisateurs', '29', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:25:37');
INSERT INTO `logs_activites` VALUES ('370', NULL, 'Tentative échouée', 'utilisateurs', '30', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:26:30');
INSERT INTO `logs_activites` VALUES ('371', NULL, 'Tentative échouée', 'utilisateurs', '30', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:26:53');
INSERT INTO `logs_activites` VALUES ('372', NULL, 'Tentative échouée', 'utilisateurs', '30', NULL, NULL, 'Tentative 3/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:27:42');
INSERT INTO `logs_activites` VALUES ('373', '7', 'Connexion', 'utilisateurs', '7', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:27:58');
INSERT INTO `logs_activites` VALUES ('374', '7', 'Déconnexion', 'utilisateurs', '7', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:54:20');
INSERT INTO `logs_activites` VALUES ('375', '7', 'Connexion', 'utilisateurs', '7', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:54:39');
INSERT INTO `logs_activites` VALUES ('376', '7', 'Déconnexion', 'utilisateurs', '7', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:54:50');
INSERT INTO `logs_activites` VALUES ('377', '7', 'Connexion', 'utilisateurs', '7', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-03 23:55:28');
INSERT INTO `logs_activites` VALUES ('378', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : eleves.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:23:18');
INSERT INTO `logs_activites` VALUES ('379', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:25:06');
INSERT INTO `logs_activites` VALUES ('380', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : notes.saisir', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:25:08');
INSERT INTO `logs_activites` VALUES ('381', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : notes.saisir', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:25:11');
INSERT INTO `logs_activites` VALUES ('382', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : notes.saisir', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:25:33');
INSERT INTO `logs_activites` VALUES ('383', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:30:38');
INSERT INTO `logs_activites` VALUES ('384', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:31:09');
INSERT INTO `logs_activites` VALUES ('385', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:31:24');
INSERT INTO `logs_activites` VALUES ('386', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:31:31');
INSERT INTO `logs_activites` VALUES ('387', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:32:06');
INSERT INTO `logs_activites` VALUES ('388', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:32:10');
INSERT INTO `logs_activites` VALUES ('389', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : retards.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:32:12');
INSERT INTO `logs_activites` VALUES ('390', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:32:15');
INSERT INTO `logs_activites` VALUES ('391', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:35:32');
INSERT INTO `logs_activites` VALUES ('392', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:35:41');
INSERT INTO `logs_activites` VALUES ('393', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:36:44');
INSERT INTO `logs_activites` VALUES ('394', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : notes.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:40:37');
INSERT INTO `logs_activites` VALUES ('395', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:40:44');
INSERT INTO `logs_activites` VALUES ('396', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:40:47');
INSERT INTO `logs_activites` VALUES ('397', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : retards.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:40:56');
INSERT INTO `logs_activites` VALUES ('398', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:40:57');
INSERT INTO `logs_activites` VALUES ('399', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : resultats.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:41:01');
INSERT INTO `logs_activites` VALUES ('400', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : eleves.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:41:45');
INSERT INTO `logs_activites` VALUES ('401', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : bulletins.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:41:47');
INSERT INTO `logs_activites` VALUES ('402', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:41:52');
INSERT INTO `logs_activites` VALUES ('403', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:42:01');
INSERT INTO `logs_activites` VALUES ('404', '7', 'Déconnexion', 'utilisateurs', '7', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:43:35');
INSERT INTO `logs_activites` VALUES ('405', '7', 'Connexion', 'utilisateurs', '7', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:43:48');
INSERT INTO `logs_activites` VALUES ('406', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:43:51');
INSERT INTO `logs_activites` VALUES ('407', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:49:43');
INSERT INTO `logs_activites` VALUES ('408', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:50:22');
INSERT INTO `logs_activites` VALUES ('409', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : eleves.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:51:45');
INSERT INTO `logs_activites` VALUES ('410', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 00:51:54');
INSERT INTO `logs_activites` VALUES ('411', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : notes.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:01:34');
INSERT INTO `logs_activites` VALUES ('412', '7', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : absences.marquer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:01:36');
INSERT INTO `logs_activites` VALUES ('413', '7', 'Déconnexion', 'utilisateurs', '7', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:01:41');
INSERT INTO `logs_activites` VALUES ('414', '7', 'Connexion', 'utilisateurs', '7', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:01:50');
INSERT INTO `logs_activites` VALUES ('415', '7', 'Création', 'eleves', '113', NULL, NULL, 'Élève créé : hhhhh hhhhhhhh', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:04:59');
INSERT INTO `logs_activites` VALUES ('416', '7', 'Déconnexion', 'utilisateurs', '7', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:21:18');
INSERT INTO `logs_activites` VALUES ('417', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:21:28');
INSERT INTO `logs_activites` VALUES ('418', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:21:52');
INSERT INTO `logs_activites` VALUES ('419', '7', 'Connexion', 'utilisateurs', '7', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:22:08');
INSERT INTO `logs_activites` VALUES ('420', '7', 'Déconnexion', 'utilisateurs', '7', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:29:47');
INSERT INTO `logs_activites` VALUES ('421', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:29:55');
INSERT INTO `logs_activites` VALUES ('422', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:30:12');
INSERT INTO `logs_activites` VALUES ('423', '7', 'Connexion', 'utilisateurs', '7', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:30:27');
INSERT INTO `logs_activites` VALUES ('424', '7', 'Déconnexion', 'utilisateurs', '7', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:40:34');
INSERT INTO `logs_activites` VALUES ('425', NULL, 'Tentative échouée', 'utilisateurs', '30', NULL, NULL, 'Tentative 4/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:41:02');
INSERT INTO `logs_activites` VALUES ('426', NULL, 'Tentative échouée', 'utilisateurs', '30', NULL, NULL, 'Tentative 5/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:41:11');
INSERT INTO `logs_activites` VALUES ('427', NULL, 'Tentative échouée', 'utilisateurs', '31', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:41:48');
INSERT INTO `logs_activites` VALUES ('428', '24', 'Connexion', 'utilisateurs', '24', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:45:39');
INSERT INTO `logs_activites` VALUES ('429', '24', 'Déconnexion', 'utilisateurs', '24', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:49:08');
INSERT INTO `logs_activites` VALUES ('430', NULL, 'Tentative échouée', 'utilisateurs', '33', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:50:27');
INSERT INTO `logs_activites` VALUES ('431', NULL, 'Tentative échouée', 'utilisateurs', '25', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:53:00');
INSERT INTO `logs_activites` VALUES ('432', '24', 'Connexion', 'utilisateurs', '24', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 01:53:12');
INSERT INTO `logs_activites` VALUES ('433', '24', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : eleves.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:01:28');
INSERT INTO `logs_activites` VALUES ('434', '24', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : eleves.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:01:43');
INSERT INTO `logs_activites` VALUES ('435', '24', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : eleves.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:02:28');
INSERT INTO `logs_activites` VALUES ('436', '24', 'Déconnexion', 'utilisateurs', '24', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:06:13');
INSERT INTO `logs_activites` VALUES ('437', NULL, 'Tentative échouée', 'utilisateurs', '29', NULL, NULL, 'Tentative 3/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:06:28');
INSERT INTO `logs_activites` VALUES ('438', NULL, 'Tentative échouée', 'utilisateurs', '29', NULL, NULL, 'Tentative 4/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:06:52');
INSERT INTO `logs_activites` VALUES ('439', NULL, 'Tentative échouée', 'utilisateurs', '29', NULL, NULL, 'Tentative 5/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:08:15');
INSERT INTO `logs_activites` VALUES ('440', NULL, 'Tentative échouée', 'utilisateurs', '31', NULL, NULL, 'Tentative 2/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:09:54');
INSERT INTO `logs_activites` VALUES ('441', NULL, 'Tentative échouée', 'utilisateurs', '31', NULL, NULL, 'Tentative 3/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:12:12');
INSERT INTO `logs_activites` VALUES ('442', '31', 'Connexion', 'utilisateurs', '31', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:13:33');
INSERT INTO `logs_activites` VALUES ('443', '31', 'Déconnexion', 'utilisateurs', '31', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:14:49');
INSERT INTO `logs_activites` VALUES ('444', '30', 'Connexion', 'utilisateurs', '30', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:15:10');
INSERT INTO `logs_activites` VALUES ('445', '30', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : retards.enregistrer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:23:02');
INSERT INTO `logs_activites` VALUES ('446', '30', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : retards.enregistrer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:23:50');
INSERT INTO `logs_activites` VALUES ('447', '30', 'Déconnexion', 'utilisateurs', '30', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:28:27');
INSERT INTO `logs_activites` VALUES ('448', NULL, 'Tentative échouée', 'utilisateurs', '4', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:28:49');
INSERT INTO `logs_activites` VALUES ('449', '4', 'Connexion', 'utilisateurs', '4', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:30:38');
INSERT INTO `logs_activites` VALUES ('450', '4', 'Déconnexion', 'utilisateurs', '4', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:57:29');
INSERT INTO `logs_activites` VALUES ('451', '2', 'Connexion', 'utilisateurs', '2', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 02:57:38');
INSERT INTO `logs_activites` VALUES ('452', '2', 'Déconnexion', 'utilisateurs', '2', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 03:01:26');
INSERT INTO `logs_activites` VALUES ('453', NULL, 'Tentative échouée', 'utilisateurs', '3', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 03:01:49');
INSERT INTO `logs_activites` VALUES ('454', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 03:02:20');
INSERT INTO `logs_activites` VALUES ('455', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:01:48');
INSERT INTO `logs_activites` VALUES ('456', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:02:15');
INSERT INTO `logs_activites` VALUES ('457', '2', 'Connexion', 'utilisateurs', '2', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:02:21');
INSERT INTO `logs_activites` VALUES ('458', '2', 'Déconnexion', 'utilisateurs', '2', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:08:31');
INSERT INTO `logs_activites` VALUES ('459', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:08:41');
INSERT INTO `logs_activites` VALUES ('460', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:16:09');
INSERT INTO `logs_activites` VALUES ('461', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:16:13');
INSERT INTO `logs_activites` VALUES ('462', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:16:22');
INSERT INTO `logs_activites` VALUES ('463', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 12:16:32');
INSERT INTO `logs_activites` VALUES ('464', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:45:26');
INSERT INTO `logs_activites` VALUES ('465', '14', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 2 destinataire(s) : Message au secretaire', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:52:51');
INSERT INTO `logs_activites` VALUES ('466', '14', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 2 destinataire(s) : Message au secretaire', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:52:52');
INSERT INTO `logs_activites` VALUES ('467', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:53:14');
INSERT INTO `logs_activites` VALUES ('468', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:53:22');
INSERT INTO `logs_activites` VALUES ('469', '3', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Salt admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:54:32');
INSERT INTO `logs_activites` VALUES ('470', '3', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Salt admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:54:33');
INSERT INTO `logs_activites` VALUES ('471', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:54:38');
INSERT INTO `logs_activites` VALUES ('472', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:54:54');
INSERT INTO `logs_activites` VALUES ('473', '14', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Re: Salt admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:55:24');
INSERT INTO `logs_activites` VALUES ('474', '14', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Re: Salt admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:55:24');
INSERT INTO `logs_activites` VALUES ('475', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:56:07');
INSERT INTO `logs_activites` VALUES ('476', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 14:56:18');
INSERT INTO `logs_activites` VALUES ('477', '3', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Re: Conversation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:04:30');
INSERT INTO `logs_activites` VALUES ('478', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:21:36');
INSERT INTO `logs_activites` VALUES ('479', '2', 'Connexion', 'utilisateurs', '2', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:21:45');
INSERT INTO `logs_activites` VALUES ('480', '2', 'Déconnexion', 'utilisateurs', '2', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:28:42');
INSERT INTO `logs_activites` VALUES ('481', NULL, 'Tentative échouée', 'utilisateurs', '2', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:28:49');
INSERT INTO `logs_activites` VALUES ('482', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:29:02');
INSERT INTO `logs_activites` VALUES ('483', '14', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Salutation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:33:32');
INSERT INTO `logs_activites` VALUES ('484', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:33:46');
INSERT INTO `logs_activites` VALUES ('485', '2', 'Connexion', 'utilisateurs', '2', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:33:55');
INSERT INTO `logs_activites` VALUES ('486', '2', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Re: Conversation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:34:47');
INSERT INTO `logs_activites` VALUES ('487', '2', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Re: Conversation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:34:56');
INSERT INTO `logs_activites` VALUES ('488', '2', 'Déconnexion', 'utilisateurs', '2', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:35:06');
INSERT INTO `logs_activites` VALUES ('489', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:35:16');
INSERT INTO `logs_activites` VALUES ('490', '14', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Re: Conversation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:36:01');
INSERT INTO `logs_activites` VALUES ('491', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:58:39');
INSERT INTO `logs_activites` VALUES ('492', '2', 'Connexion', 'utilisateurs', '2', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 15:58:45');
INSERT INTO `logs_activites` VALUES ('493', '2', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 2 destinataire(s) : salut admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 16:02:29');
INSERT INTO `logs_activites` VALUES ('494', '2', '', 'messages', NULL, NULL, NULL, 'Message envoyé à 1 destinataire(s) : Re: Conversation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 16:03:18');
INSERT INTO `logs_activites` VALUES ('495', '2', 'Déconnexion', 'utilisateurs', '2', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 16:06:38');
INSERT INTO `logs_activites` VALUES ('496', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 16:06:48');
INSERT INTO `logs_activites` VALUES ('497', '14', '', 'documents', NULL, NULL, NULL, '1 document(s) uploadé(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 16:48:30');
INSERT INTO `logs_activites` VALUES ('498', '14', '', 'documents', NULL, NULL, NULL, '1 document(s) uploadé(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 16:51:45');
INSERT INTO `logs_activites` VALUES ('499', '14', '', 'documents', NULL, NULL, NULL, '1 document(s) uploadé(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 16:54:33');
INSERT INTO `logs_activites` VALUES ('500', '14', '', 'documents', NULL, NULL, NULL, '1 document(s) uploadé(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-04 16:59:13');
INSERT INTO `logs_activites` VALUES ('501', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:45:25');
INSERT INTO `logs_activites` VALUES ('502', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:46:45');
INSERT INTO `logs_activites` VALUES ('503', '2', 'Connexion', 'utilisateurs', '2', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:46:58');
INSERT INTO `logs_activites` VALUES ('504', '2', 'Déconnexion', 'utilisateurs', '2', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:47:10');
INSERT INTO `logs_activites` VALUES ('505', '30', 'Connexion', 'utilisateurs', '30', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:47:24');
INSERT INTO `logs_activites` VALUES ('506', '30', 'Déconnexion', 'utilisateurs', '30', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:47:35');
INSERT INTO `logs_activites` VALUES ('507', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:47:45');
INSERT INTO `logs_activites` VALUES ('508', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:49:50');
INSERT INTO `logs_activites` VALUES ('509', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:50:03');
INSERT INTO `logs_activites` VALUES ('510', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:54:09');
INSERT INTO `logs_activites` VALUES ('511', '4', 'Connexion', 'utilisateurs', '4', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:54:16');
INSERT INTO `logs_activites` VALUES ('512', '4', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : documents.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:54:59');
INSERT INTO `logs_activites` VALUES ('513', '4', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : documents.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:55:51');
INSERT INTO `logs_activites` VALUES ('514', '4', 'Déconnexion', 'utilisateurs', '4', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:55:53');
INSERT INTO `logs_activites` VALUES ('515', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:56:07');
INSERT INTO `logs_activites` VALUES ('516', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:56:36');
INSERT INTO `logs_activites` VALUES ('517', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:56:48');
INSERT INTO `logs_activites` VALUES ('518', '14', '', 'documents', NULL, NULL, NULL, '1 document(s) uploadé(s)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:57:44');
INSERT INTO `logs_activites` VALUES ('519', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:58:07');
INSERT INTO `logs_activites` VALUES ('520', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:58:14');
INSERT INTO `logs_activites` VALUES ('521', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:58:38');
INSERT INTO `logs_activites` VALUES ('522', '6', 'Connexion', 'utilisateurs', '6', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:58:49');
INSERT INTO `logs_activites` VALUES ('523', '6', 'Déconnexion', 'utilisateurs', '6', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:58:59');
INSERT INTO `logs_activites` VALUES ('524', NULL, 'Tentative échouée', 'utilisateurs', '20', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:59:09');
INSERT INTO `logs_activites` VALUES ('525', '5', 'Connexion', 'utilisateurs', '5', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 11:59:24');
INSERT INTO `logs_activites` VALUES ('526', '5', 'Déconnexion', 'utilisateurs', '5', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:01:37');
INSERT INTO `logs_activites` VALUES ('527', '17', 'Connexion', 'utilisateurs', '17', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:01:51');
INSERT INTO `logs_activites` VALUES ('528', '17', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : documents.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:02:00');
INSERT INTO `logs_activites` VALUES ('529', '17', 'Déconnexion', 'utilisateurs', '17', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:02:38');
INSERT INTO `logs_activites` VALUES ('530', NULL, 'Tentative échouée', 'utilisateurs', '16', NULL, NULL, 'Tentative 1/5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:02:50');
INSERT INTO `logs_activites` VALUES ('531', '7', 'Connexion', 'utilisateurs', '7', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:03:20');
INSERT INTO `logs_activites` VALUES ('532', '7', 'Déconnexion', 'utilisateurs', '7', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:03:39');
INSERT INTO `logs_activites` VALUES ('533', '17', 'Connexion', 'utilisateurs', '17', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:03:55');
INSERT INTO `logs_activites` VALUES ('534', '17', 'Déconnexion', 'utilisateurs', '17', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:03:59');
INSERT INTO `logs_activites` VALUES ('535', '30', 'Connexion', 'utilisateurs', '30', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:04:11');
INSERT INTO `logs_activites` VALUES ('536', '30', 'Déconnexion', 'utilisateurs', '30', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:04:37');
INSERT INTO `logs_activites` VALUES ('537', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:04:49');
INSERT INTO `logs_activites` VALUES ('538', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:13:50');
INSERT INTO `logs_activites` VALUES ('539', '2', 'Connexion', 'utilisateurs', '2', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:14:01');
INSERT INTO `logs_activites` VALUES ('540', '2', 'Déconnexion', 'utilisateurs', '2', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:15:24');
INSERT INTO `logs_activites` VALUES ('541', '3', 'Connexion', 'utilisateurs', '3', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:15:30');
INSERT INTO `logs_activites` VALUES ('542', '3', '', NULL, NULL, NULL, NULL, 'Vous n\'avez pas la permission nécessaire : rapports.consulter', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:16:56');
INSERT INTO `logs_activites` VALUES ('543', '3', 'Déconnexion', 'utilisateurs', '3', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:17:59');
INSERT INTO `logs_activites` VALUES ('544', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:18:06');
INSERT INTO `logs_activites` VALUES ('545', '14', 'Modification', 'parametres_ecole', '1', NULL, NULL, 'Paramètres de l\'école mis à jour', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:51:27');
INSERT INTO `logs_activites` VALUES ('546', '14', 'Modification', 'parametres_ecole', '1', NULL, NULL, 'Paramètres de l\'école mis à jour', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:51:31');
INSERT INTO `logs_activites` VALUES ('547', '14', 'Modification', 'parametres_ecole', '1', NULL, NULL, 'Paramètres de l\'école mis à jour', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:52:05');
INSERT INTO `logs_activites` VALUES ('548', '14', 'Modification', 'parametres_ecole', '1', NULL, NULL, 'Paramètres de l\'école mis à jour', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 12:54:48');
INSERT INTO `logs_activites` VALUES ('549', '14', 'Déconnexion', 'utilisateurs', '14', NULL, NULL, 'Déconnexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 13:05:07');
INSERT INTO `logs_activites` VALUES ('550', '14', 'Connexion', 'utilisateurs', '14', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 13:05:16');
INSERT INTO `logs_activites` VALUES ('551', '14', 'Sauvegarde', 'sauvegardes', '1', NULL, NULL, 'Sauvegarde créée : backup_2026-08-05_13-34-33.sql (441293 octets)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 13:34:33');
INSERT INTO `logs_activites` VALUES ('552', '14', 'Suppression', 'sauvegardes', '1', NULL, NULL, 'Sauvegarde supprimée : backup_2026-08-05_13-34-33.sql', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2026-08-05 13:36:29');
/*!40000 ALTER TABLE `logs_activites` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `matieres`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `matieres`;
CREATE TABLE `matieres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ex: MATH, FRAN, SCIE',
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coefficient` tinyint(4) NOT NULL DEFAULT 1,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `maxima` int(11) NOT NULL DEFAULT 20 COMMENT 'Note maximale possible pour cette matière (ex: 100, 50, 25, 20)',
  `est_groupe` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'TRUE si c''est un groupe de matières (ex: Français, Math)',
  `parent_id` int(11) DEFAULT NULL COMMENT 'ID de la matière parente si c''est une sous-matière',
  PRIMARY KEY (`id`),
  UNIQUE KEY `libelle` (`libelle`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_matiere_parent` (`parent_id`),
  CONSTRAINT `fk_matiere_parent` FOREIGN KEY (`parent_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `matieres`
LOCK TABLES `matieres` WRITE;
/*!40000 ALTER TABLE `matieres` DISABLE KEYS */;
INSERT INTO `matieres` VALUES ('56', 'BRANCHES', 'I. BRANCHES DE BASE', NULL, '1', '1', '2026-08-01 02:12:29', NULL, '0', '1', NULL);
INSERT INTO `matieres` VALUES ('57', 'INSTRUM', 'II. ACTIVITÉS INSTRUMENTALES', NULL, '1', '1', '2026-08-01 02:12:29', NULL, '0', '1', NULL);
INSERT INTO `matieres` VALUES ('58', 'FRANCAIS', 'III. FRANÇAIS', NULL, '1', '1', '2026-08-01 02:12:29', NULL, '0', '1', NULL);
INSERT INTO `matieres` VALUES ('59', 'MATHS', 'IV. MATHÉMATIQUES', NULL, '1', '1', '2026-08-01 02:12:29', NULL, '0', '1', NULL);
INSERT INTO `matieres` VALUES ('60', 'EVEIL_SC', 'V. ÉVEIL SCIENTIFIQUE', NULL, '1', '1', '2026-08-01 02:12:29', NULL, '0', '1', NULL);
INSERT INTO `matieres` VALUES ('61', 'EVEIL_ESTH', 'VI. ÉVEIL ESTHÉTIQUE', NULL, '1', '1', '2026-08-01 02:12:29', NULL, '0', '1', NULL);
INSERT INTO `matieres` VALUES ('62', 'RELIG', 'Religion — Branches de base', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '56');
INSERT INTO `matieres` VALUES ('63', 'EDCIV', 'Éduc. civ. & morale — Branches de base', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '56');
INSERT INTO `matieres` VALUES ('64', 'EDVIE', 'Éduc. à la vie — Branches de base', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '56');
INSERT INTO `matieres` VALUES ('65', 'INSTR_GCA', 'Gram. Conj. Analyse — Instrum.', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '5', '0', '57');
INSERT INTO `matieres` VALUES ('66', 'INSTR_EVR', 'Éloc. Voc. Récitation — Instrum.', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '5', '0', '57');
INSERT INTO `matieres` VALUES ('67', 'INSTR_RO', 'Rédac. Orthographe — Instrum.', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '5', '0', '57');
INSERT INTO `matieres` VALUES ('68', 'INSTR_LEC', 'Lecture — Instrum.', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '5', '0', '57');
INSERT INTO `matieres` VALUES ('69', 'FRAN_GCA', 'Gram. Conj. Analyse — Français', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '20', '0', '58');
INSERT INTO `matieres` VALUES ('70', 'FRAN_EVR', 'Éloc. Voc. Récitation — Français', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '25', '0', '58');
INSERT INTO `matieres` VALUES ('71', 'FRAN_RO', 'Rédac. Orthographe — Français', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '15', '0', '58');
INSERT INTO `matieres` VALUES ('72', 'FRAN_LEC', 'Lecture — Français', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '58');
INSERT INTO `matieres` VALUES ('73', 'MATH_NO', 'Numér. Opérations — Maths', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '20', '0', '59');
INSERT INTO `matieres` VALUES ('74', 'MATH_GRA', 'Grandeurs — Maths', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '15', '0', '59');
INSERT INTO `matieres` VALUES ('75', 'MATH_FG', 'Formes géométriques — Maths', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '15', '0', '59');
INSERT INTO `matieres` VALUES ('76', 'MATH_PRO', 'Problèmes — Maths', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '20', '0', '59');
INSERT INTO `matieres` VALUES ('77', 'EVEIL_EDS', 'Éd. Santé Env. — Éveil', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '60');
INSERT INTO `matieres` VALUES ('78', 'EVEIL_HIST', 'Histoire — Éveil', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '60');
INSERT INTO `matieres` VALUES ('79', 'EVEIL_GEO', 'Géographie — Éveil', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '60');
INSERT INTO `matieres` VALUES ('80', 'EVEIL_SCN', 'Sciences naturelles — Éveil', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '20', '0', '60');
INSERT INTO `matieres` VALUES ('81', 'EVEIL_INFO', 'Informatique — Éveil', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '60');
INSERT INTO `matieres` VALUES ('82', 'ESTH_DESS', 'Dessin — Esthétique', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '61');
INSERT INTO `matieres` VALUES ('83', 'ESTH_CALL', 'Calligraphie — Esthétique', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '61');
INSERT INTO `matieres` VALUES ('84', 'ESTH_CHANT', 'Chant / Musique — Esthétique', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '61');
INSERT INTO `matieres` VALUES ('85', 'ESTH_EPS', 'Éd. phys. & sport — Esthétique', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '61');
INSERT INTO `matieres` VALUES ('86', 'ESTH_MAN', 'Activités manuelles — Esthétique', NULL, '1', '1', '2026-08-01 02:12:30', NULL, '10', '0', '61');
/*!40000 ALTER TABLE `matieres` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `messages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_lu` tinyint(1) NOT NULL DEFAULT 0,
  `date_lecture` datetime DEFAULT NULL,
  `est_archive_expediteur` tinyint(1) NOT NULL DEFAULT 0,
  `est_archive_destinataire` tinyint(1) NOT NULL DEFAULT 0,
  `est_supprime_expediteur` tinyint(1) NOT NULL DEFAULT 0,
  `est_supprime_destinataire` tinyint(1) NOT NULL DEFAULT 0,
  `message_parent_id` int(11) DEFAULT NULL COMMENT 'pour les réponses, référence au message d''origine',
  `date_envoi` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_messages_expediteur` (`expediteur_id`),
  KEY `fk_messages_destinataire` (`destinataire_id`),
  KEY `fk_messages_parent` (`message_parent_id`),
  CONSTRAINT `fk_messages_destinataire` FOREIGN KEY (`destinataire_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_expediteur` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_parent` FOREIGN KEY (`message_parent_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `messages`
LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES ('16', '14', '3', 'Message au secretaire', 'Salut secretaire', '1', '2026-08-04 14:53:29', '0', '0', '1', '0', NULL, '2026-08-04 14:52:51', '2026-08-04 14:52:51');
INSERT INTO `messages` VALUES ('17', '14', '18', 'Message au secretaire', 'Salut secretaire', '0', NULL, '0', '0', '1', '0', NULL, '2026-08-04 14:52:51', '2026-08-04 14:52:51');
INSERT INTO `messages` VALUES ('18', '14', '3', 'Message au secretaire', 'Salut secretaire', '1', '2026-08-04 14:56:35', '0', '0', '1', '0', NULL, '2026-08-04 14:52:51', '2026-08-04 14:52:51');
INSERT INTO `messages` VALUES ('19', '14', '18', 'Message au secretaire', 'Salut secretaire', '0', NULL, '0', '0', '1', '0', NULL, '2026-08-04 14:52:52', '2026-08-04 14:52:52');
INSERT INTO `messages` VALUES ('20', '3', '14', 'Salt admin', 'Salut admin', '1', '2026-08-04 15:29:52', '0', '0', '0', '1', '16', '2026-08-04 14:54:32', '2026-08-04 14:54:32');
INSERT INTO `messages` VALUES ('21', '3', '14', 'Salt admin', 'Salut admin', '1', '2026-08-04 14:55:39', '0', '0', '0', '1', '16', '2026-08-04 14:54:32', '2026-08-04 14:54:32');
INSERT INTO `messages` VALUES ('22', '14', '3', 'Re: Salt admin', 'c comment ?', '1', '2026-08-04 15:05:30', '0', '0', '1', '0', '20', '2026-08-04 14:55:24', '2026-08-04 14:55:24');
INSERT INTO `messages` VALUES ('23', '14', '3', 'Re: Salt admin', 'c comment ?', '1', '2026-08-04 14:56:35', '0', '0', '1', '0', '20', '2026-08-04 14:55:24', '2026-08-04 14:55:24');
INSERT INTO `messages` VALUES ('24', '3', '14', 'Re: Conversation', 'je vais bien et toi ?', '1', '2026-08-04 15:29:54', '0', '0', '0', '1', NULL, '2026-08-04 15:04:30', '2026-08-04 15:04:30');
INSERT INTO `messages` VALUES ('25', '14', '2', 'Salutation', 'Salut directeur', '1', '2026-08-04 15:34:17', '0', '0', '0', '1', NULL, '2026-08-04 15:33:32', '2026-08-04 15:33:32');
INSERT INTO `messages` VALUES ('26', '2', '14', 'Re: Conversation', 'Salut admin', '1', '2026-08-04 15:40:18', '0', '0', '0', '0', NULL, '2026-08-04 15:34:47', '2026-08-04 15:34:47');
INSERT INTO `messages` VALUES ('27', '2', '14', 'Re: Conversation', 'c comment admin', '1', '2026-08-04 15:58:07', '0', '0', '0', '0', NULL, '2026-08-04 15:34:56', '2026-08-04 15:34:56');
INSERT INTO `messages` VALUES ('28', '14', '2', 'Re: Conversation', 'je vais bien directeur', '1', '2026-08-04 16:01:50', '0', '0', '0', '1', NULL, '2026-08-04 15:36:01', '2026-08-04 15:36:01');
INSERT INTO `messages` VALUES ('29', '2', '1', 'salut admin', 'salut', '0', NULL, '0', '0', '0', '0', NULL, '2026-08-04 16:02:28', '2026-08-04 16:02:28');
INSERT INTO `messages` VALUES ('30', '2', '14', 'salut admin', 'salut', '1', '2026-08-04 16:22:09', '0', '0', '0', '0', NULL, '2026-08-04 16:02:29', '2026-08-04 16:02:29');
INSERT INTO `messages` VALUES ('31', '2', '14', 'Re: Conversation', 'comment', '1', '2026-08-04 16:22:16', '0', '0', '0', '0', NULL, '2026-08-04 16:03:18', '2026-08-04 16:03:18');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `moyennes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `moyennes`;
CREATE TABLE `moyennes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `trimestre_id` int(11) NOT NULL,
  `moyenne_periode1` decimal(5,2) DEFAULT NULL,
  `moyenne_periode2` decimal(5,2) DEFAULT NULL,
  `moyenne_examen` decimal(5,2) DEFAULT NULL,
  `moyenne_trimestre` decimal(5,2) DEFAULT NULL COMMENT 'calculée: (p1*% + p2*% + examen*%)',
  `rang_matiere` int(11) DEFAULT NULL COMMENT 'rang de l''élève dans cette matière pour sa classe',
  `appreciation` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_moyenne` (`eleve_id`,`matiere_id`,`trimestre_id`),
  KEY `fk_moyennes_matiere` (`matiere_id`),
  KEY `fk_moyennes_trimestre` (`trimestre_id`),
  CONSTRAINT `fk_moyennes_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_moyennes_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_moyennes_trimestre` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `notes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `sous_matiere_id` int(11) DEFAULT NULL,
  `valeur` decimal(5,2) DEFAULT NULL CHECK (`valeur` >= 0 and `valeur` <= 50),
  `absence` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'absent lors de l''évaluation',
  `commentaire` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saisie_par` int(11) NOT NULL COMMENT 'utilisateur qui a saisi la note',
  `date_saisie` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_note` (`eleve_id`,`evaluation_id`,`sous_matiere_id`),
  KEY `fk_notes_evaluation` (`evaluation_id`),
  KEY `fk_notes_saisie_par` (`saisie_par`),
  KEY `fk_notes_sous_matiere` (`sous_matiere_id`),
  CONSTRAINT `fk_notes_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notes_saisie_par` FOREIGN KEY (`saisie_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_notes_sous_matiere` FOREIGN KEY (`sous_matiere_id`) REFERENCES `matieres` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `notes`
LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES ('2633', '107', '189', '62', '6.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2634', '107', '189', '63', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2635', '107', '189', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2636', '107', '190', '62', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2637', '107', '190', '63', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2638', '107', '190', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2639', '107', '191', '62', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2640', '107', '191', '63', '16.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2641', '107', '191', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2642', '107', '192', '65', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2643', '107', '192', '66', '3.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2644', '107', '192', '67', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2645', '107', '192', '68', '3.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2646', '107', '193', '65', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2647', '107', '193', '66', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2648', '107', '193', '67', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2649', '107', '193', '68', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2650', '107', '194', '65', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2651', '107', '194', '66', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2652', '107', '194', '67', '6.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2653', '107', '194', '68', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2654', '107', '195', '69', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2655', '107', '195', '70', '17.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2656', '107', '195', '71', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2657', '107', '195', '72', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2658', '107', '196', '69', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2659', '107', '196', '70', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2660', '107', '196', '71', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2661', '107', '196', '72', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2662', '107', '197', '69', '35.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2663', '107', '197', '70', '46.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2664', '107', '197', '71', '24.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2665', '107', '197', '72', '16.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2666', '107', '198', '73', '13.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2667', '107', '198', '74', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2668', '107', '198', '75', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2669', '107', '198', '76', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2670', '107', '199', '73', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2671', '107', '199', '74', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2672', '107', '199', '75', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2673', '107', '199', '76', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2674', '107', '200', '73', '32.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2675', '107', '200', '74', '24.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2676', '107', '200', '75', '24.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2677', '107', '200', '76', '40.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2678', '107', '201', '77', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2679', '107', '201', '78', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2680', '107', '201', '79', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2681', '107', '201', '80', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2682', '107', '201', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2683', '107', '202', '77', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2684', '107', '202', '78', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2685', '107', '202', '79', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2686', '107', '202', '80', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2687', '107', '202', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2688', '107', '203', '77', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2689', '107', '203', '78', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2690', '107', '203', '79', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2691', '107', '203', '80', '40.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2692', '107', '203', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2693', '107', '204', '82', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2694', '107', '204', '83', '6.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2695', '107', '204', '84', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2696', '107', '204', '85', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2697', '107', '204', '86', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2698', '107', '205', '82', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2699', '107', '205', '83', '6.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2700', '107', '205', '84', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2701', '107', '205', '85', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2702', '107', '205', '86', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2703', '107', '206', '82', '11.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2704', '107', '206', '83', '17.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2705', '107', '206', '84', '16.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2706', '107', '206', '85', '19.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2707', '107', '206', '86', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2708', '107', '207', '62', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2709', '107', '207', '63', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2710', '107', '207', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2711', '107', '208', '62', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2712', '107', '208', '63', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2713', '107', '208', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2714', '107', '209', '62', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2715', '107', '209', '63', '16.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2716', '107', '209', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2717', '107', '210', '65', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2718', '107', '210', '66', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2719', '107', '210', '67', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2720', '107', '210', '68', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2721', '107', '211', '65', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2722', '107', '211', '66', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2723', '107', '211', '67', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2724', '107', '211', '68', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2725', '107', '212', '65', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2726', '107', '212', '66', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2727', '107', '212', '67', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2728', '107', '212', '68', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2729', '107', '213', '69', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2730', '107', '213', '70', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2731', '107', '213', '71', '13.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2732', '107', '213', '72', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2733', '107', '214', '69', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2734', '107', '214', '70', '22.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2735', '107', '214', '71', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2736', '107', '214', '72', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2737', '107', '215', '69', '30.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2738', '107', '215', '70', '42.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2739', '107', '215', '71', '26.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2740', '107', '215', '72', '17.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2741', '107', '216', '73', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2742', '107', '216', '74', '13.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2743', '107', '216', '75', '12.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2744', '107', '216', '76', '19.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2745', '107', '217', '73', '19.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2746', '107', '217', '74', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2747', '107', '217', '75', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2748', '107', '217', '76', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2749', '107', '218', '73', '35.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2750', '107', '218', '74', '28.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2751', '107', '218', '75', '26.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2752', '107', '218', '76', '38.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2753', '107', '219', '77', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2754', '107', '219', '78', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2755', '107', '219', '79', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2756', '107', '219', '80', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2757', '107', '219', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2758', '107', '220', '77', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2759', '107', '220', '78', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2760', '107', '220', '79', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2761', '107', '220', '80', '19.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2762', '107', '220', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2763', '107', '221', '77', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2764', '107', '221', '78', '17.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2765', '107', '221', '79', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2766', '107', '221', '80', '35.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2767', '107', '221', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2768', '107', '222', '82', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2769', '107', '222', '83', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2770', '107', '222', '84', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2771', '107', '222', '85', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2772', '107', '222', '86', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2773', '107', '223', '82', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2774', '107', '223', '83', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2775', '107', '223', '84', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2776', '107', '223', '85', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2777', '107', '223', '86', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2778', '107', '224', '82', '13.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2779', '107', '224', '83', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2780', '107', '224', '84', '17.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2781', '107', '224', '85', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2782', '107', '224', '86', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2783', '107', '225', '62', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2784', '107', '225', '63', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2785', '107', '225', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2786', '107', '226', '62', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2787', '107', '226', '63', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2788', '107', '226', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2789', '107', '227', '62', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2790', '107', '227', '63', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2791', '107', '227', '64', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2792', '107', '228', '65', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2793', '107', '228', '66', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2794', '107', '228', '67', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2795', '107', '228', '68', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2796', '107', '229', '65', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2797', '107', '229', '66', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2798', '107', '229', '67', '4.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2799', '107', '229', '68', '5.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2800', '107', '230', '65', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2801', '107', '230', '66', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2802', '107', '230', '67', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2803', '107', '230', '68', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2804', '107', '231', '69', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2805', '107', '231', '70', '21.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2806', '107', '231', '71', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2807', '107', '231', '72', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2808', '107', '232', '69', '17.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2809', '107', '232', '70', '23.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2810', '107', '232', '71', '13.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2811', '107', '232', '72', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2812', '107', '233', '69', '32.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2813', '107', '233', '70', '44.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2814', '107', '233', '71', '25.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2815', '107', '233', '72', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2816', '107', '234', '73', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2817', '107', '234', '74', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2818', '107', '234', '75', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2819', '107', '234', '76', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2820', '107', '235', '73', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2821', '107', '235', '74', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2822', '107', '235', '75', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2823', '107', '235', '76', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2824', '107', '236', '73', '38.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2825', '107', '236', '74', '30.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2826', '107', '236', '75', '28.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2827', '107', '236', '76', '40.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2828', '107', '237', '77', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2829', '107', '237', '78', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2830', '107', '237', '79', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2831', '107', '237', '80', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2832', '107', '237', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2833', '107', '238', '77', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2834', '107', '238', '78', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2835', '107', '238', '79', '6.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2836', '107', '238', '80', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2837', '107', '238', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2838', '107', '239', '77', '20.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2839', '107', '239', '78', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2840', '107', '239', '79', '14.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2841', '107', '239', '80', '38.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2842', '107', '239', '81', '0.00', '1', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2843', '107', '240', '82', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2844', '107', '240', '83', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2845', '107', '240', '84', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2846', '107', '240', '85', '10.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2847', '107', '240', '86', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2848', '107', '241', '82', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2849', '107', '241', '83', '7.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2850', '107', '241', '84', '8.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2851', '107', '241', '85', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2852', '107', '241', '86', '9.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2853', '107', '242', '82', '12.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2854', '107', '242', '83', '15.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2855', '107', '242', '84', '18.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2856', '107', '242', '85', '19.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('2857', '107', '242', '86', '16.00', '0', NULL, '1', '2026-08-01 15:54:29', NULL, '2026-08-01 15:54:29', NULL);
INSERT INTO `notes` VALUES ('3398', '111', '189', '62', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3399', '111', '189', '63', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3400', '111', '189', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3401', '111', '190', '62', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3402', '111', '190', '63', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3403', '111', '190', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3404', '111', '191', '62', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3405', '111', '191', '63', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3406', '111', '191', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3407', '111', '192', '65', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3408', '111', '192', '66', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3409', '111', '192', '67', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3410', '111', '192', '68', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3411', '111', '193', '65', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3412', '111', '193', '66', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3413', '111', '193', '67', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3414', '111', '193', '68', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3415', '111', '194', '65', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3416', '111', '194', '66', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3417', '111', '194', '67', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3418', '111', '194', '68', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3419', '111', '195', '69', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3420', '111', '195', '70', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3421', '111', '195', '71', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3422', '111', '195', '72', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3423', '111', '196', '69', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3424', '111', '196', '70', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3425', '111', '196', '71', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3426', '111', '196', '72', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3427', '111', '197', '69', '8.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3428', '111', '197', '70', '8.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3429', '111', '197', '71', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3430', '111', '197', '72', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3431', '111', '198', '73', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3432', '111', '198', '74', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3433', '111', '198', '75', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3434', '111', '198', '76', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3435', '111', '199', '73', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3436', '111', '199', '74', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3437', '111', '199', '75', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3438', '111', '199', '76', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3439', '111', '200', '73', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3440', '111', '200', '74', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3441', '111', '200', '75', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3442', '111', '200', '76', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3443', '111', '201', '77', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3444', '111', '201', '78', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3445', '111', '201', '79', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3446', '111', '201', '80', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3447', '111', '201', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3448', '111', '202', '77', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3449', '111', '202', '78', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3450', '111', '202', '79', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3451', '111', '202', '80', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3452', '111', '202', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3453', '111', '203', '77', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3454', '111', '203', '78', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3455', '111', '203', '79', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3456', '111', '203', '80', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3457', '111', '203', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3458', '111', '204', '82', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3459', '111', '204', '83', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3460', '111', '204', '84', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3461', '111', '204', '85', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3462', '111', '204', '86', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3463', '111', '205', '82', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3464', '111', '205', '83', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3465', '111', '205', '84', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3466', '111', '205', '85', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3467', '111', '205', '86', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3468', '111', '206', '82', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3469', '111', '206', '83', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3470', '111', '206', '84', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3471', '111', '206', '85', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3472', '111', '206', '86', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3473', '111', '207', '62', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3474', '111', '207', '63', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3475', '111', '207', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3476', '111', '208', '62', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3477', '111', '208', '63', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3478', '111', '208', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3479', '111', '209', '62', '7.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3480', '111', '209', '63', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3481', '111', '209', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3482', '111', '210', '65', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3483', '111', '210', '66', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3484', '111', '210', '67', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3485', '111', '210', '68', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3486', '111', '211', '65', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3487', '111', '211', '66', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3488', '111', '211', '67', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3489', '111', '211', '68', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3490', '111', '212', '65', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3491', '111', '212', '66', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3492', '111', '212', '67', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3493', '111', '212', '68', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3494', '111', '213', '69', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3495', '111', '213', '70', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3496', '111', '213', '71', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3497', '111', '213', '72', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3498', '111', '214', '69', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3499', '111', '214', '70', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3500', '111', '214', '71', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3501', '111', '214', '72', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3502', '111', '215', '69', '10.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3503', '111', '215', '70', '10.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3504', '111', '215', '71', '7.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3505', '111', '215', '72', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3506', '111', '216', '73', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3507', '111', '216', '74', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3508', '111', '216', '75', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3509', '111', '216', '76', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3510', '111', '217', '73', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3511', '111', '217', '74', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3512', '111', '217', '75', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3513', '111', '217', '76', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3514', '111', '218', '73', '8.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3515', '111', '218', '74', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3516', '111', '218', '75', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3517', '111', '218', '76', '7.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3518', '111', '219', '77', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3519', '111', '219', '78', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3520', '111', '219', '79', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3521', '111', '219', '80', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3522', '111', '219', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3523', '111', '220', '77', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3524', '111', '220', '78', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3525', '111', '220', '79', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3526', '111', '220', '80', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3527', '111', '220', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3528', '111', '221', '77', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3529', '111', '221', '78', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3530', '111', '221', '79', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3531', '111', '221', '80', '7.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3532', '111', '221', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3533', '111', '222', '82', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3534', '111', '222', '83', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3535', '111', '222', '84', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3536', '111', '222', '85', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3537', '111', '222', '86', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3538', '111', '223', '82', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3539', '111', '223', '83', '1.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3540', '111', '223', '84', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3541', '111', '223', '85', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3542', '111', '223', '86', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3543', '111', '224', '82', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3544', '111', '224', '83', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3545', '111', '224', '84', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3546', '111', '224', '85', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3547', '111', '224', '86', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3548', '111', '225', '62', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3549', '111', '225', '63', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3550', '111', '225', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3551', '111', '226', '62', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3552', '111', '226', '63', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3553', '111', '226', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3554', '111', '227', '62', '8.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3555', '111', '227', '63', '7.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3556', '111', '227', '64', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3557', '111', '228', '65', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3558', '111', '228', '66', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3559', '111', '228', '67', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3560', '111', '228', '68', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3561', '111', '229', '65', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3562', '111', '229', '66', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3563', '111', '229', '67', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3564', '111', '229', '68', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3565', '111', '230', '65', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3566', '111', '230', '66', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3567', '111', '230', '67', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3568', '111', '230', '68', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3569', '111', '231', '69', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3570', '111', '231', '70', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3571', '111', '231', '71', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3572', '111', '231', '72', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3573', '111', '232', '69', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3574', '111', '232', '70', '7.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3575', '111', '232', '71', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3576', '111', '232', '72', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3577', '111', '233', '69', '12.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3578', '111', '233', '70', '12.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3579', '111', '233', '71', '8.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3580', '111', '233', '72', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3581', '111', '234', '73', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3582', '111', '234', '74', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3583', '111', '234', '75', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3584', '111', '234', '76', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3585', '111', '235', '73', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3586', '111', '235', '74', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3587', '111', '235', '75', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3588', '111', '235', '76', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3589', '111', '236', '73', '10.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3590', '111', '236', '74', '8.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3591', '111', '236', '75', '6.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3592', '111', '236', '76', '9.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3593', '111', '237', '77', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3594', '111', '237', '78', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3595', '111', '237', '79', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3596', '111', '237', '80', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3597', '111', '237', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3598', '111', '238', '77', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3599', '111', '238', '78', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3600', '111', '238', '79', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3601', '111', '238', '80', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3602', '111', '238', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3603', '111', '239', '77', '7.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3604', '111', '239', '78', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3605', '111', '239', '79', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3606', '111', '239', '80', '8.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3607', '111', '239', '81', '0.00', '1', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3608', '111', '240', '82', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3609', '111', '240', '83', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3610', '111', '240', '84', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3611', '111', '240', '85', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3612', '111', '240', '86', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3613', '111', '241', '82', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3614', '111', '241', '83', '2.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3615', '111', '241', '84', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3616', '111', '241', '85', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3617', '111', '241', '86', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3618', '111', '242', '82', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3619', '111', '242', '83', '3.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3620', '111', '242', '84', '4.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3621', '111', '242', '85', '7.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3622', '111', '242', '86', '5.00', '0', NULL, '1', '2026-08-01 16:33:24', NULL, '2026-08-01 16:33:24', NULL);
INSERT INTO `notes` VALUES ('3653', '110', '189', '62', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3654', '110', '189', '63', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3655', '110', '189', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3656', '110', '190', '62', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3657', '110', '190', '63', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3658', '110', '190', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3659', '110', '191', '62', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3660', '110', '191', '63', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3661', '110', '191', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3662', '110', '192', '65', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3663', '110', '192', '66', '2.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3664', '110', '192', '67', '2.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3665', '110', '192', '68', '2.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3666', '110', '193', '65', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3667', '110', '193', '66', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3668', '110', '193', '67', '2.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3669', '110', '193', '68', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3670', '110', '194', '65', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3671', '110', '194', '66', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3672', '110', '194', '67', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3673', '110', '194', '68', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3674', '110', '195', '69', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3675', '110', '195', '70', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3676', '110', '195', '71', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3677', '110', '195', '72', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3678', '110', '196', '69', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3679', '110', '196', '70', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3680', '110', '196', '71', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3681', '110', '196', '72', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3682', '110', '197', '69', '20.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3683', '110', '197', '70', '25.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3684', '110', '197', '71', '15.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3685', '110', '197', '72', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3686', '110', '198', '73', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3687', '110', '198', '74', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3688', '110', '198', '75', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3689', '110', '198', '76', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3690', '110', '199', '73', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3691', '110', '199', '74', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3692', '110', '199', '75', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3693', '110', '199', '76', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3694', '110', '200', '73', '20.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3695', '110', '200', '74', '15.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3696', '110', '200', '75', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3697', '110', '200', '76', '20.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3698', '110', '201', '77', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3699', '110', '201', '78', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3700', '110', '201', '79', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3701', '110', '201', '80', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3702', '110', '201', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3703', '110', '202', '77', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3704', '110', '202', '78', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3705', '110', '202', '79', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3706', '110', '202', '80', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3707', '110', '202', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3708', '110', '203', '77', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3709', '110', '203', '78', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3710', '110', '203', '79', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3711', '110', '203', '80', '18.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3712', '110', '203', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3713', '110', '204', '82', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3714', '110', '204', '83', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3715', '110', '204', '84', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3716', '110', '204', '85', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3717', '110', '204', '86', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3718', '110', '205', '82', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3719', '110', '205', '83', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3720', '110', '205', '84', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3721', '110', '205', '85', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3722', '110', '205', '86', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3723', '110', '206', '82', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3724', '110', '206', '83', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3725', '110', '206', '84', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3726', '110', '206', '85', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3727', '110', '206', '86', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3728', '110', '207', '62', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3729', '110', '207', '63', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3730', '110', '207', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3731', '110', '208', '62', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3732', '110', '208', '63', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3733', '110', '208', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3734', '110', '209', '62', '13.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3735', '110', '209', '63', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3736', '110', '209', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3737', '110', '210', '65', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3738', '110', '210', '66', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3739', '110', '210', '67', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3740', '110', '210', '68', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3741', '110', '211', '65', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3742', '110', '211', '66', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3743', '110', '211', '67', '2.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3744', '110', '211', '68', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3745', '110', '212', '65', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3746', '110', '212', '66', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3747', '110', '212', '67', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3748', '110', '212', '68', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3749', '110', '213', '69', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3750', '110', '213', '70', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3751', '110', '213', '71', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3752', '110', '213', '72', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3753', '110', '214', '69', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3754', '110', '214', '70', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3755', '110', '214', '71', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3756', '110', '214', '72', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3757', '110', '215', '69', '22.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3758', '110', '215', '70', '28.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3759', '110', '215', '71', '17.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3760', '110', '215', '72', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3761', '110', '216', '73', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3762', '110', '216', '74', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3763', '110', '216', '75', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3764', '110', '216', '76', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3765', '110', '217', '73', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3766', '110', '217', '74', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3767', '110', '217', '75', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3768', '110', '217', '76', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3769', '110', '218', '73', '22.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3770', '110', '218', '74', '16.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3771', '110', '218', '75', '14.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3772', '110', '218', '76', '22.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3773', '110', '219', '77', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3774', '110', '219', '78', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3775', '110', '219', '79', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3776', '110', '219', '80', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3777', '110', '219', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3778', '110', '220', '77', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3779', '110', '220', '78', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3780', '110', '220', '79', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3781', '110', '220', '80', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3782', '110', '220', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3783', '110', '221', '77', '13.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3784', '110', '221', '78', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3785', '110', '221', '79', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3786', '110', '221', '80', '20.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3787', '110', '221', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3788', '110', '222', '82', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3789', '110', '222', '83', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3790', '110', '222', '84', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3791', '110', '222', '85', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3792', '110', '222', '86', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3793', '110', '223', '82', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3794', '110', '223', '83', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3795', '110', '223', '84', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3796', '110', '223', '85', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3797', '110', '223', '86', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3798', '110', '224', '82', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3799', '110', '224', '83', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3800', '110', '224', '84', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3801', '110', '224', '85', '13.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3802', '110', '224', '86', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3803', '110', '225', '62', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3804', '110', '225', '63', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3805', '110', '225', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3806', '110', '226', '62', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3807', '110', '226', '63', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3808', '110', '226', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3809', '110', '227', '62', '14.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3810', '110', '227', '63', '13.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3811', '110', '227', '64', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3812', '110', '228', '65', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3813', '110', '228', '66', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3814', '110', '228', '67', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3815', '110', '228', '68', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3816', '110', '229', '65', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3817', '110', '229', '66', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3818', '110', '229', '67', '3.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3819', '110', '229', '68', '4.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3820', '110', '230', '65', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3821', '110', '230', '66', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3822', '110', '230', '67', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3823', '110', '230', '68', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3824', '110', '231', '69', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3825', '110', '231', '70', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3826', '110', '231', '71', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3827', '110', '231', '72', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3828', '110', '232', '69', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3829', '110', '232', '70', '13.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3830', '110', '232', '71', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3831', '110', '232', '72', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3832', '110', '233', '69', '23.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3833', '110', '233', '70', '30.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3834', '110', '233', '71', '18.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3835', '110', '233', '72', '11.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3836', '110', '234', '73', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3837', '110', '234', '74', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3838', '110', '234', '75', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3839', '110', '234', '76', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3840', '110', '235', '73', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3841', '110', '235', '74', '9.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3842', '110', '235', '75', '8.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3843', '110', '235', '76', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3844', '110', '236', '73', '24.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3845', '110', '236', '74', '18.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3846', '110', '236', '75', '15.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3847', '110', '236', '76', '24.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3848', '110', '237', '77', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3849', '110', '237', '78', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3850', '110', '237', '79', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3851', '110', '237', '80', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3852', '110', '237', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3853', '110', '238', '77', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3854', '110', '238', '78', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3855', '110', '238', '79', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3856', '110', '238', '80', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3857', '110', '238', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3858', '110', '239', '77', '14.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3859', '110', '239', '78', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3860', '110', '239', '79', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3861', '110', '239', '80', '22.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3862', '110', '239', '81', '0.00', '1', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3863', '110', '240', '82', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3864', '110', '240', '83', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3865', '110', '240', '84', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3866', '110', '240', '85', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3867', '110', '240', '86', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3868', '110', '241', '82', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3869', '110', '241', '83', '5.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3870', '110', '241', '84', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3871', '110', '241', '85', '7.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3872', '110', '241', '86', '6.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3873', '110', '242', '82', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3874', '110', '242', '83', '10.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3875', '110', '242', '84', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3876', '110', '242', '85', '14.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3877', '110', '242', '86', '12.00', '0', NULL, '1', '2026-08-01 16:35:15', NULL, '2026-08-01 16:35:15', NULL);
INSERT INTO `notes` VALUES ('3908', '108', '189', '62', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3909', '108', '189', '63', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3910', '108', '189', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3911', '108', '190', '62', '7.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3912', '108', '190', '63', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3913', '108', '190', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3914', '108', '191', '62', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3915', '108', '191', '63', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3916', '108', '191', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3917', '108', '192', '65', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3918', '108', '192', '66', '4.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3919', '108', '192', '67', '4.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3920', '108', '192', '68', '4.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3921', '108', '193', '65', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3922', '108', '193', '66', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3923', '108', '193', '67', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3924', '108', '193', '68', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3925', '108', '194', '65', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3926', '108', '194', '66', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3927', '108', '194', '67', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3928', '108', '194', '68', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3929', '108', '195', '69', '15.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3930', '108', '195', '70', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3931', '108', '195', '71', '12.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3932', '108', '195', '72', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3933', '108', '196', '69', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3934', '108', '196', '70', '22.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3935', '108', '196', '71', '14.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3936', '108', '196', '72', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3937', '108', '197', '69', '32.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3938', '108', '197', '70', '42.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3939', '108', '197', '71', '26.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3940', '108', '197', '72', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3941', '108', '198', '73', '16.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3942', '108', '198', '74', '12.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3943', '108', '198', '75', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3944', '108', '198', '76', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3945', '108', '199', '73', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3946', '108', '199', '74', '14.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3947', '108', '199', '75', '13.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3948', '108', '199', '76', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3949', '108', '200', '73', '35.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3950', '108', '200', '74', '28.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3951', '108', '200', '75', '26.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3952', '108', '200', '76', '38.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3953', '108', '201', '77', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3954', '108', '201', '78', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3955', '108', '201', '79', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3956', '108', '201', '80', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3957', '108', '201', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3958', '108', '202', '77', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3959', '108', '202', '78', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3960', '108', '202', '79', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3961', '108', '202', '80', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3962', '108', '202', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3963', '108', '203', '77', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3964', '108', '203', '78', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3965', '108', '203', '79', '16.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3966', '108', '203', '80', '35.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3967', '108', '203', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3968', '108', '204', '82', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3969', '108', '204', '83', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3970', '108', '204', '84', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3971', '108', '204', '85', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3972', '108', '204', '86', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3973', '108', '205', '82', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3974', '108', '205', '83', '7.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3975', '108', '205', '84', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3976', '108', '205', '85', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3977', '108', '205', '86', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3978', '108', '206', '82', '15.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3979', '108', '206', '83', '16.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3980', '108', '206', '84', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3981', '108', '206', '85', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3982', '108', '206', '86', '16.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3983', '108', '207', '62', '7.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3984', '108', '207', '63', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3985', '108', '207', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3986', '108', '208', '62', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3987', '108', '208', '63', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3988', '108', '208', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3989', '108', '209', '62', '16.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3990', '108', '209', '63', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3991', '108', '209', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3992', '108', '210', '65', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3993', '108', '210', '66', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3994', '108', '210', '67', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3995', '108', '210', '68', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3996', '108', '211', '65', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3997', '108', '211', '66', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3998', '108', '211', '67', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('3999', '108', '211', '68', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4000', '108', '212', '65', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4001', '108', '212', '66', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4002', '108', '212', '67', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4003', '108', '212', '68', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4004', '108', '213', '69', '16.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4005', '108', '213', '70', '21.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4006', '108', '213', '71', '13.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4007', '108', '213', '72', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4008', '108', '214', '69', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4009', '108', '214', '70', '23.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4010', '108', '214', '71', '15.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4011', '108', '214', '72', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4012', '108', '215', '69', '34.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4013', '108', '215', '70', '44.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4014', '108', '215', '71', '27.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4015', '108', '215', '72', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4016', '108', '216', '73', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4017', '108', '216', '74', '13.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4018', '108', '216', '75', '11.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4019', '108', '216', '76', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4020', '108', '217', '73', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4021', '108', '217', '74', '15.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4022', '108', '217', '75', '14.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4023', '108', '217', '76', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4024', '108', '218', '73', '36.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4025', '108', '218', '74', '29.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4026', '108', '218', '75', '27.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4027', '108', '218', '76', '39.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4028', '108', '219', '77', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4029', '108', '219', '78', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4030', '108', '219', '79', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4031', '108', '219', '80', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4032', '108', '219', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4033', '108', '220', '77', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4034', '108', '220', '78', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4035', '108', '220', '79', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4036', '108', '220', '80', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4037', '108', '220', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4038', '108', '221', '77', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4039', '108', '221', '78', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4040', '108', '221', '79', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4041', '108', '221', '80', '36.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4042', '108', '221', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4043', '108', '222', '82', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4044', '108', '222', '83', '7.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4045', '108', '222', '84', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4046', '108', '222', '85', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4047', '108', '222', '86', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4048', '108', '223', '82', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4049', '108', '223', '83', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4050', '108', '223', '84', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4051', '108', '223', '85', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4052', '108', '223', '86', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4053', '108', '224', '82', '16.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4054', '108', '224', '83', '15.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4055', '108', '224', '84', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4056', '108', '224', '85', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4057', '108', '224', '86', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4058', '108', '225', '62', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4059', '108', '225', '63', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4060', '108', '225', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4061', '108', '226', '62', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4062', '108', '226', '63', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4063', '108', '226', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4064', '108', '227', '62', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4065', '108', '227', '63', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4066', '108', '227', '64', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4067', '108', '228', '65', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4068', '108', '228', '66', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4069', '108', '228', '67', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4070', '108', '228', '68', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4071', '108', '229', '65', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4072', '108', '229', '66', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4073', '108', '229', '67', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4074', '108', '229', '68', '5.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4075', '108', '230', '65', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4076', '108', '230', '66', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4077', '108', '230', '67', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4078', '108', '230', '68', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4079', '108', '231', '69', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4080', '108', '231', '70', '22.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4081', '108', '231', '71', '14.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4082', '108', '231', '72', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4083', '108', '232', '69', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4084', '108', '232', '70', '24.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4085', '108', '232', '71', '13.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4086', '108', '232', '72', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4087', '108', '233', '69', '33.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4088', '108', '233', '70', '45.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4089', '108', '233', '71', '28.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4090', '108', '233', '72', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4091', '108', '234', '73', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4092', '108', '234', '74', '14.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4093', '108', '234', '75', '12.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4094', '108', '234', '76', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4095', '108', '235', '73', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4096', '108', '235', '74', '15.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4097', '108', '235', '75', '14.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4098', '108', '235', '76', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4099', '108', '236', '73', '37.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4100', '108', '236', '74', '30.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4101', '108', '236', '75', '28.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4102', '108', '236', '76', '40.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4103', '108', '237', '77', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4104', '108', '237', '78', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4105', '108', '237', '79', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4106', '108', '237', '80', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4107', '108', '237', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4108', '108', '238', '77', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4109', '108', '238', '78', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4110', '108', '238', '79', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4111', '108', '238', '80', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4112', '108', '238', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4113', '108', '239', '77', '20.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4114', '108', '239', '78', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4115', '108', '239', '79', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4116', '108', '239', '80', '38.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4117', '108', '239', '81', '0.00', '1', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4118', '108', '240', '82', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4119', '108', '240', '83', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4120', '108', '240', '84', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4121', '108', '240', '85', '10.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4122', '108', '240', '86', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4123', '108', '241', '82', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4124', '108', '241', '83', '7.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4125', '108', '241', '84', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4126', '108', '241', '85', '9.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4127', '108', '241', '86', '8.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4128', '108', '242', '82', '14.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4129', '108', '242', '83', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4130', '108', '242', '84', '18.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4131', '108', '242', '85', '19.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
INSERT INTO `notes` VALUES ('4132', '108', '242', '86', '17.00', '0', NULL, '1', '2026-08-01 16:36:53', NULL, '2026-08-01 16:36:53', NULL);
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `notes_backup`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notes_backup`;
CREATE TABLE `notes_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `eleve_id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `sous_matiere_id` int(11) DEFAULT NULL,
  `valeur` decimal(5,2) NOT NULL CHECK (`valeur` >= 0 and `valeur` <= 20),
  `absence` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'absent lors de l''évaluation',
  `commentaire` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saisie_par` int(11) NOT NULL COMMENT 'utilisateur qui a saisi la note',
  `date_saisie` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_notification` enum('Information','Alerte','Rappel','Urgence','Succès') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Information',
  `lien` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL vers la ressource concernée',
  `est_lue` tinyint(1) NOT NULL DEFAULT 0,
  `date_lecture` datetime DEFAULT NULL,
  `date_envoi` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_notifications_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_notifications_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `notifications`
LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('16', '24', 'Absence justifiée', 'L\'absence de votre enfant LUKUSA Emmanuel du 05/10/2025 a été justifiée (Maladie).', 'Succès', '/gestion_scolaire/absences/liste', '0', NULL, '2025-10-06 08:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('17', '24', 'Bulletin disponible', 'Le bulletin du 1er Trimestre de votre enfant LUKUSA Emmanuel est disponible.', 'Information', '/gestion_scolaire/bulletins/consulter?eleve_id=107', '0', NULL, '2025-12-20 10:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('18', '24', 'Rappel : Réunion des parents', 'Une réunion des parents est prévue le 15/01/2026 à 14h00 dans la salle de classe.', 'Rappel', NULL, '0', NULL, '2026-01-10 09:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('19', '24', 'Paiement enregistré', 'Votre paiement de $ 150.00 pour le Minerval a été enregistré avec succès.', 'Succès', '/gestion_scolaire/paiements', '0', NULL, '2025-09-05 10:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('20', '25', 'Absence non justifiée', 'Votre enfant KABONGO Grâce a été absent(e) le 15/01/2026. Veuillez justifier cette absence.', 'Alerte', '/gestion_scolaire/absences/justifier', '0', NULL, '2026-01-15 07:45:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('21', '25', 'Retard justifié', 'Le retard de votre enfant KABONGO Grâce du 08/10/2025 a été justifié (Transport).', 'Succès', '/gestion_scolaire/retards/liste', '0', NULL, '2025-10-09 08:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('22', '25', 'Bulletin disponible', 'Le bulletin du 1er Trimestre de votre enfant KABONGO Grâce est disponible.', 'Information', '/gestion_scolaire/bulletins/consulter?eleve_id=108', '0', NULL, '2025-12-20 10:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('23', '25', 'Paiement enregistré', 'Votre paiement de $ 150.00 pour le Minerval a été enregistré avec succès.', 'Succès', '/gestion_scolaire/paiements', '0', NULL, '2025-09-10 10:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('24', '26', 'Absence non justifiée', 'Votre enfant MULONDA Daniel a été absent(e) le 12/10/2025. Veuillez justifier cette absence.', 'Alerte', '/gestion_scolaire/absences/justifier', '0', NULL, '2025-10-12 07:45:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('25', '26', 'Absence non justifiée', 'Votre enfant MULONDA Daniel a été absent(e) le 20/01/2026. Veuillez justifier cette absence.', 'Alerte', '/gestion_scolaire/absences/justifier', '0', NULL, '2026-01-20 07:45:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('26', '26', 'Retard enregistré', 'Votre enfant MULONDA Daniel est arrivé en retard le 12/09/2025 (20 minutes).', 'Alerte', '/gestion_scolaire/retards/liste', '0', NULL, '2025-09-12 08:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('27', '26', 'Rappel de paiement', 'Le Minerval de votre enfant est partiellement payé ($ 75.00 / $ 150.00). Veuillez régulariser.', 'Rappel', '/gestion_scolaire/paiements/impayes', '0', NULL, '2025-10-15 09:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('28', '27', 'Absence non justifiée', 'Votre enfant TSHIBOLA Esther a été absent(e) le 15/09/2025. Veuillez justifier cette absence.', 'Alerte', '/gestion_scolaire/absences/justifier', '0', NULL, '2025-09-15 07:45:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('29', '27', 'Retard enregistré', 'Votre enfant TSHIBOLA Esther est arrivé en retard le 25/09/2025 (18 minutes).', 'Alerte', '/gestion_scolaire/retards/liste', '0', NULL, '2025-09-25 08:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('30', '27', 'Rappel de paiement', 'Le Minerval de votre enfant est partiellement payé ($ 50.00 / $ 150.00). Veuillez régulariser.', 'Rappel', '/gestion_scolaire/paiements/impayes', '0', NULL, '2025-11-10 09:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('31', '28', 'Absence non justifiée', 'Votre enfant KANKU Joseph a été absent(e) le 08/09/2025. Veuillez justifier cette absence.', 'Alerte', '/gestion_scolaire/absences/justifier', '0', NULL, '2025-09-08 07:45:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('32', '28', 'Absence non justifiée', 'Votre enfant KANKU Joseph a été absent(e) le 22/09/2025. Veuillez justifier cette absence.', 'Alerte', '/gestion_scolaire/absences/justifier', '0', NULL, '2025-09-22 07:45:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('33', '28', 'Convocation des parents', 'Votre enfant KANKU Joseph totalise plus de 10 absences. Une convocation des parents est demandée.', 'Urgence', '/gestion_scolaire/absences/liste', '0', NULL, '2026-02-10 10:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('34', '28', 'Retard enregistré', 'Votre enfant KANKU Joseph est arrivé en retard le 05/09/2025 (22 minutes).', 'Alerte', '/gestion_scolaire/retards/liste', '0', NULL, '2025-09-05 08:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('35', '28', 'Retard enregistré', 'Votre enfant KANKU Joseph est arrivé en retard le 22/10/2025 (50 minutes).', 'Alerte', '/gestion_scolaire/retards/liste', '0', NULL, '2025-10-22 08:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('36', '28', 'Alerte retards répétés', 'Votre enfant KANKU Joseph totalise plus de 10 retards. Une convocation est demandée.', 'Urgence', '/gestion_scolaire/retards/liste', '0', NULL, '2026-03-20 10:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('37', '28', 'Paiement impayé', 'Le Minerval de votre enfant KANKU Joseph n\'a pas encore été payé ($ 150.00). Veuillez régulariser.', 'Urgence', '/gestion_scolaire/paiements/impayes', '0', NULL, '2025-10-01 09:00:00', '2026-08-02 11:39:53');
INSERT INTO `notifications` VALUES ('38', '29', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:31', '2026-08-02 13:42:31');
INSERT INTO `notifications` VALUES ('39', '30', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:31', '2026-08-02 13:42:31');
INSERT INTO `notifications` VALUES ('40', '31', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:31', '2026-08-02 13:42:31');
INSERT INTO `notifications` VALUES ('41', '32', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:31', '2026-08-02 13:42:31');
INSERT INTO `notifications` VALUES ('42', '33', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:31', '2026-08-02 13:42:31');
INSERT INTO `notifications` VALUES ('43', '29', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:32', '2026-08-02 13:42:32');
INSERT INTO `notifications` VALUES ('44', '30', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:32', '2026-08-02 13:42:32');
INSERT INTO `notifications` VALUES ('45', '31', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:32', '2026-08-02 13:42:32');
INSERT INTO `notifications` VALUES ('46', '32', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:32', '2026-08-02 13:42:32');
INSERT INTO `notifications` VALUES ('47', '33', 'RETARD', 'JUSTIFICATION', 'Rappel', NULL, '0', NULL, '2026-08-02 13:42:32', '2026-08-02 13:42:32');
INSERT INTO `notifications` VALUES ('48', '29', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('49', '30', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('50', '31', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('51', '32', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('52', '33', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('53', '29', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('54', '30', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('55', '31', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('56', '32', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('57', '33', 'SALUT', 'SALUT', 'Information', NULL, '0', NULL, '2026-08-02 13:45:38', '2026-08-02 13:45:38');
INSERT INTO `notifications` VALUES ('58', '33', 'SALUTATION', 'SALUT PAPA', 'Rappel', NULL, '0', NULL, '2026-08-02 13:57:45', '2026-08-02 13:57:45');
INSERT INTO `notifications` VALUES ('59', '33', 'SALUTATION', 'SALUT PAPA', 'Rappel', NULL, '0', NULL, '2026-08-02 13:57:45', '2026-08-02 13:57:45');
INSERT INTO `notifications` VALUES ('60', '29', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:37', '2026-08-02 13:58:37');
INSERT INTO `notifications` VALUES ('61', '30', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:38', '2026-08-02 13:58:38');
INSERT INTO `notifications` VALUES ('62', '31', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:38', '2026-08-02 13:58:38');
INSERT INTO `notifications` VALUES ('63', '32', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:39', '2026-08-02 13:58:39');
INSERT INTO `notifications` VALUES ('64', '33', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:39', '2026-08-02 13:58:39');
INSERT INTO `notifications` VALUES ('65', '29', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:41', '2026-08-02 13:58:41');
INSERT INTO `notifications` VALUES ('66', '30', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:42', '2026-08-02 13:58:42');
INSERT INTO `notifications` VALUES ('67', '31', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:43', '2026-08-02 13:58:43');
INSERT INTO `notifications` VALUES ('68', '32', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:43', '2026-08-02 13:58:43');
INSERT INTO `notifications` VALUES ('69', '33', 'REUNION', 'DEMAIN REUNION', 'Information', NULL, '0', NULL, '2026-08-02 13:58:43', '2026-08-02 13:58:43');
INSERT INTO `notifications` VALUES ('70', '33', 'retard', 'retard exagere', 'Rappel', NULL, '0', NULL, '2026-08-02 17:30:17', '2026-08-02 17:30:17');
INSERT INTO `notifications` VALUES ('71', '33', 'retard', 'retard exagere', 'Rappel', NULL, '0', NULL, '2026-08-02 17:30:21', '2026-08-02 17:30:21');
INSERT INTO `notifications` VALUES ('72', '33', 'test', 'test', 'Rappel', NULL, '0', NULL, '2026-08-02 19:06:47', '2026-08-02 19:06:47');
INSERT INTO `notifications` VALUES ('73', '33', 'test', 'test', 'Rappel', NULL, '0', NULL, '2026-08-02 19:06:50', '2026-08-02 19:06:50');
INSERT INTO `notifications` VALUES ('74', '33', 'raujuh', 'kjhgfghhhhhhhh', 'Rappel', NULL, '0', NULL, '2026-08-02 19:32:58', '2026-08-02 19:32:58');
INSERT INTO `notifications` VALUES ('75', '33', 'raujuh', 'kjhgfghhhhhhhh', 'Rappel', NULL, '0', NULL, '2026-08-02 19:33:05', '2026-08-02 19:33:05');
INSERT INTO `notifications` VALUES ('76', '33', 'raujuh', 'kjhgfghhhhhhhh', 'Rappel', NULL, '0', NULL, '2026-08-02 19:33:12', '2026-08-02 19:33:12');
INSERT INTO `notifications` VALUES ('77', '33', 'raujuh', 'kjhgfghhhhhhhh', 'Rappel', NULL, '0', NULL, '2026-08-02 19:33:20', '2026-08-02 19:33:20');
INSERT INTO `notifications` VALUES ('78', '33', 'raujuh', 'kjhgfghhhhhhhh', 'Rappel', NULL, '0', NULL, '2026-08-02 19:33:28', '2026-08-02 19:33:28');
INSERT INTO `notifications` VALUES ('79', '33', 'dhjdhdhd', 'shdhdhdhdh', 'Rappel', NULL, '0', NULL, '2026-08-02 19:34:03', '2026-08-02 19:34:03');
INSERT INTO `notifications` VALUES ('80', '33', 'dhjdhdhd', 'shdhdhdhdh', 'Rappel', NULL, '0', NULL, '2026-08-02 19:34:03', '2026-08-02 19:34:03');
INSERT INTO `notifications` VALUES ('81', '33', 'fghgggg', 'hhhhhhhh', 'Alerte', NULL, '0', NULL, '2026-08-02 19:35:39', '2026-08-02 19:35:39');
INSERT INTO `notifications` VALUES ('82', '33', 'fghgggg', 'hhhhhhhh', 'Alerte', NULL, '0', NULL, '2026-08-02 19:35:47', '2026-08-02 19:35:47');
INSERT INTO `notifications` VALUES ('83', '33', 'fghgggg', 'hhhhhhhh', 'Alerte', NULL, '0', NULL, '2026-08-02 19:35:55', '2026-08-02 19:35:55');
INSERT INTO `notifications` VALUES ('84', '33', 'ffffffffff', 'ythhjhhhhhhhhhh', 'Information', NULL, '0', NULL, '2026-08-02 19:40:31', '2026-08-02 19:40:31');
INSERT INTO `notifications` VALUES ('85', '33', 'ffffffffff', 'ythhjhhhhhhhhhh', 'Information', NULL, '0', NULL, '2026-08-02 19:40:31', '2026-08-02 19:40:31');
INSERT INTO `notifications` VALUES ('86', '33', 'ggggggggggg', 'jjjjjjjjjjjjjjjjjjjjjjjjjjj', 'Information', NULL, '0', NULL, '2026-08-02 19:41:02', '2026-08-02 19:41:02');
INSERT INTO `notifications` VALUES ('87', '33', 'ggggggggggg', 'jjjjjjjjjjjjjjjjjjjjjjjjjjj', 'Information', NULL, '0', NULL, '2026-08-02 19:41:10', '2026-08-02 19:41:10');
INSERT INTO `notifications` VALUES ('88', '33', 'hhhhhhhh', 'gggggggggggggggggggg', 'Information', NULL, '0', NULL, '2026-08-02 19:55:59', '2026-08-02 19:55:59');
INSERT INTO `notifications` VALUES ('89', '33', 'hhhhhhhh', 'gggggggggggggggggggg', 'Information', NULL, '0', NULL, '2026-08-02 19:56:07', '2026-08-02 19:56:07');
INSERT INTO `notifications` VALUES ('90', '33', 'gggggggggggg', 'bbbbbbbbbbbbbbbbbbbbbbb', 'Information', NULL, '0', NULL, '2026-08-02 20:04:04', '2026-08-02 20:04:04');
INSERT INTO `notifications` VALUES ('91', '33', 'gggggggggggg', 'bbbbbbbbbbbbbbbbbbbbbbb', 'Information', NULL, '0', NULL, '2026-08-02 20:04:05', '2026-08-02 20:04:05');
INSERT INTO `notifications` VALUES ('92', '33', 'gggggggggggg', 'ccccccccccccccccc', 'Information', NULL, '0', NULL, '2026-08-02 20:04:38', '2026-08-02 20:04:38');
INSERT INTO `notifications` VALUES ('93', '33', 'gggggggggggg', 'ccccccccccccccccc', 'Information', NULL, '0', NULL, '2026-08-02 20:04:48', '2026-08-02 20:04:48');
INSERT INTO `notifications` VALUES ('94', '33', 'llllllllll', 'hhhhhhhhhhhhhhhhhh', 'Information', NULL, '0', NULL, '2026-08-02 20:13:36', '2026-08-02 20:13:36');
INSERT INTO `notifications` VALUES ('95', '33', 'llllllllll', 'hhhhhhhhhhhhhhhhhh', 'Information', NULL, '0', NULL, '2026-08-02 20:13:44', '2026-08-02 20:13:44');
INSERT INTO `notifications` VALUES ('96', '33', 'hhhhhhh', 'hhhhhhhhhhhhhhh', 'Information', NULL, '0', NULL, '2026-08-02 21:15:00', '2026-08-02 21:15:00');
INSERT INTO `notifications` VALUES ('97', '33', 'hhhhhhh', 'hhhhhhhhhhhhhhh', 'Information', NULL, '0', NULL, '2026-08-02 21:15:06', '2026-08-02 21:15:06');
INSERT INTO `notifications` VALUES ('98', '33', 'gggggggg', 'vvvvvvvvvvvvv', 'Information', NULL, '0', NULL, '2026-08-02 21:24:21', '2026-08-02 21:24:21');
INSERT INTO `notifications` VALUES ('99', '33', 'gggggggg', 'vvvvvvvvvvvvv', 'Information', NULL, '0', NULL, '2026-08-02 21:24:28', '2026-08-02 21:24:28');
INSERT INTO `notifications` VALUES ('100', '33', 'gggggggggggg', 'fffffffffffffffffffffffffffff', 'Information', NULL, '0', NULL, '2026-08-02 21:48:19', '2026-08-02 21:48:19');
INSERT INTO `notifications` VALUES ('101', '33', 'gggggggggggg', 'fffffffffffffffffffffffffffff', 'Information', NULL, '0', NULL, '2026-08-02 21:48:24', '2026-08-02 21:48:24');
INSERT INTO `notifications` VALUES ('102', '33', 'hddddddddd', 'sggggggggggggtdddddddddddd', 'Information', NULL, '0', NULL, '2026-08-02 21:56:15', '2026-08-02 21:56:15');
INSERT INTO `notifications` VALUES ('103', '33', 'hddddddddd', 'sggggggggggggtdddddddddddd', 'Information', NULL, '0', NULL, '2026-08-02 21:56:22', '2026-08-02 21:56:22');
INSERT INTO `notifications` VALUES ('104', '33', 'fffffffff', 'sssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:57:47', '2026-08-02 21:57:47');
INSERT INTO `notifications` VALUES ('105', '33', 'fffffffff', 'sssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:57:47', '2026-08-02 21:57:47');
INSERT INTO `notifications` VALUES ('106', '33', 'dddddddddddddddd', 'sqsssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:58:17', '2026-08-02 21:58:17');
INSERT INTO `notifications` VALUES ('107', '33', 'dddddddddddddddd', 'sqsssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:58:23', '2026-08-02 21:58:23');
INSERT INTO `notifications` VALUES ('108', '29', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:58:59', '2026-08-02 21:58:59');
INSERT INTO `notifications` VALUES ('109', '30', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:05', '2026-08-02 21:59:05');
INSERT INTO `notifications` VALUES ('110', '31', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:11', '2026-08-02 21:59:11');
INSERT INTO `notifications` VALUES ('111', '32', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:16', '2026-08-02 21:59:16');
INSERT INTO `notifications` VALUES ('112', '33', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:22', '2026-08-02 21:59:22');
INSERT INTO `notifications` VALUES ('113', '29', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:27', '2026-08-02 21:59:27');
INSERT INTO `notifications` VALUES ('114', '30', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:33', '2026-08-02 21:59:33');
INSERT INTO `notifications` VALUES ('115', '31', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:40', '2026-08-02 21:59:40');
INSERT INTO `notifications` VALUES ('116', '32', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:46', '2026-08-02 21:59:46');
INSERT INTO `notifications` VALUES ('117', '33', 'gggggggggggggg', 'ssssssssssssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 21:59:53', '2026-08-02 21:59:53');
INSERT INTO `notifications` VALUES ('118', '33', 'vvvvvvvvvvvvvvvvvv', 'aaaaaaaaaaaaaaaaaaaaaaa', 'Information', NULL, '0', NULL, '2026-08-02 22:01:51', '2026-08-02 22:01:51');
INSERT INTO `notifications` VALUES ('119', '33', 'vvvvvvvvvvvvvvvvvv', 'aaaaaaaaaaaaaaaaaaaaaaa', 'Information', NULL, '0', NULL, '2026-08-02 22:02:21', '2026-08-02 22:02:21');
INSERT INTO `notifications` VALUES ('120', '32', 'hhhhhhhhhhhhhhhhhh', 'vvvvvvvvvvvvvvvvvvvvv', 'Information', NULL, '0', NULL, '2026-08-02 22:08:18', '2026-08-02 22:08:18');
INSERT INTO `notifications` VALUES ('121', '32', 'hhhhhhhhhhhhhhhhhh', 'vvvvvvvvvvvvvvvvvvvvv', 'Information', NULL, '0', NULL, '2026-08-02 22:08:22', '2026-08-02 22:08:22');
INSERT INTO `notifications` VALUES ('122', '33', 'gggggggggg', 'vvvvvvvvvvvvvv', 'Information', NULL, '0', NULL, '2026-08-02 22:12:52', '2026-08-02 22:12:52');
INSERT INTO `notifications` VALUES ('123', '32', 'sfsfsfsfsfs', 'gssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 22:14:33', '2026-08-02 22:14:33');
INSERT INTO `notifications` VALUES ('124', '32', 'hhhhhhhhhh', 'gggggggggggsddddddddddds', 'Information', NULL, '0', NULL, '2026-08-02 22:19:05', '2026-08-02 22:19:05');
INSERT INTO `notifications` VALUES ('125', '31', 'hhhhhhhhhh', 'ffffffffffff', 'Information', NULL, '0', NULL, '2026-08-02 22:20:28', '2026-08-02 22:20:28');
INSERT INTO `notifications` VALUES ('126', '33', 'hhhhh', 'xxxxxxxx', 'Information', NULL, '0', NULL, '2026-08-02 22:43:38', '2026-08-02 22:43:38');
INSERT INTO `notifications` VALUES ('127', '33', 'ggggggggg', 'sssssssssssss', 'Information', NULL, '0', NULL, '2026-08-02 22:44:09', '2026-08-02 22:44:09');
INSERT INTO `notifications` VALUES ('128', '33', 'ggggggggggg', 'vvvvvvvvvvvjjjjjjjjjjjj', 'Information', NULL, '0', NULL, '2026-08-02 22:49:11', '2026-08-02 22:49:11');
INSERT INTO `notifications` VALUES ('129', '33', 'fffffffff', 'reeeeeeeeeeeeeeeeeeeeee', 'Information', NULL, '0', NULL, '2026-08-02 22:55:19', '2026-08-02 22:55:19');
INSERT INTO `notifications` VALUES ('130', '33', 'ffffffff', 'ccccccccccccccc', 'Information', NULL, '0', NULL, '2026-08-02 22:57:47', '2026-08-02 22:57:47');
INSERT INTO `notifications` VALUES ('131', '33', 'ffffffff', 'ccccccccccccccc', 'Information', NULL, '0', NULL, '2026-08-02 22:57:51', '2026-08-02 22:57:51');
INSERT INTO `notifications` VALUES ('132', '33', 'hhhhhhhhh', 'ggggggggggg', 'Information', NULL, '0', NULL, '2026-08-02 22:58:21', '2026-08-02 22:58:21');
INSERT INTO `notifications` VALUES ('133', '33', 'hhhhhhhhh', 'ggggggggggg', 'Information', NULL, '0', NULL, '2026-08-02 22:58:27', '2026-08-02 22:58:27');
INSERT INTO `notifications` VALUES ('134', '32', 'hhhhhh', 'ffffff', 'Information', NULL, '0', NULL, '2026-08-02 23:00:05', '2026-08-02 23:00:05');
INSERT INTO `notifications` VALUES ('135', '32', 'hhhhhh', 'ffffff', 'Information', NULL, '0', NULL, '2026-08-02 23:00:06', '2026-08-02 23:00:06');
INSERT INTO `notifications` VALUES ('136', '32', 'fffff', 'ssssssss', 'Information', NULL, '0', NULL, '2026-08-02 23:00:42', '2026-08-02 23:00:42');
INSERT INTO `notifications` VALUES ('137', '32', 'fffff', 'ssssssss', 'Information', NULL, '0', NULL, '2026-08-02 23:00:46', '2026-08-02 23:00:46');
INSERT INTO `notifications` VALUES ('138', '33', 'jjjjjjj', 'kkkkkkkkkkkk', 'Information', NULL, '0', NULL, '2026-08-02 23:10:32', '2026-08-02 23:10:32');
INSERT INTO `notifications` VALUES ('139', '33', 'cccccccccccc', 'vvvvvvvvvvvvvvvvvvv', 'Information', NULL, '0', NULL, '2026-08-02 23:25:55', '2026-08-02 23:25:55');
INSERT INTO `notifications` VALUES ('140', '33', 'hhhhhhh', 'gggggggggg', 'Information', NULL, '0', NULL, '2026-08-02 23:34:10', '2026-08-02 23:34:10');
INSERT INTO `notifications` VALUES ('141', '32', 'hhhhhhhhh', 'vvvvvvvvvvvvvv', 'Information', NULL, '0', NULL, '2026-08-02 23:37:42', '2026-08-02 23:37:42');
INSERT INTO `notifications` VALUES ('142', '33', 'retard', 'Retard exagere', 'Alerte', NULL, '0', NULL, '2026-08-02 23:40:15', '2026-08-02 23:40:15');
INSERT INTO `notifications` VALUES ('143', '33', 'absent', 'passe au bureau', 'Alerte', NULL, '0', NULL, '2026-08-02 23:43:10', '2026-08-02 23:43:10');
INSERT INTO `notifications` VALUES ('144', '32', 'urgence grave', 'arrive vite', 'Urgence', NULL, '0', NULL, '2026-08-02 23:46:26', '2026-08-02 23:46:26');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `paiements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE `paiements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `frais_scolaire_id` int(11) NOT NULL,
  `montant_total` decimal(10,2) NOT NULL COMMENT 'montant dû pour ce frais',
  `montant_paye` decimal(10,2) NOT NULL COMMENT 'montant versé',
  `reste_a_payer` decimal(10,2) GENERATED ALWAYS AS (`montant_total` - `montant_paye`) STORED COMMENT 'calculé automatiquement',
  `date_paiement` date NOT NULL DEFAULT curdate(),
  `mode_paiement` enum('Espèces','Chèque','Virement bancaire','Mobile Money','Autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Espèces',
  `reference_paiement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'numéro chèque, référence virement, etc.',
  `banque` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('Payé','Partiel','Impayé') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Impayé',
  `commentaire` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enregistre_par` int(11) NOT NULL COMMENT 'utilisateur ayant enregistré le paiement',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_paiements_eleve` (`eleve_id`),
  KEY `fk_paiements_frais` (`frais_scolaire_id`),
  KEY `fk_paiements_utilisateur` (`enregistre_par`),
  CONSTRAINT `fk_paiements_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_paiements_frais` FOREIGN KEY (`frais_scolaire_id`) REFERENCES `frais_scolaires` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_paiements_utilisateur` FOREIGN KEY (`enregistre_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `paiements`
LOCK TABLES `paiements` WRITE;
/*!40000 ALTER TABLE `paiements` DISABLE KEYS */;
INSERT INTO `paiements` VALUES ('142', '110', '15', '500.00', '200.00', '300.00', '2026-08-01', 'Espèces', NULL, NULL, 'Partiel', NULL, '14', '2026-08-01 22:22:54', '2026-08-01 22:22:54');
INSERT INTO `paiements` VALUES ('143', '108', '15', '500.00', '140.00', '360.00', '2026-08-01', 'Espèces', NULL, NULL, 'Partiel', NULL, '14', '2026-08-01 22:24:19', '2026-08-01 22:28:07');
INSERT INTO `paiements` VALUES ('144', '109', '15', '500.00', '350.00', '150.00', '2026-08-01', 'Espèces', NULL, NULL, 'Partiel', NULL, '14', '2026-08-01 22:31:37', '2026-08-02 10:45:59');
INSERT INTO `paiements` VALUES ('145', '111', '15', '500.00', '40.00', '460.00', '2026-08-02', 'Espèces', NULL, NULL, 'Partiel', NULL, '14', '2026-08-02 10:31:00', '2026-08-02 10:33:58');
INSERT INTO `paiements` VALUES ('146', '107', '15', '500.00', '10.00', '490.00', '2026-08-02', 'Espèces', NULL, NULL, 'Partiel', NULL, '14', '2026-08-02 10:49:11', NULL);
/*!40000 ALTER TABLE `paiements` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `parametres_ecole`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `parametres_ecole`;
CREATE TABLE `parametres_ecole` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_ecole` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `devise` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_web` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annee_scolaire_active_id` int(11) DEFAULT NULL,
  `trimestre_actif_id` int(11) DEFAULT NULL,
  `nb_periodes_par_trimestre` tinyint(4) NOT NULL DEFAULT 2,
  `bareme_note_max` decimal(5,2) NOT NULL DEFAULT 20.00,
  `moyenne_passage` decimal(5,2) NOT NULL DEFAULT 10.00,
  `pourcentage_interro` decimal(5,2) NOT NULL DEFAULT 30.00,
  `pourcentage_devoir` decimal(5,2) NOT NULL DEFAULT 30.00,
  `pourcentage_examen` decimal(5,2) NOT NULL DEFAULT 40.00,
  `format_matricule` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EL-{ANNEE}-{ID}',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `pays` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'RÉPUBLIQUE DÉMOCRATIQUE DU CONGO',
  `ministere` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'MINISTÈRE DE L''ENSEIGNEMENT PRIMAIRE, SECONDAIRE ET PROFESSIONNEL',
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `commune_territoire` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `code_etablissement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `smtp_host` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'smtp.gmail.com',
  `smtp_port` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '587',
  `smtp_username` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `smtp_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `smtp_encryption` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'tls',
  `email_notification` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `fk_param_annee` (`annee_scolaire_active_id`),
  KEY `fk_param_trimestre` (`trimestre_actif_id`),
  CONSTRAINT `fk_param_annee` FOREIGN KEY (`annee_scolaire_active_id`) REFERENCES `annees_scolaires` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_param_trimestre` FOREIGN KEY (`trimestre_actif_id`) REFERENCES `trimestres` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `parametres_ecole`
LOCK TABLES `parametres_ecole` WRITE;
/*!40000 ALTER TABLE `parametres_ecole` DISABLE KEYS */;
INSERT INTO `parametres_ecole` VALUES ('1', 'C S GASON', 'Discipline - Travail - Excellence', '123, Avenue de l\'Éducation, Quartier Centre, Ville', '+243 984 270 438', 'barakantwali06@gmail.com', NULL, 'uploads/logos/logo_1785927280.jpg', '5', '13', '2', '20.00', '10.00', '30.00', '30.00', '40.00', 'EL-{ANNEE}-{ID}', '2026-07-29 18:23:30', '2026-08-05 12:54:40', 'RÉPUBLIQUE DÉMOCRATIQUE DU CONGO', 'MINISTÈRE DE L\'ENSEIGNEMENT PRIMAIRE, SECONDAIRE ET PROFESSIONNEL', NULL, NULL, NULL, 'smtp.gmail.com', '587', 'barakantwali06@gmail.com', 'iqrhxcjkllywytwo', 'tls', '');
/*!40000 ALTER TABLE `parametres_ecole` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `parents_tuteurs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `parents_tuteurs`;
CREATE TABLE `parents_tuteurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lien` enum('Père','Mère','Tuteur','Tutrice','Grand-père','Grand-mère','Oncle','Tante','Frère','Sœur','Autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `profession` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_principal` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_secondaire` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_contact_urgence` tinyint(1) NOT NULL DEFAULT 1,
  `utilisateur_id` int(11) DEFAULT NULL COMMENT 'compte parent pour suivi en ligne',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_parents_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_parents_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `parents_tuteurs`
LOCK TABLES `parents_tuteurs` WRITE;
/*!40000 ALTER TABLE `parents_tuteurs` DISABLE KEYS */;
INSERT INTO `parents_tuteurs` VALUES ('50', 'LUKUSA', 'Marc', 'Père', NULL, '+243810000002', NULL, 'marc.lukusa@gmail.com', '45 Avenue Lumumba', '1', '29', '2026-08-02 13:40:24', '2026-08-02 13:40:25');
INSERT INTO `parents_tuteurs` VALUES ('51', 'KABONGO', 'Marie', 'Mère', NULL, '+243810000004', NULL, 'marie.kabongo@gmail.com', '78 Avenue Kasa-Vubu', '1', '30', '2026-08-02 13:40:24', '2026-08-02 13:40:25');
INSERT INTO `parents_tuteurs` VALUES ('52', 'MULONDA', 'Jean', 'Père', NULL, '+243810000006', NULL, 'jean.mulonda@gmail.com', '12 Avenue Moero', '1', '31', '2026-08-02 13:40:24', '2026-08-02 13:40:25');
INSERT INTO `parents_tuteurs` VALUES ('53', 'TSHIBOLA', 'Sarah', 'Mère', NULL, '+243810000008', NULL, 'felixbaraka002@gmail.com', '34 Avenue Tanganyika', '1', '32', '2026-08-02 13:40:24', '2026-08-02 22:07:44');
INSERT INTO `parents_tuteurs` VALUES ('54', 'KANKU', 'Paul', 'Tuteur', NULL, '+243810000010', NULL, 'barakantwali06@gmail.com', '56 Avenue Kivu', '1', '33', '2026-08-02 13:40:24', '2026-08-02 17:27:44');
INSERT INTO `parents_tuteurs` VALUES ('55', 'Vvvvvvvvv', 'Bbbbbbbbbbbb', 'Tante', 'bbbbbbbb', '99999999', NULL, 'parent@gmail.com', NULL, '1', NULL, '2026-08-04 01:04:59', NULL);
/*!40000 ALTER TABLE `parents_tuteurs` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: eleves.creer, notes.consulter',
  `libelle` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: Créer un élève',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: eleves, notes, utilisateurs',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: creer, modifier, supprimer, consulter',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `permissions`
LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES ('1', 'dashboard.consulter', 'Voir le tableau de bord', 'Accéder au tableau de bord principal', 'dashboard', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('2', 'utilisateurs.consulter', 'Voir les utilisateurs', 'Consulter la liste des utilisateurs', 'utilisateurs', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('3', 'utilisateurs.creer', 'Créer un utilisateur', 'Ajouter un nouvel utilisateur', 'utilisateurs', 'creer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('4', 'utilisateurs.modifier', 'Modifier un utilisateur', 'Modifier les informations d\'un utilisateur', 'utilisateurs', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('5', 'utilisateurs.supprimer', 'Supprimer un utilisateur', 'Supprimer un compte utilisateur', 'utilisateurs', 'supprimer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('6', 'utilisateurs.activer', 'Activer/Désactiver un utilisateur', 'Changer le statut actif/inactif', 'utilisateurs', 'activer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('7', 'roles.consulter', 'Voir les rôles', 'Consulter la liste des rôles', 'roles', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('8', 'roles.creer', 'Créer un rôle', 'Ajouter un nouveau rôle', 'roles', 'creer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('9', 'roles.modifier', 'Modifier un rôle', 'Modifier les permissions d\'un rôle', 'roles', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('10', 'roles.supprimer', 'Supprimer un rôle', 'Supprimer un rôle', 'roles', 'supprimer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('11', 'eleves.consulter', 'Voir les élèves', 'Consulter la liste des élèves', 'eleves', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('12', 'eleves.creer', 'Ajouter un élève', 'Inscrire un nouvel élève', 'eleves', 'creer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('13', 'eleves.modifier', 'Modifier un élève', 'Modifier les informations d\'un élève', 'eleves', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('14', 'eleves.supprimer', 'Supprimer un élève', 'Supprimer un élève du système', 'eleves', 'supprimer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('15', 'eleves.importer', 'Importer des élèves', 'Importer une liste d\'élèves (CSV)', 'eleves', 'importer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('16', 'eleves.exporter', 'Exporter des élèves', 'Exporter la liste des élèves', 'eleves', 'exporter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('17', 'enseignants.consulter', 'Voir les enseignants', 'Consulter la liste des enseignants', 'enseignants', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('18', 'enseignants.creer', 'Ajouter un enseignant', 'Ajouter un nouvel enseignant', 'enseignants', 'creer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('19', 'enseignants.modifier', 'Modifier un enseignant', 'Modifier les informations d\'un enseignant', 'enseignants', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('20', 'enseignants.supprimer', 'Supprimer un enseignant', 'Supprimer un enseignant', 'enseignants', 'supprimer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('21', 'parents.consulter', 'Voir les parents', 'Consulter la liste des parents', 'parents', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('22', 'parents.creer', 'Ajouter un parent', 'Ajouter un nouveau parent/tuteur', 'parents', 'creer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('23', 'parents.modifier', 'Modifier un parent', 'Modifier les informations d\'un parent', 'parents', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('24', 'parents.supprimer', 'Supprimer un parent', 'Supprimer un parent', 'parents', 'supprimer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('25', 'classes.consulter', 'Voir les classes', 'Consulter la liste des classes', 'classes', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('26', 'classes.creer', 'Créer une classe', 'Créer une nouvelle classe', 'classes', 'creer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('27', 'classes.modifier', 'Modifier une classe', 'Modifier les informations d\'une classe', 'classes', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('28', 'classes.supprimer', 'Supprimer une classe', 'Supprimer une classe', 'classes', 'supprimer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('29', 'matieres.consulter', 'Voir les matières', 'Consulter la liste des matières', 'matieres', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('30', 'matieres.creer', 'Ajouter une matière', 'Ajouter une nouvelle matière', 'matieres', 'creer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('31', 'matieres.modifier', 'Modifier une matière', 'Modifier les informations d\'une matière', 'matieres', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('32', 'matieres.supprimer', 'Supprimer une matière', 'Supprimer une matière', 'matieres', 'supprimer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('33', 'inscriptions.consulter', 'Voir les inscriptions', 'Consulter la liste des inscriptions', 'inscriptions', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('34', 'inscriptions.creer', 'Inscrire un élève', 'Effectuer une nouvelle inscription', 'inscriptions', 'creer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('35', 'inscriptions.modifier', 'Modifier une inscription', 'Modifier le statut d\'une inscription', 'inscriptions', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('36', 'notes.consulter', 'Voir les notes', 'Consulter les notes des élèves', 'notes', 'consulter', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('37', 'notes.saisir', 'Saisir des notes', 'Saisir les notes des évaluations', 'notes', 'saisir', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('38', 'notes.modifier', 'Modifier des notes', 'Modifier des notes existantes', 'notes', 'modifier', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('39', 'notes.supprimer', 'Supprimer des notes', 'Supprimer des notes', 'notes', 'supprimer', '2026-07-29 23:00:54', NULL);
INSERT INTO `permissions` VALUES ('40', 'evaluations.consulter', 'Voir les évaluations', 'Consulter la liste des évaluations', 'evaluations', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('41', 'evaluations.creer', 'Créer une évaluation', 'Créer une nouvelle évaluation', 'evaluations', 'creer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('42', 'evaluations.modifier', 'Modifier une évaluation', 'Modifier une évaluation', 'evaluations', 'modifier', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('43', 'evaluations.supprimer', 'Supprimer une évaluation', 'Supprimer une évaluation', 'evaluations', 'supprimer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('44', 'resultats.consulter', 'Voir les résultats', 'Consulter les résultats scolaires', 'resultats', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('45', 'resultats.calculer', 'Calculer les résultats', 'Lancer le calcul des moyennes et rangs', 'resultats', 'calculer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('46', 'resultats.exporter', 'Exporter les résultats', 'Exporter les procès-verbaux', 'resultats', 'exporter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('47', 'bulletins.consulter', 'Voir les bulletins', 'Consulter les bulletins scolaires', 'bulletins', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('48', 'bulletins.generer', 'Générer les bulletins', 'Générer les bulletins d\'un trimestre', 'bulletins', 'generer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('49', 'bulletins.imprimer', 'Imprimer les bulletins', 'Imprimer les bulletins', 'bulletins', 'imprimer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('50', 'emploi.consulter', 'Voir l\'emploi du temps', 'Consulter l\'emploi du temps', 'emploi', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('51', 'emploi.creer', 'Créer un créneau', 'Ajouter un créneau horaire', 'emploi', 'creer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('52', 'emploi.modifier', 'Modifier l\'emploi du temps', 'Modifier les créneaux', 'emploi', 'modifier', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('53', 'emploi.supprimer', 'Supprimer un créneau', 'Supprimer un créneau horaire', 'emploi', 'supprimer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('54', 'absences.consulter', 'Voir les absences', 'Consulter la liste des absences', 'absences', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('55', 'absences.marquer', 'Marquer les absences', 'Marquer les présences/absences', 'absences', 'marquer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('56', 'absences.justifier', 'Justifier une absence', 'Ajouter un justificatif', 'absences', 'justifier', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('57', 'retards.consulter', 'Voir les retards', 'Consulter la liste des retards', 'retards', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('58', 'retards.enregistrer', 'Enregistrer un retard', 'Signaler un retard', 'retards', 'enregistrer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('59', 'discipline.consulter', 'Voir la discipline', 'Consulter les incidents disciplinaires', 'discipline', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('60', 'discipline.signaler', 'Signaler un incident', 'Signaler un nouvel incident', 'discipline', 'signaler', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('61', 'discipline.sanctionner', 'Appliquer une sanction', 'Appliquer une sanction disciplinaire', 'discipline', 'sanctionner', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('62', 'paiements.consulter', 'Voir les paiements', 'Consulter la liste des paiements', 'paiements', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('63', 'paiements.enregistrer', 'Enregistrer un paiement', 'Enregistrer un nouveau paiement', 'paiements', 'enregistrer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('64', 'paiements.modifier', 'Modifier un paiement', 'Modifier un paiement', 'paiements', 'modifier', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('65', 'paiements.supprimer', 'Supprimer un paiement', 'Annuler un paiement', 'paiements', 'supprimer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('66', 'paiements.factures', 'Gérer les factures', 'Générer et imprimer des factures', 'paiements', 'factures', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('67', 'cantine.consulter', 'Voir la cantine', 'Consulter le service de cantine', 'cantine', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('68', 'cantine.gerer', 'Gérer la cantine', 'Gérer les inscriptions et menus', 'cantine', 'gerer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('69', 'transport.consulter', 'Voir le transport', 'Consulter le service de transport', 'transport', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('70', 'transport.gerer', 'Gérer le transport', 'Gérer les trajets et inscriptions', 'transport', 'gerer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('71', 'calendrier.consulter', 'Voir le calendrier', 'Consulter le calendrier scolaire', 'calendrier', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('72', 'calendrier.creer', 'Ajouter un événement', 'Ajouter un événement au calendrier', 'calendrier', 'creer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('73', 'calendrier.modifier', 'Modifier un événement', 'Modifier un événement', 'calendrier', 'modifier', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('74', 'calendrier.supprimer', 'Supprimer un événement', 'Supprimer un événement', 'calendrier', 'supprimer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('75', 'messages.consulter', 'Voir les messages', 'Consulter la boîte de réception', 'messages', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('76', 'messages.envoyer', 'Envoyer un message', 'Envoyer un nouveau message', 'messages', 'envoyer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('77', 'messages.supprimer', 'Supprimer un message', 'Supprimer un message', 'messages', 'supprimer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('78', 'documents.consulter', 'Voir les documents', 'Consulter les documents', 'documents', 'consulter', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('79', 'documents.uploader', 'Téléverser un document', 'Ajouter un nouveau document', 'documents', 'uploader', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('80', 'documents.supprimer', 'Supprimer un document', 'Supprimer un document', 'documents', 'supprimer', '2026-07-29 23:00:55', NULL);
INSERT INTO `permissions` VALUES ('81', 'rapports.consulter', 'Voir les rapports', 'Consulter les rapports et statistiques', 'rapports', 'consulter', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('82', 'rapports.exporter', 'Exporter les rapports', 'Exporter les rapports en Excel/PDF', 'rapports', 'exporter', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('83', 'parametres.consulter', 'Voir les paramètres', 'Consulter les paramètres du système', 'parametres', 'consulter', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('84', 'parametres.modifier', 'Modifier les paramètres', 'Modifier la configuration du système', 'parametres', 'modifier', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('85', 'sauvegardes.consulter', 'Voir les sauvegardes', 'Consulter l\'historique des sauvegardes', 'sauvegardes', 'consulter', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('86', 'sauvegardes.creer', 'Créer une sauvegarde', 'Effectuer une sauvegarde manuelle', 'sauvegardes', 'creer', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('87', 'sauvegardes.restaurer', 'Restaurer une sauvegarde', 'Restaurer la base de données', 'sauvegardes', 'restaurer', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('88', 'sauvegardes.supprimer', 'Supprimer une sauvegarde', 'Supprimer un fichier de sauvegarde', 'sauvegardes', 'supprimer', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('89', 'logs.consulter', 'Voir les journaux', 'Consulter les journaux d\'activité', 'logs', 'consulter', '2026-07-29 23:00:56', NULL);
INSERT INTO `permissions` VALUES ('90', 'logs.supprimer', 'Purger les journaux', 'Supprimer les anciens journaux', 'logs', 'supprimer', '2026-07-29 23:00:56', NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `resultats_trimestriels`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `resultats_trimestriels`;
CREATE TABLE `resultats_trimestriels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `trimestre_id` int(11) NOT NULL,
  `moyenne_generale` decimal(5,2) DEFAULT NULL COMMENT 'moyenne de toutes les matières',
  `total_points` decimal(7,2) DEFAULT NULL COMMENT 'somme des (moyenne_trimestre × coefficient)',
  `total_coefficients` int(11) DEFAULT NULL COMMENT 'somme des coefficients',
  `rang` int(11) DEFAULT NULL COMMENT 'rang dans la classe',
  `effectif_classe` int(11) DEFAULT NULL COMMENT 'nombre d''élèves dans la classe à ce trimestre',
  `mention` enum('Échec','Insuffisant','Passable','Assez Bien','Bien','Très Bien','Excellent') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decision` enum('Passe','Ajourné','Redouble','Exclu') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decision_finale` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'décision du conseil de classe',
  `appreciation_conseil` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_resultat` (`eleve_id`,`trimestre_id`),
  KEY `fk_resultats_classe` (`classe_id`),
  KEY `fk_resultats_trimestre` (`trimestre_id`),
  CONSTRAINT `fk_resultats_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_resultats_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resultats_trimestre` FOREIGN KEY (`trimestre_id`) REFERENCES `trimestres` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `resultats_trimestriels`
LOCK TABLES `resultats_trimestriels` WRITE;
/*!40000 ALTER TABLE `resultats_trimestriels` DISABLE KEYS */;
INSERT INTO `resultats_trimestriels` VALUES ('76', '107', '21', '13', '79.40', '953.00', '1200', '2', '5', 'Bien', '', '0', '', '2026-08-01 16:48:56', '2026-08-01 17:07:42');
INSERT INTO `resultats_trimestriels` VALUES ('77', '108', '21', '13', '80.80', '970.00', '1200', '1', '5', 'Très Bien', '', '0', 'Cool', '2026-08-01 16:48:56', '2026-08-01 17:07:42');
INSERT INTO `resultats_trimestriels` VALUES ('78', '109', '21', '13', '0.00', '0.00', '1200', '5', '5', 'Insuffisant', 'Ajourné', '0', '', '2026-08-01 16:48:57', '2026-08-01 17:07:42');
INSERT INTO `resultats_trimestriels` VALUES ('79', '110', '21', '13', '43.20', '518.00', '1200', '3', '5', 'Insuffisant', 'Ajourné', '0', '', '2026-08-01 16:48:57', '2026-08-01 17:07:42');
INSERT INTO `resultats_trimestriels` VALUES ('80', '111', '21', '13', '15.80', '189.00', '1200', '4', '5', 'Insuffisant', 'Ajourné', '0', 'Mauvaise', '2026-08-01 16:48:57', '2026-08-01 17:07:42');
INSERT INTO `resultats_trimestriels` VALUES ('81', '107', '21', '14', '79.70', '956.00', '1200', '2', '5', 'Bien', '', '0', NULL, '2026-08-01 16:49:08', '2026-08-01 16:49:08');
INSERT INTO `resultats_trimestriels` VALUES ('82', '108', '21', '14', '83.90', '1007.00', '1200', '1', '5', 'Très Bien', '', '0', NULL, '2026-08-01 16:49:08', '2026-08-01 16:49:08');
INSERT INTO `resultats_trimestriels` VALUES ('83', '109', '21', '14', '0.00', '0.00', '1200', '5', '5', 'Insuffisant', 'Ajourné', '0', NULL, '2026-08-01 16:49:08', '2026-08-01 16:49:08');
INSERT INTO `resultats_trimestriels` VALUES ('84', '110', '21', '14', '48.70', '584.00', '1200', '3', '5', 'Insuffisant', 'Ajourné', '0', NULL, '2026-08-01 16:49:08', '2026-08-01 16:49:08');
INSERT INTO `resultats_trimestriels` VALUES ('85', '111', '21', '14', '20.80', '250.00', '1200', '4', '5', 'Insuffisant', 'Ajourné', '0', NULL, '2026-08-01 16:49:08', '2026-08-01 16:49:08');
INSERT INTO `resultats_trimestriels` VALUES ('86', '107', '21', '15', '83.50', '1002.00', '1200', '2', '5', 'Très Bien', '', '0', NULL, '2026-08-01 16:49:16', '2026-08-01 16:49:16');
INSERT INTO `resultats_trimestriels` VALUES ('87', '108', '21', '15', '86.00', '1032.00', '1200', '1', '5', 'Très Bien', '', '0', NULL, '2026-08-01 16:49:16', '2026-08-01 16:49:16');
INSERT INTO `resultats_trimestriels` VALUES ('88', '109', '21', '15', '0.00', '0.00', '1200', '5', '5', 'Insuffisant', 'Ajourné', '0', NULL, '2026-08-01 16:49:16', '2026-08-01 16:49:16');
INSERT INTO `resultats_trimestriels` VALUES ('89', '110', '21', '15', '53.30', '640.00', '1200', '3', '5', 'Passable', '', '0', NULL, '2026-08-01 16:49:16', '2026-08-01 16:49:16');
INSERT INTO `resultats_trimestriels` VALUES ('90', '111', '21', '15', '24.70', '296.00', '1200', '4', '5', 'Insuffisant', 'Ajourné', '0', NULL, '2026-08-01 16:49:16', '2026-08-01 16:49:16');
/*!40000 ALTER TABLE `resultats_trimestriels` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `retards`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `retards`;
CREATE TABLE `retards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `date_retard` date NOT NULL,
  `heure_arrivee` time NOT NULL,
  `heure_prevue` time NOT NULL DEFAULT '07:30:00',
  `duree_retard` int(11) NOT NULL COMMENT 'en minutes',
  `motif` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_justifie` tinyint(1) NOT NULL DEFAULT 0,
  `saisie_par` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_retards_eleve` (`eleve_id`),
  KEY `fk_retards_saisie` (`saisie_par`),
  CONSTRAINT `fk_retards_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_retards_saisie` FOREIGN KEY (`saisie_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `retards`
LOCK TABLES `retards` WRITE;
/*!40000 ALTER TABLE `retards` DISABLE KEYS */;
INSERT INTO `retards` VALUES ('10', '107', '2026-01-20', '07:45:00', '07:30:00', '15', 'Réveil tardif', '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('11', '108', '2025-10-08', '07:55:00', '07:30:00', '25', 'Transport — Embouteillage', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('12', '108', '2026-02-10', '08:10:00', '07:30:00', '40', 'Intempéries — Pluie diluvienne', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('13', '108', '2026-05-15', '07:38:00', '07:30:00', '8', 'Réveil tardif', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('14', '109', '2025-09-12', '07:50:00', '07:30:00', '20', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('15', '109', '2025-11-15', '08:00:00', '07:30:00', '30', 'Transport — Panne de bus', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('16', '109', '2026-01-08', '07:42:00', '07:30:00', '12', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('17', '109', '2026-03-20', '08:25:00', '07:30:00', '55', 'Maladie — Consultation matinale', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('18', '109', '2026-06-10', '07:35:00', '07:30:00', '5', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('19', '110', '2025-09-25', '07:48:00', '07:30:00', '18', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('20', '110', '2025-10-15', '08:05:00', '07:30:00', '35', 'Transport', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('21', '110', '2025-11-08', '07:38:00', '07:30:00', '8', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('22', '110', '2025-12-12', '07:55:00', '07:30:00', '25', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('23', '110', '2026-02-05', '08:15:00', '07:30:00', '45', 'Intempéries', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('24', '110', '2026-03-22', '07:42:00', '07:30:00', '12', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('25', '110', '2026-05-08', '07:50:00', '07:30:00', '20', 'Réveil tardif', '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('26', '110', '2026-07-12', '08:30:00', '07:30:00', '60', 'Maladie — Certificat médical', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('27', '111', '2025-09-05', '07:52:00', '07:30:00', '22', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('28', '111', '2025-09-18', '08:10:00', '07:30:00', '40', 'Réveil tardif', '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('29', '111', '2025-10-02', '07:45:00', '07:30:00', '15', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('30', '111', '2025-10-22', '08:20:00', '07:30:00', '50', 'Transport', '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('31', '111', '2025-11-10', '07:38:00', '07:30:00', '8', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('32', '111', '2025-11-28', '08:00:00', '07:30:00', '30', 'Intempéries', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('33', '111', '2025-12-15', '07:55:00', '07:30:00', '25', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('34', '111', '2026-01-12', '08:15:00', '07:30:00', '45', 'Réveil tardif', '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('35', '111', '2026-02-08', '07:42:00', '07:30:00', '12', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('36', '111', '2026-02-25', '08:30:00', '07:30:00', '60', 'Transport — Panne', '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('37', '111', '2026-03-18', '07:48:00', '07:30:00', '18', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('38', '111', '2026-04-05', '08:05:00', '07:30:00', '35', 'Maladie', '1', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('39', '111', '2026-05-02', '07:35:00', '07:30:00', '5', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('40', '111', '2026-05-28', '08:10:00', '07:30:00', '40', 'Réveil tardif', '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('41', '111', '2026-06-15', '07:50:00', '07:30:00', '20', NULL, '0', '1', '2026-08-01 20:44:04', NULL);
INSERT INTO `retards` VALUES ('42', '108', '2026-08-01', '08:00:00', '07:30:00', '30', 'Transport', '1', '14', '2026-08-01 20:45:26', NULL);
INSERT INTO `retards` VALUES ('43', '107', '2026-08-01', '10:00:00', '07:30:00', '150', 'Maladie', '0', '14', '2026-08-01 20:45:46', NULL);
/*!40000 ALTER TABLE `retards` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `role_permission`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `role_permission`;
CREATE TABLE `role_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=410 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `role_permission`
LOCK TABLES `role_permission` WRITE;
/*!40000 ALTER TABLE `role_permission` DISABLE KEYS */;
INSERT INTO `role_permission` VALUES ('1', '1', '54', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('2', '1', '56', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('3', '1', '55', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('4', '1', '47', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('5', '1', '48', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('6', '1', '49', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('7', '1', '71', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('8', '1', '72', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('9', '1', '73', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('10', '1', '74', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('11', '1', '67', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('12', '1', '68', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('13', '1', '25', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('14', '1', '26', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('15', '1', '27', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('16', '1', '28', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('17', '1', '1', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('18', '1', '59', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('19', '1', '61', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('20', '1', '60', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('21', '1', '78', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('22', '1', '80', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('23', '1', '79', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('24', '1', '11', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('25', '1', '12', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('26', '1', '16', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('27', '1', '15', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('28', '1', '13', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('29', '1', '14', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('30', '1', '50', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('31', '1', '51', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('32', '1', '52', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('33', '1', '53', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('34', '1', '17', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('35', '1', '18', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('36', '1', '19', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('37', '1', '20', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('38', '1', '40', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('39', '1', '41', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('40', '1', '42', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('41', '1', '43', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('42', '1', '33', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('43', '1', '34', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('44', '1', '35', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('45', '1', '89', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('46', '1', '90', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('47', '1', '29', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('48', '1', '30', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('49', '1', '31', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('50', '1', '32', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('51', '1', '75', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('52', '1', '76', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('53', '1', '77', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('54', '1', '36', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('55', '1', '38', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('56', '1', '37', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('57', '1', '39', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('58', '1', '62', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('59', '1', '63', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('60', '1', '66', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('61', '1', '64', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('62', '1', '65', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('63', '1', '83', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('64', '1', '84', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('65', '1', '21', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('66', '1', '22', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('67', '1', '23', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('68', '1', '24', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('69', '1', '81', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('70', '1', '82', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('71', '1', '45', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('72', '1', '44', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('73', '1', '46', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('74', '1', '57', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('75', '1', '58', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('76', '1', '7', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('77', '1', '8', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('78', '1', '9', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('79', '1', '10', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('80', '1', '85', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('81', '1', '86', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('82', '1', '87', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('83', '1', '88', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('84', '1', '69', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('85', '1', '70', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('86', '1', '6', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('87', '1', '2', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('88', '1', '3', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('89', '1', '4', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('90', '1', '5', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('128', '2', '54', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('129', '2', '56', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('130', '2', '55', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('131', '2', '47', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('132', '2', '48', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('133', '2', '49', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('134', '2', '71', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('135', '2', '72', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('136', '2', '73', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('137', '2', '74', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('138', '2', '67', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('139', '2', '68', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('140', '2', '25', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('141', '2', '26', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('142', '2', '27', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('143', '2', '28', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('144', '2', '1', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('145', '2', '59', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('146', '2', '61', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('147', '2', '60', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('148', '2', '78', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('149', '2', '80', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('150', '2', '79', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('151', '2', '11', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('152', '2', '12', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('153', '2', '16', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('154', '2', '15', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('155', '2', '13', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('156', '2', '14', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('157', '2', '50', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('158', '2', '51', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('159', '2', '52', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('160', '2', '53', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('161', '2', '17', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('162', '2', '18', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('163', '2', '19', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('164', '2', '20', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('165', '2', '40', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('166', '2', '41', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('167', '2', '42', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('168', '2', '43', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('169', '2', '33', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('170', '2', '34', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('171', '2', '35', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('173', '2', '29', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('174', '2', '30', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('175', '2', '31', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('176', '2', '32', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('177', '2', '75', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('178', '2', '76', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('179', '2', '77', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('180', '2', '36', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('181', '2', '38', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('182', '2', '37', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('183', '2', '39', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('184', '2', '62', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('185', '2', '63', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('186', '2', '66', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('187', '2', '64', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('188', '2', '65', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('189', '2', '83', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('190', '2', '84', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('191', '2', '21', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('192', '2', '22', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('193', '2', '23', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('194', '2', '24', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('195', '2', '81', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('196', '2', '82', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('197', '2', '45', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('198', '2', '44', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('199', '2', '46', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('200', '2', '57', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('201', '2', '58', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('209', '2', '69', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('210', '2', '70', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('211', '2', '6', '2026-07-29 23:00:56');
INSERT INTO `role_permission` VALUES ('295', '4', '1', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('296', '4', '11', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('297', '4', '36', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('298', '4', '37', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('299', '4', '40', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('300', '4', '44', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('301', '4', '47', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('302', '4', '50', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('303', '4', '54', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('304', '4', '55', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('305', '4', '57', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('306', '4', '58', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('307', '4', '71', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('308', '4', '75', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('309', '4', '78', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('310', '4', '48', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('311', '4', '76', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('312', '4', '38', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('313', '4', '45', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('317', '5', '1', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('318', '5', '62', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('319', '5', '63', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('320', '5', '66', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('321', '5', '67', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('322', '5', '68', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('323', '5', '69', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('324', '5', '70', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('325', '5', '75', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('326', '5', '81', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('327', '5', '82', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('332', '5', '11', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('333', '5', '76', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('334', '5', '64', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('339', '6', '1', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('340', '6', '36', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('341', '6', '47', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('342', '6', '54', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('343', '6', '57', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('344', '6', '59', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('345', '6', '62', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('346', '6', '71', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('347', '6', '75', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('354', '6', '76', '2026-07-29 23:05:39');
INSERT INTO `role_permission` VALUES ('364', '3', '54', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('365', '3', '56', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('366', '3', '55', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('367', '3', '71', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('368', '3', '72', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('369', '3', '73', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('370', '3', '25', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('371', '3', '1', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('372', '3', '78', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('373', '3', '79', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('374', '3', '11', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('375', '3', '12', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('376', '3', '16', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('377', '3', '15', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('378', '3', '13', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('379', '3', '50', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('380', '3', '51', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('381', '3', '52', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('382', '3', '17', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('383', '3', '33', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('384', '3', '34', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('385', '3', '35', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('386', '3', '75', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('387', '3', '76', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('388', '3', '62', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('389', '3', '63', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('390', '3', '21', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('391', '3', '22', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('392', '3', '23', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('393', '3', '57', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('394', '3', '58', '2026-07-30 12:31:40');
INSERT INTO `role_permission` VALUES ('396', '4', '12', '2026-08-04 00:31:55');
INSERT INTO `role_permission` VALUES ('397', '4', '51', '2026-08-04 00:31:55');
INSERT INTO `role_permission` VALUES ('398', '4', '52', '2026-08-04 00:31:55');
INSERT INTO `role_permission` VALUES ('399', '4', '41', '2026-08-04 00:31:55');
INSERT INTO `role_permission` VALUES ('404', '6', '11', '2026-08-04 02:03:13');
INSERT INTO `role_permission` VALUES ('406', '5', '78', '2026-08-05 12:02:21');
INSERT INTO `role_permission` VALUES ('407', '6', '78', '2026-08-05 12:02:21');
INSERT INTO `role_permission` VALUES ('409', '3', '81', '2026-08-05 12:17:33');
/*!40000 ALTER TABLE `role_permission` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `roles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `niveau_acces` int(11) NOT NULL DEFAULT 1 COMMENT '1=bas, 10=admin',
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `roles`
LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES ('1', 'Admin', 'Administrateur système, accès total à toutes les fonctionnalités', '10', '1', '2026-07-29 18:23:29', NULL);
INSERT INTO `roles` VALUES ('2', 'Directeur', 'Directeur ou Directrice de l\'école, supervision générale', '9', '1', '2026-07-29 18:23:29', NULL);
INSERT INTO `roles` VALUES ('3', 'Secrétaire', 'Secrétariat administratif, gestion des inscriptions et dossiers', '7', '1', '2026-07-29 18:23:29', NULL);
INSERT INTO `roles` VALUES ('4', 'Enseignant', 'Enseignant titulaire de classe, saisie des notes et présences', '5', '1', '2026-07-29 18:23:29', NULL);
INSERT INTO `roles` VALUES ('5', 'Comptable', 'Gestion financière, paiements et factures', '6', '1', '2026-07-29 18:23:29', NULL);
INSERT INTO `roles` VALUES ('6', 'Parent', 'Parent ou tuteur, consultation des résultats de ses enfants', '2', '1', '2026-07-29 18:23:29', NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `sanctions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sanctions`;
CREATE TABLE `sanctions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gravite` enum('légère','moyenne','grave') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'légère',
  `duree_max` int(11) DEFAULT NULL COMMENT 'en jours si applicable, NULL si sans durée',
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `libelle` (`libelle`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `sanctions`
LOCK TABLES `sanctions` WRITE;
/*!40000 ALTER TABLE `sanctions` DISABLE KEYS */;
INSERT INTO `sanctions` VALUES ('1', 'Avertissement oral', 'Réprimande verbale donnée par l\'enseignant ou le directeur', 'légère', NULL, '1', '2026-07-29 18:23:30', NULL);
INSERT INTO `sanctions` VALUES ('2', 'Avertissement écrit', 'Avertissement noté dans le carnet de correspondance et signé par les parents', 'légère', NULL, '1', '2026-07-29 18:23:30', NULL);
INSERT INTO `sanctions` VALUES ('3', 'Retenue simple', 'Heure de retenue après les cours pour travaux supplémentaires', 'moyenne', '1', '1', '2026-07-29 18:23:30', NULL);
INSERT INTO `sanctions` VALUES ('4', 'Retenue prolongée', 'Plusieurs heures de retenue réparties sur plusieurs jours', 'moyenne', '5', '1', '2026-07-29 18:23:30', NULL);
INSERT INTO `sanctions` VALUES ('5', 'Exclusion temporaire courte', 'Exclusion de l\'école pour une durée de 1 à 3 jours', 'grave', '3', '1', '2026-07-29 18:23:30', NULL);
INSERT INTO `sanctions` VALUES ('6', 'Exclusion temporaire longue', 'Exclusion de l\'école pour une durée de 4 à 15 jours', 'grave', '15', '1', '2026-07-29 18:23:30', NULL);
INSERT INTO `sanctions` VALUES ('7', 'Convocation des parents', 'Rencontre obligatoire avec les parents ou tuteurs', 'moyenne', NULL, '1', '2026-07-29 18:23:30', NULL);
INSERT INTO `sanctions` VALUES ('8', 'Conseil de discipline', 'Passage devant le conseil de discipline de l\'école', 'grave', NULL, '1', '2026-07-29 18:23:30', NULL);
INSERT INTO `sanctions` VALUES ('9', 'Renvoi définitif', 'Exclusion définitive et radiation de l\'école', 'grave', NULL, '1', '2026-07-29 18:23:30', NULL);
/*!40000 ALTER TABLE `sanctions` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `sauvegardes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sauvegardes`;
CREATE TABLE `sauvegardes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin_fichier` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taille_fichier` int(11) NOT NULL COMMENT 'en octets',
  `type_sauvegarde` enum('Manuelle','Automatique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Manuelle',
  `statut` enum('Succès','Échec','En cours') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Succès',
  `message_erreur` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tables_incluses` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'liste des tables sauvegardées',
  `nb_enregistrements` int(11) DEFAULT NULL COMMENT 'nombre total d''enregistrements sauvegardés',
  `duree_execution` decimal(5,2) DEFAULT NULL COMMENT 'en secondes',
  `cree_par` int(11) NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sauvegardes_utilisateur` (`cree_par`),
  CONSTRAINT `fk_sauvegardes_utilisateur` FOREIGN KEY (`cree_par`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `trajets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `trajets`;
CREATE TABLE `trajets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `point_depart` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `point_arrivee` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'généralement l''école',
  `distance_km` decimal(6,2) DEFAULT NULL,
  `heure_depart` time DEFAULT NULL,
  `heure_retour` time DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `trajets`
LOCK TABLES `trajets` WRITE;
/*!40000 ALTER TABLE `trajets` DISABLE KEYS */;
INSERT INTO `trajets` VALUES ('1', 'Trajet Nord', NULL, 'Quartier Nord - Station Total', 'École', NULL, '06:30:00', '16:00:00', '1', '2026-07-29 18:23:31', NULL);
INSERT INTO `trajets` VALUES ('2', 'Trajet Sud', NULL, 'Quartier Sud - Marché Central', 'École', NULL, '06:45:00', '16:00:00', '1', '2026-07-29 18:23:31', NULL);
INSERT INTO `trajets` VALUES ('3', 'Trajet Est', NULL, 'Quartier Est - Église', 'École', NULL, '07:00:00', '16:00:00', '1', '2026-07-29 18:23:31', NULL);
/*!40000 ALTER TABLE `trajets` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `transport`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `transport`;
CREATE TABLE `transport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `trajet_id` int(11) NOT NULL,
  `vehicule_id` int(11) NOT NULL,
  `annee_scolaire_id` int(11) NOT NULL,
  `date_inscription` date NOT NULL DEFAULT curdate(),
  `point_montee` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'arrêt où l''élève monte',
  `point_descente` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'École',
  `ordre_montee` int(11) DEFAULT NULL COMMENT 'ordre de ramassage sur le trajet',
  `tarif_mensuel` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tarif_trimestriel` decimal(8,2) NOT NULL DEFAULT 0.00,
  `date_debut_service` date NOT NULL,
  `date_fin_service` date DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_transport_eleve_annee` (`eleve_id`,`annee_scolaire_id`),
  KEY `fk_transport_trajet` (`trajet_id`),
  KEY `fk_transport_vehicule` (`vehicule_id`),
  KEY `fk_transport_annee` (`annee_scolaire_id`),
  CONSTRAINT `fk_transport_annee` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transport_eleve` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transport_trajet` FOREIGN KEY (`trajet_id`) REFERENCES `trajets` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transport_vehicule` FOREIGN KEY (`vehicule_id`) REFERENCES `vehicules` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table : `trimestres`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `trimestres`;
CREATE TABLE `trimestres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `annee_scolaire_id` int(11) NOT NULL,
  `numero` tinyint(4) NOT NULL COMMENT '1, 2 ou 3',
  `libelle` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: 1er Trimestre',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trimestre_annee` (`annee_scolaire_id`,`numero`),
  CONSTRAINT `fk_trimestres_annee` FOREIGN KEY (`annee_scolaire_id`) REFERENCES `annees_scolaires` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `trimestres`
LOCK TABLES `trimestres` WRITE;
/*!40000 ALTER TABLE `trimestres` DISABLE KEYS */;
INSERT INTO `trimestres` VALUES ('13', '5', '1', '1er Trimestre', '2025-09-01', '2025-12-15', '1', '2026-08-01 02:12:29', '2026-08-05 12:54:48');
INSERT INTO `trimestres` VALUES ('14', '5', '2', '2ème Trimestre', '2026-01-05', '2026-03-30', '0', '2026-08-01 02:12:29', NULL);
INSERT INTO `trimestres` VALUES ('15', '5', '3', '3ème Trimestre', '2026-04-06', '2026-07-05', '0', '2026-08-01 02:12:29', NULL);
/*!40000 ALTER TABLE `trimestres` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `utilisateurs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `derniere_connexion` datetime DEFAULT NULL,
  `derniere_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tentatives_echecs` tinyint(4) NOT NULL DEFAULT 0,
  `bloque_jusqua` datetime DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `idx_google_id` (`google_id`),
  KEY `fk_utilisateurs_role` (`role_id`),
  CONSTRAINT `fk_utilisateurs_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `utilisateurs`
LOCK TABLES `utilisateurs` WRITE;
/*!40000 ALTER TABLE `utilisateurs` DISABLE KEYS */;
INSERT INTO `utilisateurs` VALUES ('1', 'Admin', 'Système', 'admin@ecolelareference.edu', NULL, '+243 000 000 001', 'admin', '$2y$12$8gF6hG5k9mN2pR4sT7vXwOyJzQ3bL5nM8kP1rS6tU9vW0xYzA4bC', '1', NULL, '1', NULL, NULL, '5', '2026-07-30 10:36:20', NULL, NULL, '2026-07-29 18:23:30', '2026-07-30 10:21:20');
INSERT INTO `utilisateurs` VALUES ('2', 'LUKUNKU', 'Jean-Pierre', 'direction@ecolelareference.edu', NULL, '+243 000 000 002', 'directeur', '$2y$12$SbkUrnXr9Qnrgqu0Dx68S.iAAc0SsFN8g0a/aYpOyb3.hXPo9q9c.', '2', NULL, '1', '2026-08-05 12:14:01', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-29 18:23:30', '2026-08-05 12:14:01');
INSERT INTO `utilisateurs` VALUES ('3', 'MUTOMBO', 'Marie-Claire', 'secretariat@ecolelareference.edu', NULL, '+243 000 000 003', 'secretaire', '$2y$12$JOpOToj1i9WbIpBNBHyjauJVmtNPSlIAAU6uUKoqBysfP4BqpMs0.', '3', NULL, '1', '2026-08-05 12:15:30', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-29 18:23:30', '2026-08-05 12:15:30');
INSERT INTO `utilisateurs` VALUES ('4', 'KALONDA', 'David', 'comptabilite@ecolelareference.edu', NULL, '+243 000 000 004', 'comptable', '$2y$12$ONtEMWTOlmDCqoK7g3Ax1Oyss/d8.TVecg3OjSFL2EUuynyKi5mnG', '5', NULL, '1', '2026-08-05 11:54:16', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-29 18:23:30', '2026-08-05 11:54:16');
INSERT INTO `utilisateurs` VALUES ('5', 'Baraka', 'Ntwali', 'barakantwal06@gmail.com', NULL, '098765432', 'nbaraka', '$2y$12$xOJjJWbrAKqMTYWy8Wn3/O2lQhugF/M723j9XGt0n2byNYkXOsTyi', '4', NULL, '1', '2026-08-05 11:59:24', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-29 20:55:02', '2026-08-05 11:59:24');
INSERT INTO `utilisateurs` VALUES ('6', 'Felix', 'Baraka', 'barakantwal006@gmail.com', NULL, NULL, 'bfelix', '$2y$12$wqPfBx1rCyj5o2tDaPnvBuMRP1OKVnVYLeNEyqY9QuXWrI/iGp.CW', '4', NULL, '1', '2026-08-05 11:58:48', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-29 21:47:52', '2026-08-05 11:58:48');
INSERT INTO `utilisateurs` VALUES ('7', 'KALENGA', 'Marie', 'marie.kalenga@ecole.edu', NULL, '+243812345601', 'enseignant1', '$2y$12$5lon8I/6Kw.mVZTHy9G8JeghetpK5locBh2bkkdWROvn27lVLGCjm', '4', NULL, '1', '2026-08-05 12:03:20', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-29 22:43:47', '2026-08-05 12:03:20');
INSERT INTO `utilisateurs` VALUES ('8', 'MUTOMBO', 'Pierre', 'pierre.mutombo@ecole.edu', NULL, '+243812345602', 'enseignant2', '$2y$12$8HUQrn7Hlvtju3xbsKoiY.t5Ln1YpiGfGWJNKL4d8z0KcOV0f.Iq2', '4', NULL, '1', '2026-07-30 11:16:41', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-29 22:43:47', '2026-07-30 11:16:41');
INSERT INTO `utilisateurs` VALUES ('9', 'NGALULA', 'Jeanne', 'jeanne.ngalula@ecole.edu', NULL, '+243812345603', 'enseignant3', '$2y$12$LJ3m4ys3Gql.ZHKrOgj5yeHCFqFhqFhqFhqFhqFhqFhqFhqFhqFhq', '4', NULL, '1', NULL, NULL, '0', NULL, NULL, NULL, '2026-07-29 22:43:47', NULL);
INSERT INTO `utilisateurs` VALUES ('10', 'ILUNGA', 'David', 'david.ilunga@ecole.edu', NULL, '+243812345604', 'enseignant4', '$2y$12$LJ3m4ys3Gql.ZHKrOgj5yeHCFqFhqFhqFhqFhqFhqFhqFhqFhqFhq', '4', NULL, '1', NULL, NULL, '0', NULL, NULL, NULL, '2026-07-29 22:43:47', NULL);
INSERT INTO `utilisateurs` VALUES ('11', 'KASONGO', 'Aimée', 'aimee.kasongo@ecole.edu', NULL, '+243812345605', 'enseignant5', '$2y$12$LJ3m4ys3Gql.ZHKrOgj5yeHCFqFhqFhqFhqFhqFhqFhqFhqFhqFhq', '4', NULL, '1', NULL, NULL, '0', NULL, NULL, NULL, '2026-07-29 22:43:47', NULL);
INSERT INTO `utilisateurs` VALUES ('12', 'TSHIBAMBA', 'Joseph', 'joseph.tshibamba@ecole.edu', NULL, '+243812345606', 'enseignant6', '$2y$12$LJ3m4ys3Gql.ZHKrOgj5yeHCFqFhqFhqFhqFhqFhqFhqFhqFhqFhq', '4', NULL, '1', NULL, NULL, '1', NULL, NULL, NULL, '2026-07-29 22:43:47', '2026-07-30 11:15:45');
INSERT INTO `utilisateurs` VALUES ('13', 'Parent', 'Felix', 'barakantwal0006@gmail.com', NULL, '0987665555', 'fparent', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', '2026-08-01 17:32:11', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-29 23:07:25', '2026-08-04 02:08:25');
INSERT INTO `utilisateurs` VALUES ('14', 'Test', 'Admin', 'test@gmail.com', NULL, '+243000000999', 'test', '$2y$12$zg4T63s3UGx88ijcyLbKY.OAXfvfk5fGfctPMD2wTLLOSkfgRP1De', '1', NULL, '1', '2026-08-05 13:05:16', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-30 00:11:28', '2026-08-05 13:05:16');
INSERT INTO `utilisateurs` VALUES ('16', 'KABONGO', 'Martin', 'martin.kabongo@email.com', NULL, '+243820000003', 'parent1', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', '2026-07-30 11:22:49', '127.0.0.1', '1', NULL, NULL, NULL, '2026-07-30 11:19:57', '2026-08-05 12:02:49');
INSERT INTO `utilisateurs` VALUES ('17', 'KALONDA', 'David', 'david.kalonda@ecole.edu', NULL, '+243820000999', 'comptable1', '$2y$12$6Srjj0PZuP7.mFgJvwIorebpM2j1QADjAkJPgLpmEITsI978kGJ7S', '5', NULL, '1', '2026-08-05 12:03:55', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-30 11:28:00', '2026-08-05 12:03:55');
INSERT INTO `utilisateurs` VALUES ('18', 'MUTOMBO', 'Marie-Claire', 'marie-claire.mutombo@ecole.edu', NULL, '+243820000888', 'secretaire1', '$2y$12$YmoxwU1dPegLcwydhYXeVOowkL.biGOPlpCDbrlwub3EJEkjgmMUi', '3', NULL, '1', '2026-07-30 11:39:11', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-30 11:37:27', '2026-07-30 11:39:11');
INSERT INTO `utilisateurs` VALUES ('19', 'Bonane', 'Rutanga', 'barakantwal00006@gmail.com', NULL, '0987456766', 'bonane', '$2y$12$Iftuk5Shpv6N2X5TCMAQ4OJhYQywAsTon/QxJJAKPJh1DU8sq0wqK', '4', NULL, '1', '2026-07-31 01:45:17', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-30 12:04:23', '2026-07-31 01:45:17');
INSERT INTO `utilisateurs` VALUES ('20', 'Tufurahi', 'Amijay', 'barakantwal000006@gmail.com', NULL, '0987654333', 'tufu', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', '2026-08-03 23:22:28', '127.0.0.1', '1', NULL, NULL, NULL, '2026-07-30 15:52:59', '2026-08-05 11:59:09');
INSERT INTO `utilisateurs` VALUES ('21', 'Baraka', 'Baba', 'baba@gmail.com', NULL, NULL, 'parent.baba.baraka', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', '2026-07-30 22:14:35', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-30 21:11:12', '2026-08-04 02:08:25');
INSERT INTO `utilisateurs` VALUES ('22', 'Josias', 'Kub', 'josias@gmail.com', NULL, NULL, 'parent.kub.josias', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', '2026-08-01 17:46:56', '127.0.0.1', '0', NULL, NULL, NULL, '2026-07-30 22:00:10', '2026-08-04 02:08:25');
INSERT INTO `utilisateurs` VALUES ('23', 'Bienve', 'Charles', 'bienve@gmail.com', NULL, NULL, 'ens.charles.bienve', '$2y$12$mgR1iH2MFeLYx8WVAgmPKOOuye/MWqPw.WdUkIkM6GEoh13ifC65u', '4', NULL, '1', NULL, NULL, '2', NULL, NULL, NULL, '2026-07-30 22:40:01', '2026-08-01 17:46:36');
INSERT INTO `utilisateurs` VALUES ('24', 'LUKUSA', 'Jean', 'parent.lukusa@email.com', NULL, NULL, 'parent.lukusa', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', '2026-08-04 01:53:12', '127.0.0.1', '0', NULL, NULL, NULL, '2026-08-02 11:39:52', '2026-08-04 02:08:25');
INSERT INTO `utilisateurs` VALUES ('25', 'KABONGO', 'Martin', 'parent.kabongo@email.com', NULL, NULL, 'parent.kabongo', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', NULL, NULL, '1', NULL, NULL, NULL, '2026-08-02 11:39:52', '2026-08-04 02:08:25');
INSERT INTO `utilisateurs` VALUES ('26', 'MULONDA', 'Pierre', 'parent.mulonda@email.com', NULL, NULL, 'parent.mulonda', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', NULL, NULL, '0', NULL, NULL, NULL, '2026-08-02 11:39:52', '2026-08-04 02:08:25');
INSERT INTO `utilisateurs` VALUES ('27', 'TSHIBOLA', 'André', 'parent.tshibola@email.com', NULL, NULL, 'parent.tshibola', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', NULL, NULL, '0', NULL, NULL, NULL, '2026-08-02 11:39:52', '2026-08-04 02:08:25');
INSERT INTO `utilisateurs` VALUES ('28', 'KANKU', 'Jacques', 'parent.kanku@email.com', NULL, NULL, 'parent.kanku', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', NULL, '1', NULL, NULL, '0', NULL, NULL, NULL, '2026-08-02 11:39:52', '2026-08-04 02:08:25');
INSERT INTO `utilisateurs` VALUES ('29', 'LUKUSA', 'Marc', 'marc.lukusa@gmail.com', NULL, '+243810000002', 'marc.lukusa@gmail.com', '$2y$10$//vzuaF4Nokm1w5oPJqgf.5IWSYKzi3Qbbq2usHy8RRMbd0QfHOb2', '6', NULL, '1', NULL, NULL, '5', '2026-08-04 02:23:15', NULL, NULL, '2026-08-02 13:40:25', '2026-08-04 02:11:42');
INSERT INTO `utilisateurs` VALUES ('30', 'KABONGO', 'Marie', 'marie.kabongo@gmail.com', NULL, '+243810000004', 'marie.kabongo@gmail.com', '$2y$12$zq7K3QJQFGUjxemzNXq8R.kapKhF.KEccnPh8tuB4xGxW0umB4PJu', '6', NULL, '1', '2026-08-05 12:04:11', '127.0.0.1', '0', NULL, NULL, NULL, '2026-08-02 13:40:25', '2026-08-05 12:04:11');
INSERT INTO `utilisateurs` VALUES ('31', 'MULONDA', 'Jean', 'jean.mulonda@gmail.com', NULL, '+243810000006', 'jean.mulonda@gmail.com', '$2y$12$x3rXHKNWHSmdUExB.2ksDu4JJLPY07ORGku/4HGM7Qhjo6dFH6bLy', '6', NULL, '1', '2026-08-04 02:13:33', '127.0.0.1', '0', NULL, NULL, NULL, '2026-08-02 13:40:25', '2026-08-04 02:13:33');
INSERT INTO `utilisateurs` VALUES ('32', 'TSHIBOLA', 'Sarah', 'sarah.tshibola@gmail.com', NULL, '+243810000008', 'sarah.tshibola@gmail.com', '$2y$10$//vzuaF4Nokm1w5oPJqgf.5IWSYKzi3Qbbq2usHy8RRMbd0QfHOb2', '6', NULL, '1', NULL, NULL, '0', NULL, NULL, NULL, '2026-08-02 13:40:25', '2026-08-04 02:11:42');
INSERT INTO `utilisateurs` VALUES ('33', 'KANKU', 'Paul', 'paul.kanku@gmail.com', NULL, '+243810000010', 'paul.kanku@gmail.com', '$2y$10$//vzuaF4Nokm1w5oPJqgf.5IWSYKzi3Qbbq2usHy8RRMbd0QfHOb2', '6', NULL, '1', NULL, NULL, '1', NULL, NULL, NULL, '2026-08-02 13:40:25', '2026-08-04 02:11:42');
INSERT INTO `utilisateurs` VALUES ('36', 'Ntwali', 'Baraka', 'barakantwali06@gmail.com', '112075492614177848738', NULL, 'bntwali', '$2y$12$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOP', '6', 'https://lh3.googleusercontent.com/a/ACg8ocIi50Y90va9WiQ7l7cafJA-uVfkMgKnkcFm8hpANw9P-k-3gNs=s96-c', '1', '2026-08-02 19:04:08', '127.0.0.1', '0', NULL, NULL, NULL, '2026-08-02 19:04:08', '2026-08-04 02:08:25');
/*!40000 ALTER TABLE `utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;

-- --------------------------------------------------------
-- Table : `vehicules`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `vehicules`;
CREATE TABLE `vehicules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `immatriculation` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marque` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modele` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacite` int(11) NOT NULL,
  `couleur` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_mise_service` date DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `immatriculation` (`immatriculation`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de `vehicules`
LOCK TABLES `vehicules` WRITE;
/*!40000 ALTER TABLE `vehicules` DISABLE KEYS */;
INSERT INTO `vehicules` VALUES ('1', 'BUS-001', 'Toyota', 'Coaster', '30', 'Blanc', NULL, '1', '2026-07-29 18:23:32', NULL);
INSERT INTO `vehicules` VALUES ('2', 'BUS-002', 'Mitsubishi', 'Rosa', '26', 'Blanc', NULL, '1', '2026-07-29 18:23:32', NULL);
INSERT INTO `vehicules` VALUES ('3', 'BUS-003', 'Hino', 'Liesse', '30', 'Blanc', NULL, '1', '2026-07-29 18:23:32', NULL);
/*!40000 ALTER TABLE `vehicules` ENABLE KEYS */;
UNLOCK TABLES;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
