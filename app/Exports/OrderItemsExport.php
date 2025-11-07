<?php

namespace App\Exports;

use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Schema;

class OrderItemsExport implements FromQuery, WithMapping, WithHeadings
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

    public function withFilters(?string $status, ?string $paymentMethod, ?string $categoryId, ?string $productId, array $accessibleCategoryIds = [])
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
        $query = OrderItem::select([
                DB::raw('DATE(orders.created_at) as order_date'),
                'orders.transaction_number',
                'products.name as product_name',
                $this->hasCategoryColumn() ? 'categories.name as category_name' : DB::raw('NULL as category_name'),
                'order_items.quantity',
                'order_items.total_price',
            ])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$this->start, $this->end])
            ->when(!empty($this->userIds), fn($q) => $q->whereIn('orders.user_id', $this->userIds))
            ->when($this->status, fn($q) => $q->where('orders.status', $this->status))
            ->when($this->paymentMethod, fn($q) => $q->where('orders.payment_method', $this->paymentMethod))
            ->when($this->productId, fn($q) => $q->where('order_items.product_id', $this->productId));

        if ($this->hasCategoryColumn()) {
            $query->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->when($this->categoryId, fn($q) => $q->where('products.category_id', $this->categoryId))
                ->when($this->shouldFilterCategories(), function ($q) {
                    $q->whereIn('products.category_id', $this->accessibleCategoryIds);
                });
        }

        return $query->orderBy('orders.created_at', 'desc');
    }

    public function map($row): array
    {
        return [
            $row->order_date,
            $row->transaction_number,
            $row->product_name,
            $row->category_name,
            $row->quantity,
            $row->total_price,
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            'Transaction No',
            'Product',
            'Category',
            'Quantity',
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
