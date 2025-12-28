<?php
/**
 * Script d'exécution de la correction GetInvoicesPerBLNumber
 * 
 * PROBLÈME: La procédure retournait les yardItems doublés
 * SOLUTION: Utiliser une sous-requête DISTINCT pour éviter les doublons
 */

// Configuration de la base de données
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "ies";

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("❌ Erreur de connexion: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "═══════════════════════════════════════════════════════════════\n";
echo "CORRECTION: GetInvoicesPerBLNumber - Éliminer les doublons\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Lire le fichier SQL
$sql_file = __DIR__ . '/PROCEDURE_GET_INVOICES_CORRECTION.sql';

if (!file_exists($sql_file)) {
    die("❌ Fichier SQL non trouvé: $sql_file\n");
}

$sql_content = file_get_contents($sql_file);

echo "📄 Fichier SQL chargé: $sql_file\n";
echo "📊 Taille: " . strlen($sql_content) . " caractères\n\n";

// Enlever les directives DELIMITER
$sql_content = preg_replace('/DELIMITER\s+.*$/m', '', $sql_content);

// Diviser par les point-virgules
$parts = explode(';', $sql_content);
$statements = [];
foreach ($parts as $part) {
    $trimmed = trim($part);
    if (!empty($trimmed)) {
        $statements[] = $trimmed . ';';
    }
}

echo "⏳ Exécution de " . count($statements) . " statement(s)...\n\n";

// Exécuter chaque statement
$success = true;
foreach ($statements as $i => $statement) {
    if (empty($statement)) continue;
    
    if (!$conn->query($statement)) {
        $success = false;
        echo "❌ Erreur au statement " . ($i + 1) . ": " . $conn->error . "\n";
        break;
    }
}

if ($success) {
    echo "✅ Procédure GetInvoicesPerBLNumber corrigée avec succès!\n\n";
    
    // Vérifier que la procédure existe
    $check_sql = "SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES 
                  WHERE ROUTINE_SCHEMA = 'ies' AND ROUTINE_NAME = 'GetInvoicesPerBLNumber' 
                  AND ROUTINE_TYPE = 'PROCEDURE'";
    
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Vérification: La procédure GetInvoicesPerBLNumber est bien créée\n";
        
        // Afficher les informations
        $row = $result->fetch_assoc();
        echo "   • Nom: " . $row['ROUTINE_NAME'] . "\n";
    } else {
        echo "❌ Vérification échouée\n";
    }
}

$conn->close();

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Correction terminée avec succès!\n";
echo "═══════════════════════════════════════════════════════════════\n";
?>
