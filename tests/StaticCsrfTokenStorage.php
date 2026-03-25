<?php

namespace App\Tests;

use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * In-memory CSRF token storage for functional tests.
 * Stores tokens in a static array so they are shared across
 * getCsrfToken() calls and HTTP request cycles.
 */
class StaticCsrfTokenStorage implements TokenStorageInterface
{
    private static array $tokens = [];

    public function getToken(string $tokenId): string
    {
        if (!isset(self::$tokens[$tokenId])) {
            throw new \Symfony\Component\Security\Csrf\Exception\TokenNotFoundException();
        }

        return self::$tokens[$tokenId];
    }

    public function setToken(string $tokenId, string $token): void
    {
        self::$tokens[$tokenId] = $token;
    }

    public function removeToken(string $tokenId): ?string
    {
        $token = self::$tokens[$tokenId] ?? null;
        unset(self::$tokens[$tokenId]);

        return $token;
    }

    public function hasToken(string $tokenId): bool
    {
        return isset(self::$tokens[$tokenId]);
    }

    public static function clear(): void
    {
        self::$tokens = [];
    }
}
