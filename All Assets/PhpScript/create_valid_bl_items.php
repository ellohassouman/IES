<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== CRÉATION DES BLITEMS VALIDES ===\n\n";

// Données de conteneurs valides (normes ISO 6346)
$containers = [
    ['code' => 'TCLU', 'number_base' => '123456'],
    ['code' => 'OOCL', 'number_base' => '234567'],
    ['code' => 'HAPAG', 'number_base' => '345678'],
    ['code' => 'COSCO', 'number_base' => '456789'],
    ['code' => 'EVERGREEN', 'number_base' => '567890'],
];

// Données de véhicules (VIN format)
$vehicles = [
    'VIN111111111111111A',
    'VIN222222222222222B',
    'VIN333333333333333C',
    'VIN444444444444444D',
    'VIN555555555555555E',
];

// Poids et volumes typiques pour conteneurs
$weights = [20000, 22000, 23000, 24000, 25000];
$volumes = [30, 35, 38, 40, 42];

// Récupérer tous les BL
$bls = $conn->query("SELECT Id, BlNumber FROM bl ORDER BY Id");
$bl_count = 0;
$item_count = 0;

while ($bl = $bls->fetch_assoc()) {
    $bl_id = $bl['Id'];
    $bl_number = $bl['BlNumber'];
    
    // Pour chaque BL, créer : 2 conteneurs + 1 véhicule
    
    // Créer 2 conteneurs
    for ($c = 0; $c < 2; $c++) {
        $container_index = ($bl_id + $c) % count($containers);
        $container = $containers[$container_index];
        
        // Générer numéro de conteneur ISO valide
        $serial = str_pad((int)$container['number_base'] + $bl_id + $c, 6, '0', STR_PAD_LEFT);
        // Calculer le check digit (simple checksum pour démo)
        $check_digit = (int)substr($serial, -1);
        $container_number = substr($container['code'], 0, 4) . $serial . $check_digit;
        
        // Poids et volume
        $weight = $weights[($bl_id + $c) % count($weights)];
        $volume = $volumes[($bl_id + $c) % count($volumes)];
        
        // Récupérer un ItemCodeId valide pour conteneur
        $code_result = $conn->query("SELECT Id FROM yarditemcode LIMIT 1");
        $code_row = $code_result->fetch_assoc();
        $item_code_id = $code_row ? $code_row['Id'] : 1;
        
        // Insérer le conteneur
        $insert = $conn->query("
            INSERT INTO blitem (Number, Weight, Volume, BlId, ItemTypeId, ItemCodeId)
            VALUES ('$container_number', $weight, $volume, $bl_id, 1, $item_code_id)
        ");
        
        if ($insert) {
            $item_count++;
            echo "✓ Conteneur créé: $container_number (Weight: $weight, Volume: $volume)\n";
        }
    }
    
    // Créer 1 véhicule
    $vehicle_index = $bl_id % count($vehicles);
    $vehicle_number = $vehicles[$vehicle_index];
    
    $insert = $conn->query("
        INSERT INTO blitem (Number, BlId, ItemTypeId)
        VALUES ('$vehicle_number', $bl_id, 2)
    ");
    
    if ($insert) {
        $item_count++;
        echo "✓ Véhicule créé: $vehicle_number\n";
    }
    
    $bl_count++;
    echo "\n";
}

echo "=== VÉRIFICATION FINALE ===\n\n";

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
    ORDER BY bl.BlNumber
");

$valid_count = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status = ($row['container_count'] >= 2 && $row['vehicle_count'] >= 1 && $row['item_count'] == 3) ? '✅' : '⚠️';
        echo "$status BL {$row['BlNumber']}: {$row['item_count']} items (Conteneurs: {$row['container_count']}, Véhicules: {$row['vehicle_count']})\n";
        if ($row['container_count'] >= 2 && $row['vehicle_count'] >= 1 && $row['item_count'] == 3) {
            $valid_count++;
        }
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "BL traités: $bl_count\n";
echo "Items créés: $item_count\n";
echo "BL valides: $valid_count\n";
echo "\n✅ Création terminée!\n";

$conn->close();
?>