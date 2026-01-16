<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== DISTRIBUTION DES CLIENTS RESTANTS ===\n\n";

// Récupérer les clients déjà assignés
$assigned_clients = [];
$result = $conn->query("SELECT DISTINCT ThirdParty_Id FROM customerusers_thirdparty");
while ($row = $result->fetch_assoc()) {
    $assigned_clients[] = $row['ThirdParty_Id'];
}
echo "Clients déjà assignés: " . count($assigned_clients) . "\n";
echo "IDs: " . implode(', ', $assigned_clients) . "\n\n";

// Récupérer tous les clients
$all_clients = [];
$result = $conn->query("SELECT Id FROM thirdparty ORDER BY Id");
while ($row = $result->fetch_assoc()) {
    $all_clients[] = $row['Id'];
}

// Trouver les clients non assignés
$unassigned_clients = array_diff($all_clients, $assigned_clients);
echo "Clients à distribuer: " . count($unassigned_clients) . "\n";
echo "IDs: " . implode(', ', $unassigned_clients) . "\n\n";

// Récupérer les utilisateurs actifs
$users = $conn->query("
    SELECT Id FROM customerusers 
    WHERE CustomerUsersStatusId = 1 
    ORDER BY Id
");
$user_list = [];
while ($row = $users->fetch_assoc()) {
    $user_list[] = $row['Id'];
}
echo "Utilisateurs actifs: " . count($user_list) . "\n";
echo "IDs: " . implode(', ', $user_list) . "\n\n";

// Distribuer les clients restants de manière circulaire
echo "=== DISTRIBUTION ===\n\n";
$user_index = 0;
$inserted = 0;

foreach ($unassigned_clients as $client_id) {
    $user_id = $user_list[$user_index % count($user_list)];
    
    // Vérifier que ce client n'est pas déjà assigné à cet utilisateur
    $check = $conn->query("
        SELECT COUNT(*) as count FROM customerusers_thirdparty 
        WHERE CustomerUsers_Id = $user_id AND ThirdParty_Id = $client_id
    ");
    $check_row = $check->fetch_assoc();
    
    if ($check_row['count'] == 0) {
        $conn->query("
            INSERT INTO customerusers_thirdparty (CustomerUsers_Id, ThirdParty_Id)
            VALUES ($user_id, $client_id)
        ");
        echo "✓ Client #$client_id assigné à utilisateur #$user_id\n";
        $inserted++;
    }
    
    $user_index++;
}

echo "\nAssociations créées: $inserted\n\n";

echo "=== VÉRIFICATION FINALE ===\n\n";

echo "Clients par utilisateur:\n";
$result = $conn->query("
    SELECT cu.Id, COUNT(cut.ThirdParty_Id) as client_count, 
           GROUP_CONCAT(cut.ThirdParty_Id) as client_ids
    FROM customerusers cu
    LEFT JOIN customerusers_thirdparty cut ON cu.Id = cut.CustomerUsers_Id
    WHERE cu.CustomerUsersStatusId = 1
    GROUP BY cu.Id
    ORDER BY client_count DESC
");
while ($row = $result->fetch_assoc()) {
    if ($row['client_count'] > 0) {
        echo "  Utilisateur #{$row['Id']}: {$row['client_count']} clients (IDs: {$row['client_ids']})\n";
    }
}

echo "\nUtilisateurs par client:\n";
$result = $conn->query("
    SELECT tp.Id, tp.Label, COUNT(cut.CustomerUsers_Id) as user_count
    FROM thirdparty tp
    LEFT JOIN customerusers_thirdparty cut ON tp.Id = cut.ThirdParty_Id
    GROUP BY tp.Id
    HAVING user_count > 0
    ORDER BY tp.Id
");

$clients_with_multiple = 0;
while ($row = $result->fetch_assoc()) {
    if ($row['user_count'] > 1) {
        echo "  ⚠️ Client #{$row['Id']} ({$row['Label']}): {$row['user_count']} utilisateurs (DOUBLON!)\n";
        $clients_with_multiple++;
    }
}

if ($clients_with_multiple == 0) {
    echo "  ✅ Aucun client assigné à plusieurs utilisateurs\n";
}

echo "\nClients sans utilisateur:\n";
$result = $conn->query("
    SELECT tp.Id, tp.Label
    FROM thirdparty tp
    LEFT JOIN customerusers_thirdparty cut ON tp.Id = cut.ThirdParty_Id
    WHERE cut.ThirdParty_Id IS NULL
");

$unassigned_count = $result->num_rows;
if ($unassigned_count > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  Client #{$row['Id']} ({$row['Label']})\n";
    }
} else {
    echo "  ✅ Tous les clients sont assignés!\n";
}

echo "\n✅ Distribution terminée!\n";

$conn->close();
?>