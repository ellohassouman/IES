<?php
/**
 * VERIFY INTEGRITY - Vérification complète de l'intégrité des données
 * Consolidation de tous les checks et diagnostics
 */

$conn = new mysqli('localhost', 'root', '', 'ies');
if ($conn->connect_error) {
    die("❌ Connexion échouée: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$action = $argv[1] ?? 'menu';

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ VÉRIFICATION INTÉGRITÉ - Diagnostic Complet\n";
echo str_repeat("=", 80) . "\n";

switch ($action) {
    case 'eventtype':
        checkEventType();
        break;
    
    case 'dateclose':
        checkDateClose();
        break;
    
    case 'cycles':
        checkCycles();
        break;
    
    case 'bl-items':
        checkBLItems();
        break;
    
    case 'invoices':
        checkInvoices();
        break;
    
    case 'access-control':
        checkAccessControl();
        break;
    
    case 'all':
        echo "\n📌 Exécution de tous les checks...\n\n";
        checkEventType();
        checkDateClose();
        checkCycles();
        checkBLItems();
        checkInvoices();
        checkAccessControl();
        break;
    
    default:
        showMenu();
}

function showMenu() {
    echo "\nUsage: php verify_integrity.php [action]\n\n";
    echo "Checks disponibles:\n";
    echo "  eventtype       - Vérifier EventType (68 types)\n";
    echo "  dateclose       - Vérifier DateClose des jobfiles\n";
    echo "  cycles          - Vérifier cycles de vie (IN→OUT)\n";
    echo "  bl-items        - Vérifier relations BL-Items\n";
    echo "  invoices        - Vérifier factures par BL\n";
    echo "  access-control  - Vérifier contrôle d'accès\n";
    echo "  all             - Tous les checks\n";
}

/**
 * CHECK 1: EventType
 */
function checkEventType() {
    global $conn;
    
    echo "\n📋 CHECK 1: EventType (68 types)\n";
    echo str_repeat("-", 80) . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM eventtype");
    $row = $result->fetch_assoc();
    $count = $row['cnt'];
    
    printf("✅ Total EventType: %d\n", $count);
    
    if ($count == 68) {
        echo "✅ Nombre correct (68)\n";
    } else {
        echo "❌ Erreur: attendu 68, trouvé $count\n";
    }
    
    // Afficher les codes invalides
    $result = $conn->query("
        SELECT Id, Code, Label FROM eventtype 
        WHERE Code IS NULL OR Code = '' OR LENGTH(Code) = 0 
        LIMIT 5
    ");
    
    if ($result->num_rows > 0) {
        echo "\n⚠️  EventType avec Code invalide:\n";
        while ($row = $result->fetch_assoc()) {
            printf("  ID %d: Code='%s' Label='%s'\n", $row['Id'], $row['Code'], $row['Label']);
        }
    } else {
        echo "✅ Tous les codes valides\n";
    }
}

/**
 * CHECK 2: DateClose
 */
function checkDateClose() {
    global $conn;
    
    echo "\n📋 CHECK 2: DateClose (Jobfiles)\n";
    echo str_repeat("-", 80) . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM jobfile");
    $row = $result->fetch_assoc();
    $total = $row['cnt'];
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM jobfile WHERE DateClose IS NOT NULL");
    $row = $result->fetch_assoc();
    $with_dateclose = $row['cnt'];
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM jobfile WHERE DateClose IS NULL");
    $row = $result->fetch_assoc();
    $without_dateclose = $row['cnt'];
    
    printf("✅ Total jobfiles: %d\n", $total);
    printf("   - Avec DateClose: %d (complétés)\n", $with_dateclose);
    printf("   - Sans DateClose: %d (en cours)\n", $without_dateclose);
    
    // Vérifier les inconsistances
    $result = $conn->query("
        SELECT COUNT(DISTINCT jf.Id) as cnt
        FROM jobfile jf
        WHERE (DateClose IS NOT NULL AND NOT EXISTS (
            SELECT 1 FROM event e 
            WHERE e.JobFileId = jf.Id 
            AND e.EventTypeId IN (
                SELECT Id FROM eventtype WHERE FamilyId IN (
                    SELECT Id FROM family WHERE Label = 'Out'
                )
            )
        )) OR (DateClose IS NULL AND EXISTS (
            SELECT 1 FROM event e 
            WHERE e.JobFileId = jf.Id 
            AND e.EventTypeId IN (
                SELECT Id FROM eventtype WHERE FamilyId IN (
                    SELECT Id FROM family WHERE Label = 'Out'
                )
            )
        ))
    ");
    
    $row = $result->fetch_assoc();
    $inconsistent = $row['cnt'];
    
    if ($inconsistent == 0) {
        echo "✅ DateClose cohérent avec les événements\n";
    } else {
        echo "⚠️  $inconsistent jobfiles avec DateClose incohérent\n";
    }
}

/**
 * CHECK 3: Cycles de Vie
 */
function checkCycles() {
    global $conn;
    
    echo "\n📋 CHECK 3: Cycles de Vie (IN→OUT)\n";
    echo str_repeat("-", 80) . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM blitem");
    $row = $result->fetch_assoc();
    $total_items = $row['cnt'];
    
    // Items avec IN
    $result = $conn->query("
        SELECT COUNT(DISTINCT bi.Id) as cnt
        FROM blitem bi
        JOIN jobfile jf ON bi.JobFileId = jf.Id
        JOIN event e ON jf.Id = e.JobFileId
        WHERE e.EventTypeId IN (
            SELECT et.Id FROM eventtype et 
            WHERE et.FamilyId IN (
                SELECT f.Id FROM family f WHERE f.Label = 'In'
            )
        )
    ");
    $row = $result->fetch_assoc();
    $with_in = $row['cnt'];
    
    // Items avec OUT
    $result = $conn->query("
        SELECT COUNT(DISTINCT bi.Id) as cnt
        FROM blitem bi
        JOIN jobfile jf ON bi.JobFileId = jf.Id
        JOIN event e ON jf.Id = e.JobFileId
        WHERE e.EventTypeId IN (
            SELECT et.Id FROM eventtype et 
            WHERE et.FamilyId IN (
                SELECT f.Id FROM family f WHERE f.Label = 'Out'
            )
        )
    ");
    $row = $result->fetch_assoc();
    $with_out = $row['cnt'];
    
    // Items avec IN ET OUT
    $result = $conn->query("
        SELECT COUNT(DISTINCT bi.Id) as cnt
        FROM blitem bi
        JOIN jobfile jf ON bi.JobFileId = jf.Id
        WHERE EXISTS (
            SELECT 1 FROM event e WHERE e.JobFileId = jf.Id
            AND e.EventTypeId IN (
                SELECT et.Id FROM eventtype et 
                WHERE et.FamilyId IN (SELECT f.Id FROM family f WHERE f.Label = 'In')
            )
        ) AND EXISTS (
            SELECT 1 FROM event e WHERE e.JobFileId = jf.Id
            AND e.EventTypeId IN (
                SELECT et.Id FROM eventtype et 
                WHERE et.FamilyId IN (SELECT f.Id FROM family f WHERE f.Label = 'Out')
            )
        )
    ");
    $row = $result->fetch_assoc();
    $with_in_out = $row['cnt'];
    
    printf("✅ Total items: %d\n", $total_items);
    printf("   - Avec IN: %d\n", $with_in);
    printf("   - Avec OUT: %d\n", $with_out);
    printf("   - Avec IN et OUT: %d\n", $with_in_out);
    
    if ($with_in_out == $total_items) {
        echo "✅ Tous les items ont un cycle complet (IN→OUT)\n";
    } else {
        printf("⚠️  %d items sans cycle complet\n", $total_items - $with_in_out);
    }
}

/**
 * CHECK 4: Relations BL-Items
 */
function checkBLItems() {
    global $conn;
    
    echo "\n📋 CHECK 4: Relations BL-Items\n";
    echo str_repeat("-", 80) . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM bl");
    $row = $result->fetch_assoc();
    $total_bl = $row['cnt'];
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM blitem");
    $row = $result->fetch_assoc();
    $total_items = $row['cnt'];
    
    printf("✅ Total BL: %d\n", $total_bl);
    printf("✅ Total Items: %d\n", $total_items);
    printf("   Ratio: %.2f items par BL\n", $total_items / $total_bl);
    
    // Vérifier les items orphelins
    $result = $conn->query("
        SELECT COUNT(*) as cnt FROM blitem 
        WHERE BLId NOT IN (SELECT Id FROM bl)
    ");
    $row = $result->fetch_assoc();
    
    if ($row['cnt'] == 0) {
        echo "✅ Aucun item orphelin\n";
    } else {
        echo "⚠️  " . $row['cnt'] . " items orphelins\n";
    }
}

/**
 * CHECK 5: Factures
 */
function checkInvoices() {
    global $conn;
    
    echo "\n📋 CHECK 5: Factures\n";
    echo str_repeat("-", 80) . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM invoice");
    $row = $result->fetch_assoc();
    $total_invoices = $row['cnt'];
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM bl");
    $row = $result->fetch_assoc();
    $total_bl = $row['cnt'];
    
    printf("✅ Total factures: %d\n", $total_invoices);
    printf("✅ Total BL: %d\n", $total_bl);
    printf("   Ratio: %.2f factures par BL\n", $total_invoices / $total_bl);
    
    // Vérifier les BL sans factures
    $result = $conn->query("
        SELECT COUNT(*) as cnt FROM bl 
        WHERE Id NOT IN (
            SELECT DISTINCT bl.Id FROM bl
            JOIN blitem bi ON bl.Id = bi.BLId
            JOIN invoiceitem ii ON bi.Id = ii.JobFileId
            JOIN invoice i ON ii.InvoiceId = i.Id
        )
    ");
    $row = $result->fetch_assoc();
    
    if ($row['cnt'] == 0) {
        echo "✅ Tous les BL ont des factures\n";
    } else {
        echo "⚠️  " . $row['cnt'] . " BL sans factures\n";
    }
}

/**
 * CHECK 6: Contrôle d'Accès
 */
function checkAccessControl() {
    global $conn;
    
    echo "\n📋 CHECK 6: Contrôle d'Accès\n";
    echo str_repeat("-", 80) . "\n";
    
    $result = $conn->query("
        SELECT COUNT(*) as cnt FROM customerusers_thirdparty
    ");
    $row = $result->fetch_assoc();
    $total_relations = $row['cnt'];
    
    $result = $conn->query("
        SELECT COUNT(DISTINCT CustomerUsers_Id) as cnt FROM customerusers_thirdparty
    ");
    $row = $result->fetch_assoc();
    $total_users = $row['cnt'];
    
    $result = $conn->query("
        SELECT COUNT(DISTINCT ThirdParty_Id) as cnt FROM customerusers_thirdparty
    ");
    $row = $result->fetch_assoc();
    $total_thirdparty = $row['cnt'];
    
    printf("✅ Relations utilisateur-tiers: %d\n", $total_relations);
    printf("   - Utilisateurs: %d\n", $total_users);
    printf("   - Tiers: %d\n", $total_thirdparty);
    printf("   Ratio: %.1f tiers par utilisateur\n", $total_relations / max(1, $total_users));
}

$conn->close();
echo "\n✅ Vérification complète!\n";
echo str_repeat("=", 80) . "\n\n";
?>
