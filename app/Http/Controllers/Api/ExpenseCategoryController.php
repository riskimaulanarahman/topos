<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        Gate::authorize('finance.view');
        return response()->json(['data' => ExpenseCategory::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Gate::authorize('finance.manage');
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'description' => ['nullable','string']
        ]);
        $cat = ExpenseCategory::create($data);
        return response()->json(['message' => 'Created', 'data' => $cat], 201);
    }

    public function update(Request $request, ExpenseCategory $expense_category)
    {
        Gate::authorize('finance.manage');
        $data = $request->validate([
            'name' => ['sometimes','string','max:100'],
            'description' => ['nullable','string']
        ]);
        $expense_category->update($data);
        return response()->json(['message' => 'Updated', 'data' => $expense_category]);
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        Gate::authorize('finance.manage');
        $expense_category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

