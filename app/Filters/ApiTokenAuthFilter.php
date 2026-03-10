<?php

namespace App\Filters;

use App\Models\ApiTokenModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Use on API routes that require token authentication.
 * Expects header: Authorization: Bearer <token>
 * On success, sets request->api_user_id for use in API controllers.
 */
class ApiTokenAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');
        $token  = '';
        if (preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            $token = trim($m[1]);
        }

        if ($token === '') {
            return $this->unauthorized('Missing or invalid Authorization header.');
        }

        $model  = new ApiTokenModel();
        $userId = $model->getUserIdByToken($token);

        if ($userId === null) {
            return $this->unauthorized('Invalid or expired token.');
        }

        $request->api_user_id = $userId;

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function unauthorized(string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
    }
}
