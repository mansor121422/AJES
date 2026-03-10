# API Login for Android (AJES)

The backend exposes a REST login endpoint for the AJESCHAT Android app. The **web login** (browser) is unchanged.

## Endpoint

- **URL:** `POST /api/login`  
  Full URL example: `http://localhost/AJES/api/login` or `https://your-domain.com/AJES/api/login`

- **Content-Type:** `application/json` (or `application/x-www-form-urlencoded`)

- **Body (JSON):**
  ```json
  {
    "username": "your_username_or_email",
    "password": "your_password"
  }
  ```
  You can send either `username` or `email` as the key; both are accepted (same as web login).

## Success response (HTTP 200)

```json
{
  "status": "success",
  "data": {
    "user_id": 1,
    "username": "jdoe",
    "name": "John Doe",
    "role": "STUDENT",
    "token": "64-char-hex-string"
  }
}
```

- **token:** Use this for all future API requests. Send it in the header:  
  `Authorization: Bearer <token>`

- **role:** One of: ADMIN, PRINCIPAL, ANNOUNCER, TEACHER, GUIDANCE, STUDENT

## Error responses

| HTTP | Body |
|------|------|
| 400 | `{ "status": "error", "message": "Username and password are required." }` |
| 401 | `{ "status": "error", "message": "Invalid credentials." }` |
| 403 | `{ "status": "error", "message": "Account locked. Please contact administrator." }` |

## Logout (optional)

- **URL:** `POST /api/logout`
- **Header:** `Authorization: Bearer <token>`
- **Response:** `{ "status": "success", "data": { "message": "Logged out." } }`  
  The token is revoked and cannot be reused.

## Using the token on other API routes

For any future API route that requires authentication (e.g. chat list, send message), send:

```
Authorization: Bearer <token>
```

Routes protected with the `api_token` filter will validate this token and set the current user. The token is valid for 30 days unless revoked by logout.

## Android integration summary

1. On login screen: POST to `/api/login` with `username` and `password` (JSON).
2. If `status` is `success`, save `data.token`, `data.user_id`, `data.name`, `data.role` (e.g. in SharedPreferences).
3. For every subsequent request to the API, add header:  
   `Authorization: Bearer <saved_token>`
4. On logout, call `POST /api/logout` with the same header, then clear the saved token.

## Security

- Passwords are validated with the same hashing as the web (password_verify).
- Tokens are stored in the `api_tokens` table and expire after 30 days.
- Use HTTPS in production.
