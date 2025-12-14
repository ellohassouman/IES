-- ============================================================================
-- DONNÉES FICTIVES POUR LA BASE DE DONNÉES IES
-- Basées sur les données réelles du frontend
-- ============================================================================

-- ============================================================================
-- 1. DONNÉES DE BASE
-- ============================================================================

-- Familles d'événements
INSERT INTO `Family` (`Id`, `Label`) VALUES 
(1, 'Arrivée'),
(2, 'Déchargement'),
(3, 'Entreposage'),
(4, 'Service'),
(5, 'Enlèvement'),
(6, 'Livraison');

-- Types d'événements
INSERT INTO `EventType` (`Id`, `Code`, `Label`, `FamilyId`) VALUES 
(1, 'ARR', 'Arrivée au terminal', 1),
(2, 'DCH', 'Déchargement effectué', 2),
(3, 'ENT', 'Entreposage', 3),
(4, 'SRV', 'Service effectué', 4),
(5, 'ENL', 'Enlèvement prévu', 5),
(6, 'LIV', 'Livraison effectuée', 6);

-- Types de tiers (clients, expéditeurs, destinataires, etc.)
INSERT INTO `ThirdPartyType` (`Id`, `Label`) VALUES 
(1, 'Armateur'),
(2, 'Client'),
(3, 'Expéditeur'),
(4, 'Transitaire'),
(5, 'Commissionnaire'),
(6, 'Agence');

-- ============================================================================
-- 2. TIERS (CLIENTS, ARMATEURS, ETC.)
-- ============================================================================

-- Armateurs (Shipping Lines)
INSERT INTO `ThirdParty` (`Id`, `code`, `Label`) VALUES 
(1, 'MSC', 'Mediterranean Shipping Company'),
(2, 'MAERSK', 'Maersk Line'),
(3, 'CMA', 'CMA CGM'),
(4, 'HAPAG', 'Hapag-Lloyd');

-- Clients
INSERT INTO `ThirdParty` (`Id`, `code`, `Label`) VALUES 
(10, 'CLI001', 'KARIMEX'),
(11, 'CLI002', 'IMPORT EXPORT SARL'),
(12, 'CLI003', 'COMMERCE INTERNATIONAL'),
(13, 'CLI004', 'TRADE SOLUTIONS');

-- Transitaires
INSERT INTO `ThirdParty` (`Id`, `code`, `Label`) VALUES 
(20, 'TRA001', 'TRANSITAIRE EXPRESS'),
(21, 'TRA002', 'WORLD LOGISTICS'),
(22, 'TRA003', 'CARGO SERVICES');

-- Commissionnaires
INSERT INTO `ThirdParty` (`Id`, `code`, `Label`) VALUES 
(30, 'COM001', 'COMMISSION CARGO'),
(31, 'COM002', 'CUSTOMS BROKER PLUS');

-- Agences
INSERT INTO `ThirdParty` (`Id`, `code`, `Label`) VALUES 
(40, 'AGE001', 'AGENCE MARITIME PORT'),
(41, 'AGE002', 'SHIPPING AGENCY INT');

-- ============================================================================
-- 3. CODES ET TYPES D'ARTICLES DE COUR
-- ============================================================================

INSERT INTO `YardItemCode` (`Id`, `Label`) VALUES 
(1, 'CONT'),
(2, 'PALLET'),
(3, 'CRATE'),
(4, 'BULK');

INSERT INTO `YardItemType` (`Id`, `Label`) VALUES 
(1, 'Conteneur 20 pieds'),
(2, 'Conteneur 40 pieds'),
(3, 'Palette EUR'),
(4, 'Colis'),
(5, 'Vrac');

-- ============================================================================
-- 4. COMMODITÉS
-- ============================================================================

INSERT INTO `Commodity` (`Id`, `Label`) VALUES 
(1, 'Électronique'),
(2, 'Vêtements et textiles'),
(3, 'Produits chimiques'),
(4, 'Matériel agricole'),
(5, 'Mobilier'),
(6, 'Équipements industriels');

-- ============================================================================
-- 5. CODES FISCAUX ET TAXES
-- ============================================================================

INSERT INTO `TaxCodes` (`Id`, `Code`, `Label`, `TaxValue`) VALUES 
(1, 'TVA20', 'TVA 20%', 0.20),
(2, 'TVA10', 'TVA 10%', 0.10),
(3, 'TVA5', 'TVA 5%', 0.05),
(4, 'EXONERE', 'Exonéré', 0.00);

-- ============================================================================
-- 6. CONTRATS
-- ============================================================================

INSERT INTO `Contract` (`Id`, `Code`, `InvoiceLabel`, `TaxCodeId`) VALUES 
(1, 'CTR001', 'Contrat Standard', 1),
(2, 'CTR002', 'Contrat Premium', 1),
(3, 'CTR003', 'Contrat Special', 2);

-- ============================================================================
-- 7. TAUX ET PÉRIODES
-- ============================================================================

INSERT INTO `Rate` (`Id`, `Code`, `Label`) VALUES 
(1, 'RATE001', 'Taux standard'),
(2, 'RATE002', 'Taux réduit'),
(3, 'RATE003', 'Taux premium');

INSERT INTO `RatePeriod` (`Id`, `ToDate`, `RateId`) VALUES 
(1, '2026-12-31', 1),
(2, '2026-12-31', 2),
(3, '2026-12-31', 3);

INSERT INTO `RateRangePeriod` (`Id`, `StartValue`, `EndValue`, `Rate`, `RatePeriodId`) VALUES 
(1, 0, 20, 50.00, 1),
(2, 21, 50, 45.00, 1),
(3, 51, 100, 40.00, 1),
(4, 0, 20, 35.00, 2),
(5, 21, 50, 32.00, 2),
(6, 0, 20, 75.00, 3);

-- ============================================================================
-- 8. TYPES DE PAIEMENTS
-- ============================================================================

INSERT INTO `PaymentType` (`Id`, `Label`) VALUES 
(1, 'Espèces'),
(2, 'Chèque'),
(3, 'Virement bancaire'),
(4, 'Carte de crédit'),
(5, 'Crédit client');

-- ============================================================================
-- 9. TYPES DE DOCUMENTS
-- ============================================================================

INSERT INTO `DocumentType` (`Id`, `Label`) VALUES 
(1, 'Bill of Lading (B/L)'),
(2, 'Manifest'),
(3, 'Packing List'),
(4, 'Facture'),
(5, 'Bon de livraison'),
(6, 'Certificat'),
(7, 'Assurance');

-- ============================================================================
-- 10. STATUTS ET TYPES UTILISATEURS
-- ============================================================================

INSERT INTO `CustomerUsersStatus` (`Id`, `Label`) VALUES 
(1, 'Actif'),
(2, 'Inactif'),
(3, 'Suspendu'),
(4, 'En attente');

INSERT INTO `CustomerUsersType` (`Id`, `Label`) VALUES 
(1, 'Administrateur'),
(2, 'Client Standard'),
(3, 'Client Premium'),
(4, 'Partenaire');

-- ============================================================================
-- 11. UTILISATEURS CLIENTS
-- ============================================================================

INSERT INTO `CustomerUsers` (`Id`, `UserName`, `PasswordHash`, `EmailConfirmed`, `FirstName`, `LastName`, `CompanyName`, `CompanyAddress`, `PhoneNumber`, `CustomerUsersStatusId`, `CustomerUsersTypeId`) VALUES 
(1, 'karimex@user.com', 'hashed_password_1', 1, 'Ahmed', 'Hassan', 'KARIMEX', '123 Rue du Commerce, Casablanca', '+212661234567', 1, 2),
(2, 'import@company.com', 'hashed_password_2', 1, 'Mohamed', 'Bennani', 'IMPORT EXPORT SARL', '456 Avenue Hassan II, Rabat', '+212662345678', 1, 2),
(3, 'trade@intl.com', 'hashed_password_3', 1, 'Fatima', 'Alaoui', 'COMMERCE INTERNATIONAL', '789 Boulevard Zerktouni, Casablanca', '+212663456789', 1, 3);

-- Associations utilisateurs-tiers
INSERT INTO `CustomerUsers_ThirdParty` (`CustomerUsers_Id`, `ThirdParty_Id`) VALUES 
(1, 10),
(2, 11),
(3, 12);

-- ============================================================================
-- 12. TERMINAL, ZONES ET POSITIONS
-- ============================================================================

-- Terminal
INSERT INTO `Terminal` (`Id`, `Code`, `Label`) VALUES 
(1, 'TICTC', 'Terminal Intercontinental Tangier Container'),
(2, 'ATLCIVP', 'Atlantic Container Terminal Casablanca');

-- Zones/Aires
INSERT INTO `Area` (`Id`, `Code`, `TerminalId`) VALUES 
(1, 'ZONE_A', 1),
(2, 'ZONE_B', 1),
(3, 'ZONE_C', 2),
(4, 'ZONE_D', 2);

-- Rangées
INSERT INTO `Row` (`Id`, `Code`, `AreaId`) VALUES 
(1, 'ROW_01', 1),
(2, 'ROW_02', 1),
(3, 'ROW_03', 2),
(4, 'ROW_04', 3),
(5, 'ROW_05', 4);

-- Positions
INSERT INTO `Position` (`Id`, `Label`, `Number`, `RowId`) VALUES 
(1, 'Position A1', 1, 1),
(2, 'Position A2', 2, 1),
(3, 'Position A3', 3, 1),
(4, 'Position B1', 1, 2),
(5, 'Position C1', 1, 3),
(6, 'Position D1', 1, 4);

-- ============================================================================
-- 13. APPELS (CALLS) - ARRIVÉES DE NAVIRES
-- ============================================================================

INSERT INTO `Call` (`Id`, `CallNumber`, `VesselArrivalDate`, `VesselDepatureDate`, `ThirdPartyId`) VALUES 
(1, 'CALL_2025_001', '2025-11-10 08:00:00', '2025-11-25 18:00:00', 1),
(2, 'CALL_2025_002', '2025-11-15 10:30:00', '2025-11-30 20:00:00', 2),
(3, 'CALL_2025_003', '2025-11-18 14:00:00', '2025-12-05 22:00:00', 3);

-- ============================================================================
-- 14. BILLS OF LADING (B/L)
-- ============================================================================

INSERT INTO `BL` (`Id`, `BlNumber`, `ConsigneeId`, `RelatedCustomerId`, `CallId`) VALUES 
(1, 'MEDUDM992142', 10, 11, 1),
(2, 'EBKG08737243', 11, 12, 2),
(3, 'AEV0238293', 12, 10, 2),
(4, 'AEV0239463', 13, 11, 3);

-- ============================================================================
-- 15. ARTICLES DE B/L
-- ============================================================================

INSERT INTO `BLItem` (`Id`, `Number`, `Weight`, `Volume`, `BlId`, `ItemTypeId`, `ItemCodeId`) VALUES 
(1, 'BL001-001', 18000.00, 33.50, 1, 2, 1),
(2, 'BL001-002', 16000.00, 33.50, 1, 2, 1),
(3, 'BL002-001', 15000.00, 33.50, 2, 2, 1),
(4, 'BL002-002', 8000.00, 16.75, 2, 1, 1),
(5, 'BL003-001', 2500.00, 2.50, 3, 3, 2),
(6, 'BL003-002', 3000.00, 3.00, 3, 3, 2),
(7, 'BL004-001', 12000.00, 25.00, 4, 2, 1),
(8, 'BL004-002', 500.00, 1.20, 4, 4, 4);

-- ============================================================================
-- 16. COMMODITÉS D'ARTICLES
-- ============================================================================

INSERT INTO `CommodityItem` (`Id`, `Weight`, `NumberOfPackages`, `CommodityId`, `BlItemId`) VALUES 
(1, 18000.00, 85, 1, 1),
(2, 16000.00, 75, 2, 2),
(3, 15000.00, 70, 3, 3),
(4, 8000.00, 40, 4, 4),
(5, 2500.00, 50, 5, 5),
(6, 3000.00, 60, 5, 6),
(7, 12000.00, 60, 6, 7),
(8, 500.00, 250, 1, 8);

-- ============================================================================
-- 17. JOB FILES
-- ============================================================================

INSERT INTO `JobFile` (`Id`, `DateOpen`, `DateClose`, `ShippingLineId`, `PositionId`) VALUES 
(1, '2025-11-10 09:00:00', NULL, 1, 1),
(2, '2025-11-15 11:00:00', NULL, 2, 2),
(3, '2025-11-18 15:00:00', NULL, 3, 4),
(4, '2025-11-20 10:00:00', '2025-11-22 18:00:00', 1, 5);

-- ============================================================================
-- 18. RELATION BLItem - JobFile
-- ============================================================================

INSERT INTO `BLItem_JobFile` (`BLItem_Id`, `JobFile_Id`) VALUES 
(1, 1),
(2, 1),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 3),
(8, 3);

-- ============================================================================
-- 19. DOCUMENTS
-- ============================================================================

INSERT INTO `Document` (`Id`, `Text`, `Date`, `BlId`, `JobFileId`, `DocumentTypeId`) VALUES 
(1, 'Original B/L - MEDUDM992142', '2025-11-10 09:30:00', 1, 1, 1),
(2, 'Manifest de chargement', '2025-11-10 10:00:00', 1, 1, 2),
(3, 'Packing List', '2025-11-10 10:30:00', 1, 1, 3),
(4, 'Original B/L - EBKG08737243', '2025-11-15 11:30:00', 2, 2, 1),
(5, 'Certificat de sécurité', '2025-11-15 12:00:00', 2, 2, 6);

-- ============================================================================
-- 20. ÉVÉNEMENTS
-- ============================================================================

INSERT INTO `Event` (`Id`, `EventDate`, `JobFileId`, `EventTypeId`) VALUES 
(1, '2025-11-10 09:00:00', 1, 1),
(2, '2025-11-10 14:00:00', 1, 2),
(3, '2025-11-15 11:00:00', 2, 1),
(4, '2025-11-15 16:00:00', 2, 2),
(5, '2025-11-18 15:00:00', 3, 1),
(6, '2025-11-20 10:00:00', 4, 5),
(7, '2025-11-22 17:00:00', 4, 6);

-- ============================================================================
-- 21. SOUSCRIPTIONS (SUBSCRIPTIONS)
-- ============================================================================

INSERT INTO `Subscription` (`Id`, `Code`, `FromDate`, `Todate`, `AppliesTo`, `ThirdPartyId`, `RateId`, `ContractId`) VALUES 
(1, 'SUB001', '2025-01-01', '2025-12-31', 'CL', 10, 1, 1),
(2, 'SUB002', '2025-01-01', '2025-12-31', 'CL', 11, 1, 1),
(3, 'SUB003', '2025-01-01', '2025-12-31', 'CL', 12, 2, 2),
(4, 'SUB004', '2025-01-01', '2025-12-31', 'TR', 20, 3, 3);

-- ============================================================================
-- 22. FACTURES
-- ============================================================================

INSERT INTO `Invoice` (`Id`, `InvoiceNumber`, `ValIdationDate`, `SubTotalAmount`, `TotalTaxAmount`, `TotalAmount`, `BilledThirdPartyId`) VALUES 
(1, 251018767, '2025-11-12', 5000.00, 1000.00, 6000.00, 10),
(2, 251018791, '2025-11-12', 4500.00, 900.00, 5400.00, 10),
(3, 251083041, '2025-11-19', 3800.00, 760.00, 4560.00, 11),
(4, 251083042, '2025-11-20', 2500.00, 500.00, 3000.00, 12),
(5, 251083043, '2025-11-20', 6000.00, 1200.00, 7200.00, 13);

-- ============================================================================
-- 23. ARTICLES DE FACTURE
-- ============================================================================

INSERT INTO `InvoiceItem` (`Id`, `Quantity`, `Rate`, `Amount`, `CalculatedTax`, `InvoiceId`, `JobFileId`, `EventId`, `SubscriptionId`, `RateRangePeriodId`) VALUES 
(1, 10, 500.00, 5000.00, 1000.00, 1, 1, 1, 1, 1),
(2, 12, 375.00, 4500.00, 900.00, 2, 1, 2, 1, 2),
(3, 8, 475.00, 3800.00, 760.00, 3, 2, 3, 2, 1),
(4, 5, 500.00, 2500.00, 500.00, 4, 2, 4, 2, 1),
(5, 15, 400.00, 6000.00, 1200.00, 5, 3, 5, 3, 3);

-- ============================================================================
-- 24. PAIEMENTS
-- ============================================================================

INSERT INTO `Payment` (`Id`, `Number`, `Value`, `PaymentDate`, `PaymentTypeId`) VALUES 
(1, 1001, 6000.00, '2025-11-13', 3),
(2, 1002, 5400.00, '2025-11-14', 3),
(3, 1003, 4560.00, '2025-11-21', 2),
(4, 1004, 3000.00, '2025-11-21', 4);

-- ============================================================================
-- 25. RELATIONS PAIEMENTS - FACTURES
-- ============================================================================

INSERT INTO `Payment_Invoice` (`Payment_Id`, `Invoice_Id`) VALUES 
(1, 1),
(2, 2),
(3, 3),
(4, 4);

-- ============================================================================
-- ASSOCIATIONS DE TIERS ET TYPES
-- ============================================================================

INSERT INTO `ThirdParty_ThirdPartyType` (`ThirdParty_Id`, `ThirdPartyType_Id`) VALUES 
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

-- ============================================================================
-- RELATIONS CONTRATS - TYPES D'ÉVÉNEMENTS
-- ============================================================================

INSERT INTO `Contract_EventType` (`Contract_Id`, `EventType_Id`) VALUES 
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

-- ============================================================================
-- 21. HISTORIQUE DES RECHERCHES DE B/L PAR LES UTILISATEURS
-- ============================================================================

INSERT INTO `CustomerUserBLSearchHistory` (`Id`, `BlNumber`, `ShipName`, `ArrivalDate`, `ItemCount`, `UserId`, `SearchDate`) VALUES 
-- Utilisateur 1 (Ahmed Hassan - KARIMEX)
(1, 'AEV0238293', 'Mediterranean Dream', '2025-11-15 10:30:00', 1, 1, '2025-11-20 09:15:00'),
(2, 'MEDUDM992142', 'Atlantic Express', '2025-11-10 08:00:00', 1, 1, '2025-11-21 14:30:00'),
(3, 'AEV0239463', 'Pacific Voyager', '2025-11-18 14:00:00', 1, 1, '2025-11-22 11:45:00'),

-- Utilisateur 2 (Mohamed Bennani - IMPORT EXPORT SARL)
(4, 'EBKG08737243', 'Global Carrier', '2025-11-15 10:30:00', 1, 2, '2025-11-19 08:00:00'),
(5, 'AEV0238293', 'Mediterranean Dream', '2025-11-15 10:30:00', 1, 2, '2025-11-20 16:20:00'),
(6, 'MEDUDM992142', 'Atlantic Express', '2025-11-10 08:00:00', 1, 2, '2025-11-21 10:10:00'),

-- Utilisateur 3 (Fatima Alaoui - COMMERCE INTERNATIONAL)
(7, 'EBKG08737243', 'Global Carrier', '2025-11-15 10:30:00', 1, 3, '2025-11-18 13:30:00'),
(8, 'AEV0239463', 'Pacific Voyager', '2025-11-18 14:00:00', 1, 3, '2025-11-19 15:45:00'),
(9, 'AEV0238293', 'Mediterranean Dream', '2025-11-15 10:30:00', 1, 3, '2025-11-21 09:00:00'),
(10, 'MEDUDM992142', 'Atlantic Express', '2025-11-10 08:00:00', 1, 3, '2025-11-22 12:00:00');

-- ============================================================================
-- DONNÉES: Panier (Cart) et Articles du Panier (CartItem)
-- ============================================================================

-- Sample Cart Data
INSERT INTO `Cart` (`Id`, `CustomerUserId`, `CreatedDate`, `Deleted`) VALUES
(1, 1, NOW(), 0),
(2, 2, NOW(), 0);

-- Sample CartItem Data
INSERT INTO `CartItem` (`CartId`, `InvoiceId`, `InvoicePaidAmount`, `InvoiceNumber`, `BillingDate`) VALUES
(1, 3308200, 311645.51, '251018767', NOW()),
(1, 3308300, 150000.00, '251018768', NOW()),
(2, 3308400, 200000.00, '251018769', NOW());

-- ============================================================================
-- FIN DES DONNÉES FICTIVES
-- ============================================================================


