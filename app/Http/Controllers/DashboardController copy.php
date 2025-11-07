<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){
        $users = User::count();
        $products = Product::count();
        $ordersLength = Order::count();
        $categories = Category::count();
        $discounts= Discount::count();
        $additional_charges = \App\Models\AdditionalCharges::count();
        $orders = Order::with('user')->whereDate('created_at', Carbon::today())->orderBy('created_at', 'DESC')->paginate(10);
        $totalPriceToday = Order::whereDate('created_at', Carbon::today())->sum('total_price');
        $month = date('m');
        $year = date('Y');

        $data = $this->getMonthlyData($month, $year);
        return view('pages.dashboard', compact('users', 'products', 'ordersLength', 'categories', 'discounts', 'additional_charges', 'orders', 'totalPriceToday', 'data', 'month', 'year'));
    }

    public function filter(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->input('month');
        $year = $request->input('year');

        $users = User::count();
        $products = Product::count();
        $ordersLength = Order::count();
        $categories = Category::count();
        $discounts= Discount::count();
        $additional_charges = \App\Models\AdditionalCharges::count();
        $orders = Order::with('kasir')->whereDate('created_at', Carbon::today())->orderBy('created_at', 'DESC')->paginate(10);
        $totalPriceToday = Order::whereDate('created_at', Carbon::today())->sum('total_price');

        $data = $this->getMonthlyData($month, $year);
        // dd($data, $month, $year);
        return view('pages.dashboard', compact('users', 'products', 'ordersLength', 'categories', 'discounts', 'additional_charges', 'orders', 'totalPriceToday', 'data', 'month', 'year'));


    }

    private function getMonthlyData($month, $year)
    {
        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;

        $dailyData = array_fill(1, $daysInMonth, 0);

        $totalPriceDaily = Order::selectRaw('DAY(created_at) as day, SUM(total_price) as total_price')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupByRaw('DAY(created_at)')
            ->get();

        foreach ($totalPriceDaily as $data) {
            $dailyData[$data->day] = $data->total_price;
        }

        return $dailyData;
    }
}
