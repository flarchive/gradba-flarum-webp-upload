<?php

/*
 * This file is part of gradba/flarum-webp-upload.
 *
 * For the full copyright and license information, see the LICENSE file.
 */

namespace Gradba\WebpUpload\Listeners;

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Upload\Events\File\WillBeUploaded;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;

/**
 * Converts uploaded raster images to WebP before FoF Upload hands the temp file
 * to the storage adapter.
 *
 * Timing matters: FoF dispatches WillBeUploaded *after* the File model is built
 * but *before* `$adapter->upload()` re-reads the temp file, so rewriting the temp
 * file here is what actually gets stored. The File model's name/mime/size are
 * mutated to match, so the DB row and the stored object stay consistent.
 *
 * This extension requires fof/upload in composer.json, which makes Flarum boot
 * FoF Upload first — so its own ImageProcessor (resize/watermark/orientate) has
 * already run by the time we re-encode.
 */
class ConvertToWebp
{
    /** Formats worth re-encoding. GIF is excluded: it may be animated, and GD would flatten it. */
    private const CONVERTIBLE = ['image/jpeg', 'image/png', 'image/bmp', 'image/tiff'];

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected LoggerInterface $log
    ) {
    }

    public function handle(WillBeUploaded $event): void
    {
        if (!$this->settings->get('gradba-webp-upload.enabled', true)) {
            return;
        }

        if (!in_array($event->mime, self::CONVERTIBLE, true)) {
            return;
        }

        $path = $event->uploadedFile->getRealPath();

        if (!$path || !is_readable($path) || !is_writable($path)) {
            return;
        }

        $quality = (int) ($this->settings->get('gradba-webp-upload.quality') ?: 82);
        $maxSize = (int) ($this->settings->get('gradba-webp-upload.maxSize') ?: 1920);

        $originalSize = (int) @filesize($path);

        try {
            $image = (new ImageManager(['driver' => 'gd']))->make($path);

            if ($maxSize > 0) {
                $image->resize($maxSize, $maxSize, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            $encoded = (string) $image->encode('webp', $quality);
            $image->destroy();
        } catch (\Throwable $e) {
            // An optimization must never break an upload — keep the original.
            $this->log->warning('[gradba-webp-upload] conversion skipped: '.$e->getMessage());

            return;
        }

        $newSize = strlen($encoded);

        // Keep whichever is smaller. Already-optimized or tiny images can grow as WebP.
        if ($newSize <= 0 || ($originalSize > 0 && $newSize >= $originalSize)) {
            return;
        }

        if (@file_put_contents($path, $encoded) === false) {
            return;
        }

        clearstatcache(true, $path);

        $file = $event->file;
        $file->base_name = preg_replace('/\.[A-Za-z0-9]+$/', '', $file->base_name).'.webp';
        $file->type = 'image/webp';
        $file->size = $newSize;
    }
}
