<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== STRUCTURE: customeruserblsearchhistory ===\n";
$result = $conn->query("DESC customeruserblsearchhistory");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== EXEMPLE: customeruserblsearchhistory ===\n";
$result = $conn->query("SELECT * FROM customeruserblsearchhistory LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\n";
        foreach ($row as $key => $value) {
            echo "  $key: " . substr($value, 0, 100) . "\n";
        }
    }
}
echo "\n";

echo "=== STRUCTURE: bl ===\n";
$result = $conn->query("DESC bl");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== TOTAL ===\n";
$result = $conn->query("SELECT COUNT(*) as total FROM customeruserblsearchhistory");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total entrées d'historique: {$row['total']}\n";
}

echo "\n=== ANALYSE: BL search history vs clients assignés ===\n";
$result = $conn->query("
    SELECT 
        cubsh.UserId,
        cubsh.BlNumber,
        bl.ConsigneeId,
        CASE 
            WHEN cut.ThirdParty_Id IS NOT NULL THEN 'VALIDE'
            ELSE 'INVALIDE'
        END as status
    FROM customeruserblsearchhistory cubsh
    LEFT JOIN bl ON cubsh.BlNumber = bl.BlNumber
    LEFT JOIN customerusers_thirdparty cut ON bl.ConsigneeId = cut.ThirdParty_Id AND cubsh.UserId = cut.CustomerUsers_Id
    LIMIT 10
");
if ($result && $result->num_rows > 0) {
    echo "Exemples:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  User #{$row['UserId']} - BL {$row['BlNumber']} - Client #{$row['ConsigneeId']} - {$row['status']}\n";
    }
}
echo "\n";

echo "=== STATISTIQUES ===\n";
$result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN cut.ThirdParty_Id IS NULL THEN 1 ELSE 0 END) as invalides
    FROM customeruserblsearchhistory cubsh
    LEFT JOIN bl ON cubsh.BlNumber = bl.BlNumber
    LEFT JOIN customerusers_thirdparty cut ON bl.ConsigneeId = cut.ThirdParty_Id AND cubsh.UserId = cut.CustomerUsers_Id
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total entrées: {$row['total']}\n";
    echo "Entrées invalides (à supprimer): " . ($row['invalides'] ?? 0) . "\n";
}

$conn->close();
?>