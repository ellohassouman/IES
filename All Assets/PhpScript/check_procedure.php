<?php
$mysqli = new mysqli('localhost', 'root', '', 'ies');

if ($mysqli->connect_error) {
    die("Connexion échouée: " . $mysqli->connect_error);
}

echo "=== VÉRIFICATION DES PROCÉDURES ===\n\n";

// Vérifier les procédures existantes
$result = $mysqli->query("
    SELECT ROUTINE_NAME, ROUTINE_TYPE
    FROM INFORMATION_SCHEMA.ROUTINES
    WHERE ROUTINE_SCHEMA = 'ies'
    ORDER BY ROUTINE_NAME
");

echo "Procédures actuelles:\n";
while($row = $result->fetch_assoc()) {
    echo "- {$row['ROUTINE_NAME']} ({$row['ROUTINE_TYPE']})\n";
}

echo "\n=== CODE ACTUEL DE GetInvoiceDetails ===\n\n";

// Afficher le code de la procédure
$result = $mysqli->query("SHOW CREATE PROCEDURE GetInvoiceDetails");
if ($result) {
    $row = $result->fetch_assoc();
    echo $row['Create Procedure'];
} else {
    echo "ERREUR: " . $mysqli->error;
}

$mysqli->close();
?>
