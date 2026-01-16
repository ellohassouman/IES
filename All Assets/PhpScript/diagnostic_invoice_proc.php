<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== DIAGNOSTIC DE LA PROCÉDURE GetInvoiceDetails ===\n\n";

// Vérifier la procédure stockée en base
$result = $conn->query("SHOW CREATE PROCEDURE GetInvoiceDetails");
if ($result && $row = $result->fetch_assoc()) {
    echo "Procédure trouvée en base de données:\n\n";
    echo $row['Create Procedure'];
    echo "\n\n";
} else {
    echo "Erreur: " . $conn->error . "\n";
}

// Tester un appel
echo "\n=== TEST D'APPEL ===\n";
$result = $conn->query("SELECT Id FROM invoice LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $id = $row['Id'];
    echo "Test avec InvoiceId: $id\n\n";
    
    $result = $conn->query("CALL GetInvoiceDetails($id)");
    if ($result) {
        $data = $result->fetch_assoc();
        if ($data) {
            echo "✓ Appel réussi\n";
            echo "Colonnes retournées:\n";
            foreach (array_keys($data) as $col) {
                echo "  - $col\n";
            }
        } else {
            echo "❌ Pas de résultat\n";
        }
    } else {
        echo "❌ Erreur SQL: " . $conn->error . "\n";
    }
}

$conn->close();
?>
