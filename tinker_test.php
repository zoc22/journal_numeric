<?php
namespace {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    use Modules\User\Models\User;
    use Spatie\Permission\Models\Role;

    try {
        echo "Attempting to create user...\n";
        $user = User::create([
            'nom' => 'Tinker User',
            'email' => 'tinker_'.time().'@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        echo "User created with ID: " . $user->id . "\n";
        
        echo "Attempting to assign role...\n";
        // Ensure role exists
        $role = Role::firstOrCreate(['name' => 'journaliste', 'guard_name' => 'sanctum']);
        $user->assignRole($role);
        echo "Role assigned.\n";
        
        echo "Checking hasRole('journaliste'): ";
        var_dump($user->hasRole('journaliste'));
        
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}
