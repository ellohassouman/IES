<?php
// Diagnostiquer les contraintes de clés étrangères

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $mysqli = new mysqli('localhost', 'root', '', 'ies');
    
    if ($mysqli->connect_error) {
        die("Erreur de connexion: " . $mysqli->connect_error);
    }
    
    echo "=== DIAGNOSTIC DES CONTRAINTES DE CLÉS ÉTRANGÈRES ===\n\n";
    
    // Vérifier l'état FOREIGN_KEY_CHECKS
    $result = $mysqli->query('SELECT @@foreign_key_checks as status');
    $row = $result->fetch_assoc();
    echo "État FOREIGN_KEY_CHECKS: " . ($row['status'] ? "ACTIVÉ" : "DÉSACTIVÉ") . "\n\n";
    
    // Récupérer toutes les contraintes FK existantes
    echo "--- Contraintes FK existantes ---\n";
    $sql = "SELECT 
                CONSTRAINT_NAME,
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'ies' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME, CONSTRAINT_NAME";
    
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "Nombre de contraintes FK trouvées: " . $result->num_rows . "\n\n";
        
        while ($row = $result->fetch_assoc()) {
            echo "  - " . $row['CONSTRAINT_NAME'] . "\n";
            echo "    Table: " . $row['TABLE_NAME'] . " (" . $row['COLUMN_NAME'] . ")\n";
            echo "    Référence: " . $row['REFERENCED_TABLE_NAME'] . " (" . $row['REFERENCED_COLUMN_NAME'] . ")\n\n";
        }
    } else {
        echo "❌ AUCUNE CONTRAINTE FK TROUVÉE!\n";
        echo "Les relations ne sont pas définies dans la base de données.\n\n";
    }
    
    // Récupérer les informations des tables InnoDB
    echo "--- Tables disponibles ---\n";
    $sql = "SELECT TABLE_NAME, ENGINE, TABLE_TYPE
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = 'ies'
            ORDER BY TABLE_NAME";
    
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "Nombre de tables: " . $result->num_rows . "\n\n";
        
        while ($row = $result->fetch_assoc()) {
            $engine = $row['ENGINE'] ? $row['ENGINE'] : 'N/A';
            echo "  - " . $row['TABLE_NAME'] . " (" . $engine . ")\n";
        }
    }
    
    $mysqli->close();
    
} catch(Exception $exp) {
    echo "ERROR: " . $exp->getMessage();
}
?>
