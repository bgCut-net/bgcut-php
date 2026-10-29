<?php
declare(strict_types=1);

namespace BgCut;

/** A processed image returned by the API. */
final class Result
{
    /** Raw image bytes (PNG/JPG/WebP as requested). */
    public readonly string $bytes;
    public readonly string $format;
    public readonly int $width;
    public readonly int $height;
    /** 'hd' (full size, charged) or 'free' (preview, free quota). */
    public readonly string $type;
    public readonly int $creditsCharged;
    public readonly int $creditsRemaining;
    public readonly int $freeRemaining;
    /** Image id – the result stays available in your bgcut.net account for 7 days. */
    public readonly string $imageId;

    /** @param array<string,string> $headers lower-cased response headers */
    public function __construct(string $bytes, array $headers)
    {
        $this->bytes = $bytes;
        $this->format = match ($headers['content-type'] ?? 'image/png') {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
        $this->width = (int)($headers['x-width'] ?? 0);
        $this->height = (int)($headers['x-height'] ?? 0);
        $this->type = $headers['x-type'] ?? 'hd';
        $this->creditsCharged = (int)($headers['x-credits-charged'] ?? 0);
        $this->creditsRemaining = (int)($headers['x-credits-remaining'] ?? 0);
        $this->freeRemaining = (int)($headers['x-free-remaining'] ?? 0);
        $this->imageId = $headers['x-image-id'] ?? '';
    }

    /** Write the image to disk. */
    public function save(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create directory $dir");
        }
        if (file_put_contents($path, $this->bytes) === false) {
            throw new \RuntimeException("Cannot write $path");
        }
    }

    /** data: URI – handy for <img src="…"> previews. */
    public function dataUri(): string
    {
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'][$this->format];
        return 'data:' . $mime . ';base64,' . base64_encode($this->bytes);
    }

    /** GD image resource (requires ext-gd). */
    public function toGd(): \GdImage
    {
        $im = imagecreatefromstring($this->bytes);
        if (!$im) throw new \RuntimeException('GD could not decode the image');
        imagealphablending($im, false);
        imagesavealpha($im, true);
        return $im;
    }
}
