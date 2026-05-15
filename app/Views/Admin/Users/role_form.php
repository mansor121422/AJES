<?php
use App\Libraries\AdminPrivilege;

$isEdit = $role_row !== null;
$row = $role_row ?? [];
$privilegeLabels = $privilege_labels ?? [];
$assigned = $assigned_privileges ?? AdminPrivilege::normalize(old('privileges'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Add' ?> role - User Management</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header"><?= $isEdit ? 'Edit role' : 'Add role' ?></h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php
    $active_section = 'roles';
    include(APPPATH . 'Views/Admin/Users/_tabs.php');
    ?>

    <div class="card">
        <div class="card-title"><?= $isEdit ? esc($row['name'] ?? '') : 'New role' ?></div>
        <form action="<?= $isEdit ? base_url('admin/users/roles/update/' . (int) $row['id']) : base_url('admin/users/roles/store') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name" style="color: #1b5e20;">Display name</label>
                <input type="text" id="name" name="name" required value="<?= esc(old('name', $row['name'] ?? '')) ?>" placeholder="e.g. Head Teacher" style="width: 100%; padding: 10px; border: 1px solid #c8e6c9; border-radius: 8px;">
            </div>
            <div style="margin-bottom: 16px; padding: 12px; border: 1px solid #c8e6c9; border-radius: 10px; background: #f8fff8;">
                <div style="font-weight: 600; color: #1b5e20; margin-bottom: 8px;">Privileges for this role</div>
                <small style="display:block; margin-bottom: 10px; color: #558b2f;">Users assigned this role receive these privileges automatically.</small>
                <div style="margin-bottom: 10px;">
                    <button type="button" id="privileges-select-all" style="margin-right: 8px; padding: 6px 10px; border: 1px solid #81c784; border-radius: 6px; background: #e8f5e9; color: #1b5e20; cursor: pointer;">Select all</button>
                    <button type="button" id="privileges-clear-all" style="padding: 6px 10px; border: 1px solid #c8e6c9; border-radius: 6px; background: #fff; color: #2e7d32; cursor: pointer;">Clear all</button>
                </div>
                <?php foreach ($privilegeLabels as $key => $label): ?>
                    <label style="display: block; margin-bottom: 6px; color: #1b5e20;">
                        <input type="checkbox" name="privileges[]" value="<?= esc($key) ?>" <?= in_array($key, $assigned, true) ? 'checked' : '' ?>>
                        <?= esc($label) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="login-button" style="width: auto; padding: 10px 24px;"><?= $isEdit ? 'Save changes' : 'Create role' ?></button>
            <a href="<?= base_url('admin/users?section=roles') ?>" style="margin-left: 12px; color: #2e7d32;">Cancel</a>
        </form>
    </div>

    <script>
    (function () {
        var checks = document.querySelectorAll('input[name="privileges[]"]');
        var selectAll = document.getElementById('privileges-select-all');
        var clearAll = document.getElementById('privileges-clear-all');
        if (selectAll) selectAll.addEventListener('click', function () { checks.forEach(function (c) { c.checked = true; }); });
        if (clearAll) clearAll.addEventListener('click', function () { checks.forEach(function (c) { c.checked = false; }); });
    })();
    </script>

    </main>
</div>
</body>
</html>
