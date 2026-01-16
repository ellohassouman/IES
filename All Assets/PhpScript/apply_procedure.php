<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== MISE À JOUR DE LA PROCÉDURE GetInvoiceDetails ===\n\n";

// Lire le fichier SQL
$sql = file_get_contents('GetInvoiceDetails.sql');

// Supprimer la procédure d'abord
$conn->query("DROP PROCEDURE IF EXISTS GetInvoiceDetails");
echo "✓ Ancienne procédure supprimée\n";

// Exécuter avec délimiteur
$delimiter = "$$";
$sql_parts = array_filter(array_map('trim', explode($delimiter, $sql)));

foreach ($sql_parts as $part) {
    if (!empty($part) && $part !== 'DELIMITER $$') {
        if (!$conn->query($part)) {
            echo "❌ Erreur: " . $conn->error . "\n";
            echo "Requête: " . substr($part, 0, 100) . "...\n";
            exit(1);
        }
    }
}

echo "✓ Nouvelle procédure créée\n\n";

// Vérifier
$result = $conn->query("SHOW CREATE PROCEDURE GetInvoiceDetails");
if ($row = $result->fetch_assoc()) {
    if (preg_match('/PROCEDURE `?GetInvoiceDetails`?\((.*?)\)/', $row['Create Procedure'], $matches)) {
        echo "Signature correcte: GetInvoiceDetails(" . $matches[1] . ")\n\n";
    }
}

// Tester l'appel
echo "=== TEST ===\n";
$result = $conn->query("SELECT Id FROM invoice LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $id = $row['Id'];
    echo "Test avec InvoiceId: $id\n";
    
    $result = $conn->query("CALL GetInvoiceDetails($id)");
    if ($result) {
        $data = $result->fetch_assoc();
        if ($data) {
            echo "✅ Appel réussi!\n";
            echo "Champs retournés: " . count($data) . "\n";
        } else {
            echo "⚠ Pas de résultat (facture sans données)\n";
        }
    } else {
        echo "❌ Erreur: " . $conn->error . "\n";
    }
}

echo "\n✅ Procédure GetInvoiceDetails corrigée et mise à jour\n";

$conn->close();
?>
