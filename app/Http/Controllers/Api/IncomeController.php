<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncomeStoreRequest;
use App\Http\Requests\IncomeUpdateRequest;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('finance.view');

        $query = Income::query()->with(['category']);
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        $incomes = $query->orderBy('date','desc')->paginate($request->integer('page_size', 15));
        return IncomeResource::collection($incomes);
    }

    public function show(Income $income)
    {
        Gate::authorize('finance.view');
        return new IncomeResource($income->load('category'));
    }

    public function store(IncomeStoreRequest $request)
    {
        Gate::authorize('finance.manage');
        $data = $request->validated();
        $data['reference_no'] = $this->generateRef('INC');
        $income = Income::create($data);
        return (new IncomeResource($income->load('category')))
            ->additional(['message' => 'Income created']);
    }

    public function update(IncomeUpdateRequest $request, Income $income)
    {
        Gate::authorize('finance.manage');
        $income->update($request->validated());
        return (new IncomeResource($income->load('category')))
            ->additional(['message' => 'Income updated']);
    }

    public function destroy(Income $income)
    {
        Gate::authorize('finance.manage');
        $income->delete();
        return response()->json(['message' => 'Income deleted']);
    }

    private function generateRef(string $prefix): string
    {
        $date = now()->format('Ymd');
        $count = Income::whereDate('date', now()->toDateString())->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}

