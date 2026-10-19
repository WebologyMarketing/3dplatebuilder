<?php

namespace App\Providers;

use App\Services\Billing;
use App\Services\StripeBilling;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Billing::class, StripeBilling::class);
    }

    public function boot(): void
    {
        ResetPassword::toMailUsing(fn ($user, string $token) => (new MailMessage)
            ->subject('Choose your password | 3dPlateBuilder.com')
            ->greeting('Hello '.strtok($user->name, ' ').',')
            ->line('Use the button below to choose a password for your 3dPlateBuilder.com account.')
            ->action('Choose a password', url(route('password.reset', ['token' => $token, 'email' => $user->email], false)))
            ->line('The link works for 60 minutes. If you didn\'t ask for this, you can ignore this email.')
            ->salutation('3dPlateBuilder.com'));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
