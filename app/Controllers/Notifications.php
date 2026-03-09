<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Notifications extends BaseController
{
    /**
     * Unread count for the current user (for the bell badge).
     */
    public function count(): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return $this->response->setJSON(['count' => 0]);
        }
        $db    = \Config\Database::connect();
        $row   = $db->table('notifications')->where('user_id', $userId)->where('is_read', 0)->countAllResults();
        return $this->response->setJSON(['count' => (int) $row]);
    }

    /**
     * List notifications for the current user (bell dropdown / notifications page).
     */
    public function index(): string|RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return redirect()->to(base_url('auth/login'));
        }
        $db   = \Config\Database::connect();
        $rows = $db->table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        $data = [
            'notifications' => $rows,
            'role'          => session()->get('role') ?? 'ADMIN',
            'name'          => session()->get('name') ?? 'User',
        ];
        return view('Notifications/index', $data);
    }

    /**
     * Mark a notification as read (GET link from notifications page).
     */
    public function markReadGet(int $id): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        if (! $userId || $id < 1) {
            return redirect()->to(base_url('notifications'));
        }
        $db = \Config\Database::connect();
        $db->table('notifications')->where('id', $id)->where('user_id', $userId)->update(['is_read' => 1]);
        return redirect()->to(base_url('notifications'))->with('success', 'Marked as read.');
    }

    /**
     * Mark a notification as read (POST/JSON for API).
     */
    public function markRead(): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        $id     = (int) $this->request->getPost('id');
        if (! $userId || $id < 1) {
            return $this->response->setJSON(['ok' => false]);
        }
        $db = \Config\Database::connect();
        $db->table('notifications')->where('id', $id)->where('user_id', $userId)->update(['is_read' => 1]);
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllRead(): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return redirect()->to(base_url('notifications'));
        }
        $db = \Config\Database::connect();
        $db->table('notifications')->where('user_id', $userId)->update(['is_read' => 1]);
        return redirect()->to(base_url('notifications'))->with('success', 'All notifications marked as read.');
    }
}
