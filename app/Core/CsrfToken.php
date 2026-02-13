<?php

namespace App\Core;

class CsrfToken
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_TIME_KEY = '_csrf_token_time';
    private const TOKEN_LIFETIME = 3600; // 1 hour

    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::TOKEN_KEY] = $token;
        $_SESSION[self::TOKEN_TIME_KEY] = time();

        return $token;
    }

    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::TOKEN_KEY]) || self::isExpired()) {
            return self::generate();
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($token) || empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }

        if (self::isExpired()) {
            return false;
        }

        return $_SESSION[self::TOKEN_KEY] === $token;
    }

    private static function isExpired(): bool
    {
        if (empty($_SESSION[self::TOKEN_TIME_KEY])) {
            return true;
        }

        return (time() - $_SESSION[self::TOKEN_TIME_KEY]) > self::TOKEN_LIFETIME;
    }

    public static function clear(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::TOKEN_KEY], $_SESSION[self::TOKEN_TIME_KEY]);
    }
}
