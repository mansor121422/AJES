<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * JSON notifications for AJESCHAT (Alerts tab).
 */
class Notifications extends BaseController
{
    public function index(): ResponseInterface
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Please log in first.',
            ]);
        }

        $db = \Config\Database::connect();
        $rows = $db->table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        $items = array_map(static function (array $row): array {
            return [
                'id'          => (int) ($row['id'] ?? 0),
                'message'     => (string) ($row['message'] ?? ''),
                'created_at'  => (string) ($row['created_at'] ?? ''),
                'is_read'     => ! empty($row['is_read']),
                'type'        => (string) ($row['type'] ?? ''),
            ];
        }, $rows);

        return $this->response->setJSON(['items' => $items]);
    }

    public function markRead(): ResponseInterface
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        $id      = 0;

        $json = null;
        try {
            $json = $this->request->getJSON(true);
        } catch (HTTPException) {
            $json = null;
        }
        if (is_array($json) && isset($json['id'])) {
            $id = (int) $json['id'];
        } else {
            $id = (int) $this->request->getPost('id');
        }

        if ($userId < 1 || $id < 1) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }

        $db = \Config\Database::connect();
        $db->table('notifications')->where('id', $id)->where('user_id', $userId)->update(['is_read' => 1]);

        return $this->response->setJSON(['ok' => true]);
    }

    public function markAllRead(): ResponseInterface
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false]);
        }

        $db = \Config\Database::connect();
        $db->table('notifications')->where('user_id', $userId)->update(['is_read' => 1]);

        return $this->response->setJSON(['ok' => true]);
    }
}
