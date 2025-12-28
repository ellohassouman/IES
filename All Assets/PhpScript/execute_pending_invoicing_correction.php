<?php
/**
 * Script d'exécution de la correction de procédure
 * GetPendingInvoicingItemsPerBLNumber
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
$sql_file = __DIR__ . '/PROCEDURE_GET_PENDING_INVOICING_CORRECTION.sql';

if (!file_exists($sql_file)) {
    die("❌ Fichier non trouvé: $sql_file\n");
}

$sql_content = file_get_contents($sql_file);

// Exécuter les requêtes SQL
echo "════════════════════════════════════════════════════════════════\n";
echo "  CORRECTION DE PROCÉDURE: GetPendingInvoicingItemsPerBLNumber\n";
echo "════════════════════════════════════════════════════════════════\n\n";

if ($conn->multi_query($sql_content)) {
    echo "✅ Procédure 'GetPendingInvoicingItemsPerBLNumber' mise à jour!\n\n";
    
    // Consommer les résultats
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "📋 RÉSUMÉ DES MODIFICATIONS:\n";
    echo "   • Procédure: GetPendingInvoicingItemsPerBLNumber\n";
    echo "   • Correction: Jointure avec invoice dans NOT EXISTS\n";
    echo "   • Vérification: invoice.deleted = 0\n";
    echo "   • Impact: Les items facturés (supprimés) ne seront pas comptés\n\n";
    
    echo "✓ La procédure est prête à être utilisée!\n";
} else {
    echo "❌ Erreur lors de l'exécution: " . $conn->error . "\n";
    exit(1);
}

$conn->close();
?>
