<?php
    $role = 'STUDENT';
    $name = session()->get('name') ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Student Dashboard</title>
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
                <div class="card-title">Announcements</div>
                <p style="font-size: 13px; color: #555;">Announcements for your grade and section will appear here.</p>
            </div>

            <div class="card">
                <div class="card-title">Messages</div>
                <p style="font-size: 13px; color: #555;">Messages from your teachers and guidance office will appear here.</p>
            </div>
    </div>
</div>
</body>
</html>
