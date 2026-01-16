<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== Vérification existence ShippingLine et Position ===\n\n";

$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

echo "ShippingLine existe? " . (in_array('shippingline', $tables) ? "OUI" : "NON") . "\n";
echo "Position existe? " . (in_array('position', $tables) ? "OUI" : "NON") . "\n";

// Vérifier les contraintes de jobfile
echo "\n=== Contraintes de clés étrangères sur jobfile ===\n";
$result = $conn->query("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'jobfile' AND TABLE_SCHEMA = 'ies'
");

while ($row = $result->fetch_assoc()) {
    echo "Contrainte: {$row['CONSTRAINT_NAME']}, Colonne: {$row['COLUMN_NAME']}, Ref: {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
}

$conn->close();
?>
