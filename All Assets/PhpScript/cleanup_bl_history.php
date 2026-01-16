<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== NETTOYAGE DE L'HISTORIQUE DE RECHERCHE BL ===\n\n";

// Récupérer les entrées invalides
$result = $conn->query("
    SELECT cubsh.Id
    FROM customeruserblsearchhistory cubsh
    LEFT JOIN bl ON cubsh.BlNumber = bl.BlNumber
    LEFT JOIN customerusers_thirdparty cut ON bl.ConsigneeId = cut.ThirdParty_Id AND cubsh.UserId = cut.CustomerUsers_Id
    WHERE cut.ThirdParty_Id IS NULL
");

$invalid_ids = [];
while ($row = $result->fetch_assoc()) {
    $invalid_ids[] = $row['Id'];
}

echo "Entrées invalides à supprimer: " . count($invalid_ids) . "\n";

if (count($invalid_ids) > 0) {
    // Supprimer les entrées invalides par lots
    $deleted = 0;
    $batch_size = 100;
    
    for ($i = 0; $i < count($invalid_ids); $i += $batch_size) {
        $batch = array_slice($invalid_ids, $i, $batch_size);
        $ids_str = implode(',', $batch);
        
        $conn->query("DELETE FROM customeruserblsearchhistory WHERE Id IN ($ids_str)");
        $deleted += count($batch);
        echo "✓ $deleted entrées supprimées\n";
    }
    
    echo "\n✅ Suppression complète: $deleted entrées supprimées\n\n";
} else {
    echo "✅ Aucune entrée invalide à supprimer\n\n";
}

echo "=== VÉRIFICATION FINALE ===\n\n";

$result = $conn->query("SELECT COUNT(*) as total FROM customeruserblsearchhistory");
$row = $result->fetch_assoc();
echo "Total entrées restantes: {$row['total']}\n\n";

echo "Entrées valides restantes:\n";
$result = $conn->query("
    SELECT 
        cubsh.UserId,
        COUNT(*) as count,
        GROUP_CONCAT(cubsh.BlNumber) as bl_numbers
    FROM customeruserblsearchhistory cubsh
    LEFT JOIN bl ON cubsh.BlNumber = bl.BlNumber
    LEFT JOIN customerusers_thirdparty cut ON bl.ConsigneeId = cut.ThirdParty_Id AND cubsh.UserId = cut.CustomerUsers_Id
    WHERE cut.ThirdParty_Id IS NOT NULL
    GROUP BY cubsh.UserId
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  User #{$row['UserId']}: {$row['count']} entrées valides (BLs: {$row['bl_numbers']})\n";
    }
} else {
    echo "  (Aucune entrée valide)\n";
}

echo "\n✅ Nettoyage terminé!\n";

$conn->close();
?>