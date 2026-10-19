<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/*
 * Makes an admin login without anyone typing a password into a terminal: the admin gets an email
 * with a link to choose their own password.
 *
 *   php artisan portal:make-admin --email=hello@3dplatebuilder.com --name=Harsh
 *   --if-missing  does nothing when that admin already exists (safe to leave in Plesk's deploy actions)
 */
Artisan::command('portal:make-admin {--email=} {--name=} {--if-missing}', function () {
    $email = strtolower(trim((string) ($this->option('email') ?: config('portal.admin_email'))));
    $name = trim((string) ($this->option('name') ?: 'Admin'));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Give a valid --email.');

        return 1;
    }

    $user = User::where('email', $email)->first();
    if ($user && $user->is_admin && $this->option('if-missing')) {
        $this->line("Admin {$email} already exists.");

        return 0;
    }

    $user ??= new User(['email' => $email]);
    $user->fill(['name' => $name]);
    if (! $user->exists) {
        $user->password = Str::random(64); // never used: they set their own from the email
    }
    $user->is_admin = true;
    $user->save();

    $status = Password::sendResetLink(['email' => $email]);
    $this->info($status === Password::RESET_LINK_SENT
        ? "Admin ready. A link to choose a password has gone to {$email}."
        : "Admin ready, but the email didn't send ({$status}). Use 'Forgotten your password?' on the sign-in page.");

    return 0;
})->purpose('Create an admin login and email them a link to set their password');
