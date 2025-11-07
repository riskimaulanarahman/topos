<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RawMaterial;
use App\Services\ExpenseService;
use App\Services\PartnerCategoryAccessService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpenseWebController extends Controller
{
    public function __construct(private ExpenseService $expenses)
    {
    }

    public function index(Request $request)
    {
        $role = OutletContext::currentRole();
        $canManageExpense = $this->currentUserCanManageExpense();

        $q = Expense::query()
            ->with(['category', 'items.rawMaterial'])
            ->orderByDesc('date');

        if ($role && $role->role !== 'owner' && ! $canManageExpense) {
            $q->where('created_by', auth()->id());
        }
        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('vendor')) {
            $q->where('vendor', 'like', '%' . $request->input('vendor') . '%');
        }
        $expenses = $q->paginate(10);
        $categories = ExpenseCategory::orderBy('name')->get();
        $vendorSuggestions = Expense::query()
            ->when($role && $role->role !== 'owner' && ! $canManageExpense, function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->whereNotNull('vendor')
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(50)
            ->pluck('vendor');
        return view('pages.expenses.index', compact('expenses','categories','vendorSuggestions'));
    }

    public function create()
    {
        $this->ensureCanManageExpense();

        $categories = ExpenseCategory::orderBy('name')->get();
        $materials = RawMaterial::query()
            ->accessibleBy(auth()->user())
            ->with('categories:id,name')
            ->orderBy('name')
            ->get();
        $vendorSuggestions = Expense::where('created_by', auth()->id())
            ->whereNotNull('vendor')
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(50)
            ->pluck('vendor');
        return view('pages.expenses.create', compact('categories','vendorSuggestions','materials'));
    }

    public function store(Request $request)
    {
        $this->ensureCanManageExpense();

        $data = $this->validateExpense($request);
        $items = $data['items'];
        unset($data['items'], $data['attachment'], $data['amount']);

        $data['reference_no'] = $this->generateExpenseRef($data['date']);
        $data['outlet_id'] = OutletContext::currentOutlet()?->id;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $expense = $this->expenses->create($data, $items, $request->file('attachment'));

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran ditambahkan');
    }

    public function edit(Expense $expense)
    {
        $this->ensureCanManageExpense();

        $categories = ExpenseCategory::orderBy('name')->get();
        $materials = RawMaterial::query()
            ->accessibleBy(auth()->user())
            ->with('categories:id,name')
            ->orderBy('name')
            ->get();
        $vendorSuggestions = Expense::where('created_by', auth()->id())
            ->whereNotNull('vendor')
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(50)
            ->pluck('vendor');
        $expense->loadMissing('items.rawMaterial.categories');
        return view('pages.expenses.edit', compact('expense','categories','vendorSuggestions','materials'));
    }

    public function update(Request $request, Expense $expense)
    {
        $this->ensureCanManageExpense();
        $data = $this->validateExpense($request, true);
        $items = $data['items'];
        unset($data['items'], $data['attachment'], $data['amount']);

        $data['updated_by'] = auth()->id();

        $this->expenses->update($expense, $data, $items, $request->file('attachment'));

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran diperbarui');
    }

    public function destroy(Expense $expense)
    {
        $this->ensureCanManageExpense();
        $this->expenses->delete($expense);
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran dihapus');
    }

    public function duplicate(Expense $expense, Request $request)
    {
        $this->ensureCanManageExpense();

        $today = now()->toDateString();
        $newRef = $this->generateExpenseRef($today);

        $payload = $expense->only(['date','category_id','vendor','notes']);
        $payload['date'] = $today;
        $payload['reference_no'] = $newRef;
        $payload['outlet_id'] = OutletContext::currentOutlet()?->id;
        $payload['created_by'] = auth()->id();
        $payload['updated_by'] = auth()->id();

        $items = $expense->items()->get()->map(function ($item) {
            return [
                'raw_material_id' => $item->raw_material_id,
                'description' => $item->description,
                'unit' => $item->unit,
                'qty' => $item->qty,
                'item_price' => $item->total_cost,
                'unit_cost' => $item->unit_cost,
                'notes' => $item->notes,
            ];
        })->toArray();

        $new = $this->expenses->create($payload, $items);
        $new->created_by = auth()->id();
        $new->updated_by = auth()->id();
        $new->save();

        return redirect()->route('expenses.edit', $new)->with('success', 'Pengeluaran berhasil diduplikat. Silakan periksa dan simpan.');
    }

    private function ensureCanManageExpense(): void
    {
        $role = OutletContext::currentRole();

        if (! $role) {
            abort(403, 'Outlet aktif tidak ditemukan.');
        }

        if ($this->currentUserCanManageExpense()) {
            return;
        }

        if ($role->role === 'partner') {
            abort(403, 'Anda perlu mendapatkan akses kategori dari owner sebelum mencatat pengeluaran.');
        }

        abort(403, 'Anda tidak memiliki izin untuk mengelola pengeluaran.');
    }

    private function currentUserCanManageExpense(): bool
    {
        $role = OutletContext::currentRole();
        $outlet = OutletContext::currentOutlet();
        $user = auth()->user();

        if (! $role || ! $user) {
            return false;
        }

        if ($role->role === 'owner' || $role->can_manage_expense) {
            return true;
        }

        if ($role->role !== 'partner' || ! $outlet) {
            return false;
        }

        /** @var PartnerCategoryAccessService $access */
        $access = app(PartnerCategoryAccessService::class);
        $categoryIds = $access->accessibleCategoryIdsFor($user, $outlet);

        return $categoryIds === ['*'] || ! empty($categoryIds);
    }

    private function generateExpenseRef($date): string
    {
        $ymd = Carbon::parse($date)->format('Ymd');
        $prefix = 'EXP-' . $ymd . '-';
        $last = Expense::where('reference_no', 'like', $prefix . '%')
            ->orderBy('reference_no', 'desc')
            ->value('reference_no');
        $n = 1;
        if ($last) {
            $n = (int) substr($last, -4) + 1;
        }
        $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        while (Expense::where('reference_no', $ref)->exists()) {
            $n++;
            $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        }
        return $ref;
    }

    private function validateExpense(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'date' => ['required','date'],
            'amount' => ['nullable','numeric','min:0'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string','max:255'],
            'notes' => ['nullable','string','max:1000'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120'],
            'items' => ['required','array','min:1'],
            'items.*.raw_material_id' => ['nullable','exists:raw_materials,id'],
            'items.*.description' => ['nullable','string','max:255'],
            'items.*.unit' => ['nullable','string','max:50'],
            'items.*.qty' => ['required','numeric','min:0.0001'],
            'items.*.item_price' => ['required','numeric','min:0'],
            'items.*.unit_cost' => ['nullable','numeric','min:0'],
            'items.*.notes' => ['nullable','string'],
        ];

        if ($isUpdate) {
            $rules['remove_attachment'] = ['nullable','boolean'];
        }

        $payload = $request->validate($rules);

        $items = $payload['items'] ?? [];
        $filtered = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $filtered[] = $item;
        }
        $payload['items'] = $filtered;

        return $payload;
    }
}
