<?php

declare(strict_types=1);

namespace Siro\Core;

final class Route
{
    public function __construct(
        private readonly Router $router,
        private readonly string $method,
        private readonly string $path
    ) {
    }

    /**
     * @param array<int, callable|string> $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->router->setRouteMiddleware($this->method, $this->path, $middleware);
        return $this;
    }

    public function cache(int $ttl = 60): self
    {
        $this->router->setRouteCacheTTL($this->method, $this->path, $ttl);
        return $this;
    }
}
