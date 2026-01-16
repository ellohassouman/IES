<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== NETTOYAGE DES JOBFILES CRÉÉS ===\n\n";

// Supprimer les associations blitem_jobfile
$delete1 = $conn->query("DELETE FROM blitem_jobfile WHERE JobFile_Id >= 232");
echo "Associations blitem_jobfile supprimées\n";

// Supprimer les événements
$delete2 = $conn->query("DELETE FROM event WHERE JobFileId >= 232");
echo "Événements supprimés\n";

// Supprimer les jobfiles
$delete3 = $conn->query("DELETE FROM jobfile WHERE Id >= 232");
echo "JobFiles supprimés\n";

echo "\n✅ Nettoyage terminé!\n";

$conn->close();
?>
