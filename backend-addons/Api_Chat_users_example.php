<?php
/**
 * Example: GET api/chat/users for AJESCHAT Android
 *
 * Copy this logic into your AJES project. In CodeIgniter 4 you might have:
 *   app/Controllers/Api/Chat.php with a users() method,
 *   and route: $routes->get('api/chat/users', 'Api\Chat::users');
 *
 * Replace getChatUserList() with your actual method that returns
 * "all active users except current user" and optionally has_chat.
 */

// Pseudo-code – adapt to your framework and DB:

function getChatUsersForApi() {
    $currentUserId = session()->get('user_id'); // or your session key
    if (!$currentUserId) {
        return $this->response->setJSON(['users' => []])->setStatusCode(401);
    }

    // Use the same logic as your web Chat::index getChatUserList()
    $userList = $this->getChatUserList(); // your existing method

    $users = [];
    foreach ($userList as $u) {
        $users[] = [
            'id'       => (int) $u['id'],
            'name'     => $u['name'],
            'role'     => $u['role'] ?? '',
            'has_chat' => (bool) ($u['has_chat'] ?? false),
        ];
    }

    return $this->response->setJSON(['users' => $users]);
}
