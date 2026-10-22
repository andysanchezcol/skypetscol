<?php
header('Content-Type: text/plain');
echo PHP_VERSION . ' | ' . PHP_SAPI . "\n";
@unlink(__FILE__);
