<?php
/**
 * Script d'exécution de la correction GenerateProforma
 * 
 * MODIFICATION: Ajouter BillingDate lors de la création de la facture
 */

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "ies";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("❌ Erreur de connexion: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "═══════════════════════════════════════════════════════════════\n";
echo "CORRECTION: GenerateProforma - Stocker BillingDate\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// DROP la procédure
echo "⏳ Suppression de l'ancienne procédure...\n";
if ($conn->query("DROP PROCEDURE IF EXISTS `GenerateProforma`")) {
    echo "✅ Ancienne procédure supprimée\n\n";
} else {
    echo "⚠️  " . $conn->error . "\n\n";
}

// CREATE la nouvelle procédure
echo "⏳ Création de la nouvelle procédure...\n";

$create_sql = "CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateProforma`(
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
    AND NOT EXISTS (
        SELECT 1 FROM invoiceitem ii 
        WHERE ii.EventId = e.Id
    );
    
    IF v_LineCount > 0 THEN
        SELECT DISTINCT bl.ConsigneeId
        INTO v_BilledThirdPartyId
        FROM blitem_jobfile bjf
        INNER JOIN blitem bi ON bjf.BLItem_Id = bi.Id
        INNER JOIN bl ON bi.BLId = bl.Id
        WHERE bjf.JobFile_Id = p_JobFileId
        LIMIT 1;
        
        INSERT INTO invoice (BilledThirdPartyId, StatusId, BillingDate, Deleted)
        VALUES (v_BilledThirdPartyId, 1, p_BillingDate, 0);
        
        SET v_InvoiceId = LAST_INSERT_ID();
        
        INSERT INTO invoiceitem (InvoiceId, JobFileId, EventId, SubscriptionId, RateRangePeriodId, Quantity, Amount, CalculatedTax)
        SELECT 
            v_InvoiceId,
            e.JobFileId,
            e.Id,
            s.Id,
            rpr.Id,
            DATEDIFF(p_BillingDate, e.EventDate) + 1 as quantity,
            CASE 
                WHEN (SELECT COUNT(*) FROM raterangeperiod WHERE RatePeriodId = rp.Id) = 1
                THEN rpr.Rate
                ELSE 
                    CASE 
                        WHEN (DATEDIFF(p_BillingDate, e.EventDate) + 1) >= rpr.EndValue
                        THEN (rpr.EndValue - rpr.StartValue + 1) * rpr.Rate
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
        
        SELECT 
            COALESCE(SUM(Amount), 0),
            COALESCE(SUM(CalculatedTax), 0)
        INTO v_SubTotalAmount, v_TotalTaxAmount
        FROM invoiceitem
        WHERE InvoiceId = v_InvoiceId;
        
        SET v_TotalAmount = v_SubTotalAmount + v_TotalTaxAmount;
        
        UPDATE invoice
        SET 
            SubTotalAmount = v_SubTotalAmount,
            TotalTaxAmount = v_TotalTaxAmount,
            TotalAmount = v_TotalAmount
        WHERE Id = v_InvoiceId;
        
        SELECT v_InvoiceId AS InvoiceId, v_SubTotalAmount AS SubTotalAmount, v_TotalTaxAmount AS TotalTaxAmount, v_TotalAmount AS TotalAmount;
    ELSE
        SELECT NULL AS InvoiceId, 0 AS SubTotalAmount, 0 AS TotalTaxAmount, 0 AS TotalAmount;
    END IF;
END";

if ($conn->query($create_sql)) {
    echo "✅ Nouvelle procédure créée avec succès!\n\n";
    
    // Vérifier
    $check_sql = "SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES 
                  WHERE ROUTINE_SCHEMA = 'ies' AND ROUTINE_NAME = 'GenerateProforma' 
                  AND ROUTINE_TYPE = 'PROCEDURE'";
    
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Vérification: La procédure GenerateProforma est bien créée\n";
    } else {
        echo "❌ Vérification échouée\n";
    }
} else {
    echo "❌ Erreur: " . $conn->error . "\n";
}

$conn->close();

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Correction terminée avec succès!\n";
echo "═══════════════════════════════════════════════════════════════\n";
?>
