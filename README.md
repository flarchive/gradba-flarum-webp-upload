# WebP Upload for Flarum (`gradba/flarum-webp-upload`)

Automatically converts uploaded images to **WebP** — and caps their dimensions —
before [FoF Upload](https://github.com/FriendsOfFlarum/upload) stores them.

Forum attachments are usually the largest thing a Flarum install stores. On our
forum, converting the existing library measured **−61% for JPEG** and **−90% for
PNG** (5.3 GB → ~2.1 GB). This extension keeps new uploads that small at the
source, which matters whether you store files locally or pay per GB on S3/R2.

## How it works

FoF Upload dispatches `WillBeUploaded` after the file record is built but before
the storage adapter reads the temp file. This extension hooks that moment to
re-encode the temp file and update the file record's name, mime type and size to
match — so the stored object and the database row stay consistent.

Because it declares `fof/upload` as a dependency, Flarum boots FoF Upload first,
so its own resize/watermark/orientate pass has already run before conversion.

**Safety behaviors:**

- Converts **JPEG, PNG, BMP, TIFF**. Never touches **GIF** (may be animated) or **SVG**.
- If the WebP copy would be **larger** than the original, the original is kept.
- Any conversion error is logged and the upload proceeds with the original file —
  an optimization never breaks an upload.
- Existing uploads are untouched; this only affects new ones.

## Settings

Admin → Extensions → **WebP Upload**:

| Setting | Default | Notes |
|---|---|---|
| Convert uploads to WebP | on | Master switch |
| WebP quality | 82 | Near-lossless for photos at ~⅓ the size |
| Maximum width/height | 1920 | Aspect ratio kept, never upscaled; `0` disables |

## Install

```bash
composer require gradba/flarum-webp-upload
php flarum cache:clear
```

## Requires

Flarum `^1.8`, FoF Upload `^1.8`, and PHP **GD compiled with WebP support**
(check with `php -r '$g=gd_info(); var_dump($g["WebP Support"]);'`).

## License

MIT — see [LICENSE](LICENSE).
