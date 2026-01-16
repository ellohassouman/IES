<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== VÉRIFICATION COMPLÈTE DE L'ENDPOINT ===\n\n";

// Récupérer une facture avec des données réelles
$result = $conn->query("SELECT Id, InvoiceNumber FROM invoice WHERE Deleted = 0 LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $invoice_id = $row['Id'];
    $invoice_number = $row['InvoiceNumber'];
    
    echo "Facture trouvée:\n";
    echo "  ID: $invoice_id\n";
    echo "  Number: " . ($invoice_number ?: 'NULL') . "\n\n";
    
    // Appeler la procédure avec uniquement l'ID
    echo "Appel: CALL GetInvoiceDetails($invoice_id)\n\n";
    
    $result = $conn->query("CALL GetInvoiceDetails($invoice_id)");
    
    if ($result) {
        $data = $result->fetch_assoc();
        if ($data) {
            echo "✅ SUCCÈS - Données retournées:\n\n";
            
            echo "Informations générales:\n";
            echo "  invoiceId: " . $data['invoiceId'] . "\n";
            echo "  invoiceNumber: " . $data['invoiceNumber'] . "\n";
            echo "  client: " . $data['client'] . "\n";
            echo "  printedDate: " . $data['printedDate'] . "\n\n";
            
            echo "JSON Fields:\n";
            if (is_string($data['shipInfo'])) {
                echo "  shipInfo: JSON string ✓\n";
            } else {
                echo "  shipInfo: " . gettype($data['shipInfo']) . "\n";
            }
            
            if (is_string($data['rubrics'])) {
                echo "  rubrics: JSON string ✓\n";
            } else {
                echo "  rubrics: " . gettype($data['rubrics']) . "\n";
            }
            
            if (is_string($data['totals'])) {
                echo "  totals: JSON string ✓\n";
            } else {
                echo "  totals: " . gettype($data['totals']) . "\n";
            }
            
            echo "\n✅ L'endpoint est prêt à être utilisé\n";
        } else {
            echo "❌ Pas de résultat\n";
        }
    } else {
        echo "❌ Erreur SQL: " . $conn->error . "\n";
    }
} else {
    echo "❌ Aucune facture trouvée\n";
}

$conn->close();
?>
