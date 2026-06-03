<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('email', 'admin@emonev.com')->first();
if ($u) {
    $u->password = Illuminate\Support\Facades\Hash::make('admin12345678');
    $u->save();
    echo "Password reset successfully.";
} else {
    echo "User not found.";
}
