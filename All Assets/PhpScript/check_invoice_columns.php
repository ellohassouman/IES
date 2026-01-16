<?php
$conn = mysqli_connect('localhost', 'root', '', 'ies');
if (!$conn) die("Connexion échouée: " . mysqli_connect_error());

echo "=== COLONNES DE LA TABLE INVOICE ===\n\n";

$result = mysqli_query($conn, "DESCRIBE invoice");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

mysqli_close($conn);
?>
