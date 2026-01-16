<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== VÉRIFICATION FINALE DES JOBFILES ===\n\n";

// Vérifier la distribution par type
$result = $conn->query("
    SELECT 
        yit.Label as ItemType,
        COUNT(DISTINCT jf.Id) as JobFileCount,
        AVG(et_count.EventCount) as AvgEventsPerJobfile
    FROM blitem bli
    JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id
    JOIN blitem_jobfile bjf ON bli.Id = bjf.BLItem_Id
    JOIN jobfile jf ON bjf.JobFile_Id = jf.Id
    LEFT JOIN (
        SELECT JobFileId, COUNT(*) as EventCount
        FROM event
        GROUP BY JobFileId
    ) et_count ON jf.Id = et_count.JobFileId
    GROUP BY yit.Label
");

while ($row = $result->fetch_assoc()) {
    echo "{$row['ItemType']}: {$row['JobFileCount']} jobfiles, {$row['AvgEventsPerJobfile']} events en moyenne\n";
}

echo "\n=== Distribution des événements ===\n\n";

// Vérifier la distribution des événements par famille
$result = $conn->query("
    SELECT 
        f.Label as Family,
        COUNT(e.Id) as EventCount,
        COUNT(DISTINCT e.JobFileId) as JobFileCount
    FROM event e
    JOIN eventtype et ON e.EventTypeId = et.Id
    LEFT JOIN family f ON et.FamilyId = f.Id
    GROUP BY f.Label
    ORDER BY Family
");

while ($row = $result->fetch_assoc()) {
    $family = $row['Family'] ?? 'NULL';
    echo "$family: {$row['EventCount']} events, {$row['JobFileCount']} jobfiles\n";
}

echo "\n=== Vérification DateOpen/DateClose ===\n\n";

$result = $conn->query("
    SELECT 
        COUNT(*) as Total,
        SUM(CASE WHEN DateOpen IS NOT NULL THEN 1 ELSE 0 END) as WithDateOpen,
        SUM(CASE WHEN DateClose IS NOT NULL THEN 1 ELSE 0 END) as WithDateClose
    FROM jobfile
");

$row = $result->fetch_assoc();
echo "Total jobfiles: {$row['Total']}\n";
echo "Avec DateOpen: {$row['WithDateOpen']}\n";
echo "Avec DateClose: {$row['WithDateClose']}\n";

echo "\n✅ Vérification complète!\n";

$conn->close();
?>
