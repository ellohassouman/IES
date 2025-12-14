<?php
/**
 * Script de génération de données réalistes pour IES
 * 50 BLs avec 50% conteneurs et 50% véhicules
 */

// Inclure la configuration
require_once 'config.php';

// Charger les variables d'environnement
$env_file = __DIR__ . '/../Backend/.env';
$env = [];
if (file_exists($env_file)) {
    $lines = file($env_file);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $env[trim($key)] = trim($val);
        }
    }
}

// Configuration de la base de données (utilise config.php comme fallback)
$host = $env['DB_HOST'] ?? $DB_CONFIG['host'];
$user = $env['DB_USERNAME'] ?? $DB_CONFIG['user'];
$password = $env['DB_PASSWORD'] ?? $DB_CONFIG['password'];
$database = $env['DB_DATABASE'] ?? $DB_CONFIG['database'];
$port = $env['DB_PORT'] ?? 3306;

// Connexion à la base de données
try {
    $conn = new mysqli($host, $user, $password, $database, $port);
    if ($conn->connect_error) {
        die("✗ Erreur de connexion: " . $conn->connect_error);
    }
    showSuccess("Connexion réussie à la base de données");
} catch (Exception $e) {
    die("✗ Erreur: " . $e->getMessage());
}

class DataGenerator
{
    private $conn;
    private $nextIds = [];
    
    public function __construct($conn)
    {
        $this->conn = $conn;
    }
    
    public function getNextId($table)
    {
        if (!isset($this->nextIds[$table])) {
            $result = $this->conn->query("SELECT MAX(Id) as maxId FROM `$table`");
            $row = $result->fetch_assoc();
            $this->nextIds[$table] = ($row['maxId'] ?? 0) + 1;
        } else {
            $this->nextIds[$table]++;
        }
        return $this->nextIds[$table];
    }
    
    public function createShippingLines()
    {
        echo "\n📦 Création des Shipping Lines...\n";
        
        $shippingLines = [
            ['code' => 'MSC', 'label' => 'Mediterranean Shipping Company'],
            ['code' => 'MAERSK', 'label' => 'Maersk Line'],
            ['code' => 'CMA', 'label' => 'CMA CGM'],
            ['code' => 'HAPAG', 'label' => 'Hapag-Lloyd'],
            ['code' => 'OOCL', 'label' => 'Orient Overseas Container Line'],
            ['code' => 'EVERGREEN', 'label' => 'Evergreen Line'],
            ['code' => 'COSCO', 'label' => 'China Ocean Shipping Company'],
            ['code' => 'YANGMING', 'label' => 'Yang Ming Marine Transport'],
        ];
        
        $slIds = [];
        $startId = 100;
        
        foreach ($shippingLines as $i => $sl) {
            $tpId = $startId + $i;
            
            $sql = "INSERT IGNORE INTO `thirdparty` (Id, code, Label) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iss", $tpId, $sl['code'], $sl['label']);
            $stmt->execute();
            
            $sql = "INSERT IGNORE INTO `thirdparty_thirdpartytype` (ThirdParty_Id, ThirdPartyType_Id) VALUES (?, 1)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $tpId);
            $stmt->execute();
            
            $slIds[$sl['code']] = $tpId;
            echo "  ✓ {$sl['label']} (ID: $tpId)\n";
        }
        
        return $slIds;
    }
    
    public function createCalls($slIds)
    {
        echo "\n⚓ Création des appels de navires...\n";
        
        $callIds = [];
        $startDate = new DateTime('2025-01-01');
        
        foreach ($slIds as $code => $tpId) {
            $callId = $this->getNextId('call');
            
            $arrivalInterval = rand(0, 364);
            $arrivalDate = (clone $startDate)->add(new DateInterval("P{$arrivalInterval}D"));
            
            $departInterval = rand(2, 7);
            $departureDate = (clone $arrivalDate)->add(new DateInterval("P{$departInterval}D"));
            
            $callNumber = sprintf('CALL/2025/%05d', $callId);
            
            $sql = "INSERT INTO `call` (Id, CallNumber, VesselArrivalDate, VesselDepatureDate, ThirdPartyId) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $arrivalStr = $arrivalDate->format('Y-m-d H:i:s');
            $departStr = $departureDate->format('Y-m-d H:i:s');
            $stmt->bind_param("isssi", $callId, $callNumber, $arrivalStr, $departStr, $tpId);
            $stmt->execute();
            
            $callIds[$callNumber] = [
                'callId' => $callId,
                'slId' => $tpId,
                'arrivalDate' => $arrivalDate,
            ];
            
            echo "  ✓ $callNumber - $code (" . $arrivalDate->format('Y-m-d') . ")\n";
        }
        
        return $callIds;
    }
    
    public function generateBlNumber($index)
    {
        $prefixes = ['EBKG', 'AEV', 'MEDU', 'HAPG', 'OOCL', 'EVER', 'COSC', 'YMLN'];
        $prefix = $prefixes[$index % count($prefixes)];
        $number = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        return $prefix . $number;
    }
    
    public function createBlsAndItems($slIds, $callIds)
    {
        echo "\n📋 Création des BLs et items...\n";
        
        $containerNumbers = [
            'TCLU1234567', 'MSCU7654321', 'MAEU9876543', 'CMAU1122334',
            'HAPAG555666', 'OOCLK778899', 'EVERC001002', 'COSCO112233',
        ];
        
        $chassisNumbers = [
            'WBA1234567890', 'VIN2023XYZ001', 'JT2BF18K0M0123456',
            'WVWZZZ3CZ9E123456', 'KMHEC4A46CU123456', 'LVV1234567ABC',
        ];
        
        $blData = [];
        $callList = array_values($callIds);
        
        for ($blIndex = 0; $blIndex < 50; $blIndex++) {
            $blId = $this->getNextId('bl');
            $blNumber = $this->generateBlNumber($blIndex);
            
            $callInfo = $callList[array_rand($callList)];
            $consigneeId = rand(1, 3);
            
            $sql = "INSERT INTO `bl` (Id, BlNumber, ConsigneeId, CallId) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isii", $blId, $blNumber, $consigneeId, $callInfo['callId']);
            $stmt->execute();
            
            $numItems = rand(2, 4);
            $itemIds = [];
            $itemTypes = [];
            
            for ($itemIndex = 0; $itemIndex < $numItems; $itemIndex++) {
                $itemId = $this->getNextId('blitem');
                $itemTypeId = rand(1, 2);
                
                if ($itemTypeId == 1) {
                    $itemNumber = $containerNumbers[array_rand($containerNumbers)];
                    $itemCodeId = rand(17, 280);
                } else {
                    $itemNumber = $chassisNumbers[array_rand($chassisNumbers)];
                    $itemCodeId = rand(1, 5);
                }
                
                $weight = rand(1000, 25000);
                $volume = rand(10, 60);
                
                $sql = "INSERT INTO `blitem` (Id, Number, Weight, Volume, BlId, ItemTypeId, ItemCodeId) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("isiiiii", $itemId, $itemNumber, $weight, $volume, $blId, $itemTypeId, $itemCodeId);
                $stmt->execute();
                
                $itemIds[] = $itemId;
                $itemTypes[] = $itemTypeId;
            }
            
            $blData[$blNumber] = [
                'id' => $blId,
                'callId' => $callInfo['callId'],
                'slId' => $callInfo['slId'],
                'consigneeId' => $consigneeId,
                'arrivalDate' => $callInfo['arrivalDate'],
                'itemIds' => $itemIds,
                'itemTypes' => $itemTypes,
            ];
            
            if (($blIndex + 1) % 10 == 0) {
                echo "  ✓ " . ($blIndex + 1) . "/50 BLs créés\n";
            }
        }
        
        $totalItems = array_sum(array_map(fn($bl) => count($bl['itemIds']), $blData));
        echo "  ✓ " . count($blData) . " BLs et $totalItems items créés\n";
        return $blData;
    }
    
    public function createJobfilesAndEvents($blData)
    {
        echo "\n🔄 Création des cycles de vie (JobFiles) et événements...\n";
        
        $eventFamilies = [
            'IN' => [4],
            'MOVE' => [29, 31, 32],
            'TRANSFER' => [33, 35],
            'CLEANING' => [21, 23, 24],
            'REPAIR' => [17, 19, 20],
            'OUT' => [6, 8, 9],
        ];
        
        $positionIds = [47, 48, 49, 50];
        $jobfileData = [];
        
        foreach ($blData as $blNumber => $blInfo) {
            foreach ($blInfo['itemIds'] as $itemIndex => $itemId) {
                if (rand(0, 100) > 30) {
                    $jobfileId = $this->getNextId('jobfile');
                    $dateOpenInterval = rand(0, 3);
                    $dateOpen = (clone $blInfo['arrivalDate'])->add(new DateInterval("P{$dateOpenInterval}D"));
                    
                    $hasOut = rand(0, 100) < 60;
                    $dateClose = null;
                    
                    if ($hasOut) {
                        $dateCloseInterval = rand(5, 30);
                        $dateClose = (clone $dateOpen)->add(new DateInterval("P{$dateCloseInterval}D"));
                    }
                    
                    $positionId = $positionIds[array_rand($positionIds)];
                    
                    $sql = "INSERT INTO `jobfile` (Id, DateOpen, DateClose, ShippingLineId, PositionId) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $dateOpenStr = $dateOpen->format('Y-m-d H:i:s');
                    $dateCloseStr = $dateClose ? $dateClose->format('Y-m-d H:i:s') : null;
                    $stmt->bind_param("issii", $jobfileId, $dateOpenStr, $dateCloseStr, $blInfo['slId'], $positionId);
                    $stmt->execute();
                    
                    $sql = "INSERT INTO `blitem_jobfile` (BLItem_Id, JobFile_Id) VALUES (?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("ii", $itemId, $jobfileId);
                    $stmt->execute();
                    
                    $eventIds = $this->createEventsForJobfile($jobfileId, $dateOpen, $dateClose, $eventFamilies);
                    
                    $jobfileData[$jobfileId] = [
                        'blId' => $blInfo['id'],
                        'itemId' => $itemId,
                        'dateOpen' => $dateOpen,
                        'dateClose' => $dateClose,
                        'eventIds' => $eventIds,
                    ];
                }
            }
        }
        
        echo "  ✓ " . count($jobfileData) . " JobFiles créés avec événements\n";
        return $jobfileData;
    }
    
    public function createEventsForJobfile($jobfileId, $dateOpen, $dateClose, $eventFamilies)
    {
        $eventIds = [];
        
        // Event d'entrée
        $eventId = $this->getNextId('event');
        $eventTypeId = $eventFamilies['IN'][0];
        
        $sql = "INSERT INTO `event` (Id, EventDate, JobFileId, EventTypeId) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $dateStr = $dateOpen->format('Y-m-d H:i:s');
        $stmt->bind_param("isii", $eventId, $dateStr, $jobfileId, $eventTypeId);
        $stmt->execute();
        
        $eventIds[] = ['id' => $eventId, 'family' => 'IN', 'typeId' => $eventTypeId];
        
        $currentDate = clone $dateOpen;
        $currentDate->add(new DateInterval('P' . rand(1, 3) . 'D'));
        
        if ($dateClose) {
            $numIntermediate = rand(2, 4);
            $intermediateFamilies = ['MOVE', 'TRANSFER', 'CLEANING', 'REPAIR'];
            
            for ($i = 0; $i < $numIntermediate; $i++) {
                if ($currentDate < $dateClose) {
                    $family = $intermediateFamilies[array_rand($intermediateFamilies)];
                    $eventTypeId = $eventFamilies[$family][array_rand($eventFamilies[$family])];
                    
                    $eventId = $this->getNextId('event');
                    $sql = "INSERT INTO `event` (Id, EventDate, JobFileId, EventTypeId) VALUES (?, ?, ?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $dateStr = $currentDate->format('Y-m-d H:i:s');
                    $stmt->bind_param("isii", $eventId, $dateStr, $jobfileId, $eventTypeId);
                    $stmt->execute();
                    
                    $eventIds[] = ['id' => $eventId, 'family' => $family, 'typeId' => $eventTypeId];
                    $currentDate->add(new DateInterval('P' . rand(1, 4) . 'D'));
                }
            }
            
            // Event de sortie
            $eventId = $this->getNextId('event');
            $eventTypeId = $eventFamilies['OUT'][array_rand($eventFamilies['OUT'])];
            
            $sql = "INSERT INTO `event` (Id, EventDate, JobFileId, EventTypeId) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $dateStr = $dateClose->format('Y-m-d H:i:s');
            $stmt->bind_param("isii", $eventId, $dateStr, $jobfileId, $eventTypeId);
            $stmt->execute();
            
            $eventIds[] = ['id' => $eventId, 'family' => 'OUT', 'typeId' => $eventTypeId];
        }
        
        return $eventIds;
    }
    
    public function createSearchHistory($blData)
    {
        echo "\n🔍 Création de l'historique de recherche...\n";
        
        $userIds = [1, 2, 3];
        $searchCount = 0;
        
        $blDataArray = array_slice(array_values($blData), 0, 30);
        
        foreach ($blDataArray as $blInfo) {
            $numUsers = rand(1, 2);
            for ($u = 0; $u < $numUsers; $u++) {
                $userId = $userIds[array_rand($userIds)];
                $searchId = $this->getNextId('customeruserblsearchhistory');
                
                // Récupérer le nom du shipping
                $result = $this->conn->query("SELECT Label FROM `thirdparty` WHERE Id = " . $blInfo['slId']);
                $shipData = $result->fetch_assoc();
                $shipName = $shipData['Label'] ?? 'Unknown';
                
                $searchDateInterval = rand(1, 10);
                $searchDate = (clone $blInfo['arrivalDate'])->add(new DateInterval("P{$searchDateInterval}D"));
                $itemCount = count($blInfo['itemIds']);
                
                // Récupérer le numéro BL
                $blNumber = '';
                foreach ($blData as $bn => $bi) {
                    if ($bi['id'] == $blInfo['id']) {
                        $blNumber = $bn;
                        break;
                    }
                }
                
                $sql = "INSERT INTO `customeruserblsearchhistory` (Id, BlNumber, ShipName, ArrivalDate, ItemCount, UserId, SearchDate) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $arrivalStr = $blInfo['arrivalDate']->format('Y-m-d H:i:s');
                $searchStr = $searchDate->format('Y-m-d H:i:s');
                $stmt->bind_param("isssiis", $searchId, $blNumber, $shipName, $arrivalStr, $itemCount, $userId, $searchStr);
                $stmt->execute();
                
                $searchCount++;
            }
        }
        
        echo "  ✓ $searchCount entrées d'historique créées\n";
    }
    
    public function createInvoices($blData)
    {
        echo "\n📄 Création des factures...\n";
        
        $clients = [];
        foreach ($blData as $blNumber => $blInfo) {
            if (!isset($clients[$blInfo['consigneeId']])) {
                $clients[$blInfo['consigneeId']] = [];
            }
            $clients[$blInfo['consigneeId']][] = $blInfo;
        }
        
        $invoiceCount = 0;
        $statusDistribution = [1 => 0, 3 => 0, 4 => 0];
        $statuses = [1, 3, 4];
        
        foreach ($clients as $consigneeId => $blList) {
            $numInvoices = rand(5, 8);
            
            for ($i = 0; $i < $numInvoices; $i++) {
                $invoiceId = $this->getNextId('invoice');
                $invoiceNumber = intval('251' . str_pad($invoiceId, 6, '0', STR_PAD_LEFT));
                
                $status = $statuses[array_rand($statuses)];
                $statusDistribution[$status]++;
                
                $month = rand(1, 12);
                $day = rand(1, 28);
                $validationDate = DateTime::createFromFormat('Y-m-d', sprintf('2025-%02d-%02d', $month, $day));
                
                $subtotal = rand(1000, 50000);
                $tax = intval($subtotal * 0.20);
                $total = $subtotal + $tax;
                
                $sql = "INSERT INTO `invoice` (Id, InvoiceNumber, ValIdationDate, SubTotalAmount, TotalTaxAmount, TotalAmount, BilledThirdPartyId, StatusId) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $dateStr = $validationDate->format('Y-m-d');
                $stmt->bind_param("isisiiii", $invoiceId, $invoiceNumber, $dateStr, $subtotal, $tax, $total, $consigneeId, $status);
                $stmt->execute();
                
                // Créer des lignes de facture
                $numItems = rand(3, 5);
                for ($j = 0; $j < $numItems; $j++) {
                    $invoiceItemId = $this->getNextId('invoiceitem');
                    $quantity = rand(1, 30);
                    $rate = rand(100, 5000);
                    $amount = $quantity * $rate;
                    $calculatedTax = intval($amount * 0.20);
                    
                    $result = $this->conn->query("SELECT Id FROM `jobfile` LIMIT 1");
                    $jobfileData = $result->fetch_assoc();
                    $jobfileId = $jobfileData['Id'] ?? 1;
                    
                    $result = $this->conn->query("SELECT Id FROM `event` WHERE JobFileId = $jobfileId LIMIT 1");
                    $eventData = $result->fetch_assoc();
                    $eventId = $eventData['Id'] ?? 1;
                    
                    $sql = "INSERT INTO `invoiceitem` (Id, Quantity, Rate, Amount, CalculatedTax, InvoiceId, JobFileId, EventId, SubscriptionId, RateRangePeriodId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("iiiiiiii", $invoiceItemId, $quantity, $rate, $amount, $calculatedTax, $invoiceId, $jobfileId, $eventId);
                    $stmt->execute();
                }
                
                $invoiceCount++;
            }
        }
        
        echo "  ✓ $invoiceCount factures créées\n";
        echo "    - Draft: " . $statusDistribution[1] . "\n";
        echo "    - Validated: " . $statusDistribution[3] . "\n";
        echo "    - Paid: " . $statusDistribution[4] . "\n";
    }
    
    public function createCustomerThirdpartyLinks($slIds)
    {
        echo "\n🔗 Création des liens client-shipping line...\n";
        
        $userIds = [1, 2, 3];
        $slIdList = array_values($slIds);
        $linkCount = 0;
        
        foreach ($userIds as $userId) {
            $numLinks = rand(2, 3);
            $keys = array_rand($slIdList, min($numLinks, count($slIdList)));
            if (!is_array($keys)) {
                $keys = [$keys];
            }
            
            foreach ($keys as $key) {
                $slId = $slIdList[$key];
                
                $sql = "INSERT IGNORE INTO `customerusers_thirdparty` (CustomerUsers_Id, ThirdParty_Id) VALUES (?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ii", $userId, $slId);
                $stmt->execute();
                
                $linkCount++;
            }
        }
        
        echo "  ✓ $linkCount liens créés\n";
    }
    
    public function generate()
    {
        try {
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "🚀 Génération des données réalistes IES\n";
            echo str_repeat("=", 60) . "\n";
            
            $slIds = $this->createShippingLines();
            $callIds = $this->createCalls($slIds);
            $blData = $this->createBlsAndItems($slIds, $callIds);
            $this->createCustomerThirdpartyLinks($slIds);
            $jobfileData = $this->createJobfilesAndEvents($blData);
            $this->createSearchHistory($blData);
            $this->createInvoices($blData);
            
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "✅ Génération terminée avec succès!\n";
            echo str_repeat("=", 60) . "\n";
            echo "\n📊 Résumé:\n";
            echo "  • 50 BLs créés\n";
            $totalItems = array_sum(array_map(fn($b) => count($b['itemIds']), $blData));
            echo "  • $totalItems items créés\n";
            echo "  • " . count($jobfileData) . " cycles de vie créés\n";
            echo "  • 8 shipping lines créées\n";
            echo "  • Factures et événements liés créés\n";
            
        } catch (Exception $e) {
            echo "\n✗ Erreur: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}

try {
    $generator = new DataGenerator($conn);
    $generator->generate();
    $conn->close();
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
