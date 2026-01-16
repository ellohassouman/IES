<?php
$conn = mysqli_connect('localhost', 'root', '', 'ies');
if (!$conn) die("Connexion échouée: " . mysqli_connect_error());

$backquote = '`';

echo "=== VÉRIFICATION DE LA STRUCTURE CALL ===\n\n";

$result = mysqli_query($conn, "DESCRIBE " . $backquote . "call" . $backquote . "");
echo "Colonnes de la table call:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== TEST DE RÉCUPÉRATION DE CALL ===\n\n";

$testQuery = "SELECT 
    bl.Id,
    bl.CallId,
    c.Id AS callId,
    c.CallNumber,
    c.VesselArrivalDate,
    c.VesselDepatureDate
FROM bl
LEFT JOIN " . $backquote . "call" . $backquote . " c ON bl.CallId = c.Id
LIMIT 3";

$result = mysqli_query($conn, $testQuery);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "BL ID: " . $row['Id'] . "\n";
        echo "  - CallNumber: " . $row['CallNumber'] . "\n";
        echo "  - VesselArrivalDate: " . $row['VesselArrivalDate'] . "\n";
        echo "  - VesselDepatureDate: " . $row['VesselDepatureDate'] . "\n\n";
    }
}

mysqli_close($conn);
?>
