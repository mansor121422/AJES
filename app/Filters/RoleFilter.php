<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $role    = $session->get('role');

        if (! $role) {
            return redirect()->to('/')->with('error', 'Please log in first.');
        }

        if ($arguments && ! in_array($role, $arguments, true)) {
            return redirect()->to('/')->with('error', 'You are not allowed to access that page.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

