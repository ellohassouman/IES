<?php
/**
 * ╔════════════════════════════════════════════════════════════════════════════╗
 * ║         SYSTÈME UNIFIÉ DE MAINTENANCE ET DIAGNOSTICS - IES                ║
 * ║                                                                            ║
 * ║  Script maître consolidant:                                               ║
 * ║  • Configuration centralisée (DB config)                                  ║
 * ║  • Gestion procédures stockées (création/recréation)                      ║
 * ║  • Gestion clés étrangères (création/vérification)                        ║
 * ║  • Outils de diagnostic et analyse (relations, tables, structures)        ║
 * ║  • Scripts de correction et vérification                                  ║
 * ║  • Interface CLI complète                                                 ║
 * ╚════════════════════════════════════════════════════════════════════════════╝
 * 
 * USAGE:
 *   Mode CLI: php UNIFIED_SYSTEM.php [command] [options]
 *   Mode Menu: php UNIFIED_SYSTEM.php (mode interactif)
 * 
 * COMMANDES:
 *   config                  : Affiche la configuration actuelle
 *   relationships           : Créer les clés étrangères manquantes
 *   verify-relationships    : Vérifier et rapporter les relations établies
 *   validate-relationships  : Tester que les contraintes fonctionnent
 *   procedures              : Créer/recréer les procédures stockées
 *   user-procedures         : Gestion procédures utilisateur
 *   maintenance             : Maintenance complète de la BD
 *   analyze                 : Analyse et diagnostics complets
 *   menu                    : Mode interactif avec menu
 *   help                    : Affiche cette aide
 */

// ============================================================================
// CONFIGURATION CENTRALISÉE
// ============================================================================

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'ies';
const DB_CHARSET = 'utf8mb4';

// ============================================================================
// CLASSE: DATABASE CONNECTION (SINGLETON)
// ============================================================================

class DatabaseConnection {
    private static $instance = null;
    private $conn = null;
    
    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("❌ Erreur de connexion: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset(DB_CHARSET);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->conn;
    }
}

// ============================================================================
// CLASSE: OUTPUT DISPLAY (UTILITIES)
// ============================================================================

class Display {
    public static function title($title) {
        echo "\n╔" . str_repeat("═", 78) . "╗\n";
        echo "║ " . str_pad($title, 76) . " ║\n";
        echo "╚" . str_repeat("═", 78) . "╝\n\n";
    }
    
    public static function section($title) {
        echo "\n" . $title . "\n";
        echo str_repeat("─", 75) . "\n";
    }
    
    public static function success($message) {
        echo "✅ " . $message . "\n";
    }
    
    public static function error($message) {
        echo "❌ " . $message . "\n";
    }
    
    public static function info($message) {
        echo "ℹ️  " . $message . "\n";
    }
    
    public static function warning($message) {
        echo "⚠️  " . $message . "\n";
    }
    
    public static function table($headers, $rows) {
        if (empty($rows)) {
            Display::info("Aucune donnée à afficher");
            return;
        }
        
        $colWidths = array_fill(0, count($headers), 0);
        
        foreach ($headers as $i => $header) {
            $colWidths[$i] = strlen($header);
        }
        
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $colWidths[$i] = max($colWidths[$i], strlen((string)$cell));
            }
        }
        
        echo "\n";
        foreach ($headers as $i => $header) {
            echo str_pad($header, $colWidths[$i] + 2);
        }
        echo "\n";
        
        foreach ($colWidths as $width) {
            echo str_repeat("─", $width + 2);
        }
        echo "\n";
        
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                echo str_pad((string)$cell, $colWidths[$i] + 2);
            }
            echo "\n";
        }
        echo "\n";
    }
}

// ============================================================================
// CLASSE: GESTION DES RELATIONS (CLÉS ÉTRANGÈRES)
// ============================================================================

class RelationshipManager {
    private $conn;
    private $createdCount = 0;
    private $skippedCount = 0;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function createAll() {
        Display::title("CRÉATION DES CLÉS ÉTRANGÈRES");
        
        $mappings = [
            'area' => ['TerminalId' => 'terminal'],
            'bl' => ['ConsigneeId' => 'thirdparty', 'RelatedCustomerId' => 'thirdparty', 'CallId' => 'call'],
            'blitem' => ['BlId' => 'bl', 'ItemTypeId' => 'yarditemtype', 'ItemCodeId' => 'yarditemcode'],
            'blitem_jobfile' => ['BLItem_Id' => 'blitem', 'JobFile_Id' => 'jobfile'],
            'call' => ['ThirdPartyId' => 'thirdparty'],
            'cart' => ['CustomerUserId' => 'customerusers'],
            'contract' => ['TaxCodeId' => 'taxcodes'],
            'contract_eventtype' => ['Contract_Id' => 'contract', 'EventType_Id' => 'eventtype'],
            'customerusers' => ['CustomerUsersStatusId' => 'customerusersstatus', 'CustomerUsersTypeId' => 'customeruserstype'],
            'customerusers_thirdparty' => ['CustomerUsers_Id' => 'customerusers', 'ThirdParty_Id' => 'thirdparty'],
            'document' => ['BlId' => 'bl', 'JobFileId' => 'jobfile', 'DocumentTypeId' => 'documenttype'],
            'event' => ['JobFileId' => 'jobfile', 'EventTypeId' => 'eventtype'],
            'eventtype' => ['FamilyId' => 'family'],
            'invoiceitem' => ['InvoiceId' => 'invoice', 'EventId' => 'event', 'SubscriptionId' => 'subscription'],
            'jobfile' => ['PositionId' => 'position'],
            'payment' => ['PaymentTypeId' => 'paymenttype'],
            'position' => ['RowId' => 'row'],
            'rateperiod' => ['RateId' => 'rate'],
            'raterangeperiod' => ['RatePeriodId' => 'rateperiod'],
            'row' => ['AreaId' => 'area'],
            'subscription' => ['RateId' => 'rate', 'ContractId' => 'contract'],
            'thirdparty_thirdpartytype' => ['ThirdParty_Id' => 'thirdparty', 'ThirdPartyType_Id' => 'thirdpartytype'],
        ];
        
        $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
        
        foreach ($mappings as $table => $cols) {
            foreach ($cols as $col => $refTable) {
                $this->addFK($table, $col, $refTable);
            }
        }
        
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        Display::success("Clés étrangères créées: {$this->createdCount}");
        echo "   • Ignorées/Existantes: {$this->skippedCount}\n";
    }
    
    private function addFK($table, $col, $refTable) {
        $key = "$table.$col";
        $hash = substr(md5($key), 0, 5);
        $name = "FK_" . substr($table, 0, 10) . "_$hash";
        $sql = "ALTER TABLE `$table` ADD CONSTRAINT `$name` 
                FOREIGN KEY (`$col`) REFERENCES `$refTable` (`Id`) 
                ON DELETE RESTRICT ON UPDATE CASCADE";
        
        if ($this->conn->query($sql)) {
            echo "   • $key → $refTable\n";
            $this->createdCount++;
        } else {
            if (strpos($this->conn->error, 'already exists') === false) {
                Display::warning("$key: " . $this->conn->error);
            }
            $this->skippedCount++;
        }
    }
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
        Display::title("CRÉATION DES PROCÉDURES STOCKÉES");
        Display::info("⚠️  Les définitions des procédures stockées sont gérées directement dans la base de données.");
        Display::info("Consultez la documentation ou exécutez les scripts SQL fournis séparément.");
        return true;
    }
}

// ============================================================================
// CLASSE: MAINTENANCE
// ============================================================================

class DatabaseMaintenance {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function verifyIntegrity() {
        Display::title("VÉRIFICATION INTÉGRITÉ BASE DE DONNÉES");
        
        Display::info("Démarrage des vérifications...");
        
        $result = $this->conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='ies'");
        $row = $result->fetch_assoc();
        Display::success("Total tables: " . $row['count']);
        
        $tables_check = ['event', 'eventtype', 'paymenttype', 'commodityitem'];
        foreach ($tables_check as $table) {
            $result = $this->conn->query("SELECT COUNT(*) as count FROM `$table` WHERE Id = 0");
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row['count'] > 0) {
                    Display::warning("$table: {$row['count']} entrée(s) avec Id=0");
                } else {
                    Display::success("$table: Aucun enregistrement invalide");
                }
            }
        }
        
        Display::success("Vérification complétée");
    }
    
    public function fixStructure() {
        Display::title("CORRECTION STRUCTURE BASE DE DONNÉES");
        
        Display::info("Vérification et correction de la structure...");
        
        $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
        
        $corrections = [
            "ALTER TABLE `event` MODIFY COLUMN `Id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
            "ALTER TABLE `eventtype` MODIFY COLUMN `Id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
            "ALTER TABLE `paymenttype` MODIFY COLUMN `Id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        ];
        
        $success = 0;
        foreach ($corrections as $sql) {
            if ($this->conn->query($sql)) {
                $success++;
            }
        }
        
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        Display::success("$success tables corrigées");
    }
    
    public function analyze() {
        Display::title("ANALYSE COMPLÈTE BASE DE DONNÉES");
        
        Display::info("Analyse de la structure...");
        
        $result = $this->conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='ies'");
        $row = $result->fetch_assoc();
        Display::info("Tables: {$row['count']}");
        
        $result = $this->conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='ies'");
        $row = $result->fetch_assoc();
        Display::info("Colonnes: {$row['count']}");
        
        $result = $this->conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='ies' AND REFERENCED_TABLE_NAME IS NOT NULL");
        $row = $result->fetch_assoc();
        Display::info("Clés étrangères: {$row['count']}");
        
        Display::success("Analyse complétée");
    }
}

// ============================================================================
// CLASSE: VÉRIFICATION DES RELATIONS
// ============================================================================

class RelationshipVerifier {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function verify() {
        Display::title("RAPPORT DES CLÉS ÉTRANGÈRES IES");
        
        $result = $this->conn->query(
            "SELECT DISTINCT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA='ies' AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY TABLE_NAME, COLUMN_NAME"
        );
        
        if ($result && $result->num_rows > 0) {
            echo "📊 RELATIONS ÉTABLIES\n";
            echo str_repeat("─", 82) . "\n";
            printf("%-32s | %-32s | %s\n", "Colonne Source", "Table Cible", "Colonne Cible");
            echo str_repeat("─", 82) . "\n";
            
            $relations = [];
            while ($row = $result->fetch_assoc()) {
                $key = $row['TABLE_NAME'] . '.' . $row['COLUMN_NAME'];
                if (!isset($relations[$key])) {
                    $relations[$key] = $row;
                }
            }
            
            foreach ($relations as $row) {
                printf("%-32s | %-32s | %s\n",
                    $row['TABLE_NAME'] . '.' . $row['COLUMN_NAME'],
                    $row['REFERENCED_TABLE_NAME'],
                    $row['REFERENCED_COLUMN_NAME']
                );
            }
            
            echo str_repeat("─", 82) . "\n";
            Display::success("Total de clés étrangères: " . count($relations));
        } else {
            Display::warning("Aucune clé étrangère trouvée");
        }
    }
}

// ============================================================================
// CLASSE: VALIDATION DES RELATIONS
// ============================================================================

class RelationshipValidator {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function validate() {
        Display::title("VALIDATION DES CONTRAINTES ÉTRANGÈRES");
        
        $result = $this->conn->query(
            "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA='ies' AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        $row = $result->fetch_assoc();
        Display::success("Total de clés étrangères: " . $row['count']);
        
        $result = $this->conn->query(
            "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES 
             WHERE TABLE_SCHEMA='ies' AND ENGINE='InnoDB'"
        );
        $row = $result->fetch_assoc();
        Display::success("Tables InnoDB: " . $row['count']);
        
        echo "\n🧪 TEST DE CONTRAINTE:\n";
        echo "   Tentative d'insertion avec clé étrangère invalide...\n";
        
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        $test_sql = "INSERT INTO cart (CustomerUserId) VALUES (999999)";
        if ($this->conn->query($test_sql)) {
            Display::warning("   ⚠️ FK non validée (insertion acceptée)");
        } else {
            if (strpos($this->conn->error, 'foreign key constraint fails') !== false) {
                Display::success("   CORRECTE: Insertion rejetée par la contrainte FK");
                echo "   ✓ Les contraintes d'intégrité fonctionnent correctement\n";
            } else {
                Display::info("   Test: " . substr($this->conn->error, 0, 80) . "...");
            }
        }
    }
}

// ============================================================================
// CLASSE: SCRIPTS D'ANALYSE
// ============================================================================

class AnalysisScripts {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function analyzeRelations() {
        Display::title("ANALYSE DES RELATIONS ET DE LA LOGIQUE MÉTIER");
        
        Display::section("STRUCTURE DE LIEN invoiceitem → invoice");
        echo "invoiceitem.InvoiceId → invoice.Id\n";
        echo "invoiceitem.JobFileId → jobfile.Id\n";
        echo "invoiceitem.EventId → event.Id\n";
        echo "invoiceitem.SubscriptionId → subscription.Id\n";
        
        Display::section("COLONNES DE invoice");
        $result = $this->conn->query("DESCRIBE invoice");
        while ($row = $result->fetch_assoc()) {
            if (stripos($row['Field'], 'thirdparty') !== false || 
                stripos($row['Field'], 'customer') !== false ||
                stripos($row['Field'], 'bl') !== false) {
                echo "  → {$row['Field']} ({$row['Type']})\n";
            }
        }
        
        Display::section("TABLE: subscription");
        $result = $this->conn->query("DESCRIBE subscription");
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['Key'] ? " [KEY: {$row['Key']}]" : '';
            $rows[] = [$row['Field'], $row['Type'], $key];
        }
        Display::table(['Field', 'Type', 'Key'], $rows);
        
        Display::section("RELATION jobfile → bl");
        echo "jobfile.Id ← blitem_jobfile.JobFile_Id\n";
        echo "blitem_jobfile.BLItem_Id → blitem.Id\n";
        echo "blitem.BLId → bl.Id\n";
        echo "bl.ConsigneeId → thirdparty.Id\n";
    }
    
    public function analyzeTables() {
        Display::title("ANALYSE DE LA STRUCTURE RÉELLE DES TABLES");
        
        $tables = ['invoice', 'invoiceitem', 'jobfile', 'event'];
        
        foreach ($tables as $table) {
            Display::section("TABLE: $table");
            
            $result = $this->conn->query("DESCRIBE $table");
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $nullable = $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
                $key = $row['Key'] ? " [KEY: {$row['Key']}]" : '';
                $rows[] = [$row['Field'], $row['Type'], $nullable, $key];
            }
            Display::table(['Field', 'Type', 'Nullable', 'Key'], $rows);
        }
    }
    
    public function findJobfileBLRelation() {
        Display::title("RECHERCHE DE LA RELATION jobfile ↔ bl");
        
        Display::section("Clés étrangères pour jobfile et bl");
        $result = $this->conn->query("
            SELECT COLUMN_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'ies'
            AND (TABLE_NAME = 'jobfile' OR TABLE_NAME = 'bl')
            AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME
        ");
        
        while ($row = $result->fetch_assoc()) {
            echo "{$row['TABLE_NAME']}.{$row['COLUMN_NAME']} → {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
        }
    }
    
    public function verifyCorrectRelation() {
        Display::title("VÉRIFICATION DE LA RELATION COMPLÈTE");
        
        Display::section("Structure blitem_jobfile");
        $result = $this->conn->query("DESCRIBE blitem_jobfile");
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['Key'] ? " [KEY: {$row['Key']}]" : '';
            $nullable = $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
            $rows[] = [$row['Field'], $row['Type'], $nullable, $key];
        }
        Display::table(['Field', 'Type', 'Nullable', 'Key'], $rows);
    }
    
    public function checkTaxStructure() {
        Display::title("STRUCTURE - TVA ET TAXCODE");
        
        Display::section("Colonnes de TAXCODE");
        $result = $this->conn->query("DESCRIBE taxcode");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                echo "  • {$row['Field']} ({$row['Type']})\n";
            }
        } else {
            Display::warning("Table taxcode non trouvée");
        }
    }
}

// ============================================================================
// CLASSE: VÉRIFICATION DES PROCÉDURES
// ============================================================================

class VerificationScripts {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function checkProcedures() {
        Display::title("PROCÉDURES STOCKÉES EXISTANTES DANS LA BASE");
        
        $result = $this->conn->query("SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES 
                                      WHERE ROUTINE_SCHEMA = 'ies' AND ROUTINE_TYPE = 'PROCEDURE' 
                                      ORDER BY ROUTINE_NAME");
        
        $procedures = [];
        while ($row = $result->fetch_assoc()) {
            $procedures[] = $row['ROUTINE_NAME'];
        }
        
        echo "Procédures trouvées: " . count($procedures) . "\n";
        foreach ($procedures as $proc) {
            echo "  • $proc\n";
        }
    }
    
    public function verifyGenerateProforma() {
        Display::title("VÉRIFICATION DE LA PROCÉDURE GenerateProforma");
        
        $sql = 'SELECT ROUTINE_DEFINITION, CREATED, LAST_ALTERED FROM INFORMATION_SCHEMA.ROUTINES 
                WHERE ROUTINE_SCHEMA = "ies" AND ROUTINE_NAME = "GenerateProforma"';
        $result = $this->conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            Display::success("Procédure GenerateProforma trouvée");
            echo "   Créée: {$row['CREATED']}\n";
            echo "   Modifiée: {$row['LAST_ALTERED']}\n";
        } else {
            Display::error("Procédure GenerateProforma introuvable");
        }
    }
}

// ============================================================================
// CLASSE: DIAGNOSTICS
// ============================================================================

class DiagnosticTools {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function diagnoseProcedureStructure() {
        Display::title("DIAGNOSTIC DE LA STRUCTURE - GenerateProforma");
        
        $tables = ['contract_eventtype', 'contract', 'event', 'subscription'];
        
        foreach ($tables as $table) {
            Display::section("ANALYSE TABLE: $table");
            
            $result = $this->conn->query("DESC $table");
            if ($result) {
                $rows = [];
                while ($col = $result->fetch_assoc()) {
                    $rows[] = [$col['Field'], $col['Type'], ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL')];
                }
                Display::table(['Field', 'Type', 'Nullable'], $rows);
            } else {
                Display::error("Table non trouvée!");
            }
        }
    }
}

// ============================================================================
// CLASSE: APPLICATION AVEC MENU INTERACTIF
// ============================================================================

class UnifiedSystemApp {
    private $scripts = [];
    
    public function __construct() {
        $this->scripts = [
            // ANALYSIS
            '1' => ['name' => 'Analyze Relations', 'class' => 'AnalysisScripts', 'method' => 'analyzeRelations'],
            '2' => ['name' => 'Analyze Tables', 'class' => 'AnalysisScripts', 'method' => 'analyzeTables'],
            '3' => ['name' => 'Find JobFile-BL Relation', 'class' => 'AnalysisScripts', 'method' => 'findJobfileBLRelation'],
            '4' => ['name' => 'Verify Correct Relation', 'class' => 'AnalysisScripts', 'method' => 'verifyCorrectRelation'],
            '5' => ['name' => 'Check Tax Structure', 'class' => 'AnalysisScripts', 'method' => 'checkTaxStructure'],
            // VERIFICATION
            '6' => ['name' => 'Check Procedures', 'class' => 'VerificationScripts', 'method' => 'checkProcedures'],
            '7' => ['name' => 'Verify GenerateProforma', 'class' => 'VerificationScripts', 'method' => 'verifyGenerateProforma'],
            // MAINTENANCE
            '8' => ['name' => 'Create Relationships', 'class' => 'RelationshipManager', 'method' => 'createAll'],
            '9' => ['name' => 'Verify Relationships', 'class' => 'RelationshipVerifier', 'method' => 'verify'],
            '10' => ['name' => 'Validate Relationships', 'class' => 'RelationshipValidator', 'method' => 'validate'],
            '11' => ['name' => 'Create Procedures', 'class' => 'ProcedureManager', 'method' => 'createAll'],
            '12' => ['name' => 'Verify Integrity', 'class' => 'DatabaseMaintenance', 'method' => 'verifyIntegrity'],
            '13' => ['name' => 'Fix Structure', 'class' => 'DatabaseMaintenance', 'method' => 'fixStructure'],
            '14' => ['name' => 'Analyze Database', 'class' => 'DatabaseMaintenance', 'method' => 'analyze'],
            // DIAGNOSTICS
            '15' => ['name' => 'Diagnose Procedure Structure', 'class' => 'DiagnosticTools', 'method' => 'diagnoseProcedureStructure'],
        ];
    }
    
    public function showMenu() {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║ SYSTÈME UNIFIÉ DE MAINTENANCE ET DIAGNOSTICS - IES                             ║\n";
        echo "║ Version: 1.0 | Fusion system.php + CONSOLIDATED_SCRIPTS.php                    ║\n";
        echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";
        
        echo "SÉLECTIONNEZ UN SCRIPT À EXÉCUTER:\n\n";
        echo "ANALYSE:\n";
        echo "  1.  Analyze Relations\n";
        echo "  2.  Analyze Tables\n";
        echo "  3.  Find JobFile-BL Relation\n";
        echo "  4.  Verify Correct Relation\n";
        echo "  5.  Check Tax Structure\n\n";
        
        echo "VÉRIFICATION:\n";
        echo "  6.  Check Procedures\n";
        echo "  7.  Verify GenerateProforma\n\n";
        
        echo "MAINTENANCE:\n";
        echo "  8.  Create Relationships\n";
        echo "  9.  Verify Relationships\n";
        echo "  10. Validate Relationships\n";
        echo "  11. Create Procedures\n";
        echo "  12. Verify Integrity\n";
        echo "  13. Fix Structure\n";
        echo "  14. Analyze Database\n\n";
        
        echo "DIAGNOSTICS:\n";
        echo "  15. Diagnose Procedure Structure\n\n";
        
        echo "AUTRES:\n";
        echo "  all  - Run all scripts\n";
        echo "  exit - Exit program\n\n";
    }
    
    public function run() {
        if (php_sapi_name() !== 'cli') {
            echo "This application must be run from the command line.\n";
            return;
        }
        
        while (true) {
            $this->showMenu();
            echo "Entrez votre choix: ";
            $choice = trim(fgets(STDIN));
            
            if ($choice === 'exit') {
                echo "\nAu revoir!\n";
                break;
            } else if ($choice === 'all') {
                foreach ($this->scripts as $script) {
                    $this->executeScript($script);
                    echo "\n\nAppuyez sur ENTRÉE pour continuer...";
                    fgets(STDIN);
                }
            } else if (isset($this->scripts[$choice])) {
                $this->executeScript($this->scripts[$choice]);
            } else {
                echo "\n❌ Choix invalide. Veuillez réessayer.\n";
            }
        }
    }
    
    private function executeScript($script) {
        $conn = DatabaseConnection::getInstance();
        $class = $script['class'];
        $method = $script['method'];
        
        if (in_array($class, ['AnalysisScripts', 'VerificationScripts', 'DiagnosticTools'])) {
            $obj = new $class($conn);
        } else {
            $obj = new $class($conn);
        }
        
        $obj->$method();
    }
}

// ============================================================================
// SYSTÈME DE COMMANDES CLI
// ============================================================================

if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'help';
    $conn = DatabaseConnection::getInstance();
    
    try {
        switch ($command) {
            case 'config':
                Display::title("CONFIGURATION ACTUELLE");
                echo "Host: " . DB_HOST . "\n";
                echo "User: " . DB_USER . "\n";
                echo "Database: " . DB_NAME . "\n";
                echo "Charset: " . DB_CHARSET . "\n";
                break;
            
            case 'relationships':
                $manager = new RelationshipManager($conn);
                $manager->createAll();
                break;
            
            case 'verify-relationships':
                $verifier = new RelationshipVerifier($conn);
                $verifier->verify();
                break;
            
            case 'validate-relationships':
                $validator = new RelationshipValidator($conn);
                $validator->validate();
                break;
            
            case 'procedures':
                $manager = new ProcedureManager($conn);
                $manager->createAll();
                break;
            
            case 'maintenance':
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
                        Display::error("Commande inconnue: $subcommand");
                }
                break;
            
            case 'analyze':
                $analysis = new AnalysisScripts($conn);
                $analysis->analyzeRelations();
                $analysis->analyzeTables();
                $analysis->findJobfileBLRelation();
                break;
            
            case 'menu':
                $app = new UnifiedSystemApp();
                $app->run();
                break;
            
            case 'help':
            default:
                Display::title("AIDE - SYSTÈME UNIFIÉ DE MAINTENANCE ET DIAGNOSTICS");
                echo "UTILISATION:\n";
                echo "  php UNIFIED_SYSTEM.php [command] [options]\n\n";
                echo "COMMANDES DISPONIBLES:\n";
                echo "  config                  - Afficher la configuration\n";
                echo "  relationships           - Créer les clés étrangères manquantes\n";
                echo "  verify-relationships    - Vérifier les relations établies\n";
                echo "  validate-relationships  - Valider les contraintes FK\n";
                echo "  procedures              - Créer les procédures stockées\n";
                echo "  maintenance             - Maintenance BD (verify-integrity, fix-structure, analyze)\n";
                echo "  analyze                 - Analyse complète des relations et tables\n";
                echo "  menu                    - Mode interactif avec menu\n";
                echo "  help                    - Afficher cette aide\n\n";
                echo "EXEMPLES:\n";
                echo "  php UNIFIED_SYSTEM.php relationships\n";
                echo "  php UNIFIED_SYSTEM.php verify-relationships\n";
                echo "  php UNIFIED_SYSTEM.php procedures\n";
                echo "  php UNIFIED_SYSTEM.php maintenance verify-integrity\n";
                echo "  php UNIFIED_SYSTEM.php analyze\n";
                echo "  php UNIFIED_SYSTEM.php menu\n";
                break;
        }
    } catch (Exception $e) {
        Display::error("Erreur: " . $e->getMessage());
    }
} else {
    echo "This application must be run from the command line.\n";
    echo "Usage: php UNIFIED_SYSTEM.php [command] [options]\n";
}

?>
