<?php
// Connexion à la base de données
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Récupérer la définition de la procédure Register
$result = $conn->query("SHOW CREATE PROCEDURE Register");

if ($result) {
    $row = $result->fetch_assoc();
    echo "=== DÉFINITION DE LA PROCÉDURE REGISTER ===\n\n";
    echo $row['Create Procedure'];
} else {
    echo "Erreur: " . $conn->error;
}

$conn->close();
?>
