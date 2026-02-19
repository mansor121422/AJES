<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Login - AJES</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
        }

        .left-panel {
            width: 35%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .logo-container {
            text-align: center;
        }

        .logo {
            width: 250px;
            height: 250px;
        }

        .right-panel {
            width: 65%;
            background: linear-gradient(135deg, #8bc34a 0%, #2d5f3a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .login-container {
            width: 100%;
            max-width: 460px;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 40px;
            color: #f9d71c;
            text-align: center;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #ffffff;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(249, 215, 28, 0.3);
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #f9d71c;
            box-shadow: 0 4px 12px rgba(249, 215, 28, 0.5);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .forgot-password {
            text-align: right;
            margin-top: 8px;
        }

        .forgot-password a {
            color: #f9d71c;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            color: #ffffff;
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background-color: #f9d71c;
            color: #2d5f3a;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-button:hover {
            background-color: #e6c519;
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
