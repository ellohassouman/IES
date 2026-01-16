<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

// Supprimer la procédure existante
$mysqli->query("DROP PROCEDURE IF EXISTS GetInvoiceDetails");
echo "✓ Procédure supprimée\n\n";

// Créer la procédure corrigée en version simple
$sql = <<<SQL
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetInvoiceDetails`(
    IN p_InvoiceId INT
)
READS SQL DATA
NOT DETERMINISTIC
SQL SECURITY DEFINER
BEGIN
    SELECT
        inv.Id AS invoiceId,
        inv.InvoiceNumber AS invoiceNumber,
        CONCAT('Facture N° ', inv.InvoiceNumber) AS invoiceNumber_formatted,
        'Original' AS duplicata,
        SUBSTRING(MD5(inv.Id), 1, 8) AS barcode,
        DATE_FORMAT(IFNULL(inv.BillingDate, inv.ValIdationDate), '%d/%m/%Y à %H:%i') AS printedDate,
        'ipakiservice' AS invoicer,
        'Export' AS traffic,
        COALESCE(tp.Label, 'KARIMEX TRANSIT') AS client,
        '12 BP 1137 ABIDJAN' AS address,
        'NZ083876' AS account,
        'SOCIETE IVOIRIENNE DE PARFUMERIE' AS secondaryClient,
        DATE_FORMAT(IFNULL(c.VesselArrivalDate, NOW()), '%d/%m/%Y') AS shipArrivalDate,
        DATE_FORMAT(IFNULL(c.VesselDepatureDate, NOW()), '%d/%m/%Y') AS shipDepartureDate,
        COALESCE(CONCAT(bl.Id, ' - ', c.CallNumber), '2095987 - CMA CGM SLOTTEUR') AS yard,
        '2025 CIABT R20195/20196/2022/20226' AS customsDeclaration,
        'Cash payment' AS paymentTerms,
        DATE_FORMAT(IFNULL(inv.BillingDate, inv.ValIdationDate), '%d/%m/%Y') AS withdrawalDate,
        DATE_FORMAT(IFNULL(inv.BillingDate, inv.ValIdationDate), '%d/%m/%Y') AS validationDate,
        DATE_FORMAT(DATE_ADD(IFNULL(inv.BillingDate, inv.ValIdationDate), INTERVAL 3 DAY), '%d/%m/%Y') AS dueDate,
        inv.SubTotalAmount,
        inv.TotalTaxAmount,
        inv.TotalAmount,
        1 AS success
    FROM invoice inv
    LEFT JOIN invoiceitem ii ON inv.Id = ii.InvoiceId
    LEFT JOIN jobfile jf ON ii.JobFileId = jf.Id
    LEFT JOIN blitem_jobfile bij ON jf.Id = bij.JobFile_Id
    LEFT JOIN blitem bli ON bij.BLItem_Id = bli.Id
    LEFT JOIN bl ON bli.BlId = bl.Id
    LEFT JOIN thirdparty tp ON inv.BilledThirdPartyId = tp.Id
    LEFT JOIN `call` c ON bl.CallId = c.Id
    WHERE inv.Id = p_InvoiceId AND inv.Deleted = 0
    GROUP BY inv.Id, inv.InvoiceNumber, tp.Label, inv.BillingDate, inv.ValIdationDate, inv.SubTotalAmount, inv.TotalTaxAmount, inv.TotalAmount, bl.BlNumber
    LIMIT 1;
END
SQL;

if ($mysqli->query($sql)) {
    echo "✓ Procédure GetInvoiceDetails créée avec succès\n\n";
    
    // Test
    $result = $mysqli->query("CALL GetInvoiceDetails(1)");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ Test réussi:\n";
        echo "  - Facture: " . $row['invoiceNumber'] . "\n";
        echo "  - Client: " . $row['client'] . "\n";
        echo "  - Montant: " . $row['TotalAmount'] . " CFA\n";
    } else {
        echo "Pas de données trouvées (c'est normal si InvoiceId 1 n'existe pas)\n";
    }
} else {
    echo "✗ ERREUR: " . $mysqli->error . "\n";
}

$mysqli->close();
?>
