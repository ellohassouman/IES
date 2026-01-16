<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== STRUCTURE: customerusers_thirdparty ===\n";
$result = $conn->query("DESC customerusers_thirdparty");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== STRUCTURE: customerusers ===\n";
$result = $conn->query("DESC customerusers");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== EXEMPLE: customerusers_thirdparty ===\n";
$result = $conn->query("SELECT * FROM customerusers_thirdparty LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\n";
        foreach ($row as $key => $value) {
            echo "  $key: " . $value . "\n";
        }
    }
}
echo "\n";

echo "=== ANALYSE: Clients par utilisateur ===\n";
$result = $conn->query("
    SELECT cu.Id, COUNT(cut.ThirdParty_Id) as client_count
    FROM customerusers cu
    LEFT JOIN customerusers_thirdparty cut ON cu.Id = cut.CustomerUsers_Id
    GROUP BY cu.Id
    ORDER BY client_count DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Utilisateur #{$row['Id']}: {$row['client_count']} clients\n";
    }
}
echo "\n";

echo "=== ANALYSE: Utilisateurs par client (doublons) ===\n";
$result = $conn->query("
    SELECT tp.Id, tp.Label, COUNT(cut.CustomerUsers_Id) as user_count, 
           GROUP_CONCAT(DISTINCT cut.CustomerUsers_Id) as user_ids
    FROM thirdparty tp
    LEFT JOIN customerusers_thirdparty cut ON tp.Id = cut.ThirdParty_Id
    GROUP BY tp.Id
    HAVING user_count > 1
    ORDER BY user_count DESC
");
if ($result && $result->num_rows > 0) {
    echo "Clients assignés à plusieurs utilisateurs:\n";
    while ($row = $result->fetch_assoc()) {
        echo "Client #{$row['Id']} ({$row['Label']}): assigné à {$row['user_count']} utilisateurs (IDs: {$row['user_ids']})\n";
    }
} else {
    echo "Aucun client assigné à plusieurs utilisateurs.\n";
}
echo "\n";

echo "=== TOTAL ===\n";
$result = $conn->query("SELECT COUNT(*) as total FROM customerusers_thirdparty");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total associations: {$row['total']}\n";
}

$conn->close();
?>