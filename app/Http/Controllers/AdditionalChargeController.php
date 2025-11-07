<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AdditionalCharges;
class AdditionalChargeController extends Controller
{
    public function index( Request $request )
    {
        $additionalCharges= DB::table('additional_charges')->when($request->input('name'), function ($query, $name) {
            $query->where('name', 'like', '%' . $name . '%');
        })->orderBy('created_at', 'desc')->paginate(10);
        return view('pages.additional_charges.index', compact('additionalCharges'));
    }


    public function create()
    {
        return view('pages.additional_charges.create');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric',
        ]);
        $additionalCharges = new AdditionalCharges();
        $additionalCharges->name = $request->name;
        $additionalCharges->type = $request->type;
        $additionalCharges->value = $request->value;
        $additionalCharges->save();
        return redirect()->route('additional_charge.index')->with('success', 'Additional charge created successfully');
    }


    public function edit($id)
    {
        $additionalCharge = \App\Models\AdditionalCharges::findOrFail($id);
        return view('pages.additional_charges.edit', compact('additionalCharge'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric',
        ]);
        $additionalCharges = \App\Models\AdditionalCharges::findOrFail($id);
        $additionalCharges->name = $request->name;
        $additionalCharges->type = $request->type;
        $additionalCharges->value = $request->value;
        $additionalCharges->save();
        return redirect()->route('additional_charge.index')->with('success', 'Additional charge updated successfully');
    }


    public function destroy($id)
    {
        $additionalCharges = \App\Models\AdditionalCharges::findOrFail($id);
        $additionalCharges->delete();
        return redirect()->route('additional_charge.index')->with('success', 'Additional charge deleted successfully');
    }
}
