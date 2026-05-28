<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class TenantController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'domain' => 'required|string|ends_with:.localhost',
        ]);

        $name = $request->input('name');
        $domain = $request->input('domain');

        try {
            // Call the artisan command programmatically
            $exitCode = Artisan::call('tenant:create-with-host', [
                '--name' => $name,
                '--domain' => $domain,
            ]);

            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Tenant '{$name}' créé avec succès",
                    'tenant' => [
                        'name' => $name,
                        'domain' => $domain,
                        'url' => "http://{$domain}",
                    ],
                    'output' => $output,
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Erreur lors de la création du tenant",
                    'output' => $output,
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur: " . $e->getMessage(),
            ], 500);
        }
    }
}
