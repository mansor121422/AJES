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
        <div id="notif-panel" style="display:none; position:absolute; top:56px; right:146px; width:340px; max-width:calc(100vw - 24px); background:#fff; border:1px solid #dcedc8; border-radius:12px; box-shadow:0 12px 28px rgba(0,0,0,.18); z-index:2000;">
            <div style="padding:10px 12px; border-bottom:1px solid #e8f5e9; display:flex; justify-content:space-between; align-items:center;">
                <strong style="color:#1b5e20; font-size:0.92rem;">Notifications</strong>
                <a href="<?= base_url('notifications/mark-all-read') ?>" id="notif-mark-all" style="font-size:0.78rem; color:#2e7d32; text-decoration:none;">Mark all read</a>
            </div>
            <div id="notif-list" style="max-height:320px; overflow:auto; padding:8px 0;">
                <div style="padding:10px 12px; color:#558b2f; font-size:0.88rem;">Loading...</div>
            </div>
            <div style="padding:10px 12px; border-top:1px solid #e8f5e9; text-align:right;">
                <a href="<?= base_url('notifications') ?>" style="font-size:0.84rem; color:#2e7d32; text-decoration:none;">View all notifications</a>
            </div>
        </div>
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
    var bell = document.getElementById('notif-bell');
    var badge = document.getElementById('notif-badge');
    var panel = document.getElementById('notif-panel');
    var list = document.getElementById('notif-list');
    var markAll = document.getElementById('notif-mark-all');
    if (!bell || !badge || !panel || !list) return;

    function esc(str) {
        return String(str || '').replace(/[&<>"']/g, function(ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
        });
    }

    function renderList(items) {
        if (!items || !items.length) {
            list.innerHTML = '<div style="padding:10px 12px; color:#558b2f; font-size:0.88rem;">No notifications yet.</div>';
            return;
        }
        var html = '';
        items.forEach(function(item) {
            var unreadStyle = item.is_read ? '' : 'background:#f1f8e9;';
            var weight = item.is_read ? '400' : '600';
            html += '<a href="' + esc(item.url) + '" style="display:block; padding:10px 12px; border-bottom:1px solid #f1f8e9; text-decoration:none; color:#1b5e20; ' + unreadStyle + '">';
            html += '<div style="font-size:0.9rem; font-weight:' + weight + ';">' + esc(item.message) + '</div>';
            html += '<div style="margin-top:4px; font-size:0.78rem; color:#558b2f;">' + esc(item.created_at) + '</div>';
            html += '</a>';
        });
        list.innerHTML = html;
    }

    function refreshCount() {
        return fetch('<?= base_url('notifications/count') ?>', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                var n = (d && d.count) ? parseInt(d.count, 10) : 0;
                if (n > 0) {
                    badge.textContent = n > 99 ? '99+' : n;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            });
    }

    function refreshList() {
        return fetch('<?= base_url('notifications/recent') ?>', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                renderList((d && d.items) ? d.items : []);
            })
            .catch(function() {
                list.innerHTML = '<div style="padding:10px 12px; color:#c62828; font-size:0.88rem;">Could not load notifications.</div>';
            });
    }

    function openPanel() {
        panel.style.display = 'block';
        refreshList();
    }

    function closePanel() {
        panel.style.display = 'none';
    }

    bell.addEventListener('click', function(e) {
        e.preventDefault();
        if (panel.style.display === 'block') {
            closePanel();
            return;
        }
        openPanel();
    });

    document.addEventListener('click', function(e) {
        if (!panel.contains(e.target) && !bell.contains(e.target)) {
            closePanel();
        }
    });

    if (markAll) {
        markAll.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('<?= base_url('notifications/mark-all-read') ?>', { credentials: 'same-origin' })
                .then(function() {
                    return Promise.all([refreshCount(), refreshList()]);
                })
                .catch(function() {});
        });
    }

    refreshCount().catch(function() {});
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
