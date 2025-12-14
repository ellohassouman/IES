<?php
$db = new PDO('mysql:host=localhost;dbname=ies', 'root', '');

echo "🧹 NETTOYAGE: UN ITEM = UN JOBFILE\n";
echo str_repeat("=", 70) . "\n\n";

// 1. Analyser les items avec plusieurs jobfiles
echo "1️⃣ ANALYSE: ITEMS AVEC PLUSIEURS JOBFILES:\n";
$result = $db->query("
    SELECT 
        bij.BLItem_Id,
        COUNT(DISTINCT bij.JobFile_Id) as JobFileCount,
        GROUP_CONCAT(DISTINCT bij.JobFile_Id ORDER BY bij.JobFile_Id) as JobFileIds
    FROM blitem_jobfile bij
    GROUP BY bij.BLItem_Id
    HAVING JobFileCount > 1
");
$itemsMultiple = $result->fetchAll(PDO::FETCH_ASSOC);
echo "   Items avec plusieurs jobfiles: " . count($itemsMultiple) . "\n\n";

if(count($itemsMultiple) > 0) {
    echo "   Exemples:\n";
    foreach(array_slice($itemsMultiple, 0, 5) as $m) {
        echo "   - BLItem {$m['BLItem_Id']}: {$m['JobFileCount']} jobfiles ({$m['JobFileIds']})\n";
    }
}

// 2. Stratégie: Pour chaque item, garder le jobfile COMPLET (avec OUT)
// Si pas de complet, garder le plus récent
echo "\n2️⃣ STRATÉGIE DE SÉLECTION:\n";
echo "   - Préférence 1: JobFile avec OUT (cycle complet)\n";
echo "   - Préférence 2: JobFile le plus récent (DateOpen le plus récent)\n";
echo "   - Supprimer: Les autres jobfiles\n\n";

// 3. Pour chaque item problématique, identifier le jobfile à garder
$toDelete = [];

foreach($itemsMultiple as $item) {
    $itemId = $item['BLItem_Id'];
    $jobFileIds = explode(',', $item['JobFileIds']);
    
    // Récupérer les infos de tous les jobfiles
    $idStr = implode(',', $jobFileIds);
    $result = $db->query("
        SELECT 
            jf.Id,
            jf.DateOpen,
            jf.DateClose,
            (SELECT COUNT(*) FROM event WHERE JobFileId = jf.Id) as EventCount,
            (SELECT COUNT(*) FROM event e LEFT JOIN eventtype et ON e.EventTypeId = et.Id LEFT JOIN family f ON et.FamilyId = f.Id WHERE e.JobFileId = jf.Id AND f.Label = 'Out') as HasOut
        FROM jobfile jf
        WHERE jf.Id IN ($idStr)
    ");
    $jobfiles = $result->fetchAll(PDO::FETCH_ASSOC);
    
    // Sélectionner le meilleur
    $best = null;
    
    // 1. Chercher un avec OUT (cycle complet)
    foreach($jobfiles as $jf) {
        if($jf['HasOut'] > 0) {
            $best = $jf['Id'];
            break;
        }
    }
    
    // 2. Si pas de OUT, prendre le plus récent
    if(!$best) {
        $best = $jobfiles[0]['Id'];
        foreach($jobfiles as $jf) {
            if(strtotime($jf['DateOpen']) > strtotime($jobfiles[array_search($best, array_column($jobfiles, 'Id'))]['DateOpen'])) {
                $best = $jf['Id'];
            }
        }
    }
    
    // Marquer les autres pour suppression
    foreach($jobfiles as $jf) {
        if($jf['Id'] != $best) {
            $toDelete[] = [
                'itemId' => $itemId,
                'jobFileId' => $jf['Id'],
                'keep' => $best
            ];
        }
    }
}

echo "3️⃣ RELATIONS À SUPPRIMER:\n";
echo "   Total relations à supprimer: " . count($toDelete) . "\n";

// 4. Afficher les détails
echo "\n   Détails (premiers 10):\n";
foreach(array_slice($toDelete, 0, 10) as $del) {
    echo "   - BLItem {$del['itemId']}: garder JobFile {$del['keep']}, supprimer {$del['jobFileId']}\n";
}

// 5. Supprimer les relations
echo "\n4️⃣ SUPPRESSION:\n";
$deletedCount = 0;

foreach($toDelete as $del) {
    $deleted = $db->exec("
        DELETE FROM blitem_jobfile
        WHERE BLItem_Id = {$del['itemId']}
        AND JobFile_Id = {$del['jobFileId']}
    ");
    $deletedCount += $deleted;
}

echo "   Lignes supprimées: $deletedCount\n";

// 6. Vérification
echo "\n5️⃣ VÉRIFICATION:\n";
$result = $db->query("
    SELECT 
        COUNT(DISTINCT BLItem_Id) as ItemsWithMultiple
    FROM (
        SELECT 
            BLItem_Id,
            COUNT(DISTINCT JobFile_Id) as JobFileCount
        FROM blitem_jobfile
        GROUP BY BLItem_Id
        HAVING JobFileCount > 1
    ) as t
");
$stillMultiple = $result->fetch(PDO::FETCH_ASSOC)['ItemsWithMultiple'];

if($stillMultiple == 0) {
    echo "   ✅ PARFAIT! Tous les items ont maintenant UN SEUL jobfile\n";
} else {
    echo "   ⚠️  Encore $stillMultiple items avec plusieurs jobfiles\n";
}

// 7. Vérifier l'item MSCU9876543
echo "\n6️⃣ VÉRIFICATION ITEM MSCU9876543:\n";
$result = $db->query("
    SELECT COUNT(DISTINCT bij.JobFile_Id) as JobFileCount
    FROM blitem_jobfile bij
    WHERE bij.BLItem_Id = 5
");
$count = $result->fetch(PDO::FETCH_ASSOC)['JobFileCount'];
echo "   Nombre de jobfiles: $count (devrait être 1)\n";

// 8. Afficher les événements totaux maintenant
echo "\n7️⃣ TOTAL ÉVÉNEMENTS POUR MSCU9876543:\n";
$result = $db->query("
    SELECT COUNT(*) as TotalEvents
    FROM blitem_jobfile bij
    LEFT JOIN jobfile jf ON bij.JobFile_Id = jf.Id
    LEFT JOIN event e ON jf.Id = e.JobFileId
    WHERE bij.BLItem_Id = 5
");
$totalEvents = $result->fetch(PDO::FETCH_ASSOC)['TotalEvents'];
echo "   Total événements: $totalEvents\n";

echo "\n✅ NETTOYAGE COMPLÉTÉ\n";
?>
