<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

echo "=== Structure contract_eventtype ===\n";
$result = $conn->query('DESC contract_eventtype');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}

echo "\n=== Exemples de données ===\n";
$result = $conn->query('SELECT * FROM contract_eventtype LIMIT 5');
while ($row = $result->fetch_assoc()) {
    echo json_encode($row) . "\n";
}

$conn->close();
?>
