<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== STRUCTURE: contract ===\n";
$result = $conn->query("DESC contract");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== EXEMPLE: contract ===\n";
$result = $conn->query("SELECT * FROM contract LIMIT 2");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\n";
        foreach ($row as $key => $value) {
            echo "  $key: " . substr($value, 0, 100) . "\n";
        }
    }
}
echo "\n";

echo "=== STRUCTURE: eventtype ===\n";
$result = $conn->query("DESC eventtype");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== EXEMPLE: eventtype ===\n";
$result = $conn->query("SELECT * FROM eventtype LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\n";
        foreach ($row as $key => $value) {
            echo "  $key: " . substr($value, 0, 100) . "\n";
        }
    }
}
echo "\n";

// Chercher les relations possibles
echo "=== CLÉS ÉTRANGÈRES invoiceitem ===\n";
$result = $conn->query("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'invoiceitem' AND CONSTRAINT_SCHEMA = 'ies'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
}

$conn->close();
?>