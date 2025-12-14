<?php
// Exécuter les mises à jour EventType depuis Excel

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'd:\\Websites\\IES\\All Assets\\IPAKI SAMPLE DATA.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('EventType');

// Charger les données Excel
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

// Connexion BD
$conn = new mysqli('localhost', 'root', '', 'ies');
if ($conn->connect_error) {
    die("❌ Connexion échouée: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "🔧 MISE À JOUR: Table eventtype\n";
echo str_repeat("=", 80) . "\n\n";

$updated_count = 0;
$errors = [];

foreach ($excel_data as $id => $excel_row) {
    // Préparer les mises à jour
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
            printf("✅ ID %2d: Mise à jour réussie\n", $id);
        } else {
            $errors[] = "ID $id: " . $conn->error;
            printf("❌ ID %2d: ERREUR - %s\n", $id, $conn->error);
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
printf("✅ TOTAL MISES À JOUR: %d / %d\n", $updated_count, count($excel_data));

if (!empty($errors)) {
    echo "\n⚠️ ERREURS:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// Vérification finale
echo "\n" . str_repeat("=", 80) . "\n";
echo "📋 VÉRIFICATION FINALE:\n\n";

$result = $conn->query("SELECT COUNT(*) as cnt FROM eventtype");
$row = $result->fetch_assoc();
printf("Total enregistrements eventtype: %d\n", $row['cnt']);

// Afficher les premières et dernières entrées
echo "\nPremiers types d'événements:\n";
$result = $conn->query("SELECT Id, Code, Label, FamilyId FROM eventtype ORDER BY Id LIMIT 5");
while ($row = $result->fetch_assoc()) {
    printf("  ID %2d: %6s - %s (Family: %d)\n", $row['Id'], $row['Code'], $row['Label'], $row['FamilyId']);
}

echo "\nDerniers types d'événements:\n";
$result = $conn->query("SELECT Id, Code, Label, FamilyId FROM eventtype ORDER BY Id DESC LIMIT 5");
$rows = [];
while ($row = $result->fetch_assoc()) {
    array_unshift($rows, $row);
}
foreach ($rows as $row) {
    printf("  ID %2d: %6s - %s (Family: %d)\n", $row['Id'], $row['Code'], $row['Label'], $row['FamilyId']);
}

$conn->close();
echo "\n✅ SYNCHRONISATION COMPLÈTE!\n";
?>
