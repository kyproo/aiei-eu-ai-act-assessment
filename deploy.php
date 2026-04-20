<?php
// deploy.php — GitHub webhook auto-deploy
// Add this as webhook in GitHub: https://baltumsoft.com/assessment/deploy.php

$secret = 'aiei_deploy_2026';
$deployDir = __DIR__;
$logFile = $deployDir . '/deploy.log';

function log_msg($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

// Verify GitHub signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    log_msg('ERROR: Invalid signature');
    die('Forbidden');
}

$data = json_decode($payload, true);
$branch = $data['ref'] ?? '';

if ($branch !== 'refs/heads/main') {
    log_msg("Skipped: branch $branch");
    die('Not main branch');
}

// Pull latest
$output = shell_exec('cd ' . escapeshellarg($deployDir) . ' && git pull origin main 2>&1');
log_msg("Deploy: $output");

http_response_code(200);
echo 'OK';
