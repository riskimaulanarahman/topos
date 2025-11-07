<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdditionalCharges;

class AdditionalChargeController extends Controller
{
    public function index(){
        $additionalCharges= AdditionalCharges::all();
        return response()->json([
            'status'=> 'success',
            'data'=>$additionalCharges
        ],200);
    }

    // public function store(Request $request){
    //     $request->validate([
    //         'name' => 'required',
    //         'type' => 'required|in:fixed,percentage',
    //         'value' => 'required|numeric',
    //     ]);
    //     $additionalCharges = new AdditionalCharges();
    //     $additionalCharges->name = $request->name;
    //     $additionalCharges->type = $request->type;
    //     $additionalCharges->value = $request->value;
    //     $additionalCharges->save();
    //     return response()->json([
    //         'status'=> 'success',
    //         'data'=>$additionalCharges
    //     ],200);
    // }

    public function update(Request $request, $id){
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric',
            'status' => 'required|in:active,inactive',
        ]);
        $additionalCharges = AdditionalCharges::findOrFail($id);
        $additionalCharges->name = $request->name;
        $additionalCharges->type = $request->type;
        $additionalCharges->value = $request->value;
        $additionalCharges->status = $request->status;
        $additionalCharges->save();
        return response()->json([
            'status'=> 'success',
            'data'=>$additionalCharges
        ],200);
    }

    // public function destroy($id){
    //     $additionalCharges = AdditionalCharges::findOrFail($id);
    //     $additionalCharges->delete();
    //     return response()->json([
    //         'status'=> 'success',
    //         'data'=>$additionalCharges
    //     ],200);
    // }
}
