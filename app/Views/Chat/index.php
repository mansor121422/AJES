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
        .chat-layout {
            display: flex; gap: 0; min-height: calc(100vh - 120px); border-radius: 12px; overflow: hidden; background: #fff;
            border: 1px solid var(--chat-layout-border, #c8e6c9);
            --chat-sidebar-bg: #f1f8e9;
            --chat-sidebar-title-bg: #e8f5e9;
            --chat-sidebar-border: #c8e6c9;
            --chat-header-bg: #e8f5e9;
            --chat-header-text: #1b5e20;
            --chat-header-sub: #558b2f;
            --chat-presence-online: #2e7d32;
            --chat-presence-offline: #888;
            --chat-msg-area: #fafafa;
            --chat-form-bg: #fff;
            --chat-form-border: #c8e6c9;
            --chat-bubble-mine: #c8e6c9;
            --chat-bubble-mine-text: #1b5e20;
            --chat-bubble-theirs: #fff;
            --chat-bubble-theirs-border: #e0e0e0;
            --chat-bubble-theirs-text: #333;
            --chat-accent: #2e7d32;
            --chat-accent-hover: #1b5e20;
            --chat-layout-border: #c8e6c9;
            --chat-textarea-border: #c8e6c9;
            --chat-active-bg: #c8e6c9;
            --chat-active-text: #1b5e20;
            --chat-file-name: #558b2f;
            --chat-user-role: #558b2f;
            --chat-like-shadow: rgba(27, 94, 32, 0.48);
            --chat-msg-watermark: none;
        }
        .chat-layout[data-chat-theme="cat"] {
            --chat-sidebar-bg: #fff4e6;
            --chat-sidebar-title-bg: #ffe0b2;
            --chat-sidebar-border: #ffcc80;
            --chat-header-bg: linear-gradient(125deg, #ff8f42 0%, #ff6d00 55%, #f4511e 100%);
            --chat-header-text: #fff;
            --chat-header-sub: rgba(255, 255, 255, 0.92);
            --chat-presence-online: #e8ffcc;
            --chat-presence-offline: rgba(255, 255, 255, 0.85);
            --chat-msg-area: #fff8f0;
            --chat-form-bg: #fffaf5;
            --chat-form-border: #ffcc80;
            --chat-bubble-mine: #ffb74d;
            --chat-bubble-mine-text: #4e2600;
            --chat-bubble-theirs: #fff;
            --chat-bubble-theirs-border: #ffab91;
            --chat-bubble-theirs-text: #4e2600;
            --chat-accent: #ef6c00;
            --chat-accent-hover: #e65100;
            --chat-layout-border: #ffab91;
            --chat-textarea-border: #ffcc80;
            --chat-active-bg: #ffcc80;
            --chat-active-text: #4e2600;
            --chat-file-name: #e65100;
            --chat-user-role: #e65100;
            --chat-like-shadow: rgba(230, 81, 0, 0.45);
            --chat-msg-watermark: url("<?= base_url('public/assets/chat-cat-watermark.svg') ?>");
        }
        .chat-layout[data-chat-theme="dog"] {
            --chat-sidebar-bg: #efebe9;
            --chat-sidebar-title-bg: #d7ccc8;
            --chat-sidebar-border: #bcaaa4;
            --chat-header-bg: linear-gradient(125deg, #a1887f 0%, #6d4c41 45%, #5d4037 100%);
            --chat-header-text: #fff;
            --chat-header-sub: #efebe9;
            --chat-presence-online: #c5e1a5;
            --chat-presence-offline: rgba(255, 255, 255, 0.82);
            --chat-msg-area: #faf6f0;
            --chat-form-bg: #fff;
            --chat-form-border: #bcaaa4;
            --chat-bubble-mine: #90caf9;
            --chat-bubble-mine-text: #0d47a1;
            --chat-bubble-theirs: #fff;
            --chat-bubble-theirs-border: #a1887f;
            --chat-bubble-theirs-text: #3e2723;
            --chat-accent: #6d4c41;
            --chat-accent-hover: #4e342e;
            --chat-layout-border: #a1887f;
            --chat-textarea-border: #bcaaa4;
            --chat-active-bg: #bcaaa4;
            --chat-active-text: #3e2723;
            --chat-file-name: #5d4037;
            --chat-user-role: #6d4c41;
            --chat-like-shadow: rgba(93, 64, 55, 0.45);
            --chat-msg-watermark: url("<?= base_url('public/assets/chat-dog-watermark.svg') ?>");
        }
        .chat-sidebar { width: 280px; flex-shrink: 0; background: var(--chat-sidebar-bg); border-right: 1px solid var(--chat-sidebar-border); overflow-y: auto; }
        .chat-sidebar-title { padding: 16px; font-weight: 700; color: var(--chat-header-text); background: var(--chat-sidebar-title-bg); border-bottom: 1px solid var(--chat-sidebar-border); }
        .chat-user-item {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #e8f5e9;
            transition: background 0.15s, transform 0.15s;
            position: relative;
        }
        .chat-user-item:hover { background: #e8f5e9; }
        .chat-user-item.active { background: var(--chat-active-bg); color: var(--chat-active-text); font-weight: 600; }
        .chat-user-item.has-unread { background: #e8f5e9; font-weight: 600; }
        .chat-user-avatar-wrap {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            background: rgba(255,255,255,0.75);
            border: 2px solid var(--chat-sidebar-border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .chat-user-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .chat-user-avatar-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--chat-accent);
            background: rgba(46, 125, 50, 0.08);
        }
        .chat-user-meta { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .chat-user-item .chat-user-name { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .chat-user-item .chat-user-unread-dot { width: 8px; height: 8px; border-radius: 50%; background: #66bb6a; flex-shrink: 0; }
        .chat-user-item .chat-user-role { font-size: 0.8rem; color: var(--chat-user-role); margin-top: 2px; font-weight: 700; letter-spacing: 0.02em; }
        .chat-user-status { font-size: 0.75rem; color: #888; margin-top: 4px; }
        .chat-sidebar .chat-user-status.online { color: var(--chat-accent); font-weight: 700; }
        .chat-sidebar .chat-user-status.offline { color: #888; }
        .chat-header .chat-user-status.online { color: var(--chat-presence-online); font-weight: 700; }
        .chat-header .chat-user-status.offline { color: var(--chat-presence-offline); }
        #chat-typing-indicator { display: none; color: var(--chat-header-sub); font-weight: 600; margin-left: 10px; font-size: 0.85rem; }
        .chat-user-typing { display: none; color: #558b2f; font-weight: 600; font-size: 0.75rem; margin-top: 4px; }
        .chat-main { flex: 1; display: flex; flex-direction: column; min-width: 0; background: var(--chat-form-bg); }
        .chat-header { padding: 12px 20px; background: var(--chat-header-bg); border-bottom: 1px solid var(--chat-sidebar-border); color: var(--chat-header-text); font-weight: 600; }
        .chat-header-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .chat-header-left { flex: 1; min-width: 0; display: flex; align-items: center; gap: 12px; }
        .chat-header-avatar-wrap {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.35);
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            background: rgba(255,255,255,0.2);
        }
        .chat-header-avatar-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .chat-header-avatar-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            background: rgba(0,0,0,0.15);
        }
        .chat-header-text { flex: 1; min-width: 0; }
        .chat-header-topline { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; }
        .chat-header-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .chat-header-role { font-weight: normal; color: var(--chat-header-sub); }
        .chat-theme-wrap { position: relative; }
        .chat-theme-btn {
            width: 40px; height: 40px; border-radius: 999px; border: none; background: var(--chat-accent); color: #fff;
            cursor: pointer; font-size: 1.1rem; display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .chat-theme-btn:hover { background: var(--chat-accent-hover); }
        .chat-theme-menu {
            display: none; position: absolute; right: 0; top: calc(100% + 8px); min-width: 220px;
            background: #fff; border: 1px solid var(--chat-sidebar-border); border-radius: 12px;
            box-shadow: 0 10px 28px rgba(0,0,0,0.18); z-index: 100; overflow: hidden;
        }
        .chat-theme-menu.open { display: block; }
        .chat-theme-menu button {
            display: block; width: 100%; text-align: left; padding: 12px 16px; border: none; background: none;
            cursor: pointer; font-size: 0.9rem; color: #333;
        }
        .chat-theme-menu button:hover { background: #f5f5f5; }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background-color: var(--chat-msg-area);
            background-image: var(--chat-msg-watermark, none);
            background-repeat: no-repeat;
            background-position: center 38%;
            background-size: min(72vmin, 500px);
        }
        .chat-msg {
            width: fit-content;
            max-width: 50%;
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.4;
        }
        .chat-msg.mine { margin-left: auto; background: var(--chat-bubble-mine); color: var(--chat-bubble-mine-text); border-bottom-right-radius: 4px; }
        .chat-msg.theirs { margin-right: auto; background: var(--chat-bubble-theirs); border: 1px solid var(--chat-bubble-theirs-border); color: var(--chat-bubble-theirs-text); border-bottom-left-radius: 4px; }
        .chat-msg-time { font-size: 0.75rem; color: #888; margin-top: 4px; }
        .chat-msg-inner { display: flex; align-items: flex-start; gap: 6px; }
        .chat-msg-body { flex: 1; min-width: 0; word-break: break-word; overflow-wrap: anywhere; }
        .chat-msg-menu { position: relative; flex-shrink: 0; }
        .chat-msg-dots { background: none; border: none; cursor: pointer; padding: 2px 6px; color: var(--chat-accent); font-size: 1.1rem; line-height: 1; border-radius: 4px; }
        .chat-msg-dots:hover { background: rgba(0,0,0,0.08); color: var(--chat-accent-hover); }
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
        /* AI Bot message styling */
        .chat-msg.ai-bot {
            background: #f3e5f5;
            border: 1px solid #ce93d8;
            border-bottom-left-radius: 4px;
        }
        .chat-msg-ai-badge {
            display: inline-block;
            background: #7b1fa2;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 8px;
            vertical-align: middle;
        }
        .chat-msg-dropdown { display: none; position: absolute; right: 0; top: 100%; margin-top: 2px; min-width: 160px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10; overflow: hidden; }
        .chat-msg-dropdown.open { display: block; }
        .chat-msg-dropdown form { display: block; border-bottom: 1px solid #eee; }
        .chat-msg-dropdown form:last-child { border-bottom: none; }
        .chat-msg-dropdown button { display: block; width: 100%; text-align: left; background: none; border: none; padding: 10px 14px; font-size: 0.875rem; color: #333; cursor: pointer; }
        .chat-msg-dropdown button:hover { background: #f5f5f5; }
        .chat-empty { text-align: center; color: #888; padding: 40px 20px; }
        .chat-form-wrap { padding: 16px; background: var(--chat-form-bg); border-top: 1px solid var(--chat-form-border); }
        .chat-form { display: block; }
        .chat-compose-row { display: flex; gap: 10px; align-items: flex-end; }
        .chat-compose-row textarea { flex: 1; min-height: 44px; max-height: 120px; resize: vertical; padding: 12px; border: 1px solid var(--chat-textarea-border); border-radius: 10px; font-family: inherit; }
        .chat-form button { flex-shrink: 0; padding: 12px 20px; background: var(--chat-accent); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .chat-form button:hover { background: var(--chat-accent-hover); }
        .chat-form button:disabled { opacity: 0.6; cursor: not-allowed; }
        .chat-compose-like-btn {
            flex-shrink: 0;
            width: 48px;
            min-width: 48px;
            height: 48px;
            padding: 0;
            font-size: 1.35rem;
            line-height: 1;
            background: var(--chat-accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            transition: transform 0.38s cubic-bezier(0.34, 1.45, 0.64, 1), box-shadow 0.38s ease, background 0.15s ease;
            touch-action: manipulation;
            -webkit-user-select: none;
            user-select: none;
        }
        .chat-compose-like-btn:hover:not(.chat-compose-like-holding):not(:disabled) { background: var(--chat-accent-hover); transform: scale(1.04); }
        .chat-compose-like-btn:active:not(.chat-compose-like-holding):not(:disabled) { transform: scale(0.98); }
        .chat-compose-like-btn.chat-compose-like-holding {
            transform: scale(1.75);
            box-shadow: 0 12px 32px var(--chat-like-shadow);
            z-index: 25;
            position: relative;
            background: var(--chat-accent-hover);
        }
        .chat-compose-like-btn:disabled { transform: none; }

        .chat-attachment-wrap { display: flex; gap: 10px; align-items: center; flex: 0 0 auto; }
        .chat-attachment-input { display: none; }
        .chat-camera-button {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--chat-accent);
            color: #fff;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            user-select: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            font-size: 20px;
            line-height: 1;
        }
        .chat-camera-button:hover { background: var(--chat-accent-hover); }
        .chat-attachment-button {
            padding: 12px 18px;
            background: var(--chat-accent);
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        .chat-attachment-button:hover { background: var(--chat-accent-hover); }
        .chat-file-name { color: var(--chat-file-name); font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
        .chat-image-thumb {
            max-width: 320px;
            max-height: 240px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            cursor: zoom-in;
        }
        .chat-video-thumb {
            width: 320px;
            max-width: 100%;
            height: 240px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            background: #000;
        }
        .chat-audio-thumb {
            width: 100%;
            max-width: 280px;
            height: 40px;
            border-radius: 10px;
            vertical-align: middle;
        }
        .chat-voice-button {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--chat-accent);
            color: #fff;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            user-select: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            font-size: 20px;
            line-height: 1;
            flex-shrink: 0;
            transition: background 0.15s ease, transform 0.12s ease;
        }
        .chat-voice-button:hover:not(:disabled) { background: var(--chat-accent-hover); }
        .chat-voice-button:disabled { opacity: 0.55; cursor: not-allowed; }
        .chat-voice-button.chat-voice-recording {
            background: #c62828;
            animation: chat-voice-pulse 1.2s ease-in-out infinite;
        }
        @keyframes chat-voice-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(198, 40, 40, 0.45); }
            50% { box-shadow: 0 0 0 10px rgba(198, 40, 40, 0); }
        }
        .chat-voice-hint {
            font-size: 0.78rem;
            color: var(--chat-file-name);
            white-space: nowrap;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chat-image-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.82);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 24px;
        }
        .chat-image-modal.open { display: flex; }
        .chat-image-modal img {
            max-width: min(95vw, 1200px);
            max-height: 90vh;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.45);
        }
        .chat-image-modal-close {
            position: absolute;
            top: 16px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: none;
            border-radius: 999px;
            width: 36px;
            height: 36px;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }
        .chat-camera-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
        }
        .chat-camera-modal.open { display: flex; }
        .chat-camera-panel {
            width: min(92vw, 520px);
            background: #ffffff;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.35);
        }
        .chat-camera-preview {
            width: 100%;
            aspect-ratio: 4/3;
            background: #111;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .chat-camera-preview video,
        .chat-camera-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .chat-camera-actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .chat-camera-actions button {
            border: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .chat-camera-actions .btn-primary { background: #2e7d32; color: #fff; }
        .chat-camera-actions .btn-muted { background: #eceff1; color: #263238; }
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

    <div class="chat-layout" id="chat-layout-root" data-chat-theme="forest">
        <script>
        (function () {
            try {
                var t = localStorage.getItem('ajes_chat_theme');
                if (t && /^(forest|cat|dog)$/.test(t)) {
                    document.getElementById('chat-layout-root').setAttribute('data-chat-theme', t);
                }
            } catch (e) {}
        })();
        </script>
        <aside class="chat-sidebar">
            <div class="chat-sidebar-title">💬 Message</div>
            <?php foreach ($chat_users as $u): ?>
                <?php
                $uid        = (int) $u['id'];
                $isActive   = ($with_id === $uid);
                $hasUnread  = ! empty($u['has_unread']);
                $unreadCnt  = (int) ($u['unread'] ?? 0);
                $avatarUrl  = $u['profile_photo_url'] ?? null;
                $nameStr    = trim((string) ($u['name'] ?? ''));
                $initial    = $nameStr !== ''
                    ? (function_exists('mb_substr') ? mb_substr($nameStr, 0, 1, 'UTF-8') : substr($nameStr, 0, 1))
                    : '?';
                ?>
                <a href="<?= base_url('chat?with=' . $uid) ?>" class="chat-user-item <?= $isActive ? 'active' : '' ?> <?= $hasUnread ? 'has-unread' : '' ?>" data-user-id="<?= (int) $uid ?>">
                    <div class="chat-user-avatar-wrap" aria-hidden="true">
                        <?php if (! empty($avatarUrl)): ?>
                            <img src="<?= esc($avatarUrl) ?>" alt="" loading="lazy" width="44" height="44" />
                        <?php else: ?>
                            <div class="chat-user-avatar-fallback"><?= esc(strtoupper($initial)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-user-meta">
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
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (empty($chat_users)): ?>
                <p style="padding: 16px; color: #888;">No other users to chat with.</p>
            <?php endif; ?>
        </aside>
        <div class="chat-main">
            <?php if ($with_user): ?>
                <?php
                $withPhotoRel = trim((string) ($with_user['profile_photo'] ?? ''));
                $withPhotoUrl = $withPhotoRel !== '' ? base_url($withPhotoRel) : null;
                $withNameStr  = trim((string) ($with_user['name'] ?? ''));
                $withInitial  = $withNameStr !== ''
                    ? (function_exists('mb_substr') ? mb_substr($withNameStr, 0, 1, 'UTF-8') : substr($withNameStr, 0, 1))
                    : '?';
                ?>
                <div class="chat-header chat-header-row">
                    <div class="chat-header-left">
                        <div class="chat-header-avatar-wrap" aria-hidden="true">
                            <?php if ($withPhotoUrl): ?>
                                <img src="<?= esc($withPhotoUrl) ?>" alt="" width="44" height="44" />
                            <?php else: ?>
                                <div class="chat-header-avatar-fallback"><?= esc(strtoupper($withInitial)) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="chat-header-text">
                            <div class="chat-header-topline">
                                <?= esc($with_user['name']) ?>
                                <span class="chat-header-role">(<?= esc($with_user['role']) ?>)</span>
                                <?php if (! empty($with_user['presence_label'])): ?>
                                    <span
                                        id="chat-presence-header"
                                        class="chat-user-status <?= (($with_user['presence_state'] ?? 'offline') === 'online') ? 'online' : 'offline' ?>"
                                    >
                                        <?= esc($with_user['presence_label']) ?>
                                    </span>
                                <?php endif; ?>
                                <span id="chat-typing-indicator" class="chat-user-status offline">Typing...</span>
                            </div>
                        </div>
                    </div>
                    <div class="chat-header-right">
                        <div class="chat-theme-wrap">
                            <button type="button" class="chat-theme-btn" id="chat-theme-btn" title="Chat theme" aria-label="Change theme" aria-expanded="false" aria-haspopup="true">🎨</button>
                            <div class="chat-theme-menu" id="chat-theme-menu" role="menu">
                                <button type="button" data-theme="forest" role="menuitem">🌲 Forest (default)</button>
                                <button type="button" data-theme="cat" role="menuitem">🐱 Cat — orange &amp; warm</button>
                                <button type="button" data-theme="dog" role="menuitem">🐶 Dog — brown &amp; park blue</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <?php foreach ($conversation as $msg): ?>
                        <?php
                        $isMine = (int) $msg['sender_id'] === $current_id;
                        $unsentForAll = ! empty($msg['deleted_at']);
                        $isBot = ! empty($msg['is_bot']);
                        $msgClass = $isMine ? 'mine' : ($isBot ? 'ai-bot' : 'theirs');
                        ?>
                        <div class="chat-msg <?= $msgClass ?> <?= $unsentForAll ? 'chat-msg-unsent' : '' ?>" data-message-id="<?= (int) $msg['id'] ?>" data-is-mine="<?= $isMine ? '1' : '0' ?>">
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
                                                <img src="<?= esc($attachmentUrl) ?>" alt="<?= esc($attachmentName ?? 'image') ?>" class="chat-image-thumb chat-zoomable-image" />
                                            </div>
                                        <?php elseif ($attachmentType === 'video'): ?>
                                            <div style="margin-bottom: 8px;">
                                                <video controls class="chat-video-thumb">
                                                    <source src="<?= esc($attachmentUrl) ?>" type="<?= esc($msg['attachment_mime'] ?? 'video/mp4') ?>">
                                                </video>
                                            </div>
                                        <?php elseif ($attachmentType === 'audio'): ?>
                                            <div style="margin-bottom: 8px;">
                                                <audio controls preload="metadata" class="chat-audio-thumb">
                                                    <source src="<?= esc($attachmentUrl) ?>" type="<?= esc($msg['attachment_mime'] ?? 'audio/webm') ?>">
                                                </audio>
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
                                <input type="file" name="attachment" id="chat-attachment" class="chat-attachment-input" accept="image/*,video/*" />
                                <button type="button" id="chat-open-camera" class="chat-camera-button" title="Open camera" aria-label="Open camera">📷</button>
                                <button type="button" id="chat-voice-btn" class="chat-voice-button" title="Voice message (tap to start, tap again to send)" aria-label="Record voice message">🎤</button>
                                <span id="chat-voice-hint" class="chat-voice-hint" hidden></span>
                                <label for="chat-attachment" class="chat-attachment-button" id="chat-attachment-label">Choose File</label>
                                <span id="chat-file-name" class="chat-file-name">No file chosen</span>
                            </div>
                            <textarea name="content" id="chat-content" rows="1" placeholder="Type a message..."></textarea>
                            <button type="button" id="chat-quick-like-btn" class="chat-compose-like-btn" title="Send like">👍</button>
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

    <script>
    (function chatThemePicker() {
        var root = document.getElementById('chat-layout-root');
        var btn = document.getElementById('chat-theme-btn');
        var menu = document.getElementById('chat-theme-menu');
        if (!root || !btn || !menu) return;
        var storageKey = 'ajes_chat_theme';
        function apply(theme) {
            var t = theme === 'cat' || theme === 'dog' ? theme : 'forest';
            root.setAttribute('data-chat-theme', t);
            try {
                localStorage.setItem(storageKey, t);
            } catch (e) {}
        }
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = menu.classList.contains('open');
            document.querySelectorAll('.chat-theme-menu.open').forEach(function (m) {
                m.classList.remove('open');
            });
            if (!open) {
                menu.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            } else {
                btn.setAttribute('aria-expanded', 'false');
            }
        });
        menu.querySelectorAll('button[data-theme]').forEach(function (b) {
            b.addEventListener('click', function () {
                apply(b.getAttribute('data-theme'));
                menu.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            });
        });
        document.addEventListener('click', function () {
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        });
    })();
    </script>

    <?php if ($with_user): ?>
    <script>
    (function() {
        var withId = <?= (int) $with_user['id'] ?>;
        var lastCount = <?= count($conversation) ?>;
        var lastMessagesSignature = '';
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
        function buildMessagesSignature(messages) {
            return JSON.stringify((messages || []).map(function(m) {
                return [
                    m.id,
                    m.status || '',
                    m.content || '',
                    m.unsent_for_all ? 1 : 0,
                    m.attachment_type || '',
                    m.attachment_url || '',
                    m.attachment_name || '',
                    m.attachment_mime || '',
                    m.created_at || ''
                ];
            }));
        }
        function capturePlayingVideos() {
            var states = {};
            messagesEl.querySelectorAll('.chat-msg[data-message-id]').forEach(function(msgEl) {
                var msgId = msgEl.getAttribute('data-message-id');
                if (!msgId) return;
                var videoEl = msgEl.querySelector('video');
                var audioEl = msgEl.querySelector('audio');
                var mediaEl = videoEl || audioEl;
                if (!mediaEl) return;
                if (!mediaEl.paused) {
                    states[msgId] = {
                        currentTime: mediaEl.currentTime || 0,
                        wasPlaying: true
                    };
                }
            });
            return states;
        }
        function restorePlayingVideos(states) {
            Object.keys(states || {}).forEach(function(msgId) {
                var msgEl = messagesEl.querySelector('.chat-msg[data-message-id="' + msgId + '"]');
                if (!msgEl) return;
                var mediaEl = msgEl.querySelector('video') || msgEl.querySelector('audio');
                if (!mediaEl) return;
                var st = states[msgId];
                if (typeof st.currentTime === 'number' && isFinite(st.currentTime)) {
                    try { mediaEl.currentTime = st.currentTime; } catch (e) {}
                }
                if (st.wasPlaying) {
                    var p = mediaEl.play();
                    if (p && typeof p.catch === 'function') p.catch(function() {});
                }
            });
        }
        function poll() {
            fetch('<?= base_url('chat/messages') ?>?with=' + withId, { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.messages) {
                        var nextSignature = buildMessagesSignature(data.messages);
                        if (nextSignature === lastMessagesSignature) {
                            return;
                        }
                        var playingStates = capturePlayingVideos();
                        lastCount = data.messages.length;
                        var html = '';
                        data.messages.forEach(function(m) {
                            var cls = m.is_mine ? 'mine' : (m.is_bot ? 'ai-bot' : 'theirs');
                            var unsent = m.unsent_for_all;
                            if (unsent) cls += ' chat-msg-unsent';
                            html += '<div class="chat-msg ' + cls + '" data-message-id="' + m.id + '" data-is-mine="' + (m.is_mine ? '1' : '0') + '">';
                            html += '<div class="chat-msg-inner"><div class="chat-msg-body">';
                            if (unsent) {
                                html += '<div class="chat-msg-unsent-text">The message was unsent for everyone.</div>';
                            } else {
                                if (m.attachment_type && m.attachment_url) {
                                    if (m.attachment_type === 'image') {
                                        html += '<div style="margin-bottom: 8px;"><img src="' + escapeHtml(m.attachment_url) + '" alt="' + escapeHtml(m.attachment_name || 'image') + '" class="chat-image-thumb chat-zoomable-image" /></div>';
                                    } else if (m.attachment_type === 'video') {
                                        html += '<div style="margin-bottom: 8px;"><video controls class="chat-video-thumb"><source src="' + escapeHtml(m.attachment_url) + '" type="' + escapeHtml(m.attachment_mime || 'video/mp4') + '"></video></div>';
                                    } else if (m.attachment_type === 'audio') {
                                        html += '<div style="margin-bottom: 8px;"><audio controls preload="metadata" class="chat-audio-thumb"><source src="' + escapeHtml(m.attachment_url) + '" type="' + escapeHtml(m.attachment_mime || 'audio/webm') + '"></audio></div>';
                                    } else {
                                        html += '<div style="margin-bottom: 8px;"><a href="' + escapeHtml(m.attachment_url) + '" target="_blank" rel="noreferrer" class="link-details">' + escapeHtml(m.attachment_name || 'Download file') + '</a></div>';
                                    }
                                }
                                if (m.content) {
                                    html += '<div>' + escapeHtml(m.content).replace(/\n/g, '<br>') + '</div>';
                                }
                            }
                            var timeHtml = escapeHtml(m.created_at);
                            var statusLabel = (m.is_mine && !unsent && m.status) ? (m.status === 'READ' ? 'Seen' : 'Delivered') : '';
                            var statusClass = (m.is_mine && !unsent && m.status) ? (' chat-msg-status chat-msg-status-' + (m.status || 'sent').toLowerCase()) : '';
                            timeHtml += (statusLabel ? ' <span class="chat-msg-status' + statusClass + '" title="' + escapeHtml(statusLabel) + '">' + escapeHtml(statusLabel) + '</span>' : '');
                            html += '<div class="chat-msg-time">' + timeHtml + '</div>';
                            html += '</div>';
                            if (!unsent) html += unsendMenuHtml(m.id, m.is_mine);
                            html += '</div></div>';
                        });
                        messagesEl.innerHTML = html;
                        lastMessagesSignature = nextSignature;
                        restorePlayingVideos(playingStates);
                        messagesEl.scrollTop = messagesEl.scrollHeight;
                        bindDotsMenus(messagesEl);
                    }
                });
        }
        bindDotsMenus(messagesEl);
        if (form) {
            var chatVoiceRecording = false;
            var sendBtn = document.getElementById('chat-send-btn');
            var likeBtn = document.getElementById('chat-quick-like-btn');
            var contentEl = document.getElementById('chat-content');
            var fileEl = document.getElementById('chat-attachment');

            function runSendFetch(formData) {
                if (sendBtn) sendBtn.disabled = true;
                if (likeBtn) likeBtn.disabled = true;
                var voiceBtnEl = document.getElementById('chat-voice-btn');
                if (voiceBtnEl) voiceBtnEl.disabled = true;
                return fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res || res.status !== 'success') {
                        throw new Error((res && res.message) ? res.message : 'Failed to send message.');
                    }
                    if (contentEl) contentEl.value = '';
                    if (fileEl) fileEl.value = '';
                    var fileNameEl = document.getElementById('chat-file-name');
                    if (fileNameEl) fileNameEl.textContent = 'No file chosen';
                    poll();
                })
                .catch(function(err) {
                    alert(err && err.message ? err.message : 'Failed to send message.');
                })
                .finally(function() {
                    if (sendBtn) sendBtn.disabled = false;
                    if (likeBtn) likeBtn.disabled = false;
                    var vb = document.getElementById('chat-voice-btn');
                    if (vb) vb.disabled = false;
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (chatVoiceRecording) {
                    alert('Tap the stop button to send your voice message first.');
                    return;
                }
                runSendFetch(new FormData(form));
            });

            if (likeBtn) {
                likeBtn.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                });
                function likeReleaseHold() {
                    likeBtn.classList.remove('chat-compose-like-holding');
                }
                likeBtn.addEventListener('pointerdown', function(e) {
                    if (likeBtn.disabled) return;
                    if (e.pointerType === 'mouse' && e.button !== 0) return;
                    likeBtn.classList.add('chat-compose-like-holding');
                    try {
                        likeBtn.setPointerCapture(e.pointerId);
                    } catch (err) {}
                });
                likeBtn.addEventListener('pointerup', function(e) {
                    try {
                        likeBtn.releasePointerCapture(e.pointerId);
                    } catch (err) {}
                    likeReleaseHold();
                });
                likeBtn.addEventListener('pointercancel', likeReleaseHold);
                likeBtn.addEventListener('pointerleave', function(e) {
                    if (e.pointerType === 'mouse') likeReleaseHold();
                });
                likeBtn.addEventListener('click', function() {
                    var fd = new FormData();
                    var csrfInput = form.querySelector('input[name="' + csrfName + '"]');
                    if (csrfInput) fd.append(csrfInput.name, csrfInput.value);
                    var recv = form.querySelector('input[name="receiver_id"]');
                    if (recv) fd.append('receiver_id', recv.value);
                    fd.append('content', '👍');
                    runSendFetch(fd);
                });
            }

            var voiceBtn = document.getElementById('chat-voice-btn');
            var voiceHint = document.getElementById('chat-voice-hint');
            var voiceRecorder = null;
            var voiceStream = null;
            var voiceChunks = [];
            var voiceTick = null;
            var voiceShouldSend = false;
            var voiceStartedAt = 0;
            var maxVoiceMs = 180000;
            function pickVoiceMime() {
                var list = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];
                for (var i = 0; i < list.length; i++) {
                    if (window.MediaRecorder && MediaRecorder.isTypeSupported(list[i])) {
                        return list[i];
                    }
                }
                return '';
            }
            function stopVoiceTracks() {
                if (voiceStream) {
                    voiceStream.getTracks().forEach(function(t) { try { t.stop(); } catch (e) {} });
                    voiceStream = null;
                }
            }
            function formatVoiceTime(ms) {
                var s = Math.floor(ms / 1000);
                var m = Math.floor(s / 60);
                s = s % 60;
                return m + ':' + (s < 10 ? '0' : '') + s;
            }
            function updateVoiceUi() {
                if (!voiceBtn) return;
                if (!chatVoiceRecording) {
                    if (voiceHint) voiceHint.hidden = true;
                    voiceBtn.textContent = '🎤';
                    voiceBtn.setAttribute('aria-label', 'Record voice message');
                    voiceBtn.classList.remove('chat-voice-recording');
                    return;
                }
                if (voiceHint) {
                    voiceHint.hidden = false;
                    voiceHint.textContent = 'Recording ' + formatVoiceTime(Date.now() - voiceStartedAt) + ' — tap to send';
                }
                voiceBtn.textContent = '⏹';
                voiceBtn.setAttribute('aria-label', 'Stop and send voice message');
                voiceBtn.classList.add('chat-voice-recording');
            }
            function finishVoiceRecording(sendIt) {
                if (!chatVoiceRecording || !voiceRecorder) return;
                clearInterval(voiceTick);
                voiceTick = null;
                voiceShouldSend = !!sendIt;
                try {
                    if (voiceRecorder.state === 'recording') {
                        voiceRecorder.stop();
                    }
                } catch (e) {}
            }
            if (voiceBtn) {
                voiceBtn.addEventListener('click', function() {
                    if (voiceBtn.disabled) return;
                    if (chatVoiceRecording) {
                        finishVoiceRecording(true);
                        return;
                    }
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
                        alert('Voice messages are not supported in this browser.');
                        return;
                    }
                    var mimePick = pickVoiceMime();
                    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
                        voiceStream = stream;
                        voiceChunks = [];
                        try {
                            voiceRecorder = mimePick ? new MediaRecorder(stream, { mimeType: mimePick }) : new MediaRecorder(stream);
                        } catch (err1) {
                            voiceRecorder = new MediaRecorder(stream);
                        }
                        voiceRecorder.ondataavailable = function(e) {
                            if (e.data && e.data.size > 0) voiceChunks.push(e.data);
                        };
                        voiceRecorder.onstop = function() {
                            stopVoiceTracks();
                            chatVoiceRecording = false;
                            updateVoiceUi();
                            var rec = voiceRecorder;
                            voiceRecorder = null;
                            if (!voiceShouldSend) {
                                voiceShouldSend = false;
                                return;
                            }
                            voiceShouldSend = false;
                            var mt = (rec && rec.mimeType) ? rec.mimeType : (mimePick || 'audio/webm');
                            var blob = new Blob(voiceChunks, { type: mt });
                            voiceChunks = [];
                            if (blob.size < 600) {
                                alert('Recording too short.');
                                return;
                            }
                            var ext = 'webm';
                            if (mt.indexOf('mp4') !== -1) ext = 'm4a';
                            else if (mt.indexOf('ogg') !== -1) ext = 'ogg';
                            var fname = 'voice-' + Date.now() + '.' + ext;
                            var file = new File([blob], fname, { type: blob.type });
                            var fd = new FormData();
                            var csrfInput = form.querySelector('input[name="' + csrfName + '"]');
                            if (csrfInput) fd.append(csrfInput.name, csrfInput.value);
                            var recv = form.querySelector('input[name="receiver_id"]');
                            if (recv) fd.append('receiver_id', recv.value);
                            fd.append('content', '');
                            fd.append('voice_message', '1');
                            fd.append('attachment_audio', file, fname);
                            runSendFetch(fd);
                        };
                        chatVoiceRecording = true;
                        voiceStartedAt = Date.now();
                        voiceRecorder.start(1000);
                        updateVoiceUi();
                        voiceTick = setInterval(function() {
                            if (Date.now() - voiceStartedAt >= maxVoiceMs) {
                                finishVoiceRecording(true);
                            } else {
                                updateVoiceUi();
                            }
                        }, 400);
                    }).catch(function() {
                        alert('Microphone access is required to record a voice message.');
                    });
                });
            }
        }
        setInterval(poll, 4000);
    })();
    </script>
    <?php endif; ?>

    <div id="chat-image-modal" class="chat-image-modal" aria-hidden="true">
        <button type="button" id="chat-image-modal-close" class="chat-image-modal-close" aria-label="Close image preview">&times;</button>
        <img id="chat-image-modal-preview" src="" alt="Image preview" />
    </div>
    <div id="chat-camera-modal" class="chat-camera-modal" aria-hidden="true">
        <div class="chat-camera-panel">
            <div class="chat-camera-preview">
                <video id="chat-camera-video" autoplay playsinline muted></video>
                <img id="chat-camera-snapshot" alt="Captured photo" style="display:none;" />
            </div>
            <div class="chat-camera-actions">
                <button type="button" id="chat-camera-close" class="btn-muted">Close</button>
                <button type="button" id="chat-camera-capture" class="btn-primary">Capture</button>
                <button type="button" id="chat-camera-use" class="btn-primary" style="display:none;">Use photo</button>
            </div>
        </div>
    </div>

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
        var openCameraBtn = document.getElementById('chat-open-camera');
        var cameraModal = document.getElementById('chat-camera-modal');
        var cameraVideo = document.getElementById('chat-camera-video');
        var cameraSnapshot = document.getElementById('chat-camera-snapshot');
        var cameraCloseBtn = document.getElementById('chat-camera-close');
        var cameraCaptureBtn = document.getElementById('chat-camera-capture');
        var cameraUseBtn = document.getElementById('chat-camera-use');
        if (!fileInput || !fileNameEl) return;

        var cameraStream = null;
        var snapshotBlob = null;

        function updateFileName() {
            var f = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            fileNameEl.textContent = f ? f.name : 'No file chosen';
        }

        function stopCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(function(track) { track.stop(); });
                cameraStream = null;
            }
        }

        function resetSnapshot() {
            snapshotBlob = null;
            cameraSnapshot.style.display = 'none';
            cameraSnapshot.src = '';
            cameraVideo.style.display = 'block';
            cameraCaptureBtn.style.display = 'inline-block';
            cameraUseBtn.style.display = 'none';
        }

        function closeCameraModal() {
            if (!cameraModal) return;
            cameraModal.classList.remove('open');
            cameraModal.setAttribute('aria-hidden', 'true');
            stopCamera();
            resetSnapshot();
        }

        function blobToFile(blob, fileName) {
            return new File([blob], fileName, { type: blob.type || 'image/jpeg' });
        }

        function applyCapturedPhoto(blob) {
            var dt = new DataTransfer();
            var now = new Date();
            var stamped = 'camera-' + now.getFullYear()
                + String(now.getMonth() + 1).padStart(2, '0')
                + String(now.getDate()).padStart(2, '0')
                + '-' + String(now.getHours()).padStart(2, '0')
                + String(now.getMinutes()).padStart(2, '0')
                + String(now.getSeconds()).padStart(2, '0')
                + '.jpg';
            dt.items.add(blobToFile(blob, stamped));
            fileInput.files = dt.files;
            updateFileName();
        }

        async function openCameraModal() {
            if (!cameraModal || !cameraVideo) return;
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                cameraVideo.srcObject = cameraStream;
                resetSnapshot();
                cameraModal.classList.add('open');
                cameraModal.setAttribute('aria-hidden', 'false');
            } catch (err) {
                alert('Unable to access camera. Please allow camera permission.');
            }
        }

        if (openCameraBtn && cameraModal && cameraCaptureBtn && cameraUseBtn && cameraCloseBtn) {
            openCameraBtn.addEventListener('click', function() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Camera is not supported on this browser.');
                    return;
                }
                openCameraModal();
            });

            cameraCaptureBtn.addEventListener('click', function() {
                if (!cameraVideo.videoWidth || !cameraVideo.videoHeight) return;
                var canvas = document.createElement('canvas');
                canvas.width = cameraVideo.videoWidth;
                canvas.height = cameraVideo.videoHeight;
                var ctx = canvas.getContext('2d');
                if (!ctx) return;
                ctx.drawImage(cameraVideo, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function(blob) {
                    if (!blob) return;
                    snapshotBlob = blob;
                    cameraSnapshot.src = URL.createObjectURL(blob);
                    cameraSnapshot.style.display = 'block';
                    cameraVideo.style.display = 'none';
                    cameraCaptureBtn.style.display = 'none';
                    cameraUseBtn.style.display = 'inline-block';
                }, 'image/jpeg', 0.92);
            });

            cameraUseBtn.addEventListener('click', function() {
                if (!snapshotBlob) return;
                applyCapturedPhoto(snapshotBlob);
                closeCameraModal();
            });

            cameraCloseBtn.addEventListener('click', closeCameraModal);
            cameraModal.addEventListener('click', function(e) {
                if (e.target === cameraModal) closeCameraModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && cameraModal.classList.contains('open')) {
                    closeCameraModal();
                }
            });
        }

        fileInput.addEventListener('change', updateFileName);
        updateFileName();
    })();
    </script>
    <script>
    (function() {
        var modal = document.getElementById('chat-image-modal');
        var preview = document.getElementById('chat-image-modal-preview');
        var closeBtn = document.getElementById('chat-image-modal-close');
        if (!modal || !preview || !closeBtn) return;

        function openModal(src, alt) {
            preview.src = src || '';
            preview.alt = alt || 'Image preview';
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            preview.src = '';
        }

        document.addEventListener('click', function(e) {
            var target = e.target;
            if (target && target.classList && target.classList.contains('chat-zoomable-image')) {
                openModal(target.getAttribute('src'), target.getAttribute('alt'));
                return;
            }
            if (target === modal) {
                closeModal();
            }
        });

        closeBtn.addEventListener('click', closeModal);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                closeModal();
            }
        });
    })();
    </script>
</body>
</html>
