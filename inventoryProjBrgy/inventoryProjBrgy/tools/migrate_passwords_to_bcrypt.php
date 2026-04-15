<?php

declare(strict_types=1);

/**
 * One-time (or repeatable) migration: copy legacy plaintext `users.PaSS` into `password_hash` (bcrypt).
 *
 * Works with:
 *   - Migrated schema (migration 001): `users.id` + `password_hash`
 *   - Legacy schema: PK is `UserName` only — updates use `UserName` (still requires `password_hash` column; run 001 first if missing).
 *
 * Usage (from inventoryProjBrgy/inventoryProjBrgy):
 *   php tools/migrate_passwords_to_bcrypt.php              # dry-run: list rows that would be updated
 *   php tools/migrate_passwords_to_bcrypt.php --apply      # write bcrypt hashes
 *   php tools/migrate_passwords_to_bcrypt.php --apply --clear-plaintext
 *   php tools/migrate_passwords_to_bcrypt.php --clear-plaintext-only
 *   php tools/migrate_passwords_to_bcrypt.php --apply --clear-plaintext-only
 *
 * See migrations/PASSWORD_MIGRATION.md
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only.\n");
	exit(1);
}

$apply = in_array('--apply', $argv, true);
$clearPlaintext = in_array('--clear-plaintext', $argv, true);
$clearOnly = in_array('--clear-plaintext-only', $argv, true);

if ($clearPlaintext && $clearOnly) {
	fwrite(STDERR, "Use either --clear-plaintext (with --apply) or --clear-plaintext-only, not both.\n");
	exit(1);
}

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/env_bootstrap.php';
inv_load_dotenv($root . '/.env.local');
inv_load_dotenv($root . '/.env');
require_once $root . '/dbcon.php';

/** @var mysqli $con */

/**
 * @return 'id'|'UserName'
 */
function users_row_key(mysqli $con): string
{
	$db = $con->query('SELECT DATABASE() AS d');
	if ($db === false) {
		return 'UserName';
	}
	$row = $db->fetch_assoc();
	$db->close();
	$schema = is_array($row) && isset($row['d']) ? (string) $row['d'] : '';
	if ($schema === '') {
		return 'UserName';
	}
	$escSchema = mysqli_real_escape_string($con, $schema);
	$q = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_SCHEMA = '{$escSchema}' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'id'
		LIMIT 1";
	$res = mysqli_query($con, $q);
	$has = $res && mysqli_fetch_row($res);
	if ($res) {
		mysqli_free_result($res);
	}
	return $has ? 'id' : 'UserName';
}

function users_has_password_hash(mysqli $con): bool
{
	$db = $con->query('SELECT DATABASE() AS d');
	if ($db === false) {
		return false;
	}
	$row = $db->fetch_assoc();
	$db->close();
	$schema = is_array($row) && isset($row['d']) ? (string) $row['d'] : '';
	if ($schema === '') {
		return false;
	}
	$escSchema = mysqli_real_escape_string($con, $schema);
	$q = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_SCHEMA = '{$escSchema}' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_hash'
		LIMIT 1";
	$res = mysqli_query($con, $q);
	$has = $res && mysqli_fetch_row($res);
	if ($res) {
		mysqli_free_result($res);
	}
	return (bool) $has;
}

if (!users_has_password_hash($con)) {
	fwrite(STDERR, "Column users.password_hash not found. Run migrations/001_users_role_and_id.sql first.\n");
	exit(1);
}

$key = users_row_key($con);
echo "Using row key: {$key}\n";

if ($clearOnly) {
	$sel = $key === 'id'
		? 'SELECT `id`, `UserName` FROM `users`
			WHERE `password_hash` IS NOT NULL AND TRIM(`password_hash`) <> \'\'
			AND `PaSS` IS NOT NULL AND `PaSS` <> \'\''
		: 'SELECT `UserName` FROM `users`
			WHERE `password_hash` IS NOT NULL AND TRIM(`password_hash`) <> \'\'
			AND `PaSS` IS NOT NULL AND `PaSS` <> \'\'';

	$res = mysqli_query($con, $sel);
	if ($res === false) {
		fwrite(STDERR, mysqli_error($con) . "\n");
		exit(1);
	}
	$rows = [];
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	mysqli_free_result($res);

	if ($rows === []) {
		echo "No rows need plaintext cleared (password_hash set, PaSS empty or already cleared).\n";
		exit(0);
	}

	echo "Rows with bcrypt set but PaSS still non-empty:\n";
	foreach ($rows as $r) {
		if ($key === 'id') {
			echo "  id={$r['id']} UserName={$r['UserName']}\n";
		} else {
			echo "  UserName={$r['UserName']}\n";
		}
	}

	if (!$apply) {
		echo "\nDry-run. Re-run with --apply --clear-plaintext-only to clear PaSS for these users.\n";
		exit(0);
	}

	$upd = $key === 'id'
		? mysqli_prepare($con, 'UPDATE `users` SET `PaSS` = \'\' WHERE `id` = ?')
		: mysqli_prepare($con, 'UPDATE `users` SET `PaSS` = \'\' WHERE `UserName` = ?');
	if ($upd === false) {
		fwrite(STDERR, mysqli_error($con) . "\n");
		exit(1);
	}
	$n = 0;
	foreach ($rows as $r) {
		if ($key === 'id') {
			$id = (int) $r['id'];
			mysqli_stmt_bind_param($upd, 'i', $id);
		} else {
			$un = (string) $r['UserName'];
			mysqli_stmt_bind_param($upd, 's', $un);
		}
		if (mysqli_stmt_execute($upd)) {
			$n += mysqli_affected_rows($con);
		}
	}
	mysqli_stmt_close($upd);
	echo "Cleared PaSS for {$n} user(s).\n";
	exit(0);
}

$sel = $key === 'id'
	? 'SELECT `id`, `UserName`, `PaSS`, `password_hash` FROM `users`
		WHERE (`password_hash` IS NULL OR TRIM(`password_hash`) = \'\')
		AND `PaSS` IS NOT NULL AND `PaSS` <> \'\''
	: 'SELECT `UserName`, `PaSS`, `password_hash` FROM `users`
		WHERE (`password_hash` IS NULL OR TRIM(`password_hash`) = \'\')
		AND `PaSS` IS NOT NULL AND `PaSS` <> \'\'';

$res = mysqli_query($con, $sel);
if ($res === false) {
	fwrite(STDERR, mysqli_error($con) . "\n");
	exit(1);
}

$rows = [];
while ($row = mysqli_fetch_assoc($res)) {
	$rows[] = $row;
}
mysqli_free_result($res);

if ($rows === []) {
	echo "No users need migration (all have password_hash or empty PaSS).\n";
	if ($apply && $clearPlaintext) {
		echo "Tip: run with --clear-plaintext-only --apply if bcrypt exists but PaSS is not empty.\n";
	}
	exit(0);
}

echo ($apply ? 'Migrating' : 'Would migrate') . ' ' . count($rows) . " user(s) from PaSS → password_hash:\n";
foreach ($rows as $r) {
	if ($key === 'id') {
		echo "  id={$r['id']} UserName={$r['UserName']}\n";
	} else {
		echo "  UserName={$r['UserName']}\n";
	}
}

if (!$apply) {
	echo "\nDry-run. Re-run with --apply to write bcrypt hashes";
	if ($clearPlaintext) {
		echo " and clear PaSS";
	}
	echo ".\n";
	exit(0);
}

$upd = $key === 'id'
	? mysqli_prepare($con, 'UPDATE `users` SET `password_hash` = ? WHERE `id` = ?')
	: mysqli_prepare($con, 'UPDATE `users` SET `password_hash` = ? WHERE `UserName` = ?');

if ($upd === false) {
	fwrite(STDERR, mysqli_error($con) . "\n");
	exit(1);
}

$migrated = 0;
foreach ($rows as $r) {
	$plain = (string) $r['PaSS'];
	$hash = password_hash($plain, PASSWORD_BCRYPT);
	if ($hash === false) {
		$label = $key === 'id' ? 'id=' . (int) $r['id'] : 'UserName=' . $r['UserName'];
		fwrite(STDERR, "password_hash failed for {$label}\n");
		continue;
	}
	if ($key === 'id') {
		$id = (int) $r['id'];
		mysqli_stmt_bind_param($upd, 'si', $hash, $id);
	} else {
		$un = (string) $r['UserName'];
		mysqli_stmt_bind_param($upd, 'ss', $hash, $un);
	}
	if (mysqli_stmt_execute($upd)) {
		$migrated += mysqli_affected_rows($con) > 0 ? 1 : 0;
	}
}
mysqli_stmt_close($upd);
echo "Updated password_hash for {$migrated} user(s).\n";

if ($clearPlaintext) {
	$upd2 = $key === 'id'
		? mysqli_prepare($con, 'UPDATE `users` SET `PaSS` = \'\' WHERE `id` = ?')
		: mysqli_prepare($con, 'UPDATE `users` SET `PaSS` = \'\' WHERE `UserName` = ?');
	if ($upd2 === false) {
		fwrite(STDERR, mysqli_error($con) . "\n");
		exit(1);
	}
	$cleared = 0;
	foreach ($rows as $r) {
		if ($key === 'id') {
			$id = (int) $r['id'];
			mysqli_stmt_bind_param($upd2, 'i', $id);
		} else {
			$un = (string) $r['UserName'];
			mysqli_stmt_bind_param($upd2, 's', $un);
		}
		if (mysqli_stmt_execute($upd2)) {
			$cleared += mysqli_affected_rows($con) > 0 ? 1 : 0;
		}
	}
	mysqli_stmt_close($upd2);
	echo "Cleared PaSS for {$cleared} user(s).\n";
}

exit(0);
