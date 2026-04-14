<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
echo "ok php-apache\n";
echo "app_marker=brgy_no_actions_v1\n";
$commit = getenv('GIT_COMMIT');
if ($commit !== false && $commit !== '') {
	echo 'git_commit=' . $commit . "\n";
}
