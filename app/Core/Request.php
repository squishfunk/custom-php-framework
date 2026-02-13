<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Request representation
 */
class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;

    /**
     * @param string $method HTTP method
     * @param string $path Request path
     * @param array $query Query parameters
     * @param array $post POST data
     */
    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $post = []
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->post = $post;
    }

    /**
     * Create Request from PHP globals
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        return new self(
            $method,
            $path,
            $_GET,
            $_POST
        );
    }

    /**
     * Get HTTP method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get query parameters
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Get POST data
     */
    public function getPost(): array
    {
        return $this->post;
    }

    public function input(string $key): ?string
    {
        return isset($this->post[$key]) ? $this->post[$key] : null;
    }

    public function all(): array
    {
        return $this->post;
    }
}
