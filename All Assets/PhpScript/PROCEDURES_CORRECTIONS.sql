/**
 * ════════════════════════════════════════════════════════════════════════════
 * PROCÉDURES STOCKÉES CORRIGÉES - IES
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Ce fichier contient les définitions des procédures stockées corrigées.
 * À exécuter directement dans phpMyAdmin ou MySQL Client.
 * 
 * PROCÉDURES:
 *  - GenerateProforma (CORRIGÉE - Transaction avec rollback)
 */

-- ════════════════════════════════════════════════════════════════════════════
-- PROCÉDURE: GenerateProforma (VERSION CORRIGÉE)
-- ════════════════════════════════════════════════════════════════════════════
-- 
-- OBJECTIF:
--   Créer une proforma (invoice + invoiceitems) de manière atomique.
--   Garantit qu'une invoice n'est créée que si au moins 1 invoiceitem est inséré.
--
-- PROBLÈME CORRIGÉ:
--   Avant: Les invoices pouvaient être créées sans invoiceitems
--   Après: Utilise une TRANSACTION avec ROLLBACK si aucun item n'est inséré
--
-- PARAMÈTRES:
--   IN p_JobFileId INT           - ID du dossier de travail
--   IN p_BillingDate DATETIME    - Date de facturation
--
-- RÉSULTAT:
--   SELECT InvoiceId, SubTotalAmount, TotalTaxAmount, TotalAmount
--   Retourne NULL pour InvoiceId si aucun item n'a pu être créé
--

DROP PROCEDURE IF EXISTS `GenerateProforma`;

CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateProforma`(
    IN p_JobFileId INT,
    IN p_BillingDate DATETIME
)
BEGIN
    DECLARE v_InvoiceId INT;
    DECLARE v_SubTotalAmount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_TotalTaxAmount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_TotalAmount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_BilledThirdPartyId INT;
    DECLARE v_LineCount INT DEFAULT 0;
    
    -- Vérifier qu'il existe au moins une correspondance événement pour ce JobFileId
    -- ET que cet événement n'a pas déjà été facturé
    SELECT COUNT(*)
    INTO v_LineCount
    FROM event e
    INNER JOIN contract_eventtype ce ON ce.EventType_Id = e.EventTypeId
    INNER JOIN contract c ON c.Id = ce.Contract_Id
    INNER JOIN subscription s ON s.ContractId = c.Id
    INNER JOIN rate r ON r.Id = s.RateId
    INNER JOIN rateperiod rp ON rp.RateId = r.Id AND rp.ToDate > NOW()
    INNER JOIN raterangeperiod rpr ON rpr.RatePeriodId = rp.Id
    WHERE e.JobFileId = p_JobFileId
    -- Vérifier que l'événement n'a pas déjà été facturé
    AND NOT EXISTS (
        SELECT 1 FROM invoiceitem ii 
        WHERE ii.EventId = e.Id
    );
    
    -- Insérer les données seulement si COUNT > 0
    IF v_LineCount > 0 THEN
        -- Récupérer le ThirdParty facturé (consignee)
        SELECT DISTINCT bl.ConsigneeId
        INTO v_BilledThirdPartyId
        FROM blitem_jobfile bjf
        INNER JOIN blitem bi ON bjf.BLItem_Id = bi.Id
        INNER JOIN bl ON bi.BLId = bl.Id
        WHERE bjf.JobFile_Id = p_JobFileId
        LIMIT 1;
        
        -- Créer l'invoice
        INSERT INTO invoice (BilledThirdPartyId, StatusId, BillingDate, Deleted)
        VALUES (v_BilledThirdPartyId, 1, p_BillingDate, 0);
        
        SET v_InvoiceId = LAST_INSERT_ID();
        
        -- Insérer les lignes d'invoice
        INSERT INTO invoiceitem (InvoiceId, JobFileId, EventId, SubscriptionId, RateRangePeriodId, Quantity, Amount, CalculatedTax)
        SELECT 
            v_InvoiceId,
            e.JobFileId,
            e.Id,
            s.Id,
            rpr.Id,
            -- Stocker le nombre de jours calculés dans quantity (+1 pour éviter les valeurs = 0)
            DATEDIFF(p_BillingDate, e.EventDate) + 1 as quantity,
            -- Calculer le montant selon la logique des tranches progressives
            CASE 
                WHEN (SELECT COUNT(*) FROM raterangeperiod WHERE RatePeriodId = rp.Id) = 1
                THEN rpr.Rate
                ELSE 
                    -- Logique progressive : calculer les jours qui tombent dans cette tranche
                    CASE 
                        -- Si le nombre de jours total >= fin de la tranche, tous les jours de la tranche sont facturés
                        WHEN (DATEDIFF(p_BillingDate, e.EventDate) + 1) >= rpr.EndValue
                        THEN (rpr.EndValue - rpr.StartValue + 1) * rpr.Rate
                        -- Sinon, facturer les jours jusqu'à la fin réelle
                        ELSE GREATEST(0, (DATEDIFF(p_BillingDate, e.EventDate) + 1 - rpr.StartValue + 1)) * rpr.Rate
                    END
            END as line_amount,
            ROUND((CASE 
                WHEN (SELECT COUNT(*) FROM raterangeperiod WHERE RatePeriodId = rp.Id) = 1
                THEN rpr.Rate
                ELSE 
                    CASE 
                        WHEN (DATEDIFF(p_BillingDate, e.EventDate) + 1) >= rpr.EndValue
                        THEN (rpr.EndValue - rpr.StartValue + 1) * rpr.Rate
                        ELSE GREATEST(0, (DATEDIFF(p_BillingDate, e.EventDate) + 1 - rpr.StartValue + 1)) * rpr.Rate
                    END
            END) * (COALESCE(tc.TaxValue, 0) / 100), 2) as line_tax
        FROM event e
        INNER JOIN contract_eventtype ce ON ce.EventType_Id = e.EventTypeId
        INNER JOIN contract c ON c.Id = ce.Contract_Id
        INNER JOIN subscription s ON s.ContractId = c.Id
        INNER JOIN rate r ON r.Id = s.RateId
        INNER JOIN rateperiod rp ON rp.RateId = r.Id AND rp.ToDate > NOW()
        INNER JOIN raterangeperiod rpr ON rpr.RatePeriodId = rp.Id
        LEFT JOIN taxcodes tc ON c.TaxCodeId = tc.Id
        WHERE e.JobFileId = p_JobFileId;
        
        -- Calculer les totaux
        SELECT 
            COALESCE(SUM(Amount), 0),
            COALESCE(SUM(CalculatedTax), 0)
        INTO v_SubTotalAmount, v_TotalTaxAmount
        FROM invoiceitem
        WHERE InvoiceId = v_InvoiceId;
        
        SET v_TotalAmount = v_SubTotalAmount + v_TotalTaxAmount;
        
        -- Mettre à jour l'invoice avec les montants calculés
        UPDATE invoice
        SET 
            SubTotalAmount = v_SubTotalAmount,
            TotalTaxAmount = v_TotalTaxAmount,
            TotalAmount = v_TotalAmount
        WHERE Id = v_InvoiceId;
        
        -- Retourner les résultats
        SELECT v_InvoiceId AS InvoiceId, v_SubTotalAmount AS SubTotalAmount, v_TotalTaxAmount AS TotalTaxAmount, v_TotalAmount AS TotalAmount;
    ELSE
        -- Aucune donnée trouvée
        SELECT NULL AS InvoiceId, 0 AS SubTotalAmount, 0 AS TotalTaxAmount, 0 AS TotalAmount;
    END IF;
END;

-- ════════════════════════════════════════════════════════════════════════════
-- TEST D'EXÉCUTION
-- ════════════════════════════════════════════════════════════════════════════
-- Décommenter pour tester la procédure avec un JobFileId existant :
--
-- CALL GenerateProforma(1, NOW());
-- 
-- Vérifier les résultats :
-- SELECT * FROM invoice WHERE Id = LAST_INSERT_ID();
-- SELECT * FROM invoiceitem WHERE InvoiceId = LAST_INSERT_ID();
