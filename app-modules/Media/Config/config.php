<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module Media
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient toutes les configurations spécifiques à la gestion
    | des médias (images, documents, vidéos, etc.)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Stockage
    |--------------------------------------------------------------------------
    |
    | Configuration du disque de stockage pour les médias.
    |
    */
    'storage' => [
        'disk' => env('MEDIA_DISK', 'public'),
        'path' => env('MEDIA_PATH', 'media'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de fichiers autorisés
    |--------------------------------------------------------------------------
    |
    | Extensions et types MIME autorisés par catégorie.
    |
    */
    'allowed_types' => [
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            'max_size' => 5 * 1024 * 1024, // 5 MB
        ],
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'mimes' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'max_size' => 10 * 1024 * 1024, // 10 MB
        ],
        'video' => [
            'extensions' => ['mp4', 'webm', 'ogg'],
            'mimes' => ['video/mp4', 'video/webm', 'video/ogg'],
            'max_size' => 100 * 1024 * 1024, // 100 MB
        ],
        'audio' => [
            'extensions' => ['mp3', 'wav', 'ogg'],
            'mimes' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
            'max_size' => 20 * 1024 * 1024, // 20 MB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimisation des images
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'optimisation automatique des images téléchargées.
    |
    */
    'image_optimization' => [
        'enabled' => env('MEDIA_OPTIMIZE_IMAGES', true),
        'driver' => env('MEDIA_IMAGE_DRIVER', 'gd'), // 'gd' or 'imagick'
        'quality' => env('MEDIA_IMAGE_QUALITY', 85),
        'formats' => [
            'original' => ['width' => null, 'height' => null, 'quality' => 85],
            'large' => ['width' => 1200, 'height' => 1200, 'quality' => 85],
            'medium' => ['width' => 800, 'height' => 800, 'quality' => 80],
            'small' => ['width' => 400, 'height' => 400, 'quality' => 75],
            'thumbnail' => ['width' => 150, 'height' => 150, 'quality' => 70],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Génération de noms uniques
    |--------------------------------------------------------------------------
    |
    | Configuration pour la génération de noms de fichiers uniques.
    |
    */
    'naming' => [
        'strategy' => 'uuid', // 'uuid', 'timestamp', 'original'
        'preserve_original' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | URLs et CDN
    |--------------------------------------------------------------------------
    |
    | Configuration pour les URLs publiques des médias.
    |
    */
    'urls' => [
        'cdn_url' => env('MEDIA_CDN_URL', null),
        'signed_url_ttl' => env('MEDIA_SIGNED_URL_TTL', 3600), // 1 hour
    ],
];
