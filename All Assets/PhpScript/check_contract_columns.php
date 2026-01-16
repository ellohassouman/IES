<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

echo "=== Colonnes de la table contract ===\n\n";
$result = $mysqli->query('SHOW COLUMNS FROM contract');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== Clés étrangères existantes sur contract ===\n\n";
$result = $mysqli->query("
    SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'contract' 
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "- {$row['CONSTRAINT_NAME']}: {$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "Aucune clé étrangère trouvée\n";
}

$mysqli->close();
?>
