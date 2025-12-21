<?php
/**
 * Script de mise à jour de la procédure GetAllCustomUsers
 * Corrige la procédure stockée pour correspondre au modèle frontend CustomerUser
 * et à la structure réelle de la base de données IES
 *
 * Modifications apportées:
 * - Colonnes Label au lieu de Name pour AccountType et Status
 * - Retourne les codes tiers (ThirdPartyCodes) au lieu des IDs
 * - Ajoute le champ CellPhone (NULL par défaut)
 */

// Configuration de la connexion
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ies';

// Créer la connexion
$mysqli = new mysqli($host, $user, $password, $database);

// Vérifier la connexion
if ($mysqli->connect_error) {
    die("❌ Erreur de connexion: " . $mysqli->connect_error . "\n");
}

echo "✅ Connecté à la base de données: $database\n\n";

// Définir le charset
$mysqli->set_charset("utf8mb4");

echo "📋 Mise à jour de la procédure GetAllCustomUsers...\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // Supprimer la procédure existante
    $mysqli->query("DROP PROCEDURE IF EXISTS `GetAllCustomUsers`");
    echo "✅ Ancienne procédure supprimée\n";

    // Créer la nouvelle procédure
    $procedure_sql = "CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAllCustomUsers`()
    BEGIN
        SELECT
            cu.`Id`,
            cu.`UserName`,
            cu.`FirstName`,
            cu.`LastName`,
            (SELECT `code` FROM `thirdparty` WHERE `Id` = (
                SELECT `ThirdParty_Id` FROM `customerusers_thirdparty`
                WHERE `CustomerUsers_Id` = cu.`Id` LIMIT 1
            )) AS `Site`,
            cu.`CompanyName`,
            cu.`CompanyAddress`,
            cu.`PhoneNumber`,
            NULL AS `CellPhone`,
            cus_type.`Label` AS `AccountType`,
            cus_status.`Label` AS `Status`,
            cu.`CustomerUsersStatusId`,
            cu.`CustomerUsersTypeId`,
            JSON_ARRAYAGG(tp.`code`) AS `ThirdPartyCodes`
        FROM `customerusers` cu
        LEFT JOIN `customeruserstype` cus_type ON cu.`CustomerUsersTypeId` = cus_type.`Id`
        LEFT JOIN `customerusersstatus` cus_status ON cu.`CustomerUsersStatusId` = cus_status.`Id`
        LEFT JOIN `customerusers_thirdparty` cut_tp ON cu.`Id` = cut_tp.`CustomerUsers_Id`
        LEFT JOIN `thirdparty` tp ON cut_tp.`ThirdParty_Id` = tp.`Id`
        WHERE cu.`UserName` IS NOT NULL
        AND cu.`UserName` != ''
        AND cu.`CustomerUsersStatusId` != 5
        GROUP BY cu.`Id`, cu.`UserName`, cu.`FirstName`, cu.`LastName`, cu.`CompanyName`, cu.`CompanyAddress`, cu.`PhoneNumber`, cus_type.`Label`, cus_status.`Label`, cu.`CustomerUsersStatusId`, cu.`CustomerUsersTypeId`
        ORDER BY cu.`UserName` ASC;
    END";

    if ($mysqli->query($procedure_sql)) {
        echo "✅ Procédure GetAllCustomUsers créée avec succès!\n\n";
    } else {
        echo "❌ Erreur lors de la création de la procédure:\n";
        echo $mysqli->error . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// Vérifier la procédure
echo "🔍 Vérification de la procédure...\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$result = $mysqli->query("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_NAME = 'GetAllCustomUsers' AND ROUTINE_SCHEMA = '$database'");

if ($result && $row = $result->fetch_assoc()) {
    echo "✅ Procédure trouvée:\n";
    echo "   Nom: " . $row['ROUTINE_NAME'] . "\n";
    echo "   Type: " . $row['ROUTINE_TYPE'] . "\n";
    $result->free();
} else {
    echo "❌ Procédure non trouvée!\n";
    exit(1);
}

// Tester la procédure
echo "\n📊 Test d'exécution de la procédure...\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$test_result = $mysqli->query("CALL GetAllCustomUsers()");

if ($test_result) {
    $row_count = 0;
    while ($row = $test_result->fetch_assoc()) {
        $row_count++;
        if ($row_count <= 3) {
            echo "Utilisateur #$row_count:\n";
            echo "  Id: " . $row['Id'] . "\n";
            echo "  UserName: " . $row['UserName'] . "\n";
            echo "  FirstName: " . $row['FirstName'] . "\n";
            echo "  LastName: " . $row['LastName'] . "\n";
            echo "  Site: " . ($row['Site'] ?? 'N/A') . "\n";
            echo "  AccountType: " . $row['AccountType'] . "\n";
            echo "  Status: " . $row['Status'] . "\n";
            echo "  ThirdPartyCodes: " . $row['ThirdPartyCodes'] . "\n";
            echo "\n";
        }
    }
    $test_result->free();

    echo "✅ Procédure exécutée avec succès!\n";
    echo "   Nombre total d'utilisateurs: $row_count\n";
} else {
    echo "❌ Erreur lors de l'exécution de la procédure:\n";
    echo $mysqli->error . "\n";
    exit(1);
}

echo "\n🎉 Mise à jour terminée avec succès!\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Résumé des modifications
echo "\n📝 Résumé des modifications:\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "✓ Colonnes renommées: Name → Label\n";
echo "✓ ThirdPartyIds → ThirdPartyCodes (codes au lieu d'IDs)\n";
echo "✓ Ajout du champ CellPhone (NULL)\n";
echo "✓ Jointures corrigées avec les bonnes tables\n";
echo "✓ Filtre des utilisateurs supprimés (Status ID ≠ 5)\n";
echo "───────────────────────────────────────────────────────────────\n";

// Fermer la connexion
$mysqli->close();

echo "\n✅ Connexion fermée.\n";
?>
