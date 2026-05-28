<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Console\Command;

class CreateTenantWithHost extends Command
{
    protected $signature = 'tenant:create-with-host
                            {--name= : Le nom du tenant}
                            {--domain= : Le domaine du tenant (ex: monentreprise.localhost)}';

    protected $description = 'Crée un tenant, son domaine et ajoute l\'entrée au fichier hosts automatiquement';

    public function handle()
    {
        $name = $this->option('name') ?? $this->ask('Nom du tenant');
        $domain = $this->option('domain') ?? $this->ask('Domaine du tenant (ex: mycompany.localhost)');

        if (!str_ends_with($domain, '.localhost')) {
            $this->error('Le domaine doit se terminer par .localhost');
            return Command::FAILURE;
        }

        try {
            $this->info("Création du tenant '{$name}'...");
            $tenant = Tenant::create(['id' => $name]);
            $this->info("✓ Tenant créé avec l'ID: {$tenant->id}");

            $this->info("Création du domaine '{$domain}'...");
            Domain::create([
                'domain' => $domain,
                'tenant_id' => $tenant->id,
            ]);
            $this->info("✓ Domaine créé");

            $this->info("Ajout de l'entrée au fichier hosts...");
            $this->addToHosts($domain);

            $this->info("\n✅ Tenant '{$name}' créé avec succès!");
            $this->info("   Domaine: {$domain}");
            $this->info("   URL: http://{$domain}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Erreur: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function addToHosts(string $domain): void
    {
        $hostsFile = 'C:\\Windows\\System32\\drivers\\etc\\hosts';
        $ip = '127.0.0.1';
        $entry = "{$ip} {$domain}";

        if (!file_exists($hostsFile)) {
            throw new \Exception("Fichier hosts introuvable: {$hostsFile}");
        }

        $content = file_get_contents($hostsFile);

        if (str_contains($content, $entry)) {
            $this->comment("✓ Entrée existait déjà");
            return;
        }

        $newEntry = PHP_EOL . $entry;
        if (@file_put_contents($hostsFile, $newEntry, FILE_APPEND)) {
            $this->info("✓ Entrée ajoutée au fichier hosts");
            return;
        }

        $this->warn("\nAccès administrateur requis!");
        $this->line("Exécutez en tant qu'administrateur:");
        $this->line("Add-Content -Path 'C:\\Windows\\System32\\drivers\\etc\\hosts' -Value '127.0.0.1 {$domain}' -Encoding UTF8");
    }
}
