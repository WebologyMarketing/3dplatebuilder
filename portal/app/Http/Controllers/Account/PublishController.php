<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Publisher;
use Illuminate\Http\Request;

class PublishController extends Controller
{
    public function __invoke(Request $request, Publisher $publisher)
    {
        $shop = $request->user()->shop;
        $told = $publisher->publish($shop);

        $message = match (true) {
            $told => 'Published. Your website is updating now; give it a minute.',
            (bool) $shop->siteUrl() => 'Published. Your website will pick this up within the hour.',
            default => 'Published. We\'ll use these when we build your shop, and your website will pick up any changes once it\'s set up.',
        };

        return back()->with('status', $message);
    }
}
