<?php
/**
 * MAINTENANCE CORE - Opérations de maintenance critiques
 * Fusion de 4 scripts essentiels:
 * 1. Synchronisation EventType (Excel)
 * 2. Déduplications BLItem-JobFile
 * 3. Correction DateClose
 * 4. Optimisation Procedures
 */

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$conn = new mysqli('localhost', 'root', '', 'ies');
if ($conn->connect_error) {
    die("❌ Connexion échouée: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$action = $argv[1] ?? 'menu';

echo "\n" . str_repeat("=", 80) . "\n";
echo "🔧 MAINTENANCE CORE - Opérations Critiques\n";
echo str_repeat("=", 80) . "\n";

switch ($action) {
    case 'sync-eventtype':
        syncEventType();
        break;
    
    case 'dedup-blitem':
        dedupBLItem();
        break;
    
    case 'fix-dateclose':
        fixDateClose();
        break;
    
    case 'optimize-procedures':
        optimizeProcedures();
        break;
    
    case 'all':
        echo "\n📌 Exécution de toutes les opérations...\n\n";
        syncEventType();
        dedupBLItem();
        fixDateClose();
        optimizeProcedures();
        break;
    
    default:
        showMenu();
}

function showMenu() {
    echo "\nUsage: php maintenance_core.php [action]\n\n";
    echo "Actions disponibles:\n";
    echo "  sync-eventtype      - Synchronise EventType depuis Excel\n";
    echo "  dedup-blitem        - Déduplique BLItem-JobFile\n";
    echo "  fix-dateclose       - Corrige DateClose des jobfiles\n";
    echo "  optimize-procedures - Optimise les procédures stockées\n";
    echo "  all                 - Exécute toutes les opérations\n";
}

/**
 * 1. SYNCHRONISATION EVENTTYPE
 */
function syncEventType() {
    global $conn;
    
    echo "\n📌 1. SYNCHRONISATION EVENTTYPE\n";
    echo str_repeat("-", 80) . "\n";
    
    $excel_file = 'd:\\Websites\\IES\\All Assets\\IPAKI SAMPLE DATA.xlsx';
    
    if (!file_exists($excel_file)) {
        echo "❌ Fichier Excel non trouvé: $excel_file\n";
        return;
    }
    
    $spreadsheet = IOFactory::load($excel_file);
    $sheet = $spreadsheet->getSheetByName('EventType');
    
    $excel_data = [];
    foreach ($sheet->getRowIterator(2) as $row) {
        $cells = $row->getCellIterator();
        $col_idx = 0;
        $row_data = [];
        foreach ($cells as $cell) {
            if ($col_idx == 0) $row_data['id'] = $cell->getValue();
            if ($col_idx == 1) $row_data['code'] = $cell->getValue();
            if ($col_idx == 2) $row_data['familyId'] = $cell->getValue();
            if ($col_idx == 3) $row_data['billable'] = $cell->getValue();
            if ($col_idx == 4) $row_data['name'] = $cell->getValue();
            $col_idx++;
        }
        if (isset($row_data['id'])) {
            $excel_data[$row_data['id']] = $row_data;
        }
    }
    
    echo "✅ Excel chargé: " . count($excel_data) . " types d'événements\n\n";
    
    $updated_count = 0;
    $errors = [];
    
    foreach ($excel_data as $id => $excel_row) {
        $updates = [];
        
        if (isset($excel_row['code']) && !empty($excel_row['code'])) {
            $updates[] = "Code = '" . $conn->real_escape_string(trim($excel_row['code'])) . "'";
        }
        if (isset($excel_row['name']) && !empty($excel_row['name'])) {
            $updates[] = "Label = '" . $conn->real_escape_string(trim($excel_row['name'])) . "'";
        }
        if (isset($excel_row['familyId'])) {
            $updates[] = "FamilyId = " . (int)$excel_row['familyId'];
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE eventtype SET " . implode(", ", $updates) . " WHERE Id = " . (int)$id;
            
            if ($conn->query($sql)) {
                $updated_count++;
            } else {
                $errors[] = "ID $id: " . $conn->error;
            }
        }
    }
    
    printf("✅ %d mises à jour réussies\n", $updated_count);
    if (!empty($errors)) {
        echo "⚠️ Erreurs: " . count($errors) . "\n";
    }
}

/**
 * 2. DÉDUPLICATIONS BLITEM-JOBFILE
 */
function dedupBLItem() {
    global $conn;
    
    echo "\n📌 2. DÉDUPLICATIONS BLITEM-JOBFILE\n";
    echo str_repeat("-", 80) . "\n";
    
    // Chercher items avec plusieurs jobfiles
    $result = $conn->query("
        SELECT bi.YardItemNumber, COUNT(DISTINCT bjf.JobFileId) as jf_count
        FROM blitem bi
        JOIN blitem_jobfile bjf ON bi.Id = bjf.BLItemId
        GROUP BY bi.Id
        HAVING jf_count > 1
    ");
    
    $problematic_items = [];
    while ($row = $result->fetch_assoc()) {
        $problematic_items[] = $row;
    }
    
    if (empty($problematic_items)) {
        echo "✅ Aucun item avec plusieurs jobfiles\n";
        return;
    }
    
    echo "⚠️  " . count($problematic_items) . " items avec plusieurs jobfiles\n\n";
    
    $deleted_count = 0;
    foreach ($problematic_items as $item) {
        // Garder le jobfile avec OUT, sinon le plus récent
        $sql = "
            SELECT bjf.JobFileId, COUNT(e.Id) as evt_count, MAX(e.EventDate) as latest_date
            FROM blitem_jobfile bjf
            JOIN jobfile jf ON bjf.JobFileId = jf.Id
            LEFT JOIN event e ON jf.Id = e.JobFileId
            WHERE bjf.BLItemId = (SELECT Id FROM blitem WHERE YardItemNumber = '" . $item['YardItemNumber'] . "')
            GROUP BY bjf.JobFileId
            ORDER BY (jf.DateClose IS NOT NULL) DESC, latest_date DESC
            LIMIT 1
        ";
        
        $keep_result = $conn->query($sql);
        $keep_row = $keep_result->fetch_assoc();
        $keep_id = $keep_row['JobFileId'];
        
        // Supprimer les autres
        $delete_sql = "
            DELETE FROM blitem_jobfile 
            WHERE BLItemId = (SELECT Id FROM blitem WHERE YardItemNumber = '" . $item['YardItemNumber'] . "')
            AND JobFileId != " . (int)$keep_id
        ";
        
        $conn->query($delete_sql);
        $deleted_count += $conn->affected_rows;
    }
    
    printf("✅ %d relations supprimées\n", $deleted_count);
}

/**
 * 3. CORRECTION DATECLOSE
 */
function fixDateClose() {
    global $conn;
    
    echo "\n📌 3. CORRECTION DATECLOSE\n";
    echo str_repeat("-", 80) . "\n";
    
    // Jobfiles sans OUT mais avec DateClose
    $result = $conn->query("
        SELECT jf.Id, jf.DateClose
        FROM jobfile jf
        WHERE jf.DateClose IS NOT NULL
        AND NOT EXISTS (
            SELECT 1 FROM event e 
            WHERE e.JobFileId = jf.Id 
            AND e.EventTypeId IN (
                SELECT et.Id FROM eventtype et 
                JOIN family f ON et.FamilyId = f.Id 
                WHERE f.Label = 'Out'
            )
        )
    ");
    
    $null_count = 0;
    $ids_to_null = [];
    while ($row = $result->fetch_assoc()) {
        $ids_to_null[] = $row['Id'];
    }
    
    if (!empty($ids_to_null)) {
        $conn->query("UPDATE jobfile SET DateClose = NULL WHERE Id IN (" . implode(",", $ids_to_null) . ")");
        $null_count = $conn->affected_rows;
    }
    
    // Jobfiles avec OUT mais sans DateClose
    $result = $conn->query("
        SELECT DISTINCT jf.Id, MAX(e.EventDate) as max_date
        FROM jobfile jf
        JOIN event e ON jf.Id = e.JobFileId
        WHERE jf.DateClose IS NULL
        AND e.EventTypeId IN (
            SELECT et.Id FROM eventtype et 
            JOIN family f ON et.FamilyId = f.Id 
            WHERE f.Label = 'Out'
        )
        GROUP BY jf.Id
    ");
    
    $set_count = 0;
    while ($row = $result->fetch_assoc()) {
        $conn->query("UPDATE jobfile SET DateClose = '" . $row['max_date'] . "' WHERE Id = " . $row['Id']);
        $set_count++;
    }
    
    printf("✅ %d DateClose remis à NULL\n", $null_count);
    printf("✅ %d DateClose définis\n", $set_count);
}

/**
 * 4. OPTIMISATION PROCEDURES
 */
function optimizeProcedures() {
    global $conn;
    
    echo "\n📌 4. OPTIMISATION PROCÉDURES\n";
    echo str_repeat("-", 80) . "\n";
    
    $procedure_sql = "
        DROP PROCEDURE IF EXISTS GetYardItemTrackingMovements;
        CREATE PROCEDURE GetYardItemTrackingMovements(
            IN p_YardItemId INT,
            IN p_YardItemNumber VARCHAR(100),
            IN p_BillOfLadingNumber VARCHAR(100)
        )
        BEGIN
            SELECT 
                e.EventDate as Date,
                et.Label as EventTypeName,
                et.Code as EventTypeCode,
                e.CreatedByIES,
                e.Position
            FROM event e
            INNER JOIN eventtype et ON e.EventTypeId = et.Id
            INNER JOIN jobfile jf ON e.JobFileId = jf.Id
            INNER JOIN blitem bi ON jf.Id = bi.JobFileId
            INNER JOIN bl ON bi.BLId = bl.Id
            WHERE 
                (p_YardItemId IS NULL OR bi.Id = p_YardItemId)
                AND (p_YardItemNumber IS NULL OR bi.YardItemNumber = p_YardItemNumber)
                AND (p_BillOfLadingNumber IS NULL OR bl.BlNumber = p_BillOfLadingNumber)
            ORDER BY e.EventDate ASC;
        END;
    ";
    
    foreach (explode(";", $procedure_sql) as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            $conn->multi_query($stmt . ";");
            while ($conn->next_result()) {}
        }
    }
    
    echo "✅ Procédure GetYardItemTrackingMovements optimisée (INNER JOINs)\n";
}

$conn->close();
echo "\n✅ Maintenance complète!\n";
echo str_repeat("=", 80) . "\n\n";
?>
