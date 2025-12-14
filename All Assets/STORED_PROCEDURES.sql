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
-- 2.5 SearchBLByNumberWithHistory - Recherche BL et insère dans l'historique
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `SearchBLByNumberWithHistory`(
    IN p_BlNumber VARCHAR(100),
    IN p_UserId INT
)
BEGIN
    DECLARE v_BlId INT;
    DECLARE v_ShipName VARCHAR(500);
    DECLARE v_ArrivalDate DATETIME;
    DECLARE v_ItemCount INT;
    DECLARE v_NextId INT;

    -- Récupérer les détails du BL
    SELECT 
        bl.`Id`,
        COALESCE(tp_consignee.`Label`, '') AS `ShipName`,
        c.`VesselArrivalDate` AS `ArrivalDate`,
        COUNT(bli.`Id`) AS `ItemCount`
    INTO v_BlId, v_ShipName, v_ArrivalDate, v_ItemCount
    FROM `BL` bl
    LEFT JOIN `Call` c ON bl.`CallId` = c.`Id`
    LEFT JOIN `ThirdParty` tp_consignee ON bl.`ConsigneeId` = tp_consignee.`Id`
    LEFT JOIN `BLItem` bli ON bl.`Id` = bli.`BlId`
    WHERE bl.`BlNumber` = p_BlNumber
    GROUP BY bl.`Id`, bl.`BlNumber`, tp_consignee.`Label`, c.`VesselArrivalDate`;

    -- Si le BL n'existe pas, retourner vide
    IF v_BlId IS NULL THEN
        SELECT 0 AS found;
    ELSE
        -- Obtenir le prochain ID
        SELECT COALESCE(MAX(`Id`), 0) + 1 INTO v_NextId FROM `customeruserblsearchhistory`;

        -- Insérer dans l'historique de recherche
        INSERT INTO `customeruserblsearchhistory` (
            `Id`, `BlNumber`, `ShipName`, `ArrivalDate`, `ItemCount`, `UserId`, `SearchDate`
        ) VALUES (
            v_NextId, p_BlNumber, v_ShipName, v_ArrivalDate, v_ItemCount, p_UserId, NOW()
        );

        -- Retourner les détails du BL
        SELECT 
            v_BlId AS `Id`,
            p_BlNumber AS `BlNumber`,
            v_ShipName AS `ShipName`,
            v_ArrivalDate AS `ArrivalDate`,
            v_ItemCount AS `ItemCount`,
            1 AS `found`;
    END IF;
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
    IN p_BlNumber VARCHAR(100),
    IN p_CustomerUserId INT DEFAULT NULL
)
BEGIN
    SELECT 
        inv.`Id` AS `id`,
        inv.`InvoiceNumber` AS `invoiceNumber`,
        'Invoice' AS `invoiceType`,
        COALESCE(tp.`Label`, '') AS `client`,
        DATE_FORMAT(inv.`ValIdationDate`, '%d/%m/%Y') AS `billingDate`,
        DATE_FORMAT(inv.`ValIdationDate`, '%d/%m/%Y') AS `withdrawalDate`,
        CONCAT(FORMAT(inv.`TotalAmount`, 2), ' XOF') AS `total`,
        'XOF' AS `currencyCode`,
        inv.`Id` AS `filterId`,
        'STI' AS `journalType`,
        bl.`BlNumber` AS `blNumber`,
        bl.`Id` AS `blId`,
        inv.`StatusId` AS `statusId`,
        COALESCE(invs.`Label`, '') AS `statusLabel`,
        CASE 
            WHEN p_CustomerUserId IS NOT NULL 
                 AND EXISTS (
                    SELECT 1 FROM `Cart` c
                    LEFT JOIN `CartItem` ci ON c.`Id` = ci.`CartId`
                    WHERE c.`CustomerUserId` = p_CustomerUserId 
                      AND c.`Deleted` = 0
                      AND ci.`InvoiceId` = inv.`Id`
                 ) THEN 1
            ELSE 0
        END AS `isInCart`,
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
-- 7. GetYardItemsPerBLNumber - Récupère la liste des yard items pour un BL
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `GetYardItemsPerBLNumber`(
    IN p_BlNumber VARCHAR(100)
)
BEGIN
    SELECT DISTINCT
        bli.`Id` AS `id`,
        COALESCE(bli.`Number`, '') AS `yardItemNumber`,
        FALSE AS `isDraft`,
        FALSE AS `isDNPrintable`
    FROM `blitem` bli
    LEFT JOIN `bl` bl ON bli.`BlId` = bl.`Id`
    WHERE bl.`BlNumber` = p_BlNumber
    ORDER BY bli.`Number`;
END //

DELIMITER ;

-- ============================================================================
-- 8. GetYardItemTrackingMovements - Récupère les mouvements de suivi d'un yard item
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `GetYardItemTrackingMovements`(
    IN p_YardItemId INT,
    IN p_YardItemNumber VARCHAR(100),
    IN p_BillOfLadingNumber VARCHAR(100)
)
BEGIN
    SELECT 
        COALESCE(evt.`EventDate`, '') AS `Date`,
        COALESCE(et.`Label`, '') AS `EventTypeName`,
        COALESCE(et.`Code`, '') AS `EventTypeCode`,
        COALESCE('False', '') AS `CreatedByIES`,
        COALESCE('', '') AS `Position`
    FROM `event` evt
    LEFT JOIN `eventtype` et ON evt.`EventTypeId` = et.`Id`
    LEFT JOIN `jobfile` jf ON evt.`JobFileId` = jf.`Id`
    LEFT JOIN `blitem_jobfile` bij ON jf.`Id` = bij.`JobFile_Id`
    LEFT JOIN `blitem` bli ON bij.`BLItem_Id` = bli.`Id`
    LEFT JOIN `bl` bl ON bli.`BlId` = bl.`Id`
    WHERE bli.`Number` = p_YardItemNumber
    AND bl.`BlNumber` = p_BillOfLadingNumber
    ORDER BY evt.`EventDate` DESC;
END //

DELIMITER ;

-- ============================================================================
-- 9. DeleteYardItemEvent - Supprime un événement de suivi créé par IES
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `DeleteYardItemEvent`(
    IN p_EventTypeCode VARCHAR(50),
    IN p_YardItemNumber VARCHAR(100),
    IN p_EventDateString DATETIME,
    IN p_BillOfLadingNumber VARCHAR(100)
)
BEGIN
    DECLARE v_EventId INT;
    DECLARE v_YardItemId INT;
    
    -- Récupérer l'ID du yard item
    SELECT bli.`Id` INTO v_YardItemId
    FROM `blitem` bli
    LEFT JOIN `bl` bl ON bli.`BlId` = bl.`Id`
    WHERE bli.`Number` = p_YardItemNumber
    AND bl.`BlNumber` = p_BillOfLadingNumber
    LIMIT 1;
    
    -- Si le yard item existe, chercher et supprimer l'événement
    IF v_YardItemId IS NOT NULL THEN
        SELECT evt.`Id` INTO v_EventId
        FROM `event` evt
        LEFT JOIN `eventtype` et ON evt.`EventTypeId` = et.`Id`
        LEFT JOIN `jobfile` jf ON evt.`JobFileId` = jf.`Id`
        LEFT JOIN `blitem_jobfile` bij ON jf.`Id` = bij.`JobFile_Id`
        WHERE bij.`BLItem_Id` = v_YardItemId
        AND et.`Code` = p_EventTypeCode
        AND DATE(evt.`EventDate`) = DATE(p_EventDateString)
        LIMIT 1;
        
        -- Supprimer l'événement s'il existe
        IF v_EventId IS NOT NULL THEN
            DELETE FROM `event` WHERE `Id` = v_EventId;
        END IF;
    END IF;
END //

DELIMITER ;

-- ============================================================================
-- 10. GetBLByNumber - Recherche BL et insère dans l'historique
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `GetBLByNumber`(
    IN p_BlNumber VARCHAR(100),
    IN p_UserId INT
)
BEGIN
    DECLARE v_BlId INT;
    DECLARE v_ShipName VARCHAR(500);
    DECLARE v_ArrivalDate DATETIME;
    DECLARE v_ItemCount INT;
    DECLARE v_NextId INT;

    -- Récupérer les détails du BL
    SELECT 
        bl.`Id`,
        COALESCE(tp_consignee.`Label`, '') AS `ShipName`,
        c.`VesselArrivalDate` AS `ArrivalDate`,
        COUNT(bli.`Id`) AS `ItemCount`
    INTO v_BlId, v_ShipName, v_ArrivalDate, v_ItemCount
    FROM `BL` bl
    LEFT JOIN `Call` c ON bl.`CallId` = c.`Id`
    LEFT JOIN `ThirdParty` tp_consignee ON bl.`ConsigneeId` = tp_consignee.`Id`
    LEFT JOIN `BLItem` bli ON bl.`Id` = bli.`BlId`
    WHERE bl.`BlNumber` = p_BlNumber
    GROUP BY bl.`Id`, bl.`BlNumber`, tp_consignee.`Label`, c.`VesselArrivalDate`;

    -- Si le BL n'existe pas, retourner vide
    IF v_BlId IS NULL THEN
        SELECT NULL AS `BlNumber`;
    ELSE
        -- Obtenir le prochain ID
        SELECT COALESCE(MAX(`Id`), 0) + 1 INTO v_NextId FROM `customeruserblsearchhistory`;

        -- Insérer dans l'historique de recherche
        INSERT INTO `customeruserblsearchhistory` (
            `Id`, `BlNumber`, `ShipName`, `ArrivalDate`, `ItemCount`, `UserId`, `SearchDate`
        ) VALUES (
            v_NextId, p_BlNumber, v_ShipName, v_ArrivalDate, v_ItemCount, p_UserId, NOW()
        );

        -- Retourner les détails du BL
        SELECT 
            v_BlId AS `Id`,
            p_BlNumber AS `BlNumber`,
            v_ShipName AS `ShipName`,
            v_ArrivalDate AS `ArrivalDate`,
            v_ItemCount AS `ItemCount`,
            1 AS `found`;
    END IF;
END //

DELIMITER ;

-- ============================================================================
-- 11. GetCartByUserId - Récupère le panier et ses articles pour un utilisateur
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `GetCartByUserId`(
    IN p_CustomerUserId INT
)
BEGIN
    -- Récupérer le panier et tous les articles avec les détails du BL
    SELECT 
        c.`Id` AS cartId,
        c.`CustomerUserId`,
        c.`CreatedDate`,
        ci.`Id` AS itemId,
        ci.`InvoiceId`,
        ci.`InvoicePaidAmount`,
        ci.`InvoiceNumber`,
        ci.`BillingDate`,
        COALESCE(bl.`BlNumber`, '') AS `BlNumber`
    FROM `Cart` c
    LEFT JOIN `CartItem` ci ON c.`Id` = ci.`CartId`
    LEFT JOIN `invoice` inv ON ci.`InvoiceId` = inv.`Id`
    LEFT JOIN `invoiceitem` ii ON inv.`Id` = ii.`InvoiceId`
    LEFT JOIN `jobfile` jf ON ii.`JobFileId` = jf.`Id`
    LEFT JOIN `blitem_jobfile` bij ON jf.`Id` = bij.`JobFile_Id`
    LEFT JOIN `blitem` bli ON bij.`BLItem_Id` = bli.`Id`
    LEFT JOIN `bl` bl ON bli.`BlId` = bl.`Id`
    WHERE c.`CustomerUserId` = p_CustomerUserId
      AND c.`Deleted` = 0
    ORDER BY c.`CreatedDate` DESC, ci.`Id` ASC;
END //

DELIMITER ;

-- ============================================================================
-- 12. AddInvoiceToCart - Ajoute une facture au panier d'un utilisateur
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `AddInvoiceToCart`(
    IN p_CustomerUserId INT,
    IN p_InvoiceId INT,
    IN p_InvoiceNumber VARCHAR(100),
    IN p_InvoicePaidAmount DECIMAL(18, 2)
)
BEGIN
    DECLARE v_CartId INT;
    DECLARE v_ExistingItem INT;

    -- Récupérer ou créer le panier
    SELECT `Id` INTO v_CartId 
    FROM `Cart`
    WHERE `CustomerUserId` = p_CustomerUserId AND `Deleted` = 0
    LIMIT 1;

    -- Si pas de panier, en créer un
    IF v_CartId IS NULL THEN
        INSERT INTO `Cart` (`CustomerUserId`, `CreatedDate`, `Deleted`)
        VALUES (p_CustomerUserId, NOW(), 0);
        SET v_CartId = LAST_INSERT_ID();
    END IF;

    -- Vérifier si la facture existe déjà dans le panier
    SELECT COUNT(*) INTO v_ExistingItem
    FROM `CartItem`
    WHERE `CartId` = v_CartId AND `InvoiceId` = p_InvoiceId;

    -- Si la facture n'existe pas, l'ajouter
    IF v_ExistingItem = 0 THEN
        INSERT INTO `CartItem` (
            `CartId`, `InvoiceId`, `InvoicePaidAmount`, `InvoiceNumber`, `BillingDate`
        ) VALUES (
            v_CartId, p_InvoiceId, p_InvoicePaidAmount, p_InvoiceNumber, NOW()
        );
    END IF;

    -- Retourner l'ID du panier et le nombre d'articles
    SELECT 
        v_CartId AS `cartId`,
        (SELECT COUNT(*) FROM `CartItem` WHERE `CartId` = v_CartId) AS `itemCount`;
END //

DELIMITER ;

-- ============================================================================
-- 13. RemoveInvoiceFromCart - Supprime une facture du panier d'un utilisateur
-- ============================================================================
DELIMITER //

CREATE PROCEDURE `RemoveInvoiceFromCart`(
    IN p_CustomerUserId INT,
    IN p_InvoiceId INT
)
BEGIN
    DECLARE v_CartId INT;

    -- Récupérer le panier de l'utilisateur
    SELECT `Id` INTO v_CartId
    FROM `Cart`
    WHERE `CustomerUserId` = p_CustomerUserId AND `Deleted` = 0
    LIMIT 1;

    IF v_CartId IS NOT NULL THEN
        -- Supprimer l'article du panier
        DELETE FROM `CartItem`
        WHERE `CartId` = v_CartId AND `InvoiceId` = p_InvoiceId;

        -- Retourner le nombre d'articles restants
        SELECT 
            v_CartId AS cartId,
            (SELECT COUNT(*) FROM `CartItem` WHERE `CartId` = v_CartId) AS itemCount;
    ELSE
        SELECT 0 AS cartId, 0 AS itemCount;
    END IF;
END //

DELIMITER ;

-- ============================================================================
-- FIN DES PROCÉDURES STOCKÉES
-- ============================================================================
