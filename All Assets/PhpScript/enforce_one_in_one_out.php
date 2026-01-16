<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== ANALYSE DES VIOLATIONS DE RÈGLE ===\n\n";

// Récupérer les familles
$in_family = $conn->query("SELECT Id FROM family WHERE Label = 'In'")->fetch_assoc()['Id'];
$out_family = $conn->query("SELECT Id FROM family WHERE Label = 'Out'")->fetch_assoc()['Id'];

echo "Famille IN: $in_family\n";
echo "Famille OUT: $out_family\n\n";

// Vérifier les jobfiles avec plusieurs IN
echo "=== Jobfiles avec plusieurs IN ===\n";
$result = $conn->query("
    SELECT 
        jf.Id,
        COUNT(e.Id) as INCount
    FROM jobfile jf
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    WHERE et.FamilyId = $in_family
    GROUP BY jf.Id
    HAVING COUNT(e.Id) > 1
");

$violations_in = $result->num_rows;
echo "Jobfiles avec plusieurs IN: $violations_in\n";

if ($violations_in > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  - JobFile ID {$row['Id']}: {$row['INCount']} IN\n";
    }
}

echo "\n=== Jobfiles avec plusieurs OUT ===\n";
$result = $conn->query("
    SELECT 
        jf.Id,
        COUNT(e.Id) as OUTCount
    FROM jobfile jf
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    WHERE et.FamilyId = $out_family
    GROUP BY jf.Id
    HAVING COUNT(e.Id) > 1
");

$violations_out = $result->num_rows;
echo "Jobfiles avec plusieurs OUT: $violations_out\n";

if ($violations_out > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  - JobFile ID {$row['Id']}: {$row['OUTCount']} OUT\n";
    }
}

echo "\n=== CORRECTION ===\n\n";

// Supprimer les IN supplémentaires (garder le plus ancien)
echo "Suppression des IN supplémentaires...\n";
$result = $conn->query("
    SELECT jf.Id, e.Id as EventId
    FROM jobfile jf
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    WHERE et.FamilyId = $in_family
    ORDER BY jf.Id, e.EventDate DESC
");

$events_to_delete = [];
$last_jobfile = null;

while ($row = $result->fetch_assoc()) {
    if ($last_jobfile == $row['Id']) {
        $events_to_delete[] = $row['EventId'];
    }
    $last_jobfile = $row['Id'];
}

if (!empty($events_to_delete)) {
    $ids_str = implode(',', $events_to_delete);
    $conn->query("DELETE FROM event WHERE Id IN ($ids_str)");
    echo "✓ " . count($events_to_delete) . " IN supplémentaires supprimés\n";
}

// Supprimer les OUT supplémentaires (garder le plus ancien)
echo "Suppression des OUT supplémentaires...\n";
$result = $conn->query("
    SELECT jf.Id, e.Id as EventId
    FROM jobfile jf
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    WHERE et.FamilyId = $out_family
    ORDER BY jf.Id, e.EventDate DESC
");

$events_to_delete = [];
$last_jobfile = null;

while ($row = $result->fetch_assoc()) {
    if ($last_jobfile == $row['Id']) {
        $events_to_delete[] = $row['EventId'];
    }
    $last_jobfile = $row['Id'];
}

if (!empty($events_to_delete)) {
    $ids_str = implode(',', $events_to_delete);
    $conn->query("DELETE FROM event WHERE Id IN ($ids_str)");
    echo "✓ " . count($events_to_delete) . " OUT supplémentaires supprimés\n";
}

echo "\n=== VÉRIFICATION FINALE ===\n\n";

// Vérifier qu'il n'y a plus de violations
$violations = $conn->query("
    SELECT COUNT(*) as violations
    FROM (
        SELECT jf.Id
        FROM jobfile jf
        JOIN event e ON jf.Id = e.JobFileId
        JOIN eventtype et ON e.EventTypeId = et.Id
        WHERE et.FamilyId = $in_family
        GROUP BY jf.Id
        HAVING COUNT(e.Id) > 1
        UNION
        SELECT jf.Id
        FROM jobfile jf
        JOIN event e ON jf.Id = e.JobFileId
        JOIN eventtype et ON e.EventTypeId = et.Id
        WHERE et.FamilyId = $out_family
        GROUP BY jf.Id
        HAVING COUNT(e.Id) > 1
    ) violations
")->fetch_assoc()['violations'];

echo "Violations restantes: $violations\n";

if ($violations == 0) {
    echo "✅ Règle respectée : 1 seul IN et 1 seul OUT par jobfile\n";
} else {
    echo "⚠ Il reste encore des violations\n";
}

$conn->close();
?>
