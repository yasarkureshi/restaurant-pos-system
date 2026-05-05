<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function process(Request $request, Order $order)
    {
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);

        $validated = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,upi,wallet,credit,debit,net_banking,food_coupon,loyalty_points,other',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.cash_tendered' => 'nullable|numeric',
            'payments.*.tendered_amount' => 'nullable|numeric',
            'payments.*.upi_id' => 'nullable|string',
            'payments.*.upi_transaction_id' => 'nullable|string',
            'payments.*.transaction_id' => 'nullable|string',
            'payments.*.transaction_reference' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $order, $request) {
            $totalPaid = 0;

            foreach ($validated['payments'] as $paymentData) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'restaurant_id' => $order->restaurant_id,
                    'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
                    'amount' => $paymentData['amount'],
                    'payment_method' => $paymentData['method'],
                    'upi_id' => $paymentData['upi_id'] ?? null,
                    'upi_transaction_id' => $paymentData['upi_transaction_id'] ?? $paymentData['transaction_reference'] ?? null,
                    'transaction_id' => $paymentData['transaction_id'] ?? $paymentData['transaction_reference'] ?? null,
                    'cash_tendered' => $paymentData['cash_tendered'] ?? $paymentData['tendered_amount'] ?? null,
                    'cash_change' => isset($paymentData['tendered_amount']) || isset($paymentData['cash_tendered'])
                        ? ($paymentData['tendered_amount'] ?? $paymentData['cash_tendered']) - $paymentData['amount']
                        : null,
                    'status' => 'completed',
                    'processed_by' => $request->user()->id,
                ]);
                $totalPaid += $paymentData['amount'];
            }

            $order->paid_amount += $totalPaid;
            $order->balance_amount = max(0, $order->grand_total - $order->paid_amount);
            $order->updatePaymentStatus();

            // Use float comparison to avoid decimal precision issues
            $isFullyPaid = (float) $order->paid_amount >= (float) $order->grand_total;

            if ($isFullyPaid) {
                if ($order->status !== 'completed') {
                    $order->status = 'completed';
                    $order->save();
                }
                // Always free the table on full payment
                if ($order->table_id) {
                    \App\Models\Table::where('id', $order->table_id)->update([
                        'status' => 'available',
                        'current_order_id' => null,
                    ]);
                }
            }

            $invoice = $this->generateInvoice($order);

            return response()->json([
                'order' => $order->load(['payments', 'items']),
                'invoice' => $invoice,
                'change' => max(0, $totalPaid - $order->grand_total),
            ]);
        });
    }

    public function refund(Request $request, Payment $payment)
    {
        $order = $payment->order;
        abort_if($order->restaurant_id !== $request->user()->restaurant_id, 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payment->amount,
            'reason' => 'required|string',
        ]);

        $payment->update([
            'status' => 'refunded',
            'refund_amount' => $validated['amount'],
            'refund_reason' => $validated['reason'],
        ]);

        $order->paid_amount -= $validated['amount'];
        $order->balance_amount += $validated['amount'];
        $order->save();
        $order->updatePaymentStatus();

        return response()->json(['payment' => $payment, 'order' => $order]);
    }

    private function generateInvoice(Order $order): Invoice
    {
        if ($order->invoice) return $order->invoice;

        $cgstTotal = 0;
        $sgstTotal = 0;
        foreach ($order->items as $item) {
            $taxAmt = $item->tax_amount;
            $cgstTotal += $taxAmt / 2;
            $sgstTotal += $taxAmt / 2;
        }

        return Invoice::create([
            'order_id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'invoice_number' => 'INV-' . date('ymd') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'invoice_type' => 'tax_invoice',
            'customer_name' => $order->customer?->name ?? $order->customer_name ?? 'Walk-in Customer',
            'sub_total' => $order->sub_total,
            'cgst_amount' => round($cgstTotal, 2),
            'sgst_amount' => round($sgstTotal, 2),
            'discount_amount' => $order->discount_amount,
            'grand_total' => $order->grand_total,
            'status' => 'active',
        ]);
    }
}
