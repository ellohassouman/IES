<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== Événements avec 'Vehicle' ===\n";
$result = $conn->query("
    SELECT Id, Label, FamilyId FROM eventtype
    WHERE Label LIKE '%Vehicle%' OR Label LIKE '%vehicle%'
    ORDER BY Label
");
echo "Résultats: " . $result->num_rows . "\n";

echo "\n=== Événements avec 'VT' ou 'Vide' ===\n";
$result = $conn->query("
    SELECT Id, Label, FamilyId FROM eventtype
    WHERE Label LIKE '%VT%' OR Label LIKE '%Vide%' OR Label LIKE '%vide%'
    ORDER BY Label
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    echo "{$row['Label']} (ID: {$row['Id']}, Family: {$row['FamilyId']})\n";
}

echo "\n=== Tous les types d'événements ===\n";
$result = $conn->query("
    SELECT DISTINCT Label FROM eventtype
    WHERE Label NOT LIKE 'event_type%'
    ORDER BY Label
    LIMIT 30
");
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Label']}\n";
}

$conn->close();
?>
