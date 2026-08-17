<?php
$dir = 'app/Http/Controllers/Admin';
$files = scandir($dir);

$results = [];

foreach ($files as $file) {
    if (is_dir($dir . '/' . $file)) continue;
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
    
    $content = file_get_contents($dir . '/' . $file);
    
    // Find $pageTitle = '...';
    preg_match_all("/\\\$pageTitle\s*=\s*['\"]([^'\"]+)['\"];/", $content, $matchesTitle);
    
    // Find $notify[] = ['...', '...'];
    preg_match_all("/\\\$notify\[\]\s*=\s*\[\s*['\"][^'\"]+['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\];/", $content, $matchesNotify);

    // Find $notification = '...';
    preg_match_all("/\\\$notification\s*=\s*['\"]([^'\"]+)['\"];/", $content, $matchesNotifVar);
    
    $results[$file] = [
        'titles' => $matchesTitle[1],
        'notifies' => array_merge($matchesNotify[1], $matchesNotifVar[1])
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
