<?php
/**
 * Script d'exécution de la correction GetInvoicesPerBLNumber - Version Simple
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
echo "CORRECTION: GetInvoicesPerBLNumber - Éliminer les doublons\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// DROP la procédure
echo "⏳ Suppression de l'ancienne procédure...\n";
if ($conn->query("DROP PROCEDURE IF EXISTS `GetInvoicesPerBLNumber`")) {
    echo "✅ Ancienne procédure supprimée\n\n";
} else {
    echo "⚠️  " . $conn->error . "\n\n";
}

// CREATE la nouvelle procédure
echo "⏳ Création de la nouvelle procédure...\n";

$create_sql = "CREATE DEFINER=`root`@`localhost` PROCEDURE `GetInvoicesPerBLNumber` (
    IN `p_BlNumber` VARCHAR(100), 
    IN `p_CustomerUserId` INT
) 
BEGIN
    SELECT 
        inv.Id AS id,
        inv.InvoiceNumber AS invoiceNumber,
        'Invoice' AS invoiceType,
        COALESCE(tp.Label, '') AS client,
        DATE_FORMAT(IFNULL(inv.BillingDate, inv.ValIdationDate), '%d/%m/%Y') AS billingDate,
        DATE_FORMAT(IFNULL(inv.BillingDate, inv.ValIdationDate), '%d/%m/%Y') AS withdrawalDate,
        CONCAT(FORMAT(inv.TotalAmount, 2), ' XOF') AS total,
        'XOF' AS currencyCode,
        inv.Id AS filterId,
        'STI' AS journalType,
        bl.BlNumber AS blNumber,
        bl.Id AS blId,
        inv.StatusId AS statusId,
        COALESCE(invs.Label, '') AS statusLabel,
        CASE 
            WHEN p_CustomerUserId IS NOT NULL 
                 AND EXISTS (
                    SELECT 1 FROM Cart c
                    LEFT JOIN CartItem ci ON c.Id = ci.CartId
                    WHERE c.CustomerUserId = p_CustomerUserId 
                      AND c.Deleted = 0
                      AND ci.InvoiceId = inv.Id
                 ) THEN 1
            ELSE 0
        END AS isInCart,
        COALESCE(
            (
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'id', CAST(bli.Id AS CHAR),
                        'number', COALESCE(bli.Number, ''),
                        'type', CONCAT('[', COALESCE(bit.Label, ''), ']'),
                        'description', COALESCE(ci.NumberOfPackages, ''),
                        'isDraft', FALSE,
                        'dnPrintable', FALSE
                    )
                )
                FROM (
                    SELECT DISTINCT
                        bli.Id,
                        bli.Number,
                        bit.Label,
                        ci.NumberOfPackages
                    FROM invoiceitem ii
                    LEFT JOIN jobfile jf ON ii.JobFileId = jf.Id
                    LEFT JOIN blitem_jobfile bij ON jf.Id = bij.JobFile_Id
                    LEFT JOIN blitem bli ON bij.BLItem_Id = bli.Id
                    LEFT JOIN yarditemtype bit ON bli.ItemTypeId = bit.Id
                    LEFT JOIN commodityitem ci ON bli.Id = ci.BlItemId
                    WHERE ii.InvoiceId = inv.Id
                ) sub
                INNER JOIN blitem bli ON sub.Id = bli.Id
                LEFT JOIN yarditemtype bit ON bli.ItemTypeId = bit.Id
                LEFT JOIN commodityitem ci ON bli.Id = ci.BlItemId
            ),
            JSON_ARRAY()
        ) AS yardItems
    FROM invoice inv
    LEFT JOIN invoicestatus invs ON inv.StatusId = invs.Id
    LEFT JOIN thirdparty tp ON inv.BilledThirdPartyId = tp.Id
    LEFT JOIN invoiceitem ii ON inv.Id = ii.InvoiceId
    LEFT JOIN jobfile jf ON ii.JobFileId = jf.Id
    LEFT JOIN blitem_jobfile bij ON jf.Id = bij.JobFile_Id
    LEFT JOIN blitem bli ON bij.BLItem_Id = bli.Id
    LEFT JOIN bl ON bli.BlId = bl.Id
    WHERE bl.BlNumber = p_BlNumber AND inv.Deleted = 0
    GROUP BY inv.Id, inv.InvoiceNumber, tp.Label, inv.BillingDate, inv.ValIdationDate, inv.TotalAmount, bl.BlNumber, bl.Id, inv.StatusId, invs.Label
    ORDER BY IFNULL(inv.BillingDate, inv.ValIdationDate) DESC;
END";

if ($conn->query($create_sql)) {
    echo "✅ Nouvelle procédure créée avec succès!\n\n";
    
    // Vérifier
    $check_sql = "SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES 
                  WHERE ROUTINE_SCHEMA = 'ies' AND ROUTINE_NAME = 'GetInvoicesPerBLNumber' 
                  AND ROUTINE_TYPE = 'PROCEDURE'";
    
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Vérification: La procédure GetInvoicesPerBLNumber est bien créée\n";
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
