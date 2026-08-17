<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/../vendor/greenter');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (stripos($content, 'FormaPago') !== false) {
            echo "Found in: " . $file->getPathname() . "\n";
            $lines = explode("\n", $content);
            foreach ($lines as $i => $line) {
                if (stripos($line, 'FormaPago') !== false) {
                    echo "  Line " . ($i + 1) . ": " . trim($line) . "\n";
                }
            }
        }
    }
}
