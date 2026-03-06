<?php
$role = $role ?? 'ADMIN';
$name = $name ?? 'Administrator';
$logs = $logs ?? [];
$users = $users ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Logs - AJES Admin</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        .chat-logs-table { width: 100%; }
        .chat-logs-table td { vertical-align: top; padding: 10px 12px; border-bottom: 1px solid #eee; }
        .chat-logs-table .col-from, .chat-logs-table .col-to { white-space: nowrap; color: #1b5e20; font-weight: 600; }
        .chat-logs-table .col-msg { max-width: 400px; word-break: break-word; }
        .chat-logs-table .col-time { font-size: 0.85rem; color: #666; white-space: nowrap; }
        .chat-logs-table .col-status { white-space: nowrap; }
        .badge-unsent { background: #ffebee; color: #c62828; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem; }
        .badge-censored { background: #fff3e0; color: #e65100; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem; margin-left: 4px; }
    </style>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Chat logs</h1>

    <p style="margin-bottom: 16px; color: #555;">All chat messages across the system. For censored messages, you see the <strong>original text</strong> (what the user actually typed) for monitoring; in chat, users still see ****. Unsent messages are also shown with their content.</p>

    <div class="card">
        <div class="card-title">All messages (newest first)</div>
        <table class="chat-logs-table recent-table">
            <thead>
                <tr>
                    <th style="text-align:left;">From</th>
                    <th style="text-align:left;">To</th>
                    <th style="text-align:left;">Message</th>
                    <th style="text-align:left;">Date</th>
                    <th style="text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5">No chat messages yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $m): ?>
                        <?php
                        $fromId = (int) $m['sender_id'];
                        $toId   = (int) $m['receiver_id'];
                        $fromName = $users[$fromId] ?? 'User #' . $fromId;
                        $toName   = $users[$toId] ?? 'User #' . $toId;
                        $unsentForAll = ! empty($m['deleted_at']);
                        $content = $m['content'] ?? '';
                        $contentOriginal = $m['content_original'] ?? '';
                        $displayContent = ($contentOriginal !== '') ? $contentOriginal : $content;
                        $isCensored = ($contentOriginal !== '' && $content !== $contentOriginal) || (strpos($content, '****') !== false);
                        ?>
                        <tr>
                            <td class="col-from"><?= esc($fromName) ?></td>
                            <td class="col-to"><?= esc($toName) ?></td>
                            <td class="col-msg"><?= nl2br(esc($displayContent)) ?></td>
                            <td class="col-time"><?= esc($m['created_at'] ?? '') ?></td>
                            <td class="col-status">
                                <?php if ($unsentForAll): ?>
                                    <span class="badge-unsent">Unsent for everyone</span>
                                <?php else: ?>
                                    <span style="color: #558b2f;">Active</span>
                                <?php endif; ?>
                                <?php if ($isCensored): ?>
                                    <span class="badge-censored">Censored</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
