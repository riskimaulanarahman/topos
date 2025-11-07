<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::orderBy('name')->paginate(20);
        return view('pages.units.index', compact('units'));
    }

    public function create()
    {
        return view('pages.units.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required','string','max:50','unique:units,code'],
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string','max:255'],
        ]);

        Unit::create($data);

        return redirect()->route('units.index')->with('success', 'Satuan ditambahkan');
    }

    public function edit(Unit $unit)
    {
        return view('pages.units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'code' => ['required','string','max:50','unique:units,code,'.$unit->id],
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string','max:255'],
        ]);

        $unit->update($data);

        return redirect()->route('units.index')->with('success', 'Satuan diperbarui');
    }

    public function destroy(Unit $unit)
    {
        $inUse = \App\Models\RawMaterial::where('unit', $unit->code)->exists();
        if ($inUse) {
            return redirect()->route('units.index')->with('error', 'Satuan tidak dapat dihapus karena masih digunakan oleh bahan baku.');
        }
        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Satuan dihapus');
    }
}
