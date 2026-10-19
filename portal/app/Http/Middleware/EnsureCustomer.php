<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The account area is for plate makers; admins are sent to the admin. */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        abort_unless($user->shop, 403, 'This login has no shop attached. Email hello@3dplatebuilder.com and we\'ll sort it.');

        return $next($request);
    }
}
