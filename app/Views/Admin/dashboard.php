<?php
    $role = 'ADMIN';
    $name = session()->get('name') ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Admin Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>
            <div class="card">
                <div class="card-title">
                    Welcome back, <?= esc($name) ?>!
                </div>
                <div>
                    You are logged in as <strong><?= esc($role) ?></strong>.
                </div>
            </div>

            <div class="card">
                <div class="card-title">System Overview</div>
                <p style="font-size: 13px; color: #555;">Here you can manage users, review logs, and access announcements, chat, and records modules.</p>
            </div>
    </div>
</div>
</body>
</html>
