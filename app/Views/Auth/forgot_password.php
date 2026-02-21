<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        body { margin: 40px; }
        label { margin-bottom: 6px; }
        input[type="email"] { padding: 8px; margin-bottom: 12px; }
    </style>
    </head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('auth/forgot-password') ?>" method="post">
            <?= csrf_field() ?>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= esc(old('email')) ?>">
            <button type="submit">Send Reset Link</button>
        </form>

        <p style="margin-top: 10px;">
            <a href="<?= base_url('/') ?>">Back to login</a>
        </p>
    </div>
</body>
</html>

