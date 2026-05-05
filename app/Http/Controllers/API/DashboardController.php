<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $todayOrders = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled');

        $yesterdayOrders = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $yesterday)
            ->where('status', '!=', 'cancelled');

        $todaySales = (clone $todayOrders)->sum('grand_total');
        $yesterdaySales = (clone $yesterdayOrders)->sum('grand_total');
        $todayCount = (clone $todayOrders)->count();
        $yesterdayCount = (clone $yesterdayOrders)->count();

        $monthSales = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', '>=', $monthStart)
            ->where('status', '!=', 'cancelled')
            ->sum('grand_total');

        $activeOrders = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['placed', 'confirmed', 'preparing', 'ready'])
            ->count();

        $availableTables = Table::where('restaurant_id', $restaurantId)
            ->where('status', 'available')
            ->where('is_active', true)
            ->count();

        $occupiedTables = Table::where('restaurant_id', $restaurantId)
            ->where('status', 'occupied')
            ->count();

        $totalTables = Table::where('restaurant_id', $restaurantId)->where('is_active', true)->count();

        $totalCustomers = Customer::where('restaurant_id', $restaurantId)->count();

        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereDate('orders.created_at', $today)
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.id', 'products.name', 'products.image',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.item_total) as total_revenue'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $recentOrders = Order::where('restaurant_id', $restaurantId)
            ->with(['table', 'createdBy'])
            ->latest()
            ->limit(10)
            ->get(['id', 'order_number', 'order_type', 'status', 'payment_status', 'grand_total', 'table_id', 'created_by', 'created_at']);

        $hourlySales = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('SUM(grand_total) as sales'), DB::raw('COUNT(*) as orders'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $paymentBreakdown = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereDate('payments.created_at', $today)
            ->where('payments.status', 'completed')
            ->select('payments.payment_method', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('payments.payment_method')
            ->get();

        return response()->json([
            'stats' => [
                'today_sales' => round($todaySales, 2),
                'yesterday_sales' => round($yesterdaySales, 2),
                'sales_growth' => $yesterdaySales > 0 ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1) : 0,
                'today_orders' => $todayCount,
                'yesterday_orders' => $yesterdayCount,
                'month_sales' => round($monthSales, 2),
                'active_orders' => $activeOrders,
                'available_tables' => $availableTables,
                'occupied_tables' => $occupiedTables,
                'total_tables' => $totalTables,
                'total_customers' => $totalCustomers,
                'avg_order_value' => $todayCount > 0 ? round($todaySales / $todayCount, 2) : 0,
            ],
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
            'hourly_sales' => $hourlySales,
            'payment_breakdown' => $paymentBreakdown,
        ]);
    }
}
