<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== STRUCTURE DES TABLES ===\n\n";

$tables = ['thirdparty', 'bl', 'invoiceitem'];
foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $result = $conn->query("DESC $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "  Erreur: " . $conn->error . "\n";
    }
    echo "\n";
}

// call avec backticks
echo "--- TABLE: call ---\n";
$result = $conn->query("DESC `call`");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo "  Erreur: " . $conn->error . "\n";
}
echo "\n";

echo "=== EXAMPLE DATA ===\n\n";

// Exemple de bl
$result = $conn->query("SELECT * FROM bl LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "BL SAMPLE:\n";
    foreach ($row as $key => $value) {
        echo "  $key: " . substr($value, 0, 80) . "\n";
    }
    echo "\n";
}

// Exemple de invoiceitem
$result = $conn->query("SELECT * FROM invoiceitem LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "INVOICEITEM SAMPLE:\n";
    foreach ($row as $key => $value) {
        echo "  $key: " . substr($value, 0, 80) . "\n";
    }
    echo "\n";
}

$conn->close();
?>
