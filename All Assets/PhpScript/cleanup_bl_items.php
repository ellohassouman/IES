<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== NETTOYAGE DES BL ITEMS ===\n\n";

// Règles :
// - Max 3 items par BL
// - Au moins 2 conteneurs + 1 véhicule
// - Conteneurs doivent avoir : Weight, Volume, ItemCode
// - Supprimer les items qui ne respectent pas les critères

// 1. Supprimer les items sans Weight/Volume/ItemCode (conteneurs) ou mal renseignés
echo "=== ÉTAPE 1: Identifier et supprimer les items invalides ===\n\n";

// Supprimer les références blitem_jobfile des conteneurs sans Code
$conn->query("DELETE bjf FROM blitem_jobfile bjf JOIN blitem bli ON bjf.BLItem_Id = bli.Id WHERE bli.ItemTypeId = 1 AND bli.ItemCodeId IS NULL");
$deleted1 = $conn->query("DELETE FROM blitem WHERE ItemTypeId = 1 AND ItemCodeId IS NULL");
echo "✓ Conteneurs sans ItemCode supprimés\n";

// Supprimer les références blitem_jobfile des conteneurs sans Weight
$conn->query("DELETE bjf FROM blitem_jobfile bjf JOIN blitem bli ON bjf.BLItem_Id = bli.Id WHERE bli.ItemTypeId = 1 AND (bli.Weight IS NULL OR bli.Weight = 0)");
$deleted2 = $conn->query("DELETE FROM blitem WHERE ItemTypeId = 1 AND (Weight IS NULL OR Weight = 0)");
echo "✓ Conteneurs sans Weight supprimés\n";

// Supprimer les références blitem_jobfile des conteneurs sans Volume
$conn->query("DELETE bjf FROM blitem_jobfile bjf JOIN blitem bli ON bjf.BLItem_Id = bli.Id WHERE bli.ItemTypeId = 1 AND (bli.Volume IS NULL OR bli.Volume = 0)");
$deleted3 = $conn->query("DELETE FROM blitem WHERE ItemTypeId = 1 AND (Volume IS NULL OR Volume = 0)");
echo "✓ Conteneurs sans Volume supprimés\n";

echo "\n=== ÉTAPE 2: Limiter à max 3 items par BL ===\n\n";

// Pour chaque BL, garder max 3 items (2 conteneurs + 1 véhicule si possible)
$bls = $conn->query("SELECT DISTINCT BlId FROM blitem");
$bl_deleted = 0;

while ($bl_row = $bls->fetch_assoc()) {
    $bl_id = $bl_row['BlId'];
    
    // Compter les items par type
    $count = $conn->query("
        SELECT 
            ItemTypeId,
            COUNT(*) as count
        FROM blitem
        WHERE BlId = $bl_id
        GROUP BY ItemTypeId
    ");
    
    $types = [];
    while ($type_row = $count->fetch_assoc()) {
        $types[$type_row['ItemTypeId']] = $type_row['count'];
    }
    
    // Containers: 1, Vehicles: 2
    $containers = $types[1] ?? 0;
    $vehicles = $types[2] ?? 0;
    
    // Si pas assez de conteneurs (< 2), supprimer ce BL
    if ($containers < 2 || $vehicles < 1) {
        // Supprimer d'abord les références blitem_jobfile
        $conn->query("DELETE bjf FROM blitem_jobfile bjf JOIN blitem bli ON bjf.BLItem_Id = bli.Id WHERE bli.BlId = $bl_id");
        // Puis supprimer tous les items de ce BL
        $conn->query("DELETE FROM blitem WHERE BlId = $bl_id");
        $bl_deleted += 1;
    } else {
        // Si plus de 3 items, garder les 2 premiers conteneurs et 1 véhicule
        // Récupérer les items
        $items = $conn->query("
            SELECT Id, ItemTypeId
            FROM blitem
            WHERE BlId = $bl_id
            ORDER BY ItemTypeId, Id
        ");
        
        $kept_containers = 0;
        $kept_vehicles = 0;
        $items_to_keep = [];
        
        while ($item = $items->fetch_assoc()) {
            if ($item['ItemTypeId'] == 1 && $kept_containers < 2) { // Conteneur
                $items_to_keep[] = $item['Id'];
                $kept_containers++;
            } elseif ($item['ItemTypeId'] == 2 && $kept_vehicles < 1) { // Véhicule
                $items_to_keep[] = $item['Id'];
                $kept_vehicles++;
            }
        }
        
        // Supprimer les items non gardés
        if (count($items_to_keep) < 3) {
            $kept_str = implode(',', $items_to_keep);
            // Supprimer les références blitem_jobfile d'abord
            $conn->query("DELETE bjf FROM blitem_jobfile bjf JOIN blitem bli ON bjf.BLItem_Id = bli.Id WHERE bli.BlId = $bl_id AND bli.Id NOT IN ($kept_str)");
            // Puis supprimer les items
            $conn->query("DELETE FROM blitem WHERE BlId = $bl_id AND Id NOT IN ($kept_str)");
        }
    }
}

echo "✓ BL sans config requise supprimés: $bl_deleted\n";

echo "\n=== VÉRIFICATION FINALE ===\n\n";

$result = $conn->query("
    SELECT 
        bl.BlNumber,
        COUNT(bli.Id) as item_count,
        SUM(CASE WHEN yit.Label = 'Conteneur' THEN 1 ELSE 0 END) as container_count,
        SUM(CASE WHEN yit.Label = 'Vehicle' THEN 1 ELSE 0 END) as vehicle_count
    FROM bl
    LEFT JOIN blitem bli ON bl.Id = bli.BlId
    LEFT JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id
    GROUP BY bl.Id
    HAVING item_count > 0
");

$valid_bls = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['container_count'] >= 2 && $row['vehicle_count'] >= 1 && $row['item_count'] <= 3) {
            echo "✅ BL {$row['BlNumber']}: {$row['item_count']} items (Conteneurs: {$row['container_count']}, Véhicules: {$row['vehicle_count']})\n";
            $valid_bls++;
        } else {
            echo "⚠️ BL {$row['BlNumber']}: {$row['item_count']} items (Conteneurs: {$row['container_count']}, Véhicules: {$row['vehicle_count']})\n";
        }
    }
}

echo "\nBL valides: $valid_bls\n";

echo "\n✅ Nettoyage terminé!\n";

$conn->close();
?>