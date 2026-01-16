<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== Vérification BLItems ===\n\n";

$total = $conn->query("SELECT COUNT(*) as count FROM blitem")->fetch_assoc()['count'];
echo "Total BLItems: $total\n";

$with_jobfile = $conn->query("
    SELECT COUNT(DISTINCT BLItem_Id) as count FROM blitem_jobfile
")->fetch_assoc()['count'];
echo "BLItems avec jobfile: $with_jobfile\n";

$without_jobfile = $total - $with_jobfile;
echo "BLItems sans jobfile: $without_jobfile\n\n";

// Vérifier les types
$containers = $conn->query("SELECT COUNT(*) as count FROM blitem WHERE ItemTypeId = 1")->fetch_assoc()['count'];
$vehicles = $conn->query("SELECT COUNT(*) as count FROM blitem WHERE ItemTypeId = 2")->fetch_assoc()['count'];

echo "Conteneurs: $containers\n";
echo "Véhicules: $vehicles\n\n";

// Vérifier les jobfiles
$total_jobfiles = $conn->query("SELECT COUNT(*) as count FROM jobfile")->fetch_assoc()['count'];
echo "Total jobfiles dans DB: $total_jobfiles\n";

$conn->close();
?>
