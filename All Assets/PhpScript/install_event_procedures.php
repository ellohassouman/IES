<?php
/**
 * Script pour créer les procédures stockées GetAllEventFamilies et GetAllEventTypes
 * dans la base de données IES
 * 
 * Exécution: php install_event_procedures.php
 */

// Configuration de la base de données
const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'ies';

// Connexion à la base de données
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("❌ Erreur de connexion: " . $conn->connect_error . "\n");
}

$conn->set_charset('utf8mb4');

// Lire le fichier SQL
$sql_file = __DIR__ . '/PROCEDURE_EVENT_FAMILIES_TYPES.sql';

if (!file_exists($sql_file)) {
    die("❌ Fichier non trouvé: $sql_file\n");
}

$sql_content = file_get_contents($sql_file);

// Affichage du titre
echo "════════════════════════════════════════════════════════════════\n";
echo "  INSTALLATION DES PROCÉDURES EVENT FAMILIES & TYPES\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Exécuter les requêtes SQL
if ($conn->multi_query($sql_content)) {
    echo "✅ Procédures créées avec succès!\n\n";
    
    // Consommer les résultats
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    // Vérifier que les procédures existent
    echo "📋 VÉRIFICATION DES PROCÉDURES INSTALLÉES:\n";
    echo str_repeat("─", 66) . "\n";
    
    $result = $conn->query("
        SELECT ROUTINE_NAME, ROUTINE_TYPE, CREATED, LAST_ALTERED
        FROM INFORMATION_SCHEMA.ROUTINES 
        WHERE ROUTINE_SCHEMA='ies' 
        AND ROUTINE_NAME IN ('GetAllEventFamilies', 'GetAllEventTypes')
        ORDER BY ROUTINE_NAME
    ");
    
    $found = 0;
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "✓ " . $row['ROUTINE_NAME'] . " (" . $row['ROUTINE_TYPE'] . ")\n";
            echo "  Créée: " . $row['CREATED'] . "\n";
            $found++;
        }
    }
    
    echo str_repeat("─", 66) . "\n";
    echo "\n";
    
    // Tester les procédures
    echo "🧪 TEST DES PROCÉDURES:\n";
    echo str_repeat("─", 66) . "\n";
    
    // Test GetAllEventFamilies
    echo "\n1️⃣  GetAllEventFamilies:\n";
    $result = $conn->query("CALL GetAllEventFamilies()");
    if ($result) {
        $families = [];
        while ($row = $result->fetch_assoc()) {
            $families[] = $row;
        }
        echo "   ✅ Exécutée avec succès\n";
        echo "   📊 Familles trouvées: " . count($families) . "\n";
        if (count($families) > 0) {
            echo "   Exemples: ";
            $examples = array_slice($families, 0, 3);
            echo implode(", ", array_map(function($f) { return $f['Label']; }, $examples));
            if (count($families) > 3) echo ", ...";
            echo "\n";
        }
        // Libérer les résultats en attente
        while ($conn->more_results() && $conn->next_result()) {
            if ($extra_result = $conn->store_result()) {
                $extra_result->free();
            }
        }
    } else {
        echo "   ❌ Erreur: " . $conn->error . "\n";
    }
    
    // Test GetAllEventTypes
    echo "\n2️⃣  GetAllEventTypes:\n";
    $result = $conn->query("CALL GetAllEventTypes()");
    if ($result) {
        $types = [];
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
        echo "   ✅ Exécutée avec succès\n";
        echo "   📊 Types trouvés: " . count($types) . "\n";
        if (count($types) > 0) {
            echo "   Exemples: ";
            $examples = array_slice($types, 0, 3);
            echo implode(", ", array_map(function($t) { return $t['Label']; }, $examples));
            if (count($types) > 3) echo ", ...";
            echo "\n";
        }
    } else {
        echo "   ❌ Erreur: " . $conn->error . "\n";
    }
    
    echo "\n" . str_repeat("═", 66) . "\n";
    echo "✅ Installation complétée avec succès!\n";
    echo "════════════════════════════════════════════════════════════════\n";
    
} else {
    echo "❌ Erreur lors de l'exécution: " . $conn->error . "\n";
    exit(1);
}

$conn->close();
?>
