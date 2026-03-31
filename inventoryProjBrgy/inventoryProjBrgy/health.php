<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
echo "ok php-apache\n";
$commit = getenv('RENDER_GIT_COMMIT');
if ($commit !== false && $commit !== '') {
	echo 'render_git_commit=' . $commit . "\n";
}
