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
        .chat-user-item { display: block; padding: 12px 16px; color: #333; text-decoration: none; border-bottom: 1px solid #e8f5e9; transition: background 0.15s; }
        .chat-user-item:hover { background: #e8f5e9; }
        .chat-user-item.active { background: #c8e6c9; color: #1b5e20; font-weight: 600; }
        .chat-user-item .chat-user-role { font-size: 0.8rem; color: #558b2f; margin-top: 2px; }
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
        .chat-msg-dropdown { display: none; position: absolute; right: 0; top: 100%; margin-top: 2px; min-width: 160px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10; overflow: hidden; }
        .chat-msg-dropdown.open { display: block; }
        .chat-msg-dropdown form { display: block; border-bottom: 1px solid #eee; }
        .chat-msg-dropdown form:last-child { border-bottom: none; }
        .chat-msg-dropdown button { display: block; width: 100%; text-align: left; background: none; border: none; padding: 10px 14px; font-size: 0.875rem; color: #333; cursor: pointer; }
        .chat-msg-dropdown button:hover { background: #f5f5f5; }
        .chat-empty { text-align: center; color: #888; padding: 40px 20px; }
        .chat-form-wrap { padding: 16px; background: #fff; border-top: 1px solid #c8e6c9; }
        .chat-form { display: flex; gap: 10px; align-items: flex-end; }
        .chat-form textarea { flex: 1; min-height: 44px; max-height: 120px; resize: vertical; padding: 12px; border: 1px solid #c8e6c9; border-radius: 10px; font-family: inherit; }
        .chat-form button { flex-shrink: 0; padding: 12px 20px; background: #2e7d32; color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .chat-form button:hover { background: #1b5e20; }
        .chat-form button:disabled { opacity: 0.6; cursor: not-allowed; }
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
                <a href="<?= base_url('chat?with=' . (int) $u['id']) ?>" class="chat-user-item <?= $with_id === (int) $u['id'] ? 'active' : '' ?>">
                    <span><?= esc($u['name']) ?></span>
                    <div class="chat-user-role"><?= esc($u['role']) ?></div>
                </a>
            <?php endforeach; ?>
            <?php if (empty($chat_users)): ?>
                <p style="padding: 16px; color: #888;">No other users to chat with.</p>
            <?php endif; ?>
        </aside>
        <div class="chat-main">
            <?php if ($with_user): ?>
                <div class="chat-header"><?= esc($with_user['name']) ?> <span style="font-weight: normal; color: #558b2f;">(<?= esc($with_user['role']) ?>)</span></div>
                <div class="chat-messages" id="chat-messages">
                    <?php foreach ($conversation as $msg): ?>
                        <?php $isMine = (int) $msg['sender_id'] === $current_id; ?>
                        <div class="chat-msg <?= $isMine ? 'mine' : 'theirs' ?>" data-message-id="<?= (int) $msg['id'] ?>" data-is-mine="<?= $isMine ? '1' : '0' ?>">
                            <div class="chat-msg-inner">
                                <div class="chat-msg-body">
                                    <div><?= nl2br(esc($msg['content'])) ?></div>
                                    <div class="chat-msg-time"><?= esc($msg['created_at'] ?? '') ?></div>
                                </div>
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
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-form-wrap">
                    <form class="chat-form" action="<?= base_url('chat/send') ?>" method="post" id="chat-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="receiver_id" value="<?= (int) $with_user['id'] ?>">
                        <textarea name="content" id="chat-content" rows="1" placeholder="Type a message..." required></textarea>
                        <button type="submit" id="chat-send-btn">Send</button>
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
                    if (data.messages && data.messages.length !== lastCount) {
                        lastCount = data.messages.length;
                        var html = '';
                        data.messages.forEach(function(m) {
                            var cls = m.is_mine ? 'mine' : 'theirs';
                            html += '<div class="chat-msg ' + cls + '" data-message-id="' + m.id + '" data-is-mine="' + (m.is_mine ? '1' : '0') + '">';
                            html += '<div class="chat-msg-inner"><div class="chat-msg-body">';
                            html += '<div>' + escapeHtml(m.content).replace(/\n/g, '<br>') + '</div>';
                            html += '<div class="chat-msg-time">' + escapeHtml(m.created_at) + '</div>';
                            html += '</div>' + unsendMenuHtml(m.id, m.is_mine) + '</div></div>';
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
</body>
</html>
