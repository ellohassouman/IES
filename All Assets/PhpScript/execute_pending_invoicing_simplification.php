<?php
/**
 * Script d'exécution de la simplification de GetPendingInvoicingItemsPerBLNumber
 * 
 * MODIFICATION: Afficher TOUS les items (facturés, facturables, non facturables)
 * RAISON: Les conditions de facturation seront vérifiées dans GenerateProforma
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
echo "SIMPLIFICATION: GetPendingInvoicingItemsPerBLNumber\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// DROP la procédure
echo "⏳ Suppression de l'ancienne procédure...\n";
if ($conn->query("DROP PROCEDURE IF EXISTS `GetPendingInvoicingItemsPerBLNumber`")) {
    echo "✅ Ancienne procédure supprimée\n\n";
} else {
    echo "⚠️  " . $conn->error . "\n\n";
}

// CREATE la nouvelle procédure
echo "⏳ Création de la nouvelle procédure...\n";

$create_sql = "CREATE DEFINER=`root`@`localhost` PROCEDURE `GetPendingInvoicingItemsPerBLNumber` (IN `p_BlNumber` VARCHAR(100))
BEGIN
    SELECT DISTINCT
        CAST(bli.Id AS CHAR) AS id,
        COALESCE(bli.Number, '') AS number,
        CONCAT('[', COALESCE(bit.Label, ''), ']') AS type,
        COALESCE(ci.NumberOfPackages, '') AS description,
        COALESCE(jf.Id, 0) AS jobFileId,
        FALSE AS isDraft,
        FALSE AS dnPrintable
    FROM blitem bli
    LEFT JOIN yarditemtype bit ON bli.ItemTypeId = bit.Id
    LEFT JOIN commodityitem ci ON bli.Id = ci.BlItemId
    LEFT JOIN bl bl ON bli.BlId = bl.Id
    LEFT JOIN blitem_jobfile bij ON bli.Id = bij.BLItem_Id
    LEFT JOIN jobfile jf ON bij.JobFile_Id = jf.Id
    WHERE bl.BlNumber = p_BlNumber
    ORDER BY bli.Number;
END";

if ($conn->query($create_sql)) {
    echo "✅ Nouvelle procédure créée avec succès!\n\n";
    
    // Vérifier
    $check_sql = "SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES 
                  WHERE ROUTINE_SCHEMA = 'ies' AND ROUTINE_NAME = 'GetPendingInvoicingItemsPerBLNumber' 
                  AND ROUTINE_TYPE = 'PROCEDURE'";
    
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Vérification: La procédure GetPendingInvoicingItemsPerBLNumber est bien créée\n";
        echo "   • Affiche maintenant TOUS les items sans filtres de facturation\n";
    } else {
        echo "❌ Vérification échouée\n";
    }
} else {
    echo "❌ Erreur: " . $conn->error . "\n";
}

$conn->close();

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Simplification terminée avec succès!\n";
echo "═══════════════════════════════════════════════════════════════\n";
?>
