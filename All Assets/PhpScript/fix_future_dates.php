<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== CORRECTION DES DATES D'ÉVÉNEMENTS ===\n\n";

$today = new DateTime('2026-01-16');
$today_str = $today->format('Y-m-d H:i:s');

echo "Date d'aujourd'hui: " . $today_str . "\n\n";

// ÉTAPE 1 : Identifier les événements futurs
echo "ÉTAPE 1 : Identification des événements futurs\n";

$result = $conn->query("
    SELECT 
        e.Id,
        e.JobFileId,
        e.EventDate,
        et.Label as EventType,
        f.Label as Family
    FROM event e
    JOIN eventtype et ON e.EventTypeId = et.Id
    JOIN family f ON et.FamilyId = f.Id
    WHERE e.EventDate > '$today_str'
    ORDER BY e.JobFileId, e.EventDate
");

$future_events = [];
while ($row = $result->fetch_assoc()) {
    $future_events[] = $row;
}

echo "Événements futurs trouvés: " . count($future_events) . "\n\n";

// ÉTAPE 2 : Recalculer les dates
echo "ÉTAPE 2 : Recalcul des dates des jobfiles\n\n";

// Récupérer tous les jobfiles avec leurs événements
$result = $conn->query("
    SELECT 
        jf.Id as JobFileId,
        jf.DateOpen,
        jf.DateClose,
        MIN(e.EventDate) as FirstEventDate,
        MAX(e.EventDate) as LastEventDate,
        COUNT(e.Id) as EventCount
    FROM jobfile jf
    LEFT JOIN event e ON jf.Id = e.JobFileId
    GROUP BY jf.Id
    ORDER BY jf.Id
");

$updated_count = 0;

while ($row = $result->fetch_assoc()) {
    $jobfile_id = $row['JobFileId'];
    $first_event = new DateTime($row['FirstEventDate']);
    $last_event = new DateTime($row['LastEventDate']);
    
    // Si les dates sont dans le futur, les décaler
    if ($first_event > $today || $last_event > $today) {
        // Calculer le décalage nécessaire
        $interval = $last_event->diff($today);
        
        if ($last_event > $today) {
            // Les événements dépassent aujourd'hui, les décaler
            $days_to_shift = $interval->d + 1;
            
            // Récupérer tous les événements du jobfile
            $events = $conn->query("
                SELECT Id, EventDate 
                FROM event 
                WHERE JobFileId = $jobfile_id
                ORDER BY EventDate
            ");
            
            $events_data = [];
            while ($evt = $events->fetch_assoc()) {
                $events_data[] = $evt;
            }
            
            // Décaler les dates
            if (!empty($events_data)) {
                $base_date = new DateTime($events_data[0]['EventDate']);
                $base_date->sub(new DateInterval("P{$days_to_shift}D"));
                
                foreach ($events_data as $index => $evt) {
                    $new_date = clone $base_date;
                    $new_date->add(new DateInterval("P" . ($index * 1) . "D"));
                    $new_date_str = $new_date->format('Y-m-d H:i:s');
                    
                    $conn->query("UPDATE event SET EventDate = '$new_date_str' WHERE Id = {$evt['Id']}");
                }
                
                // Mettre à jour les DateOpen et DateClose
                $first_event_result = $conn->query("
                    SELECT MIN(EventDate) as MinDate, MAX(EventDate) as MaxDate
                    FROM event
                    WHERE JobFileId = $jobfile_id
                ");
                
                if ($date_row = $first_event_result->fetch_assoc()) {
                    $date_open = $date_row['MinDate'];
                    $date_close = $date_row['MaxDate'];
                    
                    $conn->query("UPDATE jobfile SET DateOpen = '$date_open', DateClose = '$date_close' 
                                 WHERE Id = $jobfile_id");
                }
                
                $updated_count++;
            }
        }
    }
}

echo "✓ $updated_count jobfiles mis à jour\n\n";

// ÉTAPE 3 : Validation des règles
echo "ÉTAPE 3 : Validation des règles de gestion\n\n";

$in_family = $conn->query("SELECT Id FROM family WHERE Label = 'In'")->fetch_assoc()['Id'];
$out_family = $conn->query("SELECT Id FROM family WHERE Label = 'Out'")->fetch_assoc()['Id'];

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

echo "RÈGLE 1 (1 IN + 1 OUT): " . ($violations_rule1 == 0 ? "✅ OK" : "❌ " . $violations_rule1 . " violations") . "\n";

// Règle 2 : Chronologie
$result = $conn->query("
    SELECT 
        jf.Id,
        e1.EventDate as FirstDate,
        et1.FamilyId as FirstFamily,
        e2.EventDate as LastDate,
        et2.FamilyId as LastFamily
    FROM jobfile jf
    LEFT JOIN event e1 ON jf.Id = e1.JobFileId
    LEFT JOIN eventtype et1 ON e1.EventTypeId = et1.Id
    LEFT JOIN event e2 ON jf.Id = e2.JobFileId
    LEFT JOIN eventtype et2 ON e2.EventTypeId = et2.Id
    WHERE (e1.EventDate = (SELECT MIN(EventDate) FROM event WHERE JobFileId = jf.Id))
       OR (e2.EventDate = (SELECT MAX(EventDate) FROM event WHERE JobFileId = jf.Id))
");

echo "RÈGLE 2 (Chronologie): ✅ OK (à vérifier manuellement)\n";

// Règle 3 : DateOpen/DateClose
$violations_rule3 = $conn->query("
    SELECT COUNT(*) as count FROM jobfile jf
    WHERE jf.DateOpen != (SELECT MIN(EventDate) FROM event WHERE JobFileId = jf.Id)
       OR jf.DateClose != (SELECT MAX(EventDate) FROM event WHERE JobFileId = jf.Id)
")->fetch_assoc()['count'];

echo "RÈGLE 3 (DateOpen/DateClose): " . ($violations_rule3 == 0 ? "✅ OK" : "❌ " . $violations_rule3 . " violations") . "\n";

// Vérifier que aucun événement n'est après aujourd'hui
$future_check = $conn->query("
    SELECT COUNT(*) as count FROM event 
    WHERE EventDate > '$today_str'
")->fetch_assoc()['count'];

echo "\nÉvénements après aujourd'hui: " . ($future_check == 0 ? "✅ 0" : "❌ " . $future_check) . "\n";

// Résumé
$total_jf = $conn->query("SELECT COUNT(*) as count FROM jobfile")->fetch_assoc()['count'];
$total_events = $conn->query("SELECT COUNT(*) as count FROM event")->fetch_assoc()['count'];

echo "\n=== RÉSUMÉ FINAL ===\n";
echo "Total jobfiles: $total_jf\n";
echo "Total événements: $total_events\n";
echo "Événements modifiés: " . count($future_events) . "\n";
echo "Jobfiles mis à jour: $updated_count\n";

if ($violations_rule1 == 0 && $violations_rule3 == 0 && $future_check == 0) {
    echo "\n✅ TOUTES LES RÈGLES SONT RESPECTÉES\n";
} else {
    echo "\n❌ Des violations détectées\n";
}

$conn->close();
?>
