<?php

$dir = __DIR__;

$files = array();
do {
    $files[] = $dir . '/autoload.php';
    $files[] = $dir . '/vendor/autoload.php';

    $lastDir = $dir;
    $dir = realpath($dir . '/..');
} while (substr($lastDir, -6) != 'vendor');

foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
        return;
    }
}

echo "ERROR: Could not find auto loader in: " . json_encode($files);
exit(1);