<?php

declare(strict_types=1);

namespace Modules\Media\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Media\Models\Media;
use Modules\User\Models\User;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'nom_fichier' => $this->faker->uuid() . '.jpg',
            'nom_original' => $this->faker->word() . '.jpg',
            'chemin' => 'media/' . $this->faker->uuid() . '.jpg',
            'disque' => 'public',
            'type_mime' => 'image/jpeg',
            'extension' => 'jpg',
            'taille' => $this->faker->numberBetween(1000, 5000000),
            'hash' => $this->faker->sha256(),
            'type' => 'image',
            'continent' => $this->faker->word(),
            'pays' => $this->faker->country(),
            'ville' => $this->faker->city(),
            'televerse_par' => User::factory(),
            'maison_id' => '00000000-0000-0000-0000-000000000000',
            'est_publique' => true,
        ];
    }
}
