<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);

// Vérifier si la procédure existe
$result = $conn->query("SELECT ROUTINE_DEFINITION FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_NAME = 'GetInvoicesPerBLNumber' AND ROUTINE_SCHEMA = 'ies'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['ROUTINE_DEFINITION'];
}

$conn->close();
?>
