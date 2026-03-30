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
        $attachment = $this->request->getFile('attachment');

        $hasText        = $content !== '';
        $hasAttachment = $attachment && $attachment->isValid() && $attachment->getSize() > 0;

        if ($receiverId < 1 || (! $hasText && ! $hasAttachment)) {
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

        $senderName = session()->get('name')
            ?? $this->users->find($userId)['name']
            ?? $this->users->find($userId)['username']
            ?? 'User #' . $userId;

        $attachmentType = null;
        $attachmentUrl  = null;
        $attachmentName = null;
        $attachmentMime = null;
        $attachmentSize = null;

        // Handle optional file upload.
        if ($hasAttachment) {
            $maxBytes = 10 * 1024 * 1024; // 10MB
            if ($attachment->getSize() > $maxBytes) {
                return $this->isApiRequest()
                    ? $this->response->setStatusCode(413)->setJSON(['status' => 'error', 'message' => 'Attachment too large (max 10MB).'])
                    : redirect()->back()->with('error', 'Attachment too large (max 10MB).');
            }

            $attachmentMime = (string) ($attachment->getClientMimeType() ?? $attachment->getMimeType() ?? 'application/octet-stream');
            $attachmentName = (string) ($attachment->getClientName() ?? 'attachment');
            $attachmentSize = (int) $attachment->getSize();

            if (str_starts_with($attachmentMime, 'image/')) {
                $attachmentType = 'image';
            } elseif (str_starts_with($attachmentMime, 'video/')) {
                $attachmentType = 'video';
            } else {
                $attachmentType = 'file';
            }

            $ext = strtolower((string) ($attachment->getClientExtension() ?? ''));
            $ext = preg_replace('/[^a-z0-9]/i', '', $ext);
            if ($ext === '') {
                $parts = explode('/', $attachmentMime);
                $ext = isset($parts[1]) ? preg_replace('/[^a-z0-9]/i', '', $parts[1]) : '';
            }
            if ($ext === '') {
                $ext = $attachmentType === 'image' ? 'jpg' : ($attachmentType === 'video' ? 'mp4' : 'bin');
            }

            $uploadDir = FCPATH . 'public/assets/chat_uploads';
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $attachment->move($uploadDir, $fileName);

            $attachmentUrl = base_url('assets/chat_uploads/' . $fileName);
        }

        $contentOriginal = $hasText ? $content : null;
        $contentCensored = $hasText ? $this->censorMessage($content) : '';
        $wasCensored = $hasText && ($contentCensored !== $contentOriginal);

        $this->messages->insert([
            'sender_id'        => $userId,
            'receiver_id'      => $receiverId,
            'content'          => $contentCensored,
            'content_original' => $contentOriginal,
            'attachment_type' => $attachmentType,
            'attachment_url'  => $attachmentUrl,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
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
                'attachment_type' => $deleted ? null : ($m['attachment_type'] ?? null),
                'attachment_url'  => $deleted ? null : ($m['attachment_url'] ?? null),
                'attachment_name' => $deleted ? null : ($m['attachment_name'] ?? null),
                'attachment_mime' => $deleted ? null : ($m['attachment_mime'] ?? null),
                'attachment_size' => $deleted ? null : ($m['attachment_size'] ?? null),
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
        $userId = (int) ($this->request->getGet('current') ?? session()->get('user_id'));
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON(['users' => []]);
        }
        $list = $this->getChatUserList($userId);
        return $this->response->setJSON(['users' => $list]);
    }

    /**
     * POST api/chat/typing
     * Body JSON: { "to": <user_id>, "typing": 1|0 }
     * Marks the current user typing state against a chat partner.
     */
    public function setTypingApi(): ResponseInterface
    {
        $sessionUserId = (int) session()->get('user_id');
        if ($sessionUserId < 1) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Not logged in.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [
                'to'      => $this->request->getPost('to'),
                'typing'  => $this->request->getPost('typing'),
            ];
        }

        $fromUserId = (int) ($payload['from_user_id'] ?? $payload['from'] ?? $sessionUserId);

        $toUserId = (int) ($payload['to'] ?? 0);
        $typing   = (int) ($payload['typing'] ?? 1);

        if ($toUserId < 1 || $toUserId === $fromUserId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid typing target.']);
        }

        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();

        try {
            $sql = "INSERT INTO typing_indicators (from_user_id, to_user_id, is_typing, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        is_typing = VALUES(is_typing),
                        updated_at = VALUES(updated_at)";
            $db->query($sql, [$fromUserId, $toUserId, $typing ? 1 : 0, $now, $now]);
        } catch (\Throwable $e) {
            // Typing is non-critical; never break chat.
            log_message('error', 'Typing update failed: ' . $e->getMessage());
        }

        if ($typing === 1) {
            log_message('info', 'Typing indicator ON: from_user_id=' . $fromUserId . ' to_user_id=' . $toUserId);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * GET api/chat/typing?with=<partner_id>
     * Returns whether the partner is currently typing to the logged-in user.
     */
    public function getTypingApi(): ResponseInterface
    {
        $toUserId = (int) ($this->request->getGet('to') ?? session()->get('user_id'));
        if ($toUserId < 1) {
            return $this->response->setStatusCode(401)->setJSON(['typing' => false]);
        }

        $fromUserId = (int) $this->request->getGet('with');
        if ($fromUserId < 1 || $fromUserId === $toUserId) {
            return $this->response->setJSON(['typing' => false]);
        }

        $typingTimeoutSeconds = 6;
        $threshold = date('Y-m-d H:i:s', time() - $typingTimeoutSeconds);
        $db        = \Config\Database::connect();

        try {
            $row = $db->table('typing_indicators')
                ->select('is_typing, updated_at')
                ->where('from_user_id', $fromUserId)
                ->where('to_user_id', $toUserId)
                ->get()
                ->getRowArray();

            $isTyping = false;
            if (is_array($row) && (int) ($row['is_typing'] ?? 0) === 1) {
                $updatedAt = $row['updated_at'] ?? null;
                $updatedTs = $updatedAt ? strtotime((string) $updatedAt) : null;
                $isTyping  = $updatedTs !== null && $updatedTs >= strtotime((string) $threshold);
            }

            return $this->response->setJSON(['typing' => $isTyping]);
        } catch (\Throwable $e) {
            // If the table doesn't exist yet, just report "not typing".
            return $this->response->setJSON(['typing' => false]);
        }
    }

    /**
     * List of users the current user can chat with (all active users except self).
     */
    private function getChatUserList(int $currentUserId): array
    {
        $now            = time();
        $timeoutSeconds = (int) config('App')->presenceTimeoutSeconds;
        $hasPresenceColumns = $this->usersHasPresenceColumns();
        $typingTimeoutSeconds = 6;
        $typingByFromUserId = $this->getTypingByFromUserIds($currentUserId, $typingTimeoutSeconds);

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
            $isTyping = (bool) ($typingByFromUserId[$id] ?? false);
            $list[] = [
                'id'          => $id,
                'name'        => $u['name'] ?? $u['username'] ?? 'User #' . $id,
                'role'        => $u['role'] ?? '',
                'has_chat'    => in_array($id, $partnerIds, true),
                'unread'      => $unreadBySender[$id] ?? 0,
                'has_unread'  => ($unreadBySender[$id] ?? 0) > 0,
                'presence_state' => $presence['state'],
                'presence_label' => $presence['label'],
                'typing'      => $isTyping,
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

    /**
     * Get typing state for all users typing TO the current user.
     *
     * Returns: [from_user_id => bool]
     */
    private function getTypingByFromUserIds(int $toUserId, int $timeoutSeconds): array
    {
        static $cachedToUserId = null;
        static $cachedTimeoutSeconds = null;
        static $cached = [];

        // Cache per request for the "chat sidebar" list so we don't re-query per user.
        if ($cachedToUserId === $toUserId && $cachedTimeoutSeconds === $timeoutSeconds) {
            return $cached;
        }

        $cachedToUserId = $toUserId;
        $cachedTimeoutSeconds = $timeoutSeconds;

        $cached = [];
        $hasTypingTable = $this->typingTableExists();
        if (! $hasTypingTable) {
            return $cached;
        }

        $threshold = date('Y-m-d H:i:s', time() - $timeoutSeconds);
        $db = \Config\Database::connect();

        try {
            // Only need currently-typing rows.
            // Assumes `typing_indicators.updated_at` is refreshed when "typing" is set.
            $rows = $db->table('typing_indicators')
                ->select('from_user_id')
                ->where('to_user_id', $toUserId)
                ->where('is_typing', 1)
                ->where('updated_at >=', $threshold)
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $fid = (int) ($r['from_user_id'] ?? 0);
                if ($fid > 0) {
                    $cached[$fid] = true;
                }
            }
        } catch (\Throwable $e) {
            // Typing is non-critical; fall back to all false.
            return [];
        }

        return $cached;
    }

    private function typingTableExists(): bool
    {
        $db = \Config\Database::connect();
        $has = $db->query("SHOW TABLES LIKE 'typing_indicators'")->getNumRows() > 0;
        return $has;
    }
}
