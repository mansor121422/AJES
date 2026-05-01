# AJES Backend Add-ons for AJESCHAT Android

Copy these into your existing AJES project at `F:\xampp\htdocs\AJES` (or your AJES root) so the Android app can use the same backend with minimal changes.

## 1. GET api/chat/users (recommended)

The Android app calls `GET {baseUrl}api/chat/users` to get the list of users to chat with (same data as the web chat list).

- **Option A – CodeIgniter 4:** Add a route and controller method that returns the same data as `getChatUserList()` in your existing `Chat::index`.
- **Option B – Use the provided `Api/Chat.php`** (see below) and register the route `get('api/chat/users', 'Api\Chat::users')` (or equivalent in your framework).

Response format (JSON):

```json
{
  "users": [
    { "id": 1, "name": "Juan Dela Cruz", "role": "STUDENT", "has_chat": true },
    { "id": 2, "name": "Maria Santos", "role": "TEACHER", "has_chat": false, "last_message": "Hello", "last_message_at": "12:54 AM", "pinned": false, "active_status": "30m" }
  ]
}
```

- `id` – user id (integer)
- `name` – display name
- `role` – role string (e.g. STUDENT, TEACHER)
- `has_chat` – optional; true if there is at least one message between current user and this user
- Optional for chat rows: `last_message`, `last_message_at` (display string), `pinned`, `active_status` (e.g. `30m` for a green badge on the avatar)

Use the same logic as your web `getChatUserList()` (e.g. all active users except current user; optionally set `has_chat` from existing conversations).

## 2. Optional: JSON login response

If you want the app to avoid parsing redirects, add a JSON response for `POST auth/login` when the client sends `Accept: application/json`.

- On **success:** HTTP 200, body e.g. `{ "success": true, "user": { "id": 1, "name": "Juan", "role": "STUDENT" } }`
- On **failure:** HTTP 200, body e.g. `{ "success": false, "error": "Invalid credentials." }`  
  Use the same strings as the web:  
  - "Username and password are required."  
  - "Invalid credentials."  
  - "Account locked. Please contact administrator."

The Android app currently detects success by 302 + dashboard URL + session cookie; if you add this JSON response, you can later update the app to use it and still stay compatible with the web.

## 3. CSRF

- The app fetches a page (e.g. `GET /chat`) and parses the CSRF token from the HTML (e.g. `csrf_test_name` or meta `csrf-token`), then sends it with every POST (`auth/login`, `chat/send`, `chat/unsend`).
- Use the same field name as the web (e.g. `csrf_test_name`). No backend change needed if the web already sends CSRF for those actions.
- Alternatively, you can add a filter exception for API routes used only by the app (if you use dedicated API paths).

## 4. Chat endpoints (already existing)

The app uses the same endpoints as the web:

- `GET chat/messages?with={userId}` – same response (e.g. `{ "messages": [ { "id", "sender_id", "receiver_id", "content", "created_at", "is_mine", "unsent_for_all" } ] }`).
- `POST chat/send` – body: `receiver_id`, `content`, and CSRF if required.
- `POST chat/unsend` – body: `message_id`, `scope` (`me` or `all`), `with_id`, and CSRF if required.
- **`POST chat/delete_conversation`** (app menu: Delete conversation) – body: `with_id` (other user’s id). Deletes all messages between the logged-in user and that user. **Add this route** in AJES if it does not exist yet; see `Chat_delete_conversation_example.php`.

No changes needed for send/messages/unsend if your web chat already uses those.

## 5. Base URL and cookies

- The app uses one configurable base URL (e.g. `http://10.0.2.2/AJES/` for emulator, `https://your-domain.com/AJES/` for production).
- All requests use the same OkHttp client with a persistent cookie jar; after login, the session cookie is sent on every chat request so desktop and mobile stay in sync.
