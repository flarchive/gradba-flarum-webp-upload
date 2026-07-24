<?php

/*
 * This file is part of gradba/flarum-webp-upload.
 *
 * For the full copyright and license information, see the LICENSE file.
 */

use Flarum\Extend;
use FoF\Upload\Events\File\WillBeUploaded;
use Gradba\WebpUpload\Listeners\ConvertToWebp;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Event())
        ->listen(WillBeUploaded::class, ConvertToWebp::class),

    (new Extend\Settings())
        ->serializeToForum('gradba-webp-upload.enabled', 'gradba-webp-upload.enabled', 'boolval', true)
        ->default('gradba-webp-upload.enabled', true)
        ->default('gradba-webp-upload.quality', 82)
        ->default('gradba-webp-upload.maxSize', 1920),
];
