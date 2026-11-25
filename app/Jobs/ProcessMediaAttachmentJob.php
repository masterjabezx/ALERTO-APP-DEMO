<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MediaAttachment;
use App\Services\MediaMetadataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class ProcessMediaAttachmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public int $mediaAttachmentId,
        public bool $simulateFailure = false
    ) {}

    /**
     * Execute asynchronous thumbnail, preview, and dimension/duration processing.
     * Strictly preserves the original evidence file on all failure paths.
     */
    public function handle(MediaMetadataService $metadataService): void
    {
        $media = MediaAttachment::find($this->mediaAttachmentId);
        if (! $media) {
            return;
        }

        $media->update([
            'processing_status' => 'processing',
            'processing_error' => null,
        ]);

        try {
            if ($this->simulateFailure) {
                throw new \RuntimeException('Simulated media processing pipeline failure.');
            }

            $config = $metadataService->getUploadPolicyConfiguration();
            $disk = Storage::disk($media->disk);

            if (! $disk->exists($media->file_path)) {
                throw new \RuntimeException("Original media file missing on disk [{$media->disk}]: {$media->file_path}");
            }

            $absolutePath = $disk->path($media->file_path);

            // Extract dimensions / duration if not yet captured
            $extracted = $metadataService->extractMetadata($absolutePath, $media->mime_type);
            $updates = [
                'width' => $media->width ?? $extracted['width'],
                'height' => $media->height ?? $extracted['height'],
                'duration_seconds' => $media->duration_seconds ?? $extracted['duration_seconds'],
            ];

            if ($config['auto_generate_thumbnails']) {
                if ($media->isPhoto()) {
                    $thumbResult = $this->generatePhotoDerivative($absolutePath, $media, 'thumbnails', 320);
                    $previewResult = $this->generatePhotoDerivative($absolutePath, $media, 'previews', 1280);

                    if ($thumbResult !== null) {
                        $updates['thumbnail_path'] = $thumbResult;
                    }
                    if ($previewResult !== null) {
                        $updates['preview_path'] = $previewResult;
                    }
                } elseif ($media->isVideo()) {
                    $videoThumb = $this->generateVideoThumbnail($absolutePath, $media);
                    if ($videoThumb !== null) {
                        $updates['thumbnail_path'] = $videoThumb;
                    }
                }
            }

            $updates['processing_status'] = 'ready';
            $media->update($updates);
        } catch (\Throwable $e) {
            // CRITICAL INVARIANT: Processing failure MUST NOT destroy or unlink the original file!
            Log::warning('Media processing error for MediaAttachment #'.$media->id.': '.$e->getMessage());
            $media->update([
                'processing_status' => 'failed',
                'processing_error' => substr($e->getMessage(), 0, 1000),
            ]);
        }
    }

    /**
     * Generate resized JPEG thumbnail or preview when GD extension is available.
     */
    private function generatePhotoDerivative(string $sourcePath, MediaAttachment $media, string $subDir, int $maxWidth): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $raw = @file_get_contents($sourcePath);
        if (! is_string($raw) || $raw === '' || trim($raw, "\0") === '') {
            return null;
        }

        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return null;
        }

        $origW = imagesx($img);
        $origH = imagesy($img);
        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($img);

            return null;
        }

        $targetW = min($origW, $maxWidth);
        $targetH = (int) round(($origH / $origW) * $targetW);

        $canvas = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

        ob_start();
        imagejpeg($canvas, null, 82);
        $encoded = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($img);

        if (! is_string($encoded) || $encoded === '') {
            return null;
        }

        $targetPath = "media/{$subDir}/{$media->id}_{$maxWidth}.jpg";
        Storage::disk($media->disk)->put($targetPath, $encoded);

        return $targetPath;
    }

    /**
     * Generate video thumbnail frame via FFmpeg if installed on the server.
     */
    private function generateVideoThumbnail(string $sourcePath, MediaAttachment $media): ?string
    {
        try {
            $check = Process::run(['ffmpeg', '-version']);
            if (! $check->successful()) {
                return null;
            }

            $targetRelPath = "media/thumbnails/{$media->id}_video.jpg";
            $disk = Storage::disk($media->disk);
            $disk->makeDirectory('media/thumbnails');
            $targetAbsPath = $disk->path($targetRelPath);

            $res = Process::timeout(20)->run([
                'ffmpeg',
                '-y',
                '-ss',
                '00:00:01',
                '-i',
                $sourcePath,
                '-vframes',
                '1',
                '-vf',
                'scale=480:-1',
                $targetAbsPath,
            ]);

            if ($res->successful() && is_file($targetAbsPath) && filesize($targetAbsPath) > 0) {
                return $targetRelPath;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
