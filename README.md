# bgcut-php – remove image backgrounds with AI from PHP

[![Latest Version](https://img.shields.io/packagist/v/bgCut-net/bgcut-php.svg)](https://packagist.org/packages/bgCut-net/bgcut-php)
[![PHP 8.1+](https://img.shields.io/badge/php-%3E%3D8.1-777bb4.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Official, dependency-free PHP client for the **[bgCut.net](https://bgcut.net) background removal API**.
One call turns a photo into a transparent PNG (or a cut-out on a colour / blurred background) – a drop-in alternative to the remove.bg API.

```php
$bg = new BgCut\Client('bgc_your_api_key');
$bg->removeToFile('photo.jpg', 'photo-nobg.png');
```

* 🆓 **5 free previews a month** (up to 640 px), full-resolution results from **$0.06 per image**
* 🧠 4 AI models: `fast` (0.5 s), `lite` (6 s, default), `hq` (15 s), `portrait` (0.3 s, people)
* 🎨 Transparent, solid colour or **blurred** background, auto-crop, edge feathering, PNG / JPG / WebP
* 📦 Zero dependencies – just `ext-curl`; works with or without Composer

## Installation

```bash
composer require bgCut-net/bgcut-php
```

Without Composer: copy the `src/` folder and `require_once 'src/autoload.php';`.

## Get an API key

1. Create a free account at **https://bgcut.net/register**
2. Open **https://bgcut.net/api-keys** and click *Create key* – keys look like `bgc_…`
3. Keep the key on the server side (environment variable, never in browser code)

```bash
export BGCUT_API_KEY=bgc_xxxxxxxxxxxxxxxxxxxx
```

## Usage

### Local file → transparent PNG

```php
require 'vendor/autoload.php';

$bg = new BgCut\Client(getenv('BGCUT_API_KEY'));

$result = $bg->removeFile('photo.jpg', ['size' => 'full', 'model' => 'lite']);
$result->save('photo-nobg.png');

echo "{$result->width}x{$result->height}, {$result->creditsRemaining} credits left";
```

### Image URL → white background JPG for an e-shop

```php
$result = $bg->removeUrl('https://example.com/product.jpg', [
    'bg_color'    => '#ffffff',
    'crop'        => true,
    'crop_margin' => 20,
    'format'      => 'jpg',
    'quality'     => 90,
]);
$result->save('product.jpg');
```

### Blurred background (portrait / bokeh)

```php
$bg->removeFile('selfie.jpg', ['model' => 'portrait', 'bg_blur' => 25])->save('selfie-bokeh.png');
```

### Bytes in memory, result as `data:` URI

```php
$result = $bg->removeBytes($pngBytes, ['size' => 'preview']);
echo '<img src="' . $result->dataUri() . '">';
```

### Account & history

```php
$a = $bg->account();   // ['email' => …, 'credits' => 42, 'free_remaining' => 5, 'free_per_month' => 5, …]
$bg->images();         // last 100 images processed through the API
```

### Error handling

```php
try {
    $bg->removeFile('photo.jpg');
} catch (BgCut\ApiException $e) {
    echo $e->errorCode;     // insufficient_credits, free_quota_exceeded, rate_limited, bad_type, …
    echo $e->httpStatus;    // 402, 429, 400 …
    if ($e->isOutOfCredits())  { /* https://bgcut.net/pricing */ }
    if ($e->isRateLimited())   { sleep($e->retryAfter ?? 60); }
}
```

## Options

| Option        | Values                              | Default | Description |
|---------------|-------------------------------------|---------|-------------|
| `size`        | `auto` `preview` `full`             | `auto`  | `preview` ≤ 640 px uses the free monthly quota, `full` = original resolution (1 credit), `auto` = full when you have credits |
| `model`       | `fast` `lite` `hq` `portrait`       | `lite`  | Speed vs. quality; `portrait` is tuned for people |
| `format`      | `png` `jpg` `webp`                  | `png`   | PNG/WebP keep transparency |
| `quality`     | 50–100                              | `92`    | JPG/WebP quality |
| `bg_color`    | `#rrggbb`                           | –       | Solid background colour |
| `bg_blur`     | 1–200                               | –       | Keep the original background, blurred by this radius (px). Overrides `bg_color` |
| `crop`        | `true` / `false`                    | `false` | Crop the output to the subject |
| `crop_margin` | 0–200                               | `0`     | Margin around the subject when cropping (px) |
| `roi`         | `[x, y, w, h]` fractions 0–1        | –       | Only look for the subject inside this region |
| `feather`     | 0–5                                 | –       | Soften edges (px) |

The `Result` object exposes `bytes`, `format`, `width`, `height`, `type` (`hd` or `free`), `creditsCharged`, `creditsRemaining`, `freeRemaining`, `imageId`, plus `save()`, `dataUri()` and `toGd()`.

## Examples

```bash
export BGCUT_API_KEY=bgc_xxx
php examples/01-remove-file.php photo.jpg          # one file → PNG
php examples/02-from-url-with-background.php       # URL → white background JPG, cropped
php examples/03-blur-background.php photo.jpg      # blurred background
php examples/04-bulk-folder.php ./photos ./out     # whole folder, rate-limit aware
php examples/05-account.php                        # balance + history
php -S localhost:8080 examples/06-web-upload-form.php   # tiny upload page
```

## Limits

* Max upload 25 MB, JPG / PNG / WebP / GIF / BMP / TIFF
* 60 requests per minute per API key (`429 rate_limited`, see `X-RateLimit-Remaining`)
* Results are kept in your account for 7 days

Full REST reference and OpenAPI spec: **https://bgcut.net/api** · `https://bgcut.net/api/v1/openapi.json`

## Links

* Website: https://bgcut.net
* Pricing: https://bgcut.net/pricing
* Support: support@bgcut.net

## License

MIT © [Webmint s.r.o.](https://bgcut.net) – use it freely in commercial and open-source projects.
