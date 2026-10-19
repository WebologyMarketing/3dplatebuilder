<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $shops = Shop::all();
        $paying = $shops->filter->isPaying();
        $mrr = $paying->sum->monthlyPence();
        $waiting = $paying->filter(fn ($s) => ! $s->live_at);

        $monthStart = now()->startOfMonth();
        $setupThisMonth = $shops->filter(fn ($s) => $s->setup_paid_at && $s->setup_paid_at->gte($monthStart));

        $yearAgo = now()->subYear();
        $cancelled = $shops->filter(fn ($s) => $s->cancelled_at && $s->cancelled_at->gte($yearAgo))->count();
        $churnBase = $paying->count() + $cancelled;

        // Recurring income at the end of each of the last 12 months, rebuilt from when shops paid and cancelled.
        $history = collect(range(11, 0))->map(function ($ago) use ($shops) {
            $end = now()->subMonthsNoOverflow($ago)->endOfMonth();
            $end = $end->isFuture() ? now() : $end;
            $pence = $shops->filter(fn ($s) => $s->live_at && $s->live_at->lte($end)
                && (! $s->cancelled_at || $s->cancelled_at->gt($end)))
                ->sum(fn ($s) => $s->plan === 'outright' ? intdiv(config('portal.outright.yearly'), 12) : ($s->planConfig()['monthly'] ?? 0));

            return ['month' => $end->format('M'), 'pence' => $pence];
        });

        $overdue = Payment::overdue()->with('shop')->orderBy('due_at')->get();

        return view('admin.dashboard', [
            'mrr' => $mrr,
            'waitingPence' => $waiting->sum->planMonthlyPence(),
            'waitingCount' => $waiting->count(),
            'payingCount' => $paying->count(),
            'byPlan' => $paying->groupBy('plan')->map->count(),
            'setupPence' => $setupThisMonth->sum(fn ($s) => $s->planConfig()['setup'] ?? 0),
            'setupCount' => $setupThisMonth->count(),
            'overdue' => $overdue,
            'overduePence' => $overdue->sum('amount_pence'),
            'cancelled' => $cancelled,
            'churn' => $churnBase ? round($cancelled / $churnBase * 100) : 0,
            'history' => $history,
            'historyMax' => max(1, $history->max('pence')),
            'stages' => collect(Shop::STAGES)->map(fn ($label, $key) => ['label' => $label, 'count' => $shops->where('stage', $key)->count()]),
            'signupsThisMonth' => $shops->filter(fn ($s) => $s->created_at->gte($monthStart))->count(),
            'tickets' => Ticket::with('shop')->whereIn('status', ['new', 'waiting_on_us'])->orderBy('last_reply_at')->limit(6)->get(),
            'documents' => Document::with('shop')->where('status', 'pending')->oldest()->limit(6)->get(),
            'recentPaid' => Payment::with('shop')->where('status', 'paid')->latest('paid_at')->limit(5)->get(),
        ]);
    }
}
