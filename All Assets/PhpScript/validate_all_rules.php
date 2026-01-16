<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== VALIDATION COMPLÈTE DES RÈGLES DE GESTION ===\n\n";

// Récupérer les familles
$in_family = $conn->query("SELECT Id FROM family WHERE Label = 'In'")->fetch_assoc()['Id'];
$out_family = $conn->query("SELECT Id FROM family WHERE Label = 'Out'")->fetch_assoc()['Id'];

$violations = [];

// RÈGLE 1 : Un seul IN et un seul OUT par jobfile
echo "RÈGLE 1 : Un seul IN et un seul OUT par jobfile\n";
$result = $conn->query("
    SELECT 
        jf.Id,
        SUM(CASE WHEN et.FamilyId = $in_family THEN 1 ELSE 0 END) as INCount,
        SUM(CASE WHEN et.FamilyId = $out_family THEN 1 ELSE 0 END) as OUTCount
    FROM jobfile jf
    LEFT JOIN event e ON jf.Id = e.JobFileId
    LEFT JOIN eventtype et ON e.EventTypeId = et.Id
    GROUP BY jf.Id
    HAVING INCount > 1 OR OUTCount > 1
");

if ($result->num_rows == 0) {
    echo "✅ Règle 1 respectée : tous les jobfiles ont exactement 1 IN et 1 OUT\n\n";
} else {
    echo "❌ Violations détectées : " . $result->num_rows . " jobfiles\n";
    while ($row = $result->fetch_assoc()) {
        echo "   JobFile {$row['Id']}: IN={$row['INCount']}, OUT={$row['OUTCount']}\n";
        $violations[] = "Règle 1";
    }
    echo "\n";
}

// RÈGLE 2 : Chronologie des événements (IN premier, OUT dernier)
echo "RÈGLE 2 : Chronologie des événements\n";
$result = $conn->query("
    SELECT 
        jf.Id as JobFileId,
        e.Id as EventId,
        et.Label,
        et.FamilyId,
        e.EventDate,
        COUNT(*) OVER (PARTITION BY jf.Id ORDER BY e.EventDate ASC) as RowNum,
        CASE 
            WHEN et.FamilyId = $in_family THEN 'IN'
            WHEN et.FamilyId = $out_family THEN 'OUT'
            ELSE 'INTERMEDIATE'
        END as EventCategory
    FROM jobfile jf
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    ORDER BY jf.Id, e.EventDate
");

$violations_rule2 = 0;
$last_jobfile = null;
$first_event_type = null;
$last_event_type = null;
$current_events = [];

while ($row = $result->fetch_assoc()) {
    if ($last_jobfile !== $row['JobFileId']) {
        // Vérifier le jobfile précédent
        if (!empty($current_events)) {
            // Le premier doit être IN
            if ($current_events[0]['EventCategory'] !== 'IN') {
                echo "❌ JobFile {$last_jobfile}: premier event n'est pas IN\n";
                $violations_rule2++;
            }
            // Le dernier doit être OUT
            if (count($current_events) > 1 && end($current_events)['EventCategory'] !== 'OUT') {
                echo "❌ JobFile {$last_jobfile}: dernier event n'est pas OUT\n";
                $violations_rule2++;
            }
        }
        
        $current_events = [];
        $last_jobfile = $row['JobFileId'];
    }
    
    $current_events[] = $row;
}

// Vérifier le dernier jobfile
if (!empty($current_events)) {
    if ($current_events[0]['EventCategory'] !== 'IN') {
        echo "❌ JobFile {$last_jobfile}: premier event n'est pas IN\n";
        $violations_rule2++;
    }
    if (count($current_events) > 1 && end($current_events)['EventCategory'] !== 'OUT') {
        echo "❌ JobFile {$last_jobfile}: dernier event n'est pas OUT\n";
        $violations_rule2++;
    }
}

if ($violations_rule2 == 0) {
    echo "✅ Règle 2 respectée : chronologie correcte (IN first, OUT last)\n\n";
} else {
    echo "❌ $violations_rule2 violations\n\n";
    $violations[] = "Règle 2";
}

// RÈGLE 3 : DateOpen = date du IN, DateClose = date du OUT
echo "RÈGLE 3 : DateOpen/DateClose correspondent aux dates des événements\n";
$result = $conn->query("
    SELECT 
        jf.Id,
        jf.DateOpen,
        jf.DateClose,
        MIN(CASE WHEN et.FamilyId = $in_family THEN e.EventDate END) as INDate,
        MAX(CASE WHEN et.FamilyId = $out_family THEN e.EventDate END) as OUTDate
    FROM jobfile jf
    LEFT JOIN event e ON jf.Id = e.JobFileId
    LEFT JOIN eventtype et ON e.EventTypeId = et.Id
    GROUP BY jf.Id
");

$violations_rule3 = 0;
while ($row = $result->fetch_assoc()) {
    // Vérifier DateOpen
    if ($row['INDate'] && $row['DateOpen'] !== $row['INDate']) {
        echo "❌ JobFile {$row['Id']}: DateOpen ({$row['DateOpen']}) ≠ IN date ({$row['INDate']})\n";
        $violations_rule3++;
    }
    
    // Vérifier DateClose
    if ($row['OUTDate'] && $row['DateClose'] !== $row['OUTDate']) {
        echo "❌ JobFile {$row['Id']}: DateClose ({$row['DateClose']}) ≠ OUT date ({$row['OUTDate']})\n";
        $violations_rule3++;
    }
    
    // Si pas d'OUT, DateClose doit être NULL
    if (!$row['OUTDate'] && $row['DateClose'] !== null) {
        echo "❌ JobFile {$row['Id']}: pas de OUT mais DateClose n'est pas NULL\n";
        $violations_rule3++;
    }
}

if ($violations_rule3 == 0) {
    echo "✅ Règle 3 respectée : DateOpen et DateClose corrects\n\n";
} else {
    echo "❌ $violations_rule3 violations\n\n";
    $violations[] = "Règle 3";
}

// RÈGLE 4 : Les IN des conteneurs doivent être dans contract_eventtype
echo "RÈGLE 4 : IN des conteneurs doivent être dans contract_eventtype\n";
$result = $conn->query("
    SELECT 
        jf.Id as JobFileId,
        bli.Number as ItemNumber,
        yit.Label as ItemType,
        et.Label as EventLabel,
        et.Id as EventTypeId
    FROM jobfile jf
    JOIN blitem_jobfile bjf ON jf.Id = bjf.JobFile_Id
    JOIN blitem bli ON bjf.BLItem_Id = bli.Id
    JOIN yarditemtype yit ON bli.ItemTypeId = yit.Id
    JOIN event e ON jf.Id = e.JobFileId
    JOIN eventtype et ON e.EventTypeId = et.Id
    WHERE yit.Id = 1 -- Conteneurs
    AND et.FamilyId = $in_family -- Events IN
");

$violations_rule4 = 0;
while ($row = $result->fetch_assoc()) {
    // Vérifier si cet event type est dans contract_eventtype
    $check = $conn->query("
        SELECT COUNT(*) as count 
        FROM contract_eventtype 
        WHERE EventType_Id = {$row['EventTypeId']}
    ")->fetch_assoc()['count'];
    
    if ($check == 0) {
        echo "❌ JobFile {$row['JobFileId']} (Conteneur {$row['ItemNumber']}): Event IN '{$row['EventLabel']}' pas dans contract_eventtype\n";
        $violations_rule4++;
    }
}

if ($violations_rule4 == 0) {
    echo "✅ Règle 4 respectée : tous les IN de conteneurs sont dans contract_eventtype\n\n";
} else {
    echo "❌ $violations_rule4 violations\n\n";
    $violations[] = "Règle 4";
}

// RÉSUMÉ FINAL
echo "=== RÉSUMÉ FINAL ===\n\n";

$total_jobfiles = $conn->query("SELECT COUNT(*) as count FROM jobfile")->fetch_assoc()['count'];
$total_events = $conn->query("SELECT COUNT(*) as count FROM event")->fetch_assoc()['count'];
$total_blitems = $conn->query("SELECT COUNT(*) as count FROM blitem")->fetch_assoc()['count'];

echo "Total jobfiles: $total_jobfiles\n";
echo "Total événements: $total_events\n";
echo "Total BLItems: $total_blitems\n\n";

if (empty($violations)) {
    echo "✅✅✅ TOUTES LES RÈGLES DE GESTION SONT RESPECTÉES ✅✅✅\n";
} else {
    echo "❌ Violations détectées dans : " . implode(', ', array_unique($violations)) . "\n";
}

$conn->close();
?>
