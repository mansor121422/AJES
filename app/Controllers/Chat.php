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
        helper(['url', 'form', 'text']);
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
                $now = time();
                $timeoutSeconds = (int) config('App')->presenceTimeoutSeconds;
                $presence = $this->computePresence($withUser, $now, $timeoutSeconds);
                $withUser['presence_state']  = $presence['state'];
                $withUser['presence_label']  = $presence['label'];
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
            return $this->isApiRequest()
                ? $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Not logged in.'])
                : redirect()->to(base_url('auth/login'));
        }
        $receiverId = (int) $this->request->getPost('receiver_id');
        $content    = trim((string) $this->request->getPost('content'));
        if ($receiverId < 1 || $content === '') {
            return $this->isApiRequest()
                ? $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid message or recipient.'])
                : redirect()->back()->with('error', 'Invalid message or recipient.');
        }
        if ($receiverId === $userId) {
            return $this->isApiRequest()
                ? $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'You cannot send a message to yourself.'])
                : redirect()->back()->with('error', 'You cannot send a message to yourself.');
        }
        $receiver = $this->users->find($receiverId);
        if (! $receiver) {
            return $this->isApiRequest()
                ? $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Recipient not found.'])
                : redirect()->back()->with('error', 'Recipient not found.');
        }
        $senderName      = session()->get('name') ?? $this->users->find($userId)['name'] ?? $this->users->find($userId)['username'] ?? 'User #' . $userId;
        $contentOriginal = $content;
        $content         = $this->censorMessage($content);
        $wasCensored     = ($content !== $contentOriginal);
        $this->messages->insert([
            'sender_id'        => $userId,
            'receiver_id'      => $receiverId,
            'content'          => $content,
            'content_original' => $contentOriginal,
            'status'           => 'SENT',
        ]);
        $messageId = (int) $this->messages->getInsertID();

        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Notify all admins when someone sends a censored message (for the ring/bell notification).
        if ($wasCensored && $messageId > 0) {
            $admins     = $db->table('users')->select('id')->where('role', 'ADMIN')->where('is_active', 1)->get()->getResultArray();
            $notifMsg   = 'Chat message for you (censored): from ' . character_limiter($senderName, 30);
            foreach ($admins as $row) {
                $db->table('notifications')->insert([
                    'user_id'          => (int) $row['id'],
                    'type'             => 'censored_chat',
                    'reference_table'  => 'messages',
                    'reference_id'     => $messageId,
                    'message'          => $notifMsg,
                    'is_read'          => 0,
                    'created_at'       => $now,
                ]);
            }
        }

        // Notify the receiver about a new chat message (bell + notifications page).
        if ($messageId > 0) {
            $chatNotif = 'New chat message for you from ' . character_limiter($senderName, 30);
            $db->table('notifications')->insert([
                'user_id'          => $receiverId,
                'type'             => 'chat',
                'reference_table'  => 'messages',
                'reference_id'     => $messageId,
                'message'          => $chatNotif,
                'is_read'          => 0,
                'created_at'       => $now,
            ]);
        }

        // API client (e.g. Android app): return JSON instead of redirect (avoids HTTP 303)
        if ($this->isApiRequest()) {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Message sent.']);
        }
        return redirect()->to(base_url('chat?with=' . $receiverId))->with('success', 'Message sent.');
    }

    /**
     * Unsend message: "for me" (hide for current user) or "for all" (soft-delete; sender only).
     */
    public function unsend(): RedirectResponse|ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return $this->isApiRequest()
                ? $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Not logged in.'])
                : redirect()->to(base_url('auth/login'));
        }
        $messageId = (int) $this->request->getPost('message_id');
        $scope     = trim((string) $this->request->getPost('scope'));
        $withId    = (int) $this->request->getPost('with_id');

        if ($messageId < 1 || ! in_array($scope, ['me', 'all'], true)) {
            return $this->isApiRequest()
                ? $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid request.'])
                : redirect()->back()->with('error', 'Invalid request.');
        }

        $msg = $this->messages->withDeleted()->find($messageId);
        if (! $msg) {
            return $this->isApiRequest()
                ? $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Message not found.'])
                : redirect()->back()->with('error', 'Message not found.');
        }
        $senderId = (int) $msg['sender_id'];

        if ($scope === 'me') {
            $this->messages->hideForUser($messageId, $userId);
            if ($this->isApiRequest()) {
                return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Message unsent for you.']);
            }
            $redirectWith = $withId > 0 ? base_url('chat?with=' . $withId) : base_url('chat');
            return redirect()->to($redirectWith)->with('success', 'Message unsent for you.');
        }

        if ($scope === 'all') {
            if ($senderId !== $userId) {
                return $this->isApiRequest()
                    ? $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Only the sender can unsend for everyone.'])
                    : redirect()->back()->with('error', 'Only the sender can unsend for everyone.');
            }
            $this->messages->delete($messageId);
            if ($this->isApiRequest()) {
                return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Message unsent for everyone.']);
            }
            $redirectWith = $withId > 0 ? base_url('chat?with=' . $withId) : base_url('chat');
            return redirect()->to($redirectWith)->with('success', 'Message unsent for everyone.');
        }

        return $this->isApiRequest()
            ? $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid request.'])
            : redirect()->back();
    }

    /**
     * True when request is from API client (e.g. Android with Bearer token).
     */
    private function isApiRequest(): bool
    {
        return $this->request->getHeaderLine('Authorization') !== ''
            || str_contains($this->request->getHeaderLine('Accept'), 'application/json');
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
                'status'          => $m['status'] ?? 'SENT',
            ];
        }
        return $this->response->setJSON(['messages' => $out]);
    }

    /**
     * GET api/chat/users – JSON list for Android app (same data as web chat list).
     * Requires auth (session or Bearer token).
     */
    public function getChatUsersApi(): ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON(['users' => []]);
        }
        $list = $this->getChatUserList($userId);
        return $this->response->setJSON(['users' => $list]);
    }

    /**
     * List of users the current user can chat with (all active users except self).
     */
    private function getChatUserList(int $currentUserId): array
    {
        $now            = time();
        $timeoutSeconds = (int) config('App')->presenceTimeoutSeconds;
        $hasPresenceColumns = $this->usersHasPresenceColumns();

        $select = $hasPresenceColumns
            ? 'id, name, username, role, is_online, last_seen_at'
            : 'id, name, username, role';

        $users = $this->users
            ->select($select)
            ->orderBy('name')
            ->findAll();
        $partnerIds = $this->messages->getConversationPartnerIds($currentUserId);

        // Unread counts per sender (messages sent TO current user that are not READ yet).
        $unreadRows = $this->messages
            ->select('sender_id, COUNT(*) AS unread_count')
            ->where('receiver_id', $currentUserId)
            ->where('status !=', 'READ')
            ->groupBy('sender_id')
            ->findAll();
        $unreadBySender = [];
        foreach ($unreadRows as $row) {
            $sid = (int) ($row['sender_id'] ?? 0);
            if ($sid > 0) {
                $unreadBySender[$sid] = (int) ($row['unread_count'] ?? 0);
            }
        }

        $list = [];
        foreach ($users as $u) {
            $id = (int) $u['id'];
            if ($id === $currentUserId) {
                continue;
            }

            $presence = $this->computePresence($u, $now, $timeoutSeconds);
            $list[] = [
                'id'          => $id,
                'name'        => $u['name'] ?? $u['username'] ?? 'User #' . $id,
                'role'        => $u['role'] ?? '',
                'has_chat'    => in_array($id, $partnerIds, true),
                'unread'      => $unreadBySender[$id] ?? 0,
                'has_unread'  => ($unreadBySender[$id] ?? 0) > 0,
                'presence_state' => $presence['state'],
                'presence_label' => $presence['label'],
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

    private function computePresence(array $userRow, int $now, int $timeoutSeconds): array
    {
        $isOnlineFlag = (int) ($userRow['is_online'] ?? 0);
        $lastSeenAt   = $userRow['last_seen_at'] ?? null;
        $lastSeenTs   = $lastSeenAt ? strtotime((string) $lastSeenAt) : null;

        // Consider a user "online" only when:
        // - server explicitly marked them online (`is_online=1`)
        // - and their last seen timestamp is still within the timeout window.
        if ($isOnlineFlag === 1 && $lastSeenTs !== null && $lastSeenTs >= ($now - $timeoutSeconds)) {
            return ['state' => 'online', 'label' => 'Active now'];
        }

        if ($lastSeenTs === null) {
            return ['state' => 'offline', 'label' => 'Offline'];
        }

        $diffSeconds = $now - $lastSeenTs;
        if ($diffSeconds < 0) {
            $diffSeconds = 0;
        }

        return [
            'state' => 'offline',
            'label' => 'Last active: ' . $this->formatTimeAgo($diffSeconds),
        ];
    }

    private function formatTimeAgo(int $diffSeconds): string
    {
        if ($diffSeconds < 30) {
            return 'just now';
        }
        if ($diffSeconds < 60) {
            return $diffSeconds . ' seconds ago';
        }
        if ($diffSeconds < 3600) {
            $m = (int) floor($diffSeconds / 60);
            return $m . ' ' . ($m === 1 ? 'minute' : 'minutes') . ' ago';
        }
        if ($diffSeconds < 86400) {
            $h = (int) floor($diffSeconds / 3600);
            return $h . ' ' . ($h === 1 ? 'hour' : 'hours') . ' ago';
        }

        $d = (int) floor($diffSeconds / 86400);
        return $d . ' ' . ($d === 1 ? 'day' : 'days') . ' ago';
    }

    /**
     * Presence columns may not exist yet if migrations weren't run.
     * We detect them once per request and then safely select/update.
     */
    private function usersHasPresenceColumns(): bool
    {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }

        $db = \Config\Database::connect();
        $hasIsOnline = $db->query("SHOW COLUMNS FROM `users` LIKE 'is_online'")->getNumRows() > 0;
        $hasLastSeen = $db->query("SHOW COLUMNS FROM `users` LIKE 'last_seen_at'")->getNumRows() > 0;

        $checked = $hasIsOnline && $hasLastSeen;
        return $checked;
    }
}
