<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Login - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <?php
        $showForgot     = service('request')->getGet('forgot') === '1';
        $flashError     = session()->getFlashdata('error');
        $flashSuccess   = session()->getFlashdata('success');
        $flashDevLink   = session()->getFlashdata('dev_reset_link');
    ?>
    <style>
        /*
          Split layout: logo strip absolutely positioned.
          Login: logo left (left:0), green form right (margin-left 35%).
          Forgot: logo slides to the RIGHT (left:65%), green form moves to the LEFT (margin-left 0).
        */
        body.login-page {
            position: relative;
            min-height: 100vh;
            height: 100vh;
            overflow-x: hidden;
        }

        body.login-page .left-panel-track {
            position: absolute;
            left: 0;
            top: 0;
            width: 35%;
            height: 100%;
            min-height: 100vh;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 2;
            box-sizing: border-box;
            transition: left 0.85s cubic-bezier(0.45, 0.05, 0.2, 1);
        }

        body.login-page.login-page--forgot .left-panel-track {
            left: 65%;
        }

        body.login-page .left-panel {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 100%;
            box-sizing: border-box;
        }

        body.login-page .right-panel {
            position: relative;
            margin-left: 35%;
            width: 65%;
            min-height: 100vh;
            box-sizing: border-box;
            transition:
                margin-left 0.85s cubic-bezier(0.45, 0.05, 0.2, 1),
                width 0.85s cubic-bezier(0.45, 0.05, 0.2, 1);
        }

        body.login-page.login-page--forgot .right-panel {
            margin-left: 0;
            width: 65%;
        }

        body.login-page .auth-global-flash {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 20;
            padding: 11px 14px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            line-height: 1.35;
        }

        body.login-page .auth-global-flash.error {
            background: rgba(198, 40, 40, 0.95);
            color: #fff;
        }

        body.login-page .auth-global-flash.success {
            background: rgba(46, 125, 50, 0.95);
            color: #fff;
        }

        body.login-page .right-panel.has-global-flash .login-forms-root {
            padding-top: 46px;
        }

        body.login-page .login-forms-root {
            position: relative;
            width: 100%;
            max-width: 460px;
            margin: 0 auto;
            min-height: 280px;
        }

        body.login-page .login-form-view {
            width: 100%;
            transition:
                opacity 0.55s cubic-bezier(0.45, 0.05, 0.2, 1) 0.12s,
                transform 0.75s cubic-bezier(0.45, 0.05, 0.2, 1);
        }

        body.login-page .login-form-view--forgot {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
        }

        body.login-page:not(.login-page--forgot) .login-form-view--forgot {
            opacity: 0;
            pointer-events: none;
            transform: translateX(18px);
        }

        body.login-page.login-page--forgot .login-form-view--login {
            opacity: 0;
            pointer-events: none;
            transform: translateX(-18px);
        }

        body.login-page:not(.login-page--forgot) .login-form-view--login,
        body.login-page.login-page--forgot .login-form-view--forgot {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0);
        }

        body.login-page .auth-dev-link {
            margin-bottom: 14px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(255, 248, 225, 0.95);
            border: 1px solid #ffe082;
            font-size: 13px;
            color: #1b5e20;
            word-break: break-all;
        }

        body.login-page .auth-dev-link a {
            color: #2e7d32;
        }

        body.login-page .auth-link-btn {
            display: inline;
            margin: 0;
            padding: 0;
            border: none;
            background: none;
            color: #c8e6c9;
            font-size: 14px;
            cursor: pointer;
            text-decoration: underline;
            font-family: inherit;
        }

        body.login-page .auth-link-btn:hover {
            color: #fff;
        }

        body.login-page .forgot-back-wrap {
            margin-top: 18px;
            text-align: center;
        }

        @media (prefers-reduced-motion: reduce) {
            body.login-page .left-panel-track,
            body.login-page .right-panel,
            body.login-page .login-form-view {
                transition: none !important;
            }
        }

        @media (max-width: 720px) {
            body.login-page {
                height: auto;
            }

            body.login-page .left-panel-track {
                position: relative;
                left: auto !important;
                width: 100%;
                height: auto;
                min-height: 200px;
            }

            body.login-page.login-page--forgot .left-panel-track {
                display: none;
            }

            body.login-page .right-panel {
                margin-left: 0 !important;
                width: 100% !important;
            }

            body.login-page .login-form-view--forgot {
                position: relative;
            }

            body.login-page:not(.login-page--forgot) .login-form-view--forgot,
            body.login-page.login-page--forgot .login-form-view--login {
                display: none;
            }

            body.login-page:not(.login-page--forgot) .login-form-view--login,
            body.login-page.login-page--forgot .login-form-view--forgot {
                opacity: 1 !important;
                transform: none !important;
                pointer-events: auto !important;
            }
        }

        .password-success-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: loginFadeIn 0.2s ease;
        }

        .password-success-popup {
            background: #fff;
            padding: 28px 36px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
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

        @keyframes loginFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Log in submit — quick “lift & dissolve” before redirect to dashboard */
        @keyframes loginExitHero {
            to {
                opacity: 0;
                transform: scale(0.96) translateY(-14px);
                filter: blur(10px);
            }
        }
        body.login-page.login-exit-anim .right-panel,
        body.login-page.login-exit-anim .left-panel-track {
            animation: loginExitHero 0.45s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        body.login-page.login-exit-anim {
            pointer-events: none;
        }
        @media (prefers-reduced-motion: reduce) {
            body.login-page.login-exit-anim .right-panel,
            body.login-page.login-exit-anim .left-panel-track {
                animation: none;
                opacity: 0.75;
            }
        }
    </style>
</head>
<body class="login-page<?= $showForgot ? ' login-page--forgot' : '' ?>">
    <div class="left-panel-track">
        <div class="left-panel">
            <div class="logo-container">
                <img src="/AJES/public/assets/images/ajes-logo.png" alt="AJES Logo" class="logo">
            </div>
        </div>
    </div>

    <div class="right-panel<?= ($flashError !== null && $flashError !== '') || ($flashSuccess !== null && $flashSuccess !== '') ? ' has-global-flash' : '' ?>">
        <?php if ($flashError !== null && $flashError !== ''): ?>
            <div class="auth-global-flash error" role="alert"><?= esc($flashError) ?></div>
        <?php elseif ($flashSuccess !== null && $flashSuccess !== ''): ?>
            <div class="auth-global-flash success" role="status"><?= esc($flashSuccess) ?></div>
        <?php endif; ?>

        <div class="login-forms-root">
            <div class="login-form-view login-form-view--login">
                <div class="login-container">
                    <h1>Account Login</h1>

                    <?php if (service('request')->getGet('password_changed')): ?>
                    <div id="password-success-overlay" class="password-success-overlay">
                        <div class="password-success-popup">
                            <p class="password-success-text">Password change successfully</p>
                            <button type="button" class="password-success-btn" onclick="closePasswordSuccess()">OK</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form id="form-login" action="<?= base_url('auth/login') ?>" method="post" autocomplete="on">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required autocomplete="username" value="<?= esc(old('username')) ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" required autocomplete="current-password">
                                <span class="toggle-password" onclick="togglePassword()" role="button" tabindex="0">👁</span>
                            </div>
                            <div class="forgot-password">
                                <button type="button" class="auth-link-btn" id="btnForgotInline">Forgot Password?</button>
                            </div>
                        </div>

                        <button type="submit" class="login-button">
                            <span>➜</span> Log in
                        </button>
                    </form>
                </div>
            </div>

            <div class="login-form-view login-form-view--forgot">
                <div class="login-container">
                    <h1>Forgot password</h1>

                    <?php if ($flashDevLink): ?>
                        <div class="auth-dev-link">
                            Temporary reset link (local dev):<br>
                            <a href="<?= esc((string) $flashDevLink) ?>"><?= esc((string) $flashDevLink) ?></a>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('auth/forgot-password') ?>" method="post" autocomplete="on">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required autocomplete="email" value="<?= esc(old('email')) ?>">
                        </div>

                        <button type="submit" class="login-button">
                            <span>📩</span> Send reset link
                        </button>
                    </form>

                    <div class="forgot-back-wrap">
                        <button type="button" class="auth-link-btn" id="btnBackLogin">Back to login</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var el = document.getElementById('password');
            if (!el) return;
            el.setAttribute('type', el.getAttribute('type') === 'password' ? 'text' : 'password');
        }

        function closePasswordSuccess() {
            var o = document.getElementById('password-success-overlay');
            if (o) o.remove();
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', window.location.pathname);
            }
        }

        function setAuthForgot(forgot) {
            document.body.classList.toggle('login-page--forgot', forgot);
            try {
                var path = window.location.pathname.split('?')[0];
                window.history.replaceState(null, '', path + (forgot ? '?forgot=1' : ''));
            } catch (e) {}
        }

        (function () {
            function bind(id, fn) {
                var n = document.getElementById(id);
                if (n) n.addEventListener('click', fn);
            }
            bind('btnForgotInline', function () { setAuthForgot(true); });
            bind('btnBackLogin', function () { setAuthForgot(false); });
        })();

        (function () {
            var form = document.getElementById('form-login');
            if (!form) return;
            form.addEventListener('submit', function (e) {
                if (form.getAttribute('data-ajes-exit') === '1') return;
                e.preventDefault();
                form.setAttribute('data-ajes-exit', '1');
                document.body.classList.add('login-exit-anim');
                var ms = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 60 : 420;
                setTimeout(function () { form.submit(); }, ms);
            });
        })();
    </script>
</body>
</html>
