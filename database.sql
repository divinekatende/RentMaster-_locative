CREATE DATABASE IF NOT EXISTS `rentmaster` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rentmaster`;

-- Table des administrateurs
CREATE TABLE IF NOT EXISTS `administrateurs` (
  `id_admin` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `email` VARCHAR(200) NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `uniq_administrateurs_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des bailleurs
CREATE TABLE IF NOT EXISTS `bailleurs` (
  `id_bailleur` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `prenom` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(200) NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `telephone` VARCHAR(50) DEFAULT NULL,
  `statut` VARCHAR(50) NOT NULL DEFAULT 'actif',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bailleur`),
  UNIQUE KEY `uniq_bailleurs_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des locataires
CREATE TABLE IF NOT EXISTS `locataires` (
  `id_locataire` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bailleur` INT UNSIGNED DEFAULT NULL,
  `matricule` VARCHAR(100) DEFAULT NULL,
  `nom` VARCHAR(150) NOT NULL,
  `prenom` VARCHAR(150) DEFAULT NULL,
  `telephone` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(200) DEFAULT NULL,
  `adresse` TEXT DEFAULT NULL,
  `mot_de_passe` VARCHAR(255) DEFAULT NULL,
  `first_login` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_naissance` DATE DEFAULT NULL,
  `sexe` VARCHAR(50) DEFAULT NULL,
  `etat_civil` VARCHAR(100) DEFAULT NULL,
  `nationalite` VARCHAR(100) DEFAULT NULL,
  `profession` VARCHAR(150) DEFAULT NULL,
  `statut` VARCHAR(50) NOT NULL DEFAULT 'actif',
  PRIMARY KEY (`id_locataire`),
  UNIQUE KEY `uniq_locataires_matricule` (`matricule`),
  KEY `idx_locataires_id_bailleur` (`id_bailleur`),
  CONSTRAINT `fk_locataires_bailleurs` FOREIGN KEY (`id_bailleur`) REFERENCES `bailleurs` (`id_bailleur`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des biens
CREATE TABLE IF NOT EXISTS `biens` (
  `id_bien` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bailleur` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `adresse` TEXT NOT NULL,
  `type_bien` VARCHAR(100) DEFAULT NULL,
  `surface` DECIMAL(10,2) DEFAULT NULL,
  `nombre_pieces` INT DEFAULT NULL,
  `prix` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `loyer` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `statut` VARCHAR(50) NOT NULL DEFAULT 'disponible',
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bien`),
  KEY `idx_biens_id_bailleur` (`id_bailleur`),
  CONSTRAINT `fk_biens_bailleurs` FOREIGN KEY (`id_bailleur`) REFERENCES `bailleurs` (`id_bailleur`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des contrats
CREATE TABLE IF NOT EXISTS `contrats` (
  `id_contrat` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bailleur` INT UNSIGNED NOT NULL,
  `id_locataire` INT UNSIGNED NOT NULL,
  `id_bien` INT UNSIGNED NOT NULL,
  `date_debut` DATE NOT NULL,
  `date_fin` DATE NOT NULL,
  `montant` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `statut` VARCHAR(50) NOT NULL DEFAULT 'Actif',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_contrat`),
  KEY `idx_contrats_id_bailleur` (`id_bailleur`),
  KEY `idx_contrats_id_locataire` (`id_locataire`),
  KEY `idx_contrats_id_bien` (`id_bien`),
  CONSTRAINT `fk_contrats_bailleurs` FOREIGN KEY (`id_bailleur`) REFERENCES `bailleurs` (`id_bailleur`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_contrats_locataires` FOREIGN KEY (`id_locataire`) REFERENCES `locataires` (`id_locataire`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_contrats_biens` FOREIGN KEY (`id_bien`) REFERENCES `biens` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paiements
CREATE TABLE IF NOT EXISTS `paiements` (
  `id_paiement` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_contrat` INT UNSIGNED NOT NULL,
  `mois_annee` VARCHAR(7) NOT NULL,
  `montant_verse` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `date_paiement` DATE NOT NULL,
  `statut` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_paiement`),
  KEY `idx_paiements_id_contrat` (`id_contrat`),
  CONSTRAINT `fk_paiements_contrats` FOREIGN KEY (`id_contrat`) REFERENCES `contrats` (`id_contrat`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des messages
CREATE TABLE IF NOT EXISTS `messages` (
  `id_message` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `expediteur_id` INT UNSIGNED NOT NULL,
  `expediteur_type` VARCHAR(50) NOT NULL,
  `destinataire_id` INT UNSIGNED NOT NULL,
  `destinataire_type` VARCHAR(50) NOT NULL,
  `contenu` TEXT NOT NULL,
  `date_message` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id_notification` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `type_utilisateur` VARCHAR(50) NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `contenu` TEXT NOT NULL,
  `lu` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table historique
CREATE TABLE IF NOT EXISTS `historique` (
  `id_historique` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bailleur` INT UNSIGNED NOT NULL,
  `action` TEXT NOT NULL,
  `type_action` VARCHAR(50) NOT NULL DEFAULT 'info',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historique`),
  KEY `idx_historique_id_bailleur` (`id_bailleur`),
  CONSTRAINT `fk_historique_bailleurs` FOREIGN KEY (`id_bailleur`) REFERENCES `bailleurs` (`id_bailleur`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des événements
CREATE TABLE IF NOT EXISTS `evenements` (
  `id_evenement` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bailleur` INT UNSIGNED NOT NULL,
  `id_locataire` INT UNSIGNED DEFAULT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `date_evenement` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evenement`),
  KEY `idx_evenements_id_bailleur` (`id_bailleur`),
  KEY `idx_evenements_id_locataire` (`id_locataire`),
  CONSTRAINT `fk_evenements_bailleurs` FOREIGN KEY (`id_bailleur`) REFERENCES `bailleurs` (`id_bailleur`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evenements_locataires` FOREIGN KEY (`id_locataire`) REFERENCES `locataires` (`id_locataire`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
