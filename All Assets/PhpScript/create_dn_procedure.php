<?php
$conn = mysqli_connect('localhost', 'root', '', 'ies');
if (!$conn) die("Connexion échouée: " . mysqli_connect_error());

echo "=== CRÉATION DE LA PROCÉDURE GetDnDetails ===\n\n";

mysqli_query($conn, "DROP PROCEDURE IF EXISTS GetDnDetails");
echo "✓ Ancienne procédure supprimée\n\n";

// Utiliser double guillemets pour pouvoir utiliser des variables
$backquote = '`';
$proc = 'CREATE PROCEDURE GetDnDetails(IN p_JobFileId INT) READS SQL DATA NOT DETERMINISTIC ' .
'COMMENT "Récupérer les détails complets du Bon à Délivrer" BEGIN ' .
'SELECT ' .
'jf.Id AS dnId, ' .
'CONCAT("DN-", jf.Id) AS dnNumber, ' .
'CONCAT("Bon à Délivrer N° ", jf.Id) AS dnNumber_formatted, ' .
'"Bon à Délivrer" AS title, ' .
'"DELI (Livraison normale)" AS subtitle, ' .
'DATE_FORMAT(NOW(), "%d/%m/%Y %H:%i:%s") AS printedDate, ' .
'COALESCE(jf.Id, "N/A") AS issuedBy, ' .
'COALESCE((SELECT inv.InvoiceNumber FROM invoice inv JOIN invoiceitem ii ON inv.Id = ii.InvoiceId WHERE ii.JobFileId = jf.Id LIMIT 1), "N/A") AS atlInvoiceNumber, ' .
'"ETABLISSEMENT MULTI SERVICES" AS secondaryClient, ' .
'COALESCE(tp.Label, "N/A") AS client, ' .
'DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 3 DAY), "%d/%m/%Y") AS invoiceValidityDate, ' .
'COALESCE(bl.BlNumber, "N/A") AS coreor, ' .
'"C 2977" AS customsDeclaration, ' .
'"DELI" AS deliveryMode, ' .
'DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY), "%d/%m/%Y") AS badValidityDate, ' .
'CONCAT("ATL Nr: ", jf.Id) AS atlNumber, ' .
'DATE_FORMAT(NOW(), "%d/%m/%Y") AS badValidUntil, ' .
'JSON_OBJECT("callNumber", COALESCE(c.CallNumber, "N/A"), "blNumber", COALESCE(bl.BlNumber, "N/A"), "client", COALESCE(tp.Label, "N/A")) AS shipInfo, ' .
'COALESCE((SELECT JSON_ARRAYAGG(JSON_OBJECT("number", COALESCE(bli.Number, ""), "isoType", COALESCE(yit.Label, ""), "weight", COALESCE(bli.Weight, "0"))) FROM blitem_jobfile bij LEFT JOIN blitem bli ON bij.BLItem_Id = bli.Id LEFT JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id WHERE bij.JobFile_Id = jf.Id), JSON_ARRAY()) AS containers, ' .
'(SELECT COUNT(*) FROM blitem_jobfile WHERE JobFile_Id = jf.Id) AS itemsCount, ' .
'1 AS success ' .
'FROM jobfile jf ' .
'LEFT JOIN blitem_jobfile bij ON jf.Id = bij.JobFile_Id ' .
'LEFT JOIN blitem bli ON bij.BLItem_Id = bli.Id ' .
'LEFT JOIN bl ON bli.BlId = bl.Id ' .
'LEFT JOIN thirdparty tp ON bl.RelatedCustomerId = tp.Id ' .
'LEFT JOIN thirdparty tp2 ON bl.ConsigneeId = tp2.Id ' .
'LEFT JOIN ' . $backquote . 'call' . $backquote . ' c ON bl.CallId = c.Id ' .
'WHERE jf.Id = p_JobFileId ' .
'GROUP BY jf.Id, tp.Label, bl.BlNumber, c.CallNumber ' .
'LIMIT 1; ' .
'END';

if (mysqli_query($conn, $proc)) {
    echo "✓ Procédure GetDnDetails créée avec succès\n\n";
    
    $result = mysqli_query($conn, "CALL GetDnDetails(1)");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            echo "✓ TEST RÉUSSI:\n";
            echo "  - DN ID: " . $row['dnId'] . "\n";
            echo "  - DN Number: " . $row['dnNumber'] . "\n";
            echo "  - Client: " . $row['client'] . "\n";
            echo "  - Items Count: " . $row['itemsCount'] . "\n";
        } else {
            echo "Pas de données (c'est normal si JobFileId 1 n'existe pas)\n";
        }
    } else {
        echo "Erreur lors du test: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✗ ERREUR: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
