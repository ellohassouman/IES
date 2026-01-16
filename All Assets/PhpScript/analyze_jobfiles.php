<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== ANALYSE DES RELATIONS JOBFILES ===\n\n";

// Vérifier les relations avec blitem_jobfile
$result = $conn->query("SELECT COUNT(DISTINCT JobFile_Id) as count FROM blitem_jobfile");
$row = $result->fetch_assoc();
echo "Jobfiles avec références dans blitem_jobfile: {$row['count']}\n";

// Vérifier les relations avec document
$result = $conn->query("SELECT COUNT(DISTINCT JobFileId) as count FROM document WHERE JobFileId IS NOT NULL");
$row = $result->fetch_assoc();
echo "Jobfiles avec références dans document: {$row['count']}\n";

// Vérifier les relations avec event
$result = $conn->query("SELECT COUNT(DISTINCT JobFileId) as count FROM event WHERE JobFileId IS NOT NULL");
$row = $result->fetch_assoc();
echo "Jobfiles avec références dans event: {$row['count']}\n";

// Total jobfiles
$result = $conn->query("SELECT COUNT(*) as count FROM jobfile");
$row = $result->fetch_assoc();
echo "Total jobfiles: {$row['count']}\n\n";

// Trouver les jobfiles vraiment orphelins
echo "=== JOBFILES VRAIMENT ORPHELINS ===\n\n";

$result = $conn->query("
    SELECT j.Id, j.DateOpen, j.DateClose
    FROM jobfile j
    WHERE j.Id NOT IN (
        SELECT DISTINCT JobFile_Id FROM blitem_jobfile WHERE JobFile_Id IS NOT NULL
    )
    AND j.Id NOT IN (
        SELECT DISTINCT JobFileId FROM document WHERE JobFileId IS NOT NULL
    )
    AND j.Id NOT IN (
        SELECT DISTINCT JobFileId FROM event WHERE JobFileId IS NOT NULL
    )
    LIMIT 10
");

if ($result->num_rows == 0) {
    echo "✓ Aucun jobfile orphelin trouvé\n";
} else {
    echo "Jobfiles orphelins (affichage des 10 premiers):\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['Id']}, DateOpen: {$row['DateOpen']}, DateClose: {$row['DateClose']}\n";
    }
}

$conn->close();
?>
