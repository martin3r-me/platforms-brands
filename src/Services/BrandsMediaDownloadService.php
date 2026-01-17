<?php

namespace Platform\Brands\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Platform\Core\Services\ContextFileService;
use Platform\Core\Models\ContextFile;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Auth;

/**
 * Service zum Herunterladen und Speichern von Bildern/Videos aus URLs
 * Verwendet das ContextFile-System für kontextbezogene Dateien
 */
class BrandsMediaDownloadService
{
    protected ContextFileService $contextFileService;
    protected string $disk;

    public function __construct(ContextFileService $contextFileService)
    {
        $this->contextFileService = $contextFileService;
        $this->disk = config('filesystems.default', 'public');
    }

    /**
     * Lädt ein Bild/Video von einer URL herunter und speichert es als ContextFile
     * 
     * @param string $url URL des Bildes/Videos
     * @param string $contextType Model-Klasse (z.B. BrandsInstagramMedia::class)
     * @param int $contextId ID des Models
     * @param array $meta Zusätzliche Meta-Daten
     * @return ContextFile|null
     */
    public function downloadAndStore(string $url, string $contextType, int $contextId, array $meta = []): ?ContextFile
    {
        if (empty($url)) {
            return null;
        }

        try {
            // Datei herunterladen
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                Log::warning('Failed to download media from URL', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            // Temporäre Datei erstellen
            $tempPath = $this->createTempFile($response->body(), $url);
            
            if (!$tempPath) {
                return null;
            }

            // Dateiname aus URL extrahieren
            $originalName = basename(parse_url($url, PHP_URL_PATH));
            if (empty($originalName) || $originalName === '/') {
                $extension = $this->getExtensionFromMimeType($response->header('Content-Type'));
                $originalName = 'media_' . time() . '.' . $extension;
            }

            // UploadedFile-Objekt erstellen
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                $originalName,
                $this->detectMimeTypeFromUrl($url, $response->header('Content-Type')),
                null,
                true // test mode
            );

            // Team-ID und User-ID aus dem Kontext-Model holen
            $contextModel = $contextType::find($contextId);
            $teamId = $contextModel?->team_id ?? null;
            $userId = $contextModel?->user_id ?? null;
            
            if (!$teamId || !$userId) {
                Log::error('BrandsMediaDownloadService: Keine team_id oder user_id im Kontext-Model gefunden', [
                    'context_type' => $contextType,
                    'context_id' => $contextId,
                ]);
                return null;
            }

            // Über ContextFileService hochladen
            $result = $this->contextFileService->uploadForContext(
                $uploadedFile,
                $contextType,
                $contextId,
                [
                    'keep_original' => $meta['keep_original'] ?? false,
                    'generate_variants' => $meta['generate_variants'] ?? true,
                    'user_id' => $userId,
                    'team_id' => $teamId,
                ]
            );

            // Temporäre Datei löschen
            @unlink($tempPath);

            // ContextFile aus DB laden
            $contextFile = ContextFile::find($result['id']);

            // Zusätzliche Meta-Daten hinzufügen
            if ($contextFile && !empty($meta)) {
                $existingMeta = $contextFile->meta ?? [];
                $contextFile->update([
                    'meta' => array_merge($existingMeta, [
                        'source_url' => $url,
                        'downloaded_at' => now()->toIso8601String(),
                    ], $meta),
                ]);
            }

            Log::info('Media downloaded and stored as ContextFile', [
                'url' => $url,
                'context_file_id' => $contextFile?->id,
                'context_type' => $contextType,
                'context_id' => $contextId,
            ]);

            return $contextFile;

        } catch (\Exception $e) {
            Log::error('Error downloading media from URL', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Temporäre Datei löschen, falls vorhanden
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            
            return null;
        }
    }

    /**
     * Erstellt eine temporäre Datei aus dem Inhalt
     */
    protected function createTempFile(string $content, string $url): ?string
    {
        try {
            $tempPath = tempnam(sys_get_temp_dir(), 'brands_media_');
            
            if (!$tempPath) {
                return null;
            }

            file_put_contents($tempPath, $content);
            
            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Failed to create temp file', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Erkennt MIME-Type aus URL oder Content-Type Header
     */
    protected function detectMimeTypeFromUrl(string $url, ?string $contentType = null): string
    {
        // Versuche Content-Type Header
        if ($contentType) {
            $mimeType = explode(';', $contentType)[0];
            if ($mimeType) {
                return trim($mimeType);
            }
        }

        // Fallback: Aus Dateiendung
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Ermittelt Dateiendung aus MIME-Type
     */
    protected function getExtensionFromMimeType(?string $mimeType): string
    {
        if (!$mimeType) {
            return 'jpg';
        }

        $mimeType = explode(';', $mimeType)[0];
        $mimeType = trim($mimeType);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
        ];

        return $extensions[$mimeType] ?? 'jpg';
    }
}
