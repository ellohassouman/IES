-- ============================================================================
-- SCRIPTS DE MAINTENANCE ET CORRECTIFS - BASE DE DONNÉES IES
-- ============================================================================
-- Ces scripts corrigent et optimisent la base de données IES
-- À exécuter en cas de besoin uniquement
-- ============================================================================

-- ============================================================================
-- 1. CORRECTION DE L'AUTO-INCRÉMENTATION
-- ============================================================================
-- Corrige les sauts d'ID dans les différentes tables
-- Status: A utiliser après suppression massive de données

SET FOREIGN_KEY_CHECKS=0;

-- Tables avec sauts d'ID détectés et corrigés
ALTER TABLE `cart` AUTO_INCREMENT=3;
ALTER TABLE `cartitem` AUTO_INCREMENT=5;
ALTER TABLE `commodity` AUTO_INCREMENT=3313;
ALTER TABLE `contract` AUTO_INCREMENT=696;
ALTER TABLE `customeruserblsearchhistory` AUTO_INCREMENT=17;
ALTER TABLE `eventtype` AUTO_INCREMENT=70;
ALTER TABLE `raterangeperiod` AUTO_INCREMENT=117;
ALTER TABLE `row` AUTO_INCREMENT=51;
ALTER TABLE `thirdparty` AUTO_INCREMENT=42;
ALTER TABLE `yarditemcode` AUTO_INCREMENT=604;

SET FOREIGN_KEY_CHECKS=1;

-- Statistiques:
-- 10 tables corrigées
-- cart: 1 rows, MAX(Id)=2 → AUTO_INCREMENT=3
-- cartitem: 1 rows, MAX(Id)=4 → AUTO_INCREMENT=5
-- commodity: 1118 rows, MAX(Id)=3312 → AUTO_INCREMENT=3313
-- contract: 10 rows, MAX(Id)=695 → AUTO_INCREMENT=696
-- customeruserblsearchhistory: 13 rows, MAX(Id)=16 → AUTO_INCREMENT=17
-- eventtype: 68 rows, MAX(Id)=69 → AUTO_INCREMENT=70
-- raterangeperiod: 4 rows, MAX(Id)=116 → AUTO_INCREMENT=117
-- row: 4 rows, MAX(Id)=50 → AUTO_INCREMENT=51
-- thirdparty: 15 rows, MAX(Id)=41 → AUTO_INCREMENT=42
-- yarditemcode: 341 rows, MAX(Id)=603 → AUTO_INCREMENT=604


-- ============================================================================
-- 2. RENUMÉRISATION DES IDs - CORRECTION DES SAUTS
-- ============================================================================
-- Corrige les IDs non-séquentiels pour éviter les sauts
-- Status: Optionnel (optimisation uniquement)

-- Note: Exécuter uniquement si renumérisation complète nécessaire
-- Voir script PHP pour la logique complète

-- AUTO_INCREMENT après renumérisation idéale:
-- ALTER TABLE `yarditemcode` AUTO_INCREMENT=342;
-- ALTER TABLE `commodity` AUTO_INCREMENT=1119;
-- ALTER TABLE `contract` AUTO_INCREMENT=11;
-- ALTER TABLE `thirdparty` AUTO_INCREMENT=16;
-- ALTER TABLE `raterangeperiod` AUTO_INCREMENT=5;


-- ============================================================================
-- 3. CRÉATION DES PROCÉDURES MANQUANTES
-- ============================================================================
-- Crée les procédures stockées essentielles si elles manquent
-- Status: A exécuter une seule fois

-- ============================================================================
-- PROCÉDURE: GetUserBLPerNumber
-- Retourne les informations complètes d'une BL
-- ============================================================================
DROP PROCEDURE IF EXISTS `GetUserBLPerNumber`$$

DELIMITER //

CREATE PROCEDURE `GetUserBLPerNumber`(
    IN p_BlNumber VARCHAR(100),
    IN p_UserId INT
)
BEGIN
    SELECT 
        bl.`Id`,
        bl.`BlNumber`,
        COALESCE(tp_consignee.`Label`, '') AS `ShipName`,
        c.`VesselArrivalDate` AS `ArrivalDate`,
        COUNT(bli.`Id`) AS `ItemCount`
    FROM `BL` bl
    LEFT JOIN `Call` c ON bl.`CallId` = c.`Id`
    LEFT JOIN `ThirdParty` tp_consignee ON bl.`ConsigneeId` = tp_consignee.`Id`
    LEFT JOIN `BLItem` bli ON bl.`Id` = bli.`BlId`
    WHERE bl.`BlNumber` = p_BlNumber
    GROUP BY bl.`Id`, bl.`BlNumber`, tp_consignee.`Label`, c.`VesselArrivalDate`;
END //

DELIMITER ;

-- ============================================================================
-- VÉRIFICATION DES PROCÉDURES
-- ============================================================================
-- Vérifie que toutes les procédures essentielles existent
SELECT 
    ROUTINE_NAME,
    ROUTINE_TYPE
FROM INFORMATION_SCHEMA.ROUTINES 
WHERE ROUTINE_SCHEMA = 'ies'
AND ROUTINE_NAME IN (
    'GetUserBLPerNumber', 
    'GetDetailsPerBLNumber', 
    'GetInvoicesPerBLNumber', 
    'GetPendingInvoicingItemsPerBLNumber', 
    'GetYardItemsPerBLNumber',
    'GetUserBLHistory'
)
ORDER BY ROUTINE_NAME;

-- ============================================================================
-- FIN DES CORRECTIFS
-- ============================================================================
