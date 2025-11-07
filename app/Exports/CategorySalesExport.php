<?php

namespace App\Exports;

use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Schema;

class CategorySalesExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    protected $start;
    protected $end;
    protected $userIds = [];
    protected $status;
    protected $paymentMethod;
    protected $categoryId;
    protected $productId;
    protected $accessibleCategoryIds = [];

    public function forRange(string $start, string $end)
    {
        $this->start = $start;
        $this->end = $end;
        return $this;
    }

    public function withUsers(array $userIds)
    {
        $this->userIds = $userIds;
        return $this;
    }

    public function withFilters(?string $status, ?string $paymentMethod, $categoryId, $productId, array $accessibleCategoryIds = [])
    {
        $this->status = $status;
        $this->paymentMethod = $paymentMethod;
        $this->categoryId = $categoryId;
        $this->productId = $productId;
        $this->accessibleCategoryIds = $accessibleCategoryIds;
        return $this;
    }

    public function query()
    {
        if (! $this->hasCategoryColumn()) {
            return OrderItem::query()->whereRaw('1 = 0');
        }

        return OrderItem::select([
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total_price) as total_price'),
            ])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$this->start, $this->end])
            ->when(!empty($this->userIds), fn($q) => $q->whereIn('orders.user_id', $this->userIds))
            ->when($this->status, fn($q) => $q->where('orders.status', $this->status))
            ->when($this->paymentMethod, fn($q) => $q->where('orders.payment_method', $this->paymentMethod))
            ->when($this->categoryId, function($q){
                if (is_array($this->categoryId)) {
                    $ids = array_filter($this->categoryId, fn($v)=>$v!==null && $v!=='');
                    if (!empty($ids)) {
                        $q->whereIn('products.category_id', $ids);
                    }
                } else {
                    $q->where('products.category_id', $this->categoryId);
                }
            })
            ->when($this->shouldFilterCategories(), function ($q) {
                $q->whereIn('products.category_id', $this->accessibleCategoryIds);
            })
            ->when($this->productId, fn($q) => $q->where('order_items.product_id', $this->productId))
            ->groupBy('categories.name')
            ->orderByDesc('total_price');
    }

    public function map($row): array
    {
        static $i = 1;
        return [
            $i++,
            $row->category_name,
            $row->total_quantity,
            $row->total_price,
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Category',
            'Total Quantity',
            'Total Price',
        ];
    }

    private function shouldFilterCategories(): bool
    {
        return $this->hasCategoryColumn() && ! empty($this->accessibleCategoryIds) && $this->accessibleCategoryIds !== ['*'];
    }

    private function hasCategoryColumn(): bool
    {
        static $cached = null;
        if ($cached === null) {
            $cached = Schema::hasColumn('products', 'category_id');
        }

        return $cached;
    }
}
