<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== RECONSTRUCTION COMPLÈTE DES JOBFILES ===\n\n";

// ÉTAPE 1 : Supprimer tous les jobfiles existants
echo "ÉTAPE 1 : Suppression des jobfiles existants\n";

$result = $conn->query("SELECT COUNT(*) as total FROM jobfile");
$total = $result->fetch_assoc()['total'];

// Désactiver les contraintes FK temporairement
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$conn->query("DELETE FROM invoiceitem WHERE EventId IS NOT NULL");
$conn->query("DELETE FROM cartitem WHERE 1=1");
$conn->query("DELETE FROM document WHERE 1=1");
$conn->query("DELETE FROM event WHERE JobFileId IS NOT NULL");
$conn->query("DELETE FROM blitem_jobfile WHERE 1=1");
$conn->query("DELETE FROM jobfile WHERE 1=1");

// Réactiver les contraintes FK
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "✓ $total jobfiles supprimés\n\n";

// ÉTAPE 2 : Récupérer les types d'événements
echo "ÉTAPE 2 : Chargement des types d'événements\n";

$in_family_id = $conn->query("SELECT Id FROM family WHERE Label = 'In'")->fetch_assoc()['Id'];
$out_family_id = $conn->query("SELECT Id FROM family WHERE Label = 'Out'")->fetch_assoc()['Id'];

// Événements IN pour conteneurs (dans contract_eventtype)
$result = $conn->query("
    SELECT DISTINCT et.Id
    FROM eventtype et
    JOIN contract_eventtype cet ON et.Id = cet.EventType_Id
    WHERE et.FamilyId = $in_family_id
");

$in_types_containers = [];
while ($row = $result->fetch_assoc()) {
    $in_types_containers[] = $row['Id'];
}

// Événements OUT
$result = $conn->query("
    SELECT Id FROM eventtype WHERE FamilyId = $out_family_id
");

$out_types = [];
while ($row = $result->fetch_assoc()) {
    $out_types[] = $row['Id'];
}

// Événements intermédiaires (sans "Conteneur" et sans "event_type")
$result = $conn->query("
    SELECT Id FROM eventtype 
    WHERE FamilyId NOT IN ($in_family_id, $out_family_id)
    AND Label NOT LIKE '%Conteneur%'
    AND Label NOT LIKE 'event_type%'
");

$intermediate_types = [];
while ($row = $result->fetch_assoc()) {
    $intermediate_types[] = $row['Id'];
}

echo "Types IN pour conteneurs: " . count($in_types_containers) . "\n";
echo "Types OUT: " . count($out_types) . "\n";
echo "Types intermédiaires: " . count($intermediate_types) . "\n\n";

// ÉTAPE 3 : Créer les jobfiles
echo "ÉTAPE 3 : Création des jobfiles\n\n";

$result = $conn->query("
    SELECT bli.Id, bli.Number, bli.ItemTypeId, bli.BlId
    FROM blitem bli
    ORDER BY bli.BlId, bli.Id
");

$blitems = [];
while ($row = $result->fetch_assoc()) {
    $blitems[] = $row;
}

$created = 0;
$base_date = new DateTime('2025-01-28 10:00:00');

foreach ($blitems as $index => $blitem) {
    // Déterminer le type : 1 = conteneur, 2 = véhicule
    $is_container = ($blitem['ItemTypeId'] === 1);
    
    // Déterminer les types d'événements
    if ($is_container) {
        $in_type = $in_types_containers[array_rand($in_types_containers)];
    } else {
        // Pour véhicules : chercher IN sans "Conteneur"
        $result = $conn->query("
            SELECT Id FROM eventtype 
            WHERE FamilyId = $in_family_id
            AND Label NOT LIKE '%Conteneur%'
            AND Label NOT LIKE 'event_type%'
        ");
        
        $vehicle_in_types = [];
        while ($row = $result->fetch_assoc()) {
            $vehicle_in_types[] = $row['Id'];
        }
        
        $in_type = !empty($vehicle_in_types) ? 
            $vehicle_in_types[array_rand($vehicle_in_types)] :
            $in_types_containers[array_rand($in_types_containers)];
    }
    
    $out_type = $out_types[array_rand($out_types)];
    
    // Générer les dates (5 jours par blitem)
    $date_in = clone $base_date;
    $date_in->add(new DateInterval('P' . ($index * 5) . 'D'));
    $date_in_str = $date_in->format('Y-m-d H:i:s');
    
    $date_out = clone $date_in;
    $date_out->add(new DateInterval('P3D'));
    $date_out_str = $date_out->format('Y-m-d H:i:s');
    
    // Créer le jobfile avec DateOpen et DateClose
    $conn->query("INSERT INTO jobfile (DateOpen, DateClose, PositionId) 
                 VALUES ('$date_in_str', '$date_out_str', 1)");
    
    $jobfile_id = $conn->insert_id;
    
    // Ajouter l'événement IN
    $conn->query("INSERT INTO event (JobFileId, EventTypeId, EventDate) 
                 VALUES ($jobfile_id, $in_type, '$date_in_str')");
    
    // Ajouter des événements intermédiaires optionnels (0-1)
    if (!empty($intermediate_types) && rand(0, 2) === 1) {
        $inter_type = $intermediate_types[array_rand($intermediate_types)];
        $date_inter = clone $date_in;
        $date_inter->add(new DateInterval('P1D'));
        $date_inter_str = $date_inter->format('Y-m-d H:i:s');
        
        $conn->query("INSERT INTO event (JobFileId, EventTypeId, EventDate) 
                     VALUES ($jobfile_id, $inter_type, '$date_inter_str')");
    }
    
    // Ajouter l'événement OUT
    $conn->query("INSERT INTO event (JobFileId, EventTypeId, EventDate) 
                 VALUES ($jobfile_id, $out_type, '$date_out_str')");
    
    // Lier au blitem
    $conn->query("INSERT INTO blitem_jobfile (BLItem_Id, JobFile_Id) 
                 VALUES ({$blitem['Id']}, $jobfile_id)");
    
    $created++;
}

echo "✓ $created jobfiles créés\n\n";

// ÉTAPE 4 : Vérification
echo "ÉTAPE 4 : Vérification\n";

$result = $conn->query("SELECT COUNT(*) as total FROM jobfile");
$total_jf = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM event");
$total_events = $result->fetch_assoc()['total'];

$result = $conn->query("
    SELECT COUNT(DISTINCT bli.Id) as blitems_with_jobfile
    FROM blitem bli
    JOIN blitem_jobfile bjf ON bli.Id = bjf.BLItem_Id
");
$blitems_with_jf = $result->fetch_assoc()['blitems_with_jobfile'];

echo "Total jobfiles: $total_jf\n";
echo "Total événements: $total_events\n";
echo "BLItems avec jobfiles: $blitems_with_jf\n";

if ($blitems_with_jf === 147) {
    echo "✅ Tous les blitems ont un jobfile\n";
} else {
    echo "❌ Problème : $blitems_with_jf blitems au lieu de 147\n";
}

$conn->close();
?>
