<?php

declare(strict_types=1);

/**
 * One-time (or repeatable) migration: copy legacy plaintext `users.PaSS` into `password_hash` (bcrypt).
 *
 * Prerequisites: migration 001 applied (`password_hash` column exists).
 *
 * Usage (from inventoryProjBrgy/inventoryProjBrgy):
 *   php tools/migrate_passwords_to_bcrypt.php              # dry-run: list rows that would be updated
 *   php tools/migrate_passwords_to_bcrypt.php --apply      # write bcrypt hashes
 *   php tools/migrate_passwords_to_bcrypt.php --apply --clear-plaintext
 *       # after hashing, set PaSS to empty for migrated rows (recommended)
 *   php tools/migrate_passwords_to_bcrypt.php --clear-plaintext-only
 *       # only clear PaSS where password_hash is already set (no re-hash)
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

if ($clearOnly) {
	$sql = 'SELECT `id`, `UserName` FROM `users`
		WHERE `password_hash` IS NOT NULL AND TRIM(`password_hash`) <> \'\'
		AND `PaSS` IS NOT NULL AND `PaSS` <> \'\'';
	$res = mysqli_query($con, $sql);
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
		echo "  id={$r['id']} UserName={$r['UserName']}\n";
	}

	if (!$apply) {
		echo "\nDry-run. Re-run with --apply --clear-plaintext-only to clear PaSS for these users.\n";
		exit(0);
	}

	$upd = mysqli_prepare($con, 'UPDATE `users` SET `PaSS` = \'\' WHERE `id` = ?');
	if ($upd === false) {
		fwrite(STDERR, mysqli_error($con) . "\n");
		exit(1);
	}
	$n = 0;
	foreach ($rows as $r) {
		$id = (int) $r['id'];
		mysqli_stmt_bind_param($upd, 'i', $id);
		if (mysqli_stmt_execute($upd)) {
			$n += mysqli_affected_rows($con);
		}
	}
	mysqli_stmt_close($upd);
	echo "Cleared PaSS for {$n} user(s).\n";
	exit(0);
}

$sql = 'SELECT `id`, `UserName`, `PaSS`, `password_hash` FROM `users`
	WHERE (`password_hash` IS NULL OR TRIM(`password_hash`) = \'\')
	AND `PaSS` IS NOT NULL AND `PaSS` <> \'\'';

$res = mysqli_query($con, $sql);
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
		// still try clear-only path for users who have hash but leftover PaSS
		echo "Tip: run with --clear-plaintext-only --apply if bcrypt exists but PaSS is not empty.\n";
	}
	exit(0);
}

echo ($apply ? 'Migrating' : 'Would migrate') . ' ' . count($rows) . " user(s) from PaSS → password_hash:\n";
foreach ($rows as $r) {
	echo "  id={$r['id']} UserName={$r['UserName']}\n";
}

if (!$apply) {
	echo "\nDry-run. Re-run with --apply to write bcrypt hashes";
	if ($clearPlaintext) {
		echo " and clear PaSS";
	}
	echo ".\n";
	exit(0);
}

$upd = mysqli_prepare(
	$con,
	'UPDATE `users` SET `password_hash` = ? WHERE `id` = ?'
);
if ($upd === false) {
	fwrite(STDERR, mysqli_error($con) . "\n");
	exit(1);
}

$migrated = 0;
foreach ($rows as $r) {
	$id = (int) $r['id'];
	$plain = (string) $r['PaSS'];
	$hash = password_hash($plain, PASSWORD_BCRYPT);
	if ($hash === false) {
		fwrite(STDERR, "password_hash failed for id={$id}\n");
		continue;
	}
	mysqli_stmt_bind_param($upd, 'si', $hash, $id);
	if (mysqli_stmt_execute($upd)) {
		$migrated += mysqli_affected_rows($con) > 0 ? 1 : 0;
	}
}
mysqli_stmt_close($upd);
echo "Updated password_hash for {$migrated} user(s).\n";

if ($clearPlaintext) {
	$upd2 = mysqli_prepare($con, 'UPDATE `users` SET `PaSS` = \'\' WHERE `id` = ?');
	if ($upd2 === false) {
		fwrite(STDERR, mysqli_error($con) . "\n");
		exit(1);
	}
	$cleared = 0;
	foreach ($rows as $r) {
		$id = (int) $r['id'];
		mysqli_stmt_bind_param($upd2, 'i', $id);
		if (mysqli_stmt_execute($upd2)) {
			$cleared += mysqli_affected_rows($con) > 0 ? 1 : 0;
		}
	}
	mysqli_stmt_close($upd2);
	echo "Cleared PaSS for {$cleared} user(s).\n";
}

exit(0);
