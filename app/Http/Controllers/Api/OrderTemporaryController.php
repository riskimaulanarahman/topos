<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderTemporaryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string',
            'status' => 'required|in:open,closed',
            'sub_total' => 'required',
            'discount' => 'required',
            'discount_amount' => 'required',
            'tax' => 'required',
            'service_charge' => 'required',
            'total_price' => 'required',
            'total_item' => 'required',
            'order_temporary_items' => 'required|array',
            'order_temporary_items.*.product_id' => 'required|exists:products,id',
            'order_temporary_items.*.quantity' => 'required|numeric',
            'order_temporary_items.*.total_price' => 'required|numeric',
        ]);


        $orderTemporary = \App\Models\OrderTemporary::create([
            'customer_name' => $request->customer_name,
            'status' => $request->status,
            'sub_total' => $request->sub_total,
            'discount' => $request->discount,
            'discount_amount' => $request->discount_amount,
            'tax' => $request->tax,
            'service_charge' => $request->service_charge,
            'total_price' => $request->total_price,
            'total_item' => $request->total_item
        ]);

        foreach ($request->order_temporary_items as $item) {
            \App\Models\OrderTemporaryItem::create([
                'order_temporary_id' => $orderTemporary->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price']
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Order Temporary Created'
        ], 201);
    }



    public function updateStatus(Request $request, $customer_name)
    {
        $request->validate([
            'status' => 'required|in:open,closed'
        ]);

        // get all order temporary by customer_name and status open
        $orderTemporary = \App\Models\OrderTemporary::where('customer_name', $customer_name)->where('status', 'open')->with('orderTemporaryItems.product')->get();
        if (!$orderTemporary) {
            return response()->json([
                'success' => false,
                'message' => 'Order Temporary Not Found'
            ], 404);
        }


        // $mergedOrder = [
        //     // 'id' => null,
        //     'customer_name' => $customer_name,
        //     'status' => 'closed',
        //     'sub_total' => 0,
        //     'discount' => 0,
        //     'discount_amount' => 0,
        //     'tax' => 0,
        //     'service_charge' => 0,
        //     'total_price' => 0,
        //     'total_item' => 0,
        //     'order_temporary_items' => []
        // ];

        // $itemMap = [];

        foreach ($orderTemporary as $order) {
            $order->status = 'closed';
            $order->save();

            // $mergedOrder['sub_total'] += $order->sub_total;
            // $mergedOrder['discount'] += $order->discount;
            // $mergedOrder['discount_amount'] += $order->discount_amount;
            // $mergedOrder['tax'] += $order->tax;
            // $mergedOrder['service_charge'] += $order->service_charge;
            // $mergedOrder['total_price'] += $order->total_price;
            // $mergedOrder['total_item'] += $order->total_item;

            // // Gabungkan item
            // foreach ($order->orderTemporaryItems as $item) {
            //     if (isset($itemMap[$item->product_id])) {
            //         $itemMap[$item->product_id]['quantity'] += $item->quantity;
            //         $itemMap[$item->product_id]['total_price'] += $item->total_price;
            //     } else {
            //         $itemMap[$item->product_id] = [
            //             // 'id' => $item->id,
            //             // 'order_temporary_id' => $mergedOrder['id'],
            //             'product_id' => $item->product_id,
            //             'quantity' => $item->quantity,
            //             'total_price' => $item->total_price,
            //             'product' => $item->product
            //             // 'created_at' => $item->created_at,
            //             // 'updated_at' => $item->updated_at
            //         ];
            //     }
            // }
        }
        // $mergedOrder['order_temporary_items'] = array_values($itemMap);
        return response()->json([
            'success' => true,
            'message' => 'Order Temporary Status Updated',
        ]);
    }

    // get order temporary by satatu open
    public function getOpenOrderTemporary()
    {
    $orders = \App\Models\OrderTemporary::where('status', 'open')->with('orderTemporaryItems.product')->get();

    $mergedOrders = [];

    foreach ($orders as $order) {
        $customerName = $order->customer_name;

        if (!isset($mergedOrders[$customerName])) {
            $mergedOrders[$customerName] = [
                'customer_name' => $customerName,
                'status' => 'open',
                'sub_total' => 0,
                'discount' => 0,
                'discount_amount' => 0,
                'tax' => 0,
                'service_charge' => 0,
                'total_price' => 0,
                'total_item' => 0,
                'order_temporary_items' => []
            ];
        }

        $mergedOrders[$customerName]['sub_total'] += $order->sub_total;
        $mergedOrders[$customerName]['discount'] += $order->discount;
        $mergedOrders[$customerName]['discount_amount'] += $order->discount_amount;
        $mergedOrders[$customerName]['tax'] += $order->tax;
        $mergedOrders[$customerName]['service_charge'] += $order->service_charge;
        $mergedOrders[$customerName]['total_price'] += $order->total_price;
        $mergedOrders[$customerName]['total_item'] += $order->total_item;

        foreach ($order->orderTemporaryItems as $item) {
            $productId = $item->product_id;
            if (isset($mergedOrders[$customerName]['order_temporary_items'][$productId])) {
                $mergedOrders[$customerName]['order_temporary_items'][$productId]['quantity'] += $item->quantity;
                $mergedOrders[$customerName]['order_temporary_items'][$productId]['total_price'] += $item->total_price;
            } else {
                $mergedOrders[$customerName]['order_temporary_items'][$productId] = [
                    'product_id' => $productId,
                    'quantity' => $item->quantity,
                    'total_price' => $item->total_price,
                    'product' => $item->product
                ];
            }
        }
    }

    foreach ($mergedOrders as &$order) {
        $order['order_temporary_items'] = array_values($order['order_temporary_items']);
    }

    return response()->json([
        'success' => true,
        'data' => array_values($mergedOrders)  // Ubah associative array menjadi indexed array
    ]);
    }

    // get order temporary by status open and customer_name all orderTemporaryitems
    public function getOpenOrderTemporaryWithItems($customer_name)
    {
    $orderTemporary = \App\Models\OrderTemporary::where('status', 'open')->where('customer_name', $customer_name)->with('orderTemporaryItems.product')->get();

    if ($orderTemporary->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Order Temporary Not Found'
        ], 404);
    }

    $mergedOrder = [
        'customer_name' => $customer_name,
        'status' => 'open',
        'sub_total' => 0,
        'discount' => 0,
        'discount_amount' => 0,
        'tax' => 0,
        'service_charge' => 0,
        'total_price' => 0,
        'total_item' => 0,
        'order_temporary_items' => []
    ];

    $itemMap = [];

    foreach ($orderTemporary as $order) {
        $mergedOrder['sub_total'] += $order->sub_total;
        $mergedOrder['discount'] += $order->discount;
        $mergedOrder['discount_amount'] += $order->discount_amount;
        $mergedOrder['tax'] += $order->tax;
        $mergedOrder['service_charge'] += $order->service_charge;
        $mergedOrder['total_price'] += $order->total_price;
        $mergedOrder['total_item'] += $order->total_item;


        foreach ($order->orderTemporaryItems as $item) {
            if (isset($itemMap[$item->product_id])) {

                $itemMap[$item->product_id]['quantity'] += $item->quantity;
                $itemMap[$item->product_id]['total_price'] += $item->total_price;
            } else {

                $itemMap[$item->product_id] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'total_price' => $item->total_price,
                    'product' => $item->product
                ];
            }
        }
    }

    // Convert map menjadi array
    $mergedOrder['order_temporary_items'] = array_values($itemMap);

    // Kembalikan response JSON dengan data yang sudah digabungkan
    return response()->json([
        'success' => true,
        'data' => $mergedOrder
    ]);
    }


}
