<?php
$role          = $role ?? 'ADMIN';
$name          = $name ?? 'User';
$announcements = $announcements ?? [];
$canManage     = in_array($role, ['ADMIN', 'PRINCIPAL', 'ANNOUNCER'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Announcements</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">School announcements</div>
        <p style="margin-bottom: 12px; color: #558b2f;">New announcements notify all users.</p>
        <?php if ($canManage): ?>
            <a href="<?= base_url('announcements/create') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-bottom: 16px;">Create announcement</a>
        <?php endif; ?>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Status</th>
                    <?php if ($canManage): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcements)): ?>
                    <tr><td colspan="<?= $canManage ? 4 : 3 ?>">No announcements yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                        <tr>
                            <td><strong><?= esc($a['title']) ?></strong></td>
                            <td><?= esc($a['created_at']) ?></td>
                            <td><span class="status-badge status-badge-<?= ($a['status'] ?? '') === 'ACTIVE' ? 'active' : 'pending' ?>"><?= esc($a['status'] ?? '') ?></span></td>
                            <?php if ($canManage): ?>
                                <td>
                                    <a href="<?= base_url('announcements/edit/' . $a['id']) ?>" class="link-details">Edit</a>
                                    &nbsp;|&nbsp;
                                    <a href="<?= base_url('announcements/delete/' . $a['id']) ?>" class="link-details" onclick="return confirm('Delete this announcement?')">Delete</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <td colspan="<?= $canManage ? 4 : 3 ?>" style="padding-top: 0; padding-bottom: 12px; color: #555; font-size: 0.9rem;"><?= esc(character_limiter($a['body'], 120)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    </main>
</div>
</body>
</html>
