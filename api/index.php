<?php

/**
 * Fallback when /api/ is requested without a path segment.
 */
require dirname(__DIR__) . '/ci-route-bootstrap.php';
ci_route_run('api');
