<?php
    $role = 'PRINCIPAL';
    $name = session()->get('name') ?? 'Elementary Principal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Principal Dashboard</title>
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
                <div class="card-title">School Overview</div>
                <p style="font-size: 13px; color: #555;">A summary of announcements and staff activity can be displayed here.</p>
            </div>
    </div>
</div>
</body>
</html>
