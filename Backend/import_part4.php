<?php
/**
 * Import PART 4: BL, BLItem, JobFile, Event, Invoice, InvoiceItem
 * Exécute le fichier SQL généré avec validation du cycle JobFile
 */

// Connexion à la DB
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ies';

$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    die("Erreur de connexion: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

$sql_file = __DIR__ . '/../All Assets/generate_sample_data_PART4.sql';

if (!file_exists($sql_file)) {
    die("Fichier SQL non trouvé: $sql_file\n");
}

echo "📂 Chargement du fichier SQL: $sql_file\n";

$sql_content = file_get_contents($sql_file);

// Exécuter multi_query
echo "⏳ Exécution du fichier SQL...\n";
if ($mysqli->multi_query($sql_content)) {
    echo "✅ Fichier SQL exécuté\n";

    // Consommer les résultats
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->error) {
        echo "⚠️ Erreur dans le multi_query: " . $mysqli->error . "\n";
    }
} else {
    echo "❌ Erreur: " . $mysqli->error . "\n";
}

// Statistiques
echo "\n📊 STATISTIQUES DE L'IMPORT:\n";
echo "==============================\n";

$result = $mysqli->query("SELECT COUNT(*) as cnt FROM bl");
$row = $result->fetch_assoc();
echo "BL: " . $row['cnt'] . "\n";

$result = $mysqli->query("SELECT COUNT(*) as cnt FROM blitem");
$row = $result->fetch_assoc();
echo "BLItem: " . $row['cnt'] . "\n";

$result = $mysqli->query("SELECT COUNT(*) as cnt FROM jobfile");
$row = $result->fetch_assoc();
echo "JobFile: " . $row['cnt'] . "\n";

$result = $mysqli->query("SELECT COUNT(*) as cnt FROM event");
$row = $result->fetch_assoc();
echo "Event: " . $row['cnt'] . "\n";

$result = $mysqli->query("SELECT COUNT(*) as cnt FROM blitem_jobfile");
$row = $result->fetch_assoc();
echo "BLItem_JobFile: " . $row['cnt'] . "\n";

$result = $mysqli->query("SELECT COUNT(*) as cnt FROM invoice");
$row = $result->fetch_assoc();
echo "Invoice: " . $row['cnt'] . "\n";

$result = $mysqli->query("SELECT COUNT(*) as cnt FROM invoiceitem");
$row = $result->fetch_assoc();
echo "InvoiceItem: " . $row['cnt'] . "\n";

// Validation du cycle JobFile
echo "\n🔍 VALIDATION DES CYCLES JOBFILE:\n";
echo "==================================\n";

$result = $mysqli->query("
    SELECT jf.Id, jf.DateOpen, jf.DateClose,
           (SELECT EventTypeId FROM event WHERE JobFileId = jf.Id ORDER BY EventDate ASC LIMIT 1) as first_event,
           (SELECT EventTypeId FROM event WHERE JobFileId = jf.Id ORDER BY EventDate DESC LIMIT 1) as last_event,
           (SELECT COUNT(*) FROM event WHERE JobFileId = jf.Id) as event_count
    FROM jobfile jf
    ORDER BY jf.Id
");

$valid_count = 0;
$invalid_count = 0;

// Récupérer les event types
$et_result = $mysqli->query("SELECT Id, FamilyId FROM eventtype");
$event_types = [];
while ($et = $et_result->fetch_assoc()) {
    $event_types[$et['Id']] = $et['FamilyId'];
}

while ($row = $result->fetch_assoc()) {
    $first_et = $event_types[$row['first_event']] ?? null;
    $last_et = $event_types[$row['last_event']] ?? null;

    // Vérifier: premier = famille 7 (In)
    $first_ok = ($first_et == 7) ? "✓" : "✗";

    // Vérifier: dernier = famille 2 (Out) si DateClose SET
    if ($row['DateClose'] !== null) {
        $last_ok = ($last_et == 2) ? "✓" : "✗";
    } else {
        $last_ok = ($last_et != 2) ? "✓" : "✗";  // Ne doit pas avoir OUT
    }

    $status = ($first_ok == "✓" && $last_ok == "✓") ? "✅ VALID" : "❌ INVALID";

    echo "JobFile #" . $row['Id'] . ": $status | Events: " . $row['event_count'] . " | " .
         "First: Family $first_et $first_ok | Last: Family $last_et $last_ok | " .
         "Open: " . substr($row['DateOpen'], 0, 10) . " | Close: " . ($row['DateClose'] ? substr($row['DateClose'], 0, 10) : "NULL") . "\n";

    if ($first_ok == "✓" && $last_ok == "✓") {
        $valid_count++;
    } else {
        $invalid_count++;
    }
}

echo "\n✅ JobFiles valides: $valid_count\n";
echo "❌ JobFiles invalides: $invalid_count\n";

echo "\n✨ Import PART 4 terminé!\n";

$mysqli->close();
?>
