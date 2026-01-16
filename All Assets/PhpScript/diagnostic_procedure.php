<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== DIAGNOSTIC ===\n\n";

// Vérifier les invoices
$result = $conn->query("SELECT Id, InvoiceNumber FROM invoice LIMIT 3");
echo "Invoices trouvés:\n";
while ($row = $result->fetch_assoc()) {
    echo "  ID: {$row['Id']}, Number: {$row['InvoiceNumber']}\n";
}

// Tester la procédure avec un ID connu
$result = $conn->query("SELECT Id FROM invoice LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $id = $row['Id'];
    echo "\nTest de la procédure avec InvoiceId: $id\n";
    
    // Vérifier que la procédure existe
    $check = $conn->query("SHOW CREATE PROCEDURE GetInvoiceDetails");
    if ($check) {
        echo "Procédure existe: OUI\n";
    } else {
        echo "Procédure existe: NON\n";
        echo "Erreur: " . $conn->error . "\n";
    }
    
    // Appeler la procédure
    $conn->query("SET SESSION sql_mode='TRADITIONAL'");
    $result = $conn->query("CALL GetInvoiceDetails($id)");
    
    if ($result === false) {
        echo "Erreur SQL: " . $conn->error . "\n";
    } else {
        $row = $result->fetch_assoc();
        if ($row) {
            echo "✓ Résultat obtenu\n";
            echo "  invoiceNumber: " . $row['invoiceNumber'] . "\n";
            echo "  client: " . $row['client'] . "\n";
        } else {
            echo "Pas de résultat\n";
        }
    }
}

$conn->close();
?>
