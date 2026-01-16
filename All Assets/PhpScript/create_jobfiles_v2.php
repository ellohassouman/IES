<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== CRÉATION DES JOBFILES VALIDES ===\n\n";

// Récupérer les familles d'événements IN et OUT
$in_family = $conn->query("SELECT Id FROM family WHERE Label = 'In'")->fetch_assoc()['Id'];
$out_family = $conn->query("SELECT Id FROM family WHERE Label = 'Out'")->fetch_assoc()['Id'];

echo "Famille IN: $in_family\n";
echo "Famille OUT: $out_family\n\n";

// Récupérer les types d'événements par famille
$in_event_types = [];
$result = $conn->query("SELECT Id, Label FROM eventtype WHERE FamilyId = $in_family");
while ($row = $result->fetch_assoc()) {
    $in_event_types[] = $row['Id'];
}

$out_event_types = [];
$result = $conn->query("SELECT Id, Label FROM eventtype WHERE FamilyId = $out_family");
while ($row = $result->fetch_assoc()) {
    $out_event_types[] = $row['Id'];
}

$intermediate_event_types = [];
$result = $conn->query("
    SELECT Id FROM eventtype 
    WHERE FamilyId NOT IN ($in_family, $out_family)
    AND FamilyId IS NOT NULL
");
while ($row = $result->fetch_assoc()) {
    $intermediate_event_types[] = $row['Id'];
}

echo "Types IN disponibles: " . count($in_event_types) . "\n";
echo "Types OUT disponibles: " . count($out_event_types) . "\n";
echo "Types intermédiaires disponibles: " . count($intermediate_event_types) . "\n\n";

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

while ($blitem = $result->fetch_assoc()) {
    $blitem_id = $blitem['Id'];
    $bl_id = $blitem['BlId'];
    $item_type_id = $blitem['ItemTypeId'];
    $item_number = $blitem['Number'];
    
    $is_container = ($item_type_id == 1); // 1 = Conteneur
    
    // Générer les dates des événements
    $base_date = strtotime('-10 days');
    $dates = [
        date('Y-m-d H:i:s', $base_date),
        date('Y-m-d H:i:s', $base_date + 3*86400),
        date('Y-m-d H:i:s', $base_date + 6*86400),
        date('Y-m-d H:i:s', $base_date + 9*86400),
    ];
    
    // Nombre d'événements: 2-4
    $event_count_for_jf = rand(2, 4);
    
    // Créer le jobfile
    $date_open = $dates[0]; // Date du premier événement (IN)
    $date_close = null;
    
    if ($event_count_for_jf == 4) {
        // Si 4 événements, le dernier est OUT
        $date_close = $dates[3];
    }
    
    $shipping_line_id = !empty($shipping_lines) ? $shipping_lines[array_rand($shipping_lines)] : 1;
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
        
        // Créer les événements
        
        // 1. Événement IN (obligatoire)
        if ($is_container) {
            // Pour les conteneurs : on utilise un événement IN simple
            $in_type_id = $in_event_types[array_rand($in_event_types)];
        } else {
            // Pour les véhicules : même chose
            $in_type_id = $in_event_types[array_rand($in_event_types)];
        }
        
        $conn->query("
            INSERT INTO event (EventTypeId, JobFileId, EventDate)
            VALUES ($in_type_id, $jobfile_id, '{$dates[0]}')
        ");
        $event_count++;
        echo "  └─ Événement IN créé\n";
        
        // 2. Événements intermédiaires
        for ($i = 1; $i < $event_count_for_jf - 1; $i++) {
            if (!empty($intermediate_event_types)) {
                $intermediate_type_id = $intermediate_event_types[array_rand($intermediate_event_types)];
                $conn->query("
                    INSERT INTO event (EventTypeId, JobFileId, EventDate)
                    VALUES ($intermediate_type_id, $jobfile_id, '{$dates[$i]}')
                ");
                $event_count++;
                echo "  └─ Événement intermédiaire créé\n";
            }
        }
        
        // 3. Événement OUT (si 4 événements)
        if ($event_count_for_jf == 4) {
            $out_type_id = $out_event_types[array_rand($out_event_types)];
            $conn->query("
                INSERT INTO event (EventTypeId, JobFileId, EventDate)
                VALUES ($out_type_id, $jobfile_id, '{$dates[3]}')
            ");
            $event_count++;
            echo "  └─ Événement OUT créé\n";
        }
        
        echo "\n";
    }
}

echo "=== RÉSUMÉ ===\n";
echo "JobFiles créés: $jobfile_count\n";
echo "Événements créés: $event_count\n";
echo "\n✅ Création terminée!\n";

$conn->close();
?>
