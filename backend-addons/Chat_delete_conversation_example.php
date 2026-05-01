<?php
/**
 * Example: POST chat/delete_conversation
 * Form fields: with_id = other user's id (delete all messages between session user and with_id).
 *
 * Register a route like: $routes->post('chat/delete_conversation', 'Chat::delete_conversation');
 * Implement delete_conversation() to delete rows in your messages table for that pair (both directions),
 * same rules as your web app (auth + CSRF if you use it).
 *
 * Return 200 with empty body or JSON {"ok":true} on success.
 */
