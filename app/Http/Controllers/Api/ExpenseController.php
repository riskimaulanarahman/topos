<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseStoreRequest;
use App\Http\Requests\ExpenseUpdateRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenses)
    {
    }

    public function index(Request $request)
    {
        Gate::authorize('finance.view');

        $query = Expense::query()->with(['category', 'items.rawMaterial']);
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        $expenses = $query->orderBy('date','desc')->paginate($request->integer('page_size', 15));
        return ExpenseResource::collection($expenses);
    }

    public function show(Expense $expense)
    {
        Gate::authorize('finance.view');
        return new ExpenseResource($expense->load(['category', 'items.rawMaterial']));
    }

    public function store(ExpenseStoreRequest $request)
    {
        Gate::authorize('finance.manage');
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items'], $data['attachment'], $data['amount']);
        $data['reference_no'] = $this->generateRef('EXP');

        $expense = $this->expenses->create($data, $items, $request->file('attachment'));

        return (new ExpenseResource($expense))
            ->additional(['message' => 'Expense created']);
    }

    public function update(ExpenseUpdateRequest $request, Expense $expense)
    {
        Gate::authorize('finance.manage');
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items'], $data['attachment'], $data['amount']);

        $updated = $this->expenses->update($expense, $data, $items, $request->file('attachment'));

        return (new ExpenseResource($updated))
            ->additional(['message' => 'Expense updated']);
    }

    public function destroy(Expense $expense)
    {
        Gate::authorize('finance.manage');
        $this->expenses->delete($expense);
        return response()->json(['message' => 'Expense deleted']);
    }

    private function generateRef(string $prefix): string
    {
        $date = now()->format('Ymd');
        $count = Expense::whereDate('date', now()->toDateString())->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}
