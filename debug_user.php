<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\User\Models\User;

$user = User::first();
if ($user) {
    echo "Class: " . get_class($user) . "\n";
    if (method_exists($user, 'hasRole')) {
        echo "hasRole method exists\n";
    } else {
        echo "hasRole method NOT exists\n";
    }
} else {
    echo "No user found, creating one...\n";
    $user = User::create([
        'nom' => 'Test',
        'email' => 'test_'.time().'@example.com',
        'password' => bcrypt('password')
    ]);
    echo "Created Class: " . get_class($user) . "\n";
}
