<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== NETTOYAGE DES JOBFILES NON-CONFORMES ===\n\n";

// Récupérer les familles
$in_family_id = $conn->query("SELECT Id FROM family WHERE Label = 'In'")->fetch_assoc()['Id'];
$out_family_id = $conn->query("SELECT Id FROM family WHERE Label = 'Out'")->fetch_assoc()['Id'];

// Étape 1 : Trouver les jobfiles qui violent les règles
echo "ÉTAPE 1 : Identification des jobfiles non-conformes\n";
$result = $conn->query("
    SELECT jf.Id
    FROM jobfile jf
    WHERE jf.Id NOT IN (
        SELECT DISTINCT bjf.JobFile_Id
        FROM blitem_jobfile bjf
    )
");

$orphaned_jobfiles = [];
while ($row = $result->fetch_assoc()) {
    $orphaned_jobfiles[] = $row['Id'];
}

echo "Jobfiles orphelins (pas liés à blitem): " . count($orphaned_jobfiles) . "\n";

// Étape 2 : Supprimer les jobfiles orphelins
if (!empty($orphaned_jobfiles)) {
    $ids_str = implode(',', $orphaned_jobfiles);
    
    // Supprimer les events
    $conn->query("DELETE FROM event WHERE JobFileId IN ($ids_str)");
    
    // Supprimer les jobfiles
    $conn->query("DELETE FROM jobfile WHERE Id IN ($ids_str)");
    
    echo "✓ " . count($orphaned_jobfiles) . " jobfiles orphelins supprimés\n\n";
} else {
    echo "✓ Aucun jobfile orphelin\n\n";
}

// Étape 3 : Recréer les jobfiles à partir des blitems
echo "ÉTAPE 2 : Création des jobfiles conformes\n\n";

// Récupérer tous les blitems qui n'ont pas de jobfiles
$result = $conn->query("
    SELECT bli.Id, bli.Number, bli.ItemTypeId, bli.BlId, b.Number as BlNumber
    FROM blitem bli
    JOIN bl b ON bli.BlId = b.Id
    WHERE bli.Id NOT IN (
        SELECT DISTINCT BLItem_Id FROM blitem_jobfile
    )
    ORDER BY bli.Id
");

$blitems_to_process = [];
while ($row = $result->fetch_assoc()) {
    $blitems_to_process[] = $row;
}

echo "BLItems sans jobfiles: " . count($blitems_to_process) . "\n\n";

if (empty($blitems_to_process)) {
    echo "✅ Tous les blitems ont déjà des jobfiles\n";
    $conn->close();
    exit;
}

// Récupérer les types d'événements
$result = $conn->query("
    SELECT et.Id, et.Code, et.Label, f.Label as Family
    FROM eventtype et
    JOIN family f ON et.FamilyId = f.Id
    ORDER BY f.Label, et.Label
");

$event_types = [];
$in_types_containers = [];
$out_types = [];
$intermediate_types = [];

while ($row = $result->fetch_assoc()) {
    $event_types[$row['Id']] = $row;
    
    if ($row['Family'] === 'In') {
        // Vérifier si c'est un type de conteneur (dans contract_eventtype)
        $check = $conn->query("
            SELECT COUNT(*) as count
            FROM contract_eventtype
            WHERE EventType_Id = {$row['Id']}
        ")->fetch_assoc()['count'];
        
        if ($check > 0) {
            $in_types_containers[] = $row['Id'];
        }
    } elseif ($row['Family'] === 'Out') {
        $out_types[] = $row['Id'];
    } else {
        // Types intermédiaires
        $intermediate_types[] = $row['Id'];
    }
}

echo "Types de conteneurs (IN contractuels): " . count($in_types_containers) . "\n";
echo "Types OUT: " . count($out_types) . "\n";
echo "Types intermédiaires: " . count($intermediate_types) . "\n\n";

// Créer les jobfiles
$created_count = 0;
$base_date = new DateTime('2025-01-28');

foreach ($blitems_to_process as $index => $blitem) {
    $item_type_id = $blitem['ItemTypeId'];
    
    // Déterminer le type de jobfile (conteneur ou véhicule)
    // 1 = conteneur, 2 = véhicule
    $is_container = ($item_type_id === 1);
    
    // Générer les dates (3 jours d'écart)
    $event_date = clone $base_date;
    $event_date->add(new DateInterval('P' . ($index * 5) . 'D')); // 5 jours par blitem
    
    // Sélectionner les événements
    $in_event_id = $is_container ? 
        $in_types_containers[array_rand($in_types_containers)] :
        // Pour véhicules, chercher un IN sans "Conteneur" dans le label
        $conn->query("
            SELECT et.Id FROM eventtype et
            JOIN family f ON et.FamilyId = f.Id
            WHERE f.Label = 'In'
            AND et.Label NOT LIKE '%Conteneur%'
            AND et.Label NOT LIKE 'event_type%'
            LIMIT 1
        ")->fetch_assoc()['Id'];
    
    $out_event_id = $out_types[array_rand($out_types)];
    
    // Créer le jobfile
    $date_open = $event_date->format('Y-m-d H:i:s');
    $date_out = $event_date->add(new DateInterval('P3D'))->format('Y-m-d H:i:s');
    
    $conn->query("INSERT INTO jobfile (DateOpen, DateClose, PositionId) 
                 VALUES ('$date_open', '$date_out', 1)");
    
    $jobfile_id = $conn->insert_id;
    
    // Ajouter les événements
    $in_date = $event_date->sub(new DateInterval('P3D'))->format('Y-m-d H:i:s');
    $out_date = $event_date->add(new DateInterval('P3D'))->format('Y-m-d H:i:s');
    
    $conn->query("INSERT INTO event (JobFileId, EventTypeId, EventDate) 
                 VALUES ($jobfile_id, $in_event_id, '$in_date')");
    
    $conn->query("INSERT INTO event (JobFileId, EventTypeId, EventDate) 
                 VALUES ($jobfile_id, $out_event_id, '$out_date')");
    
    // Lier au blitem
    $conn->query("INSERT INTO blitem_jobfile (BLItem_Id, JobFile_Id) 
                 VALUES ({$blitem['Id']}, $jobfile_id)");
    
    $created_count++;
}

echo "✓ $created_count nouveaux jobfiles créés\n\n";

// Vérification finale
echo "=== VÉRIFICATION FINALE ===\n";
$result = $conn->query("SELECT COUNT(*) as total FROM blitem");
$total_blitems = $result->fetch_assoc()['total'];

$result = $conn->query("
    SELECT COUNT(DISTINCT BLItem_Id) as with_jobfile
    FROM blitem_jobfile
");
$blitems_with_jobfile = $result->fetch_assoc()['with_jobfile'];

echo "Total BLItems: $total_blitems\n";
echo "BLItems avec jobfiles: $blitems_with_jobfile\n";

if ($total_blitems === $blitems_with_jobfile) {
    echo "✅ Tous les blitems ont un jobfile\n";
} else {
    echo "❌ " . ($total_blitems - $blitems_with_jobfile) . " blitems sans jobfile\n";
}

$conn->close();
?>
