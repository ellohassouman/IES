<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== STRUCTURE: blitem ===\n";
$result = $conn->query("DESC blitem");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== STRUCTURE: yarditemtype ===\n";
$result = $conn->query("DESC yarditemtype");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== STRUCTURE: yarditemcode ===\n";
$result = $conn->query("DESC yarditemcode");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== EXEMPLE: yarditemtype (types d'items) ===\n";
$result = $conn->query("SELECT * FROM yarditemtype LIMIT 10");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID {$row['Id']}: {$row['Label']}\n";
    }
}
echo "\n";

echo "=== EXEMPLE: blitem ===\n";
$result = $conn->query("
    SELECT 
        bli.Id,
        bli.Number,
        bli.Weight,
        bli.Volume,
        bli.ItemTypeId,
        bli.ItemCodeId,
        yit.Label as ItemType,
        yic.Label as ItemCode
    FROM blitem bli
    LEFT JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id
    LEFT JOIN yarditemcode yic ON bli.ItemCodeId = yic.Id
    LIMIT 10
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\n  Item #{$row['Id']}:\n";
        echo "    Number: {$row['Number']}\n";
        echo "    Type: {$row['ItemType']}\n";
        echo "    Code: {$row['ItemCode']}\n";
        echo "    Weight: {$row['Weight']}\n";
        echo "    Volume: {$row['Volume']}\n";
    }
}
echo "\n";

echo "=== ANALYSE: Items par BL ===\n";
$result = $conn->query("
    SELECT 
        bl.Id,
        bl.BlNumber,
        COUNT(bli.Id) as item_count,
        COUNT(CASE WHEN yit.Label LIKE '%Conteneur%' THEN 1 END) as container_count,
        COUNT(CASE WHEN yit.Label LIKE '%Véhicule%' THEN 1 END) as vehicle_count
    FROM bl
    LEFT JOIN blitem bli ON bl.Id = bli.BlId
    LEFT JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id
    GROUP BY bl.Id
    ORDER BY item_count DESC
    LIMIT 10
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "BL {$row['BlNumber']}: {$row['item_count']} items (Conteneurs: {$row['container_count']}, Véhicules: {$row['vehicle_count']})\n";
    }
}

$conn->close();
?>