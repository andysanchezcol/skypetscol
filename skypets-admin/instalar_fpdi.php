<?php
chdir(__DIR__);
echo "<pre>";
$home = '/home1/eadcbcam';
$composerHome = $home . '/.composer_tmp';
@mkdir($composerHome, 0755, true);
if (!file_exists('/tmp/composer.phar')) {
    file_put_contents('/tmp/composer.phar', file_get_contents('https://getcomposer.org/composer-stable.phar'));
}
echo shell_exec("HOME={$home} COMPOSER_HOME={$composerHome} php /tmp/composer.phar require setasign/fpdf setasign/fpdi 2>&1");
echo "</pre>";
