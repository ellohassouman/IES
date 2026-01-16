<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== VÉRIFICATION DES FACTURES ===\n\n";

// Récupérer TOUTES les factures (incluant les supprimées logiquement)
$result = $conn->query("
    SELECT DISTINCT inv.Id, inv.InvoiceNumber, inv.StatusId, inv.Deleted, COUNT(ii.Id) as item_count
    FROM invoice inv
    LEFT JOIN invoiceitem ii ON inv.Id = ii.InvoiceId
    GROUP BY inv.Id
    ORDER BY inv.Id DESC
");

if ($result && $result->num_rows > 0) {
    echo "Factures trouvées: " . $result->num_rows . "\n\n";
    
    $invalid_invoices = [];
    
    while ($row = $result->fetch_assoc()) {
        $invoice_id = $row['Id'];
        $invoice_number = $row['InvoiceNumber'] ?: 'N/A';
        $item_count = $row['item_count'];
        $deleted_status = $row['Deleted'] ? '(DELETED)' : '';
        
        // Vérifier si cette facture respecte les conditions de GenerateProforma
        $check = $conn->query("
            SELECT COUNT(*) as valid_items
            FROM invoiceitem ii
            LEFT JOIN event e ON ii.EventId = e.Id
            LEFT JOIN contract_eventtype ce ON ce.EventType_Id = e.EventTypeId
            LEFT JOIN contract c ON c.Id = ce.Contract_Id
            LEFT JOIN subscription s ON s.ContractId = c.Id
            LEFT JOIN rate r ON r.Id = s.RateId
            LEFT JOIN rateperiod rp ON rp.RateId = r.Id AND rp.ToDate > NOW()
            LEFT JOIN raterangeperiod rpr ON rpr.RatePeriodId = rp.Id
            WHERE ii.InvoiceId = $invoice_id
            AND e.Id IS NOT NULL
            AND ce.EventType_Id IS NOT NULL
            AND c.Id IS NOT NULL
            AND s.Id IS NOT NULL
            AND rp.Id IS NOT NULL
            AND rpr.Id IS NOT NULL
        ");
        
        $check_row = $check->fetch_assoc();
        $valid_items = $check_row['valid_items'];
        
        echo "Facture #$invoice_id (N° $invoice_number): $item_count items $deleted_status";
        
        if ($valid_items > 0) {
            echo " - VALIDE ($valid_items items valides)\n";
        } else {
            echo " - INVALIDE ❌\n";
            $invalid_invoices[] = $invoice_id;
        }
    }
    
    if (count($invalid_invoices) > 0) {
        echo "\n=== SUPPRESSION PHYSIQUE DES FACTURES INVALIDES ===\n";
        echo "Factures à supprimer: " . count($invalid_invoices) . "\n";
        echo "IDs: " . implode(', ', $invalid_invoices) . "\n\n";
        
        // Supprimer les cartitems d'abord
        foreach ($invalid_invoices as $inv_id) {
            $conn->query("DELETE FROM cartitem WHERE InvoiceId = $inv_id");
            echo "✓ Cartitems supprimés pour facture #$inv_id\n";
        }
        
        // Supprimer les invoiceitems
        foreach ($invalid_invoices as $inv_id) {
            $conn->query("DELETE FROM invoiceitem WHERE InvoiceId = $inv_id");
            echo "✓ Invoiceitems supprimés pour facture #$inv_id\n";
        }
        
        // Supprimer les factures physiquement
        foreach ($invalid_invoices as $inv_id) {
            $conn->query("DELETE FROM invoice WHERE Id = $inv_id");
            echo "✓ Facture #$inv_id supprimée physiquement\n";
        }
        
        echo "\n✅ Suppression physique complète terminée!\n";
    } else {
        echo "\n✅ Toutes les factures respectent les conditions de GenerateProforma!\n";
    }
} else {
    echo "Aucune facture trouvée.\n";
}

$conn->close();
?>