<?php

/**
 * Middleware Interface
 *
 * All middleware must implement this interface
 */
interface MiddlewareInterface
{
    /**
     * Handle the request
     *
     * @param callable $next Next middleware in the pipeline
     * @param array $params Route parameters
     * @return mixed
     */
    public function handle(callable $next, array $params = []);
}
