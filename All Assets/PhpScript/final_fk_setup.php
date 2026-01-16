<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

echo "=== CRÉATION DES FK MANQUANTES ===\n\n";

$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

// Toutes les FK à créer
$newConstraints = [
    // bl
    ['table' => 'bl', 'column' => 'ConsigneeId', 'ref_table' => 'thirdparty', 'ref_column' => 'Id'],
    ['table' => 'bl', 'column' => 'RelatedCustomerId', 'ref_table' => 'thirdparty', 'ref_column' => 'Id'],
    ['table' => 'bl', 'column' => 'CallId', 'ref_table' => 'call', 'ref_column' => 'Id'],
    
    // customerusers
    ['table' => 'customerusers', 'column' => 'CustomerUsersStatusId', 'ref_table' => 'customerusersstatus', 'ref_column' => 'Id'],
    ['table' => 'customerusers', 'column' => 'CustomerUsersTypeId', 'ref_table' => 'customeruserstype', 'ref_column' => 'Id'],
    
    // customerusers_thirdparty
    ['table' => 'customerusers_thirdparty', 'column' => 'CustomerUsers_Id', 'ref_table' => 'customerusers', 'ref_column' => 'Id'],
    ['table' => 'customerusers_thirdparty', 'column' => 'ThirdParty_Id', 'ref_table' => 'thirdparty', 'ref_column' => 'Id'],
    
    // document
    ['table' => 'document', 'column' => 'BlId', 'ref_table' => 'bl', 'ref_column' => 'Id'],
    ['table' => 'document', 'column' => 'JobFileId', 'ref_table' => 'jobfile', 'ref_column' => 'Id'],
    ['table' => 'document', 'column' => 'DocumentTypeId', 'ref_table' => 'documenttype', 'ref_column' => 'Id'],
    
    // eventtype
    ['table' => 'eventtype', 'column' => 'FamilyId', 'ref_table' => 'family', 'ref_column' => 'Id'],
    
    // jobfile
    ['table' => 'jobfile', 'column' => 'ShippingLineId', 'ref_table' => 'thirdparty', 'ref_column' => 'Id'],
    ['table' => 'jobfile', 'column' => 'PositionId', 'ref_table' => 'position', 'ref_column' => 'Id'],
    
    // payment
    ['table' => 'payment', 'column' => 'PaymentTypeId', 'ref_table' => 'paymenttype', 'ref_column' => 'Id'],
    
    // thirdparty_thirdpartytype
    ['table' => 'thirdparty_thirdpartytype', 'column' => 'ThirdParty_Id', 'ref_table' => 'thirdparty', 'ref_column' => 'Id'],
    ['table' => 'thirdparty_thirdpartytype', 'column' => 'ThirdPartyType_Id', 'ref_table' => 'thirdpartytype', 'ref_column' => 'Id'],
];

$success = 0;
$failed = 0;

foreach ($newConstraints as $idx => $fk) {
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
    
    $sql = "ALTER TABLE `$table` ADD CONSTRAINT `$constraint_name` FOREIGN KEY (`$column`) REFERENCES `$ref_table` (`$ref_column`)";
    
    if ($mysqli->query($sql)) {
        echo "✓ #" . ($idx+1) . " $table.$column → $ref_table.$ref_column\n";
        $success++;
    } else {
        echo "✗ #" . ($idx+1) . " ERREUR: " . substr($mysqli->error, 0, 60) . "\n";
        $failed++;
    }
}

echo "\n=== RÉSUMÉ FINAL ===\n";
echo "Créées: $success\n";
echo "Erreurs: $failed\n";

// Réactiver les vérifications
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

// Compter les FK totales
$result = $mysqli->query("
    SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'ies'
");
$row = $result->fetch_assoc();
echo "Total de FK dans la base: " . $row['cnt'] . "\n";

echo "\n✓ FOREIGN_KEY_CHECKS réactivé\n";

$mysqli->close();
?>
