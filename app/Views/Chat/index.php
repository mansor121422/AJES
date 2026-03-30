<?php
$role         = $role ?? 'ADMIN';
$name         = $name ?? 'User';
$current_id   = $current_id ?? 0;
$chat_users   = $chat_users ?? [];
$with_user    = $with_user ?? null;
$conversation = $conversation ?? [];
$with_id      = $with_user ? (int) $with_user['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .chat-layout { display: flex; gap: 0; min-height: calc(100vh - 120px); border: 1px solid #c8e6c9; border-radius: 12px; overflow: hidden; background: #fff; }
        .chat-sidebar { width: 280px; flex-shrink: 0; background: #f1f8e9; border-right: 1px solid #c8e6c9; overflow-y: auto; }
        .chat-sidebar-title { padding: 16px; font-weight: 700; color: #1b5e20; background: #e8f5e9; border-bottom: 1px solid #c8e6c9; }
        .chat-user-item { display: flex; flex-direction: column; padding: 12px 16px; color: #333; text-decoration: none; border-bottom: 1px solid #e8f5e9; transition: background 0.15s, transform 0.15s; position: relative; }
        .chat-user-item:hover { background: #e8f5e9; }
        .chat-user-item.active { background: #c8e6c9; color: #1b5e20; font-weight: 600; }
        .chat-user-item.has-unread { background: #e8f5e9; font-weight: 600; }
        .chat-user-item .chat-user-name { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .chat-user-item .chat-user-unread-dot { width: 8px; height: 8px; border-radius: 50%; background: #66bb6a; flex-shrink: 0; }
        .chat-user-item .chat-user-role { font-size: 0.8rem; color: #558b2f; margin-top: 2px; }
        .chat-user-status { font-size: 0.75rem; color: #888; margin-top: 4px; }
        .chat-user-status.online { color: #2e7d32; font-weight: 700; }
        .chat-user-status.offline { color: #888; }
        #chat-typing-indicator { display: none; color: #558b2f; font-weight: 600; margin-left: 10px; font-size: 0.85rem; }
        .chat-user-typing { display: none; color: #558b2f; font-weight: 600; font-size: 0.75rem; margin-top: 4px; }
        .chat-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .chat-header { padding: 12px 20px; background: #e8f5e9; border-bottom: 1px solid #c8e6c9; color: #1b5e20; font-weight: 600; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 20px; background: #fafafa; }
        .chat-msg { max-width: 75%; margin-bottom: 12px; padding: 10px 14px; border-radius: 12px; font-size: 14px; line-height: 1.4; }
        .chat-msg.mine { margin-left: auto; background: #c8e6c9; color: #1b5e20; border-bottom-right-radius: 4px; }
        .chat-msg.theirs { background: #fff; border: 1px solid #e0e0e0; color: #333; border-bottom-left-radius: 4px; }
        .chat-msg-time { font-size: 0.75rem; color: #888; margin-top: 4px; }
        .chat-msg-inner { display: flex; align-items: flex-start; gap: 6px; }
        .chat-msg-body { flex: 1; min-width: 0; }
        .chat-msg-menu { position: relative; flex-shrink: 0; }
        .chat-msg-dots { background: none; border: none; cursor: pointer; padding: 2px 6px; color: #558b2f; font-size: 1.1rem; line-height: 1; border-radius: 4px; }
        .chat-msg-dots:hover { background: rgba(0,0,0,0.08); color: #1b5e20; }
        .chat-msg.theirs .chat-msg-dots { color: #666; }
        .chat-msg.theirs .chat-msg-dots:hover { color: #333; }
        .chat-msg-unsent .chat-msg-body { font-style: italic; }
        .chat-msg-unsent-text { color: #888; font-size: 0.9rem; }
        .chat-msg-time { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        /* Status pill: SENT = light green (delivered), READ = gray (seen) */
        .chat-msg-status {
            font-size: 0.7rem;
            font-weight: 500;
            margin-left: 4px;
            padding: 2px 6px;
            border-radius: 999px;
            background: #e8f5e9;
            color: #558b2f;
        }
        .chat-msg-status.chat-msg-status-sent {
            background: #e8f5e9;
            color: #66bb6a; /* light / delivered */
        }
        .chat-msg-status.chat-msg-status-read {
            background: #eeeeee;
            color: #757575; /* gray / seen */
        }
        .chat-msg.theirs .chat-msg-status { display: none; }
        .chat-msg-dropdown { display: none; position: absolute; right: 0; top: 100%; margin-top: 2px; min-width: 160px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10; overflow: hidden; }
        .chat-msg-dropdown.open { display: block; }
        .chat-msg-dropdown form { display: block; border-bottom: 1px solid #eee; }
        .chat-msg-dropdown form:last-child { border-bottom: none; }
        .chat-msg-dropdown button { display: block; width: 100%; text-align: left; background: none; border: none; padding: 10px 14px; font-size: 0.875rem; color: #333; cursor: pointer; }
        .chat-msg-dropdown button:hover { background: #f5f5f5; }
        .chat-empty { text-align: center; color: #888; padding: 40px 20px; }
        .chat-form-wrap { padding: 16px; background: #fff; border-top: 1px solid #c8e6c9; }
        .chat-form { display: block; }
        .chat-compose-row { display: flex; gap: 10px; align-items: flex-end; }
        .chat-compose-row textarea { flex: 1; min-height: 44px; max-height: 120px; resize: vertical; padding: 12px; border: 1px solid #c8e6c9; border-radius: 10px; font-family: inherit; }
        .chat-form button { flex-shrink: 0; padding: 12px 20px; background: #2e7d32; color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .chat-form button:hover { background: #1b5e20; }
        .chat-form button:disabled { opacity: 0.6; cursor: not-allowed; }

        .chat-attachment-wrap { display: flex; gap: 10px; align-items: center; flex: 0 0 auto; }
        .chat-attachment-input { display: none; }
        .chat-attachment-button {
            padding: 12px 18px;
            background: #2e7d32;
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.12);
        }
        .chat-attachment-button:hover { background: #1b5e20; }
        .chat-file-name { color: #558b2f; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
    </style>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Chat</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="chat-layout">
        <aside class="chat-sidebar">
            <div class="chat-sidebar-title">💬 Message</div>
            <?php foreach ($chat_users as $u): ?>
                <?php
                $uid        = (int) $u['id'];
                $isActive   = ($with_id === $uid);
                $hasUnread  = ! empty($u['has_unread']);
                $unreadCnt  = (int) ($u['unread'] ?? 0);
                ?>
                <a href="<?= base_url('chat?with=' . $uid) ?>" class="chat-user-item <?= $isActive ? 'active' : '' ?> <?= $hasUnread ? 'has-unread' : '' ?>" data-user-id="<?= (int) $uid ?>">
                    <div class="chat-user-name">
                        <span><?= esc($u['name']) ?></span>
                        <?php if ($hasUnread): ?>
                            <span class="chat-user-unread-dot" title="<?= $unreadCnt === 1 ? '1 new message' : $unreadCnt . ' new messages' ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="chat-user-role"><?= esc($u['role']) ?></div>
                    <div class="chat-user-status <?= (($u['presence_state'] ?? 'offline') === 'online') ? 'online' : 'offline' ?>">
                        <?= esc($u['presence_label'] ?? '') ?>
                    </div>
                    <div class="chat-user-typing" data-user-typing="<?= ($u['typing'] ?? false) ? '1' : '0' ?>">Typing...</div>
                </a>
            <?php endforeach; ?>
            <?php if (empty($chat_users)): ?>
                <p style="padding: 16px; color: #888;">No other users to chat with.</p>
            <?php endif; ?>
        </aside>
        <div class="chat-main">
            <?php if ($with_user): ?>
                <div class="chat-header">
                    <?= esc($with_user['name']) ?>
                    <span style="font-weight: normal; color: #558b2f;">(<?= esc($with_user['role']) ?>)</span>
                <?php if (! empty($with_user['presence_label'])): ?>
                        <span
                            id="chat-presence-header"
                            class="chat-user-status <?= (($with_user['presence_state'] ?? 'offline') === 'online') ? 'online' : 'offline' ?>"
                            style="margin-left: 10px;"
                        >
                            <?= esc($with_user['presence_label']) ?>
                        </span>
                    <?php endif; ?>
                    <span id="chat-typing-indicator" class="chat-user-status offline">Typing...</span>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <?php foreach ($conversation as $msg): ?>
                        <?php
                        $isMine = (int) $msg['sender_id'] === $current_id;
                        $unsentForAll = ! empty($msg['deleted_at']);
                        ?>
                        <div class="chat-msg <?= $isMine ? 'mine' : 'theirs' ?> <?= $unsentForAll ? 'chat-msg-unsent' : '' ?>" data-message-id="<?= (int) $msg['id'] ?>" data-is-mine="<?= $isMine ? '1' : '0' ?>">
                            <div class="chat-msg-inner">
                                <div class="chat-msg-body">
                                    <?php if ($unsentForAll): ?>
                                    <div class="chat-msg-unsent-text">The message was unsent for everyone.</div>
                                    <?php else: ?>
                                    <?php
                                    $attachmentType = $msg['attachment_type'] ?? null;
                                    $attachmentUrl  = $msg['attachment_url'] ?? null;
                                    $attachmentName = $msg['attachment_name'] ?? null;
                                    $attachmentSafeUrl = $attachmentUrl ? base_url(ltrim((string) $attachmentUrl, '/')) : null;
                                    ?>
                                    <?php if (! empty($attachmentType) && ! empty($attachmentUrl)): ?>
                                        <?php if ($attachmentType === 'image'): ?>
                                            <div style="margin-bottom: 8px;">
                                                <img src="<?= esc($attachmentUrl) ?>" alt="<?= esc($attachmentName ?? 'image') ?>" style="max-width: 320px; max-height: 240px; border-radius: 10px; border: 1px solid #e0e0e0;" />
                                            </div>
                                        <?php elseif ($attachmentType === 'video'): ?>
                                            <div style="margin-bottom: 8px;">
                                                <video controls style="max-width: 360px; border-radius: 10px; border: 1px solid #e0e0e0;">
                                                    <source src="<?= esc($attachmentUrl) ?>" type="<?= esc($msg['attachment_mime'] ?? 'video/mp4') ?>">
                                                </video>
                                            </div>
                                        <?php else: ?>
                                            <div style="margin-bottom: 8px;">
                                                <a href="<?= esc($attachmentUrl) ?>" target="_blank" rel="noreferrer" class="link-details">
                                                    <?= esc($attachmentName ?? 'Download file') ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (! empty($msg['content'])): ?>
                                        <div><?= nl2br(esc($msg['content'])) ?></div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="chat-msg-time">
                                        <?= esc($msg['created_at'] ?? '') ?>
                                        <?php if ($isMine && ! $unsentForAll): ?>
                                        <span class="chat-msg-status chat-msg-status-<?= strtolower($msg['status'] ?? 'sent') ?>" title="<?= ($msg['status'] ?? 'SENT') === 'READ' ? 'Seen' : 'Delivered' ?>"><?= ($msg['status'] ?? 'SENT') === 'READ' ? 'Seen' : 'Delivered' ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (! $unsentForAll): ?>
                                <div class="chat-msg-menu">
                                    <button type="button" class="chat-msg-dots" aria-label="Message options">&#8942;</button>
                                    <div class="chat-msg-dropdown">
                                        <form action="<?= base_url('chat/unsend') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="message_id" value="<?= (int) $msg['id'] ?>">
                                            <input type="hidden" name="scope" value="me">
                                            <input type="hidden" name="with_id" value="<?= $with_id ?>">
                                            <button type="submit">Unsend for me</button>
                                        </form>
                                        <?php if ($isMine): ?>
                                        <form action="<?= base_url('chat/unsend') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="message_id" value="<?= (int) $msg['id'] ?>">
                                            <input type="hidden" name="scope" value="all">
                                            <input type="hidden" name="with_id" value="<?= $with_id ?>">
                                            <button type="submit">Unsend for everyone</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-form-wrap">
                    <form class="chat-form" action="<?= base_url('chat/send') ?>" method="post" id="chat-form" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="receiver_id" value="<?= (int) $with_user['id'] ?>">
                        <div class="chat-compose-row">
                            <div class="chat-attachment-wrap">
                                <input type="file" name="attachment" id="chat-attachment" class="chat-attachment-input" />
                                <label for="chat-attachment" class="chat-attachment-button" id="chat-attachment-label">Choose File</label>
                                <span id="chat-file-name" class="chat-file-name">No file chosen</span>
                            </div>
                            <textarea name="content" id="chat-content" rows="1" placeholder="Type a message..."></textarea>
                            <button type="submit" id="chat-send-btn">Send</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="chat-header">Chat</div>
                <div class="chat-empty">
                    <p>Select a user from the list to start chatting.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($with_user): ?>
    <script>
    (function() {
        var withId = <?= (int) $with_user['id'] ?>;
        var lastCount = <?= count($conversation) ?>;
        var form = document.getElementById('chat-form');
        var messagesEl = document.getElementById('chat-messages');
        var unsendUrl = '<?= base_url('chat/unsend') ?>';
        var csrfName = '<?= csrf_token() ?>';
        var csrfHash = '<?= csrf_hash() ?>';

        function unsendMenuHtml(msgId, isMine) {
            var h = '<div class="chat-msg-menu"><button type="button" class="chat-msg-dots" aria-label="Message options">&#8942;</button>';
            h += '<div class="chat-msg-dropdown"><form action="' + escapeHtml(unsendUrl) + '" method="post">' +
                '<input type="hidden" name="' + escapeHtml(csrfName) + '" value="' + escapeHtml(csrfHash) + '">' +
                '<input type="hidden" name="message_id" value="' + msgId + '">' +
                '<input type="hidden" name="scope" value="me">' +
                '<input type="hidden" name="with_id" value="' + withId + '">' +
                '<button type="submit">Unsend for me</button></form>';
            if (isMine) {
                h += '<form action="' + escapeHtml(unsendUrl) + '" method="post">' +
                    '<input type="hidden" name="' + escapeHtml(csrfName) + '" value="' + escapeHtml(csrfHash) + '">' +
                    '<input type="hidden" name="message_id" value="' + msgId + '">' +
                    '<input type="hidden" name="scope" value="all">' +
                    '<input type="hidden" name="with_id" value="' + withId + '">' +
                    '<button type="submit">Unsend for everyone</button></form>';
            }
            h += '</div></div>';
            return h;
        }
        function bindDotsMenus(container) {
            if (!container) return;
            container.querySelectorAll('.chat-msg-dots').forEach(function(btn) {
                if (btn._bound) return;
                btn._bound = true;
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var drop = btn.closest('.chat-msg-menu').querySelector('.chat-msg-dropdown');
                    var open = drop.classList.contains('open');
                    document.querySelectorAll('.chat-msg-dropdown.open').forEach(function(d) { d.classList.remove('open'); });
                    if (!open) drop.classList.add('open');
                });
            });
            if (!container._closeBound) {
                container._closeBound = true;
                document.addEventListener('click', function() {
                    container.querySelectorAll('.chat-msg-dropdown.open').forEach(function(d) { d.classList.remove('open'); });
                });
            }
        }
        function escapeHtml(s) {
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }
        function poll() {
            fetch('<?= base_url('chat/messages') ?>?with=' + withId, { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.messages) {
                        lastCount = data.messages.length;
                        var html = '';
                        data.messages.forEach(function(m) {
                            var cls = m.is_mine ? 'mine' : 'theirs';
                            var unsent = m.unsent_for_all;
                            if (unsent) cls += ' chat-msg-unsent';
                            html += '<div class="chat-msg ' + cls + '" data-message-id="' + m.id + '" data-is-mine="' + (m.is_mine ? '1' : '0') + '">';
                            html += '<div class="chat-msg-inner"><div class="chat-msg-body">';
                            if (unsent) {
                                html += '<div class="chat-msg-unsent-text">The message was unsent for everyone.</div>';
                            } else {
                                if (m.attachment_type && m.attachment_url) {
                                    if (m.attachment_type === 'image') {
                                        html += '<div style="margin-bottom: 8px;"><img src="' + escapeHtml(m.attachment_url) + '" alt="' + escapeHtml(m.attachment_name || 'image') + '" style="max-width: 320px; max-height: 240px; border-radius: 10px; border: 1px solid #e0e0e0;" /></div>';
                                    } else if (m.attachment_type === 'video') {
                                        html += '<div style="margin-bottom: 8px;"><video controls style="max-width: 360px; border-radius: 10px; border: 1px solid #e0e0e0;"><source src="' + escapeHtml(m.attachment_url) + '" type="' + escapeHtml(m.attachment_mime || 'video/mp4') + '"></video></div>';
                                    } else {
                                        html += '<div style="margin-bottom: 8px;"><a href="' + escapeHtml(m.attachment_url) + '" target="_blank" rel="noreferrer" class="link-details">' + escapeHtml(m.attachment_name || 'Download file') + '</a></div>';
                                    }
                                }
                                if (m.content) {
                                    html += '<div>' + escapeHtml(m.content).replace(/\n/g, '<br>') + '</div>';
                                }
                            }
                            var statusLabel = (m.is_mine && !unsent && m.status) ? (m.status === 'READ' ? 'Seen' : 'Delivered') : '';
                            var statusClass = (m.is_mine && !unsent && m.status) ? (' chat-msg-status chat-msg-status-' + (m.status || 'sent').toLowerCase()) : '';
                            html += '<div class="chat-msg-time">' + escapeHtml(m.created_at) + (statusLabel ? ' <span class="chat-msg-status' + statusClass + '" title="' + escapeHtml(statusLabel) + '">' + escapeHtml(statusLabel) + '</span>' : '') + '</div>';
                            html += '</div>';
                            if (!unsent) html += unsendMenuHtml(m.id, m.is_mine);
                            html += '</div></div>';
                        });
                        messagesEl.innerHTML = html;
                        messagesEl.scrollTop = messagesEl.scrollHeight;
                        bindDotsMenus(messagesEl);
                    }
                });
        }
        bindDotsMenus(messagesEl);
        if (form) {
            form.addEventListener('submit', function() {
                setTimeout(poll, 500);
            });
        }
        setInterval(poll, 4000);
    })();
    </script>
    <?php endif; ?>

    <script>
    (function() {
        var pollUrl = '<?= base_url('api/chat/users') ?>?current=<?= (int) $current_id ?>';
        var presenceIntervalMs = 4000;
        var headerUserId = <?= (int) $with_id ?>;
        var currentUserId = <?= (int) $current_id ?>;

        var statusById = new Map();
        document.querySelectorAll('.chat-user-item[data-user-id]').forEach(function(a) {
            var id = a.getAttribute('data-user-id');
            var el = a.querySelector('.chat-user-status');
            if (id && el) statusById.set(id, el);
        });

        var typingById = new Map();
        document.querySelectorAll('.chat-user-item[data-user-id]').forEach(function(a) {
            var id = a.getAttribute('data-user-id');
            var el = a.querySelector('.chat-user-typing');
            if (id && el) typingById.set(id, el);
        });

        var headerEl = document.getElementById('chat-presence-header');

        function applyPresence(users) {
            if (!Array.isArray(users)) return;
            users.forEach(function(u) {
                var id = String(u.id);
                var el = statusById.get(id);
                if (el) {
                    var state = u.presence_state || 'offline';
                    var label = u.presence_label || (state === 'online' ? 'Online' : 'Offline');
                    el.textContent = label;
                    el.classList.toggle('online', state === 'online');
                    el.classList.toggle('offline', state !== 'online');
                }

                var tEl = typingById.get(id);
                if (tEl) {
                    var isTyping = !!(u.typing);
                    tEl.style.display = isTyping ? 'block' : 'none';
                }

                if (headerEl && id === String(headerUserId)) {
                    var stateH = u.presence_state || 'offline';
                    var labelH = u.presence_label || (stateH === 'online' ? 'Online' : 'Offline');
                    headerEl.textContent = labelH;
                    headerEl.classList.toggle('online', stateH === 'online');
                    headerEl.classList.toggle('offline', stateH !== 'online');
                }
            });
        }

        function pollPresence() {
            fetch(pollUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function(r) {
                    if (!r.ok) throw new Error('Presence request failed');
                    return r.json();
                })
                .then(function(d) {
                    applyPresence(d.users || []);
                })
                .catch(function() {});
        }

        pollPresence();
        setInterval(pollPresence, presenceIntervalMs);
    })();
    </script>

    <script>
    (function() {
        var typingIndicatorEl = document.getElementById('chat-typing-indicator');
        var inputEl = document.getElementById('chat-content');
        var typingPollUrl = '<?= base_url('api/chat/typing') ?>';
        var typingWithId = <?= (int) $with_id ?>;
        var currentUserId = <?= (int) $current_id ?>;

        // If we are not inside an active conversation, do nothing.
        if (!typingIndicatorEl || !inputEl) {
            return;
        }

        // Throttle typing updates so we don't spam the server.
        var minTypingUpdateMs = 1200;
        var lastTypingUpdateAt = 0;
        var idleOffTimeout = null;
        // Keep "typing" ON for a bit longer so the other side's poll
        // (sidebar refresh) can catch it reliably.
        var idleOffMs = 5000;

        function setTyping(on) {
            var now = Date.now();
            if (on && (now - lastTypingUpdateAt) < minTypingUpdateMs) {
                return;
            }
            if (on) lastTypingUpdateAt = now;

            fetch(typingPollUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ from: currentUserId, to: typingWithId, typing: on ? 1 : 0 })
            }).catch(function() {});
        }

        function scheduleOff() {
            if (idleOffTimeout) clearTimeout(idleOffTimeout);
            idleOffTimeout = setTimeout(function() {
                setTyping(false);
            }, idleOffMs);
        }

        inputEl.addEventListener('input', function() {
            var hasText = (inputEl.value || '').trim().length > 0;
            setTyping(hasText);
            scheduleOff();
        });

        inputEl.addEventListener('keydown', function() {
            // Mark typing even before the `input` event commits text.
            setTyping(true);
            scheduleOff();
        });

        inputEl.addEventListener('blur', function() {
            setTyping(false);
        });

        function pollTyping() {
            fetch(typingPollUrl + '?with=' + typingWithId + '&to=' + currentUserId, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    var isTyping = !!(d && d.typing);
                    typingIndicatorEl.style.display = isTyping ? 'inline' : 'none';
                    typingIndicatorEl.textContent = isTyping ? 'Typing...' : 'Typing...';
                })
                .catch(function() {});
        }

        pollTyping();
        setInterval(pollTyping, 2000);

        // Best-effort: when leaving page, stop typing (might not always run).
        window.addEventListener('beforeunload', function() {
            setTyping(false);
        });
    })();
    </script>

    <script>
    (function() {
        var fileInput = document.getElementById('chat-attachment');
        var fileNameEl = document.getElementById('chat-file-name');
        if (!fileInput || !fileNameEl) return;

        function updateFileName() {
            var f = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            fileNameEl.textContent = f ? f.name : 'No file chosen';
        }

        fileInput.addEventListener('change', updateFileName);
        updateFileName();
    })();
    </script>
</body>
</html>
