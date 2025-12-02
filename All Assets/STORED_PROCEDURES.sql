-- ============================================================================
-- PROCÉDURES STOCKÉES POUR LA BASE DE DONNÉES IES
-- ============================================================================

-- ============================================================================
-- 1. GetUserBLHistory - Récupère l'historique des BL recherchés par un utilisateur
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `GetUserBLHistory`(
    IN p_UserId INT
)
BEGIN
    SELECT 
        `BlNumber` AS `blNumber`,
        `ShipName` AS `shipName`,
        `ArrivalDate` AS `arrivalDate`,
        `ItemCount` AS `itemCount`
    FROM `CustomerUserBLSearchHistory`
    WHERE `UserId` = p_UserId
    ORDER BY `SearchDate` DESC;
END //

DELIMITER ;

-- ============================================================================
-- 2. GetUserBLPerNumber - Récupère les détails d'un BL par numéro et userId
-- ============================================================================
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
-- 3. GetDetailsPerBLNumber - Récupère les détails complets d'un BL
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `GetDetailsPerBLNumber`(
    IN p_BlNumber VARCHAR(100)
)
BEGIN
    SELECT 
        bl.`BlNumber` AS `blNumber`,
        '' AS `manifest`,
        'Export' AS `impExp`,
        'FCL' AS `transportMode`,
        '' AS `masterBL`,
        '' AS `incoterm`,
        'Closed' AS `blStatus`,
        COALESCE(tp_consignee.`Label`, '') AS `receiver`,
        '' AS `shipper`,
        COALESCE(tp_customer.`Label`, '') AS `secondaryClient`,
        '' AS `transportAgent`,
        '' AS `forwarder`,
        '' AS `pickupOrder`,
        '' AS `pickupDate`,
        '' AS `deliveryOrder`,
        '' AS `deliveryDate`,
        '' AS `agencyName`,
        '' AS `shipperName`,
        '' AS `loadingPort`,
        '' AS `sense`,
        '' AS `receptionPlace`,
        '' AS `finalDestination`,
        '' AS `transshipmentPort`,
        '' AS `dischargePort`,
        COALESCE(tp_shipper.`Label`, '') AS `shipName`,
        c.`CallNumber` AS `voyageNumber`,
        DATE_FORMAT(c.`VesselArrivalDate`, '%Y-%m-%d') AS `blDate`
    FROM `BL` bl
    LEFT JOIN `Call` c ON bl.`CallId` = c.`Id`
    LEFT JOIN `ThirdParty` tp_consignee ON bl.`ConsigneeId` = tp_consignee.`Id`
    LEFT JOIN `ThirdParty` tp_customer ON bl.`RelatedCustomerId` = tp_customer.`Id`
    LEFT JOIN `ThirdParty` tp_shipper ON c.`ThirdPartyId` = tp_shipper.`Id`
    WHERE bl.`BlNumber` = p_BlNumber
    LIMIT 1;
END //

DELIMITER ;

DELIMITER //

CREATE PROCEDURE `GetUserBLPerCriteria`(
    IN p_BlNumber VARCHAR(100),
    IN p_UserId INT
)
BEGIN
    IF p_BlNumber IS NOT NULL AND p_BlNumber != '' THEN
        -- Recherche par numéro de BL
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
        WHERE bl.`BlNumber` LIKE CONCAT('%', p_BlNumber, '%')
        GROUP BY bl.`Id`, bl.`BlNumber`, tp_consignee.`Label`, c.`VesselArrivalDate`
        ORDER BY bl.`BlNumber`;
    ELSE
        -- Si aucun critère, retourner un résultat vide
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
        WHERE 1=0
        GROUP BY bl.`Id`, bl.`BlNumber`, tp_consignee.`Label`, c.`VesselArrivalDate`;
    END IF;
END //

DELIMITER ;

-- ============================================================================
-- 4. GetInvoicesPerBLNumber - Récupère les factures associées à un BL
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `GetInvoicesPerBLNumber`(
    IN p_BlNumber VARCHAR(100)
)
BEGIN
    SELECT 
        inv.`Id` AS `id`,
        inv.`InvoiceNumber` AS `invoiceNumber`,
        'Invoice' AS `invoiceType`,
        COALESCE(tp.`Label`, '') AS `client`,
        DATE_FORMAT(inv.`ValIdationDate`, '%d/%m/%Y') AS `billingDate`,
        DATE_FORMAT(inv.`ValIdationDate`, '%d/%m/%Y') AS `withdrawalDate`,
        CONCAT(FORMAT(inv.`TotalAmount`, 2), ' CFA') AS `total`,
        'CFA' AS `currencyCode`,
        inv.`Id` AS `filterId`,
        'STI' AS `journalType`,
        bl.`BlNumber` AS `blNumber`,
        bl.`Id` AS `blId`,
        inv.`StatusId` AS `statusId`,
        COALESCE(invs.`Label`, '') AS `statusLabel`,
        COALESCE(
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    'id', CAST(bli.`Id` AS CHAR),
                    'number', COALESCE(bli.`Number`, ''),
                    'type', CONCAT('[', COALESCE(bit.`Label`, ''), ']'),
                    'description', COALESCE(ci.`NumberOfPackages`, ''),
                    'isDraft', FALSE,
                    'dnPrintable', FALSE
                )
            ),
            JSON_ARRAY()
        ) AS `yardItems`
    FROM `invoice` inv
    LEFT JOIN `invoicestatus` invs ON inv.`StatusId` = invs.`Id`
    LEFT JOIN `thirdparty` tp ON inv.`BilledThirdPartyId` = tp.`Id`
    LEFT JOIN `invoiceitem` ii ON inv.`Id` = ii.`InvoiceId`
    LEFT JOIN `jobfile` jf ON ii.`JobFileId` = jf.`Id`
    LEFT JOIN `blitem_jobfile` bij ON jf.`Id` = bij.`JobFile_Id`
    LEFT JOIN `blitem` bli ON bij.`BLItem_Id` = bli.`Id`
    LEFT JOIN `yarditemtype` bit ON bli.`ItemTypeId` = bit.`Id`
    LEFT JOIN `commodityitem` ci ON bli.`Id` = ci.`BlItemId`
    LEFT JOIN `bl` bl ON bli.`BlId` = bl.`Id`
    WHERE bl.`BlNumber` = p_BlNumber
    GROUP BY inv.`Id`, inv.`InvoiceNumber`, tp.`Label`, inv.`ValIdationDate`, inv.`TotalAmount`, bl.`BlNumber`, bl.`Id`, inv.`StatusId`, invs.`Label`
    ORDER BY inv.`ValIdationDate` DESC;
END //

DELIMITER ;

-- ============================================================================
-- 5. GetPendingInvoicingItemsPerBLNumber - Récupère les conteneurs en attente de facturation
-- Filtrage : items dont les événements sont facturables et liés à un contrat,
-- mais n'ont pas encore été facturés
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `GetPendingInvoicingItemsPerBLNumber`(
    IN p_BlNumber VARCHAR(100)
)
BEGIN
    SELECT DISTINCT
        CAST(bli.`Id` AS CHAR) AS `id`,
        COALESCE(bli.`Number`, '') AS `number`,
        CONCAT('[', COALESCE(bit.`Label`, ''), ']') AS `type`,
        COALESCE(ci.`NumberOfPackages`, '') AS `description`,
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
    -- L'événement ne doit pas encore avoir été facturé
    AND NOT EXISTS (
        SELECT 1 
        FROM `invoiceitem` ii 
        WHERE ii.`EventId` = evt.`Id`
    )
    ORDER BY bli.`Number`;
END //

DELIMITER ;

-- ============================================================================
-- FIN DES PROCÉDURES STOCKÉES
-- ============================================================================
