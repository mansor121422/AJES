<?php
$role = $role ?? 'ADMIN';
$name = $name ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Chat</h1>

    <div class="card">
        <p style="color: #558b2f;">School messenger. (Chat with bad-word filter and principal bot will be added here.)</p>
        <p style="margin-top: 12px;"><a href="<?= base_url('dashboard/admin') ?>" class="link-details">← Back to Dashboard</a></p>
    </div>

    </main>
</div>
</body>
</html>
