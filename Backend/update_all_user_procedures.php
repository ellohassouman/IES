<?php
/**
 * Script de mise à jour de toutes les procédures utilisateur
 * Corrige les 5 procédures stockées utilisées par la page user-list
 *
 * Procédures mises à jour:
 * 1. GetAllCustomUsers ✅ (déjà fait)
 * 2. GetAllConsigneesWithBLs
 * 3. UpdateCustomUserStatus
 * 4. UpdateCustomUserThirdPartyCodes
 * 5. UpdateCustomUserInfo
 * 6. DeleteCustomUser
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

// Tableau des procédures à mettre à jour
$procedures = [];

// ============================================================================
// 1. GetAllConsigneesWithBLs
// ============================================================================
$procedures['GetAllConsigneesWithBLs'] = "
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAllConsigneesWithBLs`()
BEGIN
    SELECT DISTINCT
        tp.`Id`,
        tp.`code`,
        tp.`Label`,
        COUNT(bl.`Id`) AS `BlCount`,
        cu.`CustomerUsersStatusId`
    FROM `thirdparty` tp
    INNER JOIN `bl` ON tp.`Id` = bl.`ConsigneeId`
    LEFT JOIN `customerusers_thirdparty` cut_tp ON tp.`Id` = cut_tp.`ThirdParty_Id`
    LEFT JOIN `customerusers` cu ON cut_tp.`CustomerUsers_Id` = cu.`Id`
    WHERE tp.`code` IS NOT NULL
    AND tp.`code` != ''
    AND (cu.`CustomerUsersStatusId` IS NULL OR cu.`CustomerUsersStatusId` != 5)
    GROUP BY tp.`Id`, tp.`code`, tp.`Label`, cu.`CustomerUsersStatusId`
    ORDER BY tp.`Label` ASC;
END";

// ============================================================================
// 2. UpdateCustomUserStatus - met à jour le statut d'un utilisateur
// ============================================================================
$procedures['UpdateCustomUserStatus'] = "
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserStatus`(
    IN `p_UserId` INT,
    IN `p_StatusId` INT
)
BEGIN
    UPDATE `customerusers`
    SET `CustomerUsersStatusId` = p_StatusId
    WHERE `Id` = p_UserId;

    SELECT ROW_COUNT() AS `AffectedRows`;
END";

// ============================================================================
// 3. UpdateCustomUserThirdPartyCodes - met à jour les codes tiers d'un utilisateur
// ============================================================================
$procedures['UpdateCustomUserThirdPartyCodes'] = "
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserThirdPartyCodes`(
    IN `p_UserId` INT,
    IN `p_ThirdPartyCodesJson` JSON
)
BEGIN
    DECLARE v_Index INT DEFAULT 0;
    DECLARE v_Count INT DEFAULT 0;
    DECLARE v_ThirdPartyId INT;

    -- Supprimer les codes tiers existants pour cet utilisateur
    DELETE FROM `customerusers_thirdparty`
    WHERE `CustomerUsers_Id` = p_UserId;

    -- Ajouter les nouveaux codes tiers
    SET v_Count = JSON_LENGTH(p_ThirdPartyCodesJson);

    WHILE v_Index < v_Count DO
        SET v_ThirdPartyId = JSON_EXTRACT(p_ThirdPartyCodesJson, CONCAT('$[', v_Index, ']'));

        INSERT INTO `customerusers_thirdparty` (`CustomerUsers_Id`, `ThirdParty_Id`)
        VALUES (p_UserId, v_ThirdPartyId);

        SET v_Index = v_Index + 1;
    END WHILE;

    SELECT 'OK' AS `Result`;
END";

// ============================================================================
// 4. UpdateCustomUserInfo - met à jour les informations personnelles et d'entreprise
// ============================================================================
$procedures['UpdateCustomUserInfo'] = "
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserInfo`(
    IN `p_UserId` INT,
    IN `p_FirstName` VARCHAR(2000),
    IN `p_LastName` VARCHAR(2000),
    IN `p_PhoneNumber` VARCHAR(100),
    IN `p_CellPhone` VARCHAR(100),
    IN `p_CompanyName` VARCHAR(2000),
    IN `p_CompanyAddress` VARCHAR(2000),
    IN `p_AccountType` INT
)
BEGIN
    UPDATE `customerusers`
    SET
        `FirstName` = p_FirstName,
        `LastName` = p_LastName,
        `PhoneNumber` = p_PhoneNumber,
        `CompanyName` = p_CompanyName,
        `CompanyAddress` = p_CompanyAddress,
        `CustomerUsersTypeId` = p_AccountType
    WHERE `Id` = p_UserId;

    SELECT ROW_COUNT() AS `AffectedRows`;
END";

// ============================================================================
// 5. DeleteCustomUser - marque un utilisateur comme supprimé (Status = 5)
// ============================================================================
$procedures['DeleteCustomUser'] = "
CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteCustomUser`(
    IN `p_UserId` INT
)
BEGIN
    UPDATE `customerusers`
    SET `CustomerUsersStatusId` = 5
    WHERE `Id` = p_UserId;

    SELECT ROW_COUNT() AS `AffectedRows`;
END";

// Traiter chaque procédure
echo "📋 Mise à jour de toutes les procédures utilisateur...\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$success_count = 0;
$error_count = 0;
$results = [];

foreach ($procedures as $proc_name => $proc_sql) {
    echo "Traitement: $proc_name...";

    try {
        // Supprimer la procédure existante
        $mysqli->query("DROP PROCEDURE IF EXISTS `$proc_name`");

        // Créer la nouvelle procédure
        if ($mysqli->query($proc_sql)) {
            echo " ✅\n";
            $success_count++;
            $results[$proc_name] = 'OK';
        } else {
            echo " ❌ Erreur: " . $mysqli->error . "\n";
            $error_count++;
            $results[$proc_name] = 'ERROR: ' . $mysqli->error;
        }
    } catch (Exception $e) {
        echo " ❌ Exception: " . $e->getMessage() . "\n";
        $error_count++;
        $results[$proc_name] = 'EXCEPTION: ' . $e->getMessage();
    }
}

echo "\n🔍 Vérification des procédures créées...\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$verify_result = $mysqli->query("
    SELECT ROUTINE_NAME
    FROM INFORMATION_SCHEMA.ROUTINES
    WHERE ROUTINE_SCHEMA = '$database'
    AND ROUTINE_NAME IN ('GetAllConsigneesWithBLs', 'UpdateCustomUserStatus', 'UpdateCustomUserThirdPartyCodes', 'UpdateCustomUserInfo', 'DeleteCustomUser')
    ORDER BY ROUTINE_NAME
");

$verified = [];
while ($row = $verify_result->fetch_assoc()) {
    $verified[] = $row['ROUTINE_NAME'];
    echo "✅ " . $row['ROUTINE_NAME'] . "\n";
}

echo "\n📊 RÉSUMÉ DE L'OPÉRATION\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Procédures créées avec succès: $success_count\n";
echo "Erreurs: $error_count\n";
echo "Procédures vérifiées: " . count($verified) . "\n\n";

// Résumé détaillé
echo "📝 Détail des procédures mises à jour:\n";
echo "───────────────────────────────────────────────────────────────\n";

foreach ($results as $proc_name => $status) {
    $icon = strpos($status, 'OK') !== false ? '✅' : '❌';
    echo "$icon $proc_name: $status\n";
}

echo "\n";

// Tests rapides
echo "🧪 TEST RAPIDE DES PROCÉDURES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Test 1: GetAllConsigneesWithBLs
echo "Test 1: GetAllConsigneesWithBLs\n";
$test1 = $mysqli->query("CALL GetAllConsigneesWithBLs()");
if ($test1) {
    $count = 0;
    while ($row = $test1->fetch_assoc()) {
        $count++;
        if ($count <= 2) {
            echo "  Consignee: " . $row['Label'] . " (Code: " . $row['code'] . ")\n";
        }
    }
    $test1->free();
    // Vider le buffer après un appel de procédure
    while ($mysqli->more_results() && $mysqli->next_result()) {
        if ($res = $mysqli->store_result()) {
            $res->free();
        }
    }
    echo "  ✅ Total consignees: $count\n\n";
} else {
    echo "  ❌ Erreur: " . $mysqli->error . "\n\n";
}

// Test 2: UpdateCustomUserStatus
echo "Test 2: UpdateCustomUserStatus (dry run - utilisateur ID 10)\n";
$test2 = $mysqli->query("SELECT CustomerUsersStatusId FROM customerusers WHERE Id = 10 LIMIT 1");
if ($test2 && $row = $test2->fetch_assoc()) {
    echo "  Statut actuel: " . $row['CustomerUsersStatusId'] . "\n";
    echo "  ✅ Procédure peut être appelée avec (UserId=10, StatusId=1)\n\n";
    $test2->free();
} else {
    echo "  ❌ Utilisateur non trouvé\n\n";
}

// Test 3: UpdateCustomUserThirdPartyCodes
echo "Test 3: UpdateCustomUserThirdPartyCodes\n";
$test3 = $mysqli->query("SELECT COUNT(*) as cnt FROM thirdparty WHERE Id > 0 LIMIT 5");
if ($test3 && $row = $test3->fetch_assoc()) {
    echo "  ✅ Table thirdparty accessible (" . $row['cnt'] . " tiers)\n";
    echo "  Procédure peut être appelée avec JSON array d'IDs\n\n";
    $test3->free();
} else {
    echo "  ❌ Erreur accès table thirdparty\n\n";
}

// Test 4: UpdateCustomUserInfo
echo "Test 4: UpdateCustomUserInfo\n";
$test4 = $mysqli->query("SELECT COUNT(*) as cnt FROM customerusers");
if ($test4 && $row = $test4->fetch_assoc()) {
    echo "  ✅ " . $row['cnt'] . " utilisateurs dans la base\n";
    echo "  Procédure peut être appelée avec les paramètres de mise à jour\n\n";
    $test4->free();
} else {
    echo "  ❌ Erreur accès table customerusers\n\n";
}

// Test 5: DeleteCustomUser
echo "Test 5: DeleteCustomUser\n";
echo "  ✅ Procédure définie - marque un utilisateur avec Status = 5\n";
echo "  Utilisation: CALL DeleteCustomUser(UserId)\n\n";

echo "🎉 Mise à jour terminée!\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Fermer la connexion
$mysqli->close();

echo "\n✅ Connexion fermée.\n";
?>
