<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

// Vérifier les colonnes manquantes
$checks = [
    'commodity' => ['CommodityTypeId', 'CommodityTypeId', 'commoditytype', 'Id'],
    'commodityitem' => ['InvoiceItemId', 'InvoiceItemId', 'invoiceitem', 'Id'],
    'invoice' => ['ContractId', 'ContractId', 'contract', 'Id'],
    'payment' => ['CustomerUserId', 'CustomerUserId', 'customerusers', 'Id']
];

echo "=== VÉRIFICATION DES COLONNES MANQUANTES ===\n\n";

foreach ($checks as $table => $info) {
    echo "Table: $table\n";
    $result = $mysqli->query("SHOW COLUMNS FROM `$table`");
    
    $columns = [];
    while($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    echo "  Colonnes trouvées: " . implode(', ', $columns) . "\n";
    
    // Chercher des colonnes similaires
    foreach ($columns as $col) {
        if (stripos($col, 'commodity') !== false || 
            stripos($col, 'invoiceitem') !== false || 
            stripos($col, 'contract') !== false ||
            stripos($col, 'customer') !== false) {
            echo "  >> Similaire: $col\n";
        }
    }
    echo "\n";
}

$mysqli->close();
?>
