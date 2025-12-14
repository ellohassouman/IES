-- ============================================================================
-- TOUTES LES TABLES DU PROJET IES
-- ============================================================================
-- Ce fichier contient TOUTES les tables créées/modifiées pour le projet
-- Exécuter ce fichier pour créer la structure complète de la base de données

-- ============================================================================
-- 1. TABLE: Cart - Panier utilisateur
-- ============================================================================
CREATE TABLE IF NOT EXISTS `Cart` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `CustomerUserId` INT NOT NULL,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `Deleted` TINYINT DEFAULT 0,
    INDEX `idx_CustomerUserId` (`CustomerUserId`),
    INDEX `idx_Deleted` (`Deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. TABLE: CartItem - Articles du panier
-- ============================================================================
CREATE TABLE IF NOT EXISTS `CartItem` (
    `Id` INT AUTO_INCREMENT PRIMARY KEY,
    `CartId` INT NOT NULL,
    `InvoiceId` INT NOT NULL,
    `InvoicePaidAmount` DECIMAL(18, 2) DEFAULT 0.00,
    `InvoiceNumber` VARCHAR(100),
    `BillingDate` DATETIME,
    FOREIGN KEY (`CartId`) REFERENCES `Cart` (`Id`) ON DELETE CASCADE,
    INDEX `idx_CartId` (`CartId`),
    INDEX `idx_InvoiceId` (`InvoiceId`),
    UNIQUE KEY `uk_CartId_InvoiceId` (`CartId`, `InvoiceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- FIN DES TABLES
-- ============================================================================
