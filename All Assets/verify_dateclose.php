<?php
$db = new PDO('mysql:host=localhost;dbname=ies', 'root', '');

echo "✅ VÉRIFICATION FINALE: DATECLOSE POUR LES CYCLES EN COURS\n";
echo str_repeat("=", 70) . "\n\n";

// Vérifier les jobfiles sans OUT
$result = $db->query("
    SELECT 
        jf.Id,
        jf.DateOpen,
        jf.DateClose,
        COUNT(DISTINCT e.Id) as EventCount
    FROM jobfile jf
    LEFT JOIN event e ON jf.Id = e.JobFileId
    LEFT JOIN eventtype et ON e.EventTypeId = et.Id
    LEFT JOIN family f ON et.FamilyId = f.Id
    GROUP BY jf.Id
    HAVING (SELECT COUNT(*) FROM event e LEFT JOIN eventtype et ON e.EventTypeId = et.Id LEFT JOIN family f ON et.FamilyId = f.Id WHERE e.JobFileId = jf.Id AND f.Label = 'Out') = 0
");
$jobsNoOut = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Jobfiles SANS OUT (96 attendus): " . count($jobsNoOut) . "\n\n";

// Vérifier l'état de DateClose
$nullDateClose = 0;
$notNullDateClose = 0;

foreach($jobsNoOut as $j) {
    if(is_null($j['DateClose'])) {
        $nullDateClose++;
    } else {
        $notNullDateClose++;
    }
}

echo "DateClose = NULL: $nullDateClose ✓\n";
echo "DateClose != NULL: $notNullDateClose ✗\n\n";

if($notNullDateClose > 0) {
    echo "⚠️  CORRECTION NÉCESSAIRE: Les jobfiles sans OUT doivent avoir DateClose = NULL\n\n";
    
    // Afficher les problèmes
    echo "Jobfiles à corriger:\n";
    $problematic = array_filter($jobsNoOut, function($j) { return !is_null($j['DateClose']); });
    foreach(array_slice($problematic, 0, 10) as $p) {
        echo "  - JobFile {$p['Id']}: DateClose = {$p['DateClose']} (devrait être NULL)\n";
    }
    
    // Corriger
    echo "\n🔧 Correction automatique...\n";
    $updated = $db->exec("
        UPDATE jobfile jf
        SET jf.DateClose = NULL
        WHERE jf.DateClose IS NOT NULL
        AND jf.Id NOT IN (
            SELECT DISTINCT e.JobFileId FROM event e
            LEFT JOIN eventtype et ON e.EventTypeId = et.Id
            LEFT JOIN family f ON et.FamilyId = f.Id
            WHERE f.Label = 'Out'
        )
    ");
    echo "   $updated jobfiles corrigés\n";
    
    // Vérification
    echo "\n✅ Vérification après correction:\n";
    $result = $db->query("
        SELECT 
            COUNT(*) as cnt
        FROM jobfile jf
        WHERE jf.DateClose IS NULL
        AND jf.Id NOT IN (
            SELECT DISTINCT e.JobFileId FROM event e
            LEFT JOIN eventtype et ON e.EventTypeId = et.Id
            LEFT JOIN family f ON et.FamilyId = f.Id
            WHERE f.Label = 'Out'
        )
    ");
    $corrected = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "   Jobfiles sans OUT avec DateClose = NULL: $corrected\n";
} else {
    echo "✅ PARFAIT! Tous les jobfiles sans OUT ont DateClose = NULL\n";
}

// Vérifier les jobfiles AVEC OUT
echo "\n📊 Vérification des jobfiles AVEC OUT:\n";
$result = $db->query("
    SELECT 
        COUNT(*) as cnt
    FROM jobfile jf
    WHERE jf.DateClose IS NULL
    AND jf.Id IN (
        SELECT DISTINCT e.JobFileId FROM event e
        LEFT JOIN eventtype et ON e.EventTypeId = et.Id
        LEFT JOIN family f ON et.FamilyId = f.Id
        WHERE f.Label = 'Out'
    )
");
$noDateCloseWithOut = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "   Jobfiles AVEC OUT mais DateClose = NULL: $noDateCloseWithOut\n";

if($noDateCloseWithOut > 0) {
    echo "\n⚠️  PROBLÈME: Les jobfiles avec OUT doivent avoir DateClose != NULL\n";
    
    // Afficher les problèmes
    echo "\nJobfiles à corriger:\n";
    $result = $db->query("
        SELECT 
            jf.Id,
            jf.DateOpen,
            jf.DateClose,
            MAX(e.EventDate) as LastEventDate
        FROM jobfile jf
        LEFT JOIN event e ON jf.Id = e.JobFileId
        LEFT JOIN eventtype et ON e.EventTypeId = et.Id
        LEFT JOIN family f ON et.FamilyId = f.Id
        WHERE jf.DateClose IS NULL
        AND jf.Id IN (
            SELECT DISTINCT e2.JobFileId FROM event e2
            LEFT JOIN eventtype et2 ON e2.EventTypeId = et2.Id
            LEFT JOIN family f2 ON et2.FamilyId = f2.Id
            WHERE f2.Label = 'Out'
        )
        GROUP BY jf.Id
        LIMIT 10
    ");
    $problems = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach($problems as $p) {
        echo "  - JobFile {$p['Id']}: LastEvent = {$p['LastEventDate']}, DateClose = NULL\n";
    }
    
    // Corriger: mettre DateClose = dernier événement
    echo "\n🔧 Correction automatique...\n";
    $updated = $db->exec("
        UPDATE jobfile jf
        SET jf.DateClose = (
            SELECT MAX(e.EventDate)
            FROM event e
            WHERE e.JobFileId = jf.Id
        )
        WHERE jf.DateClose IS NULL
        AND jf.Id IN (
            SELECT DISTINCT e.JobFileId FROM event e
            LEFT JOIN eventtype et ON e.EventTypeId = et.Id
            LEFT JOIN family f ON et.FamilyId = f.Id
            WHERE f.Label = 'Out'
        )
    ");
    echo "   $updated jobfiles corrigés\n";
}

echo "\n✅ VÉRIFICATION COMPLÉTÉE\n";
?>
