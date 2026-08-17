<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/../vendor');
$iterator = new RecursiveIteratorIterator($dir);
$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (stripos($content, 'CustomizationID') !== false) {
            echo "Found in: " . $file->getPathname() . "\n";
            $count++;
            if ($count > 20) {
                break;
            }
        }
    }
}
