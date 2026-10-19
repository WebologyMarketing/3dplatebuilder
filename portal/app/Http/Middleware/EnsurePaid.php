<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Set-up pages open once the set-up fee is paid. */
class EnsurePaid
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->shop->setup_paid_at) {
            return redirect()->route('dashboard')->with('status', 'Once your payment has gone through, you can fill in your details here.');
        }

        return $next($request);
    }
}
