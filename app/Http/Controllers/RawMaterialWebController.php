<?php

namespace App\Http\Controllers;

use App\Http\Requests\RawMaterialStoreRequest;
use App\Http\Requests\RawMaterialUpdateRequest;
use App\Models\Category;
use App\Models\Expense;
use App\Models\RawMaterial;
use App\Models\RawMaterialMovement;
use App\Models\RawMaterialTransfer;
use App\Models\Unit;
use App\Services\InventoryService;
use App\Services\PartnerCategoryAccessService;
use App\Services\RawMaterialStockSummaryService;
use App\Support\OutletContext;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RawMaterialWebController extends Controller
{
    public function index(Request $request)
    {
        $q = RawMaterial::query()
            ->accessibleBy($request->user())
            ->with('categories:id,name');
        if ($request->filled('search')) {
            $s = $request->input('search');
            $q->where(function($qq) use ($s){
                $qq->where('name','like',"%$s%")
                   ->orWhere('sku','like',"%$s%");
            });
        }
        $materials = $q->orderBy('name')->paginate(15);
        return view('pages.raw_materials.index', compact('materials'));
    }

    public function create()
    {
        $nameOptions = $this->expenseNameOptions();
        $units = Unit::orderBy('name')->get();
        $categories = $this->categoryOptionsForCurrentUser();

        return view('pages.raw_materials.create', compact('nameOptions','units','categories'));
    }

    public function store(RawMaterialStoreRequest $request)
    {
        $this->ensureCanManageStock();

        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);
        $data['stock_qty'] = 0;
        $data['unit_cost'] = 0;
        $data['min_stock'] = $data['min_stock'] ?? null;
        if (! array_key_exists('unit', $data)) {
            $data['unit'] = $request->input('unit');
        }

        $material = RawMaterial::create($data);
        if (! empty($categoryIds)) {
            $material->categories()->sync($categoryIds);
        }

        return redirect()->route('raw-materials.index')->with('success','Bahan dibuat');
    }

    public function edit(RawMaterial $raw_material)
    {
        $nameOptions = $this->expenseNameOptions();
        $units = Unit::orderBy('name')->get();
        $categories = $this->categoryOptionsForCurrentUser();
        $raw_material->load('categories:id,name');
        $selectedCategories = $raw_material->categories->pluck('id')->values()->all();

        return view('pages.raw_materials.edit', [
            'material' => $raw_material,
            'nameOptions' => $nameOptions,
            'units' => $units,
            'categories' => $categories,
            'selectedCategories' => $selectedCategories,
        ]);
    }

    public function update(RawMaterialUpdateRequest $request, RawMaterial $raw_material)
    {
        $this->ensureCanManageStock();

        $data = $request->validated();
        $categoryIds = array_key_exists('category_ids', $data) ? $data['category_ids'] : null;
        unset($data['category_ids']);

        if (! array_key_exists('min_stock', $data)) {
            $data['min_stock'] = $raw_material->min_stock;
        }

        $raw_material->update($data);
        if (is_array($categoryIds)) {
            $raw_material->categories()->sync($categoryIds);
        }

        return redirect()->route('raw-materials.index')->with('success','Bahan diperbarui');
    }

    public function adjustForm(RawMaterial $raw_material)
    {
        $lastMovement = RawMaterialMovement::where('raw_material_id', $raw_material->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();

        $adjustHistory = RawMaterialMovement::where('raw_material_id', $raw_material->id)
            ->where('type', 'adjustment')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $transferHistory = RawMaterialTransfer::query()
            ->where(function (Builder $query) use ($raw_material) {
                $query->where('raw_material_from_id', $raw_material->id)
                    ->orWhere('raw_material_to_id', $raw_material->id);
            })
            ->with(['sourceOutlet:id,name', 'targetOutlet:id,name'])
            ->latest('transferred_at')
            ->latest('id')
            ->limit(25)
            ->get();

        return view('pages.raw_materials.adjust_stock', [
            'material' => $raw_material,
            'lastMovement' => $lastMovement,
            'adjustHistory' => $adjustHistory,
            'transferHistory' => $transferHistory,
        ]);
    }

    public function adjust(Request $request, RawMaterial $raw_material, InventoryService $inventory)
    {
        $this->ensureCanManageStock();

        $data = $request->validate([
            'counted_qty' => ['nullable','numeric','min:0'],
            'qty_change' => ['nullable','numeric','not_in:0','required_without:counted_qty'],
            'notes' => ['nullable','string'],
            'adjustment_reason' => ['required','in:stock_opname,damage,loss,other'],
        ]);

        $countedQty = array_key_exists('counted_qty', $data) ? $data['counted_qty'] : null;
        if ($countedQty !== null) {
            $countedQty = round((float) $countedQty, 1);
            $qtyChange = round($countedQty - (float) $raw_material->stock_qty, 1);
            if (abs($qtyChange) < 0.05) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'counted_qty' => 'Tidak ada selisih antara stok sistem dan hasil perhitungan fisik.',
                    ]);
            }
        } else {
            $qtyChange = round((float) ($data['qty_change'] ?? 0), 1);
            if (abs($qtyChange) < 0.05) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'qty_change' => 'Nilai perubahan stok terlalu kecil setelah pembulatan 1 desimal.',
                    ]);
            }
        }

        $reasonLabels = [
            'stock_opname' => 'Stok Opname',
            'damage' => 'Koreksi Barang Rusak',
            'loss' => 'Koreksi Barang Hilang',
            'other' => 'Penyesuaian Lainnya',
        ];

        $notes = $data['notes'] ?? null;
        $inventory->adjustStock(
            $raw_material,
            (float) $qtyChange,
            'adjustment',
            null,
            'manual_adjustment',
            $raw_material->id,
            $notes,
            null,
            $countedQty !== null ? round($countedQty, 1) : null,
            $data['adjustment_reason']
        );
        return redirect()->route('raw-materials.index')->with('success','Stok diperbarui');
    }

    public function destroy(RawMaterial $raw_material)
    {
        $this->ensureCanManageStock();

        if ($raw_material->expenseItems()->exists()) {
            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Bahan tidak dapat dihapus karena sudah terhubung dengan detail pengeluaran.'
                ], 422);
            }
            return redirect()->route('raw-materials.index')->with('error', 'Bahan tidak dapat dihapus karena sudah terhubung dengan detail pengeluaran.');
        }

        if ($raw_material->recipeItems()->exists()) {
            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Bahan tidak dapat dihapus karena dipakai pada resep produk.'
                ], 422);
            }
            return redirect()->route('raw-materials.index')->with('error', 'Bahan tidak dapat dihapus karena dipakai pada resep produk.');
        }

        try {
            $raw_material->delete();
        } catch (QueryException $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Bahan tidak dapat dihapus saat ini.'
                ], 422);
            }
            return redirect()->route('raw-materials.index')->with('error', 'Bahan tidak dapat dihapus saat ini.');
        }

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Bahan dihapus.']);
        }

        return redirect()->route('raw-materials.index')->with('success', 'Bahan dihapus.');
    }

    private function ensureCanManageStock(): void
    {
        $role = OutletContext::currentRole();

        if (! $role) {
            abort(403, 'Outlet aktif tidak ditemukan.');
        }

        if ($role->role === 'owner') {
            return;
        }

        if (! $role->can_manage_stock) {
            abort(403, 'Anda tidak memiliki izin untuk mengelola stok.');
        }
    }

    private function expenseNameOptions()
    {
        $query = Expense::query()->whereNotNull('vendor');
        if (auth()->check() && auth()->user()->roles !== 'admin') {
            $query->where('created_by', auth()->id());
        }

        $vendors = $query
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(100)
            ->pluck('vendor');

        $notes = Expense::query()
            ->when(auth()->check() && auth()->user()->roles !== 'admin', function ($q) {
                $q->where('created_by', auth()->id());
            })
            ->whereNotNull('notes')
            ->select('notes')
            ->distinct()
            ->orderBy('notes')
            ->limit(100)
            ->pluck('notes');

        return $vendors->merge($notes)
            ->filter()
            ->unique()
            ->values();
    }

    public function sendStockAlert(Request $request, RawMaterialStockSummaryService $stockSummaryService)
    {
        Gate::authorize('inventory.manage');

        try {
            $result = $stockSummaryService->sendSummary(null, null, null, null, null);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Gagal mengirim ringkasan stok.',
            ], 500);
        }

        $recipientCount = count($result['sent_to']);

        return response()->json([
            'message' => $recipientCount > 0
                ? 'Ringkasan stok telah dikirim kepada ' . $recipientCount . ' penerima.'
                : 'Ringkasan stok telah dibuat.',
            'sent_to' => $result['sent_to'],
            'summary' => $result['summary'],
        ]);
    }

    private function categoryOptionsForCurrentUser(): Collection
    {
        $query = Category::query()->orderBy('name');

        $outlet = OutletContext::currentOutlet();
        $role = OutletContext::currentRole();
        $user = auth()->user();

        if ($role && $role->role === 'partner' && $outlet && $user) {
            /** @var PartnerCategoryAccessService $access */
            $access = app(PartnerCategoryAccessService::class);
            $categoryIds = $access->accessibleCategoryIdsFor($user, $outlet);

            if ($categoryIds === ['*']) {
                return $query->get();
            }

            if (empty($categoryIds)) {
                return collect();
            }

            $query->whereIn('id', $categoryIds);
        }

        return $query->get();
    }
}
