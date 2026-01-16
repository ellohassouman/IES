<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== STRUCTURE TABLES ===\n\n";

$tables = ['invoice', 'billOfLading', 'yarditem'];
foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $result = $conn->query("DESC $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  {$row['Field']} ({$row['Type']}) " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
    }
    echo "\n";
}

echo "\n=== EXAMPLE DATA ===\n\n";

// Un exemple de facture
$result = $conn->query("SELECT * FROM invoice LIMIT 1");
if ($result && $result->num_rows > 0) {
    $invoice = $result->fetch_assoc();
    echo "INVOICE SAMPLE:\n";
    foreach ($invoice as $key => $value) {
        echo "  $key: " . substr($value, 0, 100) . "\n";
    }
    echo "\n";
}

// Vérifier la jointure
$result = $conn->query("SELECT i.id, i.number, bl.consignee, bl.vesselName FROM invoice i JOIN billOfLading bl ON i.blId = bl.id LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "JOIN SAMPLE:\n";
    foreach ($row as $key => $value) {
        echo "  $key: $value\n";
    }
}

$conn->close();
?>
