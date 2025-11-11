<?php

namespace App\Http\Controllers;

use App\Mail\PartnerCategoryRequestMail;
use App\Models\Category;
use App\Support\OutletContext;
use App\Services\PartnerCategoryAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->resolveOutletContext();

        $perPage = (int) $request->input('per_page', 10);
        $viewMode = $request->input('view_mode', 'tree'); // 'tree' or 'flat'

        if ($viewMode === 'tree') {
            // Get hierarchical tree structure
            $categories = Category::query()
                ->whereIn('user_id', $context['owner_user_ids'])
                ->when($this->shouldFilterCategories($context['accessible_category_ids']), function ($query) use ($context) {
                    $query->whereIn('id', $context['accessible_category_ids']);
                })
                ->when(($context['is_partner'] ?? false) && empty($context['accessible_category_ids']), function ($query) {
                    $query->whereRaw('0 = 1');
                })
                ->when($request->input('name'), function ($query, $name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->root()
                ->with(['children' => function ($query) use ($context) {
                    $query->whereIn('user_id', $context['owner_user_ids'])
                        ->when($this->shouldFilterCategories($context['accessible_category_ids']), function ($q) use ($context) {
                            $q->whereIn('id', $context['accessible_category_ids']);
                        })
                        ->with(['children' => function ($q) use ($context) {
                            $q->whereIn('user_id', $context['owner_user_ids'])
                                ->when($this->shouldFilterCategories($context['accessible_category_ids']), function ($query) use ($context) {
                                    $query->whereIn('id', $context['accessible_category_ids']);
                                });
                        }]);
                }])
                ->orderBy('name')
                ->get();
        } else {
            // Flat view (original behavior)
            $categories = Category::query()
                ->whereIn('user_id', $context['owner_user_ids'])
                ->when($this->shouldFilterCategories($context['accessible_category_ids']), function ($query) use ($context) {
                    $query->whereIn('id', $context['accessible_category_ids']);
                })
                ->when(($context['is_partner'] ?? false) && empty($context['accessible_category_ids']), function ($query) {
                    $query->whereRaw('0 = 1');
                })
                ->when($request->input('name'), function ($query, $name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        }

        return view('pages.categories.index', [
            'categories' => $categories,
            'canManageCategories' => $context['can_manage_categories'],
            'activeOutlet' => $context['outlet'],
            'viewMode' => $viewMode,
        ]);
    }


    public function create()
    {
        $context = $this->resolveOutletContext();

        if (! $context['can_manage_categories']) {
            return view('pages.categories.request', [
                'activeOutlet' => $context['outlet'],
            ]);
        }

        // Get parent categories for dropdown
        $parentCategories = Category::query()
            ->whereIn('user_id', $context['owner_user_ids'])
            ->when($this->shouldFilterCategories($context['accessible_category_ids']), function ($query) use ($context) {
                $query->whereIn('id', $context['accessible_category_ids']);
            })
            ->root()
            ->orderBy('name')
            ->get();

        return view('pages.categories.create', compact('parentCategories'));
    }


    public function store(Request $request)
    {
        $context = $this->resolveOutletContext();

        if (! $context['can_manage_categories']) {
            $this->handleCategoryRequest($request, $context);
            return redirect()->route('category.index')->with('success', 'Permintaan penambahan kategori telah dikirim ke owner outlet.');
        }

        $userId = auth()->id();

        $request->validate([
            'name' => [
                'required',
                'min:3',
                Rule::unique('categories')->where(function ($query) use ($userId, $context) {
                    return $query
                        ->where('user_id', $userId)
                        ->where('outlet_id', optional($context['outlet'])->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Validate parent category to prevent circular reference
        if ($request->filled('parent_id')) {
            $parentCategory = Category::find($request->parent_id);
            if (!$parentCategory || !$this->isValidParentCategory($request->parent_id, null, $context)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['parent_id' => 'Invalid parent category selected.']);
            }
        }
        $category = new Category();
        $category->name = $request->name;
        $category->user_id = $userId;
        $category->outlet_id = optional($context['outlet'])->id;
        $category->parent_id = $request->parent_id;
        
        if ($request->hasFile('image')) {
            $filename = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $filename);
            $category->image = $filename;
        }

        $category->save();
        return redirect()->route('category.index')->with('success', 'Category successfully created');
    }

    public function edit($id)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageCategories($context);

        $category = Category::findOrFail($id);
        
        // Get parent categories for dropdown (exclude current category and its descendants)
        $parentCategories = Category::getFlattenedList(null, '', $category->id);
        
        return view('pages.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, $id)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageCategories($context);

        $request->validate([
            'name' => [
                'required',
                'min:3',
                Rule::unique('categories', 'name')
                    ->ignore($id)
                    ->where(function ($query) use ($context) {
                        return $query
                            ->where('outlet_id', optional($context['outlet'])->id)
                            ->whereNull('deleted_at');
                    }),
            ],
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Validate parent category to prevent circular reference
        if ($request->filled('parent_id')) {
            if (!$this->isValidParentCategory($request->parent_id, $id, $context)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['parent_id' => 'Invalid parent category selected. Cannot create circular reference.']);
            }
        }
        $category = Category::findOrFail($id);

        $category->name = $request->name;
        $category->parent_id = $request->parent_id;
        
        if ($request->hasFile('image')) {
            Storage::delete('public/categories/' . $category->image);
            $filename = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $filename);
            $category->image = $filename;
        }
        $category->save();
        return redirect()->route('category.index')->with('success', 'Category successfully updated');
    }


    public function destroy($id)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageCategories($context);

        $category = Category::findOrFail($id);
        $category->delete();
        return redirect()->route('category.index')->with('success', 'Category successfully deleted');
    }

    public function requestStore(Request $request)
    {
        $context = $this->resolveOutletContext();

        if ($context['can_manage_categories']) {
            return redirect()->route('category.create');
        }

        $this->handleCategoryRequest($request, $context);

        return redirect()->route('category.index')->with('success', 'Permintaan penambahan kategori telah dikirim ke owner outlet.');
    }

    private function handleCategoryRequest(Request $request, array $context): void
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $outlet = $context['outlet'];
        $owners = $outlet?->owners ?? collect();

        if ($owners->isEmpty()) {
            return;
        }

        foreach ($owners as $owner) {
            if (! $owner->email) {
                continue;
            }

            Mail::to($owner->email)->send(new PartnerCategoryRequestMail(
                auth()->user(),
                $outlet,
                $validated
            ));
        }
    }

    private function resolveOutletContext($requestedUserId = null): array
    {
        $user = auth()->user();
        $activeOutlet = OutletContext::currentOutlet();
        $currentRole = OutletContext::currentRole();

        $ownerUserIds = [];
        if ($activeOutlet) {
            $ownerUserIds = $activeOutlet->owners()->pluck('users.id')->unique()->values()->all();
        }
        if (empty($ownerUserIds)) {
            $ownerUserIds = [$requestedUserId ?: $user?->id];
        }

        $accessibleCategoryIds = [];
        if ($currentRole && $currentRole->role === 'partner' && $activeOutlet) {
            /** @var PartnerCategoryAccessService $access */
            $access = app(PartnerCategoryAccessService::class);
            $cats = $access->accessibleCategoryIdsFor($user, $activeOutlet);
            $accessibleCategoryIds = $cats === ['*'] ? ['*'] : (array) $cats;
        }

        $isPartner = $currentRole && $currentRole->role === 'partner';
        $canManageCategories = $user?->roles === 'admin'
            || ($currentRole && $currentRole->role === 'owner');

        return [
            'outlet' => $activeOutlet,
            'owner_user_ids' => $ownerUserIds,
            'accessible_category_ids' => $accessibleCategoryIds,
            'is_partner' => $isPartner,
            'can_manage_categories' => $canManageCategories,
        ];
    }

    private function shouldFilterCategories(array $accessibleCategoryIds): bool
    {
        return ! empty($accessibleCategoryIds) && $accessibleCategoryIds !== ['*'];
    }

    private function ensureCanManageCategories(array $context): void
    {
        if (! $context['can_manage_categories']) {
            abort(403, 'Hanya owner outlet yang dapat mengelola kategori.');
        }
    }

    /**
     * Validate parent category to prevent circular reference and ensure proper access
     */
    private function isValidParentCategory($parentId, $excludeId = null, $context = null): bool
    {
        if (!$context) {
            $context = $this->resolveOutletContext();
        }

        $parentCategory = Category::find($parentId);
        
        // Check if parent exists and user has access
        if (!$parentCategory || !in_array($parentCategory->user_id, $context['owner_user_ids'])) {
            return false;
        }

        // Check if parent is accessible (for partners)
        if ($this->shouldFilterCategories($context['accessible_category_ids'])) {
            if (!in_array($parentId, $context['accessible_category_ids'])) {
                return false;
            }
        }

        // Check for circular reference
        if ($excludeId) {
            $category = Category::find($excludeId);
            if ($category && !$category->isValidParent($parentId)) {
                return false;
            }
        }

        return true;
    }
}
