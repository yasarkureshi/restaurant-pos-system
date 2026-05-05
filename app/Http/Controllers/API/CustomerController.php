<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $query = Customer::where('restaurant_id', $restaurantId)->latest();

        if ($request->has('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%");
            });
        }
        if ($request->has('type')) $query->where('customer_type', $request->type);

        return response()->json(['customers' => $query->paginate($request->integer('per_page', 20))]);
    }

    public function show(Request $request, Customer $customer)
    {
        abort_if($customer->restaurant_id !== $request->user()->restaurant_id, 403);
        return response()->json([
            'customer' => $customer->load(['wallet', 'addresses', 'orders' => fn($q) => $q->latest()->limit(10)]),
        ]);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,NULL,id,restaurant_id,' . $restaurantId,
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'anniversary_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable|string',
        ]);
        $validated['restaurant_id'] = $restaurantId;
        $customer = Customer::create($validated);
        $customer->wallet()->create(['restaurant_id' => $restaurantId]);
        return response()->json(['customer' => $customer->load('wallet')], 201);
    }

    public function update(Request $request, Customer $customer)
    {
        abort_if($customer->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'anniversary_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'customer_type' => 'in:new,regular,vip,blacklisted',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);
        $customer->update($validated);
        return response()->json(['customer' => $customer]);
    }

    public function findByPhone(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate(['phone' => 'required|string']);
        $customer = Customer::where('restaurant_id', $restaurantId)
            ->where('phone', $validated['phone'])
            ->with('wallet')
            ->first();
        return response()->json(['customer' => $customer]);
    }
}
