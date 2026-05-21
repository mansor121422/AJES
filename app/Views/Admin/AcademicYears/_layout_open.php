<?php
$ay_page_title = $ay_page_title ?? 'Academic Years';
?>
<?php include APPPATH . 'Views/Admin/AcademicYears/_styles.php'; ?>

<div class="ay-module">
    <?php include APPPATH . 'Views/Admin/AcademicYears/_sidebar.php'; ?>
    <div class="ay-panel">
        <h1 class="dashboard-header"><?= esc($ay_page_title) ?></h1>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
