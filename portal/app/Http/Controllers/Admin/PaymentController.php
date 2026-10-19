<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddonRequest;
use App\Models\Payment;
use App\Services\Billing;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.payments', [
            'overdue' => Payment::overdue()->with('shop')->orderBy('due_at')->get(),
            'open' => Payment::with('shop')->where('status', 'open')->where(fn ($q) => $q->whereNull('due_at')->orWhere('due_at', '>=', now()))->get(),
            'paid' => Payment::with('shop')->where('status', 'paid')->latest('paid_at')->limit(50)->get(),
            'paidThisMonth' => Payment::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount_paid_pence'),
            'addons' => AddonRequest::with('shop')->where('status', 'requested')->oldest()->get(),
        ]);
    }

    public function invoiceAddon(AddonRequest $addon, Billing $billing)
    {
        abort_unless($addon->status === 'requested', 409);
        if (! $addon->shop->stripe_customer_id) {
            return back()->with('error', 'This shop has no Stripe customer yet, so there\'s no invoice to add it to.');
        }

        try {
            $id = $billing->addInvoiceItem($addon->shop, $addon->name(), $addon->price_pence);
        } catch (Throwable $e) {
            Log::error('Add-on invoice item failed: '.$e->getMessage());

            return back()->with('error', 'Stripe didn\'t accept that just now. Try again shortly.');
        }

        $addon->update(['status' => 'invoiced', 'stripe_invoice_item_id' => $id]);

        return back()->with('status', $addon->name().' billed to '.$addon->shop->name.'\'s card.');
    }
}
