<?php

$dir = __DIR__;

do {
    $files = array(
        $dir . '/autoload.php' ,
        $dir . '/vendor/autoload.php'
    );

    foreach ($files as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    $lastDir = $dir;
    $dir = realpath($dir . '/..');
} while (substr($lastDir, -6) != 'vendor');

echo "ERROR: Could not find auto loader in: " . json_encode($files);
exit(1);