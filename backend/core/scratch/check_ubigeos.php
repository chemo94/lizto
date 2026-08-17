<?php
$js = file_get_contents(__DIR__ . '/../public/assets/js/ubigeos.js');
echo "Length of file: " . strlen($js) . "\n";
if (strpos($js, 'LIMA') !== false) {
    echo "LIMA exists!\n";
} else {
    echo "LIMA DOES NOT exist!\n";
}

if (strpos($js, 'MARTIN') !== false) {
    echo "MARTIN exists!\n";
} else {
    echo "MARTIN DOES NOT exist!\n";
}

if (strpos($js, 'MARTÍN') !== false) {
    echo "MARTÍN exists!\n";
} else {
    echo "MARTÍN DOES NOT exist!\n";
}
