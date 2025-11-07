<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogDuplicationRequest;
use App\Jobs\ProcessDuplicationJob;
use App\Models\DuplicationJob;
use App\Models\Outlet;
use App\Models\Category;
use App\Models\RawMaterial;
use App\Models\Product;
use App\Services\CatalogDuplicationService;
use App\Support\OutletContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogDuplicationController extends Controller
{
    public function __construct(private readonly CatalogDuplicationService $service)
    {
    }

    public function index(Request $request): View
    {
        $jobs = DuplicationJob::query()
            ->where('requested_by', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('pages.catalog_duplication.index', compact('jobs'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $ownedOutlets = $user->ownedOutlets()->orderBy('outlets.name')->get(['outlets.id', 'outlets.name']);
        $currentOutlet = OutletContext::currentOutlet();

        return view('pages.catalog_duplication.create', [
            'ownedOutlets' => $ownedOutlets,
            'currentOutlet' => $currentOutlet,
        ]);
    }

    public function sourceData(Request $request): JsonResponse
    {
        $data = $request->validate([
            'outlet_id' => ['required', 'integer'],
        ]);

        $outletId = (int) $data['outlet_id'];

        $ownsOutlet = $request->user()
            ->ownedOutlets()
            ->where('outlets.id', $outletId)
            ->exists();

        if (! $ownsOutlet) {
            abort(403, __('Anda tidak memiliki akses ke outlet tersebut.'));
        }

        $categories = Category::query()
            ->forOutlet($outletId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rawMaterials = RawMaterial::query()
            ->forOutlet($outletId)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'stock_qty']);

        $products = Product::query()
            ->forOutlet($outletId)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'category_id']);

        return response()->json([
            'categories' => $categories,
            'raw_materials' => $rawMaterials,
            'products' => $products,
        ]);
    }

    public function store(CatalogDuplicationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $sourceOutlet = Outlet::findOrFail($request->input('source_outlet_id'));
        $targetOutlet = Outlet::findOrFail($request->input('target_outlet_id'));

        $job = $this->service->createJob(
            $user,
            $sourceOutlet,
            $targetOutlet,
            $request->validated()['resources'],
            $request->validated()['options'] ?? []
        );

        ProcessDuplicationJob::dispatch($job);

        return redirect()
            ->route('catalog-duplication.jobs.show', $job)
            ->with('success', __('Proses duplikasi dimulai.'));
    }

    public function show(DuplicationJob $job): View
    {
        $this->authorize('view', $job);
        $job->load('items');

        return view('pages.catalog_duplication.show', compact('job'));
    }
}
