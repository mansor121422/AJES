<?php

namespace App\Controllers;

use App\Models\MessageModel;
use App\Models\UserModel;
use Config\BadWords;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Chat extends BaseController
{
    protected MessageModel $messages;
    protected UserModel $users;

    public function __construct()
    {
        $this->messages = new MessageModel();
        $this->users    = new UserModel();
        helper(['url', 'form']);
    }

    /**
     * Admin only: view all chat logs including unsent messages (actual content visible).
     */
    public function logs(): string
    {
        $role = session()->get('role');
        if ($role !== 'ADMIN') {
            return redirect()->to(base_url('dashboard/admin'))->with('error', 'Access denied.');
        }
        $limit  = 500;
        $rows   = $this->messages->getAllForAdmin($limit);
        $userIds = [];
        foreach ($rows as $m) {
            $userIds[(int) $m['sender_id']] = true;
            $userIds[(int) $m['receiver_id']] = true;
        }
        $userIds = array_keys($userIds);
        $users = [];
        foreach ($userIds as $id) {
            $u = $this->users->find($id);
            $users[$id] = $u ? ($u['name'] ?? $u['username'] ?? 'User #' . $id) : 'User #' . $id;
        }
        $data = [
            'role'    => 'ADMIN',
            'name'    => session()->get('name') ?? 'Administrator',
            'logs'    => $rows,
            'users'   => $users,
        ];
        return view('Chat/logs', $data);
    }

    public function index(): string
    {
        $userId = (int) session()->get('user_id');
        $withId = (int) $this->request->getGet('with');

        $chatUsers = $this->getChatUserList($userId);
        $conversation = [];
        $withUser = null;
        if ($withId > 0 && $withId !== $userId) {
            $withUser = $this->users->find($withId);
            if ($withUser) {
                $conversation = $this->messages->getConversation($userId, $withId);
                $this->markAsRead($userId, $withId);
            }
        }

        $data = [
            'role'         => session()->get('role') ?? 'ADMIN',
            'name'         => session()->get('name') ?? 'User',
            'current_id'   => $userId,
            'chat_users'   => $chatUsers,
            'with_user'    => $withUser,
            'conversation' => $conversation,
        ];
        return view('Chat/index', $data);
    }

    public function send(): RedirectResponse|ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return redirect()->to(base_url('auth/login'));
        }
        $receiverId = (int) $this->request->getPost('receiver_id');
        $content    = trim((string) $this->request->getPost('content'));
        if ($receiverId < 1 || $content === '') {
            return redirect()->back()->with('error', 'Invalid message or recipient.');
        }
        if ($receiverId === $userId) {
            return redirect()->back()->with('error', 'You cannot send a message to yourself.');
        }
        $receiver = $this->users->find($receiverId);
        if (! $receiver) {
            return redirect()->back()->with('error', 'Recipient not found.');
        }
        $contentOriginal = $content;
        $content         = $this->censorMessage($content);
        $this->messages->insert([
            'sender_id'        => $userId,
            'receiver_id'      => $receiverId,
            'content'          => $content,
            'content_original' => $contentOriginal,
            'status'           => 'SENT',
        ]);
        return redirect()->to(base_url('chat?with=' . $receiverId))->with('success', 'Message sent.');
    }

    /**
     * Unsend message: "for me" (hide for current user) or "for all" (soft-delete; sender only).
     */
    public function unsend(): RedirectResponse|ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return redirect()->to(base_url('auth/login'));
        }
        $messageId = (int) $this->request->getPost('message_id');
        $scope     = trim((string) $this->request->getPost('scope'));
        $withId    = (int) $this->request->getPost('with_id');

        if ($messageId < 1 || ! in_array($scope, ['me', 'all'], true)) {
            return redirect()->back()->with('error', 'Invalid request.');
        }

        $msg = $this->messages->withDeleted()->find($messageId);
        if (! $msg) {
            return redirect()->back()->with('error', 'Message not found.');
        }
        $senderId = (int) $msg['sender_id'];

        if ($scope === 'me') {
            $this->messages->hideForUser($messageId, $userId);
            $redirectWith = $withId > 0 ? base_url('chat?with=' . $withId) : base_url('chat');
            return redirect()->to($redirectWith)->with('success', 'Message unsent for you.');
        }

        if ($scope === 'all') {
            if ($senderId !== $userId) {
                return redirect()->back()->with('error', 'Only the sender can unsend for everyone.');
            }
            $this->messages->delete($messageId);
            $redirectWith = $withId > 0 ? base_url('chat?with=' . $withId) : base_url('chat');
            return redirect()->to($redirectWith)->with('success', 'Message unsent for everyone.');
        }

        return redirect()->back();
    }

    /**
     * JSON endpoint for polling new messages (for conversation with a given user).
     */
    public function getMessages(): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        $withId = (int) $this->request->getGet('with');
        if (! $userId || $withId < 1) {
            return $this->response->setJSON(['messages' => []]);
        }
        $messages = $this->messages->getConversation($userId, $withId);
        $out = [];
        foreach ($messages as $m) {
            $deleted = ! empty($m['deleted_at']);
            $out[] = [
                'id'              => (int) $m['id'],
                'sender_id'       => (int) $m['sender_id'],
                'receiver_id'     => (int) $m['receiver_id'],
                'content'         => $deleted ? '' : $m['content'],
                'created_at'      => $m['created_at'] ?? '',
                'is_mine'         => (int) $m['sender_id'] === $userId,
                'unsent_for_all'  => $deleted,
            ];
        }
        return $this->response->setJSON(['messages' => $out]);
    }

    /**
     * List of users the current user can chat with (all active users except self).
     */
    private function getChatUserList(int $currentUserId): array
    {
        $users = $this->users->orderBy('name')->findAll();
        $partnerIds = $this->messages->getConversationPartnerIds($currentUserId);
        $list = [];
        foreach ($users as $u) {
            $id = (int) $u['id'];
            if ($id === $currentUserId) {
                continue;
            }
            $list[] = [
                'id'       => $id,
                'name'     => $u['name'] ?? $u['username'] ?? 'User #' . $id,
                'role'     => $u['role'] ?? '',
                'has_chat' => in_array($id, $partnerIds, true),
            ];
        }
        return $list;
    }

    private function markAsRead(int $userId, int $otherUserId): void
    {
        $this->messages->where('receiver_id', $userId)->where('sender_id', $otherUserId)->set('status', 'READ')->update();
    }

    /**
     * Replace bad words (English + Filipino) with "****" in message content.
     */
    private function censorMessage(string $text): string
    {
        $words = BadWords::all();
        if ($words === []) {
            return $text;
        }
        foreach ($words as $word) {
            $word = preg_quote($word, '/');
            $text = preg_replace('/\b' . $word . '\b/iu', '****', $text);
        }
        return $text;
    }
}
