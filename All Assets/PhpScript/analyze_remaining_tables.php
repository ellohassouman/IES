<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

// Vérifier les tables sans FK pour trouver d'éventuelles colonnes d'ID
echo "=== ANALYSE DES TABLES SANS FK ===\n\n";

$tablesToCheck = [
    'bl', 'customerusers', 'customerusers_thirdparty', 'document', 
    'eventtype', 'jobfile', 'payment', 'paymenttype', 
    'taxcodes', 'terminal', 'thirdparty', 'thirdparty_thirdpartytype', 
    'yarditemtype'
];

foreach ($tablesToCheck as $table) {
    $result = $mysqli->query("SHOW COLUMNS FROM `$table`");
    
    echo "Table: $table\n";
    $idColumns = [];
    
    while($row = $result->fetch_assoc()) {
        $field = $row['Field'];
        // Chercher les colonnes qui contiennent "Id" et qui ne sont pas la PK
        if (stripos($field, 'id') !== false && $field !== 'Id') {
            $idColumns[] = $field;
        }
    }
    
    if (!empty($idColumns)) {
        echo "  Colonnes FK potentielles: " . implode(', ', $idColumns) . "\n";
    } else {
        echo "  Aucune colonne FK potentielle\n";
    }
    echo "\n";
}

$mysqli->close();
?>
