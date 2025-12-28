-- ============================================================================
-- CORRECTION: GetPendingInvoicingItemsPerBLNumber - Afficher TOUS les items
-- ============================================================================
-- Changement: Retirer les conditions de facturation, afficher tous les items
-- Raison: Les conditions de facturation seront vérifiées dans GenerateProforma

DROP PROCEDURE IF EXISTS `GetPendingInvoicingItemsPerBLNumber`;

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetPendingInvoicingItemsPerBLNumber` (IN `p_BlNumber` VARCHAR(100))
BEGIN
    SELECT DISTINCT
        CAST(bli.`Id` AS CHAR) AS `id`,
        COALESCE(bli.`Number`, '') AS `number`,
        CONCAT('[', COALESCE(bit.`Label`, ''), ']') AS `type`,
        COALESCE(ci.`NumberOfPackages`, '') AS `description`,
        COALESCE(jf.`Id`, 0) AS `jobFileId`,
        FALSE AS `isDraft`,
        FALSE AS `dnPrintable`
    FROM `blitem` bli
    LEFT JOIN `yarditemtype` bit ON bli.`ItemTypeId` = bit.`Id`
    LEFT JOIN `commodityitem` ci ON bli.`Id` = ci.`BlItemId`
    LEFT JOIN `bl` bl ON bli.`BlId` = bl.`Id`
    LEFT JOIN `blitem_jobfile` bij ON bli.`Id` = bij.`BLItem_Id`
    LEFT JOIN `jobfile` jf ON bij.`JobFile_Id` = jf.`Id`
    WHERE bl.`BlNumber` = p_BlNumber
    ORDER BY bli.`Number`;
END;
