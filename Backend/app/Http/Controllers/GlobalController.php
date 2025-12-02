<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                "CALL GetInvoicesPerBLNumber(?)",
                array(
                    $request->input('blNumber')
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
}
