-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 02 déc. 2025 à 21:16
-- Version du serveur : 8.0.27
-- Version de PHP : 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ies`
--

DELIMITER $$
--
-- Procédures
--
DROP PROCEDURE IF EXISTS `GetDetailsPerBLNumber`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetDetailsPerBLNumber` (IN `p_BlNumber` VARCHAR(100))  BEGIN
    SELECT 
        bl.`BlNumber` AS `blNumber`,
        COALESCE(c.`CallNumber`, '') AS `manifest`,
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
        COALESCE(tp_carrier.`Label`, '') AS `shipName`,
        c.`CallNumber` AS `voyageNumber`,
        DATE_FORMAT(c.`VesselArrivalDate`, '%Y-%m-%d') AS `blDate`,
        c.`CallNumber` AS `callNumber`,
        DATE_FORMAT(c.`VesselArrivalDate`, '%Y-%m-%d %H:%i:%s') AS `arrivalDate`,
        DATE_FORMAT(c.`VesselArrivalDate`, '%Y-%m-%d %H:%i:%s') AS `estimatedArrivalDate`,
        DATE_FORMAT(c.`VesselArrivalDate`, '%Y-%m-%d %H:%i:%s') AS `actualArrivalDate`,
        DATE_FORMAT(c.`VesselDepatureDate`, '%Y-%m-%d %H:%i:%s') AS `estimatedDepartureDate`,
        DATE_FORMAT(c.`VesselArrivalDate`, '%Y-%m-%d %H:%i:%s') AS `shipAvailabilityDate`,
        DATE_FORMAT(c.`VesselDepatureDate`, '%Y-%m-%d %H:%i:%s') AS `lastLineUnsecured`,
        DATE_FORMAT(c.`VesselDepatureDate`, '%Y-%m-%d %H:%i:%s') AS `shipDepartureDate`,
        '' AS `stevedore`,
        'Maritime' AS `transportedBy`,
        COALESCE(tp_consignee.`Label`, '') AS `callPort`,
        c.`CallNumber` AS `callReference`,
        '' AS `grossWeight`,
        COALESCE(tp_carrier.`Label`, '') AS `shipVoyageName`,
        c.`CallNumber` AS `incomingVoyage`,
        c.`CallNumber` AS `outgoingVoyage`,
        COALESCE(tp_carrier.`code`, '') AS `shipOperator`,
        '' AS `departureLocation`
    FROM `BL` bl
    LEFT JOIN `Call` c ON bl.`CallId` = c.`Id`
    LEFT JOIN `ThirdParty` tp_consignee ON c.`ThirdPartyId` = tp_consignee.`Id`
    LEFT JOIN `ThirdParty` tp_customer ON bl.`RelatedCustomerId` = tp_customer.`Id`
    LEFT JOIN `ThirdParty` tp_carrier ON c.`ThirdPartyId` = tp_carrier.`Id`
    WHERE bl.`BlNumber` = p_BlNumber
    LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS `GetInvoicesPerBLNumber`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetInvoicesPerBLNumber` (IN `p_BlNumber` VARCHAR(100))  BEGIN
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
    LEFT JOIN `thirdparty` tp ON inv.`BilledThirdPartyId` = tp.`Id`
    LEFT JOIN `invoiceitem` ii ON inv.`Id` = ii.`InvoiceId`
    LEFT JOIN `jobfile` jf ON ii.`JobFileId` = jf.`Id`
    LEFT JOIN `blitem_jobfile` bij ON jf.`Id` = bij.`JobFile_Id`
    LEFT JOIN `blitem` bli ON bij.`BLItem_Id` = bli.`Id`
    LEFT JOIN `yarditemtype` bit ON bli.`ItemTypeId` = bit.`Id`
    LEFT JOIN `commodityitem` ci ON bli.`Id` = ci.`BlItemId`
    LEFT JOIN `bl` bl ON bli.`BlId` = bl.`Id`
    WHERE bl.`BlNumber` = p_BlNumber
    GROUP BY inv.`Id`, inv.`InvoiceNumber`, tp.`Label`, inv.`ValIdationDate`, inv.`TotalAmount`, bl.`BlNumber`, bl.`Id`
    ORDER BY inv.`ValIdationDate` DESC;
END$$

DROP PROCEDURE IF EXISTS `GetUserBLHistory`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserBLHistory` (IN `p_UserId` INT)  BEGIN
    SELECT 
        `BlNumber` AS `blNumber`,
        `ShipName` AS `shipName`,
        `ArrivalDate` AS `arrivalDate`,
        `ItemCount` AS `itemCount`
    FROM `CustomerUserBLSearchHistory`
    WHERE `UserId` = p_UserId
    ORDER BY `SearchDate` DESC;
END$$

DROP PROCEDURE IF EXISTS `GetPendingInvoicingItemsPerBLNumber`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetPendingInvoicingItemsPerBLNumber` (IN `p_BlNumber` VARCHAR(100))  BEGIN
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
    AND cet.`Contract_Id` IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 
        FROM `invoiceitem` ii 
        WHERE ii.`EventId` = evt.`Id`
    )
    ORDER BY bli.`Number`;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `area`
--

DROP TABLE IF EXISTS `area`;
CREATE TABLE IF NOT EXISTS `area` (
  `Id` int NOT NULL,
  `Code` varchar(100) DEFAULT NULL,
  `TerminalId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Area_Terminal` (`TerminalId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `area`
--

INSERT INTO `area` (`Id`, `Code`, `TerminalId`) VALUES
(1, 'ZONE_A', 1),
(2, 'ZONE_B', 1),
(3, 'ZONE_C', 2),
(4, 'ZONE_D', 2);

-- --------------------------------------------------------

--
-- Structure de la table `bl`
--

DROP TABLE IF EXISTS `bl`;
CREATE TABLE IF NOT EXISTS `bl` (
  `Id` int NOT NULL,
  `BlNumber` varchar(100) DEFAULT NULL,
  `ConsigneeId` int DEFAULT NULL,
  `RelatedCustomerId` int DEFAULT NULL,
  `CallId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_BL_Consignee` (`ConsigneeId`),
  KEY `FK_BL_RelatedCustomer` (`RelatedCustomerId`),
  KEY `FK_BL_Call` (`CallId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `bl`
--

INSERT INTO `bl` (`Id`, `BlNumber`, `ConsigneeId`, `RelatedCustomerId`, `CallId`) VALUES
(1, 'MEDUDM992142', 10, 11, 1),
(2, 'EBKG08737243', 11, 12, 2),
(3, 'AEV0238293', 12, 10, 2),
(4, 'AEV0239463', 13, 11, 3);

-- --------------------------------------------------------

--
-- Structure de la table `blitem`
--

DROP TABLE IF EXISTS `blitem`;
CREATE TABLE IF NOT EXISTS `blitem` (
  `Id` int NOT NULL,
  `Number` varchar(100) DEFAULT NULL,
  `Weight` decimal(10,0) DEFAULT NULL,
  `Volume` decimal(10,0) DEFAULT NULL,
  `BlId` int DEFAULT NULL,
  `ItemTypeId` int DEFAULT NULL,
  `ItemCodeId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_BLItem_BL` (`BlId`),
  KEY `FK_BLItem_ItemCode` (`ItemCodeId`),
  KEY `FK_BLItem_ItemType` (`ItemTypeId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `blitem`
--

INSERT INTO `blitem` (`Id`, `Number`, `Weight`, `Volume`, `BlId`, `ItemTypeId`, `ItemCodeId`) VALUES
(1, 'BL001-001', '18000', '34', 1, 2, 1),
(2, 'BL001-002', '16000', '34', 1, 2, 1),
(3, 'BL002-001', '15000', '34', 2, 2, 1),
(4, 'BL002-002', '8000', '17', 2, 1, 1),
(5, 'BL003-001', '2500', '3', 3, 3, 2),
(6, 'BL003-002', '3000', '3', 3, 3, 2),
(7, 'BL004-001', '12000', '25', 4, 2, 1),
(8, 'BL004-002', '500', '1', 4, 4, 4);

-- --------------------------------------------------------

--
-- Structure de la table `blitem_jobfile`
--

DROP TABLE IF EXISTS `blitem_jobfile`;
CREATE TABLE IF NOT EXISTS `blitem_jobfile` (
  `BLItem_Id` int NOT NULL,
  `JobFile_Id` int NOT NULL,
  PRIMARY KEY (`BLItem_Id`,`JobFile_Id`),
  KEY `FK_BLItem_JobFile_JobFile` (`JobFile_Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `blitem_jobfile`
--

INSERT INTO `blitem_jobfile` (`BLItem_Id`, `JobFile_Id`) VALUES
(1, 1),
(2, 1),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 3),
(8, 3);

-- --------------------------------------------------------

--
-- Structure de la table `call`
--

DROP TABLE IF EXISTS `call`;
CREATE TABLE IF NOT EXISTS `call` (
  `Id` int NOT NULL,
  `CallNumber` varchar(40) DEFAULT NULL,
  `VesselArrivalDate` datetime DEFAULT NULL,
  `VesselDepatureDate` datetime DEFAULT NULL,
  `ThirdPartyId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Call_ThirdParty` (`ThirdPartyId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `call`
--

INSERT INTO `call` (`Id`, `CallNumber`, `VesselArrivalDate`, `VesselDepatureDate`, `ThirdPartyId`) VALUES
(1, 'CALL_2025_001', '2025-11-10 08:00:00', '2025-11-25 18:00:00', 1),
(2, 'CALL_2025_002', '2025-11-15 10:30:00', '2025-11-30 20:00:00', 2),
(3, 'CALL_2025_003', '2025-11-18 14:00:00', '2025-12-05 22:00:00', 3);

-- --------------------------------------------------------

--
-- Structure de la table `commodity`
--

DROP TABLE IF EXISTS `commodity`;
CREATE TABLE IF NOT EXISTS `commodity` (
  `Id` int NOT NULL,
  `Label` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `commodity`
--

INSERT INTO `commodity` (`Id`, `Label`) VALUES
(1, 'Électronique'),
(2, 'Vêtements et textiles'),
(3, 'Produits chimiques'),
(4, 'Matériel agricole'),
(5, 'Mobilier'),
(6, 'Équipements industriels');

-- --------------------------------------------------------

--
-- Structure de la table `commodityitem`
--

DROP TABLE IF EXISTS `commodityitem`;
CREATE TABLE IF NOT EXISTS `commodityitem` (
  `Id` int NOT NULL,
  `Weight` decimal(10,0) DEFAULT NULL,
  `NumberOfPackages` int DEFAULT NULL,
  `CommodityId` int DEFAULT NULL,
  `BlItemId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_CommodityItem_Commodity` (`CommodityId`),
  KEY `FK_CommodityItem_BLItem` (`BlItemId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `commodityitem`
--

INSERT INTO `commodityitem` (`Id`, `Weight`, `NumberOfPackages`, `CommodityId`, `BlItemId`) VALUES
(1, '18000', 85, 1, 1),
(2, '16000', 75, 2, 2),
(3, '15000', 70, 3, 3),
(4, '8000', 40, 4, 4),
(5, '2500', 50, 5, 5),
(6, '3000', 60, 5, 6),
(7, '12000', 60, 6, 7),
(8, '500', 250, 1, 8);

-- --------------------------------------------------------

--
-- Structure de la table `contract`
--

DROP TABLE IF EXISTS `contract`;
CREATE TABLE IF NOT EXISTS `contract` (
  `Id` int NOT NULL,
  `Code` varchar(50) DEFAULT NULL,
  `InvoiceLabel` varchar(256) DEFAULT NULL,
  `TaxCodeId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Contract_TaxCodes` (`TaxCodeId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `contract`
--

INSERT INTO `contract` (`Id`, `Code`, `InvoiceLabel`, `TaxCodeId`) VALUES
(1, 'CTR001', 'Contrat Standard', 1),
(2, 'CTR002', 'Contrat Premium', 1),
(3, 'CTR003', 'Contrat Special', 2);

-- --------------------------------------------------------

--
-- Structure de la table `contract_eventtype`
--

DROP TABLE IF EXISTS `contract_eventtype`;
CREATE TABLE IF NOT EXISTS `contract_eventtype` (
  `Contract_Id` int NOT NULL,
  `EventType_Id` int NOT NULL,
  PRIMARY KEY (`Contract_Id`,`EventType_Id`),
  KEY `FK_Contract_EventType_EventType` (`EventType_Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `contract_eventtype`
--

INSERT INTO `contract_eventtype` (`Contract_Id`, `EventType_Id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(3, 2),
(3, 3),
(3, 5),
(3, 6);

-- --------------------------------------------------------

--
-- Structure de la table `customeruserblsearchhistory`
--

DROP TABLE IF EXISTS `customeruserblsearchhistory`;
CREATE TABLE IF NOT EXISTS `customeruserblsearchhistory` (
  `Id` int NOT NULL,
  `BlNumber` varchar(100) DEFAULT NULL,
  `ShipName` varchar(500) DEFAULT NULL,
  `ArrivalDate` datetime DEFAULT NULL,
  `ItemCount` int DEFAULT NULL,
  `UserId` int DEFAULT NULL,
  `SearchDate` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `FK_UserBLSearchHistory_UserId` (`UserId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `customeruserblsearchhistory`
--

INSERT INTO `customeruserblsearchhistory` (`Id`, `BlNumber`, `ShipName`, `ArrivalDate`, `ItemCount`, `UserId`, `SearchDate`) VALUES
(1, 'AEV0238293', 'Mediterranean Dream', '2025-11-15 10:30:00', 1, 1, '2025-11-20 09:15:00'),
(2, 'MEDUDM992142', 'Atlantic Express', '2025-11-10 08:00:00', 1, 1, '2025-11-21 14:30:00'),
(3, 'AEV0239463', 'Pacific Voyager', '2025-11-18 14:00:00', 1, 1, '2025-11-22 11:45:00'),
(4, 'EBKG08737243', 'Global Carrier', '2025-11-15 10:30:00', 1, 2, '2025-11-19 08:00:00'),
(5, 'AEV0238293', 'Mediterranean Dream', '2025-11-15 10:30:00', 1, 2, '2025-11-20 16:20:00'),
(6, 'MEDUDM992142', 'Atlantic Express', '2025-11-10 08:00:00', 1, 2, '2025-11-21 10:10:00'),
(7, 'EBKG08737243', 'Global Carrier', '2025-11-15 10:30:00', 1, 3, '2025-11-18 13:30:00'),
(8, 'AEV0239463', 'Pacific Voyager', '2025-11-18 14:00:00', 1, 3, '2025-11-19 15:45:00'),
(9, 'AEV0238293', 'Mediterranean Dream', '2025-11-15 10:30:00', 1, 3, '2025-11-21 09:00:00'),
(10, 'MEDUDM992142', 'Atlantic Express', '2025-11-10 08:00:00', 1, 3, '2025-11-22 12:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `customerusers`
--

DROP TABLE IF EXISTS `customerusers`;
CREATE TABLE IF NOT EXISTS `customerusers` (
  `Id` int NOT NULL,
  `UserName` varchar(512) DEFAULT NULL,
  `PasswordHash` varchar(2000) DEFAULT NULL,
  `EmailConfirmed` int DEFAULT NULL,
  `FirstName` varchar(2000) DEFAULT NULL,
  `LastName` varchar(2000) DEFAULT NULL,
  `CompanyName` varchar(2000) DEFAULT NULL,
  `CompanyAddress` varchar(2000) DEFAULT NULL,
  `PhoneNumber` varchar(100) DEFAULT NULL,
  `CustomerUsersStatusId` int DEFAULT NULL,
  `CustomerUsersTypeId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_CustomerUsers_Status` (`CustomerUsersStatusId`),
  KEY `FK_CustomerUsers_Type` (`CustomerUsersTypeId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `customerusers`
--

INSERT INTO `customerusers` (`Id`, `UserName`, `PasswordHash`, `EmailConfirmed`, `FirstName`, `LastName`, `CompanyName`, `CompanyAddress`, `PhoneNumber`, `CustomerUsersStatusId`, `CustomerUsersTypeId`) VALUES
(1, 'karimex@user.com', 'hashed_password_1', 1, 'Ahmed', 'Hassan', 'KARIMEX', '123 Rue du Commerce, Casablanca', '+212661234567', 1, 2),
(2, 'import@company.com', 'hashed_password_2', 1, 'Mohamed', 'Bennani', 'IMPORT EXPORT SARL', '456 Avenue Hassan II, Rabat', '+212662345678', 1, 2),
(3, 'trade@intl.com', 'hashed_password_3', 1, 'Fatima', 'Alaoui', 'COMMERCE INTERNATIONAL', '789 Boulevard Zerktouni, Casablanca', '+212663456789', 1, 3);

-- --------------------------------------------------------

--
-- Structure de la table `customerusersstatus`
--

DROP TABLE IF EXISTS `customerusersstatus`;
CREATE TABLE IF NOT EXISTS `customerusersstatus` (
  `Id` int NOT NULL,
  `Label` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `customerusersstatus`
--

INSERT INTO `customerusersstatus` (`Id`, `Label`) VALUES
(1, 'Actif'),
(2, 'Inactif'),
(3, 'Suspendu'),
(4, 'En attente');

-- --------------------------------------------------------

--
-- Structure de la table `customeruserstype`
--

DROP TABLE IF EXISTS `customeruserstype`;
CREATE TABLE IF NOT EXISTS `customeruserstype` (
  `Id` int NOT NULL,
  `Label` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `customeruserstype`
--

INSERT INTO `customeruserstype` (`Id`, `Label`) VALUES
(1, 'Administrateur'),
(2, 'Client Standard'),
(3, 'Client Premium'),
(4, 'Partenaire');

-- --------------------------------------------------------

--
-- Structure de la table `customerusers_thirdparty`
--

DROP TABLE IF EXISTS `customerusers_thirdparty`;
CREATE TABLE IF NOT EXISTS `customerusers_thirdparty` (
  `CustomerUsers_Id` int NOT NULL,
  `ThirdParty_Id` int NOT NULL,
  PRIMARY KEY (`CustomerUsers_Id`,`ThirdParty_Id`),
  KEY `FK_CustomerUsers_ThirdParty_ThirdParty` (`ThirdParty_Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `customerusers_thirdparty`
--

INSERT INTO `customerusers_thirdparty` (`CustomerUsers_Id`, `ThirdParty_Id`) VALUES
(1, 10),
(2, 11),
(3, 12);

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

DROP TABLE IF EXISTS `document`;
CREATE TABLE IF NOT EXISTS `document` (
  `Id` int NOT NULL,
  `Text` varchar(100) DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `BlId` int DEFAULT NULL,
  `JobFileId` int DEFAULT NULL,
  `DocumentTypeId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Document_BL` (`BlId`),
  KEY `FK_Document_JobFile` (`JobFileId`),
  KEY `FK_Document_DocumentType` (`DocumentTypeId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `document`
--

INSERT INTO `document` (`Id`, `Text`, `Date`, `BlId`, `JobFileId`, `DocumentTypeId`) VALUES
(1, 'Original B/L - MEDUDM992142', '2025-11-10 09:30:00', 1, 1, 1),
(2, 'Manifest de chargement', '2025-11-10 10:00:00', 1, 1, 2),
(3, 'Packing List', '2025-11-10 10:30:00', 1, 1, 3),
(4, 'Original B/L - EBKG08737243', '2025-11-15 11:30:00', 2, 2, 1),
(5, 'Certificat de sécurité', '2025-11-15 12:00:00', 2, 2, 6);

-- --------------------------------------------------------

--
-- Structure de la table `documenttype`
--

DROP TABLE IF EXISTS `documenttype`;
CREATE TABLE IF NOT EXISTS `documenttype` (
  `Id` int NOT NULL,
  `Label` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `documenttype`
--

INSERT INTO `documenttype` (`Id`, `Label`) VALUES
(1, 'Bill of Lading (B/L)'),
(2, 'Manifest'),
(3, 'Packing List'),
(4, 'Facture'),
(5, 'Bon de livraison'),
(6, 'Certificat'),
(7, 'Assurance');

-- --------------------------------------------------------

--
-- Structure de la table `event`
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE IF NOT EXISTS `event` (
  `Id` int NOT NULL,
  `EventDate` datetime DEFAULT NULL,
  `JobFileId` int DEFAULT NULL,
  `EventTypeId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Event_JobFile` (`JobFileId`),
  KEY `FK_Event_EventType` (`EventTypeId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `event`
--

INSERT INTO `event` (`Id`, `EventDate`, `JobFileId`, `EventTypeId`) VALUES
(1, '2025-11-10 09:00:00', 1, 1),
(2, '2025-11-10 14:00:00', 1, 2),
(3, '2025-11-15 11:00:00', 2, 1),
(4, '2025-11-15 16:00:00', 2, 2),
(5, '2025-11-18 15:00:00', 3, 1),
(6, '2025-11-20 10:00:00', 4, 5),
(7, '2025-11-22 17:00:00', 4, 6);

-- --------------------------------------------------------

--
-- Structure de la table `eventtype`
--

DROP TABLE IF EXISTS `eventtype`;
CREATE TABLE IF NOT EXISTS `eventtype` (
  `Id` int NOT NULL,
  `Code` varchar(4) DEFAULT NULL,
  `Label` varchar(100) DEFAULT NULL,
  `FamilyId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_EventType_Family` (`FamilyId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `eventtype`
--

INSERT INTO `eventtype` (`Id`, `Code`, `Label`, `FamilyId`) VALUES
(1, 'ARR', 'Arrivée au terminal', 1),
(2, 'DCH', 'Déchargement effectué', 2),
(3, 'ENT', 'Entreposage', 3),
(4, 'SRV', 'Service effectué', 4),
(5, 'ENL', 'Enlèvement prévu', 5),
(6, 'LIV', 'Livraison effectuée', 6);

-- --------------------------------------------------------

--
-- Structure de la table `family`
--

DROP TABLE IF EXISTS `family`;
CREATE TABLE IF NOT EXISTS `family` (
  `Id` int NOT NULL,
  `Label` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `family`
--

INSERT INTO `family` (`Id`, `Label`) VALUES
(1, 'Arrivée'),
(2, 'Déchargement'),
(3, 'Entreposage'),
(4, 'Service'),
(5, 'Enlèvement'),
(6, 'Livraison');

-- --------------------------------------------------------

--
-- Structure de la table `invoicestatus`
--

DROP TABLE IF EXISTS `invoicestatus`;
CREATE TABLE IF NOT EXISTS `invoicestatus` (
  `Id` int NOT NULL,
  `Label` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `invoicestatus`
--

INSERT INTO `invoicestatus` (`Id`, `Label`) VALUES
(1, 'Draft'),
(2, 'Saved'),
(3, 'Validated'),
(4, 'Paid'),
(5, 'PartiallyPaid'),
(6, 'Reconciled'),
(7, 'Applied'),
(8, 'Settled'),
(9, 'SettledCash');

-- --------------------------------------------------------

--
-- Structure de la table `invoice`
--

DROP TABLE IF EXISTS `invoice`;
CREATE TABLE IF NOT EXISTS `invoice` (
  `Id` int NOT NULL,
  `InvoiceNumber` int DEFAULT NULL,
  `ValIdationDate` date DEFAULT NULL,
  `SubTotalAmount` decimal(10,0) DEFAULT NULL,
  `TotalTaxAmount` decimal(10,0) DEFAULT NULL,
  `TotalAmount` decimal(10,0) DEFAULT NULL,
  `BilledThirdPartyId` int DEFAULT NULL,
  `StatusId` int DEFAULT 1,
  PRIMARY KEY (`Id`),
  KEY `FK_Invoice_BilledThirdParty` (`BilledThirdPartyId`),
  KEY `FK_Invoice_Status` (`StatusId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `invoice`
--

INSERT INTO `invoice` (`Id`, `InvoiceNumber`, `ValIdationDate`, `SubTotalAmount`, `TotalTaxAmount`, `TotalAmount`, `BilledThirdPartyId`, `StatusId`) VALUES
(1, 251018767, '2025-11-12', '5000', '1000', '6000', 10, 3),
(2, 251018791, '2025-11-12', '4500', '900', '5400', 10, 4),
(3, 251083041, '2025-11-19', '3800', '760', '4560', 11, 3),
(4, 251083042, '2025-11-20', '2500', '500', '3000', 12, 1),
(5, 251083043, '2025-11-20', '6000', '1200', '7200', 13, 2);

-- --------------------------------------------------------

--
-- Structure de la table `invoiceitem`
--

DROP TABLE IF EXISTS `invoiceitem`;
CREATE TABLE IF NOT EXISTS `invoiceitem` (
  `Id` int NOT NULL,
  `Quantity` int DEFAULT NULL,
  `Rate` decimal(10,0) DEFAULT NULL,
  `Amount` decimal(10,0) DEFAULT NULL,
  `CalculatedTax` decimal(10,0) DEFAULT NULL,
  `InvoiceId` int DEFAULT NULL,
  `JobFileId` int DEFAULT NULL,
  `EventId` int DEFAULT NULL,
  `SubscriptionId` int DEFAULT NULL,
  `RateRangePeriodId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_InvoiceItem_Invoice` (`InvoiceId`),
  KEY `FK_InvoiceItem_JobFile` (`JobFileId`),
  KEY `FK_InvoiceItem_Event` (`EventId`),
  KEY `FK_InvoiceItem_Subscription` (`SubscriptionId`),
  KEY `FK_InvoiceItem_RateRangePeriod` (`RateRangePeriodId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `invoiceitem`
--

INSERT INTO `invoiceitem` (`Id`, `Quantity`, `Rate`, `Amount`, `CalculatedTax`, `InvoiceId`, `JobFileId`, `EventId`, `SubscriptionId`, `RateRangePeriodId`) VALUES
(1, 10, '500', '5000', '1000', 1, 1, 1, 1, 1),
(2, 12, '375', '4500', '900', 2, 1, 2, 1, 2),
(3, 8, '475', '3800', '760', 3, 2, 3, 2, 1),
(4, 5, '500', '2500', '500', 4, 2, 4, 2, 1),
(5, 15, '400', '6000', '1200', 5, 3, 5, 3, 3);

-- --------------------------------------------------------

--
-- Structure de la table `jobfile`
--

DROP TABLE IF EXISTS `jobfile`;
CREATE TABLE IF NOT EXISTS `jobfile` (
  `Id` int NOT NULL,
  `DateOpen` datetime DEFAULT NULL,
  `DateClose` datetime DEFAULT NULL,
  `ShippingLineId` int DEFAULT NULL,
  `PositionId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_JobFile_ShippingLine` (`ShippingLineId`),
  KEY `FK_JobFile_Position` (`PositionId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `jobfile`
--

INSERT INTO `jobfile` (`Id`, `DateOpen`, `DateClose`, `ShippingLineId`, `PositionId`) VALUES
(1, '2025-11-10 09:00:00', NULL, 1, 1),
(2, '2025-11-15 11:00:00', NULL, 2, 2),
(3, '2025-11-18 15:00:00', NULL, 3, 4),
(4, '2025-11-20 10:00:00', '2025-11-22 18:00:00', 1, 5);

-- --------------------------------------------------------

--
-- Structure de la table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `Id` int NOT NULL,
  `Number` int DEFAULT NULL,
  `Value` decimal(10,0) DEFAULT NULL,
  `PaymentDate` datetime DEFAULT NULL,
  `PaymentTypeId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Payment_PaymentType` (`PaymentTypeId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `payment`
--

INSERT INTO `payment` (`Id`, `Number`, `Value`, `PaymentDate`, `PaymentTypeId`) VALUES
(1, 1001, '6000', '2025-11-13 00:00:00', 3),
(2, 1002, '5400', '2025-11-14 00:00:00', 3),
(3, 1003, '4560', '2025-11-21 00:00:00', 2),
(4, 1004, '3000', '2025-11-21 00:00:00', 4);

-- --------------------------------------------------------

--
-- Structure de la table `paymenttype`
--

DROP TABLE IF EXISTS `paymenttype`;
CREATE TABLE IF NOT EXISTS `paymenttype` (
  `Id` int NOT NULL,
  `Label` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `paymenttype`
--

INSERT INTO `paymenttype` (`Id`, `Label`) VALUES
(1, 'Espèces'),
(2, 'Chèque'),
(3, 'Virement bancaire'),
(4, 'Carte de crédit'),
(5, 'Crédit client');

-- --------------------------------------------------------

--
-- Structure de la table `payment_invoice`
--

DROP TABLE IF EXISTS `payment_invoice`;
CREATE TABLE IF NOT EXISTS `payment_invoice` (
  `Payment_Id` int NOT NULL,
  `Invoice_Id` int NOT NULL,
  PRIMARY KEY (`Payment_Id`,`Invoice_Id`),
  KEY `FK_Payment_Invoice_Invoice` (`Invoice_Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `payment_invoice`
--

INSERT INTO `payment_invoice` (`Payment_Id`, `Invoice_Id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4);

-- --------------------------------------------------------

--
-- Structure de la table `position`
--

DROP TABLE IF EXISTS `position`;
CREATE TABLE IF NOT EXISTS `position` (
  `Id` int NOT NULL,
  `Label` varchar(100) DEFAULT NULL,
  `Number` int DEFAULT NULL,
  `RowId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Position_Row` (`RowId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `position`
--

INSERT INTO `position` (`Id`, `Label`, `Number`, `RowId`) VALUES
(1, 'Position A1', 1, 1),
(2, 'Position A2', 2, 1),
(3, 'Position A3', 3, 1),
(4, 'Position B1', 1, 2),
(5, 'Position C1', 1, 3),
(6, 'Position D1', 1, 4);

-- --------------------------------------------------------

--
-- Structure de la table `rate`
--

DROP TABLE IF EXISTS `rate`;
CREATE TABLE IF NOT EXISTS `rate` (
  `Id` int NOT NULL,
  `Code` varchar(50) DEFAULT NULL,
  `Label` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `rate`
--

INSERT INTO `rate` (`Id`, `Code`, `Label`) VALUES
(1, 'RATE001', 'Taux standard'),
(2, 'RATE002', 'Taux réduit'),
(3, 'RATE003', 'Taux premium');

-- --------------------------------------------------------

--
-- Structure de la table `rateperiod`
--

DROP TABLE IF EXISTS `rateperiod`;
CREATE TABLE IF NOT EXISTS `rateperiod` (
  `Id` int NOT NULL,
  `ToDate` datetime DEFAULT NULL,
  `RateId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_RatePeriod_Rate` (`RateId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `rateperiod`
--

INSERT INTO `rateperiod` (`Id`, `ToDate`, `RateId`) VALUES
(1, '2026-12-31 00:00:00', 1),
(2, '2026-12-31 00:00:00', 2),
(3, '2026-12-31 00:00:00', 3);

-- --------------------------------------------------------

--
-- Structure de la table `raterangeperiod`
--

DROP TABLE IF EXISTS `raterangeperiod`;
CREATE TABLE IF NOT EXISTS `raterangeperiod` (
  `Id` int NOT NULL,
  `StartValue` int DEFAULT NULL,
  `EndValue` int DEFAULT NULL,
  `Rate` decimal(10,0) DEFAULT NULL,
  `RatePeriodId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_RateRangePeriod_RatePeriod` (`RatePeriodId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `raterangeperiod`
--

INSERT INTO `raterangeperiod` (`Id`, `StartValue`, `EndValue`, `Rate`, `RatePeriodId`) VALUES
(1, 0, 20, '50', 1),
(2, 21, 50, '45', 1),
(3, 51, 100, '40', 1),
(4, 0, 20, '35', 2),
(5, 21, 50, '32', 2),
(6, 0, 20, '75', 3);

-- --------------------------------------------------------

--
-- Structure de la table `row`
--

DROP TABLE IF EXISTS `row`;
CREATE TABLE IF NOT EXISTS `row` (
  `Id` int NOT NULL,
  `Code` varchar(100) DEFAULT NULL,
  `AreaId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Row_Area` (`AreaId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `row`
--

INSERT INTO `row` (`Id`, `Code`, `AreaId`) VALUES
(1, 'ROW_01', 1),
(2, 'ROW_02', 1),
(3, 'ROW_03', 2),
(4, 'ROW_04', 3),
(5, 'ROW_05', 4);

-- --------------------------------------------------------

--
-- Structure de la table `subscription`
--

DROP TABLE IF EXISTS `subscription`;
CREATE TABLE IF NOT EXISTS `subscription` (
  `Id` int NOT NULL,
  `Code` varchar(50) DEFAULT NULL,
  `FromDate` datetime DEFAULT NULL,
  `Todate` datetime DEFAULT NULL,
  `AppliesTo` varchar(2) DEFAULT NULL,
  `ThirdPartyId` int DEFAULT NULL,
  `RateId` int DEFAULT NULL,
  `ContractId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_Subscription_Contract` (`ContractId`),
  KEY `FK_Subscription_Rate` (`RateId`),
  KEY `FK_Subscription_ThirdParty` (`ThirdPartyId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `subscription`
--

INSERT INTO `subscription` (`Id`, `Code`, `FromDate`, `Todate`, `AppliesTo`, `ThirdPartyId`, `RateId`, `ContractId`) VALUES
(1, 'SUB001', '2025-01-01 00:00:00', '2025-12-31 00:00:00', 'CL', 10, 1, 1),
(2, 'SUB002', '2025-01-01 00:00:00', '2025-12-31 00:00:00', 'CL', 11, 1, 1),
(3, 'SUB003', '2025-01-01 00:00:00', '2025-12-31 00:00:00', 'CL', 12, 2, 2),
(4, 'SUB004', '2025-01-01 00:00:00', '2025-12-31 00:00:00', 'TR', 20, 3, 3);

-- --------------------------------------------------------

--
-- Structure de la table `taxcodes`
--

DROP TABLE IF EXISTS `taxcodes`;
CREATE TABLE IF NOT EXISTS `taxcodes` (
  `Id` int NOT NULL,
  `Code` varchar(50) DEFAULT NULL,
  `Label` varchar(100) DEFAULT NULL,
  `TaxValue` decimal(10,0) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `taxcodes`
--

INSERT INTO `taxcodes` (`Id`, `Code`, `Label`, `TaxValue`) VALUES
(1, 'TVA20', 'TVA 20%', '0'),
(2, 'TVA10', 'TVA 10%', '0'),
(3, 'TVA5', 'TVA 5%', '0'),
(4, 'EXONERE', 'Exonéré', '0');

-- --------------------------------------------------------

--
-- Structure de la table `terminal`
--

DROP TABLE IF EXISTS `terminal`;
CREATE TABLE IF NOT EXISTS `terminal` (
  `Id` int NOT NULL,
  `Code` varchar(1000) DEFAULT NULL,
  `Label` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `terminal`
--

INSERT INTO `terminal` (`Id`, `Code`, `Label`) VALUES
(1, 'TICTC', 'Terminal Intercontinental Tangier Container'),
(2, 'ATLCIVP', 'Atlantic Container Terminal Casablanca');

-- --------------------------------------------------------

--
-- Structure de la table `thirdparty`
--

DROP TABLE IF EXISTS `thirdparty`;
CREATE TABLE IF NOT EXISTS `thirdparty` (
  `Id` int NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `Label` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `thirdparty`
--

INSERT INTO `thirdparty` (`Id`, `code`, `Label`) VALUES
(1, 'MSC', 'Mediterranean Shipping Company'),
(2, 'MAERSK', 'Maersk Line'),
(3, 'CMA', 'CMA CGM'),
(4, 'HAPAG', 'Hapag-Lloyd'),
(10, 'CLI001', 'KARIMEX'),
(11, 'CLI002', 'IMPORT EXPORT SARL'),
(12, 'CLI003', 'COMMERCE INTERNATIONAL'),
(13, 'CLI004', 'TRADE SOLUTIONS'),
(20, 'TRA001', 'TRANSITAIRE EXPRESS'),
(21, 'TRA002', 'WORLD LOGISTICS'),
(22, 'TRA003', 'CARGO SERVICES'),
(30, 'COM001', 'COMMISSION CARGO'),
(31, 'COM002', 'CUSTOMS BROKER PLUS'),
(40, 'AGE001', 'AGENCE MARITIME PORT'),
(41, 'AGE002', 'SHIPPING AGENCY INT');

-- --------------------------------------------------------

--
-- Structure de la table `thirdpartytype`
--

DROP TABLE IF EXISTS `thirdpartytype`;
CREATE TABLE IF NOT EXISTS `thirdpartytype` (
  `Id` int NOT NULL,
  `Label` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `thirdpartytype`
--

INSERT INTO `thirdpartytype` (`Id`, `Label`) VALUES
(1, 'Armateur'),
(2, 'Client'),
(3, 'Expéditeur'),
(4, 'Transitaire'),
(5, 'Commissionnaire'),
(6, 'Agence');

-- --------------------------------------------------------

--
-- Structure de la table `thirdparty_thirdpartytype`
--

DROP TABLE IF EXISTS `thirdparty_thirdpartytype`;
CREATE TABLE IF NOT EXISTS `thirdparty_thirdpartytype` (
  `ThirdParty_Id` int NOT NULL,
  `ThirdPartyType_Id` int NOT NULL,
  PRIMARY KEY (`ThirdParty_Id`,`ThirdPartyType_Id`),
  KEY `ThirdPartyType_Id` (`ThirdPartyType_Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `thirdparty_thirdpartytype`
--

INSERT INTO `thirdparty_thirdpartytype` (`ThirdParty_Id`, `ThirdPartyType_Id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(10, 2),
(11, 2),
(12, 2),
(13, 2),
(20, 4),
(21, 4),
(22, 4),
(30, 5),
(31, 5),
(40, 6),
(41, 6);

-- --------------------------------------------------------

--
-- Structure de la table `yarditemcode`
--

DROP TABLE IF EXISTS `yarditemcode`;
CREATE TABLE IF NOT EXISTS `yarditemcode` (
  `Id` int NOT NULL,
  `Label` varchar(4) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `yarditemcode`
--

INSERT INTO `yarditemcode` (`Id`, `Label`) VALUES
(1, 'CONT'),
(2, 'PALL'),
(3, 'CRAT'),
(4, 'BULK');

-- --------------------------------------------------------

--
-- Structure de la table `yarditemtype`
--

DROP TABLE IF EXISTS `yarditemtype`;
CREATE TABLE IF NOT EXISTS `yarditemtype` (
  `Id` int NOT NULL,
  `Label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `yarditemtype`
--

INSERT INTO `yarditemtype` (`Id`, `Label`) VALUES
(1, 'Conteneur 20 pieds'),
(2, 'Conteneur 40 pieds'),
(3, 'Palette EUR'),
(4, 'Colis'),
(5, 'Vrac');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
