<?php
require_once __DIR__ . '/session_init.php';
chdir(__DIR__);
inv_session_start();

// Already logged in → go to dashboard (relative path)
if (isset($_SESSION['user'])) {
    header('Location: brgy.php', true, 302);
    exit;
}

$login_error = '';

if (isset($_POST['btn-send'])) {
    include __DIR__ . '/dbcon.php';

    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');
    $esc  = mysqli_real_escape_string($con, $user);

    $q = mysqli_query($con, "SELECT * FROM users WHERE UserName='$esc'");

    if ($q === false) {
        $login_error = 'Could not verify login. Please try again.';
    } else {
        $r = mysqli_fetch_assoc($q);

        if (is_array($r)) {
            // Try bcrypt first (new accounts with password_hash column)
            $bcrypt_ok = !empty($r['password_hash'])
                && function_exists('password_verify')
                && password_verify($pass, (string)$r['password_hash']);

            // Fall back to legacy plaintext PaSS column
            $legacy_ok = isset($r['PaSS'])
                && (string)$r['PaSS'] !== ''
                && (string)$r['PaSS'] === $pass;

            if ($bcrypt_ok || $legacy_ok) {
                $_SESSION['user']    = (string)$r['UserName'];
                $_SESSION['role']    = isset($r['role']) ? (string)$r['role'] : 'staff';
                $_SESSION['user_id'] = isset($r['id'])  ? (int)$r['id']       : 0;

                // Relative redirect — works in any subfolder
                header('Location: brgy.php', true, 302);
                exit;
            }
        }

        $login_error = 'Invalid username or password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Barangay System</title>
<style>
  body {
    font-family: Calibri, Arial, sans-serif;
    background: #f0f2f5;
    margin: 0; padding: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .card {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.12);
    padding: 40px 36px 32px;
    width: 100%;
    max-width: 360px;
    box-sizing: border-box;
  }
  .card-title {
    text-align: center;
    font-size: 13px;
    color: #555;
    margin: 0 0 6px 0;
  }
  .card-heading {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    color: #2c3e50;
    margin: 0 0 24px 0;
  }
  .field { margin-bottom: 16px; }
  .field label {
    display: block;
    font-size: 13px;
    color: #444;
    margin-bottom: 5px;
    font-weight: 600;
  }
  .field input {
    display: block;
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    background: #f9f9f9;
    color: #333;
    outline: none;
  }
  .field input:focus {
    border-color: #4a90d9;
    background: #fff;
  }
  .btn-login {
    display: block;
    width: 100%;
    padding: 11px;
    margin-top: 8px;
    background: #2e6da4;
    color: #fff;
    font-size: 15px;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    box-sizing: border-box;
  }
  .btn-login:hover { background: #245a8a; }
  .error {
    background: #fdecea;
    color: #c0392b;
    border: 1px solid #f5c6cb;
    border-radius: 5px;
    padding: 9px 12px;
    font-size: 13px;
    margin-bottom: 14px;
    text-align: center;
  }
</style>
</head>
<body>
<div class="card">
  <p class="card-title">Barangay Inventory &amp; Monitoring System</p>
  <h2 class="card-heading">Staff Login</h2>

  <?php if ($login_error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="field">
      <label for="user">Username</label>
      <input type="text" id="user" name="user"
             value="<?php echo htmlspecialchars($_POST['user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             placeholder="Enter username" required autofocus>
    </div>
    <div class="field">
      <label for="pass">Password</label>
      <input type="password" id="pass" name="pass"
             placeholder="Enter password" required>
    </div>
    <button type="submit" class="btn-login" name="btn-send">Login</button>
  </form>
</div>
</body>
</html>
