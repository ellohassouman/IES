<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'ies');
if ($conn->connect_error) die('Erreur: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

echo "=== STRUCTURE: event ===\n";
$result = $conn->query("DESC event");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== EXEMPLE: event ===\n";
$result = $conn->query("SELECT * FROM event LIMIT 2");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\n";
        foreach ($row as $key => $value) {
            echo "  $key: " . substr($value, 0, 100) . "\n";
        }
    }
}
echo "\n";

echo "=== REQUÊTE: invoiceitem avec event et eventtype ===\n";
$result = $conn->query("
    SELECT 
        ii.Id,
        ii.Quantity,
        ii.Rate,
        ii.EventId,
        e.Id as event_id,
        e.EventTypeId,
        et.Id as eventtype_id,
        et.Label as eventtype_label,
        et.Code as eventtype_code
    FROM invoiceitem ii
    LEFT JOIN event e ON ii.EventId = e.Id
    LEFT JOIN eventtype et ON e.EventTypeId = et.Id
    LIMIT 3
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\n";
        foreach ($row as $key => $value) {
            echo "  $key: " . $value . "\n";
        }
    }
}
echo "\n";

echo "=== STRUCTURE: subscription ===\n";
$result = $conn->query("DESC subscription");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

echo "=== EXEMPLE: subscription ===\n";
$result = $conn->query("SELECT * FROM subscription LIMIT 2");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\n";
        foreach ($row as $key => $value) {
            echo "  $key: " . substr($value, 0, 100) . "\n";
        }
    }
}

$conn->close();
?>