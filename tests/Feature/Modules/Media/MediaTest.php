<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialisation de la tenancy
        $tenant = \App\Models\Tenant::firstOrCreate(['id' => '00000000-0000-0000-0000-000000000000'], ['name' => 'Test Maison']);
        if (!$tenant->domains()->where('domain', 'localhost')->exists()) {
            $tenant->domains()->create(['domain' => 'localhost']);
        }
        tenancy()->initialize($tenant);

        $this->user = User::factory()->create([
            'nom' => 'Test User',
            'continent' => 'Europe',
            'pays' => 'France',
            'ville' => 'Paris',
        ]);
        $this->user->assignRole('journaliste');
        
        Storage::fake('public');
    }

    /** @test */
    public function un_utilisateur_peut_uploader_une_image()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->postJson('/api/workflow/media', [
                'file' => $file,
                'type' => 'image',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'url',
                'localisation' => ['continent', 'pays', 'ville'],
            ]
        ]);

        $this->assertDatabaseHas('media', [
            'nom_original' => 'photo.jpg',
            'type' => 'image',
            'continent' => 'Europe',
            'pays' => 'France',
            'ville' => 'Paris',
        ]);

        $media = Media::first();
        Storage::disk('public')->assertExists($media->chemin);
        
        // Vérifie les variants (si activés)
        if (config('media.image_optimization.enabled')) {
            foreach ($media->variants as $variant) {
                Storage::disk('public')->assertExists($variant);
            }
        }
    }

    /** @test */
    public function un_utilisateur_peut_uploader_un_document()
    {
        $file = UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson('/api/workflow/media', [
                'file' => $file,
                'type' => 'document',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('media', [
            'nom_original' => 'rapport.pdf',
            'type' => 'document',
        ]);
    }

    /** @test */
    public function on_peut_mettre_a_jour_les_metadonnees_dun_media()
    {
        $media = Media::create([
            'nom_fichier' => 'test.jpg',
            'nom_original' => 'original.jpg',
            'chemin' => 'media/test.jpg',
            'disque' => 'public',
            'type_mime' => 'image/jpeg',
            'extension' => 'jpg',
            'taille' => 1024,
            'hash' => 'fakehash',
            'type' => 'image',
            'televerse_par' => $this->user->id,
            'maison_id' => tenant('id'),
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/workflow/media/{$media->id}", [
                'nom_original' => 'nouveau_nom.jpg',
                'continent' => 'Afrique',
                'pays' => 'Sénégal',
                'ville' => 'Dakar',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'nom_original' => 'nouveau_nom.jpg',
            'continent' => 'Afrique',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
        ]);
    }

    /** @test */
    public function un_utilisateur_peut_supprimer_son_media()
    {
        $file = UploadedFile::fake()->image('todelete.jpg');
        $media = Media::create([
            'nom_fichier' => 'todelete.jpg',
            'nom_original' => 'todelete.jpg',
            'chemin' => 'media/todelete.jpg',
            'disque' => 'public',
            'type_mime' => 'image/jpeg',
            'extension' => 'jpg',
            'taille' => 1024,
            'hash' => 'uniquehash',
            'type' => 'image',
            'televerse_par' => $this->user->id,
            'maison_id' => tenant('id'),
        ]);
        
        Storage::disk('public')->put('media/todelete.jpg', 'content');

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/workflow/media/{$media->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing('media/todelete.jpg');
    }

    /** @test */
    public function on_peut_lister_les_medias_avec_filtres()
    {
        Media::factory()->count(5)->create([
            'televerse_par' => $this->user->id,
            'maison_id' => tenant('id'),
            'type' => 'image'
        ]);
        
        Media::factory()->count(3)->create([
            'televerse_par' => $this->user->id,
            'maison_id' => tenant('id'),
            'type' => 'document'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/workflow/media?type=image');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }
}
