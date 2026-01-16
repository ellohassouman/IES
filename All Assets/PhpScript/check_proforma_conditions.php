<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

// Récupérer la définition de GenerateProforma
$result = $conn->query("SHOW CREATE PROCEDURE GenerateProforma");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['Create Procedure'];
}

$conn->close();
?>