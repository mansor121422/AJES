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
    </script>
</body>
</html>
