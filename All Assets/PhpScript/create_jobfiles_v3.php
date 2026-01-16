<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== CRÉATION DES JOBFILES AVEC CHRONOLOGIE ===\n\n";

// Récupérer les familles d'événements IN et OUT
$in_family = $conn->query("SELECT Id FROM family WHERE Label = 'In'")->fetch_assoc()['Id'];
$out_family = $conn->query("SELECT Id FROM family WHERE Label = 'Out'")->fetch_assoc()['Id'];

echo "Famille IN: $in_family\n";
echo "Famille OUT: $out_family\n\n";

// Récupérer les types d'événements IN valides pour les CONTENEURS (depuis contract_eventtype)
$container_in_event_types = [];
$result = $conn->query("
    SELECT DISTINCT et.Id, et.Label
    FROM contract_eventtype cet
    JOIN eventtype et ON cet.EventType_Id = et.Id
    WHERE et.FamilyId = $in_family
    AND et.Label NOT LIKE 'event_type%'
    ORDER BY et.Label
");
while ($row = $result->fetch_assoc()) {
    $container_in_event_types[] = $row['Id'];
    echo "Container IN Type: {$row['Label']} (ID: {$row['Id']})\n";
}
echo "\n";

// Récupérer les types intermédiaires CONTENEUR
$container_intermediate_event_types = [];
$result = $conn->query("
    SELECT DISTINCT Id FROM eventtype
    WHERE Label LIKE '%Conteneur%' OR Label LIKE '%conteneur%'
    AND Label NOT LIKE 'event_type%'
    AND FamilyId IS NOT NULL
");
while ($row = $result->fetch_assoc()) {
    $container_intermediate_event_types[] = $row['Id'];
}
echo "Container Intermediate Types: " . count($container_intermediate_event_types) . "\n";

// Récupérer les types OUT CONTENEUR
$container_out_event_types = [];
$result = $conn->query("
    SELECT DISTINCT Id FROM eventtype
    WHERE (Label LIKE '%Conteneur%' OR Label LIKE '%conteneur%')
    AND FamilyId = $out_family
    AND Label NOT LIKE 'event_type%'
");
while ($row = $result->fetch_assoc()) {
    $container_out_event_types[] = $row['Id'];
}
echo "Container OUT Types: " . count($container_out_event_types) . "\n\n";

// Récupérer les types d'événements IN pour les VÉHICULES
// Stratégie : IN types qui ne contiennent pas "Conteneur"
$vehicle_in_event_types = [];
$result = $conn->query("
    SELECT Id, Label
    FROM eventtype
    WHERE FamilyId = $in_family
    AND Label NOT LIKE 'event_type%'
    AND Label NOT LIKE '%Conteneur%'
    AND Label NOT LIKE '%conteneur%'
    ORDER BY Label
");
while ($row = $result->fetch_assoc()) {
    $vehicle_in_event_types[] = $row['Id'];
}
echo "Vehicle IN Types disponibles: " . count($vehicle_in_event_types) . "\n";

// Récupérer les types intermédiaires VÉHICULES
// Tout ce qui n'est pas Conteneur et n'a pas event_type en préfixe
$vehicle_intermediate_event_types = [];
$result = $conn->query("
    SELECT DISTINCT Id FROM eventtype
    WHERE Label NOT LIKE 'event_type%'
    AND Label NOT LIKE '%Conteneur%'
    AND Label NOT LIKE '%conteneur%'
    AND FamilyId IS NOT NULL
    AND FamilyId NOT IN ($in_family, $out_family)
");
while ($row = $result->fetch_assoc()) {
    $vehicle_intermediate_event_types[] = $row['Id'];
}
echo "Vehicle Intermediate Types: " . count($vehicle_intermediate_event_types) . "\n";

// Récupérer les types OUT VÉHICULES
$vehicle_out_event_types = [];
$result = $conn->query("
    SELECT DISTINCT Id FROM eventtype
    WHERE FamilyId = $out_family
    AND Label NOT LIKE 'event_type%'
    AND Label NOT LIKE '%Conteneur%'
    AND Label NOT LIKE '%conteneur%'
");
while ($row = $result->fetch_assoc()) {
    $vehicle_out_event_types[] = $row['Id'];
}
echo "Vehicle OUT Types: " . count($vehicle_out_event_types) . "\n\n";

// Récupérer les Positions
$positions = [];
$result = $conn->query("SELECT Id FROM position LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $positions[] = $row['Id'];
}

echo "Positions disponibles: " . count($positions) . "\n\n";

// Récupérer les BLItems sans jobfile
$result = $conn->query("
    SELECT bli.Id, bli.BlId, bli.ItemTypeId, bli.Number
    FROM blitem bli
    LEFT JOIN blitem_jobfile bjf ON bli.Id = bjf.BLItem_Id
    WHERE bjf.BLItem_Id IS NULL
    ORDER BY bli.BlId, bli.ItemTypeId
");

$blitems_count = $result->num_rows;
echo "BLItems à traiter: $blitems_count\n\n";

$jobfile_count = 0;
$event_count = 0;
$skipped_containers = 0;

while ($blitem = $result->fetch_assoc()) {
    $blitem_id = $blitem['Id'];
    $bl_id = $blitem['BlId'];
    $item_type_id = $blitem['ItemTypeId'];
    $item_number = $blitem['Number'];
    
    $is_container = ($item_type_id == 1); // 1 = Conteneur
    
    // Pour les conteneurs, vérifier qu'il y a des types d'événements disponibles
    if ($is_container && empty($container_in_event_types)) {
        echo "⚠ Conteneur $item_number ignoré (pas de types IN valides)\n";
        $skipped_containers++;
        continue;
    }
    
    // Nombre d'événements: 2-4
    $event_count_for_jf = rand(2, 4);
    
    // Générer les dates avec chronologie stricte
    $base_date = strtotime('-10 days');
    $dates = [];
    for ($i = 0; $i < $event_count_for_jf; $i++) {
        $dates[$i] = date('Y-m-d H:i:s', $base_date + ($i * 3 * 86400)); // 3 jours d'intervalle
    }
    
    // Créer le jobfile
    $date_open = $dates[0]; // Date du premier événement (IN)
    $date_close = null;
    
    if ($event_count_for_jf == 4) {
        // Si 4 événements, le dernier est OUT
        $date_close = $dates[3];
    }
    
    $position_id = !empty($positions) ? $positions[array_rand($positions)] : 1;
    
    $insert = $conn->query("
        INSERT INTO jobfile (DateOpen, DateClose, PositionId)
        VALUES ('$date_open', " . ($date_close ? "'$date_close'" : "NULL") . ", $position_id)
    ");
    
    if ($insert) {
        $jobfile_id = $conn->insert_id;
        $jobfile_count++;
        
        // Associer le blitem à ce jobfile
        $conn->query("
            INSERT INTO blitem_jobfile (BLItem_Id, JobFile_Id)
            VALUES ($blitem_id, $jobfile_id)
        ");
        
        echo "✓ JobFile créé (ID: $jobfile_id) pour BLItem $blitem_id ($item_number)\n";
        
        // Créer les événements avec chronologie
        
        // 1. Événement IN (obligatoire)
        if ($is_container) {
            // Pour les conteneurs : choisir depuis contract_eventtype
            $in_type_id = $container_in_event_types[array_rand($container_in_event_types)];
        } else {
            // Pour les véhicules : choisir depuis les types valides
            $in_type_id = $vehicle_in_event_types[array_rand($vehicle_in_event_types)];
        }
        
        $conn->query("
            INSERT INTO event (EventTypeId, JobFileId, EventDate)
            VALUES ($in_type_id, $jobfile_id, '{$dates[0]}')
        ");
        $event_count++;
        echo "  └─ Événement IN créé (Date: {$dates[0]})\n";
        
        // 2. Événements intermédiaires
        for ($i = 1; $i < $event_count_for_jf - 1; $i++) {
            $intermediate_types = $is_container ? $container_intermediate_event_types : $vehicle_intermediate_event_types;
            
            if (!empty($intermediate_types)) {
                $intermediate_type_id = $intermediate_types[array_rand($intermediate_types)];
                $conn->query("
                    INSERT INTO event (EventTypeId, JobFileId, EventDate)
                    VALUES ($intermediate_type_id, $jobfile_id, '{$dates[$i]}')
                ");
                $event_count++;
                echo "  └─ Événement intermédiaire créé (Date: {$dates[$i]})\n";
            }
        }
        
        // 3. Événement OUT (si 4 événements)
        if ($event_count_for_jf == 4) {
            $out_types = $is_container ? $container_out_event_types : $vehicle_out_event_types;
            
            if (!empty($out_types)) {
                $out_type_id = $out_types[array_rand($out_types)];
                $conn->query("
                    INSERT INTO event (EventTypeId, JobFileId, EventDate)
                    VALUES ($out_type_id, $jobfile_id, '{$dates[3]}')
                ");
                $event_count++;
                echo "  └─ Événement OUT créé (Date: {$dates[3]})\n";
            }
        }
        
        echo "\n";
    }
}

echo "=== RÉSUMÉ ===\n";
echo "JobFiles créés: $jobfile_count\n";
echo "Événements créés: $event_count\n";
echo "Conteneurs ignorés (pas de types valides): $skipped_containers\n";
echo "\n✅ Création terminée!\n";

$conn->close();
?>
