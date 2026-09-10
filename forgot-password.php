<?php
$resetEmail = strtolower(trim((string) ($_GET['email'] ?? '')));
$resetToken = (string) ($_GET['token'] ?? '');
$status = trim((string) ($_GET['status'] ?? ''));
$usersFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'users.json';

function readPasswordUsers(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $users = json_decode((string) file_get_contents($file), true);
    return is_array($users) ? $users : [];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset your password | Cooper Fox Realty</title>
    <link rel="stylesheet" href="cooper-fox-stylesheet.css" />
    <style>
      body {
        margin: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, #edf3f1 0%, #f6f1ea 100%);
        font-family: Arial, sans-serif;
        color: #132621;
      }
      .reset-shell {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 32px 16px;
      }
      .reset-card {
        width: min(100%, 520px);
        background: rgba(255,255,255,0.98);
        border: 1px solid rgba(18,32,29,0.08);
        border-radius: 20px;
        box-shadow: 0 24px 58px rgba(11, 26, 24, 0.1);
        padding: 28px 24px;
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
        font-weight: 700;
      }
      .brand-mark {
        display: grid;
        place-items: center;
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: linear-gradient(135deg, #d8bd84, #f0e3c2);
        color: #132621;
      }
      h1 {
        margin: 0 0 10px;
        font-size: clamp(2rem, 4vw, 2.5rem);
      }
      p {
        line-height: 1.6;
        color: #4f615d;
      }
      .field {
        display: grid;
        gap: 8px;
        margin-top: 18px;
      }
      label {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #52635e;
      }
      input {
        width: 100%;
        box-sizing: border-box;
        height: 52px;
        border: 1px solid #d7e3e0;
        border-radius: 12px;
        padding: 0 14px;
        font-size: 1rem;
      }
      .submit-btn {
        margin-top: 18px;
        width: 100%;
        height: 52px;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #102722, #1f4d46);
        color: white;
        font-weight: 700;
        cursor: pointer;
      }
      .success-box {
        margin-top: 18px;
        padding: 14px 16px;
        background: #ecf9f5;
        border: 1px solid #bfe5d8;
        border-radius: 12px;
        color: #0d7a5f;
        font-weight: 600;
      }
      .link-box {
        margin-top: 18px;
        display: inline-block;
        padding: 12px 16px;
        border-radius: 999px;
        background: #102722;
        color: white;
        text-decoration: none;
        font-weight: 700;
      }
      .footer-link {
        margin-top: 18px;
        font-size: 0.9rem;
      }
      .footer-link a {
        color: #123d36;
        text-decoration: none;
        font-weight: 700;
      }
    </style>
  </head>
  <body>
    <main class="reset-shell">
      <section class="reset-card">
        <div class="brand">
          <span class="brand-mark">CF</span>
          <span>Cooper Fox Realty</span>
        </div>

        <?php if ($resetEmail !== '' && $resetToken !== '') : ?>
          <h1>Reset link ready</h1>
          <p>Use the secure link below to set a new password for <strong><?php echo htmlspecialchars($resetEmail, ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
          <div class="success-box"><?php echo htmlspecialchars($status !== '' ? $status : 'Reset link created. Use the link below to continue.', ENT_QUOTES, 'UTF-8'); ?></div>
          <a class="link-box" href="reset-password.php?email=<?php echo rawurlencode($resetEmail); ?>&token=<?php echo rawurlencode($resetToken); ?>">Reset my password</a>
          <p class="footer-link"><a href="SignUp.html#signin">Back to sign in</a></p>
        <?php else : ?>
          <h1>Forgot password?</h1>
          <p>Enter the email on your account and we will generate a private reset link that lets you choose a new password.</p>
          <form method="post" action="mail.php">
            <input type="hidden" name="Action" value="password_reset_request" />
            <div class="field">
              <label for="resetEmail">Email</label>
              <input id="resetEmail" name="Email" type="email" placeholder="you@example.com" required />
            </div>
            <button class="submit-btn" type="submit">Send reset link</button>
          </form>
          <p class="footer-link"><a href="SignUp.html#signin">Back to sign in</a></p>
        <?php endif; ?>
      </section>
    </main>
  </body>
</html>
