<?php
$ayTab = $ay_tab ?? 'overview';
$activeYear = $active_year ?? \App\Libraries\AcademicYearManager::getActive();
$historyYear = $history_year ?? null;
$activeYearId = (int) ($activeYear['id'] ?? 0);
$activeLabel = trim((string) ($activeYear['label'] ?? ''));

$isActive = static function (string $tab) use ($ayTab): bool {
    return $ayTab === $tab;
};
?>
<aside class="ay-sidebar" aria-label="Academic year navigation">
    <div class="ay-sidebar-brand">
        <div class="ay-brand-icon">📅</div>
        <div class="ay-brand-title">Academic Years</div>
        <div class="ay-brand-sub">School year control panel</div>
        <?php if ($activeLabel !== ''): ?>
            <span class="ay-sidebar-active-pill">Active: <?= esc($activeLabel) ?></span>
        <?php endif; ?>
    </div>

    <nav class="ay-side-nav">
        <a href="<?= base_url('admin/academic-years') ?>" class="ay-side-link <?= $isActive('overview') ? 'active' : '' ?>">
            <span class="ay-side-icon">🏠</span> Overview
        </a>
        <?php if ($activeYearId > 0): ?>
            <a href="<?= base_url('admin/academic-years/close') ?>" class="ay-side-link ay-side-warn <?= $isActive('close') ? 'active' : '' ?>">
                <span class="ay-side-icon">🎓</span> End school year
            </a>
            <a href="<?= base_url('admin/academic-years/history/' . $activeYearId) ?>" class="ay-side-link <?= $isActive('records') ? 'active' : '' ?>">
                <span class="ay-side-icon">📋</span> Current enrollments
            </a>
        <?php endif; ?>
    </nav>

    <?php
        $sidebarYears = $sidebar_years ?? [];
        if ($sidebarYears === []) {
            try {
                $sidebarYears = (new \App\Models\AcademicYearModel())->orderBy('label', 'DESC')->findAll(12);
            } catch (\Throwable $e) {
                $sidebarYears = [];
            }
        }
    ?>
    <?php if ($sidebarYears !== []): ?>
        <div class="ay-side-divider"></div>
        <div class="ay-side-section">All years</div>
        <nav class="ay-side-nav">
            <?php foreach ($sidebarYears as $sy): ?>
                <?php
                    $syId = (int) ($sy['id'] ?? 0);
                    $syLabel = (string) ($sy['label'] ?? '');
                    $sySt = (string) ($sy['status'] ?? '');
                    $viewing = $historyYear !== null && (int) ($historyYear['id'] ?? 0) === $syId;
                ?>
                <a href="<?= base_url('admin/academic-years/history/' . $syId) ?>"
                   class="ay-side-link <?= $viewing ? 'active' : '' ?>"
                   style="<?= $sySt === 'active' && ! $viewing ? 'font-weight:600;' : '' ?>">
                    <span class="ay-side-icon"><?= $sySt === 'active' ? '●' : ($sySt === 'closed' ? '○' : '◌') ?></span>
                    <?= esc($syLabel) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <div class="ay-side-divider"></div>
    <div class="ay-side-section">Quick links</div>
    <nav class="ay-side-nav">
        <a href="<?= base_url('admin/sections') ?>" class="ay-side-link">
            <span class="ay-side-icon">📂</span> Sections
        </a>
        <a href="<?= base_url('admin/students-log') ?>" class="ay-side-link">
            <span class="ay-side-icon">👨‍🎓</span> Students log
        </a>
    </nav>

    <div class="ay-side-divider"></div>
    <nav class="ay-side-nav">
        <a href="<?= base_url('dashboard/admin') ?>" class="ay-side-link">
            <span class="ay-side-icon">←</span> Admin dashboard
        </a>
    </nav>
</aside>
