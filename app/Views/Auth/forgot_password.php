<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        body {
            display: flex;
            height: 100vh;
        }
    </style>
</head>
<body>
    <div class="left-panel">
        <div class="logo-container">
            <img src="/AJES/public/assets/images/ajes-logo.png" alt="AJES Logo" class="logo">
        </div>
    </div>

    <div class="right-panel">
        <div class="login-container">
            <h1>Forgot Password</h1>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="message success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>

            <form action="<?= base_url('auth/forgot-password') ?>" method="post">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= esc(old('email')) ?>">
                </div>

                <button type="submit" class="login-button">
                    <span>📩</span> Send reset link
                </button>
            </form>

            <p style="margin-top: 16px; text-align: center;">
                <a href="<?= base_url('/') ?>" style="color: #c8e6c9;">Back to login</a>
            </p>
        </div>
    </div>
</body>
</html>

