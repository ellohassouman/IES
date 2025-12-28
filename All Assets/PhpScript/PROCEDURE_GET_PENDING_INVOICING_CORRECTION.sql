/**
 * ════════════════════════════════════════════════════════════════════════════
 * PROCÉDURE: GetPendingInvoicingItemsPerBLNumber (CORRIGÉE)
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * CORRECTION:
 *   Dans le NOT EXISTS pour les invoiceitems, ajouter une jointure avec invoice
 *   et vérifier que invoice.deleted = 0 pour ne compter que les factures actives
 */

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
    LEFT JOIN `event` evt ON jf.`Id` = evt.`JobFileId`
    LEFT JOIN `eventtype` et ON evt.`EventTypeId` = et.`Id`
    LEFT JOIN `contract_eventtype` cet ON et.`Id` = cet.`EventType_Id`
    WHERE bl.`BlNumber` = p_BlNumber
    -- L'événement doit avoir un type facturable (lié à un contrat)
    AND cet.`Contract_Id` IS NOT NULL
    -- L'événement ne doit pas encore avoir été facturé (invoices non supprimées)
    AND NOT EXISTS (
        SELECT 1 
        FROM `invoiceitem` ii
        INNER JOIN `invoice` inv ON ii.`InvoiceId` = inv.`Id`
        WHERE ii.`EventId` = evt.`Id`
        AND inv.`Deleted` = 0
    )
    ORDER BY bli.`Number`;
END;
