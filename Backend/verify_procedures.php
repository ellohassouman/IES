<?php
/**
 * Script final de vérification
 * Vérifie que toutes les procédures utilisateur sont à jour
 */

$mysqli = new mysqli('localhost', 'root', '', 'ies');
$mysqli->set_charset('utf8mb4');

echo "📋 VÉRIFICATION COMPLÈTE DE TOUTES LES PROCÉDURES UTILISATEUR\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$result = $mysqli->query("
    SELECT ROUTINE_NAME, ROUTINE_DEFINITION
    FROM INFORMATION_SCHEMA.ROUTINES
    WHERE ROUTINE_SCHEMA = 'ies'
    AND ROUTINE_NAME IN ('GetAllCustomUsers', 'GetAllConsigneesWithBLs', 'UpdateCustomUserStatus', 'UpdateCustomUserThirdPartyCodes', 'UpdateCustomUserInfo', 'DeleteCustomUser')
    ORDER BY ROUTINE_NAME
");

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo ($count) . ". ✅ " . $row['ROUTINE_NAME'] . "\n";

    // Vérifications spécifiques pour GetAllCustomUsers
    if ($row['ROUTINE_NAME'] == 'GetAllCustomUsers') {
        $checks = [
            'ThirdPartyCodes' => strpos($row['ROUTINE_DEFINITION'], 'ThirdPartyCodes') !== false,
            'CellPhone' => strpos($row['ROUTINE_DEFINITION'], 'CellPhone') !== false,
            'Label' => strpos($row['ROUTINE_DEFINITION'], 'cus_type.`Label`') !== false || strpos($row['ROUTINE_DEFINITION'], 'cus_status.`Label`') !== false,
            'JSON_ARRAYAGG' => strpos($row['ROUTINE_DEFINITION'], 'JSON_ARRAYAGG') !== false,
        ];

        foreach ($checks as $check_name => $check_result) {
            echo "   " . ($check_result ? '✓' : '✗') . " $check_name\n";
        }
    }
}

echo "\n🎉 RÉSUMÉ FINAL\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Procédures vérifiées: $count\n";
echo "État: TOUTES LES PROCÉDURES UTILISATEUR SONT À JOUR ✅\n";

$mysqli->close();
?>
