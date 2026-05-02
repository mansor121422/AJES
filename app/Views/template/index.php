<?php
if (!isset($role)) {
    $role = 'STUDENT';
}

$role = strtoupper($role);
$menuItems = [];

switch ($role) {
    case 'ADMIN':
        $menuItems = [
            ['url' => 'dashboard/admin', 'label' => 'Dashboard Home', 'icon' => '📊'],
            ['url' => 'admin/sections', 'label' => 'Sections', 'icon' => '📂'],
            ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'],
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
            ['url' => 'admin/chat-logs', 'label' => 'Chat Logs', 'icon' => '📋'],
            ['url' => 'records', 'label' => 'Records', 'icon' => '📁'],
            ['url' => 'admin/users', 'label' => 'User Management', 'icon' => '👥'],
        ];
        break;
    case 'STUDENT':
        $menuItems = [
            ['url' => 'dashboard/student', 'label' => 'Dashboard Home', 'icon' => '📊'],
            ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'],
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
        ];
        break;
    case 'TEACHER':
        $menuItems = [
            ['url' => 'dashboard/teacher', 'label' => 'Dashboard Home', 'icon' => '📊'],
            ['url' => 'announcements', 'label' => 'Create Announcement', 'icon' => '📢'],
            ['url' => 'teacher/sections', 'label' => 'My Sections', 'icon' => '📂'],
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
        ];
        break;
    case 'PRINCIPAL':
        $menuItems = [
            ['url' => 'dashboard/principal', 'label' => 'Dashboard Home', 'icon' => '📊'],
            ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'],
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
        ];
        break;
    case 'GUIDANCE':
        $menuItems = [
            ['url' => 'dashboard/guidance', 'label' => 'Dashboard Home', 'icon' => '📊'],
            ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'],
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
            ['url' => 'records', 'label' => 'Records', 'icon' => '📁'],
        ];
        break;
    case 'ANNOUNCER':
        $menuItems = [
            ['url' => 'dashboard/announcer', 'label' => 'Dashboard Home', 'icon' => '📊'],
            ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'],
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
        ];
        break;
    default:
        $menuItems = [
            ['url' => 'dashboard/student', 'label' => 'Dashboard Home', 'icon' => '📊'],
            ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'],
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
        ];
}

$currentUri = uri_string();
$sessionUserId = (int) (session()->get('user_id') ?? 0);
$topbarPhotoUrl = null;
if ($sessionUserId > 0) {
    $sessionUser = (new \App\Models\UserModel())->find($sessionUserId);
    $profilePhoto = trim((string) ($sessionUser['profile_photo'] ?? ''));
    if ($profilePhoto !== '') {
        $topbarPhotoUrl = base_url($profilePhoto);
    }
}
?>
<div class="topbar" aria-label="AJES dashboard top navigation">
    <div class="topbar-left">AJES CRIER</div>
    <div class="topbar-right">
        <a href="<?= base_url('notifications') ?>" class="icon-button" aria-label="Notifications" id="notif-bell">🔔<span class="icon-badge" id="notif-badge" style="display: none;">0</span></a>
        <a href="<?= base_url('profile') ?>" style="display:flex; align-items:center; gap:8px; color:#fff; text-decoration:none;">
            <?php if ($topbarPhotoUrl): ?>
                <img src="<?= esc($topbarPhotoUrl) ?>" alt="Profile" style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,0.7);">
            <?php else: ?>
                <span style="width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center;">👤</span>
            <?php endif; ?>
            <span><?= esc($name ?? 'User') ?> <span class="badge"><?= esc($role) ?></span></span>
        </a>
        <a href="<?= base_url('auth/logout') ?>" style="color: #fff; text-decoration: none; font-weight: 500;">Logout</a>
    </div>
</div>
<script>
(function() {
    var badge = document.getElementById('notif-badge');
    if (!badge) return;
    fetch('<?= base_url('notifications/count') ?>', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var n = (d && d.count) ? parseInt(d.count, 10) : 0;
            if (n > 0) {
                badge.textContent = n > 99 ? '99+' : n;
                badge.style.display = 'inline-block';
            }
        })
        .catch(function() {});
})();
</script>
<div class="layout">
    <aside class="sidebar" aria-label="Sidebar navigation">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">📢</div>
            <span>AJES CRIER</span>
        </div>
        <nav class="menu">
            <?php foreach ($menuItems as $item):
                $isActive = (strpos($currentUri, $item['url']) === 0);
            ?>
                <a href="<?= base_url($item['url']) ?>" class="<?= $isActive ? 'active' : '' ?>">
                    <span class="menu-icon"><?= $item['icon'] ?? '•' ?></span>
                    <?= esc($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= base_url('profile') ?>">⚙️ Profile Settings</a>
        </div>
    </aside>
    <main class="content">
<script>
(function () {
    var EXIT_MS = 380;
    function pathKey(url) {
        try {
            var p = new URL(url, location.href).pathname;
            p = p.replace(/\/index\.php/i, '').replace(/\/+$/, '') || '/';
            return p;
        } catch (e) { return ''; }
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a || !a.getAttribute('href')) return;
        if (e.defaultPrevented) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (a.target === '_blank') return;
        if (a.hasAttribute('download')) return;
        var hrefAttr = a.getAttribute('href');
        if (hrefAttr && hrefAttr.charAt(0) === '#') return;
        if (!a.closest('.sidebar')) return;
        var url = a.href;
        if (!url || url.indexOf(location.origin) !== 0) return;
        if (pathKey(url) === pathKey(location.href)) {
            e.preventDefault();
            return;
        }
        a.classList.add('menu-link-pulse');
        if (document.startViewTransition) {
            e.preventDefault();
            document.startViewTransition(function () {
                location.href = url;
            });
            return;
        }
        e.preventDefault();
        document.body.classList.add('ajes-nav-leaving');
        setTimeout(function () {
            location.href = url;
        }, EXIT_MS);
    }, true);
})();
</script>
        <!-- React entry script compiled by Vite (frontend/) into public/react/main.js -->
<script type="module" src="<?= base_url('react/main.js') ?>"></script>
