<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$summary = process_pending_events();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['worker' => 'integration_events', 'summary' => $summary], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
