<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'mail';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = '';

    /**
     * Which SMTP authentication method to use: login, plain
     */
    public string $SMTPAuthMethod = 'login';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 25;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 5;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Load Gmail/SMTP settings from environment when set (e.g. .env).
     * Used for Forgot Password to send reset link via Gmail.
     */
    public function __construct()
    {
        parent::__construct();
        $from = env('EMAIL_FROM', '');
        $fromName = env('EMAIL_FROM_NAME', '');
        $smtpHost = env('SMTP_HOST', '');
        $smtpUser = (string) env('SMTP_USER', '');
        if ($from !== '') {
            $this->fromEmail = $from;
        }
        if ($fromName !== '') {
            $this->fromName = $fromName;
        }
        if ($smtpHost !== '') {
            $smtpPass = (string) env('SMTP_PASS', '');
            // Gmail App Password is 16 chars and sometimes copied with spaces.
            // Normalize it so auth won't fail due to formatting.
            $smtpPass = str_replace(' ', '', trim($smtpPass));

            $this->protocol   = env('EMAIL_PROTOCOL', 'smtp') ?: 'smtp';
            $this->SMTPHost   = $smtpHost;
            $this->SMTPUser   = $smtpUser;
            $this->SMTPPass   = $smtpPass;
            $this->SMTPPort   = (int) (env('SMTP_PORT', 587) ?: 587);
            $this->SMTPCrypto = (string) (env('SMTP_CRYPTO', 'tls') ?: 'tls');
            $this->SMTPAuthMethod = (string) (env('SMTP_AUTH_METHOD', 'login') ?: 'login');
            $this->SMTPTimeout = (int) (env('SMTP_TIMEOUT', 15) ?: 15);
        }

        // Prevent "Cannot send mail with no From header" on forgot password.
        if ($this->fromEmail === '' && $smtpUser !== '') {
            $this->fromEmail = $smtpUser;
        }
        if ($this->fromName === '') {
            $this->fromName = 'AJES CRIER';
        }
    }

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'text';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;
}
