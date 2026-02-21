<?php
    $role = 'GUIDANCE';
    $name = session()->get('name') ?? 'Guidance Counselor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Guidance Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>
            <div class="card">
                <div class="card-title">
                    Welcome back, <?= esc($name) ?>!
                </div>
                <div>
                    Your role: <strong><?= esc($role) ?></strong>
                </div>
            </div>

            <div class="card">
                <div class="card-title">Guidance Overview</div>
                <p style="font-size: 13px; color: #555;">You can view counseling-related records and student announcements here.</p>
            </div>
    </div>
</div>
</body>
</html>
