<?php
use App\Libraries\AdminPrivilege;
use App\Libraries\RoleRegistry;

$active_section = $active_section ?? 'users';
$users = $users ?? [];
$roles = $roles ?? [];
$show_deleted = $show_deleted ?? false;
$deleted_count = $deleted_count ?? 0;
$privilegeLabels = $privilege_labels ?? AdminPrivilege::labels();
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

    <?php include(APPPATH . 'Views/Admin/Users/_tabs.php'); ?>

    <?php if ($active_section === 'roles'): ?>
        <div class="card">
            <div class="card-title">Roles &amp; privileges</div>
            <p style="margin-bottom: 12px; color: #558b2f;">
                Create roles and choose which features each role can access. When you add a user, assign one of these roles — privileges are applied automatically.
            </p>
            <a href="<?= base_url('admin/users/roles/create') ?>" class="login-button" style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; margin-bottom: 16px;">Add role</a>
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Dashboard</th>
                        <th>Privileges</th>
                        <th>System</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($roles === []): ?>
                        <tr><td colspan="6">No roles yet. Click <strong>Add role</strong> to create one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($roles as $r): ?>
                            <?php
                                $privKeys = AdminPrivilege::normalize($r['privileges'] ?? []);
                                $privNames = [];
                                foreach ($privKeys as $k) {
                                    $privNames[] = $privilegeLabels[$k] ?? $k;
                                }
                            ?>
                            <tr>
                                <td><?= esc($r['slug'] ?? '') ?></td>
                                <td><?= esc($r['name'] ?? '') ?></td>
                                <td><?= esc($r['dashboard_type'] ?? 'generic') ?></td>
                                <td style="font-size: 0.85rem;"><?= esc($privNames !== [] ? implode(', ', $privNames) : '—') ?></td>
                                <td><?= (int) ($r['is_system'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                                <td>
                                    <a href="<?= base_url('admin/users/roles/edit/' . (int) $r['id']) ?>">Edit</a>
                                    <?php if ((int) ($r['is_system'] ?? 0) !== 1): ?>
                                        &nbsp;|&nbsp;
                                        <a href="<?= base_url('admin/users/roles/delete/' . (int) $r['id']) ?>" onclick="return confirm('Delete this role?');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-title"><?= $show_deleted ? 'Deleted users' : 'All users' ?></div>
            <p style="margin-bottom: 12px; color: #558b2f;">
                <?php if ($show_deleted): ?>
                    Users are soft-deleted (not removed from the database). Restore to make them active again.
                <?php else: ?>
                    Create, edit, and archive system users. Assign a role from the <a href="<?= base_url('admin/users?section=roles') ?>" style="color: #2e7d32;">Roles &amp; privileges</a> tab.
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
                    <?php if ($users === []): ?>
                        <tr><td colspan="7"><?= $show_deleted ? 'No deleted users.' : 'No users.' ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= esc($u['id']) ?></td>
                                <td><?= esc($u['name']) ?></td>
                                <td><?= esc($u['username']) ?></td>
                                <td><?= esc($u['email']) ?></td>
                                <td><span class="status-badge status-badge-approved"><?= esc(RoleRegistry::displayName((string) ($u['role'] ?? ''))) ?></span></td>
                                <td><?= ! empty($u['is_active']) ? 'Yes' : 'No' ?></td>
                                <td>
                                    <?php if ($show_deleted): ?>
                                        <a href="<?= base_url('admin/users/restore/' . $u['id']) ?>" class="link-details" onclick="return confirm('Restore this user?');">Restore</a>
                                    <?php else: ?>
                                        <a href="<?= base_url('admin/users/edit/' . $u['id']) ?>" class="link-details">Edit</a>
                                        <?php if (($u['role'] ?? '') !== 'ADMIN'): ?>
                                            &nbsp;|&nbsp;
                                            <a href="<?= base_url('admin/users/delete/' . $u['id']) ?>" class="link-details" onclick="return confirm('Archive this user?');">Delete</a>
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
    <?php endif; ?>

    </main>
</div>
</body>
</html>
