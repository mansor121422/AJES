<?php

namespace App\Filters;

use App\Libraries\ActivityLogger;
use App\Libraries\SessionTracker;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Automatically logs every authenticated page visit and updates session heartbeat.
 */
class ActivityLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return null;
        }

        ActivityLogger::pageVisit($userId);
        SessionTracker::heartbeat($userId);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
