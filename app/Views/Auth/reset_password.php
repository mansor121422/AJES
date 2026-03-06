<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        body {
            display: flex;
            height: 100vh;
        }
        .reset-instruction {
            color: #fff;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1rem;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
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
            <h1>Reset Password</h1>
            <p class="reset-instruction">Enter your new password below.</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <form action="<?= base_url('auth/reset-password/' . esc($token)) ?>" method="post">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Min 6 characters" minlength="6">
                        <span class="toggle-password" onclick="togglePassword('password')" title="Show/hide">👁</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirm" name="password_confirm" required placeholder="Re-enter password" minlength="6">
                        <span class="toggle-password" onclick="togglePassword('password_confirm')" title="Show/hide">👁</span>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    <span>➜</span> Update Password
                </button>
            </form>

            <p style="margin-top: 20px; font-size: 0.9rem;">
                <a href="<?= base_url('/') ?>" style="color: #2e7d32;">← Back to Login</a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            var el = document.getElementById(id);
            if (!el) return;
            var type = el.getAttribute('type') === 'password' ? 'text' : 'password';
            el.setAttribute('type', type);
        }
    </script>
</body>
</html>
