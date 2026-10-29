<?php
declare(strict_types=1);

namespace BgCut;

/**
 * Thrown for every non-2xx API response.
 *
 * $e->errorCode: auth_required | invalid_key | bad_request | too_large | bad_type | bad_url |
 *                insufficient_credits | free_quota_exceeded | unprocessable | rate_limited |
 *                processing_failed | compose_failed | network_error
 * $e->httpStatus: 400, 401, 402, 422, 429, 500 … (0 for network errors)
 */
final class ApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $httpStatus,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $httpStatus);
    }

    public function isAuthError(): bool { return $this->httpStatus === 401; }
    public function isOutOfCredits(): bool { return $this->httpStatus === 402; }
    public function isRateLimited(): bool { return $this->httpStatus === 429; }
}
