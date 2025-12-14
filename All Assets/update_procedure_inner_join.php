<?php
$db = new PDO('mysql:host=localhost;dbname=ies', 'root', '');

echo "🔧 MODIFICATION: GetYardItemTrackingMovements\n";
echo str_repeat("=", 70) . "\n\n";

// 1. Supprimer l'ancienne procédure
echo "1️⃣ Suppression de l'ancienne procédure...\n";
try {
    $db->exec("DROP PROCEDURE IF EXISTS GetYardItemTrackingMovements");
    echo "   ✓ Ancienne procédure supprimée\n";
} catch(Exception $e) {
    echo "   ✗ Erreur: {$e->getMessage()}\n";
}

// 2. Créer la nouvelle procédure avec INNER JOIN
echo "\n2️⃣ Création de la nouvelle procédure avec jointures fortes...\n";

$newProcedure = "
CREATE PROCEDURE GetYardItemTrackingMovements(
    IN p_YardItemId INT, 
    IN p_YardItemNumber VARCHAR(100), 
    IN p_BillOfLadingNumber VARCHAR(100)
)
BEGIN
    SELECT
        evt.EventDate AS Date,
        et.Label AS EventTypeName,
        et.Code AS EventTypeCode,
        'True' AS CreatedByIES,
        '' AS Position
    FROM event evt
    INNER JOIN eventtype et ON evt.EventTypeId = et.Id
    INNER JOIN jobfile jf ON evt.JobFileId = jf.Id
    INNER JOIN blitem_jobfile bij ON jf.Id = bij.JobFile_Id
    INNER JOIN blitem bli ON bij.BLItem_Id = bli.Id
    INNER JOIN bl ON bli.BlId = bl.Id
    WHERE bli.Number = p_YardItemNumber
    AND bl.BlNumber = p_BillOfLadingNumber
    ORDER BY evt.EventDate DESC;
END
";

try {
    $db->exec($newProcedure);
    echo "   ✓ Nouvelle procédure créée avec succès\n";
} catch(Exception $e) {
    echo "   ✗ Erreur: {$e->getMessage()}\n";
}

// 3. Vérifier la nouvelle définition
echo "\n3️⃣ Vérification de la nouvelle procédure...\n";
$result = $db->query("SHOW CREATE PROCEDURE GetYardItemTrackingMovements");
$procedure = $result->fetch(PDO::FETCH_ASSOC);
if($procedure) {
    echo "   ✓ Procédure vérifiée\n";
    echo "\n   Définition:\n";
    $def = $procedure['Create Procedure'];
    // Afficher seulement la partie SELECT
    if(preg_match('/SELECT.*?ORDER BY/s', $def, $matches)) {
        echo "   " . str_replace("\n", "\n   ", substr($matches[0], 0, 200)) . "...\n";
    }
}

// 4. Tester avec MSCU9876543
echo "\n4️⃣ Test avec MSCU9876543 (BL 5):\n";
try {
    $result = $db->query("CALL GetYardItemTrackingMovements(5, 'MSCU9876543', 'BLNO00005')");
    $events = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "   Résultats: " . count($events) . " événements\n";
    
    foreach($events as $evt) {
        echo "   - {$evt['Date']}: {$evt['EventTypeName']} ({$evt['EventTypeCode']})\n";
    }
    
    if(count($events) <= 4) {
        echo "\n   ✅ OK! Max 4 événements comme prévu\n";
    } else {
        echo "\n   ⚠️  Encore " . count($events) . " événements (max 4 attendu)\n";
    }
} catch(Exception $e) {
    echo "   ✗ Erreur lors du test: {$e->getMessage()}\n";
}

// 5. Tester avec d'autres items
echo "\n5️⃣ Test avec autres items:\n";
$result = $db->query("
    SELECT DISTINCT bli.Number, bl.BlNumber
    FROM blitem bli
    INNER JOIN bl ON bli.BlId = bl.Id
    LIMIT 5
");
$items = $result->fetchAll(PDO::FETCH_ASSOC);

foreach($items as $item) {
    try {
        $result = $db->query("CALL GetYardItemTrackingMovements(0, '{$item['Number']}', '{$item['BlNumber']}')");
        $events = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "   {$item['Number']}: " . count($events) . " événements\n";
    } catch(Exception $e) {
        echo "   {$item['Number']}: Erreur - {$e->getMessage()}\n";
    }
}

echo "\n✅ MODIFICATION COMPLÉTÉE\n";
?>
