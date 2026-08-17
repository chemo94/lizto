<?php
$viewsDir = 'resources/views';
$langFile = 'resources/lang/es.json';

$translations = json_decode(file_get_contents($langFile), true);

$keys = [];

// Function to scan directory
function scan($dir, &$keys) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            scan($path, $keys);
        } else {
            $content = file_get_contents($path);
            // Match @lang('...') or @lang("...")
            preg_match_all("/@lang\(['\"]([^'\"\$]+)['\"]\)/", $content, $matches1);
            // Match __('...') or __("...")
            preg_match_all("/__\(['\"]([^'\"\$]+)['\"]\)/", $content, $matches2);
            
            $keys = array_merge($keys, $matches1[1], $matches2[1]);
        }
    }
}

scan($viewsDir, $keys);
$keys = array_unique($keys);
sort($keys);

$missing = [];
foreach ($keys as $key) {
    if (!isset($translations[$key])) {
        $missing[] = $key;
    }
}

echo json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
