<?php
// PHP script to download and generate Peruvian ubigeo JS data using MB_STRTOUPPER for correct accents conversion
$url = 'https://raw.githubusercontent.com/rootscale/ubigeo-peru/master/ubigeo-peru.json';
echo "Downloading ubigeo data from $url...\n";
$data = file_get_contents($url);

if ($data) {
    $json = json_decode($data, true);
    
    // Step 1: Map codes to names
    $deps = [];
    $provs = []; // keyed by "dep_code.prov_code"
    
    // First pass: extract departments and provinces
    foreach ($json as $item) {
        $depCode = $item['departamento'];
        $provCode = $item['provincia'];
        $distCode = $item['distrito'];
        $name = trim($item['nombre']);
        
        if ($provCode === '00' && $distCode === '00') {
            $deps[$depCode] = mb_strtoupper($name, 'UTF-8');
        } elseif ($provCode !== '00' && $distCode === '00') {
            $provs["$depCode.$provCode"] = mb_strtoupper($name, 'UTF-8');
        }
    }
    
    // Second pass: build structure of names
    $structure = [];
    foreach ($json as $item) {
        $depCode = $item['departamento'];
        $provCode = $item['provincia'];
        $distCode = $item['distrito'];
        $name = trim($item['nombre']);
        
        if ($provCode !== '00' && $distCode !== '00') {
            $depName = $deps[$depCode] ?? null;
            $provName = $provs["$depCode.$provCode"] ?? null;
            $distName = mb_strtoupper($name, 'UTF-8');
            
            if ($depName && $provName) {
                if (!isset($structure[$depName])) {
                    $structure[$depName] = [];
                }
                if (!isset($structure[$depName][$provName])) {
                    $structure[$depName][$provName] = [];
                }
                $ubigeoCode = $depCode . $provCode . $distCode;
                $structure[$depName][$provName][$distName] = $ubigeoCode;
            }
        }
    }
    
    // Sort keys alphabetically
    ksort($structure);
    foreach ($structure as $dep => &$provinces) {
        ksort($provinces);
        foreach ($provinces as $prov => &$districts) {
            ksort($districts);
        }
    }
    
    $jsContent = "const PERU_UBIGEOS = " . json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";\n";
    
    @mkdir(__DIR__ . '/../public/assets/js', 0755, true);
    file_put_contents(__DIR__ . '/../public/assets/js/ubigeos.js', $jsContent);
    echo "Successfully generated public/assets/js/ubigeos.js with capitalized accents!\n";
} else {
    echo "Failed to download ubigeo data.\n";
}
