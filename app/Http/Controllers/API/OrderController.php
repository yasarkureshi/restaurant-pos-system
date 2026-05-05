<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['table', 'customer', 'createdBy', 'items'])
            ->latest();

        if ($request->has('status')) $query->where('status', $request->status);
        if ($request->has('payment_status')) $query->where('payment_status', $request->payment_status);
        if ($request->has('order_type')) $query->where('order_type', $request->order_type);
        if ($request->has('date')) $query->whereDate('created_at', $request->date);
        if ($request->boolean('active')) {
            $query->whereIn('status', ['placed', 'confirmed', 'preparing', 'ready', 'served']);
        }

        return response()->json(['orders' => $query->paginate($request->integer('per_page', 20))]);
    }

    public function show(Request $request, Order $order)
    {
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);
        return response()->json([
            'order' => $order->load(['table', 'customer', 'createdBy', 'waiter', 'items.product', 'items.variant', 'items.addons', 'payments', 'invoice']),
        ]);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'order_type' => 'required|in:dine_in,takeaway,delivery,online,catering,room_service',
            'table_id' => 'nullable|exists:tables,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'number_of_guests' => 'integer|min:1',
            'waiter_id' => 'nullable|exists:users,id',
            'special_instructions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.special_instructions' => 'nullable|string',
            'items.*.addons' => 'nullable|array',
            'items.*.addons.*.addon_id' => 'nullable|integer',
            'items.*.addons.*.addon_name' => 'required_with:items.*.addons|string',
            'items.*.addons.*.addon_price' => 'required_with:items.*.addons|numeric',
        ]);

        // Inventory pre-check before opening a transaction
        foreach ($validated['items'] as $itemData) {
            $product = Product::find($itemData['product_id']);
            if ($product && $product->track_inventory && $product->current_stock < $itemData['quantity']) {
                $available = (float) $product->current_stock;
                return response()->json([
                    'message' => "'{$product->name}' is out of stock. Available: {$available} {$product->unit_type}",
                ], 422);
            }
        }

        return DB::transaction(function () use ($validated, $restaurantId, $request) {
            $order = Order::create([
                'restaurant_id' => $restaurantId,
                'order_type' => $validated['order_type'],
                'table_id' => $validated['table_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'number_of_guests' => $validated['number_of_guests'] ?? 1,
                'waiter_id' => $validated['waiter_id'] ?? null,
                'special_instructions' => $validated['special_instructions'] ?? null,
                'status' => 'placed',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                $variantPrice = 0;
                $variantName = null;

                if (!empty($itemData['variant_id'])) {
                    $variant = $product->variants()->find($itemData['variant_id']);
                    if ($variant) {
                        $variantPrice = $variant->price_adjustment;
                        $variantName = $variant->name;
                    }
                }

                $addonsTotal = 0;
                $addonsList = $itemData['addons'] ?? [];
                foreach ($addonsList as $addon) {
                    $addonsTotal += $addon['addon_price'] * ($addon['quantity'] ?? 1);
                }

                $unitPrice = $product->current_price + $variantPrice + $addonsTotal;
                $taxRate = $product->taxCategory ? $product->taxCategory->tax_percentage : 0;
                $itemSubtotal = $unitPrice * $itemData['quantity'];
                $taxAmount = round($itemSubtotal * $taxRate / 100, 2);
                $itemTotal = $itemSubtotal;

                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $product->price,
                    'quantity' => $itemData['quantity'],
                    'unit_type' => $product->unit_type,
                    'variant_id' => $itemData['variant_id'] ?? null,
                    'variant_name' => $variantName,
                    'variant_price' => $variantPrice,
                    'addons_total' => $addonsTotal,
                    'unit_price' => $unitPrice,
                    'tax_percentage' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'item_total' => $itemTotal,
                    'special_instructions' => $itemData['special_instructions'] ?? null,
                    'estimated_preparation_time' => $product->preparation_time_minutes,
                ]);

                if (!empty($addonsList)) {
                    $orderItem->addons()->createMany($addonsList);
                }

                $product->deductStock($itemData['quantity']);
            }

            $order->calculateTotals()->save();

            if ($validated['table_id'] ?? null) {
                Table::where('id', $validated['table_id'])->update([
                    'status' => 'occupied',
                    'current_order_id' => $order->id,
                ]);
            }

            return response()->json([
                'order' => $order->load(['table', 'customer', 'items.product', 'items.addons', 'items.variant']),
            ], 201);
        });
    }

    public function addItems(Request $request, Order $order)
    {
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);
        if (!in_array($order->status, ['placed', 'confirmed', 'preparing'])) {
            return response()->json(['message' => 'Cannot add items to this order.'], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.special_instructions' => 'nullable|string',
            'items.*.addons' => 'nullable|array',
        ]);

        // Inventory pre-check
        foreach ($validated['items'] as $itemData) {
            $product = Product::find($itemData['product_id']);
            if ($product && $product->track_inventory && $product->current_stock < $itemData['quantity']) {
                $available = (float) $product->current_stock;
                return response()->json([
                    'message' => "'{$product->name}' is out of stock. Available: {$available} {$product->unit_type}",
                ], 422);
            }
        }

        return DB::transaction(function () use ($validated, $order) {
            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                $variantPrice = 0;
                $variantName = null;
                if (!empty($itemData['variant_id'])) {
                    $variant = $product->variants()->find($itemData['variant_id']);
                    if ($variant) { $variantPrice = $variant->price_adjustment; $variantName = $variant->name; }
                }
                $addonsTotal = collect($itemData['addons'] ?? [])->sum('addon_price');
                $unitPrice = $product->current_price + $variantPrice + $addonsTotal;
                $taxRate = $product->taxCategory ? $product->taxCategory->tax_percentage : 0;
                $itemSubtotal = $unitPrice * $itemData['quantity'];
                $taxAmount = round($itemSubtotal * $taxRate / 100, 2);

                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $product->price,
                    'quantity' => $itemData['quantity'],
                    'unit_type' => $product->unit_type,
                    'variant_id' => $itemData['variant_id'] ?? null,
                    'variant_name' => $variantName,
                    'variant_price' => $variantPrice,
                    'addons_total' => $addonsTotal,
                    'unit_price' => $unitPrice,
                    'tax_percentage' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'item_total' => $itemSubtotal,
                    'special_instructions' => $itemData['special_instructions'] ?? null,
                    'estimated_preparation_time' => $product->preparation_time_minutes,
                ]);

                if (!empty($itemData['addons'])) {
                    $orderItem->addons()->createMany($itemData['addons']);
                }

                $product->deductStock($itemData['quantity']);
            }

            $order->calculateTotals()->save();
            return response()->json(['order' => $order->load(['items.product', 'items.addons'])]);
        });
    }

    public function updateStatus(Request $request, Order $order)
    {
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'status' => 'required|in:placed,confirmed,preparing,ready,served,completed,cancelled',
            'cancellation_reason' => 'required_if:status,cancelled|nullable|string',
        ]);

        $statusTimestamps = [
            'confirmed' => 'confirmed_at',
            'preparing' => null,
            'ready' => 'ready_at',
            'served' => 'served_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];

        $updates = ['status' => $validated['status']];
        if (isset($statusTimestamps[$validated['status']])) {
            $field = $statusTimestamps[$validated['status']];
            if ($field) $updates[$field] = now();
        }

        if ($validated['status'] === 'cancelled') {
            $updates['cancellation_reason'] = $validated['cancellation_reason'] ?? null;
            $updates['cancelled_by'] = $request->user()->id;
            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['status' => 'available', 'current_order_id' => null]);
            }
        }

        if ($validated['status'] === 'completed') {
            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['status' => 'available', 'current_order_id' => null]);
            }
        }

        $order->update($updates);
        return response()->json(['order' => $order->load(['table', 'items', 'payments'])]);
    }

    public function applyDiscount(Request $request, Order $order)
    {
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'discount_type' => 'required|in:percentage,fixed,manual',
            'discount_amount' => 'required|numeric|min:0',
            'discount_reason' => 'nullable|string',
        ]);

        $order->update($validated);
        $order->calculateTotals()->save();
        return response()->json(['order' => $order->load('items')]);
    }

    public function destroy(Request $request, Order $order)
    {
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);
        if (!$order->canCancel()) {
            return response()->json(['message' => 'Order cannot be deleted in its current state.'], 422);
        }
        if ($order->table_id) {
            Table::where('id', $order->table_id)->update(['status' => 'available', 'current_order_id' => null]);
        }
        $order->delete();
        return response()->json(['message' => 'Order deleted.']);
    }
}
