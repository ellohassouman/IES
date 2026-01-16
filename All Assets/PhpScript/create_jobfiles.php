<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== CRÉATION DES JOBFILES VALIDES ===\n\n";

// Vérifier les types d'événements disponibles
$event_types = [];
$result = $conn->query("SELECT Id, Label FROM eventtype");
while ($row = $result->fetch_assoc()) {
    $event_types[$row['Label']] = $row['Id'];
    echo "EventType: {$row['Label']} (ID: {$row['Id']})\n";
}
echo "\n";

// Vérifier les ShippingLines disponibles
$shipping_lines = [];
$result = $conn->query("SELECT Id FROM shippingline LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $shipping_lines[] = $row['Id'];
}
echo "ShippingLines disponibles: " . count($shipping_lines) . "\n";

// Vérifier les Positions disponibles
$positions = [];
$result = $conn->query("SELECT Id FROM position LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $positions[] = $row['Id'];
}
echo "Positions disponibles: " . count($positions) . "\n\n";

// Récupérer les BLItems (conteneurs et véhicules) sans jobfile
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
    
    // Sélectionner des dates pour les événements
    $dates = [
        date('Y-m-d H:i:s', strtotime('-10 days')),
        date('Y-m-d H:i:s', strtotime('-7 days')),
        date('Y-m-d H:i:s', strtotime('-3 days')),
        date('Y-m-d H:i:s', strtotime('-1 days')),
    ];
    
    // Créer un jobfile
    $shipping_line_id = $shipping_lines[array_rand($shipping_lines)];
    $position_id = $positions[array_rand($positions)];
    
    // Déterminer le nombre d'événements (2-4, toujours commencer par IN, peut finir par OUT)
    $event_count_for_jf = rand(2, 4);
    
    if ($is_container) {
        // Pour les conteneurs, créer seulement des événements facturables
        // Vérifier les événements facturables avec contract valide
        $facturables = $conn->query("
            SELECT DISTINCT e.EventTypeId, et.Label
            FROM event e
            JOIN eventtype et ON e.EventTypeId = et.Id
            WHERE e.ContractId IS NOT NULL
            AND e.SubscriptionId IS NOT NULL
            LIMIT 5
        ");
        
        $event_types_available = [];
        while ($et_row = $facturables->fetch_assoc()) {
            $event_types_available[] = [
                'id' => $et_row['EventTypeId'],
                'label' => $et_row['Label']
            ];
        }
    }
    
    // Créer le jobfile
    // La dateopen sera la date du premier événement IN
    $date_open = $dates[0];
    $date_close = null;
    
    if ($event_count_for_jf == 4) {
        // Si 4 événements, le dernier peut être OUT
        $date_close = $dates[3];
    }
    
    $insert = $conn->query("
        INSERT INTO jobfile (DateOpen, DateClose, ShippingLineId, PositionId)
        VALUES ('$date_open', " . ($date_close ? "'$date_close'" : "NULL") . ", $shipping_line_id, $position_id)
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
        // 1. Événement IN
        $in_type_id = $event_types['IN'] ?? 1;
        
        // Pour les conteneurs : trouver un événement existant valide
        if ($is_container && !empty($event_types_available)) {
            $random_event_type = $event_types_available[array_rand($event_types_available)];
            $in_type_id = $random_event_type['id'];
            
            // Récupérer un événement avec les bons attributs
            $event_data = $conn->query("
                SELECT e.Id, e.EventTypeId, e.ContractId, e.SubscriptionId, e.CallId
                FROM event e
                WHERE e.EventTypeId = $in_type_id
                AND e.ContractId IS NOT NULL
                AND e.SubscriptionId IS NOT NULL
                LIMIT 1
            ")->fetch_assoc();
            
            if ($event_data) {
                $conn->query("
                    INSERT INTO event (EventTypeId, JobFileId, EventDate, ContractId, SubscriptionId, CallId)
                    VALUES ($in_type_id, $jobfile_id, '{$dates[0]}', {$event_data['ContractId']}, {$event_data['SubscriptionId']}, {$event_data['CallId']})
                ");
                $event_count++;
                echo "  └─ Événement IN créé (Type: {$in_type_id})\n";
            }
        } else {
            // Pour les véhicules : liberté totale
            $conn->query("
                INSERT INTO event (EventTypeId, JobFileId, EventDate)
                VALUES ($in_type_id, $jobfile_id, '{$dates[0]}')
            ");
            $event_count++;
            echo "  └─ Événement IN créé\n";
        }
        
        // 2. Événements intermédiaires (si applicable)
        for ($i = 1; $i < $event_count_for_jf - 1; $i++) {
            $intermediate_types = array_filter($event_types, function($k) {
                return $k != 'IN' && $k != 'OUT';
            }, ARRAY_FILTER_USE_KEY);
            
            if (!empty($intermediate_types)) {
                $type_label = array_rand($intermediate_types);
                $type_id = $event_types[$type_label];
                
                $conn->query("
                    INSERT INTO event (EventTypeId, JobFileId, EventDate)
                    VALUES ($type_id, $jobfile_id, '{$dates[$i]}')
                ");
                $event_count++;
                echo "  └─ Événement $type_label créé\n";
            }
        }
        
        // 3. Événement OUT (si applicable)
        if ($event_count_for_jf == 4) {
            $out_type_id = $event_types['OUT'] ?? 2;
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
