# Step-by-Step: Gmail for Forgot Password (Send reset link to user's email)

This guide shows how to use a **Gmail account** to send the “forgot password” reset link to users. The app will send the email via Gmail’s SMTP using an **App Password** (no need for full Gmail API/OAuth).

---

## Step 1: Use a Gmail account for sending

- Use a **Gmail address** that will be the “sender” of all password-reset emails (e.g. `ajes.crier@gmail.com`).
- You will create an **App Password** for this account (Step 3).  
- **Important:** This account must have **2-Step Verification** turned on (Step 2).

---

## Step 2: Turn on 2-Step Verification

1. Open: **https://myaccount.google.com**
2. Sign in with the Gmail account you chose in Step 1.
3. In the left menu, click **Security**.
4. Under “How you sign in to Google”, click **2-Step Verification**.
5. Click **Get started** and follow the steps (e.g. phone number, code).
6. Finish until 2-Step Verification is **On**.

---

## Step 3: Create a Gmail App Password

1. Go to: **https://myaccount.google.com/apppasswords**  
   - Or: **Google Account** → **Security** → **2-Step Verification** → scroll to **App passwords**.
2. You may need to sign in again.
3. Under “App name”, type: **AJES Forgot Password** (or any name).
4. Click **Create**.
5. Google shows a **16-character password** (like `abcd efgh ijkl mnop`).
6. **Copy this password** and save it somewhere safe.  
   - You will use it in Step 5 as the “SMTP password” (you can paste it with or without spaces).

---

## Step 4: Know your app’s base URL

- Your reset link will look like:  
  `http://localhost/AJES/auth/reset-password/TOKEN`  
  or on a live server:  
  `https://yoursite.com/auth/reset-password/TOKEN`
- Make sure **App/Config/App.php** → `$baseURL` is correct for your environment (local vs production).  
  The forgot-password feature uses this to build the link.

---

## Step 5: Put Gmail settings in `.env`

In your project **root** (same folder as `app`, `writable`, etc.), create or edit a file named **`.env`**.

**Important:** The file must be named **`.env`** (with a dot at the start), not `env`. CodeIgniter only loads `.env`. If you edited the template file `env`, copy it and rename the copy to `.env`, then add the lines below (uncommented).

```env
# Gmail for Forgot Password
EMAIL_PROTOCOL=smtp
EMAIL_FROM=your.gmail@gmail.com
EMAIL_FROM_NAME=AJES CRIER
SMTP_HOST=smtp.gmail.com
SMTP_USER=your.gmail@gmail.com
SMTP_PASS=your-16-char-app-password
SMTP_PORT=587
SMTP_CRYPTO=tls
```

Replace:

- **your.gmail@gmail.com** → the Gmail address from Step 1 (use the same for `EMAIL_FROM` and `SMTP_USER`).
- **your-16-char-app-password** → the 16-character App Password from Step 3 (no spaces is fine, e.g. `abcdefghijklmnop`).
- **EMAIL_FROM_NAME** → any sender name you want (e.g. `AJES CRIER`).

Save the file.  
**Security:** Do **not** commit `.env` to Git. Add `.env` to `.gitignore`.

---

## Step 6: Make sure the app uses the new config

- The app is already set up to use **SMTP** when these `.env` variables are present.
- When a user submits **Forgot Password** with their email (e.g. their Gmail), the app will:
  1. Find the user by that email.
  2. Create a reset token and save it.
  3. Send an email **from** your Gmail (Step 1) **to** the user’s email, with a link like:  
     `{baseURL}auth/reset-password/{token}`  
  4. User clicks the link and sets a new password.

No code change is needed in the forgot-password flow beyond what is already in the project (and the Email config that reads from `.env`).

---

## Step 7: Test Forgot Password

1. Open your site and go to the **Login** page.
2. Click **Forgot Password?** and enter an email that exists in your `users` table (e.g. a test Gmail).
3. Submit the form.
4. Check that user’s Gmail inbox (and spam) for the reset email.
5. Click the link in the email and confirm you can set a new password and log in.

---

## Troubleshooting

| Problem | What to do |
|--------|------------|
| “Username and Password not accepted” | Use the **App Password** from Step 3, not your normal Gmail password. Ensure 2-Step Verification is on. |
| No email received | Check spam/junk. Confirm `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, `SMTP_PORT`, `SMTP_CRYPTO` in `.env`. Check `writable/logs` for errors. |
| “Less secure app” / blocked | Do **not** use “Less secure apps”. Use **2-Step Verification + App Password** as in Steps 2–3. |
| Wrong reset link (e.g. localhost on live) | Fix `$baseURL` in **app/Config/App.php** for the environment you’re using. |

---

## Summary

1. Use one Gmail account for sending.  
2. Turn on 2-Step Verification for that account.  
3. Create an App Password for “AJES Forgot Password”.  
4. Set correct `baseURL` in the app config.  
5. Add the Gmail SMTP settings to `.env`.  
6. Test Forgot Password with a real user email (e.g. Gmail).  

After this, when a user (with a Gmail or any email) uses Forgot Password, the reset message will be sent through your Gmail and they can reset their password from the link in that email.
