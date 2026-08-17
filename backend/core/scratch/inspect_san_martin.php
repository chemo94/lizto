<?php
$js = file_get_contents(__DIR__ . '/../public/assets/js/ubigeos.js');
$jsonText = substr($js, strpos($js, '{'));
$jsonText = rtrim($jsonText, ";\n\r");
$data = json_decode($jsonText, true);

if (isset($data['SAN MARTÍN'])) {
    echo "SAN MARTÍN department exists!\n";
    echo "Provinces under SAN MARTÍN:\n";
    print_r(array_keys($data['SAN MARTÍN']));
    
    if (isset($data['SAN MARTÍN']['SAN MARTÍN'])) {
        echo "SAN MARTÍN province under SAN MARTÍN department exists!\n";
        echo "Districts:\n";
        print_r($data['SAN MARTÍN']['SAN MARTÍN']);
    } else {
        echo "SAN MARTÍN province DOES NOT exist under SAN MARTÍN department!\n";
    }
} else {
    echo "SAN MARTÍN department DOES NOT exist!\n";
}
