<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'group_by' => 'in:day,week,month',
        ]);

        $groupBy = $validated['group_by'] ?? 'day';
        $dateFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $sales = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', '>=', $validated['from'])
            ->whereDate('created_at', '<=', $validated['to'])
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as period"),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(grand_total) as total_sales'),
                DB::raw('SUM(discount_amount) as total_discounts'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('AVG(grand_total) as avg_order_value'),
                DB::raw('SUM(CASE WHEN order_type = "dine_in" THEN 1 ELSE 0 END) as dine_in_count'),
                DB::raw('SUM(CASE WHEN order_type = "takeaway" THEN 1 ELSE 0 END) as takeaway_count'),
                DB::raw('SUM(CASE WHEN order_type = "delivery" THEN 1 ELSE 0 END) as delivery_count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $cancelledOrders = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', '>=', $validated['from'])
            ->whereDate('created_at', '<=', $validated['to'])
            ->where('status', 'cancelled')
            ->count();

        $paymentBreakdown = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereDate('payments.created_at', '>=', $validated['from'])
            ->whereDate('payments.created_at', '<=', $validated['to'])
            ->where('payments.status', 'completed')
            ->select('payments.payment_method', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payments.payment_method')
            ->get();

        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereDate('orders.created_at', '>=', $validated['from'])
            ->whereDate('orders.created_at', '<=', $validated['to'])
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'products.id', 'products.name', 'products.image',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.item_total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return response()->json([
            'sales' => $sales,
            'cancelled_orders' => $cancelledOrders,
            'payment_breakdown' => $paymentBreakdown,
            'top_products' => $topProducts,
            'summary' => [
                'total_sales' => $sales->sum('total_sales'),
                'total_orders' => $sales->sum('total_orders'),
                'total_discounts' => $sales->sum('total_discounts'),
                'total_tax' => $sales->sum('total_tax'),
                'avg_order_value' => $sales->avg('avg_order_value'),
            ],
        ]);
    }

    public function inventory(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $items = DB::table('inventory_items')
            ->where('restaurant_id', $restaurantId)
            ->select('*', DB::raw('(current_stock <= reorder_point) as is_low_stock'))
            ->orderByDesc('is_low_stock')
            ->orderBy('name')
            ->get();
        return response()->json(['items' => $items]);
    }
}
