<?php
// Debug cart data

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__FILE__) . '/../../Backend/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

try {
    // Get cart data
    $customerUserId = 1;
    $result = DB::select("CALL GetCartByUserId(?)", array($customerUserId));
    
    echo "=== DEBUG CART DATA ===\n";
    echo "CustomerUserId: $customerUserId\n\n";
    
    echo "Raw Result from Procedure:\n";
    var_dump($result);
    echo "\n";
    
    // Process like controller does
    $cart = [
        'id' => null,
        'customerId' => $customerUserId,
        'currencyCode' => 'XOF',
        'totalAmount' => 0,
        'invoices' => []
    ];
    
    $invoiceTotals = [];
    
    foreach ($result as $row) {
        if ($cart['id'] === null && !empty($row->cartId)) {
            $cart['id'] = intval($row->cartId);
        }
        
        $invoiceId = isset($row->InvoiceId) ? $row->InvoiceId : null;
        
        if (!empty($invoiceId)) {
            $invoiceKey = intval($invoiceId);
            
            if (!isset($invoiceTotals[$invoiceKey])) {
                $billingDate = !empty($row->BillingDate) ? $row->BillingDate : date('Y-m-d');
                $invoiceNumber = !empty($row->InvoiceNumber) ? $row->InvoiceNumber : '';
                $invoicePaidAmount = !empty($row->InvoicePaidAmount) ? floatval($row->InvoicePaidAmount) : 0;
                $blNumber = !empty($row->BlNumber) ? $row->BlNumber : '';
                
                echo "\nInvoice $invoiceNumber:\n";
                echo "  - Raw InvoicePaidAmount: $invoicePaidAmount (type: " . gettype($invoicePaidAmount) . ")\n";
                
                $formatted = number_format($invoicePaidAmount, 2, ',', ' ');
                echo "  - After number_format: '$formatted'\n";
                
                $invoiceTotals[$invoiceKey] = [
                    'invoiceId' => $invoiceKey,
                    'invoiceNumber' => $invoiceNumber,
                    'billOfLadingNumber' => $blNumber,
                    'pickupDate' => date('d/m/Y', strtotime($billingDate)),
                    'amount' => $formatted,
                    'currency' => 'XOF'
                ];
            }
        }
    }
    
    $cart['invoices'] = array_values($invoiceTotals);
    
    // Calculate total amount
    foreach ($cart['invoices'] as $invoice) {
        $amount = (float)str_replace([' ', ','], ['', '.'], $invoice['amount']);
        echo "Processing invoice: " . $invoice['invoiceNumber'];
        echo " - amount string: '" . $invoice['amount'] . "'";
        echo " - extracted as: $amount\n";
        $cart['totalAmount'] += $amount;
    }
    
    echo "\n=== FINAL CART DATA ===\n";
    echo "Total Amount: " . $cart['totalAmount'] . " (type: " . gettype($cart['totalAmount']) . ")\n";
    echo "Number of invoices: " . count($cart['invoices']) . "\n";
    
    echo "\n=== CART JSON ===\n";
    echo json_encode($cart, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch(Exception $exp) {
    echo "ERROR: " . $exp->getMessage();
    echo "\nFile: " . $exp->getFile();
    echo "\nLine: " . $exp->getLine();
}
?>
