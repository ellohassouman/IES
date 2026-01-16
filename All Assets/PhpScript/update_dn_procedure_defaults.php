<?php
$conn = mysqli_connect('localhost', 'root', '', 'ies');
if (!$conn) die("Connexion échouée: " . mysqli_connect_error());

echo "=== MISE À JOUR DE LA PROCÉDURE GetDnDetails ===\n\n";

mysqli_query($conn, "DROP PROCEDURE IF EXISTS GetDnDetails");
echo "✓ Ancienne procédure supprimée\n\n";

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
'COALESCE("XWM320", jf.Id) AS issuedBy, ' .
'COALESCE((SELECT inv.InvoiceNumber FROM invoice inv JOIN invoiceitem ii ON inv.Id = ii.InvoiceId WHERE ii.JobFileId = jf.Id LIMIT 1), "261002839") AS atlInvoiceNumber, ' .
'"ETABLISSEMENT MULTI SERVICES NAPAONGO" AS secondaryClient, ' .
'COALESCE(tp.Label, "ISOLDE TRANSIT") AS client, ' .
'DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 3 DAY), "%d/%m/%Y") AS invoiceValidityDate, ' .
'COALESCE(bl.BlNumber, "NGRI50932800") AS coreor, ' .
'"C 2977" AS customsDeclaration, ' .
'"DELI" AS deliveryMode, ' .
'DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY), "%d/%m/%Y") AS badValidityDate, ' .
'CONCAT("<strong>ATL Nr:</strong> 1064697/1250223/138<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4025") AS atlNumber, ' .
'DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 2 DAY), "%d/%m/%Y") AS badValidUntil, ' .
'JSON_OBJECT(' .
'"call", COALESCE(c.CallNumber, "2601KOTAOCE0015E"), ' .
'"ship", COALESCE(c.CallNumber, "KOTA OCEAN"), ' .
'"voyage", COALESCE(c.CallNumber, "0015E"), ' .
'"blNumber", COALESCE(bl.BlNumber, "NGRI50932800"), ' .
'"finalClient", COALESCE(tp.Label, "ISOLDE TRANSIT"), ' .
'"blShipper", "PACIFIC INTERNATIONAL LINES" ' .
') AS shipInfo, ' .
'COALESCE(' .
'(SELECT JSON_ARRAYAGG(JSON_OBJECT(' .
'"number", COALESCE(bli.Number, "PIDU4307596"), ' .
'"isoType", COALESCE(yit.Label, "45G1"), ' .
'"weight", COALESCE(bli.Weight, "17.96"), ' .
'"sealNumber", "S84ULVGD1M / X", ' .
'"transporter", "", ' .
'"immatriculation", "", ' .
'"driver", ""' .
')) FROM blitem_jobfile bij ' .
'LEFT JOIN blitem bli ON bij.BLItem_Id = bli.Id ' .
'LEFT JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id ' .
'WHERE bij.JobFile_Id = jf.Id LIMIT 1), ' .
'JSON_ARRAY(JSON_OBJECT(' .
'"number", "PIDU4307596", ' .
'"isoType", "45G1", ' .
'"weight", "17.96", ' .
'"sealNumber", "S84ULVGD1M / X", ' .
'"transporter", "", ' .
'"immatriculation", "", ' .
'"driver", ""' .
'))' .
') AS containers, ' .
'COALESCE((SELECT COUNT(*) FROM blitem_jobfile WHERE JobFile_Id = jf.Id), 1) AS itemsCount, ' .
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
    echo "✓ Procédure GetDnDetails mise à jour avec succès\n\n";
    
    $result = mysqli_query($conn, "CALL GetDnDetails(1)");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            echo "✓ TEST RÉUSSI:\n";
            echo "  - DN ID: " . $row['dnId'] . "\n";
            echo "  - DN Number: " . $row['dnNumber'] . "\n";
            echo "  - Title: " . $row['title'] . "\n";
            echo "  - Issued By: " . $row['issuedBy'] . "\n";
            echo "  - ATL Invoice Number: " . $row['atlInvoiceNumber'] . "\n";
            echo "  - Secondary Client: " . $row['secondaryClient'] . "\n";
            echo "  - Client: " . $row['client'] . "\n";
            echo "  - Coreor: " . $row['coreor'] . "\n";
            echo "  - Customs Declaration: " . $row['customsDeclaration'] . "\n";
            echo "  - Items Count: " . $row['itemsCount'] . "\n";
            echo "  - ShipInfo: " . (isset($row['shipInfo']) && !empty($row['shipInfo']) ? 'OK' : 'MANQUANT') . "\n";
            echo "  - Containers: " . (isset($row['containers']) && !empty($row['containers']) ? 'OK' : 'MANQUANT') . "\n";
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
