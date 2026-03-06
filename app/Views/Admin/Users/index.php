<?php
$users = $users ?? [];
$show_deleted = $show_deleted ?? false;
$deleted_count = $deleted_count ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - AJES Admin</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">User management</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title"><?= $show_deleted ? 'Deleted users' : 'All users' ?></div>
        <p style="margin-bottom: 12px; color: #558b2f;">
            <?php if ($show_deleted): ?>
                Users are soft-deleted (not removed from the database). Restore to make them active again.
            <?php else: ?>
                Create, edit, and archive system users. Deleted users can be restored from "Deleted users".
            <?php endif; ?>
        </p>
        <?php if ($show_deleted): ?>
            <a href="<?= base_url('admin/users') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-bottom: 16px;">← Back to active users</a>
        <?php else: ?>
            <a href="<?= base_url('admin/users/create') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-bottom: 16px;">Create user</a>
            <?php if ($deleted_count > 0): ?>
                <a href="<?= base_url('admin/users?deleted=1') ?>" style="display: inline-flex; margin-left: 8px; padding: 10px 20px; color: #2e7d32;">Deleted users (<?= (int) $deleted_count ?>)</a>
            <?php endif; ?>
        <?php endif; ?>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7"><?= $show_deleted ? 'No deleted users.' : 'No users.' ?></td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= esc($u['id']) ?></td>
                            <td><?= esc($u['name']) ?></td>
                            <td><?= esc($u['username']) ?></td>
                            <td><?= esc($u['email']) ?></td>
                            <td><span class="status-badge status-badge-approved"><?= esc($u['role']) ?></span></td>
                            <td><?= ! empty($u['is_active']) ? 'Yes' : 'No' ?></td>
                            <td>
                                <?php if ($show_deleted): ?>
                                    <a href="<?= base_url('admin/users/restore/' . $u['id']) ?>" class="link-details" onclick="return confirm('Restore this user?');">Restore</a>
                                <?php else: ?>
                                    <a href="<?= base_url('admin/users/edit/' . $u['id']) ?>" class="link-details">Edit</a>
                                    <?php if (($u['role'] ?? '') !== 'ADMIN'): ?>
                                        &nbsp;|&nbsp;
                                        <a href="<?= base_url('admin/users/delete/' . $u['id']) ?>" class="link-details" onclick="return confirm('Archive this user? You can restore them later from Deleted users.');">Delete</a>
                                    <?php else: ?>
                                        <span style="color: #888; font-size: 0.85rem;">(admin cannot be deleted)</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
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
