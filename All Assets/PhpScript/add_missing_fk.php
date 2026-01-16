<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

echo "=== AJOUT DES FK MANQUANTES CORRIGÉES ===\n\n";

$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

// FK à ajouter
$newConstraints = [
    ['table' => 'commodityitem', 'column' => 'BlItemId', 'ref_table' => 'blitem', 'ref_column' => 'Id'],
    ['table' => 'invoice', 'column' => 'BilledThirdPartyId', 'ref_table' => 'thirdparty', 'ref_column' => 'Id'],
    ['table' => 'invoice', 'column' => 'StatusId', 'ref_table' => 'status', 'ref_column' => 'Id'],
];

$success = 0;
$failed = 0;

foreach ($newConstraints as $idx => $fk) {
    $table = $fk['table'];
    $column = $fk['column'];
    $ref_table = $fk['ref_table'];
    $ref_column = $fk['ref_column'];
    $constraint_name = "FK_{$table}_{$column}";
    
    $sql = "ALTER TABLE `$table` ADD CONSTRAINT `$constraint_name` FOREIGN KEY (`$column`) REFERENCES `$ref_table` (`$ref_column`)";
    
    if ($mysqli->query($sql)) {
        echo "✓ $constraint_name\n";
        $success++;
    } else {
        echo "✗ ERREUR: " . $mysqli->error . "\n";
        $failed++;
    }
}

echo "\n=== INVENTAIRE COMPLET DES TABLES ===\n\n";

// Vérifier toutes les autres tables
$result = $mysqli->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'ies' ORDER BY TABLE_NAME");

$allTables = [];
while($row = $result->fetch_assoc()) {
    $allTables[] = $row['TABLE_NAME'];
}

echo "Total de tables: " . count($allTables) . "\n";
echo "Tables: " . implode(", ", $allTables) . "\n\n";

echo "=== TABLES AVEC FK DÉFINIES ===\n\n";

$fkResult = $mysqli->query("
    SELECT DISTINCT TABLE_NAME
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'ies'
    ORDER BY TABLE_NAME
");

$tablesWithFK = [];
while($row = $fkResult->fetch_assoc()) {
    $tablesWithFK[] = $row['TABLE_NAME'];
}

echo "Tables avec FK: " . implode(", ", $tablesWithFK) . "\n";
echo "Nombre: " . count($tablesWithFK) . "\n\n";

echo "=== TABLES SANS FK (À VÉRIFIER) ===\n\n";

$withoutFK = array_diff($allTables, $tablesWithFK);
echo implode(", ", $withoutFK) . "\n";

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
echo "\n✓ FOREIGN_KEY_CHECKS réactivé\n";

$mysqli->close();
?>
