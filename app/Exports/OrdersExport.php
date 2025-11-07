<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Schema;

class OrdersExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    protected $start;
    protected $end;
    protected $status;
    protected $paymentMethod;
    protected $categoryId;
    protected $productId;
    protected $userIds = [];
    protected $accessibleCategoryIds = [];

    public function forRange(string $start, string $end)
    {
        $this->start = $start;
        $this->end = $end;
        return $this;
    }

    public function withFilters($status, $paymentMethod, $categoryId, $productId, array $accessibleCategoryIds = [])
    {
        $this->status = $status;
        $this->paymentMethod = $paymentMethod;
        $this->categoryId = $categoryId;
        $this->productId = $productId;
        $this->accessibleCategoryIds = $accessibleCategoryIds;
        return $this;
    }

    public function withUsers(array $userIds)
    {
        $this->userIds = $userIds;
        return $this;
    }

    public function query()
    {
        return Order::query()
            ->with('user')
            ->whereBetween('created_at', [$this->start, $this->end])
            ->when(!empty($this->userIds), fn($q) => $q->whereIn('user_id', $this->userIds))
            ->when($this->status, function($q){
                if (is_array($this->status)) { $q->whereIn('status', $this->status); }
                else { $q->where('status', $this->status); }
            })
            ->when($this->paymentMethod, function($q){
                if (is_array($this->paymentMethod)) { $q->whereIn('payment_method', $this->paymentMethod); }
                else { $q->where('payment_method', $this->paymentMethod); }
            })
            ->when($this->hasCategoryColumn() && ($this->categoryId || $this->shouldFilterCategories()), function ($q) {
                $categoryId = $this->categoryId;
                $accessible = $this->accessibleCategoryIds;
                $q->whereExists(function ($sub) use ($categoryId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->whereColumn('order_items.order_id', 'orders.id');

                    if ($categoryId) {
                        if (is_array($categoryId)) {
                            $ids = array_filter($categoryId, fn($v) => $v !== null && $v !== '');
                            if (!empty($ids)) {
                                $sub->whereIn('products.category_id', $ids);
                            }
                        } else {
                            $sub->where('products.category_id', $categoryId);
                        }
                    }

                    if ($this->shouldFilterCategories()) {
                        $sub->whereIn('products.category_id', $accessible);
                    }
                });
            })
            ->when($this->productId, function ($q) {
                $productId = $this->productId;
                $q->whereExists(function ($sub) use ($productId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('order_items.product_id', $productId);
                });
            });
    }

    public function map($order): array
    {
        return [
            $order->transaction_time,
            $order->total_price,
            $order->total_item,
            optional($order->user)->name ?? ($order->cashier_name ?? '-')
        ];
    }

    public function headings(): array
    {
        return [
            'Transaction Time',
            'Total Price',
            'Total Item',
            'Kasir',
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
