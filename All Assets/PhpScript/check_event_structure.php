<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== Structure complète de event ===\n";
$result = $conn->query('DESC event');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}

echo "\n=== Vérifier les colonnes disponibles dans event ===\n";
$result = $conn->query("SHOW FULL COLUMNS FROM event");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

$conn->close();
?>
