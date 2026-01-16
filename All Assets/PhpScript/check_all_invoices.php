<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== TOUTES LES FACTURES EN BASE ===\n\n";

// Récupérer TOUTES les factures sans filtre
$result = $conn->query("
    SELECT inv.Id, inv.InvoiceNumber, inv.StatusId, inv.Deleted, COUNT(ii.Id) as item_count
    FROM invoice inv
    LEFT JOIN invoiceitem ii ON inv.Id = ii.InvoiceId
    GROUP BY inv.Id
    ORDER BY inv.Id DESC
");

if ($result && $result->num_rows > 0) {
    echo "Total factures: " . $result->num_rows . "\n\n";
    
    while ($row = $result->fetch_assoc()) {
        $deleted = $row['Deleted'] ? '(DELETED)' : '';
        echo "ID: {$row['Id']} | N°: {$row['InvoiceNumber']} | Status: {$row['StatusId']} | Items: {$row['item_count']} $deleted\n";
    }
} else {
    echo "Aucune facture trouvée.\n";
}

echo "\n=== STATISTIQUES ===\n";

$result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN Deleted = 0 THEN 1 ELSE 0 END) as actives,
        SUM(CASE WHEN Deleted = 1 THEN 1 ELSE 0 END) as deleted
    FROM invoice
");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Total: {$row['total']}\n";
    echo "Actives: {$row['actives']}\n";
    echo "Supprimées (Deleted=1): {$row['deleted']}\n";
}

$conn->close();
?>