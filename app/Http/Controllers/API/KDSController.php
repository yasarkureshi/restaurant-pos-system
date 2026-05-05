<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class KDSController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['placed', 'confirmed', 'preparing'])
            ->with(['table', 'items' => function ($q) {
                $q->whereIn('item_status', ['pending', 'preparing'])
                  ->with('product');
            }])
            ->orderBy('created_at')
            ->get()
            ->filter(fn($order) => $order->items->isNotEmpty());

        return response()->json(['orders' => $orders->values()]);
    }

    public function updateItemStatus(Request $request, OrderItem $item)
    {
        $order = $item->order;
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);

        $validated = $request->validate([
            'status' => 'required|in:preparing,ready,served',
        ]);

        $updates = ['item_status' => $validated['status']];
        if ($validated['status'] === 'preparing') $updates['preparation_started_at'] = now();
        if ($validated['status'] === 'ready') {
            $updates['prepared_at'] = now();
            $updates['prepared_by'] = $request->user()->id;
            $prepTime = $item->preparation_started_at
                ? now()->diffInMinutes($item->preparation_started_at) : null;
            $updates['actual_preparation_time'] = $prepTime;
        }

        $item->update($updates);

        $pendingItems = $order->items()->whereIn('item_status', ['pending', 'preparing'])->count();
        if ($pendingItems === 0 && $order->status === 'preparing') {
            $order->update(['status' => 'ready', 'ready_at' => now()]);
        } elseif ($validated['status'] === 'preparing' && $order->status === 'placed') {
            $order->update(['status' => 'preparing']);
        }

        return response()->json(['item' => $item, 'order_status' => $order->fresh()->status]);
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate(['status' => 'required|in:preparing,ready']);
        $order->update(['status' => $validated['status']]);
        if ($validated['status'] === 'preparing') {
            $order->items()->whereIn('item_status', ['pending'])->update(['item_status' => 'preparing', 'preparation_started_at' => now()]);
        }
        return response()->json(['order' => $order->load('items.product')]);
    }
}
