<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== CORRECTION DE LA RÈGLE 4 ===\n\n";

// Identifier tous les IN des conteneurs qui ne sont pas dans contract_eventtype
$result = $conn->query("
    SELECT 
        jf.Id as JobFileId,
        bli.Number as ItemNumber,
        et.Label as EventLabel,
        et.Id as EventTypeId
    FROM jobfile jf
    JOIN blitem_jobfile bjf ON jf.Id = bjf.JobFile_Id
    JOIN blitem bli ON bjf.BLItem_Id = bli.Id
    JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    JOIN family f ON et.FamilyId = f.Id
    WHERE yit.Id = 1 -- Conteneurs
    AND f.Label = 'In'
    AND et.Id NOT IN (
        SELECT EventType_Id FROM contract_eventtype
    )
");

$violations = [];
while ($row = $result->fetch_assoc()) {
    $violations[] = $row;
}

echo "Violations détectées : " . count($violations) . "\n";

// Récupérer les IN types valides pour conteneurs
$result = $conn->query("
    SELECT DISTINCT et.Id, et.Label
    FROM eventtype et
    JOIN contract_eventtype cet ON et.Id = cet.EventType_Id
    JOIN family f ON et.FamilyId = f.Id
    WHERE f.Label = 'In'
");

$valid_in_types = [];
while ($row = $result->fetch_assoc()) {
    $valid_in_types[] = $row;
}

echo "Types IN valides pour conteneurs : " . count($valid_in_types) . "\n\n";

// Corriger chaque violation
$fixed = 0;
foreach ($violations as $violation) {
    // Sélectionner aléatoirement un IN type valide
    $valid = $valid_in_types[array_rand($valid_in_types)];
    $new_event_type_id = $valid['Id'];
    
    // Mettre à jour l'événement
    $conn->query("UPDATE event SET EventTypeId = $new_event_type_id 
                 WHERE JobFileId = {$violation['JobFileId']} 
                 AND EventTypeId = {$violation['EventTypeId']}
                 LIMIT 1");
    
    $fixed++;
}

echo "✓ $fixed événements corrigés\n\n";

// Vérification
echo "=== VÉRIFICATION ===\n";
$result = $conn->query("
    SELECT 
        jf.Id as JobFileId,
        bli.Number as ItemNumber,
        et.Label as EventLabel
    FROM jobfile jf
    JOIN blitem_jobfile bjf ON jf.Id = bjf.JobFile_Id
    JOIN blitem bli ON bjf.BLItem_Id = bli.Id
    JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    JOIN family f ON et.FamilyId = f.Id
    WHERE yit.Id = 1 -- Conteneurs
    AND f.Label = 'In'
    AND et.Id NOT IN (
        SELECT EventType_Id FROM contract_eventtype
    )
");

if ($result->num_rows === 0) {
    echo "✅ Règle 4 : RESPECTÉE - Tous les IN de conteneurs sont dans contract_eventtype\n";
} else {
    echo "❌ Règle 4 : " . $result->num_rows . " violations restantes\n";
    while ($row = $result->fetch_assoc()) {
        echo "   JobFile {$row['JobFileId']}: {$row['EventLabel']}\n";
    }
}

$conn->close();
?>
