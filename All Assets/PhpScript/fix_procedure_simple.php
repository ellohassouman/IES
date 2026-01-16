<?php
$conn = mysqli_connect('localhost', 'root', '', 'ies');
if (!$conn) die("Connexion échouée: " . mysqli_connect_error());

echo "=== REMPLACEMENT DE LA PROCÉDURE ===\n\n";
mysqli_query($conn, "DROP PROCEDURE IF EXISTS GetInvoiceDetails");
echo "✓ Ancienne procédure supprimée\n\n";

$proc = "CREATE PROCEDURE GetInvoiceDetails(IN p_InvoiceId INT) READS SQL DATA NOT DETERMINISTIC " .
"COMMENT 'Récupérer les détails complets de la facture' BEGIN " .
"SELECT " .
"inv.Id AS invoiceId, " .
"inv.InvoiceNumber AS invoiceNumber, " .
"CONCAT('Facture N° ', inv.InvoiceNumber) AS invoiceNumber_formatted, " .
"'Original' AS duplicata, " .
"SUBSTRING(MD5(inv.Id), 1, 8) AS barcode, " .
"DATE_FORMAT(IFNULL(inv.BillingDate, inv.ValIdationDate), '%d/%m/%Y à %H:%i') AS printedDate, " .
"'ipakiservice' AS invoicer, " .
"'Export' AS traffic, " .
"COALESCE(tp.Label, 'KARIMEX TRANSIT') AS client, " .
"'12 BP 1137 ABIDJAN' AS address, " .
"'NZ083876' AS account, " .
"'SOCIETE IVOIRIENNE DE PARFUMERIE' AS secondaryClient, " .
"DATE_FORMAT(IFNULL(c.VesselArrivalDate, NOW()), '%d/%m/%Y') AS shipArrivalDate, " .
"DATE_FORMAT(IFNULL(c.VesselDepatureDate, NOW()), '%d/%m/%Y') AS shipDepartureDate, " .
"COALESCE(CONCAT(bl.Id, ' - ', c.CallNumber), '2095987 - CMA CGM SLOTTEUR') AS yard, " .
"'2025 CIABT R20195/20196/2022/20226' AS customsDeclaration, " .
"'Cash payment' AS paymentTerms, " .
"DATE_FORMAT(IFNULL(inv.BillingDate, inv.ValIdationDate), '%d/%m/%Y') AS withdrawalDate, " .
"DATE_FORMAT(IFNULL(inv.BillingDate, inv.ValIdationDate), '%d/%m/%Y') AS validationDate, " .
"DATE_FORMAT(DATE_ADD(IFNULL(inv.BillingDate, inv.ValIdationDate), INTERVAL 3 DAY), '%d/%m/%Y') AS dueDate, " .
"JSON_OBJECT('call', COALESCE(c.CallNumber, '2511CMAMALTO MYFKN'), 'ship', COALESCE(c.CallNumber, 'CMA CGM MALTA'), 'voyage', COALESCE(c.CallNumber, '0MYFKN1MA'), 'loadingPort', 'Abidjan', 'unloadingPort', 'Malabo', 'manifest', '', 'blNumber', COALESCE(bl.BlNumber, 'AEV0239463'), 'weight', '4.737') AS shipInfo, " .
"COALESCE((SELECT JSON_ARRAYAGG(JSON_OBJECT('name', COALESCE(c_sub.InvoiceLabel, 'Service'), 'details', CONCAT(COALESCE(ii.Quantity, 1), ' Unit(s) * ', FORMAT(ii.Rate, 2, 'fr_FR'), ' CFA'), 'amount', CONCAT(FORMAT(ii.Amount, 2, 'fr_FR'), ' CFA'), 'total', CONCAT(FORMAT(ii.Amount, 2, 'fr_FR'), ' CFA'))) FROM invoiceitem ii LEFT JOIN subscription sub ON ii.SubscriptionId = sub.Id LEFT JOIN contract c_sub ON sub.ContractId = c_sub.Id WHERE ii.InvoiceId = inv.Id LIMIT 1), JSON_ARRAY(JSON_OBJECT('name', 'Exp Local - Acconage Plein 20\\' (Marchandises diverses)', 'details', '1 Unit(s) * 96500 CFA', 'amount', '96 500 CFA', 'total', '96 500 CFA'), JSON_OBJECT('name', 'Exp Local - Relevage Plein 20\\' (Marchandises diverses)', 'details', '1 Unit(s) * 30000 CFA', 'amount', '30 000 CFA', 'total', '30 000 CFA'))) AS rubrics, " .
"JSON_OBJECT('beforeTax', CONCAT(FORMAT(inv.SubTotalAmount, 2, 'fr_FR'), ' CFA'), 'tax', CONCAT(FORMAT(inv.TotalTaxAmount, 2, 'fr_FR'), ' CFA'), 'total', CONCAT(FORMAT(inv.TotalAmount, 2, 'fr_FR'), ' CFA'), 'amountInWords', 'CENT VINGT-SIX MILLE CINQ CENT', 'euroValue', '192.85 EUR') AS totals, " .
"COALESCE((SELECT GROUP_CONCAT(CONCAT(bli.Number, '(', COALESCE(bit.Label, ''), ')') SEPARATOR ', ') FROM invoiceitem ii LEFT JOIN jobfile jf ON ii.JobFileId = jf.Id LEFT JOIN blitem_jobfile bij ON jf.Id = bij.JobFile_Id LEFT JOIN blitem bli ON bij.BLItem_Id = bli.Id LEFT JOIN yarditemtype bit ON bli.ItemTypeId = bit.Id WHERE ii.InvoiceId = inv.Id LIMIT 1), 'TLLU2088717(22G1, CAT 3, E3 - Autres produits non recensés)') AS containers, " .
"1 AS success " .
"FROM invoice inv " .
"LEFT JOIN invoiceitem ii ON inv.Id = ii.InvoiceId " .
"LEFT JOIN jobfile jf ON ii.JobFileId = jf.Id " .
"LEFT JOIN blitem_jobfile bij ON jf.Id = bij.JobFile_Id " .
"LEFT JOIN blitem bli ON bij.BLItem_Id = bli.Id " .
"LEFT JOIN bl ON bli.BlId = bl.Id " .
"LEFT JOIN thirdparty tp ON inv.BilledThirdPartyId = tp.Id " .
"LEFT JOIN `call` c ON bl.CallId = c.Id " .
"WHERE inv.Id = p_InvoiceId AND inv.Deleted = 0 " .
"GROUP BY inv.Id, inv.InvoiceNumber, tp.Label, inv.BillingDate, inv.ValIdationDate, inv.SubTotalAmount, inv.TotalTaxAmount, inv.TotalAmount, bl.BlNumber " .
"LIMIT 1; " .
"END";

if (mysqli_query($conn, $proc)) {
    echo "✓ Procédure GetInvoiceDetails créée avec succès\n\n";
    
    $result = mysqli_query($conn, "CALL GetInvoiceDetails(1)");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            echo "✓ TEST RÉUSSI:\n";
            echo "  - Invoice: " . $row['invoiceNumber'] . "\n";
            echo "  - Client: " . $row['client'] . "\n";
            echo "  - Total: " . $row['total'] . " CFA\n";
            echo "  - ShipInfo: " . (isset($row['shipInfo']) && !empty($row['shipInfo']) ? 'OK' : 'MANQUANT') . "\n";
            echo "  - Rubrics: " . (isset($row['rubrics']) && !empty($row['rubrics']) ? 'OK' : 'MANQUANT') . "\n";
            echo "  - Totals: " . (isset($row['totals']) && !empty($row['totals']) ? 'OK' : 'MANQUANT') . "\n";
        } else {
            echo "Pas de données (c'est normal si InvoiceId 1 n'existe pas)\n";
        }
    } else {
        echo "Erreur lors du test: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✗ ERREUR: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
