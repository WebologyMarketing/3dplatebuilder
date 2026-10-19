<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->user()->shop;
        $steps = $shop->onboardingSteps();
        $done = collect($steps)->where('done', true)->count();
        $next = collect($steps)->firstWhere('done', false);

        return view('account.dashboard', [
            'shop' => $shop,
            'steps' => $steps,
            'done' => $done,
            'next' => $next,
            'openTickets' => $shop->tickets()->where('status', '!=', 'closed')->count(),
            'nextPayment' => $shop->current_period_end,
        ]);
    }
}
