<?php
if (!isset($role)) {
    $role = 'STUDENT';
}

$role = strtoupper($role);

$menuItems = [];

switch ($role) {
    case 'ADMIN':
        $menuItems = [
            ['url' => 'dashboard/admin', 'label' => 'Dashboard Home'],
            ['url' => 'announcements', 'label' => 'Announcements'],
            ['url' => 'chat', 'label' => 'Chat'],
            ['url' => 'records', 'label' => 'Records'],
            ['url' => 'admin/users', 'label' => 'User Management']
        ];
        break;

    case 'STUDENT':
        $menuItems = [
            ['url' => 'dashboard/student', 'label' => 'Dashboard Home'],
            ['url' => 'announcements', 'label' => 'Announcements'],
            ['url' => 'chat', 'label' => 'Chat']
        ];
        break;

    case 'TEACHER':
        $menuItems = [
            ['url' => 'dashboard/teacher', 'label' => 'Dashboard Home'],
            ['url' => 'announcements', 'label' => 'Create Announcement'],
            ['url' => 'dashboard/teacher', 'label' => 'My Sections'],
            ['url' => 'chat', 'label' => 'Chat'],
            ['url' => 'records', 'label' => 'Records']
        ];
        break;

    case 'PRINCIPAL':
        $menuItems = [
            ['url' => 'dashboard/principal', 'label' => 'Dashboard Home'],
            ['url' => 'announcements', 'label' => 'Announcements'],
            ['url' => 'chat', 'label' => 'Chat']
        ];
        break;

    case 'GUIDANCE':
        $menuItems = [
            ['url' => 'dashboard/guidance', 'label' => 'Dashboard Home'],
            ['url' => 'announcements', 'label' => 'Announcements'],
            ['url' => 'chat', 'label' => 'Chat'],
            ['url' => 'records', 'label' => 'Records']
        ];
        break;

    case 'ANNOUNCER':
        $menuItems = [
            ['url' => 'dashboard/announcer', 'label' => 'Dashboard Home'],
            ['url' => 'announcements', 'label' => 'Announcements'],
            ['url' => 'chat', 'label' => 'Chat']
        ];
        break;

    default:
        // Default to student menu
        $menuItems = [
            ['url' => 'dashboard/student', 'label' => 'Dashboard Home'],
            ['url' => 'announcements', 'label' => 'Announcements'],
            ['url' => 'chat', 'label' => 'Chat']
        ];
        break;
}
?>

<div class="topbar" aria-label="AJES dashboard top navigation">
    <div class="topbar-left">
        AJES CRIER
    </div>
    <div class="topbar-right">
        <div class="icon-button" aria-label="Notifications">
            🔔
        </div>
        <div>
            <?= esc($name ?? 'User') ?> <span class="badge"><?= esc($role) ?></span>
        </div>
        <a href="<?= base_url('auth/logout') ?>" style="color: #ffffff; text-decoration: none;">Logout</a>
    </div>
</div>

<div class="layout">
    <div class="sidebar" aria-label="Sidebar navigation">
    <div class="menu">
        <?php foreach ($menuItems as $item): ?>
            <a href="<?= base_url($item['url']) ?>"><?= esc($item['label']) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="content">
