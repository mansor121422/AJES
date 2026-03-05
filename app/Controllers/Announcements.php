<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Announcements extends BaseController
{
    protected AnnouncementModel $announcements;
    protected UserModel $users;

    public function __construct()
    {
        $this->announcements = new AnnouncementModel();
        $this->users        = new UserModel();
        helper(['url', 'form', 'text']);
    }

    public function index(): string
    {
        $list = $this->announcements->orderBy('created_at', 'DESC')->findAll();
        $data = [
            'announcements' => $list,
            'role'          => session()->get('role') ?? 'ADMIN',
            'name'          => session()->get('name') ?? 'User',
        ];
        return view('Announcements/index', $data);
    }

    public function create(): string|RedirectResponse
    {
        if (! $this->canManageAnnouncements()) {
            return redirect()->to(base_url('announcements'))->with('error', 'You do not have permission to create announcements.');
        }
        $data = [
            'role' => session()->get('role') ?? 'ADMIN',
            'name' => session()->get('name') ?? 'User',
        ];
        return view('Announcements/create', $data);
    }

    public function store(): RedirectResponse
    {
        if (! $this->canManageAnnouncements()) {
            return redirect()->to(base_url('announcements'))->with('error', 'You do not have permission to create announcements.');
        }
        $title   = trim((string) $this->request->getPost('title'));
        $body    = trim((string) $this->request->getPost('body'));
        $userId  = (int) session()->get('user_id');

        if ($title === '' || $body === '') {
            return redirect()->back()->withInput()->with('error', 'Title and body are required.');
        }

        $this->announcements->insert([
            'title'         => $title,
            'body'          => $body,
            'created_by'    => $userId,
            'status'        => 'ACTIVE',
            'audience_type' => 'school-wide',
        ]);

        $id = $this->announcements->getInsertID();
        $message = character_limiter($title, 80);
        $db = \Config\Database::connect();
        $userIds = $db->table('users')->select('id')->where('is_active', 1)->get()->getResultArray();
        foreach ($userIds as $row) {
            $db->table('notifications')->insert([
                'user_id'         => (int) $row['id'],
                'type'            => 'announcement',
                'reference_table' => 'announcements',
                'reference_id'   => $id,
                'message'        => 'New announcement: ' . $message,
                'is_read'        => 0,
            ]);
        }

        return redirect()->to(base_url('announcements'))->with('success', 'Announcement published. All users have been notified.');
    }

    public function edit(int $id): string|RedirectResponse
    {
        if (! $this->canManageAnnouncements()) {
            return redirect()->to(base_url('announcements'))->with('error', 'You do not have permission to edit announcements.');
        }
        $ann = $this->announcements->find($id);
        if (! $ann) {
            return redirect()->to(base_url('announcements'))->with('error', 'Announcement not found.');
        }
        $data = [
            'announcement' => $ann,
            'role'         => session()->get('role') ?? 'ADMIN',
            'name'         => session()->get('name') ?? 'User',
        ];
        return view('Announcements/edit', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $ann = $this->announcements->find($id);
        if (! $ann) {
            return redirect()->to(base_url('announcements'))->with('error', 'Announcement not found.');
        }
        $title = trim((string) $this->request->getPost('title'));
        $body  = trim((string) $this->request->getPost('body'));
        if ($title === '' || $body === '') {
            return redirect()->back()->withInput()->with('error', 'Title and body are required.');
        }
        $this->announcements->update($id, ['title' => $title, 'body' => $body]);
        return redirect()->to(base_url('announcements'))->with('success', 'Announcement updated.');
    }

    public function delete(int $id): RedirectResponse
    {
        if (! $this->canManageAnnouncements()) {
            return redirect()->to(base_url('announcements'))->with('error', 'You do not have permission to delete announcements.');
        }
        $ann = $this->announcements->find($id);
        if (! $ann) {
            return redirect()->to(base_url('announcements'))->with('error', 'Announcement not found.');
        }
        $this->announcements->delete($id);
        return redirect()->to(base_url('announcements'))->with('success', 'Announcement deleted.');
    }

    private function canManageAnnouncements(): bool
    {
        $role = session()->get('role');
        return in_array($role, ['ADMIN', 'PRINCIPAL', 'ANNOUNCER'], true);
    }
}
