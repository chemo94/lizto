<?php
$dir = 'app/Http/Controllers/Admin';
$files = scandir($dir);

$allStrings = [];

foreach ($files as $file) {
    if (is_dir($dir . '/' . $file)) continue;
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
    
    $path = $dir . '/' . $file;
    $content = file_get_contents($path);
    $originalContent = $content;
    
    // Replace $pageTitle = '...';
    $content = preg_replace_callback("/\\\$pageTitle\s*=\s*['\"]([^'\"]+)['\"];/", function($m) use (&$allStrings) {
        $allStrings[] = $m[1];
        return "\$pageTitle = __('" . addslashes($m[1]) . "');";
    }, $content);
    
    // Replace $notify[] = ['...', '...'];
    $content = preg_replace_callback("/\\\$notify\[\]\s*=\s*\[\s*(['\"][^'\"]+['\"])\s*,\s*['\"]([^'\"]+)['\"]\s*\];/", function($m) use (&$allStrings) {
        $allStrings[] = $m[2];
        return "\$notify[] = [" . $m[1] . ", __('" . addslashes($m[2]) . "')];";
    }, $content);

    // Replace $notification = '...';
    $content = preg_replace_callback("/\\\$notification\s*=\s*['\"]([^'\"]+)['\"];/", function($m) use (&$allStrings) {
        $allStrings[] = $m[1];
        return "\$notification = __('" . addslashes($m[1]) . "');";
    }, $content);
    
    if ($content !== $originalContent) {
        file_put_contents($path, $content);
        echo "Updated $file\n";
    }
}

$allStrings = array_unique($allStrings);
sort($allStrings);

echo json_encode($allStrings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
