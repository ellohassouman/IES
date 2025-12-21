<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class GlobalController extends Controller
{
    // public function GetUserBLPerCriteria(Request $request)
    // {
    //     try
    //     {
    //         $ps= $request->input('Link');
    //         $Item=DB::select(
    //             "CALL ".$ps."(?,?,?,?,?)",
    //             array(
    //                 $request->input('ObjetId')
    //                 ,$request->input('TypeObjetId')
    //                 ,$request->input('ObjetParentId')
    //                 ,$request->input('ForParametrage')
    //                 ,$request->input('UtilisateurId')


    //                 )
    //         );

    //         return response()->json($Item);
    //     }
    //     catch(Exception $exp)
    //     {
    //         throw $exp;
    //     }
    // }

    public function GetUserBLPerNumber(Request $request)
    {
        try
        {
            // $a = true;
            $a=DB::select(

                "CALL GetUserBLPerNumber(?,?)",
                array(
                    $request->input('BlNumber'),
                    $request->input('UtilisateurId')
                    )
            );

            return response()->json($a);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }

    }

    public function GetUserBLHistory(Request $request)
    {
        try
        {
            // $a = true;
            $a=DB::select(

                "CALL GetUserBLHistory(?)",
                array(
                    $request->input('UserId')
                    )
            );

            return response()->json($a);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }

    }

    public function GetDetailsPerBLNumber(Request $request)
    {
        try
        {
            $a=DB::select(

                "CALL GetDetailsPerBLNumber(?)",
                array(
                    $request->input('BlNumber')
                    )
            );

            return response()->json($a);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }

    }

    public function GetInvoicesPerBLNumber(Request $request)
    {
        try
        {
            $invoices = DB::select(
                "CALL GetInvoicesPerBLNumber(?, ?)",
                array(
                    $request->input('blNumber'),
                    $request->input('customerUserId')
                )
            );

            // Process invoices to properly decode JSON fields and format data
            foreach ($invoices as $invoice) {
                // Decode yardItems if it's a string
                if (isset($invoice->yardItems) && is_string($invoice->yardItems)) {
                    $invoice->yardItems = json_decode($invoice->yardItems, true);
                    // Ensure yardItems is an array, not null
                    if ($invoice->yardItems === null) {
                        $invoice->yardItems = [];
                    }
                }

                // Cast statusId to integer
                if (isset($invoice->statusId)) {
                    $invoice->statusId = (int)$invoice->statusId;
                }

                // Cast blId to integer
                if (isset($invoice->blId)) {
                    $invoice->blId = (int)$invoice->blId;
                }

                // Cast id to integer
                if (isset($invoice->id)) {
                    $invoice->id = (int)$invoice->id;
                }

                // Cast isInCart to boolean
                if (isset($invoice->isInCart)) {
                    $invoice->isInCart = (bool)$invoice->isInCart;
                }
            }

            return response()->json($invoices);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }

    }

    public function GetPendingInvoicingItemsPerBLNumber(Request $request)
    {
        try
        {
            $pendingItems = DB::select(
                "CALL GetPendingInvoicingItemsPerBLNumber(?)",
                array(
                    $request->input('blNumber')
                )
            );

            // Process items to ensure proper data types
            foreach ($pendingItems as $item) {
                // Cast id to string (it's already a string from CAST in stored procedure)
                if (isset($item->id)) {
                    $item->id = (string)$item->id;
                }

                // Ensure isDraft is boolean
                if (isset($item->isDraft)) {
                    $item->isDraft = (bool)$item->isDraft;
                }

                // Ensure dnPrintable is boolean
                if (isset($item->dnPrintable)) {
                    $item->dnPrintable = (bool)$item->dnPrintable;
                }
            }

            return response()->json($pendingItems);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }

    }

    public function GetYardItemsPerBLNumber(Request $request)
    {
        try
        {
            $yardItems = DB::select(
                "CALL GetYardItemsPerBLNumber(?)",
                array(
                    $request->input('blNumber')
                )
            );

            // Process items to ensure proper data types
            foreach ($yardItems as $item) {
                // Cast id to integer
                if (isset($item->id)) {
                    $item->id = (int)$item->id;
                }

                // Ensure isDraft is boolean
                if (isset($item->isDraft)) {
                    $item->isDraft = (bool)$item->isDraft;
                }

                // Ensure isDNPrintable is boolean
                if (isset($item->isDNPrintable)) {
                    $item->isDNPrintable = (bool)$item->isDNPrintable;
                }
            }

            return response()->json($yardItems);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }

    }

    public function GetYardItemTrackingMovements(Request $request)
    {
        try
        {
            $movements = DB::select(
                "CALL GetYardItemTrackingMovements(?, ?, ?)",
                array(
                    $request->input('yardItemId'),
                    $request->input('yardItemNumber'),
                    $request->input('billOfLadingNumber')
                )
            );

            return response()->json([
                'success' => true,
                'yardItemList' => $movements
            ]);
        }
        catch(Exception $exp)
        {
            return response()->json([
                'success' => false,
                'message' => $exp->getMessage()
            ]);
        }

    }

    public function DeleteYardItemEvent(Request $request)
    {
        try
        {
            DB::statement(
                "CALL DeleteYardItemEvent(?, ?, ?, ?)",
                array(
                    $request->input('eventTypeCode'),
                    $request->input('yardItemNumber'),
                    $request->input('eventDateString'),
                    $request->input('billOfLadingNumber')
                )
            );

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);
        }
        catch(Exception $exp)
        {
            return response()->json([
                'success' => false,
                'message' => $exp->getMessage()
            ]);
        }

    }

    /**
     * Search for a Bill of Lading by number and automatically insert search history
     * Simply returns the search result without interpreting it
     */
    public function GetBLByNumber(Request $request)
    {
        try
        {
            $blNumber = $request->input('blNumber');
            $userId = $request->input('userId', 1); // Default to user 1 if not provided

            // Call the stored procedure that searches and inserts history
            $result = DB::select(
                "CALL GetBLByNumber(?, ?)",
                array($blNumber, $userId)
            );

            // Return raw result - let frontend handle interpretation
            return response()->json($result);
        }
        catch(Exception $exp)
        {
            // Re-throw the exception to trigger error handler
            throw $exp;
        }
    }

    /**
     * Get cart items for a specific user
     */
    public function GetCartByUserId(Request $request)
    {
        try
        {
            $customerUserId = $request->input('customerUserId', 1);

            $result = DB::select(
                "CALL GetCartByUserId(?)",
                array($customerUserId)
            );

            // Log pour debug
            \Log::debug('GetCartByUserId - Raw result from SP:', ['result' => $result, 'customerId' => $customerUserId]);

            // Process result to structure cart properly
            $cart = [
                'id' => null,
                'customerId' => $customerUserId,
                'currencyCode' => 'XOF',
                'totalAmount' => 0,
                'invoices' => []
            ];

            $invoiceTotals = [];

            foreach ($result as $row) {
                // Log each row for debugging
                \Log::debug('Processing row from DB:', ['row' => (array)$row]);

                // Set cart ID from first row
                if ($cart['id'] === null && !empty($row->cartId)) {
                    $cart['id'] = intval($row->cartId);
                }

                // Only add invoice if invoiceId has a value
                $invoiceId = isset($row->InvoiceId) ? $row->InvoiceId : null;

                if (!empty($invoiceId)) {
                    $invoiceKey = intval($invoiceId);

                    if (!isset($invoiceTotals[$invoiceKey])) {
                        $billingDate = !empty($row->BillingDate) ? $row->BillingDate : date('Y-m-d');
                        $invoiceNumber = !empty($row->InvoiceNumber) ? $row->InvoiceNumber : '';
                        $invoicePaidAmount = !empty($row->InvoicePaidAmount) ? floatval($row->InvoicePaidAmount) : 0;
                        $blNumber = !empty($row->BlNumber) ? $row->BlNumber : '';

                        $invoiceTotals[$invoiceKey] = [
                            'invoiceId' => $invoiceKey,
                            'invoiceNumber' => $invoiceNumber,
                            'billOfLadingNumber' => $blNumber,
                            'pickupDate' => date('d/m/Y', strtotime($billingDate)),
                            'amount' => number_format($invoicePaidAmount, 2, ',', ' '),
                            'currency' => 'XOF'
                        ];

                        \Log::debug('Invoice added to cart:', ['invoice' => $invoiceTotals[$invoiceKey]]);
                    }
                }
            }

            $cart['invoices'] = array_values($invoiceTotals);

            // Calculate total amount
            foreach ($cart['invoices'] as $invoice) {
                $amount = (float)str_replace([' ', ','], ['', '.'], $invoice['amount']);
                $cart['totalAmount'] += $amount;
            }

            \Log::debug('GetCartByUserId - Final cart response:', ['cart' => $cart]);

            return response()->json($cart);
        }
        catch(Exception $exp)
        {
            \Log::error('GetCartByUserId - Exception:', ['error' => $exp->getMessage()]);
            throw $exp;
        }
    }

    public function AddInvoiceToCart(Request $request)
    {
        try
        {
            $customerUserId = $request->input('customerUserId');
            $invoiceId = $request->input('invoiceId');
            $invoiceNumber = $request->input('invoiceNumber');
            $invoicePaidAmount = $request->input('invoicePaidAmount', 0);

            // Valider les entrées
            if (!$customerUserId || !$invoiceId || !$invoiceNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètres manquants'
                ], 400);
            }

            // Appeler la procédure stockée
            $result = DB::select(
                "CALL AddInvoiceToCart(?, ?, ?, ?)",
                array($customerUserId, $invoiceId, $invoiceNumber, $invoicePaidAmount)
            );

            if (count($result) > 0) {
                $resultData = $result[0];
                return response()->json([
                    'success' => true,
                    'cartId' => isset($resultData->cartId) ? $resultData->cartId : null,
                    'itemCount' => isset($resultData->itemCount) ? $resultData->itemCount : 0,
                    'message' => 'Facture ajoutée au panier avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'ajout au panier'
                ], 500);
            }
        }
        catch(Exception $exp)
        {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $exp->getMessage()
            ], 500);
        }
    }

    public function GetCurrentUserCartCount(Request $request)
    {
        try
        {
            $customerUserId = $request->input('customerUserId', 1);

            // Query to count items in user's cart
            $result = DB::select(
                "SELECT COUNT(ci.`Id`) as ItemCount
                 FROM `Cart` c
                 LEFT JOIN `CartItem` ci ON c.`Id` = ci.`CartId`
                 WHERE c.`CustomerUserId` = ? AND c.`Deleted` = 0",
                array($customerUserId)
            );

            $itemCount = 0;
            if (count($result) > 0) {
                $itemCount = isset($result[0]->ItemCount) ? intval($result[0]->ItemCount) : 0;
            }

            return response()->json([
                'ItemCount' => $itemCount
            ]);
        }
        catch(Exception $exp)
        {
            return response()->json([
                'ItemCount' => 0,
                'error' => $exp->getMessage()
            ], 500);
        }
    }

    public function RemoveInvoiceFromCart(Request $request)
    {
        try
        {
            $customerUserId = $request->input('customerUserId', 1);
            $invoiceId = $request->input('invoiceId');

            // Valider les entrées
            if (!$customerUserId || !$invoiceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètres manquants'
                ], 400);
            }

            // Appeler la procédure stockée
            $result = DB::select(
                "CALL RemoveInvoiceFromCart(?, ?)",
                array($customerUserId, $invoiceId)
            );

            if (count($result) > 0) {
                $resultData = $result[0];
                return response()->json([
                    'success' => true,
                    'cartId' => isset($resultData->cartId) ? $resultData->cartId : null,
                    'itemCount' => isset($resultData->itemCount) ? $resultData->itemCount : 0,
                    'message' => 'Facture supprimée du panier avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression du panier'
                ], 500);
            }
        }
        catch(Exception $exp)
        {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $exp->getMessage()
            ], 500);
        }
    }

    public function UpdateInvoiceStatus(Request $request)
    {
        try
        {
            $result = DB::select(
                "CALL UpdateInvoiceStatus(?, ?)",
                array(
                    $request->input('invoiceId'),
                    $request->input('statusId')
                )
            );

            return response()->json($result);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }
    }

    public function UpdateMultipleInvoiceStatus(Request $request)
    {
        try
        {
            $invoices = $request->input('invoices');

            foreach ($invoices as $invoice) {
                DB::statement(
                    "CALL UpdateInvoiceStatus(?, ?)",
                    array($invoice['invoiceId'], $invoice['statusId'])
                );
            }

            return response()->json([
                'success' => true
            ]);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }
    }

    public function ValidatePayment(Request $request)
    {
        try
        {
            $result = DB::select(
                "CALL UpdateInvoiceStatus(?, ?)",
                array(
                    $request->input('invoiceId'),
                    4  // Statut 4 = Paiement validé
                )
            );

            return response()->json($result);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }
    }

    public function DeleteInvoice(Request $request)
    {
        try
        {
            $result = DB::select(
                "CALL DeleteInvoice(?)",
                array(
                    $request->input('invoiceId')
                )
            );

            return response()->json($result);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }
    }

    public function Login(Request $request)
    {
        try
        {
            $email = $request->input('email');
            $password = $request->input('password');

            // Récupérer l'utilisateur avec la procédure
            $users = DB::select(
                "CALL AuthenticateUser(?)",
                array(
                    $email
                )
            );

            // Si pas d'utilisateur trouvé, retourner vide
            if (empty($users)) {
                return response()->json([]);
            }

            $user = $users[0];

            // Vérifier le mot de passe avec Hash::check
            if (!Hash::check($password, $user->PasswordHash)) {
                return response()->json([]);
            }

            // Authentification réussie - retourner les données
            return response()->json($users);
        }
        catch(Exception $exp)
        {
            throw $exp;
        }
    }

    public function CreateProforma(Request $request)
    {
        try
        {
            $billOfLadingId = $request->input('billOfLadingId');
            $billOfLadingNumber = $request->input('billOfLadingNumber');
            $yardItemsJson = $request->input('yardItemsJson');
            $isCash = $request->input('isCash', '0');
            $allowClearingAgentMode = $request->input('allowClearingAgentMode', false);
            $forceOverridenClientName = $request->input('forceOverridenClientName', false);
            $journalType = $request->input('journalType', 'STI');
            $isTransitFileCustomer = $request->input('isTransitFileCustomer', false);
            $billingDate = $request->input('billingDate');

            // Valider les paramètres requis
            if (!$billOfLadingId || !$billOfLadingNumber || !$yardItemsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètres manquants: billOfLadingId, billOfLadingNumber ou yardItemsJson'
                ], 400);
            }

            // Décoder les yard items
            $yardItems = json_decode($yardItemsJson, true);
            if (!is_array($yardItems) || count($yardItems) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'yardItemsJson doit être un tableau JSON valide non vide'
                ], 400);
            }

            // Appeler la procédure stockée pour créer la proforma
            $result = DB::select(
                "CALL CreateProforma(?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array(
                    $billOfLadingId,
                    $billOfLadingNumber,
                    $yardItemsJson,
                    $isCash ? '1' : '0',
                    $allowClearingAgentMode ? '1' : '0',
                    $forceOverridenClientName ? '1' : '0',
                    $journalType,
                    $isTransitFileCustomer ? '1' : '0',
                    $billingDate
                )
            );

            // Vérifier le résultat
            if (!empty($result)) {
                $proformaData = $result[0];

                // Décoder les items si c'est une chaîne JSON
                if (isset($proformaData->items) && is_string($proformaData->items)) {
                    $proformaData->items = json_decode($proformaData->items, true);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Proforma créée avec succès',
                    'data' => $proformaData
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création de la proforma'
                ], 500);
            }
        }
        catch(Exception $exp)
        {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $exp->getMessage()
            ], 500);
        }
    }

    public function AddYardItemEvent(Request $request)
    {
        try
        {
            $yardItemIds = $request->input('yardItemIds', []);
            $blNumber = $request->input('blNumber');
            $eventType = $request->input('eventType');
            $description = $request->input('description');
            $date = $request->input('date');

            // Valider les paramètres requis
            if (empty($yardItemIds) || !$blNumber || !$eventType || !$date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètres manquants'
                ], 400);
            }

            $eventIds = [];

            // Ajouter l'événement pour chaque yard item
            foreach ($yardItemIds as $yardItemId) {
                try {
                    $result = DB::select(
                        "CALL AddYardItemEvent(?, ?, ?, ?, ?)",
                        array(
                            $yardItemId,
                            $blNumber,
                            $eventType,
                            $description,
                            $date
                        )
                    );

                    if (!empty($result) && isset($result[0]->eventId)) {
                        $eventIds[] = $result[0]->eventId;
                    }
                } catch (Exception $e) {
                    // Logger l'erreur mais continuer avec les autres items
                    \Log::error('Error adding event for yard item: ' . $yardItemId, ['error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Événement créé avec succès pour ' . count($eventIds) . ' élément(s)',
                'eventIds' => $eventIds
            ]);
        }
        catch(Exception $exp)
        {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $exp->getMessage()
            ], 500);
        }
    }

    /**
     * Get all custom users (for admin/backoffice)
     * Calls stored procedure GetAllCustomUsers
     */
    public function GetAllCustomUsers(Request $request)
    {
        try {
            $result = DB::select(
                "CALL GetAllCustomUsers()"
            );

            return response()->json($result);
        }
        catch(Exception $exp) {
            throw $exp;
        }
    }

    /**
     * Get all consignees (customers) having BLs
     * Excludes deleted users (status ID: 5)
     * Returns consignee data for multi-select
     */
    public function GetAllConsigneesWithBLs(Request $request)
    {
        try {
            $result = DB::select(
                "CALL GetAllConsigneesWithBLs()"
            );

            return response()->json($result);
        }
        catch(Exception $exp) {
            throw $exp;
        }
    }

    /**
     * Update customer user status (activate/deactivate)
     * Updates the CustomerUsersStatusId field
     */
    public function UpdateCustomUserStatus(Request $request)
    {
        try {
            $userId = $request->input('UserId');
            $statusId = $request->input('StatusId');

            if (!$userId || !$statusId) {
                return response()->json(['error' => 'UserId and StatusId are required'], 400);
            }

            $result = DB::select(
                "CALL UpdateCustomUserStatus(?, ?)",
                [$userId, $statusId]
            );

            return response()->json(['success' => true, 'result' => $result]);
        }
        catch(Exception $exp) {
            return response()->json(['error' => $exp->getMessage()], 500);
        }
    }

    /**
     * Update customer user third-party codes
     * Manages the customerusers_thirdparty relationship
     */
    public function UpdateCustomUserThirdPartyCodes(Request $request)
    {
        try {
            $userId = $request->input('UserId');
            $thirdPartyCodes = $request->input('ThirdPartyCodes', []);

            if (!$userId) {
                return response()->json(['error' => 'UserId is required'], 400);
            }

            // Convert array to JSON for the stored procedure
            $codesJson = json_encode(array_values($thirdPartyCodes));

            $result = DB::select(
                "CALL UpdateCustomUserThirdPartyCodes(?, ?)",
                [$userId, $codesJson]
            );

            return response()->json(['success' => true, 'result' => $result]);
        }
        catch(Exception $exp) {
            return response()->json(['error' => $exp->getMessage()], 500);
        }
    }

    /**
     * Update customer user information
     * Updates personal and company information
     */
    public function UpdateCustomUserInfo(Request $request)
    {
        try {
            $userId = $request->input('UserId');
            $firstName = $request->input('FirstName');
            $lastName = $request->input('LastName');
            $phoneNumber = $request->input('PhoneNumber');
            $cellPhone = $request->input('CellPhone');
            $companyName = $request->input('CompanyName');
            $companyAddress = $request->input('CompanyAddress');
            $accountType = $request->input('AccountType', 1);

            if (!$userId) {
                return response()->json(['error' => 'UserId is required'], 400);
            }

            $result = DB::select(
                "CALL UpdateCustomUserInfo(?, ?, ?, ?, ?, ?, ?, ?)",
                [$userId, $firstName, $lastName, $phoneNumber, $cellPhone, $companyName, $companyAddress, $accountType]
            );

            return response()->json(['success' => true, 'result' => $result]);
        }
        catch(Exception $exp) {
            return response()->json(['error' => $exp->getMessage()], 500);
        }
    }

}
