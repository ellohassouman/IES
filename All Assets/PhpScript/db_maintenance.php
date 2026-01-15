<?php
/**
 * SYSTÈME UNIFIÉ DE MAINTENANCE ET DIAGNOSTICS - IES v4.0 ULTRA-LIGHT
 * 
 * Fusion optimisée de tous les scripts de gestion
 * • Redondances supprimées | Code inutile éliminé
 * • Seulement les opérations essentielles | Interface simple et directe
 * 
 * USAGE: php UNIFIED_SYSTEM.php [command]
 */

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'ies';
const DB_CHARSET = 'utf8mb4';

// ============================================================================
// DATABASE CONNECTION (SINGLETON)
// ============================================================================

class DB {
    private static $conn;
    
    public static function get() {
        if (!self::$conn) {
            self::$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (self::$conn->connect_error) {
                die("❌ Erreur: " . self::$conn->connect_error);
            }
            self::$conn->set_charset(DB_CHARSET);
        }
        return self::$conn;
    }
}

// ============================================================================
// DISPLAY (AFFICHAGE)
// ============================================================================

class Out {
    public static function title($msg) {
        echo "\n╔" . str_repeat("═", 78) . "╗\n";
        echo "║ " . str_pad($msg, 76) . " ║\n";
        echo "╚" . str_repeat("═", 78) . "╝\n\n";
    }
    
    public static function ok($msg)  { echo "✅ $msg\n"; }
    public static function err($msg) { echo "❌ $msg\n"; }
    public static function info($msg) { echo "ℹ️  $msg\n"; }
    public static function warn($msg) { echo "⚠️  $msg\n"; }
}

// ============================================================================
// RELATIONSHIPS (CLÉS ÉTRANGÈRES)
// ============================================================================

class Relationships {
    private $db;
    
    public function __construct() {
        $this->db = DB::get();
    }
    
    public function create() {
        Out::title("CRÉATION CLÉS ÉTRANGÈRES");
        
        $fks = [
            'area' => ['TerminalId' => 'terminal'],
            'bl' => ['ConsigneeId' => 'thirdparty', 'CallId' => 'call'],
            'blitem' => ['BlId' => 'bl', 'ItemTypeId' => 'yarditemtype'],
            'call' => ['ThirdPartyId' => 'thirdparty'],
            'contract' => ['TaxCodeId' => 'taxcodes'],
            'contract_eventtype' => ['Contract_Id' => 'contract', 'EventType_Id' => 'eventtype'],
            'customerusers' => ['CustomerUsersStatusId' => 'customerusersstatus'],
            'document' => ['BlId' => 'bl', 'DocumentTypeId' => 'documenttype'],
            'event' => ['JobFileId' => 'jobfile', 'EventTypeId' => 'eventtype'],
            'eventtype' => ['FamilyId' => 'family'],
            'invoiceitem' => ['InvoiceId' => 'invoice', 'EventId' => 'event'],
            'jobfile' => ['PositionId' => 'position'],
            'position' => ['RowId' => 'row'],
            'row' => ['AreaId' => 'area'],
            'subscription' => ['RateId' => 'rate', 'ContractId' => 'contract'],
        ];
        
        $this->db->query("SET FOREIGN_KEY_CHECKS=0");
        $ok = $skip = 0;
        
        foreach ($fks as $table => $cols) {
            foreach ($cols as $col => $ref) {
                $hash = substr(md5("$table.$col"), 0, 4);
                $name = "FK_" . substr($table, 0, 8) . "_$hash";
                
                if ($this->db->query("ALTER TABLE `$table` ADD CONSTRAINT `$name` 
                    FOREIGN KEY (`$col`) REFERENCES `$ref` (`Id`) 
                    ON DELETE RESTRICT ON UPDATE CASCADE")) {
                    echo "   ✓ $table.$col → $ref\n";
                    $ok++;
                } else {
                    $skip++;
                }
            }
        }
        
        $this->db->query("SET FOREIGN_KEY_CHECKS=1");
        Out::ok("Créées: $ok | Existantes: $skip");
    }
    
    public function verify() {
        Out::title("CLÉS ÉTRANGÈRES ÉTABLIES");
        
        $r = $this->db->query("
            SELECT DISTINCT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA='" . DB_NAME . "' AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME");
        
        if ($r && $r->num_rows > 0) {
            printf("%-20s | %-20s | %s\n", "Table", "Colonne", "Cible");
            echo str_repeat("─", 65) . "\n";
            while ($row = $r->fetch_assoc()) {
                printf("%-20s | %-20s | %s\n", $row['TABLE_NAME'], $row['COLUMN_NAME'], $row['REFERENCED_TABLE_NAME']);
            }
            Out::ok("Total: " . $r->num_rows);
        } else {
            Out::warn("Aucune clé trouvée");
        }
    }
    
    public function validate() {
        Out::title("VALIDATION CONTRAINTES FK");
        
        $r = $this->db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                              WHERE TABLE_SCHEMA='" . DB_NAME . "' AND REFERENCED_TABLE_NAME IS NOT NULL");
        $row = $r->fetch_assoc();
        Out::ok("Clés étrangères: " . $row['c']);
        
        $this->db->query("SET FOREIGN_KEY_CHECKS=1");
        if (!$this->db->query("INSERT INTO cart (CustomerUserId) VALUES (999999)")) {
            (strpos($this->db->error, 'foreign key') !== false) 
                ? Out::ok("Contraintes OK") 
                : Out::info("Erreur: " . substr($this->db->error, 0, 40));
        }
    }
}

// ============================================================================
// PROCEDURES (PROCÉDURES STOCKÉES)
// ============================================================================

class Procedures {
    private $db;
    
    public function __construct() {
        $this->db = DB::get();
    }
    
    public function list() {
        Out::title("PROCÉDURES STOCKÉES");
        
        $r = $this->db->query("
            SELECT ROUTINE_NAME, CREATED 
            FROM INFORMATION_SCHEMA.ROUTINES 
            WHERE ROUTINE_SCHEMA='" . DB_NAME . "' AND ROUTINE_TYPE='PROCEDURE'
            ORDER BY ROUTINE_NAME");
        
        if ($r && $r->num_rows > 0) {
            while ($row = $r->fetch_assoc()) {
                echo "  • {$row['ROUTINE_NAME']} ({$row['CREATED']})\n";
            }
            Out::ok("Total: " . $r->num_rows);
        } else {
            Out::warn("Aucune procédure");
        }
    }
    
    public function execute($file) {
        Out::title("EXÉCUTION FICHIER SQL");
        
        if (!file_exists($file)) {
            Out::err("Fichier non trouvé: $file");
            return;
        }
        
        $sql = file_get_contents($file);
        $sql = preg_replace('/DELIMITER\s+.*$/m', '', $sql);
        Out::info(basename($file) . " (" . strlen($sql) . " bytes)");
        
        if ($this->db->multi_query($sql)) {
            do {
                if ($r = $this->db->store_result()) $r->free();
            } while ($this->db->next_result());
            Out::ok("Exécuté avec succès");
            $this->list();
        } else {
            Out::err($this->db->error);
        }
    }
}

// ============================================================================
// MAINTENANCE (MAINTENANCE BD)
// ============================================================================

class Maintenance {
    private $db;
    
    public function __construct() {
        $this->db = DB::get();
    }
    
    public function verify() {
        Out::title("VÉRIFICATION INTÉGRITÉ");
        
        $r = $this->db->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='" . DB_NAME . "'");
        $row = $r->fetch_assoc();
        Out::ok("Tables: " . $row['c']);
        
        foreach (['event', 'eventtype', 'invoice'] as $t) {
            $r = $this->db->query("SELECT COUNT(*) as c FROM `$t` WHERE Id=0");
            $row = $r->fetch_assoc();
            ($row['c'] > 0) ? Out::warn("$t: {$row['c']} invalide(s)") : Out::ok("$t: OK");
        }
    }
    
    public function analyze() {
        Out::title("ANALYSE BD");
        
        $stats = [
            'Tables' => "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='" . DB_NAME . "'",
            'Colonnes' => "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . DB_NAME . "'",
            'Foreign Keys' => "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='" . DB_NAME . "' AND REFERENCED_TABLE_NAME IS NOT NULL",
        ];
        
        foreach ($stats as $label => $q) {
            $r = $this->db->query($q);
            $row = $r->fetch_assoc();
            echo "• $label: {$row['c']}\n";
        }
    }
}

// ============================================================================
// CLI INTERFACE
// ============================================================================

if (php_sapi_name() !== 'cli') {
    die("CLI only\n");
}

$cmd = $argv[1] ?? 'help';

try {
    switch ($cmd) {
        case 'config':
            Out::title("CONFIGURATION");
            echo "Host: " . DB_HOST . "\nDB: " . DB_NAME . "\n";
            break;
        
        case 'relationships':
            (new Relationships())->create();
            break;
        
        case 'verify':
            (new Relationships())->verify();
            break;
        
        case 'validate':
            (new Relationships())->validate();
            break;
        
        case 'procedures':
            if (isset($argv[2]) && $argv[2] === 'execute' && isset($argv[3])) {
                (new Procedures())->execute($argv[3]);
            } else {
                (new Procedures())->list();
            }
            break;
        
        case 'correction':
            if (!isset($argv[2])) {
                Out::err("Usage: php UNIFIED_SYSTEM.php correction <file>");
            } else {
                (new Procedures())->execute($argv[2]);
            }
            break;
        
        case 'maintenance':
            $sub = $argv[2] ?? 'verify';
            $m = new Maintenance();
            ($sub === 'verify') ? $m->verify() : $m->analyze();
            break;
        
        case 'help':
        default:
            Out::title("AIDE");
            echo "COMMANDES:\n";
            echo "  config                - Configuration\n";
            echo "  relationships         - Créer clés étrangères\n";
            echo "  verify                - Vérifier relations\n";
            echo "  validate              - Valider contraintes\n";
            echo "  procedures [list]     - Lister procédures\n";
            echo "  procedures execute <f>- Exécuter fichier SQL\n";
            echo "  correction <file>     - Exécuter correction\n";
            echo "  maintenance [verify]  - Vérifier intégrité\n";
            echo "  maintenance analyze   - Analyser BD\n";
            break;
    }
} catch (Exception $e) {
    Out::err($e->getMessage());
}
?>
