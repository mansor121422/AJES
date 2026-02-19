<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - AJES</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 400px; margin: 0 auto; }
        label { display: block; margin-bottom: 6px; }
        input[type="password"] { width: 100%; padding: 8px; margin-bottom: 12px; }
        button { padding: 8px 16px; }
        .message { margin-bottom: 12px; color: red; }
        a { text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('auth/reset-password/' . esc($token)) ?>" method="post">
            <?= csrf_field() ?>
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" required>

            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" required>

            <button type="submit">Update Password</button>
        </form>
    </div>
</body>
</html>

