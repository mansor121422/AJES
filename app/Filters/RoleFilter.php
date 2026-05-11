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

        $role = strtoupper((string) $role);
        $allowed = array_map(static fn ($r): string => strtoupper((string) $r), is_array($arguments) ? $arguments : []);

        // SUPER_ADMIN can pass all role checks.
        if ($role === 'SUPER_ADMIN') {
            return null;
        }

        if ($allowed && ! in_array($role, $allowed, true)) {
            return redirect()->to('/')->with('error', 'You are not allowed to access that page.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

