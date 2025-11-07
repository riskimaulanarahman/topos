<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncomeCategoryController extends Controller
{
    public function index()
    {
        Gate::authorize('finance.view');
        return response()->json(['data' => IncomeCategory::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Gate::authorize('finance.manage');
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'description' => ['nullable','string']
        ]);
        $cat = IncomeCategory::create($data);
        return response()->json(['message' => 'Created', 'data' => $cat], 201);
    }

    public function update(Request $request, IncomeCategory $income_category)
    {
        Gate::authorize('finance.manage');
        $data = $request->validate([
            'name' => ['sometimes','string','max:100'],
            'description' => ['nullable','string']
        ]);
        $income_category->update($data);
        return response()->json(['message' => 'Updated', 'data' => $income_category]);
    }

    public function destroy(IncomeCategory $income_category)
    {
        Gate::authorize('finance.manage');
        $income_category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

