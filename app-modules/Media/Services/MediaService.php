<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Models\Media;
use Modules\User\Models\User;

/**
 * Service de gestion des médias
 *
 * Centralise la logique métier pour :
 * - Upload de fichiers
 * - Optimisation d'images
 * - Génération de variants
 * - Suppression de fichiers
 */
class MediaService
{
    /**
     * @var ImageOptimizationService
     */
    protected ImageOptimizationService $imageOptimizationService;

    /**
     * Constructeur
     */
    public function __construct(ImageOptimizationService $imageOptimizationService)
    {
        $this->imageOptimizationService = $imageOptimizationService;
    }

    /**
     * Téléverse un fichier
     *
     * @param UploadedFile $file
     * @param User $uploader
     * @param string|null $maisonId
     * @param array $options
     * @return Media
     * @throws \InvalidArgumentException
     */
    public function upload(UploadedFile $file, User $uploader, ?string $maisonId = null, array $options = []): Media
    {
        // Valide le fichier
        $type = $this->determineFileType($file);
        $this->validateFile($file, $type);

        // Génère un nom unique
        $filename = $this->generateUniqueFilename($file);
        $path = $this->buildStoragePath($filename, $type, $maisonId);

        // Calcule le hash du fichier (détection des doublons)
        $hash = hash_file('sha256', $file->getRealPath());

        // Vérifie si un fichier identique existe déjà
        $existingMedia = Media::where('hash', $hash)
            ->where('maison_id', $maisonId)
            ->first();

        if ($existingMedia && ($options['allow_duplicate'] ?? false) === false) {
            throw new \InvalidArgumentException('Un fichier identique existe déjà dans la médiathèque');
        }

        return DB::transaction(function () use ($file, $uploader, $maisonId, $path, $filename, $type, $hash, $options) {
            // Stocke le fichier
            $disk = config('media.storage.disk', 'public');
            $storedPath = Storage::disk($disk)->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );

            if (!$storedPath) {
                throw new \RuntimeException('Erreur lors du téléversement du fichier');
            }

            // Prépare les métadonnées
            $metadonnees = $this->extractMetadata($file, $type);

            // Crée l'enregistrement en base
            $media = Media::create([
                'nom_fichier' => $filename,
                'nom_original' => $file->getClientOriginalName(),
                'chemin' => $storedPath,
                'disque' => $disk,
                'type_mime' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'taille' => $file->getSize(),
                'hash' => $hash,
                'type' => $type,
                'metadonnees' => $metadonnees,
                'maison_id' => $maisonId,
                'televerse_par' => $uploader->id,
                'continent' => $options['continent'] ?? $uploader->continent,
                'pays' => $options['pays'] ?? $uploader->pays,
                'ville' => $options['ville'] ?? $uploader->ville,
                'est_publique' => $options['is_public'] ?? true,
            ]);

            // Optimise l'image si nécessaire et génère les variants
            if ($type === 'image' && config('media.image_optimization.enabled', true)) {
                $variants = $this->imageOptimizationService->optimize($media, $options);
                $media->update(['variants' => $variants]);
            }

            Log::info('Média téléversé', [
                'media_id' => $media->id,
                'filename' => $filename,
                'size' => $file->getSize(),
                'type' => $type,
                'user_id' => $uploader->id,
            ]);

            return $media;
        });
    }

    /**
     * Supprime un média
     *
     * @param Media $media
     * @param User $user
     * @return bool
     */
    public function delete(Media $media, User $user): bool
    {
        // Vérifie les permissions
        if ($media->televerse_par !== $user->id && !$user->hasRole(['editeur_chef', 'admin_plateforme'])) {
            throw new \DomainException('Vous n\'êtes pas autorisé à supprimer ce média');
        }

        // Vérifie si le média est utilisé dans des articles
        $usageCount = $media->articles()->count();
        if ($usageCount > 0) {
            throw new \DomainException("Ce média est utilisé dans {$usageCount} article(s). Supprimez d'abord ces associations.");
        }

        return $media->delete();
    }

    /**
     * Met à jour les métadonnées d'un média
     *
     * @param Media $media
     * @param array $data
     * @return Media
     */
    public function updateMetadata(Media $media, array $data): Media
    {
        $updatable = ['nom_original', 'est_publique', 'continent', 'pays', 'ville'];

        foreach ($updatable as $field) {
            if (isset($data[$field])) {
                $media->$field = $data[$field];
            }
        }

        if (isset($data['metadonnees'])) {
            $media->metadonnees = array_merge($media->metadonnees ?? [], $data['metadonnees']);
        }

        $media->save();

        return $media;
    }

    /**
     * Détermine le type de fichier
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function determineFileType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])) {
            return 'document';
        }

        return 'other';
    }

    /**
     * Valide le fichier selon son type
     *
     * @param UploadedFile $file
     * @param string $type
     * @throws \InvalidArgumentException
     */
    protected function validateFile(UploadedFile $file, string $type): void
    {
        $config = config("media.allowed_types.{$type}");

        if (!$config) {
            throw new \InvalidArgumentException("Type de fichier non supporté: {$type}");
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $config['extensions'])) {
            throw new \InvalidArgumentException(
                "Extension non autorisée. Extensions autorisées: " . implode(', ', $config['extensions'])
            );
        }

        if ($file->getSize() > $config['max_size']) {
            $maxSizeMB = $config['max_size'] / 1024 / 1024;
            throw new \InvalidArgumentException("Le fichier dépasse la taille maximale de {$maxSizeMB} MB");
        }
    }

    /**
     * Génère un nom de fichier unique
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $strategy = config('media.naming.strategy', 'uuid');
        $extension = $file->getClientOriginalExtension();

        switch ($strategy) {
            case 'uuid':
                $name = (string) Str::uuid();
                break;
            case 'timestamp':
                $name = now()->timestamp . '_' . Str::random(8);
                break;
            case 'original':
                $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $name = Str::slug($original) . '_' . Str::random(6);
                break;
            default:
                $name = (string) Str::uuid();
        }

        return $name . '.' . $extension;
    }

    /**
     * Construit le chemin de stockage
     *
     * @param string $filename
     * @param string $type
     * @param string|null $maisonId
     * @return string
     */
    protected function buildStoragePath(string $filename, string $type, ?string $maisonId = null): string
    {
        $basePath = config('media.storage.path', 'media');
        $datePath = now()->format('Y/m/d');
        $typePath = $type . 's';

        if ($maisonId) {
            return "{$basePath}/{$maisonId}/{$typePath}/{$datePath}/{$filename}";
        }

        return "{$basePath}/global/{$typePath}/{$datePath}/{$filename}";
    }

    /**
     * Extrait les métadonnées du fichier
     *
     * @param UploadedFile $file
     * @param string $type
     * @return array
     */
    protected function extractMetadata(UploadedFile $file, string $type): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
        ];

        if ($type === 'image') {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['bits'] = $imageInfo['bits'] ?? null;
                $metadata['channels'] = $imageInfo['channels'] ?? null;
            }
        }

        return $metadata;
    }

    /**
     * Récupère un média par son hash
     *
     * @param string $hash
     * @param string|null $maisonId
     * @return Media|null
     */
    public function findByHash(string $hash, ?string $maisonId = null): ?Media
    {
        return Media::where('hash', $hash)
            ->when($maisonId, fn($q) => $q->where('maison_id', $maisonId))
            ->first();
    }

    /**
     * Récupère les statistiques des médias
     *
     * @param string|null $maisonId
     * @return array
     */
    public function getStatistics(?string $maisonId = null): array
    {
        $query = Media::query();

        if ($maisonId) {
            $query->where('maison_id', $maisonId);
        }

        return [
            'total' => $query->count(),
            'by_type' => [
                'image' => (clone $query)->where('type', 'image')->count(),
                'document' => (clone $query)->where('type', 'document')->count(),
                'video' => (clone $query)->where('type', 'video')->count(),
                'audio' => (clone $query)->where('type', 'audio')->count(),
                'other' => (clone $query)->where('type', 'other')->count(),
            ],
            'total_size_bytes' => $query->sum('taille'),
            'total_size_formatted' => $this->formatBytes($query->sum('taille')),
        ];
    }

    /**
     * Formate les bytes en taille lisible
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
