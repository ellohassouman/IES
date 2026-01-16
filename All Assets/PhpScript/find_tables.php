<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== TOUTES LES TABLES ===\n";
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_row()) {
    echo "  - {$row[0]}\n";
    $tables[] = $row[0];
}

// Chercher les tables liées aux factures
echo "\n=== TABLES FACTURES (invoice/bl) ===\n";
foreach ($tables as $table) {
    if (strpos(strtolower($table), 'invoice') !== false || strpos(strtolower($table), 'bl') !== false || strpos(strtolower($table), 'lading') !== false) {
        echo "\n--- TABLE: $table ---\n";
        $result = $conn->query("DESC $table");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                echo "  {$row['Field']} ({$row['Type']})\n";
            }
        }
    }
}

$conn->close();
?>
