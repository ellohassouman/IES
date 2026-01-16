<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

// Supprimer la procédure
$conn->query("DROP PROCEDURE IF EXISTS GetInvoiceDetails");

// Lire le fichier SQL et exécuter juste la création
$sql_file = file_get_contents('GetInvoiceDetails.sql');

// Supprimer le DROP du début
$sql_file = preg_replace('/DROP PROCEDURE IF EXISTS.*?;/i', '', $sql_file, 1);

// Exécuter avec delimiter
$result = $conn->query($sql_file);

if (!$result) {
    echo "Erreur: " . $conn->error . "\n";
    exit(1);
}

echo "✓ Procédure GetInvoiceDetails mise à jour avec succès\n";

$conn->close();
?>
