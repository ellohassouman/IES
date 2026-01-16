<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== Structure jobfile ===\n";
$result = $conn->query('DESC jobfile');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ') - ' . $row['Key'] . "\n";
}

echo "\n=== Structure event ===\n";
$result = $conn->query('DESC event');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}

echo "\n=== Structure eventtype ===\n";
$result = $conn->query('DESC eventtype');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}

echo "\n=== Structure family ===\n";
$result = $conn->query('DESC family');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}

// Vérifier les familles
echo "\n=== Familles d'événements ===\n";
$result = $conn->query("SELECT Id, Label FROM family");
while ($row = $result->fetch_assoc()) {
    echo $row['Label'] . ' - ID: ' . $row['Id'] . "\n";
}

// Récupérer les types d'événements par famille
echo "\n=== EventTypes par famille ===\n";
$result = $conn->query("
    SELECT f.Label as FamilyLabel, et.Label as EventTypeLabel, et.Id
    FROM eventtype et
    LEFT JOIN family f ON et.FamilyId = f.Id
    ORDER BY f.Label, et.Label
    LIMIT 20
");
while ($row = $result->fetch_assoc()) {
    $family = $row['FamilyLabel'] ?? 'NULL';
    echo "  {$row['EventTypeLabel']} (ID: {$row['Id']}) - Famille: $family\n";
}

$conn->close();
?>
