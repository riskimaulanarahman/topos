<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountProductRule;
use App\Models\Product;
use App\Support\OutletContext;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{

    public function index(Request $request)
    {
        $discounts = DB::table('discounts')->when($request->input('name'), function ($query, $name) {
            return $query->where('name', 'like', '%' . $name . '%');
        })->orderBy('created_at', 'desc')->paginate(10);

        return view('pages.discounts.index', compact('discounts'));
    }


    public function create(Request $request)
    {
        $selectedProductIds = collect($request->old('product_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $selectedProducts = $selectedProductIds->isEmpty()
            ? collect()
            : Product::whereIn('id', $selectedProductIds)
                ->orderBy('name')
                ->get(['id', 'name']);

        return view('pages.discounts.create', [
            'selectedProducts' => $selectedProducts,
            'selectedProductIds' => $selectedProductIds->all(),
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' =>  'required|min:3|unique:discounts,name',
            'description' =>  'required|min:3',
            'type' =>  'required|in:fixed,percentage',
            'value' =>  'required|numeric',
            'status' =>  'required|in:active,inactive',
            'expired_date' =>  'required|date',
            'scope' => ['required', Rule::in(['global', 'outlet', 'product'])],
            'auto_apply' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer'],
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $discounts = new Discount();
        $discounts->name = $validated['name'];
        $discounts->description = $validated['description'];
        $discounts->type = $validated['type'];
        $discounts->value = $validated['value'];
        $discounts->status = $validated['status'];
        $discounts->expired_date = $validated['expired_date'];
        $discounts->scope = $validated['scope'];
        $discounts->auto_apply = $request->boolean('auto_apply');
        $discounts->priority = $validated['priority'] ?? 0;
        $discounts->user_id = $request->user()?->id;
        $discounts->outlet_id = $validated['outlet_id'] ?? null;
        $discounts->save();

        $this->syncDiscountProducts($discounts, $validated);

        return redirect()->route('discount.index')->with('success', 'Discount successfully created');
    }


    public function edit(Request $request, $id)
    {
        $discount= Discount::with('productRules')->findOrFail($id);

        $requestedProductIds = collect($request->old('product_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id);

        $discountProductIds = $discount->productRules->pluck('product_id');

        $selectedProductIds = $requestedProductIds
            ->merge($discountProductIds)
            ->filter()
            ->unique()
            ->values();

        $selectedProducts = $selectedProductIds->isEmpty()
            ? collect()
            : Product::whereIn('id', $selectedProductIds)
                ->orderBy('name')
                ->get(['id', 'name']);

        return view('pages.discounts.edit', [
            'discount' => $discount,
            'selectedProducts' => $selectedProducts,
            'selectedProductIds' => $selectedProductIds->all(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' =>  'required|min:3|unique:discounts,name,' . $id,
            'description' =>  'required|min:3',
            'type' =>  'required|in:fixed,percentage',
            'value' =>  'required|numeric',
            'status' =>  'required|in:active,inactive',
            'expired_date' =>  'required|date',
            'scope' => ['required', Rule::in(['global', 'outlet', 'product'])],
            'auto_apply' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer'],
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);
        $discounts = Discount::findOrFail($id);
        $discounts->name = $validated['name'];
        $discounts->description = $validated['description'];
        $discounts->type = $validated['type'];
        $discounts->value = $validated['value'];
        $discounts->status = $validated['status'];
        $discounts->expired_date = $validated['expired_date'];
        $discounts->scope = $validated['scope'];
        $discounts->auto_apply = $request->boolean('auto_apply');
        $discounts->priority = $validated['priority'] ?? 0;
        $discounts->outlet_id = $validated['outlet_id'] ?? null;
        $discounts->save();

        $this->syncDiscountProducts($discounts, $validated);

        return redirect()->route('discount.index')->with('success', 'Discount successfully updated');
    }


    public function destroy($id)
    {
        $discounts = Discount::findOrFail($id);
        $discounts->delete();
        return redirect()->route('discount.index')->with('success', 'Discount successfully deleted');
    }

    protected function syncDiscountProducts(Discount $discount, array $validated): void
    {
        $productIds = collect($validated['product_ids'] ?? [])
            ->filter()
            ->unique()
            ->values();

        DiscountProductRule::where('discount_id', $discount->id)->delete();

        if ($productIds->isEmpty()) {
            return;
        }

        $payload = $productIds->map(function ($productId, $index) use ($discount) {
            return [
                'product_id' => $productId,
                'discount_id' => $discount->id,
                'outlet_id' => $discount->outlet_id,
                'type_override' => null,
                'value_override' => null,
                'auto_apply' => (bool) $discount->auto_apply,
                'priority' => $discount->priority ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        DiscountProductRule::insert($payload);
    }

    public function searchProducts(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Product::query()->select(['id', 'name'])->orderBy('name');

        if ($request->filled('q')) {
            $term = (string) $request->string('q')->trim();
            $query->where('name', 'like', '%' . $term . '%');
        }

        if ($userId = $request->user()?->id) {
            $query->where(function ($inner) use ($userId) {
                $inner->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            });
        }

        if ($currentOutlet = OutletContext::currentOutlet()) {
            $outletId = $currentOutlet->id;
            $query->where(function ($inner) use ($outletId) {
                $inner->whereNull('outlet_id')
                    ->orWhere('outlet_id', $outletId);
            });
        }

        $page = max((int) $request->input('page', 1), 1);
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0) {
            $perPage = 20;
        }

        $products = $query->paginate($perPage, ['id', 'name'], 'page', $page);

        $results = $products->getCollection()->map(fn ($product) => [
            'id' => $product->id,
            'text' => $product->name,
        ]);

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $products->hasMorePages(),
            ],
        ]);
    }
}
