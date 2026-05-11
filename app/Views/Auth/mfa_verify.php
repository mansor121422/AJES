<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity - AJES</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
    <?php
        $flashError   = session()->getFlashdata('error');
        $flashSuccess = session()->getFlashdata('success');
        $devCode      = session()->getFlashdata('dev_mfa_code');
    ?>
    <style>
        body.mfa-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        }
        .mfa-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(27,94,32,0.15);
            padding: 40px 36px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .mfa-card h1 {
            font-size: 1.4rem;
            color: #1b5e20;
            margin: 0 0 8px;
        }
        .mfa-card p {
            color: #555;
            font-size: 0.95rem;
            margin: 0 0 24px;
            line-height: 1.5;
        }
        .mfa-card .mfa-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }
        .mfa-code-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .mfa-code-inputs input {
            width: 46px;
            height: 54px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid #c8e6c9;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.2s;
            color: #1b5e20;
        }
        .mfa-code-inputs input:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46,125,50,0.15);
        }
        .mfa-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .mfa-submit:hover { opacity: 0.9; }
        .mfa-alert {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .mfa-alert.error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .mfa-alert.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .mfa-alert.dev { background: #fff8e1; color: #1b5e20; border: 1px solid #ffe082; font-family: monospace; font-size: 1.3rem; letter-spacing: 0.3em; }
        .mfa-resend {
            margin-top: 16px;
            font-size: 0.85rem;
            color: #888;
        }
        .mfa-resend a {
            color: #2e7d32;
            text-decoration: underline;
            cursor: pointer;
        }
        .mfa-hidden-input { position: absolute; left: -9999px; }
    </style>
</head>
<body class="mfa-page">
    <div class="mfa-card">
        <div class="mfa-icon">🔐</div>
        <h1>Two-Factor Authentication</h1>
        <p>A 6-digit verification code has been sent to your email. Enter it below to continue.</p>

        <?php if ($flashError): ?>
            <div class="mfa-alert error"><?= esc($flashError) ?></div>
        <?php endif; ?>
        <?php if ($flashSuccess): ?>
            <div class="mfa-alert success"><?= esc($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($devCode): ?>
            <div class="mfa-alert dev">DEV CODE: <?= esc($devCode) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('auth/mfa/verify') ?>" method="post" id="mfa-form">
            <?= csrf_field() ?>
            <input type="hidden" name="mfa_code" id="mfa-hidden" class="mfa-hidden-input" maxlength="6">

            <div class="mfa-code-inputs" id="mfa-inputs">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" autofocus>
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            </div>

            <button type="submit" class="mfa-submit">Verify</button>
        </form>

        <div class="mfa-resend">
            <a href="<?= base_url('auth/mfa/resend') ?>">Resend code</a>
            &nbsp;|&nbsp;
            <a href="<?= base_url('auth/logout') ?>">Cancel</a>
        </div>
    </div>

    <script>
    (function() {
        var inputs = document.querySelectorAll('#mfa-inputs input');
        var hidden = document.getElementById('mfa-hidden');
        var form = document.getElementById('mfa-form');

        function collectCode() {
            var code = '';
            inputs.forEach(function(inp) { code += inp.value; });
            hidden.value = code;
        }

        inputs.forEach(function(inp, i) {
            inp.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 1);
                if (this.value && i < inputs.length - 1) {
                    inputs[i + 1].focus();
                }
                collectCode();
                if (i === inputs.length - 1 && hidden.value.length === 6) {
                    form.submit();
                }
            });
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && i > 0) {
                    inputs[i - 1].focus();
                }
            });
            inp.addEventListener('paste', function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                for (var j = 0; j < pasted.length && (i + j) < inputs.length; j++) {
                    inputs[i + j].value = pasted[j];
                }
                collectCode();
                var nextIdx = Math.min(i + pasted.length, inputs.length - 1);
                inputs[nextIdx].focus();
                if (hidden.value.length === 6) form.submit();
            });
        });

        form.addEventListener('submit', function() { collectCode(); });
    })();
    </script>
</body>
</html>
