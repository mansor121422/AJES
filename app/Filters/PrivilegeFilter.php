<?php

namespace App\Filters;

use App\Libraries\AdminPrivilege;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PrivilegeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        if ($role === '') {
            return null;
        }

        $required = is_array($arguments) ? (string) ($arguments[0] ?? '') : '';
        if ($required === '') {
            return null;
        }

        // Support privilege:feature:action format (e.g. privilege:user_management:delete)
        $action = is_array($arguments) && isset($arguments[1]) ? (string) $arguments[1] : '';
        $check  = $action !== '' ? $required . ':' . $action : $required;

        $assigned = $session->get('feature_privileges');
        if ($assigned === null) {
            $assigned = $session->get('admin_privileges');
        }
        if (AdminPrivilege::canAccess($role, $assigned, $check)) {
            return null;
        }

        return redirect()->to(base_url('chat'))->with('error', 'You are not allowed to access that feature.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
