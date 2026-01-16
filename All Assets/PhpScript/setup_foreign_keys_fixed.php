<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

// Désactiver temporairement les vérifications de clés étrangères
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

echo "=== SUPPRESSION DES CONTRAINTES FK DUPLIQUÉES ===\n\n";

// Récupérer toutes les FK existantes
$result = $mysqli->query("
    SELECT CONSTRAINT_NAME, TABLE_NAME
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'ies'
");

$existingFKs = [];
while($row = $result->fetch_assoc()) {
    $existingFKs[$row['TABLE_NAME']][] = $row['CONSTRAINT_NAME'];
}

// Supprimer les doublons connus
$toDelete = [
    'area' => ['FK_area_TerminalId', 'FK_area_19d4d'],
    'contract' => ['FK_contract_bd89c', 'FK_contract_TaxCodeId']
];

foreach ($toDelete as $table => $constraints) {
    if (!isset($existingFKs[$table])) continue;
    
    foreach ($constraints as $constraint) {
        if (in_array($constraint, $existingFKs[$table])) {
            $mysqli->query("ALTER TABLE $table DROP FOREIGN KEY $constraint");
            if ($mysqli->error === '') {
                echo "✓ Supprimé: $table.$constraint\n";
            }
        }
    }
}

echo "\n=== CRÉATION DES CONTRAINTES FK CORRIGES ===\n\n";

// Définir toutes les FK correctes
$constraints = [
    // Tables simples avec FK simples
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

foreach ($constraints as $fk) {
    $table = $fk['table'];
    $column = $fk['column'];
    $ref_table = $fk['ref_table'];
    $ref_column = $fk['ref_column'];
    $constraint_name = "FK_{$table}_{$column}";
    
    // Vérifier d'abord que la colonne existe
    $checkColumn = $mysqli->query("SHOW COLUMNS FROM $table WHERE Field = '$column'");
    if ($checkColumn->num_rows === 0) {
        echo "✗ COLONNE MANQUANTE: $table.$column n'existe pas\n";
        $failed++;
        continue;
    }
    
    // Vérifier que la table référencée existe
    $checkRefTable = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'ies' AND TABLE_NAME = '$ref_table'");
    if ($checkRefTable->num_rows === 0) {
        echo "✗ TABLE MANQUANTE: $ref_table n'existe pas\n";
        $failed++;
        continue;
    }
    
    // Créer la contrainte
    $sql = "ALTER TABLE $table ADD CONSTRAINT $constraint_name FOREIGN KEY ($column) REFERENCES $ref_table ($ref_column)";
    
    if ($mysqli->query($sql)) {
        echo "✓ FK_{$table}_{$column}: $table.$column → $ref_table.$ref_column\n";
        $success++;
    } else {
        echo "✗ ERREUR #{$failed}: " . $mysqli->error . "\n   (SQL: $sql)\n";
        $failed++;
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "Contraintes créées avec succès: $success\n";
echo "Erreurs: $failed\n";

// Réactiver les vérifications de clés étrangères
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
echo "\nFOREIGN_KEY_CHECKS réactivé: OUI\n";

$mysqli->close();
?>
