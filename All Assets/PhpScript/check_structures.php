<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== STRUCTURE JOBFILE ===\n";
$result = $conn->query('DESC jobfile');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== STRUCTURE BLITEM_JOBFILE ===\n";
$result = $conn->query('DESC blitem_jobfile');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

$conn->close();
?>
