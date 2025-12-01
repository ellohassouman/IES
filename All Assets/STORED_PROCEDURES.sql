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
-- FIN DES PROCÉDURES STOCKÉES
-- ============================================================================
