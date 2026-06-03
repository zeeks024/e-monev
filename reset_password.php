<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hash = password_hash('password123', PASSWORD_BCRYPT, ['rounds' => 12]);
DB::table('users')->where('email', 'arzakizuniorputra19@gmail.com')->update(['password' => $hash]);
echo "Password updated. Hash: $hash\n";
