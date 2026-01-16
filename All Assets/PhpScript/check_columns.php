<?php
// Vérifier les noms de colonnes

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $mysqli = new mysqli('localhost', 'root', '', 'ies');
    
    if ($mysqli->connect_error) {
        die("Erreur de connexion: " . $mysqli->connect_error);
    }
    
    echo "=== VÉRIFICATION DES COLONNES ===\n\n";
    
    // Tables importantes à vérifier
    $tables = [
        'blitem_jobfile',
        'event',
        'jobfile',
        'cartitem',
        'payment_invoice',
        'position',
        'row',
        'contract_eventtype'
    ];
    
    foreach ($tables as $table) {
        echo "Table: $table\n";
        echo "Colonnes:\n";
        
        $result = $mysqli->query("SHOW COLUMNS FROM $table");
        
        while ($row = $result->fetch_assoc()) {
            echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        echo "\n";
    }
    
    $mysqli->close();
    
} catch(Exception $exp) {
    echo "ERROR: " . $exp->getMessage();
}
?>
