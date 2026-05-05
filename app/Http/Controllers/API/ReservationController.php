<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TableReservation;
use App\Models\Table;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $query = TableReservation::where('restaurant_id', $restaurantId)
            ->with(['table'])
            ->orderBy('reservation_date')
            ->orderBy('reservation_time');

        if ($request->has('date')) {
            $query->whereDate('reservation_date', $request->date);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['reservations' => $query->get()]);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'nullable|string|max:20',
            'customer_email'   => 'nullable|email',
            'table_id'         => 'nullable|exists:tables,id',
            'number_of_guests' => 'required|integer|min:1',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required|date_format:H:i',
            'duration_minutes' => 'nullable|integer|min:30',
            'special_requests' => 'nullable|string',
            'occasion'         => 'nullable|string|max:100',
        ]);

        $validated['restaurant_id'] = $restaurantId;
        $validated['status'] = 'confirmed';
        $validated['created_by'] = $request->user()->id;

        $reservation = TableReservation::create($validated);

        // Mark table as reserved if assigned
        if (!empty($validated['table_id'])) {
            Table::where('id', $validated['table_id'])->update(['status' => 'reserved']);
        }

        return response()->json(['reservation' => $reservation->load('table')], 201);
    }

    public function update(Request $request, TableReservation $reservation)
    {
        abort_if($reservation->restaurant_id !== $request->user()->restaurant_id, 403);

        $validated = $request->validate([
            'status'           => 'sometimes|in:confirmed,seated,completed,cancelled,no_show',
            'customer_name'    => 'sometimes|string|max:255',
            'customer_phone'   => 'nullable|string|max:20',
            'number_of_guests' => 'sometimes|integer|min:1',
            'reservation_date' => 'sometimes|date',
            'reservation_time' => 'sometimes|date_format:H:i',
            'special_requests' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $reservation->update($validated);

        // If cancelled, free the table
        if (($validated['status'] ?? '') === 'cancelled' && $reservation->table_id) {
            Table::where('id', $reservation->table_id)->update(['status' => 'available']);
        }

        return response()->json(['reservation' => $reservation->load('table')]);
    }

    public function destroy(Request $request, TableReservation $reservation)
    {
        abort_if($reservation->restaurant_id !== $request->user()->restaurant_id, 403);

        if ($reservation->table_id) {
            Table::where('id', $reservation->table_id)->update(['status' => 'available']);
        }

        $reservation->delete();
        return response()->json(['message' => 'Reservation cancelled.']);
    }
}
