<?php
$active = $active_section ?? 'users';
?>
<div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="<?= base_url('admin/users') ?>"
       class="login-button"
       style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; <?= $active === 'users' ? '' : 'background: #fff; color: #2e7d32; border: 1px solid #81c784;' ?>">
        Users
    </a>
    <a href="<?= base_url('admin/users?section=roles') ?>"
       class="login-button"
       style="display: inline-flex; width: auto; padding: 10px 20px; text-decoration: none; <?= $active === 'roles' ? '' : 'background: #fff; color: #2e7d32; border: 1px solid #81c784;' ?>">
        Roles &amp; privileges
    </a>
</div>
