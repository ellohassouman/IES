<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

echo "=== PHASE 1: SUPPRESSION DE TOUTES LES FK EXISTANTES ===\n\n";

$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

// Récupérer toutes les FK existantes
$result = $mysqli->query("
    SELECT CONSTRAINT_NAME, TABLE_NAME
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'ies'
");

$deletedCount = 0;
while($row = $result->fetch_assoc()) {
    $sql = "ALTER TABLE {$row['TABLE_NAME']} DROP FOREIGN KEY {$row['CONSTRAINT_NAME']}";
    if ($mysqli->query($sql)) {
        echo "✓ Supprimé: {$row['TABLE_NAME']}.{$row['CONSTRAINT_NAME']}\n";
        $deletedCount++;
    } else {
        echo "⚠ Erreur lors de la suppression de {$row['CONSTRAINT_NAME']}: " . $mysqli->error . "\n";
    }
}

echo "\nTotal supprimé: $deletedCount FK\n";

echo "\n=== PHASE 2: CRÉATION DES NOUVELLES FK ===\n\n";

// Définir toutes les FK correctes
$constraints = [
    // Tables simples
    ['table' => 'area', 'column' => 'TerminalId', 'ref_table' => 'terminal', 'ref_column' => 'Id'],
    ['table' => 'blitem', 'column' => 'BlId', 'ref_table' => 'bl', 'ref_column' => 'Id'],
    ['table' => 'contract', 'column' => 'TaxCodeId', 'ref_table' => 'taxcodes', 'ref_column' => 'Id'],
    
    // Tables de liaison
    ['table' => 'blitem_jobfile', 'column' => 'BLItem_Id', 'ref_table' => 'blitem', 'ref_column' => 'Id'],
    ['table' => 'blitem_jobfile', 'column' => 'JobFile_Id', 'ref_table' => 'jobfile', 'ref_column' => 'Id'],
    ['table' => 'contract_eventtype', 'column' => 'Contract_Id', 'ref_table' => 'contract', 'ref_column' => 'Id'],
    ['table' => 'contract_eventtype', 'column' => 'EventType_Id', 'ref_table' => 'eventtype', 'ref_column' => 'Id'],
    
    // Tables de panier
    ['table' => 'cart', 'column' => 'CustomerUserId', 'ref_table' => 'customerusers', 'ref_column' => 'Id'],
    ['table' => 'cartitem', 'column' => 'CartId', 'ref_table' => 'cart', 'ref_column' => 'Id'],
    ['table' => 'cartitem', 'column' => 'InvoiceId', 'ref_table' => 'invoice', 'ref_column' => 'Id'],
    
    // Tables de commodités
    ['table' => 'commodity', 'column' => 'CommodityTypeId', 'ref_table' => 'commoditytype', 'ref_column' => 'Id'],
    ['table' => 'commodityitem', 'column' => 'CommodityId', 'ref_table' => 'commodity', 'ref_column' => 'Id'],
    ['table' => 'commodityitem', 'column' => 'InvoiceItemId', 'ref_table' => 'invoiceitem', 'ref_column' => 'Id'],
    
    // Tables d'événements
    ['table' => 'event', 'column' => 'JobFileId', 'ref_table' => 'jobfile', 'ref_column' => 'Id'],
    ['table' => 'event', 'column' => 'EventTypeId', 'ref_table' => 'eventtype', 'ref_column' => 'Id'],
    
    // Tables de factures
    ['table' => 'invoice', 'column' => 'ContractId', 'ref_table' => 'contract', 'ref_column' => 'Id'],
    ['table' => 'invoiceitem', 'column' => 'InvoiceId', 'ref_table' => 'invoice', 'ref_column' => 'Id'],
    
    // Tables de paiements
    ['table' => 'payment', 'column' => 'CustomerUserId', 'ref_table' => 'customerusers', 'ref_column' => 'Id'],
    ['table' => 'payment_invoice', 'column' => 'Payment_Id', 'ref_table' => 'payment', 'ref_column' => 'Id'],
    ['table' => 'payment_invoice', 'column' => 'Invoice_Id', 'ref_table' => 'invoice', 'ref_column' => 'Id'],
    
    // Tables de positions
    ['table' => 'position', 'column' => 'RowId', 'ref_table' => 'row', 'ref_column' => 'Id'],
];

$success = 0;
$failed = 0;

foreach ($constraints as $idx => $fk) {
    $table = $fk['table'];
    $column = $fk['column'];
    $ref_table = $fk['ref_table'];
    $ref_column = $fk['ref_column'];
    $constraint_name = "FK_{$table}_{$column}";
    
    // Vérifier que la colonne existe
    $checkColumn = $mysqli->query("SHOW COLUMNS FROM `$table` WHERE Field = '$column'");
    if ($checkColumn->num_rows === 0) {
        echo "✗ #" . ($idx+1) . " COLONNE MANQUANTE: $table.$column\n";
        $failed++;
        continue;
    }
    
    // Créer la contrainte
    $sql = "ALTER TABLE `$table` ADD CONSTRAINT `$constraint_name` FOREIGN KEY (`$column`) REFERENCES `$ref_table` (`$ref_column`)";
    
    if ($mysqli->query($sql)) {
        echo "✓ #" . ($idx+1) . " $table.$column → $ref_table.$ref_column\n";
        $success++;
    } else {
        echo "✗ #" . ($idx+1) . " ERREUR: " . substr($mysqli->error, 0, 80) . "\n";
        $failed++;
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "Supprimées: $deletedCount\n";
echo "Créées: $success\n";
echo "Erreurs: $failed\n";

// Réactiver les vérifications
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
echo "\nFOREIGN_KEY_CHECKS: ACTIVÉ\n";

$mysqli->close();
?>
