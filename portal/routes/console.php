<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

// Run once on the server to make your own login: php artisan portal:make-admin
Artisan::command('portal:make-admin', function () {
    $email = text('Email', default: 'hello@3dplatebuilder.com', required: true);
    $name = text('Your name', required: true);
    $pass = password('Password (at least 12 characters)', required: true, validate: fn ($v) => strlen($v) < 12 ? 'At least 12 characters, please.' : null);

    $user = User::firstOrNew(['email' => $email]);
    $user->fill(['name' => $name, 'password' => $pass]);
    $user->is_admin = true;
    $user->save();

    $this->info("Admin login ready for {$email}.");
})->purpose('Create or update an admin login for the portal');
