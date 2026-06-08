<?php
define('SECRET', '109d0170d4890e2e08490971e3f6518ae143d9191a33ea48');

$sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$body = file_get_contents('php://input');

if (!hash_equals('sha256=' . hash_hmac('sha256', $body, SECRET), $sig)) {
    http_response_code(403);
    exit('Forbidden');
}

$repo   = '/home1/eadcbcam/skypetscol';
$deploy = '/home1/eadcbcam/public_html';

$out  = shell_exec("cd $repo && git fetch origin && git reset --hard origin/main 2>&1");
$out .= shell_exec("/bin/cp -Ra $repo/skypets-project/. $deploy/ 2>&1");

file_put_contents('/tmp/deploy-log.txt', date('Y-m-d H:i:s') . "\n" . $out . "\n---\n", FILE_APPEND);

http_response_code(200);
echo $out;
