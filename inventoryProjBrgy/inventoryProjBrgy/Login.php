<?php
require_once __DIR__ . '/session_init.php';
inv_session_start();

$login_error = '';
if (isset($_POST['btn-send'])) {
	include __DIR__ . '/dbcon.php';
	$user = trim((string)($_POST['user'] ?? ''));
	$pass = (string)($_POST['pass'] ?? '');
	$esc = mysqli_real_escape_string($con, $user);
	$q = mysqli_query($con, "SELECT * FROM users WHERE UserName='$esc'");
	if ($q === false) {
		$login_error = 'Could not verify login. Please try again.';
	} else {
		$r = mysqli_fetch_assoc($q);
		if (is_array($r) && (string) $r['PaSS'] === $pass) {
			$_SESSION['user'] = $user;
			header('Location: brgy.php', true, 302);
			exit;
		}
		$login_error = 'Invalid username or password.';
	}
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Untitled</title>
    <link rel="stylesheet" href="assets2/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets2/css/styles.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>  
    <header>
        <p class="text-center" id="head">Medical Inventory &amp; Monitoring Database System</p>
    </header>
    <?php if ($login_error !== '') { ?>
        <p class="text-center" style="color:#ffb4b4;"><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>
    <div>
        <form method="POST">
            <p>Username </p>
            <input class="form-control" type="text" name="user">
            <p>Password </p>
            <input class="form-control" type="text" name="pass">
            <button type="submit" class="btn btn-default" name="btn-send">Login </button>
        </form>
    </div><img src="assets/img/doh_logo.png">
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>
