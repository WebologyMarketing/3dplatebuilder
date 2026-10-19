<?php

namespace App\Http\Controllers;

use App\Mail\PortalNotice;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SignupController extends Controller
{
    public function show(Request $request)
    {
        $plans = config('portal.plans');
        $plan = array_key_exists($request->query('plan'), $plans) ? $request->query('plan') : 'pro';

        return view('auth.signup', compact('plans', 'plan'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('portal.plans')))],
            'business' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)],
            'terms' => ['accepted'],
        ], [
            'email.unique' => 'There\'s already an account with this email. Try signing in instead.',
            'terms.accepted' => 'Please tick to say you\'ve read the privacy notice and terms.',
        ]);

        $user = DB::transaction(function () use ($data) {
            $shop = Shop::create([
                'name' => $data['business'],
                'plan' => $data['plan'],
                'stage' => 'not_paid',
                'contact_email' => $data['email'],
            ]);

            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'shop_id' => $shop->id,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        Mail::to(config('portal.admin_email'))->send(new PortalNotice(
            "New sign-up: {$data['business']}",
            ["{$data['name']} ({$data['email']}) signed up for the ".config("portal.plans.{$data['plan']}.name").' plan. They haven\'t paid yet.'],
            route('admin.shops.show', $user->shop_id),
            'Open their account',
        ));

        return redirect()->route('pay');
    }
}
