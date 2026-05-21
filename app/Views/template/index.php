<?php
use App\Libraries\AdminPrivilege;
use App\Libraries\RoleRegistry;

if (!isset($role)) {
    $role = 'STUDENT';
}

$role = strtoupper($role);
$menuItems = [];
$featurePrivileges = AdminPrivilege::effectiveForRole($role, session()->get('feature_privileges') ?? session()->get('admin_privileges'));
$hasFullFeatureAccess = $featurePrivileges === [];
$canFeature = static function (string $key) use ($hasFullFeatureAccess, $featurePrivileges): bool {
    if ($hasFullFeatureAccess) {
        return true;
    }
    return in_array($key, $featurePrivileges, true);
};

switch ($role) {
    case 'SUPER_ADMIN':
        $menuItems = [
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
        ];
        if ($canFeature('dashboard')) {
            array_unshift($menuItems, ['url' => 'dashboard/admin', 'label' => 'Dashboard Home', 'icon' => '📊']);
        }
        if ($canFeature('sections') || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $menuItems[] = ['url' => 'admin/sections', 'label' => 'Sections', 'icon' => '📂'];
        }
        if ($canFeature('academic_years') || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $menuItems[] = ['url' => 'admin/academic-years', 'label' => 'Academic Years', 'icon' => '📅'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        if ($canFeature('records')) {
            $menuItems[] = ['url' => 'records', 'label' => 'Reports', 'icon' => '📁'];
        }
        if ($canFeature('chat_logs')) {
            $menuItems[] = ['url' => 'chatlogs', 'label' => 'Chat Logs', 'icon' => '📋'];
        }
        if ($canFeature('student_log') || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $menuItems[] = ['url' => 'admin/students-log', 'label' => 'Students Log', 'icon' => '🎓'];
        }
        if ($canFeature('user_management')) {
            $menuItems[] = ['url' => 'admin/users', 'label' => 'User Management', 'icon' => '👥'];
        }
        if ($canFeature('system_settings')) {
            $menuItems[] = ['url' => 'sysadmin/settings', 'label' => 'System Settings', 'icon' => '⚙️'];
        }
        if ($canFeature('chatbot_management')) {
            $menuItems[] = ['url' => 'sysadmin/chatbot', 'label' => 'Chatbot', 'icon' => '🤖'];
        }
        if ($canFeature('backup_restore')) {
            $menuItems[] = ['url' => 'sysadmin/backup', 'label' => 'Backup & Restore', 'icon' => '💾'];
        }
        if ($canFeature('security_logs')) {
            $menuItems[] = ['url' => 'sysadmin/security-logs', 'label' => 'Security Logs', 'icon' => '🛡️'];
        }
        break;
    case 'ADMIN':
        $menuItems = [
            ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'],
        ];
        if ($canFeature('dashboard')) {
            array_unshift($menuItems, ['url' => 'dashboard/admin', 'label' => 'Dashboard Home', 'icon' => '📊']);
        }
        if ($canFeature('sections') || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $menuItems[] = ['url' => 'admin/sections', 'label' => 'Sections', 'icon' => '📂'];
        }
        if ($canFeature('academic_years') || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $menuItems[] = ['url' => 'admin/academic-years', 'label' => 'Academic Years', 'icon' => '📅'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        if ($canFeature('chat_logs')) {
            $menuItems[] = ['url' => 'chatlogs', 'label' => 'Chat Logs', 'icon' => '📋'];
        }
        if ($canFeature('records')) {
            $menuItems[] = ['url' => 'records', 'label' => 'Records', 'icon' => '📁'];
        }
        if ($canFeature('student_log') || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $menuItems[] = ['url' => 'admin/students-log', 'label' => 'Students Log', 'icon' => '🎓'];
        }
        if ($canFeature('user_management')) {
            $menuItems[] = ['url' => 'admin/users', 'label' => 'User Management', 'icon' => '👥'];
        }
        if ($canFeature('system_settings')) {
            $menuItems[] = ['url' => 'sysadmin/settings', 'label' => 'System Settings', 'icon' => '⚙️'];
        }
        if ($canFeature('chatbot_management')) {
            $menuItems[] = ['url' => 'sysadmin/chatbot', 'label' => 'Chatbot', 'icon' => '🤖'];
        }
        if ($canFeature('backup_restore')) {
            $menuItems[] = ['url' => 'sysadmin/backup', 'label' => 'Backup & Restore', 'icon' => '💾'];
        }
        if ($canFeature('security_logs')) {
            $menuItems[] = ['url' => 'sysadmin/security-logs', 'label' => 'Security Logs', 'icon' => '🛡️'];
        }
        break;
    case 'STUDENT':
        if ($canFeature('dashboard')) {
            $menuItems[] = ['url' => 'dashboard/student', 'label' => 'Dashboard Home', 'icon' => '📊'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        $menuItems[] = ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'];
        break;
    case 'TEACHER':
        if ($canFeature('dashboard')) {
            $menuItems[] = ['url' => 'dashboard/teacher', 'label' => 'Dashboard Home', 'icon' => '📊'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Create Announcement', 'icon' => '📢'];
        }
        if ($canFeature('teacher_sections')) {
            $menuItems[] = ['url' => 'teacher/sections', 'label' => 'My Sections', 'icon' => '📂'];
        }
        $menuItems[] = ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'];
        break;
    case 'PRINCIPAL':
        if ($canFeature('dashboard')) {
            $menuItems[] = ['url' => 'dashboard/principal', 'label' => 'Dashboard Home', 'icon' => '📊'];
        }
        if ($canFeature('academic_years')) {
            $menuItems[] = ['url' => 'admin/academic-years', 'label' => 'Academic Years', 'icon' => '📅'];
        }
        if ($canFeature('sections')) {
            $menuItems[] = ['url' => 'admin/sections', 'label' => 'Sections', 'icon' => '📂'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        if ($canFeature('records')) {
            $menuItems[] = ['url' => 'records', 'label' => 'Reports', 'icon' => '📁'];
        }
        if ($canFeature('chat_logs')) {
            $menuItems[] = ['url' => 'chatlogs', 'label' => 'Chat Monitoring', 'icon' => '📋'];
        }
        $menuItems[] = ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'];
        break;
    case 'VICE_PRINCIPAL':
        if ($canFeature('dashboard')) {
            $menuItems[] = ['url' => 'dashboard/vice-principal', 'label' => 'Dashboard Home', 'icon' => '📊'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        if ($canFeature('records')) {
            $menuItems[] = ['url' => 'records', 'label' => 'Reports', 'icon' => '📁'];
        }
        if ($canFeature('chat_logs')) {
            $menuItems[] = ['url' => 'chatlogs', 'label' => 'Chat Monitoring', 'icon' => '📋'];
        }
        $menuItems[] = ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'];
        break;
    case 'HEAD_TEACHER':
        if ($canFeature('dashboard')) {
            $menuItems[] = ['url' => 'dashboard/head-teacher', 'label' => 'Dashboard Home', 'icon' => '📊'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        if ($canFeature('records')) {
            $menuItems[] = ['url' => 'records', 'label' => 'Reports', 'icon' => '📁'];
        }
        if ($canFeature('chat_logs')) {
            $menuItems[] = ['url' => 'chatlogs', 'label' => 'Chat Monitoring', 'icon' => '📋'];
        }
        $menuItems[] = ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'];
        break;
    case 'GUIDANCE':
        if ($canFeature('dashboard')) {
            $menuItems[] = ['url' => 'dashboard/guidance', 'label' => 'Dashboard Home', 'icon' => '📊'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        $menuItems[] = ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'];
        if ($canFeature('records')) {
            $menuItems[] = ['url' => 'records', 'label' => 'Records', 'icon' => '📁'];
        }
        break;
    case 'ANNOUNCER':
        if ($canFeature('dashboard')) {
            $menuItems[] = ['url' => 'dashboard/announcer', 'label' => 'Dashboard Home', 'icon' => '📊'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        $menuItems[] = ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'];
        break;
    default:
        $dashType = RoleRegistry::dashboardType($role);
        $dashUrl = match ($dashType) {
            'admin'          => 'dashboard/admin',
            'principal'      => 'dashboard/principal',
            'vice_principal' => 'dashboard/vice-principal',
            'head_teacher'   => 'dashboard/head-teacher',
            'teacher'        => 'dashboard/teacher',
            'student'        => 'dashboard/student',
            'guidance'       => 'dashboard/guidance',
            'announcer'      => 'dashboard/announcer',
            default          => 'dashboard',
        };
        if ($canFeature('dashboard')) {
            $menuItems[] = ['url' => $dashUrl, 'label' => 'Dashboard Home', 'icon' => '📊'];
        }
        if ($canFeature('announcements')) {
            $menuItems[] = ['url' => 'announcements', 'label' => 'Announcements', 'icon' => '📢'];
        }
        if ($canFeature('sections') || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $menuItems[] = ['url' => 'admin/sections', 'label' => 'Sections', 'icon' => '📂'];
        }
        if ($canFeature('records')) {
            $menuItems[] = ['url' => 'records', 'label' => 'Reports', 'icon' => '📁'];
        }
        if ($canFeature('chat_logs')) {
            $menuItems[] = ['url' => 'chatlogs', 'label' => 'Chat Monitoring', 'icon' => '📋'];
        }
        if ($canFeature('student_log') || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $menuItems[] = ['url' => 'admin/students-log', 'label' => 'Students Log', 'icon' => '🎓'];
        }
        if ($canFeature('user_management')) {
            $menuItems[] = ['url' => 'admin/users', 'label' => 'User Management', 'icon' => '👥'];
        }
        if ($canFeature('system_settings')) {
            $menuItems[] = ['url' => 'sysadmin/settings', 'label' => 'System Settings', 'icon' => '⚙️'];
        }
        if ($canFeature('chatbot_management')) {
            $menuItems[] = ['url' => 'sysadmin/chatbot', 'label' => 'Chatbot', 'icon' => '🤖'];
        }
        if ($canFeature('backup_restore')) {
            $menuItems[] = ['url' => 'sysadmin/backup', 'label' => 'Backup & Restore', 'icon' => '💾'];
        }
        if ($canFeature('security_logs')) {
            $menuItems[] = ['url' => 'sysadmin/security-logs', 'label' => 'Security Logs', 'icon' => '🛡️'];
        }
        $menuItems[] = ['url' => 'chat', 'label' => 'Chat', 'icon' => '💬'];
        break;
}

$currentUri = uri_string();
$sessionUserId = (int) (session()->get('user_id') ?? 0);
$sessionUser     = null;
$topbarPhotoUrl  = null;
if ($sessionUserId > 0) {
    $sessionUser = (new \App\Models\UserModel())->find($sessionUserId);
    if ($sessionUser) {
        $profilePhoto = trim((string) ($sessionUser['profile_photo'] ?? ''));
        if ($profilePhoto !== '') {
            $topbarPhotoUrl = base_url($profilePhoto);
        }
    }
}
$logoutPrefillUsername = '';
if ($sessionUser) {
    $logoutPrefillUsername = trim((string) ($sessionUser['username'] ?? $sessionUser['email'] ?? ''));
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
        <button type="button" class="ajes-topbar-logout" id="ajes-logout-open">Logout</button>
    </div>
</div>

<div id="ajes-logout-modal" class="ajes-logout-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ajes-logout-title">
    <div class="ajes-logout-modal__backdrop" data-logout-dismiss="1" tabindex="-1"></div>
    <div class="ajes-logout-modal__card">
        <div class="ajes-logout-modal__head">
            <h2 id="ajes-logout-title" class="ajes-logout-modal__title">Log out?</h2>
        </div>
        <div class="ajes-logout-modal__body">
            <p class="ajes-logout-modal__lead">Are you sure you want to log out?</p>
            <p class="ajes-logout-modal__muted">If you continue, you’ll need to sign in again to use your account.</p>
            <div class="ajes-logout-save" role="group" aria-label="Save login information">
                <div class="ajes-logout-save__choices">
                    <button type="button" class="ajes-logout-choice" data-logout-save="1" id="ajes-logout-save-yes">Save</button>
                    <button type="button" class="ajes-logout-choice is-selected" data-logout-save="0" id="ajes-logout-save-no">Don't save</button>
                </div>
            </div>
        </div>
        <div class="ajes-logout-modal__actions">
            <button type="button" class="ajes-logout-btn ajes-logout-btn--ghost" id="ajes-logout-cancel">Cancel</button>
            <button type="button" class="ajes-logout-btn ajes-logout-btn--primary" id="ajes-logout-confirm">Log out</button>
        </div>
    </div>
</div>

<script>
window.AJES_LOGOUT_USER = <?= json_encode($logoutPrefillUsername, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script>
(function () {
    var openBtn = document.getElementById('ajes-logout-open');
    var modal = document.getElementById('ajes-logout-modal');
    if (!openBtn || !modal) return;

    var cancel = document.getElementById('ajes-logout-cancel');
    var confirmBtn = document.getElementById('ajes-logout-confirm');
    var backdrop = modal.querySelector('[data-logout-dismiss]');
    var choiceBtns = modal.querySelectorAll('.ajes-logout-choice');
    var saveChoice = '0';
    var lastFocus = null;

    function setSave(v) {
        saveChoice = v;
        choiceBtns.forEach(function (b) {
            var on = b.getAttribute('data-logout-save') === v;
            b.classList.toggle('is-selected', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    choiceBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            setSave(b.getAttribute('data-logout-save') || '0');
        });
    });

    function openModal() {
        lastFocus = document.activeElement;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ajes-logout-modal-open');
        setSave('0');
        cancel.focus();
    }

    function closeModal() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ajes-logout-modal-open');
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    openBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });
    if (cancel) cancel.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            try {
                if (saveChoice === '1') {
                    localStorage.setItem('ajes_save_login', '1');
                    var u = typeof window.AJES_LOGOUT_USER === 'string' ? window.AJES_LOGOUT_USER : '';
                    if (u) localStorage.setItem('ajes_saved_username', u);
                } else {
                    localStorage.setItem('ajes_save_login', '0');
                    localStorage.removeItem('ajes_saved_username');
                }
            } catch (err) {}
            window.location.href = <?= json_encode(base_url('auth/logout')) ?>;
        });
    }

    document.addEventListener('keydown', function (e) {
        if (modal.hidden) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal();
        }
    });
})();
</script>
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
            <?php
            $isMenuActive = static function (string $current, string $menuUrl): bool {
                $current = trim($current, '/');
                $menuUrl = trim($menuUrl, '/');

                if ($current === $menuUrl) {
                    return true;
                }

                return $menuUrl !== '' && strpos($current, $menuUrl . '/') === 0;
            };
            foreach ($menuItems as $item):
                $isActive = $isMenuActive($currentUri, (string) ($item['url'] ?? ''));
            ?>
                <a href="<?= base_url($item['url']) ?>" class="<?= $isActive ? 'active' : '' ?>">
                    <span class="menu-icon"><?= $item['icon'] ?? '•' ?></span>
                    <?= esc($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
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
