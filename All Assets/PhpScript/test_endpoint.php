<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== TEST DE L'ENDPOINT GetInvoiceDetails ===\n\n";

// Récupérer un invoice ID valide
$result = $conn->query("SELECT Id FROM invoice LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $invoice_id = $row['Id'];
    
    echo "Test avec InvoiceId: $invoice_id\n\n";
    
    // Appeler la procédure
    $result = $conn->query("CALL GetInvoiceDetails($invoice_id)");
    
    if ($result && $row = $result->fetch_assoc()) {
        echo "✅ Procédure fonctionne!\n";
        echo "Numéro de facture: " . $row['invoiceNumber'] . "\n";
        echo "Client: " . $row['client'] . "\n";
        echo "Montant: " . $row['totals'] . "\n";
    } else {
        echo "❌ Erreur lors de l'appel\n";
        echo "Erreur: " . $conn->error . "\n";
    }
} else {
    echo "❌ Aucune facture trouvée dans la base de données\n";
}

$conn->close();
?>
