<?php
$url = 'https://raw.githubusercontent.com/rootscale/ubigeo-peru/master/ubigeo-peru.json';
$data = file_get_contents($url);
$json = json_decode($data, true);
print_r($json[0]);
print_r($json[100]);
