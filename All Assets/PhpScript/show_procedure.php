<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

// Vérifier quelle procédure est en base
$result = $conn->query("SHOW CREATE PROCEDURE GetInvoiceDetails");
if ($result && $row = $result->fetch_assoc()) {
    echo "Procédure stockée:\n\n";
    echo $row['Create Procedure'];
} else {
    echo "Erreur: " . $conn->error . "\n";
}

$conn->close();
?>
