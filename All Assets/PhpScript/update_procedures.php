<?php
/**
 * Script pour mettre à jour les procédures stockées
 */

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'ies';
const DB_CHARSET = 'utf8mb4';

// Connexion à la base
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("❌ Erreur de connexion: " . $conn->connect_error);
}
$conn->set_charset(DB_CHARSET);

echo "✅ Connecté à la base de données IES\n\n";

// SQL des procédures
$procedures = [
    'UpdateCustomUserInfo' => "
DROP PROCEDURE IF EXISTS `UpdateCustomUserInfo`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserInfo` (IN `p_UserId` INT, IN `p_FirstName` VARCHAR(2000), IN `p_LastName` VARCHAR(2000), IN `p_PhoneNumber` VARCHAR(100), IN `p_CellPhone` VARCHAR(100), IN `p_CompanyName` VARCHAR(2000), IN `p_CompanyAddress` VARCHAR(2000), IN `p_AccountType` INT)  BEGIN
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
END$$
",
    'UpdateCustomUserStatus' => "
DROP PROCEDURE IF EXISTS `UpdateCustomUserStatus`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserStatus` (IN `p_UserId` INT, IN `p_StatusId` INT)  BEGIN
	UPDATE `customerusers`
	SET `CustomerUsersStatusId` = p_StatusId
	WHERE `Id` = p_UserId;
	
	SELECT ROW_COUNT() AS `AffectedRows`;
END$$
",
    'UpdateCustomUserThirdPartyCodes' => "
DROP PROCEDURE IF EXISTS `UpdateCustomUserThirdPartyCodes`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserThirdPartyCodes` (IN `p_UserId` INT, IN `p_ThirdPartyCodesJson` JSON)  BEGIN
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
END$$
"
];

// Exécuter les procédures
foreach ($procedures as $name => $sql) {
    // Remplacer les $$ par ; pour exécution
    $sqlToExecute = str_replace('$$', ';', $sql);
    
    echo "📝 Mise à jour de: $name\n";
    
    if ($conn->multi_query($sqlToExecute)) {
        // Consommer tous les résultats
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "   ✅ Succès\n\n";
    } else {
        echo "   ❌ Erreur: " . $conn->error . "\n\n";
    }
}

$conn->close();
echo "✅ Mise à jour des procédures terminée!\n";
?>
