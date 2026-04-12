<?php
declare(strict_types=1);

if (!function_exists('barangay')) {
	function barangay(): void
	{
		include __DIR__ . '/dbcon.php';
		$s = mysqli_query($con, 'SELECT * FROM barangays');
		if ($s === false) {
			return;
		}
		while ($row = mysqli_fetch_assoc($s)) {
			if (!isset($row['brgy'])) {
				continue;
			}
			$x = (string) $row['brgy'];
			echo '<tr><td><a href="patients.php?loc=' . htmlspecialchars($x, ENT_QUOTES, 'UTF-8') . '">'
				. htmlspecialchars($x, ENT_QUOTES, 'UTF-8') . '</a><br></td></tr>';
		}
	}
}

if (!function_exists('barangay2')) {
	function barangay2(): void
	{
		include __DIR__ . '/dbcon.php';
		$s = mysqli_query($con, 'SELECT * FROM barangays');
		if ($s === false) {
			return;
		}
		while ($row = mysqli_fetch_assoc($s)) {
			if (!isset($row['brgy'])) {
				continue;
			}
			$x = (string) $row['brgy'];
			echo '<tr><td><a href="history4.php?loc=' . htmlspecialchars($x, ENT_QUOTES, 'UTF-8') . '">'
				. htmlspecialchars($x, ENT_QUOTES, 'UTF-8') . '</a><br></td></tr>';
		}
	}
}
