<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== ANALYSE ET NETTOYAGE DES JOBFILES ORPHELINS ===\n\n";

// D'abord, identifier les relations possibles avec jobfile
echo "Vérification des relations possibles avec jobfile:\n\n";

// 1. Vérifier les jobfiles sans référence dans blitem_jobfile, document ET event
$result = $conn->query("
    SELECT j.Id
    FROM jobfile j
    LEFT JOIN blitem_jobfile bjf ON j.Id = bjf.JobFile_Id
    LEFT JOIN document d ON j.Id = d.JobFileId
    LEFT JOIN event e ON j.Id = e.JobFileId
    WHERE bjf.JobFile_Id IS NULL AND d.JobFileId IS NULL AND e.JobFileId IS NULL
    ORDER BY j.Id
");

$orphaned_count = $result->num_rows;
echo "Jobfiles sans référence dans blitem_jobfile, document ET event: $orphaned_count\n\n";

if ($orphaned_count > 0) {
    echo "Jobfiles à supprimer:\n";
    $ids_to_delete = [];
    
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['Id']}\n";
        $ids_to_delete[] = $row['Id'];
    }
    
    // Supprimer les jobfiles orphelins
    if (!empty($ids_to_delete)) {
        $ids_str = implode(',', $ids_to_delete);
        
        echo "\n=== SUPPRESSION ===\n";
        
        // Supprimer d'abord les enregistrements liés (cascading delete)
        $conn->query("DELETE FROM blitem_jobfile WHERE JobFile_Id IN ($ids_str)");
        $conn->query("DELETE FROM document WHERE JobFileId IN ($ids_str)");
        $conn->query("DELETE FROM event WHERE JobFileId IN ($ids_str)");
        
        // Supprimer les jobfiles orphelins
        $delete = $conn->query("DELETE FROM jobfile WHERE Id IN ($ids_str)");
        
        if ($delete) {
            echo "✓ $orphaned_count jobfile(s) orphelin(s) supprimé(s)\n";
        } else {
            echo "✗ Erreur lors de la suppression: " . $conn->error . "\n";
        }
    }
} else {
    echo "✓ Aucun jobfile orphelin trouvé\n";
}

echo "\n=== VÉRIFICATION FINALE ===\n";
$total = $conn->query("SELECT COUNT(*) as count FROM jobfile")->fetch_assoc();
$with_ref = $conn->query("
    SELECT COUNT(DISTINCT jf.Id) as count 
    FROM jobfile jf
    INNER JOIN blitem_jobfile bjf ON jf.Id = bjf.JobFile_Id
")->fetch_assoc();

echo "Total jobfiles restants: {$total['count']}\n";
echo "Jobfiles avec références: {$with_ref['count']}\n";
echo "\n✅ Nettoyage terminé!\n";

$conn->close();
?>
