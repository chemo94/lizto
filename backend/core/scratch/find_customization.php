<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/../vendor/greenter');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'CustomizationID') !== false || strpos($content, 'customizationId') !== false) {
            echo "Found in: " . $file->getPathname() . "\n";
            // Print matching lines
            $lines = explode("\n", $content);
            foreach ($lines as $i => $line) {
                if (strpos($line, 'CustomizationID') !== false || strpos($line, 'customizationId') !== false) {
                    echo "  Line " . ($i + 1) . ": " . trim($line) . "\n";
                }
            }
        }
    }
}
