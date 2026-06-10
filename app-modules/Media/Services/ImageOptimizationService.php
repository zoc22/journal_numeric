<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Modules\Media\Models\Media;

/**
 * Service d'optimisation d'images
 *
 * Gère la création de multiples versions d'une image :
 * - Redimensionnement
 * - Conversion WebP
 * - Optimisation de qualité
 */
class ImageOptimizationService
{
    /**
     * @var ImageManager
     */
    protected ImageManager $imageManager;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $driverClass = config('media.image_optimization.driver', 'gd') === 'imagick' 
            ? \Intervention\Image\Drivers\Imagick\Driver::class 
            : \Intervention\Image\Drivers\Gd\Driver::class;
            
        $this->imageManager = new ImageManager(new $driverClass());
    }

    /**
     * Optimise une image et génère les variants
     *
     * @param Media $media
     * @param array $options
     * @return array
     */
    public function optimize(Media $media, array $options = []): array
    {
        if (!$media->estImage()) {
            return [];
        }

        $variants = [];
        $originalPath = Storage::disk($media->disque)->path($media->chemin);

        if (!file_exists($originalPath)) {
            Log::warning('Fichier image introuvable pour optimisation', ['media_id' => $media->id]);
            return [];
        }

        $configs = config('media.image_optimization.formats', []);
        $quality = $options['quality'] ?? config('media.image_optimization.quality', 85);

        foreach ($configs as $key => $config) {
            try {
                $variantPath = $this->generateVariant(
                    $media,
                    $originalPath,
                    $key,
                    $config,
                    (int) $quality
                );

                if ($variantPath) {
                    $variants[$key] = $variantPath;
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la génération du variant', [
                    'media_id' => $media->id,
                    'variant' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $variants;
    }

    /**
     * Génère un variant d'image
     *
     * @param Media $media
     * @param string $originalPath
     * @param string $variantKey
     * @param array $config
     * @param int $quality
     * @return string|null
     */
    protected function generateVariant(
        Media $media,
        string $originalPath,
        string $variantKey,
        array $config,
        int $quality
    ): ?string {
        $image = $this->imageManager->decode($originalPath);

        // Redimensionnement
        if (isset($config['width']) && $config['width']) {
            $image->scale(
                width: $config['width'],
                height: $config['height'] ?? null
            );
        }

        // Détermine le format et encode
        $format = $config['format'] ?? 'jpg';
        $q = $config['quality'] ?? $quality;

        $encoder = match(strtolower($format)) {
            'webp' => new WebpEncoder(quality: $q),
            'png' => new PngEncoder(),
            'gif' => new GifEncoder(),
            default => new JpegEncoder(quality: $q),
        };

        $encoded = $image->encode($encoder);

        // Génère le chemin du variant
        $pathInfo = pathinfo($media->chemin);
        $variantPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$variantKey}." . $format;

        // Stocke le variant
        $stored = Storage::disk($media->disque)->put($variantPath, (string) $encoded);

        if (!$stored) {
            return null;
        }

        return $variantPath;
    }

    /**
     * Crée une version WebP d'une image
     *
     * @param Media $media
     * @return string|null
     */
    public function convertToWebP(Media $media): ?string
    {
        if (!$media->estImage()) {
            return null;
        }

        $originalPath = Storage::disk($media->disque)->path($media->chemin);

        if (!file_exists($originalPath)) {
            return null;
        }

        $image = $this->imageManager->decode($originalPath);

        // Génère le chemin WebP
        $pathInfo = pathinfo($media->chemin);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

        // Convertit et stocke
        $encoded = $image->encode(new WebpEncoder(quality: config('media.image_optimization.quality', 85)));
        $stored = Storage::disk($media->disque)->put($webpPath, (string) $encoded);

        return $stored ? $webpPath : null;
    }
}
