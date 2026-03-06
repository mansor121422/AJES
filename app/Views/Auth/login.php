<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Login - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <style>
        body {
            display: flex;
            height: 100vh;
        }
        .password-success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s ease;
        }
        .password-success-popup {
            background: #fff;
            padding: 28px 36px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            text-align: center;
            min-width: 280px;
            border: 2px solid #4caf50;
        }
        .password-success-text {
            margin: 0 0 20px 0;
            font-size: 1.15rem;
            font-weight: 600;
            color: #2e7d32;
        }
        .password-success-btn {
            background: #4caf50;
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 500;
        }
        .password-success-btn:hover {
            background: #388e3c;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            <h1>Account Login</h1>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="message"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="message success" role="alert"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (service('request')->getGet('password_changed')): ?>
            <div id="password-success-overlay" class="password-success-overlay">
                <div class="password-success-popup">
                    <p class="password-success-text">Password change successfully</p>
                    <button type="button" class="password-success-btn" onclick="closePasswordSuccess()">OK</button>
                </div>
            </div>
            <?php endif; ?>

            <form action="<?= base_url('auth/login') ?>" method="post">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <span class="toggle-password" onclick="togglePassword()">👁</span>
                    </div>
                    <div class="forgot-password">
                        <a href="<?= base_url('auth/forgot-password') ?>">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    <span>➜</span> Log in
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }
        function closePasswordSuccess() {
            var el = document.getElementById('password-success-overlay');
            if (el) el.remove();
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', window.location.pathname);
            }
        }
    </script>
</body>
</html>
