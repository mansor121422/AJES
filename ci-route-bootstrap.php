<?php

/**
 * Bootstrap CodeIgniter for a named route when mod_rewrite is unavailable (InfinityFree).
 *
 * Usage: create htdocs/{route}/index.php with:
 *   <?php require dirname(__DIR__) . '/ci-route-bootstrap.php'; ci_route_run('chat');
 */

declare(strict_types=1);

function ci_route_run(string $routePath): void
{
    $routePath = '/' . trim($routePath, '/');
    $root      = __DIR__;

    if (! is_file($root . '/index.php')) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'AJES bootstrap (index.php) not found in site root.';
        exit(1);
    }

    $query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
        ? '?' . $_SERVER['QUERY_STRING']
        : '';

    $_SERVER['SCRIPT_NAME']    = '/index.php';
    $_SERVER['REQUEST_URI']   = $routePath . $query;
    $_SERVER['PATH_INFO']      = $routePath;
    $_SERVER['PHP_SELF']       = '/index.php' . $routePath;

    chdir($root);
    require $root . '/index.php';
}
