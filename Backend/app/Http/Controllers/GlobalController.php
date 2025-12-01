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
}
