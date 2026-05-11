<?php
$role          = $role ?? 'ADMIN';
$name          = $name ?? 'User';
$announcements = $announcements ?? [];
$canManage     = in_array($role, ['ADMIN', 'PRINCIPAL', 'ANNOUNCER', 'TEACHER'], true);
$dateFrom      = $date_from ?? '';
$dateTo        = $date_to ?? '';
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
        <p style="margin-bottom: 12px; color: #558b2f;">
            <?= ($role ?? '') === 'STUDENT'
                ? 'You only see announcements sent to you.'
                : 'New announcements notify users based on audience.' ?>
        </p>
        <?php if ($canManage): ?>
            <form method="get" action="<?= base_url('announcements') ?>" style="display:flex; gap:8px; flex-wrap:wrap; align-items:end; margin-bottom:12px;">
                <div>
                    <label for="date_from" style="display:block; font-size:0.85rem; color:#1b5e20; margin-bottom:4px;">From</label>
                    <input type="date" id="date_from" name="date_from" value="<?= esc((string) $dateFrom) ?>" style="padding:8px 10px; border:1px solid #c8e6c9; border-radius:8px;">
                </div>
                <div>
                    <label for="date_to" style="display:block; font-size:0.85rem; color:#1b5e20; margin-bottom:4px;">To</label>
                    <input type="date" id="date_to" name="date_to" value="<?= esc((string) $dateTo) ?>" style="padding:8px 10px; border:1px solid #c8e6c9; border-radius:8px;">
                </div>
                <button type="submit" class="login-button" style="display:inline-flex; width:auto; padding:9px 16px;">Filter</button>
                <?php if ($dateFrom !== '' || $dateTo !== ''): ?>
                    <a href="<?= base_url('announcements') ?>" style="color:#2e7d32; padding-bottom:8px;">Reset</a>
                <?php endif; ?>
            </form>
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
