<?php
$js = file_get_contents(__DIR__ . '/../public/assets/js/ubigeos.js');
// Extract the JSON part from: const PERU_UBIGEOS = { ... };
$jsonText = substr($js, strpos($js, '{'));
$jsonText = rtrim($jsonText, ";\n\r");
$data = json_decode($jsonText, true);
echo "Departments:\n";
print_r(array_keys($data));
