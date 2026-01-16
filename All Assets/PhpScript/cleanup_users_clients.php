<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== NETTOYAGE DES ASSOCIATIONS UTILISATEUR-CLIENT ===\n\n";

// Récupérer tous les clients distincts
$clients = $conn->query("SELECT DISTINCT ThirdParty_Id FROM customerusers_thirdparty ORDER BY ThirdParty_Id");
$client_list = [];
while ($row = $clients->fetch_assoc()) {
    $client_list[] = $row['ThirdParty_Id'];
}

echo "Total clients uniques: " . count($client_list) . "\n";
echo "Total utilisateurs: ";

$user_count = $conn->query("SELECT COUNT(DISTINCT CustomerUsers_Id) as count FROM customerusers_thirdparty");
$user_row = $user_count->fetch_assoc();
echo $user_row['count'] . "\n\n";

// Vider la table
echo "Suppression de toutes les associations actuelles...\n";
$conn->query("DELETE FROM customerusers_thirdparty");
echo "✓ Table vidée\n\n";

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

echo "Utilisateurs actifs trouvés: " . count($user_list) . "\n";

// Distribuer les clients : max 2 par utilisateur, aucun client chez plusieurs personnes
$client_index = 0;
$clients_per_user = 2;
$associations = [];

// Créer une distribution circulaire des clients parmi les utilisateurs
for ($i = 0; $i < count($user_list); $i++) {
    $user_id = $user_list[$i];
    $user_clients_count = 0;
    
    // Assigner jusqu'à 2 clients à cet utilisateur
    while ($user_clients_count < $clients_per_user && $client_index < count($client_list)) {
        $client_id = $client_list[$client_index];
        
        // Vérifier que ce client n'est pas déjà assigné
        if (!in_array(['user' => $user_id, 'client' => $client_id], $associations)) {
            $associations[] = ['user' => $user_id, 'client' => $client_id];
            $user_clients_count++;
        }
        
        $client_index++;
    }
}

echo "Associations à créer: " . count($associations) . "\n\n";

// Insérer les associations
$inserted = 0;
foreach ($associations as $assoc) {
    $result = $conn->query("
        INSERT INTO customerusers_thirdparty (CustomerUsers_Id, ThirdParty_Id)
        VALUES ({$assoc['user']}, {$assoc['client']})
    ");
    if ($result) {
        $inserted++;
    }
}

echo "Associations créées: $inserted\n\n";

echo "=== VÉRIFICATION FINALE ===\n\n";

echo "Clients par utilisateur:\n";
$result = $conn->query("
    SELECT cu.Id, COUNT(cut.ThirdParty_Id) as client_count
    FROM customerusers cu
    LEFT JOIN customerusers_thirdparty cut ON cu.Id = cut.CustomerUsers_Id
    WHERE cu.CustomerUsersStatusId = 1
    GROUP BY cu.Id
    ORDER BY client_count DESC
");
while ($row = $result->fetch_assoc()) {
    if ($row['client_count'] > 0) {
        echo "  Utilisateur #{$row['Id']}: {$row['client_count']} clients\n";
    }
}

echo "\nUtilisateurs par client:\n";
$result = $conn->query("
    SELECT tp.Id, tp.Label, COUNT(cut.CustomerUsers_Id) as user_count
    FROM thirdparty tp
    LEFT JOIN customerusers_thirdparty cut ON tp.Id = cut.ThirdParty_Id
    GROUP BY tp.Id
    HAVING user_count > 0
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

echo "\n✅ Nettoyage terminé!\n";

$conn->close();
?>