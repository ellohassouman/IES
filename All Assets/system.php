<?php
/**
 * ╔════════════════════════════════════════════════════════════════════════════╗
 * ║              SYSTÈME DE MAINTENANCE COMPLET - IES                          ║
 * ║                                                                            ║
 * ║  Script maître consolidant :                                              ║
 * ║  • Configuration centralisée                                              ║
 * ║  • Création/recréation des procédures stockées                            ║
 * ║  • Maintenance et vérification intégrité                                  ║
 * ║  • Nettoyage et analyse des données                                       ║
 * ╚════════════════════════════════════════════════════════════════════════════╝
 * 
 * USAGE:
 *   php system.php [command] [options]
 * 
 * COMMANDES:
 *   config              : Affiche la configuration actuelle
 *   procedures          : Créer/recréer les procédures stockées
 *   maintenance         : Maintenance complète (fix-structure, verify, etc)
 *   help                : Affiche cette aide
 * 
 * EXEMPLES:
 *   php system.php procedures
 *   php system.php maintenance verify-integrity
 *   php system.php maintenance fix-structure
 */

// ============================================================================
// CONFIGURATION CENTRALISÉE
// ============================================================================

$DB_CONFIG = [
    'host'     => '127.0.0.1',
    'user'     => 'root',
    'password' => '',
    'database' => 'ies',
    'charset'  => 'utf8mb4'
];

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

function connectToDatabase($config = null) {
    global $DB_CONFIG;
    $cfg = $config ?? $DB_CONFIG;
    
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database']);
    
    if ($conn->connect_error) {
        die("❌ Erreur de connexion: " . $conn->connect_error);
    }
    
    $conn->set_charset($cfg['charset']);
    return $conn;
}

function showSuccess($message) {
    echo "✅ " . $message . "\n";
}

function showError($message) {
    echo "❌ " . $message . "\n";
}

function showInfo($message) {
    echo "ℹ️  " . $message . "\n";
}

function showWarning($message) {
    echo "⚠️  " . $message . "\n";
}

function showTitle($title) {
    $line = str_repeat("━", 80);
    echo "\n╔" . str_repeat("═", 78) . "╗\n";
    echo "║ " . str_pad($title, 76) . " ║\n";
    echo "╚" . str_repeat("═", 78) . "╝\n\n";
}

// ============================================================================
// CLASSE: GESTION DES PROCÉDURES STOCKÉES
// ============================================================================

class ProcedureManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function createAll() {
        showTitle("CRÉATION DES PROCÉDURES STOCKÉES");
        
        $sql = <<<'SQL'
DROP PROCEDURE IF EXISTS `GetAllCustomUsers`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAllCustomUsers`()
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
END;

DROP PROCEDURE IF EXISTS `GetAllConsigneesWithBLs`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAllConsigneesWithBLs`()
BEGIN
    SELECT DISTINCT
        tp.Id,
        tp.Name,
        bl.reference,
        bl.total_ttc,
        bl.total_ht
    FROM thirdparty tp
    LEFT JOIN bls bl ON tp.Id = bl.customerid
    WHERE tp.Type = 'consignee'
    AND tp.Active = 1
    AND NOT EXISTS (
        SELECT 1 FROM customerusers_thirdparty cut
        WHERE cut.ThirdPartyId = tp.Id
        AND cut.StatusId = 5
    )
    ORDER BY tp.Name;
END;

DROP PROCEDURE IF EXISTS `UpdateCustomUserStatus`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserStatus`(
    IN p_CustomerId INT,
    IN p_StatusId INT
)
BEGIN
    UPDATE customerusers_thirdparty
    SET StatusId = p_StatusId,
        UpdatedAt = NOW()
    WHERE CustomerId = p_CustomerId;
END;

DROP PROCEDURE IF EXISTS `UpdateCustomUserInfo`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserInfo`(
    IN p_CustomerId INT,
    IN p_Email VARCHAR(255),
    IN p_Label VARCHAR(255),
    IN p_Phone VARCHAR(20),
    IN p_Fax VARCHAR(20),
    IN p_Mobile VARCHAR(20),
    IN p_Poste VARCHAR(255),
    IN p_Note LONGTEXT
)
BEGIN
    UPDATE customerusers
    SET Email = p_Email,
        Label = p_Label,
        phone = p_Phone,
        fax = p_Fax,
        mobile = p_Mobile,
        poste = p_Poste,
        note = p_Note
    WHERE Id = p_CustomerId;
END;

DROP PROCEDURE IF EXISTS `UpdateCustomUserThirdPartyCodes`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomUserThirdPartyCodes`(
    IN p_CustomerId INT,
    IN p_ThirdPartyIds VARCHAR(1000),
    IN p_StatusId INT
)
BEGIN
    DELETE FROM customerusers_thirdparty
    WHERE CustomerId = p_CustomerId;
    
    IF p_ThirdPartyIds != '' THEN
        SET @sql = CONCAT(
            'INSERT INTO customerusers_thirdparty (CustomerId, ThirdPartyId, StatusId) ',
            'VALUES '
        );
        
        SET @first = 1;
        SET @pos = 1;
        SET @id = '';
        
        WHILE @pos <= CHAR_LENGTH(p_ThirdPartyIds) DO
            SET @char = SUBSTRING(p_ThirdPartyIds, @pos, 1);
            
            IF @char = ',' OR @pos = CHAR_LENGTH(p_ThirdPartyIds) THEN
                IF @pos = CHAR_LENGTH(p_ThirdPartyIds) AND @char != ',' THEN
                    SET @id = CONCAT(@id, @char);
                END IF;
                
                IF @id != '' THEN
                    IF @first = 0 THEN
                        SET @sql = CONCAT(@sql, ', ');
                    END IF;
                    SET @sql = CONCAT(@sql, '(', p_CustomerId, ', ', @id, ', ', p_StatusId, ')');
                    SET @first = 0;
                    SET @id = '';
                END IF;
            ELSE
                SET @id = CONCAT(@id, @char);
            END IF;
            
            SET @pos = @pos + 1;
        END WHILE;
        
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END;
SQL;

        if ($this->conn->multi_query($sql)) {
            do {
                if ($this->conn->store_result()) {
                    $this->conn->free_result();
                }
            } while ($this->conn->more_results() && $this->conn->next_result());
            
            showSuccess("Toutes les procédures créées!");
            echo "   • GetAllCustomUsers\n";
            echo "   • GetAllConsigneesWithBLs\n";
            echo "   • UpdateCustomUserStatus\n";
            echo "   • UpdateCustomUserInfo\n";
            echo "   • UpdateCustomUserThirdPartyCodes\n";
        } else {
            showError("Erreur: " . $this->conn->error);
            return false;
        }
        
        return true;
    }
}

// ============================================================================
// CLASSE: MAINTENANCE
// ============================================================================

class DatabaseMaintenance {
    private $conn;
    private $log = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    private function log_action($level, $message) {
        $prefix = match($level) {
            'success' => '✅',
            'error'   => '❌',
            'warning' => '⚠️',
            'info'    => 'ℹ️',
            default   => '•'
        };
        echo "$prefix $message\n";
        $this->log[] = ["$prefix", $message];
    }
    
    public function verifyIntegrity() {
        showTitle("VÉRIFICATION INTÉGRITÉ BASE DE DONNÉES");
        
        $this->log_action('info', 'Démarrage des vérifications...');
        
        // Vérification 1: Clés primaires
        $result = $this->conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='ies'");
        $row = $result->fetch_assoc();
        $this->log_action('success', "Total tables: " . $row['count']);
        
        // Vérification 2: IDs à 0 invalides
        $tables_check = ['EVENT', 'EVENTTYPE', 'PAYMENTTYPE', 'COMMODITYITEM'];
        foreach ($tables_check as $table) {
            $result = $this->conn->query("SELECT COUNT(*) as count FROM `" . $table . "` WHERE Id = 0");
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row['count'] > 0) {
                    $this->log_action('warning', "$table: {$row['count']} entrée(s) avec Id=0");
                } else {
                    $this->log_action('success', "$table: Aucun enregistrement invalide");
                }
            }
        }
        
        $this->log_action('success', 'Vérification complétée');
    }
    
    public function fixStructure() {
        showTitle("CORRECTION STRUCTURE BASE DE DONNÉES");
        
        $this->log_action('info', 'Vérification et correction de la structure...');
        
        // Désactiver les contraintes
        $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
        
        // Corrections essentielles
        $corrections = [
            "ALTER TABLE `EVENT` MODIFY COLUMN `Id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
            "ALTER TABLE `EVENTTYPE` MODIFY COLUMN `Id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
            "ALTER TABLE `PAYMENTTYPE` MODIFY COLUMN `Id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        ];
        
        $success = 0;
        foreach ($corrections as $sql) {
            if ($this->conn->query($sql)) {
                $success++;
                $this->log_action('success', 'Table mise à jour');
            }
        }
        
        // Réactiver les contraintes
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        $this->log_action('success', "$success tables corrigées");
    }
    
    public function analyze() {
        showTitle("ANALYSE COMPLÈTE BASE DE DONNÉES");
        
        $this->log_action('info', 'Analyse de la structure...');
        
        // Compter les tables
        $result = $this->conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='ies'");
        $row = $result->fetch_assoc();
        $this->log_action('info', "Tables: {$row['count']}");
        
        // Compter les colonnes
        $result = $this->conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='ies'");
        $row = $result->fetch_assoc();
        $this->log_action('info', "Colonnes: {$row['count']}");
        
        // Compter les clés étrangères
        $result = $this->conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='ies' AND REFERENCED_TABLE_NAME IS NOT NULL");
        $row = $result->fetch_assoc();
        $this->log_action('info', "Clés étrangères: {$row['count']}");
        
        $this->log_action('success', 'Analyse complétée');
    }
}

// ============================================================================
// SYSTÈME DE COMMANDES
// ============================================================================

$command = $argv[1] ?? 'help';

try {
    switch ($command) {
        case 'config':
            showTitle("CONFIGURATION ACTUELLE");
            echo "Host: " . $DB_CONFIG['host'] . "\n";
            echo "User: " . $DB_CONFIG['user'] . "\n";
            echo "Database: " . $DB_CONFIG['database'] . "\n";
            echo "Charset: " . $DB_CONFIG['charset'] . "\n";
            break;
        
        case 'procedures':
            $conn = connectToDatabase();
            $manager = new ProcedureManager($conn);
            $manager->createAll();
            $conn->close();
            break;
        
        case 'maintenance':
            $conn = connectToDatabase();
            $maintenance = new DatabaseMaintenance($conn);
            
            $subcommand = $argv[2] ?? 'verify-integrity';
            
            switch ($subcommand) {
                case 'verify-integrity':
                    $maintenance->verifyIntegrity();
                    break;
                case 'fix-structure':
                    $maintenance->fixStructure();
                    break;
                case 'analyze':
                    $maintenance->analyze();
                    break;
                default:
                    showError("Commande inconnue: $subcommand");
            }
            
            $conn->close();
            break;
        
        case 'help':
        default:
            showTitle("AIDE - SYSTÈME DE MAINTENANCE IES");
            echo "UTILISATION:\n";
            echo "  php system.php [command] [options]\n\n";
            echo "COMMANDES DISPONIBLES:\n";
            echo "  config              - Afficher la configuration\n";
            echo "  procedures          - Créer toutes les procédures stockées\n";
            echo "  maintenance         - Maintenance BD (sous-commandes):\n";
            echo "    verify-integrity  - Vérifier l'intégrité\n";
            echo "    fix-structure     - Corriger la structure\n";
            echo "    analyze           - Analyser la base de données\n";
            echo "  help                - Afficher cette aide\n\n";
            echo "EXEMPLES:\n";
            echo "  php system.php procedures\n";
            echo "  php system.php maintenance verify-integrity\n";
            echo "  php system.php maintenance fix-structure\n";
            break;
    }
} catch (Exception $e) {
    showError("Erreur: " . $e->getMessage());
}
