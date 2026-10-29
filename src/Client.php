<?php
declare(strict_types=1);

namespace BgCut;

/**
 * bgCut.net API client – remove image backgrounds with AI.
 *
 * Zero dependencies (PHP 8.1+, ext-curl, ext-json).
 * Docs: https://bgcut.net/api  ·  Get an API key: https://bgcut.net/api-keys
 *
 *   $bg = new \BgCut\Client('bgc_your_api_key');
 *   $bg->removeToFile('photo.jpg', 'photo-nobg.png');
 */
final class Client
{
    public const VERSION = '1.0.0';
    public const DEFAULT_BASE_URL = 'https://bgcut.net/api/v1';

    /** Available AI models (speed vs. quality). */
    public const MODEL_FAST = 'fast';         // ≈0.5 s
    public const MODEL_LITE = 'lite';         // ≈6 s, best balance (default)
    public const MODEL_HQ = 'hq';             // ≈15 s, highest quality
    public const MODEL_PORTRAIT = 'portrait'; // ≈0.3 s, people only

    /** Output size. */
    public const SIZE_AUTO = 'auto';       // full when you have credits, otherwise preview
    public const SIZE_PREVIEW = 'preview'; // up to 640 px – uses the free monthly quota
    public const SIZE_FULL = 'full';       // original resolution – 1 credit

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private ?Result $last = null;

    public function __construct(string $apiKey, string $baseUrl = self::DEFAULT_BASE_URL, int $timeout = 120)
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('API key is required – create one at https://bgcut.net/api-keys');
        }
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Remove the background of a local image file.
     *
     * @param string $path    Path to a JPG/PNG/WebP/GIF/BMP/TIFF file (max 25 MB)
     * @param array  $options See OPTIONS in README (size, format, model, bg_color, bg_blur, crop, crop_margin, roi, feather, quality)
     */
    public function removeFile(string $path, array $options = []): Result
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException("File not found or unreadable: $path");
        }
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $options['image_file'] = new \CURLFile($path, $mime, basename($path));
        return $this->remove($options);
    }

    /** Remove the background of an image available on a public URL. */
    public function removeUrl(string $url, array $options = []): Result
    {
        $options['image_url'] = $url;
        return $this->remove($options);
    }

    /** Remove the background of raw image bytes already in memory. */
    public function removeBytes(string $bytes, array $options = []): Result
    {
        $options['image_file_b64'] = base64_encode($bytes);
        return $this->remove($options);
    }

    /** Convenience: process a local file and write the result next to it (or to $outPath). Returns the written path. */
    public function removeToFile(string $inPath, ?string $outPath = null, array $options = []): string
    {
        $result = $this->removeFile($inPath, $options);
        $outPath ??= preg_replace('/\.[a-z0-9]+$/i', '', $inPath) . '-nobg.' . $result->format;
        $result->save($outPath);
        return $outPath;
    }

    /** Account balance, free quota and limits. */
    public function account(): array
    {
        return $this->requestJson('GET', '/account');
    }

    /** Last 100 images processed through the API. */
    public function images(): array
    {
        return $this->requestJson('GET', '/images')['images'] ?? [];
    }

    /** Result of the most recent remove*() call (handy for reading credit headers). */
    public function lastResult(): ?Result
    {
        return $this->last;
    }

    // ------------------------------------------------------------------ internals

    private function remove(array $fields): Result
    {
        foreach ($fields as $k => $v) {
            if (is_bool($v)) $fields[$k] = $v ? '1' : '0';
            elseif (is_array($v)) $fields[$k] = implode(',', $v); // roi => [x, y, w, h]
        }
        $fields['response'] = 'binary';
        [$status, $headers, $body] = $this->request('POST', '/remove', $fields, true);
        $this->ensureOk($status, $headers, $body);
        return $this->last = new Result($body, $headers);
    }

    private function requestJson(string $method, string $path): array
    {
        [$status, $headers, $body] = $this->request($method, $path);
        $this->ensureOk($status, $headers, $body);
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new ApiException('Invalid JSON response from the API.', 'bad_response', $status);
        }
        return $json;
    }

    /** @return array{0:int,1:array<string,string>,2:string} */
    private function request(string $method, string $path, array $fields = [], bool $multipart = false): array
    {
        $ch = curl_init($this->baseUrl . $path);
        $headers = [
            'X-Api-Key: ' . $this->apiKey,
            'Accept: application/json, image/*',
            'User-Agent: bgcut-php/' . self::VERSION . ' (+https://github.com/webmint/bgcut-php)',
        ];
        $respHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$respHeaders): int {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $respHeaders[strtolower(trim($k))] = trim($v);
                }
                return strlen($line);
            },
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); // array => multipart/form-data (needed for CURLFile)
        }
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new ApiException('Network error: ' . $err, 'network_error', 0);
        }
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return [$status, $respHeaders, (string)$body];
    }

    private function ensureOk(int $status, array $headers, string $body): void
    {
        if ($status >= 200 && $status < 300) return;
        $json = json_decode($body, true);
        $code = is_array($json) ? (string)($json['error'] ?? 'http_' . $status) : 'http_' . $status;
        $msg = is_array($json) ? (string)($json['message'] ?? $code) : ('HTTP ' . $status);
        throw new ApiException($msg, $code, $status, isset($headers['retry-after']) ? (int)$headers['retry-after'] : null);
    }
}
