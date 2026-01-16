<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

echo "=== RAPPORT FINAL DES CONTRAINTES FK ===\n\n";

// Vérifier l'état de FOREIGN_KEY_CHECKS
$result = $mysqli->query("SELECT @@FOREIGN_KEY_CHECKS");
$row = $result->fetch_assoc();
$fkEnabled = $row['@@FOREIGN_KEY_CHECKS'] == 1 ? 'ACTIVÉ' : 'DÉSACTIVÉ';
echo "FOREIGN_KEY_CHECKS: $fkEnabled\n\n";

// Compter les FK par table
$result = $mysqli->query("
    SELECT TABLE_NAME, COUNT(*) as fk_count
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'ies'
    GROUP BY TABLE_NAME
    ORDER BY TABLE_NAME
");

echo "=== CONTRAINTES FK PAR TABLE ===\n\n";
$totalFK = 0;
while($row = $result->fetch_assoc()) {
    echo "{$row['TABLE_NAME']}: {$row['fk_count']} FK\n";
    $totalFK += $row['fk_count'];
}

echo "\n=== TOTAL ===\n";
echo "Nombre total de contraintes FK: $totalFK\n";

// Lister toutes les FK
echo "\n=== LISTE COMPLÈTE DES CONTRAINTES FK ===\n\n";

$result = $mysqli->query("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'ies' 
    AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME, CONSTRAINT_NAME
");

while($row = $result->fetch_assoc()) {
    echo "✓ {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} → {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
}

$mysqli->close();
?>
