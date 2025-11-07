<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BarChartController extends Controller
{
    public function barChartProductSales()
    {
        // Replace this with your actual data retrieval logic
        $data = [
            'labels' => ['January', 'February', 'March', 'April', 'May'],
            'data' => [65, 59, 80, 81, 56],
        ];
        return view('pages.charts.index', compact('data'));
    }
}
