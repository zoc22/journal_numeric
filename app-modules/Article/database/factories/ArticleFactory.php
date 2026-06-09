<?php

declare(strict_types=1);

namespace Modules\Article\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $titre = fake()->sentence();
        return [
            'titre' => $titre,
            'slug' => \Illuminate\Support\Str::slug($titre),
            'contenu' => fake()->paragraphs(5, true),
            'resume' => fake()->paragraph(),
            'statut' => 'brouillon',
            'auteur_id' => User::factory(),
            'maison_id' => tenant('id'),
            'continent' => 'Afrique',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
        ];
    }
}
