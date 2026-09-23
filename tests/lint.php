<?php

$paths = [dirname(__DIR__).'/src', __DIR__];
$status = 0;

foreach ($paths as $path) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            passthru(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()), $result);
            $status = max($status, $result);
        }
    }
}

exit($status);
