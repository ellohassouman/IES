<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== CORRECTION COMPLÈTE DES DATES FUTURES ===\n\n";

$today = new DateTime('2026-01-16');
$today_str = $today->format('Y-m-d');

echo "Date d'aujourd'hui: $today_str\n\n";

// ÉTAPE 1 : Ramener TOUS les événements avant aujourd'hui
echo "ÉTAPE 1 : Décalage de tous les événements futurs\n";

// Récupérer le dernier événement
$result = $conn->query("SELECT MAX(EventDate) as MaxDate FROM event");
$max_date_row = $result->fetch_assoc();
$max_date = new DateTime($max_date_row['MaxDate']);

echo "Date maximale actuelle: " . $max_date->format('Y-m-d H:i:s') . "\n";

if ($max_date > $today) {
    // Calculer combien de jours il faut décaler
    $days_to_shift = $max_date->diff($today)->days + 1;
    
    echo "Jours à décaler: $days_to_shift\n";
    
    // Décaler les dates de chaque jobfile
    $result = $conn->query("
        SELECT Id FROM jobfile
        ORDER BY Id
    ");
    
    $updated = 0;
    while ($jf = $result->fetch_assoc()) {
        $jf_id = $jf['Id'];
        
        // Récupérer les événements du jobfile
        $events_result = $conn->query("
            SELECT Id, EventDate 
            FROM event 
            WHERE JobFileId = $jf_id
            ORDER BY EventDate ASC
        ");
        
        $events_data = [];
        while ($evt = $events_result->fetch_assoc()) {
            $events_data[] = $evt;
        }
        
        if (!empty($events_data)) {
            // Prendre le premier événement comme référence
            $first_date = new DateTime($events_data[0]['EventDate']);
            
            // Décaler la date de départ
            $new_first_date = clone $first_date;
            $new_first_date->sub(new DateInterval("P{$days_to_shift}D"));
            
            // Recalculer toutes les dates relative à la première
            foreach ($events_data as $index => $evt) {
                $original_date = new DateTime($evt['EventDate']);
                $offset_days = $first_date->diff($original_date)->days;
                
                $new_date = clone $new_first_date;
                $new_date->add(new DateInterval("P{$offset_days}D"));
                $new_date_str = $new_date->format('Y-m-d H:i:s');
                
                $conn->query("UPDATE event SET EventDate = '$new_date_str' WHERE Id = {$evt['Id']}");
            }
            
            $updated++;
        }
    }
    
    echo "✓ $updated jobfiles traités\n";
    
    // Mettre à jour les DateOpen/DateClose de tous les jobfiles
    $conn->query("
        UPDATE jobfile jf
        SET jf.DateOpen = (SELECT MIN(EventDate) FROM event e WHERE e.JobFileId = jf.Id),
            jf.DateClose = (SELECT MAX(EventDate) FROM event e WHERE e.JobFileId = jf.Id)
        WHERE EXISTS (SELECT 1 FROM event e WHERE e.JobFileId = jf.Id)
    ");
    
    echo "✓ DateOpen et DateClose mis à jour\n\n";
}

// ÉTAPE 2 : Validation
echo "ÉTAPE 2 : Validation des règles\n\n";

$in_family = $conn->query("SELECT Id FROM family WHERE Label = 'In'")->fetch_assoc()['Id'];
$out_family = $conn->query("SELECT Id FROM family WHERE Label = 'Out'")->fetch_assoc()['Id'];

// Vérifier qu'aucun événement n'est après aujourd'hui
$future_check = $conn->query("
    SELECT COUNT(*) as count FROM event 
    WHERE DATE(EventDate) > '$today_str'
")->fetch_assoc()['count'];

echo "✓ Événements après aujourd'hui: " . $future_check . " " . ($future_check == 0 ? "✅" : "❌") . "\n";

// Règle 1 : Un seul IN et OUT
$violations_rule1 = $conn->query("
    SELECT COUNT(*) as count FROM (
        SELECT jf.Id
        FROM jobfile jf
        LEFT JOIN event e ON jf.Id = e.JobFileId
        LEFT JOIN eventtype et ON e.EventTypeId = et.Id
        GROUP BY jf.Id
        HAVING SUM(CASE WHEN et.FamilyId = $in_family THEN 1 ELSE 0 END) != 1
           OR SUM(CASE WHEN et.FamilyId = $out_family THEN 1 ELSE 0 END) != 1
    ) as t
")->fetch_assoc()['count'];

echo "✓ Règle 1 (1 IN + 1 OUT): " . ($violations_rule1 == 0 ? "✅ OK" : "❌ " . $violations_rule1 . " violations") . "\n";

// Règle 3 : DateOpen/DateClose
$violations_rule3 = $conn->query("
    SELECT COUNT(*) as count FROM jobfile jf
    WHERE jf.DateOpen != (SELECT MIN(EventDate) FROM event WHERE JobFileId = jf.Id)
       OR jf.DateClose != (SELECT MAX(EventDate) FROM event WHERE JobFileId = jf.Id)
")->fetch_assoc()['count'];

echo "✓ Règle 3 (DateOpen/DateClose): " . ($violations_rule3 == 0 ? "✅ OK" : "❌ " . $violations_rule3 . " violations") . "\n";

// Règle 4 : IN des conteneurs
$violations_rule4 = $conn->query("
    SELECT COUNT(*) as count FROM jobfile jf
    JOIN blitem_jobfile bjf ON jf.Id = bjf.JobFile_Id
    JOIN blitem bli ON bjf.BLItem_Id = bli.Id
    JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    JOIN family f ON et.FamilyId = f.Id
    WHERE yit.Id = 1 -- Conteneurs
    AND f.Label = 'In'
    AND et.Id NOT IN (SELECT EventType_Id FROM contract_eventtype)
")->fetch_assoc()['count'];

echo "✓ Règle 4 (IN conteneurs): " . ($violations_rule4 == 0 ? "✅ OK" : "❌ " . $violations_rule4 . " violations") . "\n";

echo "\n=== RÉSUMÉ FINAL ===\n";
$total_jf = $conn->query("SELECT COUNT(*) as count FROM jobfile")->fetch_assoc()['count'];
$total_events = $conn->query("SELECT COUNT(*) as count FROM event")->fetch_assoc()['count'];

echo "Total jobfiles: $total_jf\n";
echo "Total événements: $total_events\n";

if ($future_check == 0 && $violations_rule1 == 0 && $violations_rule3 == 0 && $violations_rule4 == 0) {
    echo "\n✅✅✅ TOUTES LES RÈGLES SONT RESPECTÉES ✅✅✅\n";
} else {
    echo "\n❌ Des violations détectées\n";
}

$conn->close();
?>
